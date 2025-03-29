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
 * Book external functions and service definitions.
 *
 * @package    block_learningpath
 * @category   external
 * @copyright  2015 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */

defined('MOODLE_INTERNAL') || die;

$functions = array(
    'block_learningpath_get_learningpath' => array(
        'classname'     => 'block_learningpath_external',
        'methodname'    => 'get_learningpath',
        'classpath'     => 'blocks/learningpath/classes/external.php',
        'description'   => 'Get learning path of current user',
        'type'          => 'read',
        'ajax'          => true
    ),

    'block_learningpath_get_detail_line' => array(
        'classname'     => 'block_learningpath_external',
        'methodname'    => 'detail_line',
        'classpath'     => 'blocks/learningpath/classes/external.php',
        'description'   => 'Get detail of learning path of current user',
        'type'          => 'read',
        'ajax'          => true
    ),
);
