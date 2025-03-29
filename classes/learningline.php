<?php
// This file is part of the Studyplan plugin for Moodle
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Model class for study plan
 * @package    block_learningpath
 * @copyright  2023 P.M. Kuipers
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_learningpath;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/completionlib.php');
use completion_info;

/**
 * Model class for study plan
 */
class learningline {
     /**
     * Check if the user has completed a course using Moodle's built-in completion API.
     *
     * @param int $courseid
     * @param int $userid
     * @return bool
     */
    public static function check_course_completion($courseid, $userid): bool {
        $completion = new completion_info(get_course($courseid));
        return $completion->is_course_complete($userid);
    }

    /**
     * Check if the user has completed a catalogue item.
     * 
     * (Assumes catalogue items are tracked similarly to modules, but Moodle doesn't have a direct catalogue completion API.)
     *
     * @param int $catalogueid
     * @param int $userid
     * @return bool
     */
    public static function check_catalogue_completion($catalogueid, $userid): bool {
        global $DB;

        // Get category ID linked to the catalogue ID via custom field 'code'
        $courses = $DB->get_records_sql("
                SELECT c.id 
                FROM {course} c
                JOIN {customfield_field} cf ON cf.shortname = 'code'
                JOIN {customfield_data} cd ON cd.fieldid = cf.id AND cd.instanceid = c.id
                WHERE cd.value = :catalogueid
            ", ['catalogueid' => $catalogueid]);
        if (!$courses) {
            return false; // No courses linked to this catalogue
        }
        
        // Check if the user has completed at least one course
        foreach ($courses as $course) {
            $completion = new completion_info($course);
            if ($completion->is_course_complete($userid)) {
                return self::get_credit_by_id($course->id); 
            }
        }
    
        return false; // No courses completed
    }

    /**
     * Check if the user has completed a module (course activity).
     *
     * @param int $moduleid (Course module ID)
     * @param int $userid
     * @return bool
     */
    public static function check_module_completion($moduleid, $userid): bool {
        $cm = get_coursemodule_from_id(null, $moduleid);
        if (!$cm) {
            return false;
        }

        $completion = new completion_info(get_course($cm->course));
        $completiondata = $completion->get_data($cm, false, $userid);

        return ($completiondata->completionstate == COMPLETION_COMPLETE ||
                $completiondata->completionstate == COMPLETION_COMPLETE_PASS);
    }

    public static function get_line_info($line_id, $u_id) {
        global $DB, $COURSE;
    
        $line = $DB->get_record('local_learningpath_lines', ['id' => $line_id]); // Replace with your actual table
        if (!$line) {
            return [];
        }
    
        $url = $name = '';
        $startdate = $enddate = 'N/A';
        $credit = 0;
        $is_course = $is_module = $is_catalogue = false;
        $catalogue_id = $line->catalogue_id;
    
        if (!empty($line->course_id)) {
            $is_course = true;
            // Fetch course details
            $course = $DB->get_record('course', ['id' => $line->course_id], 'id, fullname, startdate, enddate');
            if ($course) {
                $url = new \moodle_url('/course/view.php', ['id' => $course->id]);
                $name = $course->fullname;
                $startdate = $course->startdate ? date('m-d-Y', $course->startdate) : 'N/A';
                $enddate = $course->enddate ? date('m-d-Y', $course->enddate) : 'N/A';

                if(self::check_course_completion($course->id, $u_id)){
                    $credit = self::get_credit_by_id($course->id);
                }
            }
        } elseif (!empty($line->module_id)) {
            $is_module = true;
            // Fetch module details
            list($course, $cm) = get_course_and_cm_from_cmid($line->module_id, '', $COURSE);
            if ($cm) {
                $name = $cm->name;
                $url = new \moodle_url('/mod/' . $cm->modname . '/view.php', ['id' => $cm->id]);

                if(self::check_module_completion($cm->id, $u_id)){
                    $credit = self::get_credit_by_id($cm->id);
                }
    
                // Get start & end dates for specific module types
                if ($cm->modname == 'quiz') {
                    $quiz = $DB->get_record('quiz', ['id' => $cm->instance], 'timeopen, timeclose');
                    $startdate = !empty($quiz->timeopen) ? date('Y-m-d', $quiz->timeopen) : 'N/A';
                    $enddate = !empty($quiz->timeclose) ? date('Y-m-d', $quiz->timeclose) : 'N/A';
                } elseif ($cm->modname == 'assign') {
                    $assignment = $DB->get_record('assign', ['id' => $cm->instance], 'allowsubmissionsfromdate, duedate');
                    $startdate = !empty($assignment->allowsubmissionsfromdate) ? date('Y-m-d', $assignment->allowsubmissionsfromdate) : 'N/A';
                    $enddate = !empty($assignment->duedate) ? date('Y-m-d', $assignment->duedate) : 'N/A';
                }
            }
        } elseif (!empty($line->catalogue_id)) {
            $is_catalogue = true;
            // Fetch catalogue details
            $catalogue = $DB->get_record('local_catalogue_courses', ['id' => $line->catalogue_id], 'id, name');
            if ($catalogue) {
                $url = new \moodle_url('/local/catalogue/detail.php', ['id' => $catalogue->id]);
                $name = $catalogue->name;
                $startdate = 'N/A';
                $enddate = 'N/A';
                if(self::check_catalogue_completion($catalogue->id, $u_id)){
                    $credit = self::check_catalogue_completion($catalogue->id, $u_id);
                }
            }
        }
    
        // Determine progress
        $completion_checks = [
            'course_id'   => 'check_course_completion',
            'catalogue_id' => 'check_catalogue_completion',
            'module_id'   => 'check_module_completion',
        ];
        $progress = 0;
        
        foreach ($completion_checks as $field => $method) {
            if (!empty($line->$field) && learningline::$method($line->$field, $line->user_id)) {
                $progress = 100;
                break;
            }
        }
    
        // Assign class based on progress
        if ($progress == 100) {
            $progress_class = 'bg-success';
        } elseif ($progress >= 50) {
            $progress_class = 'bg-warning';
        } else {
            $progress_class = 'bg-danger';
        }
    
        return 
            [
                'id' => $line->id,
                'url' => $url ? $url->out(false) : '',
                'name' => $name,
                'startdate' => $startdate,
                'enddate' => $enddate,
                'progress' => $progress,
                'progress_class' => $progress_class,
                'is_required' => !empty($line->required),
                'is_course' => $is_course,
                'is_module' => $is_module,
                'is_catalogue' => $is_catalogue,
                'catalogue_id' => $catalogue_id,
                'credit' => $credit,
            ];
    }

    public static function get_credit_by_id($id) {
        global $DB;
        $credit_field = $DB->get_record_sql("SELECT d.intvalue 
                    FROM {customfield_data} d
                    JOIN {customfield_field} f ON d.fieldid = f.id
                    WHERE f.shortname = 'credit' AND d.instanceid = ?", [$id]);
        return isset($credit_field->intvalue) ? $credit_field->intvalue : 0;
    }
    
}