<?php
/**
 * Manage files in an external DMS repository.
 *
 * Note: shamelessly "borrowed" from /files/index.php
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
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

/*
 * This file was based on /files/index.php from Moodle, with the following
 * copyright and license:
 */
///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.org                                            //
//                                                                       //
// Copyright (C) 2004  Martin Dougiamas  http://moodle.com               //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

    require_once dirname(dirname(dirname(__FILE__))) . '/config.php';
    require_once $CFG->libdir . '/filelib.php';
    require_once $CFG->libdir . '/adminlib.php';
    require_once $CFG->dirroot . '/file/repository/alfresco/lib.php';
    require_once $CFG->libdir . '/HTML_TreeMenu-1.2.0/TreeMenu.php';
    require_once $CFG->dirroot . '/file/repository/repository.class.php';


    $id       = required_param('id', PARAM_INT);
    $shared   = optional_param('shared', '', PARAM_ALPHA);
    $userid   = optional_param('userid', 0, PARAM_INT);
    $uuid     = optional_param('uuid', '', PARAM_TEXT);
    $oid      = optional_param('oid', '', PARAM_TEXT);
    $oname    = optional_param('oname', '', PARAM_TEXT);
    $dd       = optional_param('dd', 0, PARAM_INT);
    $file     = optional_param('file', '', PARAM_PATH);
    $wdir     = optional_param('wdir', '/', PARAM_PATH);
    $category = optional_param('category', 0, PARAM_INT);
    $action   = optional_param('action', '', PARAM_ACTION);
    $name     = optional_param('name', '', PARAM_FILE);
    $oldname  = optional_param('oldname', '', PARAM_FILE);
    $choose   = optional_param('choose', '', PARAM_FILE); //in fact it is always 'formname.inputname'
    $userfile = optional_param('userfile','',PARAM_FILE);
    $save     = optional_param('save', 0, PARAM_BOOL);
    $text     = optional_param('text', '', PARAM_RAW);
    $confirm  = optional_param('confirm', 0, PARAM_BOOL);

    if (empty($CFG->repository)) {
        print_error('nodefaultrepositoryplugin', 'repository');
    }

    if (!isset($CFG->repository_plugins_enabled) || (strstr($CFG->repository_plugins_enabled, 'alfresco') === false) ||
        (($repo = repository_factory::factory($CFG->repository)) === false)) {
        print_error('couldnotcreaterepositoryobject', 'repository');
    }

    if(!alfresco_user_request()) {
      print_error('nopermissions');
    }

    // If we don't have something explicitly to load and we didn't get here from the drop-down...
    if (empty($dd) && empty($uuid)) {
        if ($uuid = $repo->get_repository_location($id, $userid, $shared, $oid)) {
            redirect($CFG->wwwroot . '/file/repository/index.php?id=' . $id . '&amp;choose=' . $choose .
                     '&amp;userid=' . $userid . '&amp;shared=' . $shared . '&amp;oid=' . $oid . '&amp;uuid=' . $uuid, '', 0);
        }

        if ($uuid = $repo->get_default_browsing_location($id, $userid, $shared)) {
            redirect($CFG->wwwroot . '/file/repository/index.php?id=' . $id . '&amp;choose=' . $choose .
                     '&amp;userid=' . $userid . '&amp;shared=' . $shared . '&amp;oid=' . $oid . '&amp;uuid=' . $uuid, '', 0);
        }
    }

    if ($choose) {
        if (count(explode('.', $choose)) > 2) {
            print_error('incorectformatforchooseparameter', 'repository_alfresco');
        }
    }

    if (!$course = get_record('course', 'id', $id) ) {
        print_error('invalidcourseid', 'repository_alfresco', '', $id);
    }

    require_login($course->id);

    if (!empty($oid)) {
        if (!file_exists($CFG->dirroot . '/curriculum/plugins/cluster_classification/clusterclassification.class.php')) {
            return false;
        }
        require_once($CFG->dirroot . '/curriculum/plugins/cluster_classification/clusterclassification.class.php');
        // Get cluster context
        $cluster_context = get_context_instance(context_level_base::get_custom_context_level('cluster', 'block_curr_admin'), $oid);
    }
    if ($course->id === SITEID) {
        $context = get_context_instance(CONTEXT_SYSTEM, SITEID);
    } else {
        $context = get_context_instance(CONTEXT_COURSE, $course->id);
    }

    if(!empty($userid) && $userid != $USER->id) {
        if(!has_capability('block/repository:createsitecontent', $context)) {
            print_error('youdonothaveaccesstothisfunctionality', 'repository_alfresco');
        }
    }

/// Determine whether the current user has editing persmissions.
    $canedit = false;

    if (empty($userid) && empty($shared)) {
        if ((!empty($oid) && has_capability('block/repository:createorganizationcontent', $cluster_context, $USER->id)) ||
            ($id == SITEID && has_capability('block/repository:createsitecontent', $context, $USER->id)) ||
            ($id != SITEID && has_capability('block/repository:createcoursecontent', $context, $USER->id))) {
            $canedit = true;
        }
    } else if (empty($userid) && $shared == 'true') {
        if (!empty($USER->access['rdef'])) {
            foreach ($USER->access['rdef'] as $rdef) {
                if (isset($rdef['block/repository:createsharedcontent']) &&
                          $rdef['block/repository:createsharedcontent'] == CAP_ALLOW) {
                    $canedit = true;
                }
            }
        }
    } else if (!empty($userid)) {
        if ($USER->id == $userid) {
            if (!empty($USER->access['rdef'])) {
                foreach ($USER->access['rdef'] as $rdef) {
                    if (isset($rdef['block/repository:createowncontent']) &&
                              $rdef['block/repository:createowncontent'] == CAP_ALLOW) {
                        $canedit = true;
                    }
                }
            }
        } else if (has_capability('block/repository:createsitecontent', $context, $USER->id)) {
            $canedit = true;
        }
    }


    function html_footer() {
        global $course, $choose;

        echo '</td></tr></table>';

        print_footer($course);
    }

    function html_header($course, $wdir, $formfield=""){
        global $CFG, $ME, $USER, $id, $shared, $userid, $uuid, $oid, $repo, $choose;

        if (! $site = get_site()) {
            print_error('invalidsite', 'repository_alfresco');
        }

        // Get strfiles title and related uuid
        if (empty($userid) && empty($oid) && empty($shared) && !empty($course->id) && $course->id == $site->id) {
            $strfiles = get_string('repositorysitefiles', 'repository');
            $buuid = $repo->root->uuid;
        } else if (empty($userid) && empty($shared) && !empty($course->id)) {
            if (!empty($oid)) {
                $strfiles = get_field('crlm_cluster','name','id',$oid) . get_string('repositoryclusterfiles', 'repository');
                $buuid = $repo->get_organization_store($oid);
            } else {
                $strfiles = get_string('repositorycoursefiles', 'repository');
                $buuid = $repo->get_course_store($course->id);
            }
        } else if (empty($userid) && $shared == 'true') {
            $strfiles = get_string('repositorysharedfiles', 'repository');
            $buuid = $repo->suuid;
        } else if (!empty($userid)) {
            $strfiles = get_string('repositoryuserfiles', 'repository');
            $buuid = $repo->uuuid;
        }

        if ($wdir == '/') {
            $navlinks[] = array('name' => $strfiles, 'link' => null, 'type' => 'misc');
        } else {
            $dirs    = $repo->get_nav_breadcrumbs($uuid, $course->id, $userid, $shared, $oid);
            $numdirs = count($dirs);
            $link    = '';

            $navlinks[] = array(
                'name' => $strfiles,
                'link' => "$ME?id=$course->id&amp;shared=$shared&amp;oid=$oid&amp;uuid=$buuid&amp;userid=$userid&amp;" .
                          "wdir=/&amp;choose=$choose&amp;dd=1",
                'type' => 'misc'
            );

            if ($numdirs) {
                for ($i = 0; $i < $numdirs - 1; $i++) {
                    $name  = $dirs[$i]['name'];
                    $duuid = $dirs[$i]['uuid'];
                    $link .= '/' . urlencode($name);
                    $navlinks[] = array(
                        'name' => $name,
                        'link' => "$ME?id=$course->id&amp;shared=$shared&amp;oid=$oid&amp;userid=$userid&amp;" .
                                  "wdir=$link&amp;uuid=$duuid&amp;choose=$choose&amp;dd=1",
                        'type' => 'misc'
                    );
                }

                $navlinks[] = array(
                    'name' => $dirs[$numdirs - 1]['name'],
                    'link' => null,
                    'type' => 'misc'
                );
            }
        }

        $navigation = build_navigation($navlinks);

        if ($choose) {
            print_header();

            $chooseparts = explode('.', $choose);
            if (count($chooseparts)==2){
            ?>
            <script type="text/javascript">
            //<![CDATA[
            function set_value(txt) {
                opener.document.forms['<?php echo $chooseparts[0]."'].".$chooseparts[1] ?>.value = txt;
                window.close();
            }
            //]]>
            </script>

            <?php
            } elseif (count($chooseparts)==1){
            ?>
            <script type="text/javascript">
            //<![CDATA[
            function set_value(txt) {
                opener.document.getElementById('<?php echo $chooseparts[0] ?>').value = txt;
                window.close();
            }
            //]]>
            </script>

            <?php

            }
            $fullnav = '';
            $i = 0;
            foreach ($navlinks as $navlink) {
                // If this is the last link do not link
                if ($i == count($navlinks) - 1) {
                    $fullnav .= $navlink['name'];
                } else {
                    $fullnav .= '<a href="'.$navlink['link'].'">'.$navlink['name'].'</a>';
                }
                $fullnav .= ' -> ';
                $i++;
            }
            $fullnav = substr($fullnav, 0, -4);
            $fullnav = str_replace('->', '&raquo;', format_string($course->shortname) . " -> " . $fullnav);
            echo '<div id="nav-bar">'.$fullnav.'</div>';

        } else {
            print_header("$course->shortname: $strfiles", $course->fullname, $navigation,  $formfield);
        }

        echo "<table border=\"0\" style=\"margin-left:auto;margin-right:auto\" cellspacing=\"3\" cellpadding=\"3\" width=\"640\">";

    /// Build an array of options for a navigation drop-down menu.
        if (!empty($repo)) {
            $default = '';

            $opts = $repo->file_browse_options($course->id, $userid, $oid, $shared, $choose, 'file/repository/index.php',
                                               'files/index.php', 'file/repository/index.php', $default);

            if (!empty($opts)) {
                echo '<tr><td colspan="2" align="right">' . get_string('browsefilesfrom', 'repository') . ': ';
                popup_form($CFG->wwwroot . '/', $opts, 'filepluginselect', $default, '');
            }

            echo '</td></tr>';
        }

        echo "<tr>";
        echo "<td colspan=\"2\">";

    }

    $baseweb = $CFG->wwwroot;

    if (!($basedir = make_upload_directory("$course->id"))) {
        error("The site administrator needs to fix the file permissions");
    }


