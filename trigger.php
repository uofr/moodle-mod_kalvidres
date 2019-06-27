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
 * Acess event receive script and record student's access log.
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package   mod_kalvidres
 * @copyright (C) 2016-2017 Yamaguchi University <info-cc@ml.cc.yamaguchi-u.ac.jp>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/local/kaltura/locallib.php');

defined('MOODLE_INTERNAL') || die();

$referer = $_SERVER['HTTP_REFERER'];

$id = optional_param('id', 0, PARAM_INT); // Course Module ID.

// Retrieve module instance.
if (empty($id)) {
    print_error('invalid course module id - ' . $id, 'kalvidres');
}

$correcturl = new moodle_url('/mod/kalvidres/view.php');
$correcturl .= '?id=' . $id;

if ($referer != $correcturl) {
    print_error('invalid_access', 'kalvidres');
}

if (!empty($id)) {

    if (! $cm = get_coursemodule_from_id('kalvidres', $id)) {
        print_error('invalid_coursemodule', 'kalvidres');
    }

    if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
        print_error('course_misconf');
    }

    if (! $kalvidres = $DB->get_record('kalvidres', array('id' => $cm->instance))) {
        print_error('invalid_id', 'kalvidres');
    }
}

require_course_login($course->id, true, $cm);

global $SESSION, $CFG, $USER, $COURSE;

$PAGE->set_url('/mod/kalvidres/trigger.php', array('id' => $id));
$PAGE->set_title(format_string($kalvidres->name));
$PAGE->set_heading($course->fullname);

$context = $PAGE->context;

$student = false;

$coursecontext = context_course::instance($COURSE->id);
$roles = get_user_roles($coursecontext, $USER->id);

foreach ($roles as $role) {
    if ($role->shortname == 'student' || $role->shortname == 'guest') {
        $student = true;
    }
}

if ($student == true) {
    $event = \mod_kalvidres\event\media_resource_played::create(array(
        'objectid' => $kalvidres->id,
        'context' => context_module::instance($cm->id)
    ));
    $event->trigger();

    try {
        $kalvidreslog = $DB->get_record('kalvidres_log',
                                          array('instanceid' => $cm->instance, 'userid' => $USER->id));
        $now = time();
        if (empty($kalvidreslog)) {
            $objectdata = array('instanceid' => $cm->instance, 'userid' => $USER->id, 'plays' => 1, 'views' => 1,
                                'first' => $now, 'last' => $now);
            $DB->insert_record('kalvidres_log', $objectdata);
        } else {
            $kalvidreslog->last = $now;
            $kalvidreslog->plays = $kalvidreslog->plays + 1;
            $DB->update_record('kalvidres_log', $kalvidreslog, false);
        }
    } catch (Exception $ex) {
        print_error($ex->getMessage());
    }
}
