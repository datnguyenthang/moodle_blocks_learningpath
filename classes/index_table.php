<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * The Wunderbyte table class is an extension of the tablelib table_sql class.
 *
 * @package local_wunderbyte_table
 * @copyright 2023 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// phpcs:ignoreFile

namespace block_learningpath;

defined('MOODLE_INTERNAL') || die();

use local_wunderbyte_table\output\table;
use local_wunderbyte_table\wunderbyte_table;
use stdClass;

/**
 * Wunderbyte table demo class.
 */
class index_table extends wunderbyte_table {

    /**
     * Decodes the Unix Timestamp
     *
     * @param stdClass $values
     * @return void
     */
    public function col_startdate($values) {
        return date('d/m/Y', strtotime($values->startdate));
    }

    /**
     * Decodes the Unix Timestamp
     *
     * @param stdClass $values
     * @return void
     */
    public function col_enddate($values) {
        return date('d/m/Y', strtotime($values->enddate));
    }

    /**
     * Replace the value of the column with a string.
     *
     * @param stdClass $values
     * @return void
     */
    public function col_published($values) { 
        return $values->published == 1 ? get_string('active', 'block_learningpath') : get_string('inactive', 'block_learningpath');
    }

    /**
     * This handles the action column with buttons, icons, checkboxes.
     *
     * @param stdClass $values
     * @return void
     */
    public function col_action($values) {

        global $OUTPUT;

        $data[] = [
            'label' => get_string('checkbox', 'local_wunderbyte_table'), // Name of your action button.
            'class' => 'btn btn-success',
            'href' => '#', // You can either use the link, or JS, or both.
            'iclass' => 'fa fa-edit', // Add an icon before the label.
            'id' => $values->id.'-'.$this->uniqueid,
            'name' => $this->uniqueid.'-'.$values->id,
            'methodname' => 'togglecheckbox', // The method needs to be added to your child of wunderbyte_table class.
            'ischeckbox' => true,
            'data' => [ // Will be added eg as data-id = $values->id, so values can be transmitted to the method above.
                'id' => $values->id,
                'labelcolumn' => 'username',
            ]
        ];

        // This transforms the array to make it easier to use in mustache template.
        table::transform_actionbuttons_array($data);

        return $OUTPUT->render_from_template('local_wunderbyte_table/component_actionbutton', ['showactionbuttons' => $data]);
    }

    /**
     * Toggle Checkbox
     *
     * @param int $id
     * @param string $data
     * @return array
     */
    public function action_togglecheckbox(int $id, string $data):array {

        $dataobject = json_decode($data);
        return [
           'success' => 1,
           'message' => $dataobject->state == 'true' ? 'checked' : 'unchecked',
        ];
    }

}