//  End of configuration and access control


    if ($wdir == '') {
        $wdir = "/";
    }

    if ($wdir{0} != '/') {  //make sure $wdir starts with slash
        $wdir = "/".$wdir;
    }


/// Get the appropriate context for the site, course or cluster.
    if (!empty($oid)) {
        if (!file_exists($CFG->dirroot . '/curriculum/plugins/cluster_classification/clusterclassification.class.php')) {
            return false;
        }
        require_once($CFG->dirroot . '/curriculum/plugins/cluster_classification/clusterclassification.class.php');
        // Get cluster context
        $cluster_context = get_context_instance(context_level_base::get_custom_context_level('cluster', 'block_curr_admin'), $oid);
    }
    if ($id == SITEID) {
        $context = get_context_instance(CONTEXT_SYSTEM, SITEID);
    } else {
        $context = get_context_instance(CONTEXT_COURSE, $id);
    }

/// Make sure that we have the correct 'base' UUID for a course or user storage area as well
/// as checking for correct permissions.
    if (!empty($userid) && !empty($id)) {
        $personalfiles = false;

        if (!empty($USER->access['rdef'])) {
            foreach ($USER->access['rdef'] as $ucontext) {
                if ($personalfiles) {
                    continue;
                }

                if (isset($ucontext['block/repository:viewowncontent']) &&
                          $ucontext['block/repository:viewowncontent'] == CAP_ALLOW) {

                    $personalfiles = true;
                }
            }
        }

        if (!$personalfiles) {
            $capabilityname = get_capability_string('block/repository:viewowncontent');
            print_error('nopermissions', '', '', $capabilityname);
            exit;
        }

        // We need to determine if this user has a user account and if not, we need to migrate their data now.
        if (empty($uuid)) {
            $uuid = $repo->get_user_store($userid);
        }

    } else if (empty($userid) && !empty($shared)) {
        $sharedfiles = false;

        if (!empty($USER->access['rdef'])) {
            foreach ($USER->access['rdef'] as $ucontext) {
                if ($sharedfiles) {
                    continue;
                }

                if (isset($ucontext['block/repository:viewsharedcontent']) &&
                          $ucontext['block/repository:viewsharedcontent'] == CAP_ALLOW) {

                    $sharedfiles = true;
                }
            }
        }

        if (!$sharedfiles) {
            $capabilityname = get_capability_string('block/repository:viewsharedcontent');
            print_error('nopermissions', '', '', $capabilityname);
            exit;
        }

        if (empty($uuid)) {
            $uuid = $repo->suuid;
        }

    } else if (!empty($oid)) {
        require_capability('block/repository:vieworganizationcontent', $cluster_context, $USER->id);
        if (empty($uuid)) {
            $uuid = $repo->get_organization_store($oid);
        }
    } else if (!empty($id) && $id != SITEID) {
        require_capability('block/repository:viewcoursecontent', $context, $USER->id);

        if (empty($uuid)) {
            $uuid = $repo->get_course_store($id);
        }
    } else {
        require_capability('block/repository:viewsitecontent', $context, $USER->id);
    }

    switch ($action) {
        case 'upload':
            if (!$canedit) {
                print_error('youdonothaveaccesstothisfunctionality', 'repository_alfresco');
            }

            html_header($course, $wdir);
            require_once $CFG->dirroot . '/lib/uploadlib.php';

            if ($save and confirm_sesskey()) {
                $course->maxbytes = 0;  // We are ignoring course limits
                $um = new upload_manager('userfile', false, false, $course, false, 0);

                // Pre-process the upload to see if there were any errors in the upload.
                if (!$um->preprocess_files()) {
                    if ($id != SITEID) {
                        displaydir($uuid, $wdir, $id);
                    } else {
                        displaydir($uuid, $wdir);
                    }

                    html_footer();
                    break;
                }

                $dir = "$basedir$wdir";
                if (!$um->preprocess_files()) {
                    notify(get_string('uploadedfile'));
                }

                if (isset($_FILES['userfile'])) {
                    // Make sure that the uploaded filename does not exist in the destination directory.
                    $issafe = true;

                    // Determine if this user has enough storage space left in their quota to upload this file.
                    if (!alfresco_quota_check($_FILES['userfile']['size'], $USER)) {
                        $issafe = false;

                        if ($quotadata = alfresco_quota_info($USER->username)) {
                            $a = new stdClass;
                            $a->current = round($quotadata->current / 1048576 * 10, 1) / 10 . get_string('sizemb');
                            $a->max     = round($quotadata->quota / 1048576 * 10, 1) / 10 . get_string('sizemb');

                            $msg = '<p class="errormessage">' . get_string('erroruploadquotasize', 'repository_alfresco', $a) . '</p>';
                        } else {
                            $msg = '<p class="errormessage">' . get_string('erroruploadquota', 'repository_alfresco') . '</p>';
                        }

                        print_simple_box($msg, '', '', '', '', 'errorbox');
                    }

                    if ($issafe && ($dir = $repo->read_dir($uuid))) {
                        if (!empty($dir->files)) {
                            foreach ($dir->files as $file) {
                                if ($file->title == $_FILES['userfile']['name']) {
                                    $issafe = false;

                                    $msg = '<p class="errormessage">' .
                                           get_string('erroruploadduplicatefilename', 'repository_alfresco',
                                                      $_FILES['userfile']['name']) .
                                           '</p>';

                                    print_simple_box($msg, '', '', '', '', 'errorbox');
                                }
                            }
                        }
                    }

                    if ($issafe) {
                        if (!$repo->upload_file('userfile', '', $uuid)) {
                            notify('Error uploading file to Alfresco', DEBUG_DEVELOPER);
                        }
                    }
                }

                if ($id != SITEID) {
                    displaydir($uuid, $wdir, $id);
                } else {
                    displaydir($uuid, $wdir);
                }

            } else {
                if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && !count($_POST)) {
                    /*  This situation is likely the result of the user
                        attempting to upload a file larger than POST_MAX_SIZE
                        See bug MDL-14000 */
                    notify(get_string('uploadserverlimit'));
                }

                $upload_max_filesize = get_max_upload_file_size($CFG->maxbytes, $course->maxbytes);
                $filesize = display_size($upload_max_filesize);

                $struploadafile    = get_string("uploadafile");
                $struploadthisfile = get_string("uploadthisfile");
                $strmaxsize        = get_string("maxsize", "", $filesize);
                $strcancel         = get_string("cancel");

                $info = $repo->get_info($uuid);

                // Build up the URL used in the upload form.
                $vars = array(
                    'id'      => $id,
                    'oid'     => $oid,
                    'shared'  => $shared,
                    'userid'  => $userid,
                    'uuid'    => $uuid,
                    'action'  => 'upload',
                    'choose'  => $choose
                );

                $action = 'index.php?';

                $count = count($vars);
                $i     = 0;
                foreach ($vars as $var => $val) {
                    $action .= $var . '=' . $val . ($i < $count - 1 ? '&amp;' : '');
                    $i++;
                }

                echo "<p>$struploadafile ($strmaxsize) --> <b>{$info->title}</b></p>";
                echo "<form enctype=\"multipart/form-data\" method=\"post\" action=\"$action\">";
                echo "<div>";
                echo "<table><tr><td colspan=\"2\">";

                // Include the form variables.
                $vars['sesskey'] = $USER->sesskey;

                foreach ($vars as $var => $val) {
                    echo '    <input type="hidden" name="' . $var . '" value="' . $val . '" />' . "\n";
                }

                upload_print_form_fragment(1, array('userfile'), null, false, null, $upload_max_filesize, 0, false);
                echo " </td></tr></table>";
                echo " <input type=\"submit\" name=\"save\" value=\"$struploadthisfile\" />";
                echo "</div>";
                echo "</form>";
                echo "<form action=\"index.php\" method=\"get\">";
                echo "<div>";
                echo ' <input type="hidden" name="choose" value="'.$choose.'" />';
                echo " <input type=\"hidden\" name=\"id\" value=\"$id\" />";
                echo " <input type=\"hidden\" name=\"oid\" value=\"$oid\" />";
                echo " <input type=\"hidden\" name=\"shared\" value=\"$shared\" />";
                echo " <input type=\"hidden\" name=\"userid\" value=\"$userid\" />";
                echo " <input type=\"hidden\" name=\"uuid\" value=\"$uuid\" />";
                echo " <input type=\"hidden\" name=\"action\" value=\"cancel\" />";
                echo " <input type=\"submit\" value=\"$strcancel\" />";
                echo "</div>";
                echo "</form>";
            }
            html_footer();
            break;

        case 'delete':
            if (!$canedit) {
                print_error('youdonothaveaccesstothisfunctionality', 'repository_alfresco');
            }

            if (!empty($userid)) {
                $puuid = $repo->get_user_store($userid);
            } else if ((($uuid == '') || (!$parent = $repo->get_parent($uuid))) ||
                       (!empty($oid) && ($uuid == '' || (!$parent = $repo->get_parent($uuid)))) ||
                       (($id != SITEID) && ($uuid == '' || $uuid == $repo->get_course_store($id)))) {

            } else {
                $puuid = '';
            }

            if ($confirm and confirm_sesskey()) {
                html_header($course, $wdir);
                if (!empty($USER->filelist)) {
                    foreach ($USER->filelist as $uuid) {
                        if (empty($puuid)) {
                            $puuid = $repo->get_parent($uuid)->uuid;
                        }

                        if (!$repo->delete($uuid)) {
                            $node = $repo->get_info($uuid);
                            debugging(get_string('couldnotdeletefile', 'repository_alfresco', $node->title));
                        }
                    }
                }
                clearfilelist();
                redirect('index.php?id=' . $id . '&amp;oid=' . $oid . '&amp;shared=' . $shared . '&amp;userid=' . $userid . '&amp;uuid=' .
                         $puuid . '&amp;choose=' . $choose, '', 0);
                html_footer();

            } else {
                html_header($course, $wdir);

                if (setfilelist($_POST)) {
                    notify(get_string('deletecheckwarning') . ':');
                    print_simple_box_start('center');
                    printfilelist($USER->filelist);
                    print_simple_box_end();
                    echo "<br />";

                    $resourcelist = false;

                    foreach ($USER->filelist as $file) {
                        // If file is specified in a resource, then delete that too.
                        $clean_name = substr($file, 1);

                        if (record_exists('resource', 'reference', $clean_name)) {
                            if (!$resourcelist) {
                                print_simple_box_start('center');
                                $resourcelist = true;
                            }
                            $resource_id = files_get_cm_from_resource_name($clean_name);
                            echo '<p>' . get_string('warningdeleteresource', '', $file) .
                                 " <a href='$CFG->wwwroot/course/mod.php?update=$resource_id&sesskey=$USER->sesskey'>" .
                                 get_string('update')."</a></p>";
                        }
                    }
                    if ($resourcelist) {
                        print_simple_box_end();
                        echo "<br />";
                    }

                    notice_yesno(get_string("deletecheckfiles"),
                                "index.php?id=$id&amp;shared=$shared&amp;oid=$oid&amp;userid=$userid&amp;uuid=$uuid&amp;wdir=$wdir&amp;action=delete&amp;confirm=1&amp;sesskey=$USER->sesskey&amp;choose=$choose",
                                "index.php?id=$id&amp;shared=$shared&amp;oid=$oid&amp;userid=$userid&amp;uuid=$uuid&amp;wdir=$wdir&amp;action=cancel&amp;choose=$choose");
                } else {
                    redirect("index.php?id=$id&amp;shared=$shared&amp;oid=$oid&amp;userid=$userid&amp;uuid=$puuid&amp;choose=$choose");
                }
                html_footer();
            }
            break;

        case 'move':
            if (!$canedit) {
                error('You cannot access this functionality');
            }
            html_header($course, $wdir);
            if (($count = setfilelist($_POST)) and confirm_sesskey()) {
                $USER->fileop     = $action;
                $USER->filesource = $uuid;
                echo "<p align=\"center\">";
                print_string("selectednowmove", "moodle", $count);
                echo "</p>";
            }

            displaydir($uuid, $wdir, $id);
            html_footer();
            break;

        case 'paste':
            html_header($course, $wdir);
            if (isset($USER->fileop) and ($USER->fileop == "move") and confirm_sesskey()) {
                foreach ($USER->filelist as $file) {
                /// Determine if the content being moved is an Alfresco UUID or a moodledata file.
                    if (preg_match('/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/', $file) > 0) {
                        if (!alfresco_move_node($file, $uuid)) {
                            if ($properties = $repo->get_info($file)) {
                                echo '<p>Error: ' . $properties->title . ' not moved';
                            } else {
                                echo '<p>Error: File not moved';
                            }
                        }
                    } else {
                        if ($file[0] != '/') {
                            $basedir .= '/';
                        }

                        if (is_dir($basedir . $file)) {
                            $status = $repo->upload_dir($basedir . $file, $uuid);
                        } else {
                            $status = $repo->upload_file('', $basedir . $file, $uuid);
                        }

                        if ($status) {
                            if (!fulldelete($basedir . $file)) {
                                debugging('Could not delete ' . $file);
                            }
                        } else {
                            echo "<p>Error: $file not moved";
                        }
                    }
                }
            }
            clearfilelist();
            displaydir($uuid, $wdir, $id);
            html_footer();
            break;
