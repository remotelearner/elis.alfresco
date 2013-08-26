<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2012 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @package    elis
 * @subpackage File system
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__) .'/../config.php');
require_once("{$CFG->dirroot}/user/files_form.php");
require_once("{$CFG->dirroot}/repository/lib.php");
require_once("{$CFG->dirroot}/repository/elis_files/lib/lib.php");

/// Wait as long as it takes for this script to finish
//set_time_limit(0);

// parameters for repository
$courseid = optional_param('course', SITEID, PARAM_INT); // course ID
$returnurl = optional_param('returnurl', '', PARAM_URL);

require_login($courseid);
if (isguestuser()) {
    die();
}

if (empty($returnurl)) {
    $returnurl = new moodle_url('/repository/filemanager.php',
                                array('course' => $courseid));
}

$context = context_course::instance($courseid);
$usercontext = context_user::instance($USER->id);

$title = get_string('repositorycoursefiles', 'repository_elis_files'); // TBD
$PAGE->set_url('/repository/filemanager.php');
$PAGE->set_context($context);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('course'); // TBD: 'standard' ???
$PAGE->set_pagetype('course-view');

// RL edit: need this module for the rendering of the ELIS Files advanced search UI
// $PAGE->requires->yui_module(array('yui2-layout', 'yui2-container', 'yui2-dragdrop'), 'TBD_init_function');

// Having any one of these capabilities means the user can access at least one
// ELIS file browsing area in the file manager
$capabilities = array(
    'repository/elis_files:viewsitecontent',
    'repository/elis_files:createsitecontent',
    'repository/elis_files:viewcoursecontent',
    'repository/elis_files:createcoursecontent',
    'repository/elis_files:viewsharedcontent',
    'repository/elis_files:createsharedcontent',
    'repository/elis_files:viewowncontent',
    'repository/elis_files:createowncontent',
    'repository/elis_files:viewusersetcontent',
    'repository/elis_files:createusersetcontent'
);

if (!has_any_capability($capabilities, $context)) {
    // No access to any ELIS files areas
    require_capability('repository/elis_files:viewcoursecontent', $context);
}

// Obtain the UUID for the default browsing location
$currentpath = elis_files_get_current_path_for_course($courseid,
                   !has_any_capability(array(
                       'repository/elis_files:viewsitecontent',
                       'repository/elis_files:createsitecontent',
                       'repository/elis_files:viewcoursecontent',
                       'repository/elis_files:createcoursecontent'), $context));

$data = new stdClass();
$data->returnurl = $returnurl;
$options = array('subdirs'=>1, 'maxbytes'=>$CFG->userquota, 'maxfiles'=>-1, 'accepted_types'=>'*', 'currentpath' => $currentpath, 'label' => $title, 'nomoodlefiles' => true);
file_prepare_standard_filemanager($data, 'files', $options, $usercontext, 'user', 'private', 0);

$mform = new user_files_form(null, array('data'=>$data, 'options'=>$options));

if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if ($formdata = $mform->get_data()) {
    $formdata = file_postupdate_standard_filemanager($formdata, 'files', $options, $usercontext, 'user', 'private', 0);
    redirect($returnurl);
}

echo $OUTPUT->header();
echo $OUTPUT->box_start('generalbox');
$mform->display();
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
