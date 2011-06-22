<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2009 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    mod-assignment
 * @subpackage Repository assignment type
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2010 Remote Learner.net Inc http://www.remote-learner.net
 */


    require_once('../../../../config.php');
    require_once('../../lib.php');
    require_once('assignment.class.php');


    $id     = required_param('id', PARAM_INT);      // Course Module ID
    $userid = required_param('userid', PARAM_INT);  // User ID
    $offset = optional_param('offset', 0, PARAM_INT);
    $mode   = optional_param('mode', '', PARAM_ALPHA);

    if (! $cm = get_coursemodule_from_id('assignment', $id)) {
        error("Course Module ID was incorrect");
    }

    if (! $assignment = get_record('assignment', 'id', $cm->instance)) {
        error("Assignment ID was incorrect");
    }

    if (! $course = get_record('course', 'id', $assignment->course)) {
        error("Course is misconfigured");
    }

    if (! $user = get_record('user', 'id', $userid)) {
        error("User is misconfigured");
    }

    require_login($course->id, false, $cm);

    if (!has_capability('mod/assignment:grade', get_context_instance(CONTEXT_MODULE, $cm->id))) {
        error("You can not view this assignment");
    }

    if ($assignment->assignmenttype != 'upload') {
        error("Incorrect assignment type");
    }

    $assignmentinstance = new assignment_upload($cm->id, $assignment, $cm, $course);

    $returnurl = "../../submissions.php?id={$assignmentinstance->cm->id}&amp;userid=$userid&amp;offset=$offset&amp;mode=single";

    if ($submission = $assignmentinstance->get_submission($user->id)
      and !empty($submission->data1)) {
        print_header(fullname($user,true).': '.$assignment->name);
        print_heading(get_string('notes', 'assignment').' - '.fullname($user,true));
        print_simple_box(format_text($submission->data1, FORMAT_HTML), 'center', '100%');
        if ($mode != 'single') {
            close_window_button();
        } else {
            print_continue($returnurl);
        }
        print_footer('none');
    } else {
        print_header(fullname($user,true).': '.$assignment->name);
        print_heading(get_string('notes', 'assignment').' - '.fullname($user,true));
        print_simple_box(get_string('notesempty', 'assignment'), 'center', '100%');
        if ($mode != 'single') {
            close_window_button();
        } else {
            print_continue($returnurl);
        }
        print_footer('none');
    }

?>