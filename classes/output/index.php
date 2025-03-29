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
 * Summary renderable.
 *
 * @package    block_learningpath
 * @copyright  2016 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_learningpath\output;
defined('MOODLE_INTERNAL') || die();

use local_wunderbyte_table\filters\types\datepicker;
use local_wunderbyte_table\filters\types\hierarchicalfilter;
use local_wunderbyte_table\filters\types\hourlist;
use local_wunderbyte_table\filters\types\intrange;
use local_wunderbyte_table\filters\types\standardfilter;
use local_wunderbyte_table\wunderbyte_table;
use block_learningpath\index_table;
use renderable;
use renderer_base;
use templatable;

/**
 * Summary renderable class.
 *
 * @package    block_learningpath
 * @copyright  2016 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class index implements renderable, templatable {

    /**
     * An idstring for the table & spinner.
     *
     * @var string
     */
    public $idstring;

    /**
     * The encoded settings for the sql table.
     *
     * @var string
     */
    public $encodedtable;

    /**
     * Constructor.
     *
     */
    public function __construct() {

    }

    private function render_index() {
        // The $uniqueid Should be composed by ASCII alphanumeric characters, underlines and spaces only!
        // It is recommended to avoid of usage of simple single words like "table" to reduce chance of affecting by Moodle`s core CSS
        $table = new index_table('index_table');

        $table->define_headers(['Learning Plan Name', 'Start Date', 'End Date', 'Status', 'Credits']);
        $table->define_columns(['name', 'startdate', 'enddate', 'published', 'credits']);

        //$intrangefilter = new intrange('username', "Range of numbers given in Username");
        //$table->add_filter($intrangefilter);

        //$table->define_sortablecolumns(['learning_plan', 'startdate', 'enddate', 'published', 'credits']);

        $table->addcheckboxes = false;


        $table->sort_default_column = 'startdate';

        // Work out the sql for the table.
        $table->set_filter_sql('*', "(SELECT * FROM {local_learningpath} ORDER BY id ASC LIMIT 112) as s1", '1=1', '');

        $table->cardsort = true;

        $table->tabletemplate = 'local_wunderbyte_table/twtable_list';

        $table->pageable(true);

        $table->infinitescroll = 7;
        $table->stickyheader = false;
        $table->showcountlabel = false;
        $table->showdownloadbutton = false;
        $table->showreloadbutton = false;
        $table->showrowcountselect = false;
        $table->filteronloadinactive = false;
        $table->hide_filter();

        return $table->outhtml(10, true);
    }

    /**
     * Prepare data for use in a template
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        return [
            'table' => $this->render_index()
        ];
    }

}
