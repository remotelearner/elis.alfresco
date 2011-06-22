<?php
/**
 * Download a repository file to the local data storage area.
 *
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
 * @package    elis
 * @subpackage File system
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2010 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

    require_once dirname(dirname(dirname(__FILE__))) . '/config.php';
    require_once $CFG->dirroot . '/file/repository/repository.class.php';


    $cid      = required_param('cid', PARAM_INT);
    $uuid     = required_param('uuid', PARAM_CLEAN);
    $filename = required_param('filename', PARAM_FILE);
    $path     = required_param('path', PARAM_PATH);

    if (!$course = get_record('course', 'id', $cid)) {
        print_error('invalidcourseid', 'repository_alfresco', $cid);
    }

    if (empty($CFG->repository)) {
        print_error('nodefaultrepositoryplugin', 'repository');
    }

    $strdownloadingrepofile = get_string('downloadingrepositoryfile', 'repository');

    if ($course->id == SITEID) {
        $context = get_context_instance(CONTEXT_SYSTEM, SITEID);
    } else {
        $context = get_context_instance(CONTEXT_COURSE, $course->id);
    }

    if (!has_capability('moodle/course:managefiles', $context)) {
        print_error('youdonothaveaccesstothisfunctionality', 'repository_alfresco');
    }


    $navlinks[] = array('name' => $strdownloadingrepofile, 'link' => null, 'type' => 'misc');
    $navigation = build_navigation($navlinks);
    print_header("$course->shortname: $strdownloadingrepofile", $course->fullname, $navigation);


/// Initialize the repository object and 'read' the file by redirecting to the
/// file itself.
    if (!$repo = repository_factory::factory($CFG->repository)) {
        print_error('couldnotcreaterepositoryobject', 'repository');
    }

    if (!$repo->copy_local($uuid, $filename, $CFG->dataroot . $path)) {
        notify($repo->errormsg);
        echo '<center><a href="' . $CFG->wwwroot . '/files/index.php?id=' . $cid .
             '&amp;repouuid=' . $uuid . '&amp;repofile=' . rawurlencode($filename) .
             '">' . get_string('chooseanotherdirectory', 'repository') . '?<a/></center><br /><br />';
        close_window_button();
        print_footer($course);
        exit;
    }

    print_heading(get_string('successfullydownloadedfile', 'repository', $filename));

    close_window_button();

    print_footer($course);

?>