/*
        case 'rename':
            if (!$canedit) {
                error('You cannot access this functionality');
            }

            if (($name != '') and confirm_sesskey()) {
                html_header($course, $wdir);
                $name = clean_filename($name);
                if (file_exists($basedir.$wdir."/".$name)) {
                    echo "<center>Error: $name already exists!</center>";
                } else if (!rename($basedir.$wdir."/".$oldname, $basedir.$wdir."/".$name)) {
                    echo "<p align=\"center\">Error: could not rename $oldname to $name</p>";
                }

                //if file is part of resource then update resource table as well
                //this line only catch the root directory
                if (record_exists('resource', 'reference', $oldname)) {
                    set_field('resource', 'reference', $name, 'reference', $oldname);
                }

                if (get_dir_name_from_resource($oldname)) {
                    $resources = get_dir_name_from_resource($oldname);
                    print_simple_box_start("center");
                    echo "<b>The following files might be referenced as a resource :</b><br>";
                    foreach ($resources as $resource) {
                        $resource_id = files_get_cm_from_resource_name($name);
                        echo '<p align=\"center\">'. "$resource->reference :"."</align><a href='$CFG->wwwroot/course/mod.php?update=$resource_id&sesskey=$USER->sesskey'> ".get_string('update')."</a>";
                    }
                    print_simple_box_end();
                }
                displaydir($wdir);

            } else {
                $strrename = get_string("rename");
                $strcancel = get_string("cancel");
                $strrenamefileto = get_string("renamefileto", "moodle", $file);
                html_header($course, $wdir, "form.name");
                echo "<p>$strrenamefileto:";
                echo "<table><tr><td>";
                echo "<form action=\"index.php\" method=\"post\" name=\"form\">";
                echo ' <input type="hidden" name="choose" value="'.$choose.'" />';
                echo " <input type=\"hidden\" name=\"id\" value=\"$id\" />";
                echo " <input type=\"hidden\" name=\"shared\" value=\"$shared\" />";
                echo " <input type=\"hidden\" name=\"userid\" value=\"$userid\" />";
                echo " <input type=\"hidden\" name=\"wdir\" value=\"$wdir\" />";
                echo " <input type=\"hidden\" name=\"action\" value=\"rename\" />";
                echo " <input type=\"hidden\" name=\"oldname\" value=\"$file\" />";
                echo " <input type=\"hidden\" name=\"sesskey\" value=\"$USER->sesskey\" />";
                echo " <input type=\"text\" name=\"name\" size=\"35\" value=\"$file\" />";
                echo " <input type=\"submit\" value=\"$strrename\" />";
                echo "</form>";
                echo "</td><td>";
                echo "<form action=\"index.php\" method=\"get\">";
                echo ' <input type="hidden" name="choose" value="'.$choose.'" />';
                echo " <input type=\"hidden\" name=\"id\" value=\"$id\" />";
                echo " <input type=\"hidden\" name=\"shared\" value=\"$shared\" />";
                echo " <input type=\"hidden\" name=\"userid\" value=\"$userid\" />";
                echo " <input type=\"hidden\" name=\"wdir\" value=\"$wdir\" />";
                echo " <input type=\"hidden\" name=\"action\" value=\"cancel\" />";
                echo " <input type=\"submit\" value=\"$strcancel\" />";
                echo "</form>";
                echo "</td></tr></table>";
            }
            html_footer();
            break;
*/
        case 'makedir':
            if (!$canedit) {
                print_error('youdonothaveaccesstothisfunctionality', 'repository_alfresco');
            }

            if (($name != '') and confirm_sesskey()) {
                html_header($course, $wdir);

                if ($repo->dir_exists($name, $uuid)) {
                    debugging(get_string('errordirectorynameexists', 'repository_alfresco', $name));
                } else if ($repo->create_dir($name, $uuid) == false) {
                    debugging(get_string('errorcouldnotcreatedirectory', 'repository_alfresco', $name));
                }

                if ($id != SITEID) {
                    displaydir($uuid, $wdir, $id);
                } else {
                    displaydir($uuid, $wdir);
                }
            } else {
                $nodeproperties = $repo->get_info($uuid);

                $strcreate = get_string("create");
                $strcancel = get_string("cancel");
                $strcreatefolder = get_string("createfolder", "moodle", '<b>' . $nodeproperties->title . '</b>');
                html_header($course, $wdir, "form.name");
                echo "<p>$strcreatefolder:</p>";
                echo "<table><tr><td>";
                echo "<form action=\"index.php\" method=\"post\" name=\"form\">";
                echo ' <input type="hidden" name="choose" value="'.$choose.'" />';
                echo " <input type=\"hidden\" name=\"id\" value=\"$id\" />";
                echo " <input type=\"hidden\" name=\"oid\" value=\"$oid\" />";
                echo " <input type=\"hidden\" name=\"shared\" value=\"$shared\" />";
                echo " <input type=\"hidden\" name=\"userid\" value=\"$userid\" />";
                echo " <input type=\"hidden\" name=\"wdir\" value=\"$wdir\" />";
                echo " <input type=\"hidden\" name=\"uuid\" value=\"$uuid\" />";
                echo " <input type=\"hidden\" name=\"action\" value=\"makedir\" />";
                echo " <input type=\"text\" name=\"name\" size=\"35\" />";
                echo " <input type=\"hidden\" name=\"sesskey\" value=\"$USER->sesskey\" />";
                echo " <input type=\"submit\" value=\"$strcreate\" />";
                echo "</form>";
                echo "</td><td>";
                echo "<form action=\"index.php\" method=\"get\">";
                echo ' <input type="hidden" name="choose" value="'.$choose.'" />';
                echo " <input type=\"hidden\" name=\"id\" value=\"$id\" />";
                echo " <input type=\"hidden\" name=\"oid\" value=\"$oid\" />";
                echo " <input type=\"hidden\" name=\"shared\" value=\"$shared\" />";
                echo " <input type=\"hidden\" name=\"userid\" value=\"$userid\" />";
                echo " <input type=\"hidden\" name=\"wdir\" value=\"$wdir\" />";
                echo " <input type=\"hidden\" name=\"uuid\" value=\"$uuid\" />";
                echo " <input type=\"submit\" value=\"$strcancel\" />";
                echo "</form>";
                echo "</td></tr></table>";
            }
            html_footer();
            break;
