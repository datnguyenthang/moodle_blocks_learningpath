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

require_once($CFG->libdir.'/externallib.php');
require_once($CFG->libdir.'/filelib.php');
/**
 * Model class for study plan
 */
class learningpath {
    /**
     * Cache retrieved studyitems in this session
     * @var array */
    private static $cache = [];
    
    private $pagecache = [];

    /**
     * Holds database record
     * @var stdClass
     */
    private $r;
    /** @var int */
    private $id;
    /** @var aggregator */
    private $aggregator;

    /**
     * Hold context object once retrieved.
     * @var \context
     */
    private $context = null;
    /**
     * Cache lookup of linked users (saves queries).
     * @var int[]
     */
    private $linkeduserids = null;

    /**
     * Find record in database and return management object
     * Cache objects to avoid multiple creation events in one session.
     * @param int $id Id of database record
     */
    public static function find_by_id($id): self {
        if (!array_key_exists($id, self::$cache)) {
            self::$cache[$id] = new self($id);
        }
        return self::$cache[$id];
    }

    /**
     * Find all studyplans for a given user
     * @param int $userid Id of the user to search for
     * @return studyplan[]
     */
    public static function find_for_user($userid): array {
        global $DB;

        $sql = "SELECT ll.id FROM {local_learningpath} ll
                INNER JOIN {local_learningpath_cohorts} llc ON llc.lpt_id = ll.id
                INNER JOIN {cohort_members} cm ON llc.cohort_id = cm.cohortid
                INNER JOIN {user} u ON cm.userid = u.id
                WHERE cm.userid = :userid AND ll.published = 1";
        $cohortplanids = $DB->get_fieldset_sql($sql, ['userid' => $userid]);

        $sql = "SELECT ll.id FROM {local_learningpath} ll
                INNER JOIN {local_learningpath_users} llu ON llu.lpt_id = ll.id
                INNER JOIN {user} u ON llu.u_id = u.id
                WHERE llu.u_id = :userid AND ll.published = 1";
        $userplanids = $DB->get_fieldset_sql($sql, ['userid' => $userid]);

        $plans = [];
        foreach ($cohortplanids as $id) {
            //$plans[$id] = self::find_by_id($id);
            $plans[$id] = $id;
        }
        foreach ($userplanids as $id) {
            if (!array_key_exists($id, $plans)) {
                //$plans[$id] = self::find_by_id($id);
                $plans[$id] = $id;
            }
        }

        return $plans;
    }

    /**
     * Check if a given user has associated studyplans
     * @param int $userid Id of the user to search for
     */
    public static function exist_for_user($userid): bool {
        global $DB;
        $count = 0;
        $sql = "SELECT COUNT(ll.id) FROM {local_learningpath} ll
                INNER JOIN {local_learningpath_cohorts} llc ON llc.lpt_id = ll.id
                INNER JOIN {cohort_members} cm ON llc.cohort_id = cm.cohortid
                INNER JOIN {user} u ON cm.userid = u.id
                WHERE cm.userid = :userid  AND u.deleted != 1";
        $count += $DB->count_records_sql($sql, ['userid' => $userid]);

        $sql = "SELECT COUNT(ll.id) FROM {local_learningpath} ll
                INNER JOIN {local_learningpath_users} llu ON llu.lpt_id = ll.id
                INNER JOIN {user} u ON llu.u_id = u.id
                WHERE llu.u_id = :userid AND u.deleted != 1";
        $count += $DB->count_records_sql($sql, ['userid' => $userid]);

        return ($count > 0);
    }
    
    public static function progress_of_user($userid, $learningpathId): int {
        global $DB;
        $count = $progress = 0;

        $lines = $DB->get_records('local_learningpath_lines', ['lpt_id' => $learningpathId]);
        $count = count($lines);
        
        if ($count === 0) {
            return 0;
        }

        $progress = 0;

        foreach ($lines as $line) {
            $completion_checks = [
                'course_id'   => 'check_course_completion',
                'catalogue_id' => 'check_catalogue_completion',
                'module_id'   => 'check_module_completion',
            ];

            foreach ($completion_checks as $field => $method) {
                if (!empty($line->$field) && learningline::$method($line->$field, $userid)) {
                    $progress++;
                    break; // Exit inner loop if one condition is met
                }
            }
        }
        return $progress/$count * 100;
    }
}