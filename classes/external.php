<?php
// This file is part of the Learning Path plugin for Moodle
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
 * External API functions for the Learning Path plugin
 * @package    block_learningpath
 * @copyright  2023 P.M. Kuipers
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
//use learningpath;
//use learningline;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once("$CFG->libdir/externallib.php");
require_once($CFG->dirroot . "/blocks/learningpath/classes/learningpath.php");
require_once($CFG->dirroot . "/blocks/learningpath/classes/learningline.php");
use block_learningpath\learningpath;
use block_learningpath\learningline;

class block_learningpath_external extends \external_api {

    /**
     * Parameters for get_learningpath function
     * @return external_function_parameters
     */
    public static function get_learningpath_parameters() {
        return new \external_function_parameters([]);
    }

    /**
     * Retrieve learning paths for the current user
     * @return array Learning path data
     * @throws \dml_exception
     */
    public static function get_learningpath() {
        global $DB, $USER;

        // Validate context and capabilities (optional, add as needed)
        $context = \context_system::instance();
        self::validate_context($context);

        // Fetch learning path IDs for the user (assuming this method exists)
        $learningpathids = learningpath::find_for_user($USER->id);

        if (empty($learningpathids)) {
            return []; // Return empty array if no learning paths found
        }

        $data = [];
        foreach ($learningpathids as $learningpathid) {
            $lp = $DB->get_record('local_learningpath', ['id' => $learningpathid]);
            $progress = learningpath::progress_of_user($USER->id, $learningpathid);

            $progress_class = 'bg-danger'; // Default (0-40%)
            if ($progress > 40 && $progress <= 70) {
                $progress_class = 'bg-warning'; // Medium progress (40-70%)
            } elseif ($progress > 70) {
                $progress_class = 'bg-success'; // High progress (70-100%)
            }
            $data[] = [
                'id' => (int)$lp->id,
                'name' => $lp->name,
                'startdate' => $lp->startdate ? date('m-d-Y', $lp->startdate) : 'N/A',
                'enddate' => $lp->enddate ? date('m-d-Y', $lp->enddate) : 'N/A',
                'progress' => $progress,
                'progress_class' => $progress_class,
                'credit' => isset($lp->credit) ? (int)$lp->credit : 0,
            ];
        }

        return $data;
    }

    /**
     * Return structure for get_learningpath
     * @return external_multiple_structure
     */
    public static function get_learningpath_returns() {
        return new \external_multiple_structure(
            new \external_single_structure([
                'id' => new \external_value(PARAM_INT, 'ID of the learning path'),
                'name' => new \external_value(PARAM_TEXT, 'Name of the learning path'),
                'startdate' => new \external_value(PARAM_TEXT, 'Start date (m-d-Y) or N/A if not set'),
                'enddate' => new \external_value(PARAM_TEXT, 'End date (m-d-Y) or N/A if not set'),
                'progress' => new \external_value(PARAM_INT, 'User progress in the learning path'),
                'progress_class' => new external_value(PARAM_TEXT, 'CSS class for progress bar'),
                'credit' => new \external_value(PARAM_INT, 'Credits for the learning path'),
            ])
        );
    }

    public static function detail_line_parameters() {
        return new \external_function_parameters([
            'lpt_id' => new external_value(PARAM_INT, 'ID of the learning path'),
            'u_id' => new external_value(PARAM_INT, 'ID of the user in learning path'),
        ]);
    }
    
    public static function detail_line($lpt_id, $u_id) {
        global $DB, $COURSE;
    
        // Validate the parameters
        $params = self::validate_parameters(self::detail_line_parameters(), array(
            'lpt_id' => $lpt_id,
            'u_id' => $u_id,
        ));
    
        $lpt_id = $params['lpt_id'];
        $u_id = $params['u_id'];
    
        if (!$DB->record_exists('local_learningpath', ['id' => $lpt_id])) {
            throw new moodle_exception('invalidlearningpath', 'block_learningpath', '', $lpt_id);
        }
    
        // Fetch all lines related to this learning path
        $lines = $DB->get_records('local_learningpath_lines', ['lpt_id' => $lpt_id]);
    
        $data = [];
    
        foreach ($lines as $line) {
            $data[] = learningline::get_line_info($line->id, $u_id);
        }
    
        return $data;
    }
    
    
    public static function detail_line_returns() {
        return new \external_multiple_structure(
            new \external_single_structure([
                'id' => new \external_value(PARAM_INT, 'ID in the learning line'),
                'url' => new \external_value(PARAM_URL, 'URL of the learning path'),
                'name' => new \external_value(PARAM_TEXT, 'Name of the learning path'),
                'startdate' => new \external_value(PARAM_TEXT, 'Start date (m-d-Y) or N/A if not set'),
                'enddate' => new \external_value(PARAM_TEXT, 'End date (m-d-Y) or N/A if not set'),
                'progress' => new \external_value(PARAM_INT, 'User progress in the learning path'),
                'progress_class' => new external_value(PARAM_TEXT, 'CSS class for progress bar'),
                'is_required' => new \external_value(PARAM_BOOL, 'Required for the learning path'),
                'is_course' => new \external_value(PARAM_BOOL, 'Is Couse for the learning path'),
                'is_module' => new \external_value(PARAM_BOOL, 'Is Module for the learning path'),
                'is_catalogue' => new \external_value(PARAM_BOOL, 'Is Catalogue for the learning path'),
                'catalogue_id' => new \external_value(PARAM_INT, 'Catalogue Id for the learning line'),
                'credit' => new \external_value(PARAM_INT, 'Credits for the learning path'),
            ])
        );
    }
    
}