/*
        case 'edit':
            html_header($course, $wdir);
            if (($text != '') and confirm_sesskey()) {
                $fileptr = fopen($basedir.'/'.$file,"w");
                fputs($fileptr, stripslashes($text));
                fclose($fileptr);
                displaydir($wdir);

            } else {
                $streditfile = get_string("edit", "", "<b>$file</b>");
                $fileptr  = fopen($basedir.'/'.$file, "r");
                $contents = fread($fileptr, filesize($basedir.'/'.$file));
                fclose($fileptr);

                if (mimeinfo("type", $file) == "text/html") {
                    $usehtmleditor = can_use_html_editor();
                } else {
                    $usehtmleditor = false;
                }
                $usehtmleditor = false;    // Always keep it off for now

                print_heading("$streditfile");

                echo "<table><tr><td colspan=\"2\">";
                echo "<form action=\"index.php\" method=\"post\" name=\"form\">";
                echo ' <input type="hidden" name="choose" value="'.$choose.'" />';
                echo " <input type=\"hidden\" name=\"id\" value=\"$id\" />";
                echo " <input type=\"hidden\" name=\"shared\" value=\"$shared\" />";
                echo " <input type=\"hidden\" name=\"userid\" value=\"$userid\" />";
                echo " <input type=\"hidden\" name=\"wdir\" value=\"$wdir\" />";
                echo " <input type=\"hidden\" name=\"file\" value=\"$file\" />";
                echo " <input type=\"hidden\" name=\"action\" value=\"edit\" />";
                echo " <input type=\"hidden\" name=\"sesskey\" value=\"$USER->sesskey\" />";
                print_textarea($usehtmleditor, 25, 80, 680, 400, "text", $contents);
                echo "</td></tr><tr><td>";
                echo " <input type=\"submit\" value=\"".get_string("savechanges")."\" />";
                echo "</form>";
                echo "</td><td>";
                echo "<form action=\"index.php\" method=\"get\">";
                echo ' <input type="hidden" name="choose" value="'.$choose.'" />';
                echo " <input type=\"hidden\" name=\"id\" value=\"$id\" />";
                echo " <input type=\"hidden\" name=\"shared\" value=\"$shared\" />";
                echo " <input type=\"hidden\" name=\"userid\" value=\"$userid\" />";
                echo " <input type=\"hidden\" name=\"wdir\" value=\"$wdir\" />";
                echo " <input type=\"hidden\" name=\"action\" value=\"cancel\" />";
                echo " <input type=\"submit\" value=\"".get_string("cancel")."\" />";
                echo "</form>";
                echo "</td></tr></table>";

                if ($usehtmleditor) {
                    use_html_editor();
                }


            }
            html_footer();
            break;
*/
        case 'zip':
            if (!$canedit) {
                print_error('youdonothaveaccesstothisfunctionality', 'repository_alfresco');
            }

            if (($name != '') and confirm_sesskey()) {
                html_header($course, $wdir);
                $name     = clean_filename($name);
                $filepath = $CFG->dataroot . '/temp/alfresco/' . $name;
                $destpath = $CFG->dataroot . '/temp/alfresco/zip-' . random_string(5) . '/';

                if (!is_dir($destpath)) {
                    mkdir($destpath, $CFG->directorypermissions, true);
                }

                $filelist = array();

                foreach ($USER->filelist as $fuuid) {
                    $file = $repo->get_info($fuuid);

                    if ($repo->is_dir($file->uuid)) {
                        $dirfiles = $repo->download_dir($destpath, $fuuid);

                        if ($dirfiles !== false) {
                            $filelist[] = $destpath . $file->title;
                        }
                    } else {
                        if (!is_file($destpath . $file->title)) {
                            if ($repo->read_file($fuuid, $destpath . $file->title)) {
                                $filelist[] = $destpath . $file->title;
                            }
                        }
                    }
                }

                if (!zip_files($filelist, $filepath)) {
                    error(get_string("zipfileserror","error"));
                }

                $repo->upload_file('', $filepath, $uuid);

                fulldelete($destpath);
                fulldelete($filepath);

                clearfilelist();
                displaydir($uuid, $wdir, $id);

            } else {
                html_header($course, $wdir, "form.name");

                if (setfilelist($_POST)) {
                    echo "<p align=\"center\">".get_string("youareabouttocreatezip").":</p>";
                    print_simple_box_start("center");
                    printfilelist($USER->filelist);
                    print_simple_box_end();
                    echo "<br />";
                    echo "<p align=\"center\">".get_string("whattocallzip")."</p>";
                    echo "<table><tr><td>";
                    echo "<form action=\"index.php\" method=\"post\" name=\"form\">";
                    echo ' <input type="hidden" name="choose" value="'.$choose.'" />';
                    echo " <input type=\"hidden\" name=\"id\" value=\"$id\" />";
                    echo " <input type=\"hidden\" name=\"oid\" value=\"$oid\" />";
                    echo " <input type=\"hidden\" name=\"shared\" value=\"$shared\" />";
                    echo " <input type=\"hidden\" name=\"userid\" value=\"$userid\" />";
                    echo " <input type=\"hidden\" name=\"uuid\" value=\"$uuid\" />";
                    echo " <input type=\"hidden\" name=\"wdir\" value=\"$wdir\" />";
                    echo " <input type=\"hidden\" name=\"action\" value=\"zip\" />";
                    echo " <input type=\"text\" name=\"name\" size=\"35\" value=\"new.zip\" />";
                    echo " <input type=\"hidden\" name=\"sesskey\" value=\"$USER->sesskey\" />";
                    echo " <input type=\"submit\" value=\"".get_string("createziparchive")."\" />";
                    echo "</form>";
                    echo "</td><td>";
                    echo "<form action=\"index.php\" method=\"get\">";
                    echo ' <input type="hidden" name="choose" value="'.$choose.'" />';
                    echo " <input type=\"hidden\" name=\"id\" value=\"$id\" />";
                    echo " <input type=\"hidden\" name=\"oid\" value=\"$oid\" />";
                    echo " <input type=\"hidden\" name=\"shared\" value=\"$shared\" />";
                    echo " <input type=\"hidden\" name=\"userid\" value=\"$userid\" />";
                    echo " <input type=\"hidden\" name=\"uuid\" value=\"$uuid\" />";
                    echo " <input type=\"hidden\" name=\"wdir\" value=\"$wdir\" />";
                    echo " <input type=\"hidden\" name=\"action\" value=\"cancel\" />";
                    echo " <input type=\"submit\" value=\"".get_string("cancel")."\" />";
                    echo "</form>";
                    echo "</td></tr></table>";
                } else {
                    displaydir($uuid, $wdir, $id);
                    clearfilelist();
                }
            }
            html_footer();
            break;

        case 'unzip':
            if (!$canedit) {
                print_error('youdonothaveaccesstothisfunctionality', 'repository_alfresco');
            }

            html_header($course, $wdir);

            if (($uuid != '') and confirm_sesskey()) {
                if (($parent = $repo->get_parent($uuid)) !== false) {
                    $puuid = $parent->uuid;
                } else {
                    $puuid = '';
                }

                $node = $repo->get_info($uuid);

                $filename = $node->title;
                $filepath = $CFG->dataroot . '/temp/alfresco/' . $filename;
                $destpath = $CFG->dataroot . '/temp/alfresco/unzip-' . random_string(5);

                if (!is_dir($destpath)) {
                    mkdir($destpath, $CFG->directorypermissions, true);
                }

            /// Write the file contents to a temporary file (if needed).
                if (!file_exists($filepath)) {
                    if (!$repo->read_file($uuid, $filepath)) {
                        print_error('couldnotgetfiledataforuuid', 'repository_alfresco', '', $uuid);
                    }
                }

                $strok        = get_string('ok');
                $strunpacking = get_string('unpacking', '');

                echo "<p align=\"center\">$strunpacking:</p>";

                if (!unzip_file($filepath, $destpath)) {
                    if (isset($fp)) {
                        fclose($fp);
                    }

                    fulldelete($filepath);
                    fulldelete($destpath);

                    error(get_string('unzipfileserror', 'error'));
                }

                if (($duuid = $repo->create_dir(basename($filename, '.zip'), $puuid)) == false) {
                    if (isset($fp)) {
                        fclose($fp);
                    }

                    fulldelete($filepath);
                    fulldelete($destpath);

                    print_error('errorcouldnotcreatedirectory', 'repository_alfresco', '', basename($filename, '.zip'));
                }

                if (!$repo->upload_dir($destpath, $duuid)) {
                    if (isset($fp)) {
                        fclose($fp);
                    }

                    fulldelete($filepath);
                    fulldelete($destpath);

                    print_error('erroruploadingfile', 'repository_alfresco');
                }

                if (isset($fp)) {
                    fclose($fp);
                }

                fulldelete($filepath);
                fulldelete($destpath);

                echo "<center><form action=\"index.php\" method=\"get\">";
                echo ' <input type="hidden" name="choose" value="'.$choose.'" />';
                echo " <input type=\"hidden\" name=\"id\" value=\"$id\" />";
                echo " <input type=\"hidden\" name=\"oid\" value=\"$oid\" />";
                echo " <input type=\"hidden\" name=\"shared\" value=\"$shared\" />";
                echo " <input type=\"hidden\" name=\"userid\" value=\"$userid\" />";
                echo " <input type=\"hidden\" name=\"uuid\" value=\"$puuid\" />";
                echo " <input type=\"submit\" value=\"$strok\" />";
                echo "</form>";
                echo "</center>";
            } else {
                displaydir($uuid, $wdir, $id);
            }
            html_footer();
            break;

        case 'listzip':
            html_header($course, $wdir);

            if (($parent = $repo->get_parent($uuid)) !== false) {
                $puuid = $parent->uuid;
            } else {
                $puuid = '';
            }

            if (($uuid != '') and confirm_sesskey()) {
                $strname      = get_string("name");
                $strsize      = get_string("size");
                $strmodified  = get_string("modified");
                $strok        = get_string("ok");
                $strlistfiles = get_string("listfiles", "", $file);

                echo "<p align=\"center\">$strlistfiles:</p>";

                $node = $repo->get_info($uuid);

                $filename = $node->title;
                $filepath = $CFG->dataroot . '/temp/alfresco/' . $filename;

            /// Write the file contents to a temporary file (if needed).
                if (!file_exists($filepath)) {
                    if (!$repo->read_file($uuid, $filepath)) {
                        print_error('couldnotgetfiledataforuuid', 'repository_alfresco', '', $uuid);
                    }
                }

                include_once($CFG->libdir . '/pclzip/pclzip.lib.php');
                $archive = new PclZip(cleardoubleslashes($filepath));
                if (!$list = $archive->listContent(cleardoubleslashes($filepath))) {
                    notify($archive->errorInfo(true));

                } else {
                    echo "<table cellpadding=\"4\" cellspacing=\"2\" border=\"0\" width=\"640\" class=\"files\">";
                    echo "<tr class=\"file\"><th align=\"left\" class=\"header name\">$strname</th><th align=\"right\" class=\"header size\">$strsize</th><th align=\"right\" class=\"header date\">$strmodified</th></tr>";
                    foreach ($list as $item) {
                        echo "<tr>";
                        print_cell("left", s($item['filename']), 'name');
                        if (! $item['folder']) {
                            print_cell("right", display_size($item['size']), 'size');
                        } else {
                            echo "<td>&nbsp;</td>";
                        }
                        $filedate  = userdate($item['mtime'], get_string("strftimedatetime"));
                        print_cell("right", $filedate, 'date');
                        echo "</tr>";
                    }
                    echo "</table>";

                    fulldelete($filepath);
                }

                echo "<br /><center><form action=\"index.php\" method=\"get\">";
                echo ' <input type="hidden" name="choose" value="'.$choose.'" />';
                echo " <input type=\"hidden\" name=\"id\" value=\"$id\" />";
                echo " <input type=\"hidden\" name=\"oid\" value=\"$oid\" />";
                echo " <input type=\"hidden\" name=\"shared\" value=\"$shared\" />";
                echo " <input type=\"hidden\" name=\"userid\" value=\"$userid\" />";
                echo " <input type=\"hidden\" name=\"uuid\" value=\"$puuid\" />";
                echo " <input type=\"hidden\" name=\"courseid\" value=\"$id\" />";
                echo " <input type=\"submit\" value=\"$strok\" />";
                echo "</form>";
                echo "</center>";
            } else {
                displaydir($puuid, $wdir, $id);
            }
            html_footer();
            break;

        case 'restore':
            html_header($course, $wdir);
            if (($file != '') and confirm_sesskey()) {
                echo "<p align=\"center\">".get_string("youaregoingtorestorefrom").":</p>";
                print_simple_box_start("center");
                echo $file;
                print_simple_box_end();
                echo "<br />";
                echo "<p align=\"center\">".get_string("areyousuretorestorethisinfo")."</p>";
                $restore_path = "$CFG->wwwroot/backup/restore.php";
                notice_yesno (get_string("areyousuretorestorethis"),
                                $restore_path."?id=".$id."&amp;file=".cleardoubleslashes($id.$wdir."/".$file)."&amp;method=manual",
                                "index.php?id=$id&amp;shared=$shared&amp;oid=$oid&amp;userid=$userid&amp;wdir=$wdir&amp;action=cancel");
            } else {
                displaydir($wdir);
            }
            html_footer();
            break;

        case 'cancel':
            clearfilelist();

        default:
            html_header($course, $wdir);
            if ($id != SITEID) {
                displaydir($uuid, $wdir, $id);
            } else {
                displaydir($uuid, $wdir);
            }

            html_footer();
            break;
}


