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
 * Block learningpath main file.
 *
 * @package    block_learningpath
 * @copyright  2016 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Block learningpath class.
 *
 * @package    block_learningpath
 * @copyright  2016 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use block_learningpath\learningpath;
class block_learningpath extends block_base {

    /**
     * Applicable formats.
     *
     * @return array
     */
    public function applicable_formats() {
        return array('site' => true, 'course' => false, 'my' => true);
    }

    /**
     * Init.
     *
     * @return void
     */
    public function init() {
        //$this->title = get_string('pluginname', 'block_learningpath');
        $this->title = '';
    }

    /**
     * Get content.
     *
     * @return stdClass
     */
    public function get_content() {
        global $USER, $PAGE;

        if (isset($this->content)) {
            return $this->content;
        }
        $this->content = new stdClass();

        if (!learningpath::exist_for_user($USER->id)) return null;

        if (isloggedin() && !isguestuser()) {
            $userid = $USER->id;
            $PAGE->requires->js_call_amd('block_learningpath/init', 'init', [$userid]);

            $renderer = $this->page->get_renderer('block_learningpath');
            $this->content->text = $renderer->initview();
            $this->content->footer = '';
        }

        return $this->content;
    }
}