/// FILE FUNCTIONS ///////////////////////////////////////////////////////////


function setfilelist($VARS) {
    global $USER;

    $USER->filelist = array ();
    $USER->fileop = "";

    $count = 0;

    foreach ($VARS as $key => $val) {
        if (substr($key,0,4) == "file") {
            $val = rawurldecode($val);
            preg_match('/(.+uuid=){0,1}([a-z0-9-]+)/', $val, $matches);

            if (!empty($matches[2])) {
                $count++;
                $USER->filelist[] = clean_param($matches[2], PARAM_PATH);
            }
        }
    }

    return $count;
}

function clearfilelist() {
    global $USER;

    $USER->filelist = array ();
    $USER->fileop = "";
}


function printfilelist($filelist) {
    global $CFG, $basedir, $repo;

    foreach ($filelist as $uuid) {
        $file = $repo->get_info($uuid);

        if ($repo->is_dir($uuid)) {
            echo "<img src=\"{$file->icon}\" height=\"16\" width=\"16\" alt=\"\" /> " .
                 $file->title . "<br />";

            $subfilelist = array();

            if ($currdir = $repo->read_dir($uuid)) {
                if (!empty($currdir->folders)) {
                    foreach ($currdir->folders as $folder) {
                        $subfilelist[] = $folder->uuid;
                    }
                }

                if (!empty($currdir->files)) {
                    foreach ($currdir->files as $file) {
                        $subfilelist[] = $file->uuid;
                    }
                }
            }

            printfilelist($subfilelist);
        } else {
            $icon     = mimeinfo("icon", $file->filename);
            $filename = $file->filename;
            $fileurl  = $CFG->wwwroot . '/file/repository/alfresco/openfile.php?uuid=' . $uuid;

            $icon = mimeinfo('icon', $file->filename);
            echo "<img src=\"{$file->icon}\"  height=\"16\" width=\"16\" alt=\"\" /> " .
                 $file->filename . "<br />";
        }
    }
}


function print_cell($alignment='center', $text='&nbsp;', $class='') {
    if ($class) {
        $class = ' class="'.$class.'"';
    }
    echo '<td align="'.$alignment.'" nowrap="nowrap"'.$class.'>'.$text.'</td>';
}

function displaydir($uuid, $wdir, $courseid = 0) {
    global $USER;
    global $CFG;
    global $basedir;
    global $id;
    global $oid;
    global $shared;
    global $userid;
    global $choose;
    global $repo;
    //global $uuid;
    global $canedit;
    global $category;


    $search = optional_param('search', '', PARAM_CLEAN);


/// Get the context instance for where we originated viewing this browser from.
    if (!empty($oid)) {
        $cluster_context = get_context_instance(context_level_base::get_custom_context_level('cluster', 'block_curr_admin'), $oid);
    }
    if ($id == SITEID) {
        $context = get_context_instance(CONTEXT_SYSTEM, SITEID);
    } else {
        $context = get_context_instance(CONTEXT_COURSE, $id);
    }


    $strname                = get_string("name");
    $strsize                = get_string("size");
    $strmodified            = get_string("modified");
    $straction              = get_string("action");
    $strmakeafolder         = get_string("makeafolder");
    $struploadafile         = get_string("uploadafile");
    $strselectall           = get_string("selectall");
    $strselectnone          = get_string("deselectall");
    $strwithchosenfiles     = get_string("withchosenfiles");
    $strmovetoanotherfolder = get_string("movetoanotherfolder");
    $strmovefilestohere     = get_string("movefilestohere");
    $strdeletecompletely    = get_string("deletecompletely");
    $strcreateziparchive    = get_string("createziparchive");
    $strrename              = get_string("rename");
    $stredit                = get_string("edit");
    $strunzip               = get_string("unzip");
    $strlist                = get_string("list");
    $strrestore             = get_string("restore");
    $strchoose              = get_string("choose");
    $strbrowserepo          = get_string('browserepository', 'repository');
    $strdownloadlocal       = get_string('downloadlocally', 'repository');

    $dirlist  = array();
    $filelist = array();

    $parentdir = new Object();

    if (!empty($userid)) {
        $ustore = $repo->get_user_store($userid);
    }

    if (!empty($search)) {
        $parentdir->title = '';
        $parentdir->url   = '';

    } else if (!empty($userid) && ($uuid == '' || $uuid == $ustore)) {
        if (empty($uuid)) {
            $uuid = $ustore;
        }

        $parentdir->title = '';
        $parentdir->url   = '';

    } else if (!empty($shared) && ($uuid == '' || $uuid == $repo->suuid)) {
        if (empty($uuid)) {
            $uuid = $repo->suuid;
        }

        $parentdir->title = '';
        $parentdir->url   = '';

    } else if (!empty($oid) && ($uuid == '' || $uuid == $repo->get_organization_store($oid))) {

        if (empty($uuid)) {
            $uuid = $repo->get_organization_store($oid);
        }

        $parentdir->title = '';
        $parentdir->url   = '';

    } else if ($id != SITEID && ($uuid == '' || !($parent = $repo->get_parent($uuid)) ||
               ($uuid == '' || $uuid == $repo->get_course_store($id)))) {

        if (empty($uuid)) {
            $uuid = $repo->get_course_store($id);
        }

        $parentdir->title = '';
        $parentdir->url   = '';

    } else if ($id == SITEID && ($uuid == '' || !($parent = $repo->get_parent($uuid)))) {
        if (empty($uuid)) {
            $node = $repo->get_root();
            $uuid = $node->uuid;
        }

        $parentdir->title = '';
        $parentdir->url   = '';

    } else {
        $parentdir->uuid  = $parent->uuid;
        $parentdir->name  = $parent->title;
        $parentdir->title = '..';
    }

    $dirlist[] = $parentdir;
    $catselect = array();

    if (!empty($search)) {
        if (($data = data_submitted()) && confirm_sesskey()) {
            if (!empty($data->categories)) {
                $catselect = $data->categories;
            }
        } else if (!empty($category)) {
            $catselect = array($category);
        }

        $search  = stripslashes($search);
        $repodir = $repo->search($search, $catselect);
    } else {
        $repodir = $repo->read_dir($uuid);
    }

    // Store the UUID value that we are currently browsing.
    $repo->set_repository_location($uuid, $id, $userid, $shared, $oid);

    if (!empty($repodir->folders)) {
        foreach ($repodir->folders as $folder) {
            $dirlist[] = $folder;
        }
    }
    if (!empty($repodir->files)) {
        foreach ($repodir->files as $file) {
            $filelist[] = $file;
        }
    }

    echo '<form action="index.php" method="post" name="reposearch">';
    echo '<input type="hidden" name="choose" value="' . $choose . '" />';
    echo "<input type=\"hidden\" name=\"id\" value=\"$id\" />";
    echo "<input type=\"hidden\" name=\"oid\" value=\"$oid\" />";
    echo "<input type=\"hidden\" name=\"shared\" value=\"$shared\" />";
    echo "<input type=\"hidden\" name=\"userid\" value=\"$userid\" />";
    echo "<input type=\"hidden\" name=\"uuid\" value=\"$uuid\" /> ";
    echo "<input type=\"hidden\" name=\"sesskey\" value=\"$USER->sesskey\" />";



    echo '<center>';
    echo '<input type="text" name="search" size="40" value="' . s($search) . '" /> ';
    echo '<input type="submit" value="' . get_string('search') . '" />';

    helpbutton('search', get_string('alfrescosearch', 'repository_alfresco'), 'block_repository');

    echo '</center><br />';

    if ($cats = $repo->category_get_children(0)) {
        $baseurl = $CFG->wwwroot . '/file/repository/index.php?choose='. $choose . '&amp;id=' .
                   $id . '&amp;shared=' . $shared . '&amp;oid=' . $oid . '&amp;userid=' . $userid . '&amp;wdir=' .
                   $wdir . '&amp;uuid=' . $uuid;

        $catfilter = repository_alfresco_get_category_filter();

        $icon  = 'folder.gif';
        $eicon = 'folder-expanded.gif';
        $menu  = new HTML_TreeMenu();

        $tnodes = array();

        if ($cats = $repo->category_get_children(0)) {
            if ($nodes = repository_alfresco_make_category_select_tree_browse($cats, $catselect, $baseurl)) {
                for ($i = 0; $i < count($nodes); $i++) {
                    $menu->addItem($nodes[$i]);
                }
            }

        }

        $treemenu = new HTML_TreeMenu_DHTML($menu, array(
            'images' => $CFG->wwwroot . '/lib/HTML_TreeMenu-1.2.0/images'
        ));

        // Add roll up - roll down code here, similar to Show Advanced in course/modedit
        // Advanced Search/Hide Advanced Search
        // "category filter" now has help text - so, how to add that too, but use yui library
        // for hiding this
        echo '<script language="JavaScript" type="text/javascript">';
        echo "<!--\n";
        include($CFG->libdir . '/HTML_TreeMenu-1.2.0/TreeMenu.js');
        echo "\n// -->";
        echo '</script>';

        print_simple_box_start('center', '75%');
        //looks for search.html file and gets text alfrescosearch from repository_alfresco lang file which I guess we use too...
        // now hmmm, so where does search.html go? or in our case, categoryfilter.html, I guess repository/alfresco
        print_heading(helpbutton('categoryfilter', get_string('alfrescocategoryfilter', 'block_repository'), 'block_repository', true, false, null, true) . get_string('categoryfilter', 'block_repository') . ':', 'center', '3');
        $treemenu->printMenu();
        echo '<br />';
        print_simple_box_end();
    }
    echo '</form>';

    echo '<center>';
    print_single_button('index.php', array('id' => $id, 'shared' => $shared, 'oid' => $oid, 'userid' => $userid, 'uuid' => $uuid),
                        get_string('showall'), 'get');

    echo '</center>';

    if ($canedit) {
        echo "<form action=\"index.php\" method=\"post\" name=\"dirform\" id=\"dirform\">";
        echo '<input type="hidden" name="choose" value="'.$choose.'" />';
    }

    echo "<hr width=\"640\" align=\"center\" noshade=\"noshade\" size=\"1\" />";
    echo "<table border=\"0\" cellspacing=\"2\" cellpadding=\"2\" width=\"640\" class=\"files\">";
    echo "<tr>";
    echo "<th width=\"5\"></th>";
    echo "<th align=\"left\" class=\"header name\">$strname</th>";
    echo "<th align=\"right\" class=\"header size\">$strsize</th>";
    echo "<th align=\"right\" class=\"header date\">$strmodified</th>";
    echo "<th align=\"right\" class=\"header commands\">$straction</th>";
    echo "</tr>\n";


    $count = 0;

    if (!empty($dirlist)) {
        foreach ($dirlist as $dir) {
            if (empty($dir->title)) {
                continue;
            }

            echo "<tr class=\"folder\">";

            if (($dir->title == '..') || ($dir->title == $strbrowserepo)) {
                if (!empty($dir->url)) {
                    print_cell();
                    if (!empty($search)) {
                        print_cell('left', '<a href="' . $dir->url .'"><img src="'.$CFG->pixpath.'/f/parent.gif" height="16" width="16" alt="'.$strbrowserepo.'" /></a> <a href="' . $dir->url . '">'.$strbrowserepo.'</a>', 'name');
                    } else {
                        print_cell('left', '<a href="' . $dir->url .'"><img src="'.$CFG->pixpath.'/f/parent.gif" height="16" width="16" alt="'.get_string('parentfolder').'" /></a> <a href="' . $dir->url . '">'.get_string('parentfolder').'</a>', 'name');
                    }
                    print_cell();
                    print_cell();
                    print_cell();
                } else {
                    $pdir    = urlencode($dir->title);
                    $fileurl = $dir->uuid;

                    print_cell();
                    print_cell('left', '<a href="index.php?id='.$id.'&amp;shared='.$shared.'&amp;oid='.$oid.'&amp;userid='.$userid.'&amp;wdir='.$pdir.'&amp;uuid='.$fileurl.'&amp;choose='.$choose.'"><img src="'.$CFG->pixpath.'/f/parent.gif" height="16" width="16" alt="'.get_string('parentfolder').'" /></a> <a href="index.php?id='.$id.'&amp;shared='.$shared.'&amp;oid='.$oid.'&amp;userid='.$userid.'&amp;wdir='.$pdir.'&amp;uuid='.$fileurl.'&amp;choose='.$choose.'">'.get_string('parentfolder').'</a>', 'name');
                    print_cell();
                    print_cell();
                    print_cell();
                }
            } else {
                $count++;

                $filename = $dir->title;
                $pdir     = urlencode($filename);
                $fileurl  = $dir->uuid;
                $filesafe = rawurlencode($dir->title);
                $filesize = '-';
                $filedate = !empty($dir->modified) ? userdate($dir->modified, "%d %b %Y, %I:%M %p") : '-';

                if ($canedit) {
                    print_cell("center", "<input type=\"checkbox\" name=\"file$count\" value=\"$fileurl\" />", 'checkbox');
                } else {
                    print_cell();
                }

                print_cell("left", "<a href=\"index.php?id=$id&amp;shared=$shared&amp;oid=$oid&amp;userid=$userid&amp;wdir=$pdir&amp;uuid=$fileurl&amp;choose=$choose\"><img src=\"$CFG->pixpath/f/folder.gif\" height=\"16\" width=\"16\" border=\"0\" alt=\"Folder\" /></a> <a href=\"index.php?id=$id&amp;shared=$shared&amp;oid=$oid&amp;userid=$userid&amp;wdir=$pdir&amp;uuid=$fileurl&amp;choose=$choose\">".htmlspecialchars($dir->title)."</a>", 'name');
                print_cell("right", $filesize, 'size');
                print_cell("right", $filedate, 'date');
                print_cell('right', '-', 'commands');
            }

            echo "</tr>";
        }
    }


    if (!empty($filelist)) {
        asort($filelist);
echo '
<script language="javascript">
<!--
    function openextpopup(url,name,options,fullscreen) {
      windowobj = window.open(url,name,options);
      if (fullscreen) {
         windowobj.moveTo(0,0);
         windowobj.resizeTo(screen.availWidth,screen.availHeight);
      }
      windowobj.focus();
      return false;
    }
// -->
</script>
';

        foreach ($filelist as $file) {
//            $icon = $file->icon;
            $icon = mimeinfo('icon', $file->title);

            $count++;

            $filename    = $file->title;
            $fileurl     = $CFG->wwwroot . '/file/repository/alfresco/openfile.php?uuid=' . $file->uuid;
            $filesafe    = rawurlencode($file->title);
            $fileurlsafe = rawurlencode($fileurl);
            $filedate    = !empty($file->modified) ? userdate($file->modified, "%d %b %Y, %I:%M %p") : '-';
            $filesize    = '';

            $selectfile  = $fileurl;

            echo "<tr class=\"file\">";

            if ($canedit) {
                print_cell("center", "<input type=\"checkbox\" name=\"file$count\" value=\"$file->uuid\" />", 'checkbox');
            } else {
                print_cell();
            }

            echo "<td align=\"left\" nowrap=\"nowrap\" class=\"name\">";
            if ($CFG->slasharguments) {
                $ffurl = str_replace('//', '/', "/file.php/$id/$fileurl");
            } else {
                $ffurl = str_replace('//', '/', "/file.php?file=/$id/$fileurl");
            }

            $height   = 480;
            $width    = 640;
            $name     = '_blank';
            $url      = $fileurl;
            $title    = 'Popup window';
            $linkname = "<img src=\"$CFG->pixpath/f/$icon\" class=\"icon\" alt=\"{$file->title}\" />";
            $options  = 'menubar=0,location=0,scrollbars,resizable,width='. $width .',height='. $height;

            if (!empty($search) && !empty($file->parent)) {
                $pdir     = urlencode($file->parent->name);
                $fileurl  = $file->parent->uuid;
                $motext   = get_string('parentfolder', 'repository', $file->parent->name);

                echo "<a href=\"index.php?id=$id&amp;shared=$shared&amp;oid=$oid&amp;userid=$userid&amp;wdir=$pdir&amp;uuid=$fileurl&amp;choose=$choose\" title=\"$motext\"><img src=\"$CFG->pixpath/f/folder.gif\" height=\"16\" width=\"16\" border=\"0\" alt=\"Folder\" /></a> ";
            }

            echo '<a target="'. $name .'" title="'. $title .'" href="'. $url .'" '.">$linkname</a>";

            echo '&nbsp;';

            $linkname = htmlspecialchars($file->title);

            echo '<a target="'. $name .'" title="'. $title .'" href="'. $url .'" '.">$linkname</a>";

            echo "</td>";

            print_cell("right", display_size(isset($file->filesize)? $file->filesize:'-'), 'size');
            print_cell("right", $filedate, 'date');

            if ($choose) {
                $edittext = "<strong><a onclick=\"return set_value('$selectfile')\" href=\"#\">$strchoose</a></strong>&nbsp;";
            } else {
                $edittext = '';
            }

            if (strstr($icon, 'zip.gif') !== false) {
                $edittext .= "<a href=\"index.php?id=$id&amp;shared=$shared&amp;oid=$oid&amp;userid=$userid&amp;uuid=$file->uuid&amp;action=unzip&amp;sesskey=$USER->sesskey&amp;choose=$choose\">$strunzip</a>&nbsp;";
                $edittext .= "<a href=\"index.php?id=$id&amp;shared=$shared&amp;oid=$oid&amp;userid=$userid&amp;uuid=$file->uuid&amp;action=listzip&amp;sesskey=$USER->sesskey&amp;choose=$choose\">$strlist</a> ";
            }

        /// User's cannot download files locally if they cannot access the local file storage.
            if (has_capability('moodle/course:managefiles', $context)) {
                $popupurl = '/files/index.php?id=' . $id . '&amp;shared=' . $shared . '&amp;oid=' . $oid . '&amp;userid=' . $userid .
                            '&amp;repouuid=' . $file->uuid . '&amp;repofile=' . $filesafe . '&amp;dd=1';

                $edittext .= link_to_popup_window($popupurl, 'coursefiles', $strdownloadlocal, 500, 750,
                                                $strdownloadlocal, 'none', true);
            }

            print_cell('right', $edittext, 'commands');

            echo "</tr>";
        }
    }
    echo "</table>";
    echo "<hr width=\"640\" align=\"center\" noshade=\"noshade\" size=\"1\" />";

/// Don't display the editing form buttons (yet).

    if (empty($search)) {
        echo "<table border=\"0\" cellspacing=\"2\" cellpadding=\"2\" width=\"640\">";
        echo "<tr><td>";

        if ($canedit) {
            echo "<input type=\"hidden\" name=\"id\" value=\"$id\" />";
            echo "<input type=\"hidden\" name=\"oid\" value=\"$oid\" />";
            echo "<input type=\"hidden\" name=\"shared\" value=\"$shared\" />";
            echo "<input type=\"hidden\" name=\"userid\" value=\"$userid\" />";
            echo '<input type="hidden" name="choose" value="'.$choose.'" />';
            echo "<input type=\"hidden\" name=\"uuid\" value=\"$uuid\" /> ";
            echo "<input type=\"hidden\" name=\"sesskey\" value=\"$USER->sesskey\" />";

            $options = array (
               'move'   => $strmovetoanotherfolder,
               'delete' => $strdeletecompletely,
               'zip'    => $strcreateziparchive
            );

            if (!empty($count)) {
                choose_from_menu ($options, "action", "", "$strwithchosenfiles...", "javascript:document.dirform.submit()");
            }
        }

        echo "</form>";
        echo "<td align=\"center\">";
        if (!empty($USER->fileop) and ($USER->fileop == "move") and ($USER->filesource <> $uuid)) {
            echo "<form action=\"index.php\" method=\"get\">";
            echo ' <input type="hidden" name="choose" value="'.$choose.'" />';
            echo " <input type=\"hidden\" name=\"id\" value=\"$id\" />";
            echo " <input type=\"hidden\" name=\"oid\" value=\"$oid\" />";
            echo " <input type=\"hidden\" name=\"shared\" value=\"$shared\" />";
            echo " <input type=\"hidden\" name=\"userid\" value=\"$userid\" />";
            echo " <input type=\"hidden\" name=\"uuid\" value=\"$uuid\" />";
            echo " <input type=\"hidden\" name=\"action\" value=\"paste\" />";
            echo " <input type=\"hidden\" name=\"sesskey\" value=\"$USER->sesskey\" />";
            echo " <input type=\"submit\" value=\"$strmovefilestohere\" />";
            echo "</form>";
        }
        echo "</td>";

        if ($canedit) {
            echo "<td align=\"right\">";
                echo "<form action=\"index.php\" method=\"get\">";
                echo ' <input type="hidden" name="choose" value="'.$choose.'" />';
                echo " <input type=\"hidden\" name=\"id\" value=\"$id\" />";
                echo " <input type=\"hidden\" name=\"oid\" value=\"$oid\" />";
                echo " <input type=\"hidden\" name=\"shared\" value=\"$shared\" />";
                echo " <input type=\"hidden\" name=\"userid\" value=\"$userid\" />";
                echo " <input type=\"hidden\" name=\"uuid\" value=\"$uuid\" />";
                echo " <input type=\"hidden\" name=\"action\" value=\"makedir\" />";
                echo " <input type=\"submit\" value=\"$strmakeafolder\" />";
                echo "</form>";
            echo "</td>";
            echo "<td align=\"right\">";
                echo "<form action=\"index.php\" method=\"get\">"; //dummy form - alignment only
                echo " <input type=\"button\" value=\"$strselectall\" onclick=\"checkall();\" />";
                echo " <input type=\"button\" value=\"$strselectnone\" onclick=\"uncheckall();\" />";
                echo "</form>";
            echo "</td>";
            echo "<td align=\"right\">";
                echo "<form action=\"index.php\" method=\"get\">";
                echo ' <input type="hidden" name="choose" value="'.$choose.'" />';
                echo " <input type=\"hidden\" name=\"id\" value=\"$id\" />";
                echo " <input type=\"hidden\" name=\"oid\" value=\"$oid\" />";
                echo " <input type=\"hidden\" name=\"shared\" value=\"$shared\" />";
                echo " <input type=\"hidden\" name=\"userid\" value=\"$userid\" />";
                echo " <input type=\"hidden\" name=\"uuid\" value=\"$uuid\" />";
                echo " <input type=\"hidden\" name=\"action\" value=\"upload\" />";
                echo " <input type=\"submit\" value=\"$struploadafile\" />";
                echo "</form>";
            echo "</td>";
        }

        echo '</tr>';
        echo "</table>";
    } else {
        $url = 'index.php?id=' . $id . '&amp;shared=' . $shared . '&amp;oid=' . $oid . '&amp;userid=' . $userid . '&amp;uuid=' . $uuid;
        echo '<h3><a href="' . $url . '">' . get_string('returntofilelist', 'repository') . '</a></h3>';
    }

    if ($canedit) {
        echo "<hr width=\"640\" align=\"center\" noshade=\"noshade\" size=\"1\" />";
    }
}

function files_get_cm_from_resource_name($clean_name) {
    global $CFG;

    $SQL =  'SELECT a.id  FROM '.$CFG->prefix.'course_modules a, '.$CFG->prefix.'resource b
        WHERE a.instance = b.id AND b.reference = "'.$clean_name.'"';
    $resource = get_record_sql($SQL);
    return $resource->id;
}

function get_dir_name_from_resource($clean_name) {
    global $CFG;

    $LIKE    = sql_ilike();

    $SQL  = 'SELECT * FROM '.$CFG->prefix.'resource WHERE reference '.$LIKE. "\"%$clean_name%\"";
    $resource = get_records_sql($SQL);
    return $resource;
}

?>
