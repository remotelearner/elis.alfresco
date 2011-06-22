<?php
/**
 * Manage all uploaded files in a course file area.
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

//  This file is a hack to files/index.php that removes
//  the headers and adds some controls so that images
//  can be selected within the Richtext editor.

//  All the Moodle-specific stuff is in this top section
//  Configuration and access control occurs here.
//  Must define:  USER, basedir, baseweb, html_header and html_footer
//  USER is a persistent variable using sessions


    require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php';
    require_once $CFG->libdir . '/filelib.php';
    require_once $CFG->dirroot . '/file/repository/alfresco/lib.php';
    require_once $CFG->libdir . '/HTML_TreeMenu-1.2.0/TreeMenu.php';
    require_once $CFG->dirroot . '/file/repository/repository.class.php';


    $id            = required_param('id', PARAM_INT);
    $shared        = optional_param('shared', '', PARAM_ALPHA);
    $userid        = optional_param('userid', 0, PARAM_INT);
    $uuid          = optional_param('uuid', '', PARAM_TEXT);
    $file          = optional_param('file', '', PARAM_PATH);
    $wdir          = optional_param('wdir', '', PARAM_PATH);
    $action        = optional_param('action', '', PARAM_ACTION);
    $name          = optional_param('name', '', PARAM_FILE);
    $oldname       = optional_param('oldname', '', PARAM_FILE);
    $usecheckboxes = optional_param('usecheckboxes', 1, PARAM_INT);
    $save          = optional_param('save', 0, PARAM_BOOL);
    $text          = optional_param('text', '', PARAM_RAW);
    $confirm       = optional_param('confirm', 0, PARAM_BOOL);


    if (!$course = get_record('course', 'id', $id) ) {
        print_error('invalidcourseid', 'repository_alfresco', '', $id);
    }

    require_login($course);
//    require_capability('moodle/course:managefiles', get_context_instance(CONTEXT_COURSE, $id));

    if (empty($CFG->repository)) {
        print_error('nodefaultrepositoryplugin', 'repository');
    }

    if (!$repo = repository_factory::factory($CFG->repository)) {
        print_error('couldnotcreaterepositoryobject', 'repository');
    }

/// Get the context instance for where we originated viewing this browser from.
    if ($id == SITEID) {
        $context = get_context_instance(CONTEXT_SYSTEM, SITEID);
    } else {
        $context = get_context_instance(CONTEXT_COURSE, $id);
    }

/// Determine whether the current user has editing persmissions.
    $canedit = false;

    if (empty($userid) && empty($shared)) {
        if (($id == SITEID && has_capability('block/repository:createsitecontent', $context, $USER->id)) ||
            ($id != SITEID && has_capability('block/repository:createcoursecontent', $context, $USER->id)) ||
            ($id != SITEID && has_capability('block/repository:createorganizationcontent', $context, $USER->id))) {

            $canedit = true;
        }
    } else if (empty($userid) && $shared == 'true') {
        if (!empty($USER->access['rdef'])) {
            foreach ($USER->access['rdef'] as $rdef) {
                if (isset($rdef['block/repository:createsharedcontent']) &&
                    $rdef['block/repository:createsharedcontent']) {

                    $canedit = true;
                }
            }
        }
    } else {
        if ($USER->id == $userid) {
            if (!empty($USER->access['rdef'])) {
                foreach ($USER->access['rdef'] as $rdef) {
                    if (isset($rdef['block/repository:viewowncontent']) && $rdef['block/repository:viewowncontent']) {
                        $canedit = true;
                    }
                }
            }
        } else {
            if (has_capability('block/repository:createsitecontent', $context, $USER->id)) {
                $canedit = true;
            } else if (has_capability('block/repository:createorganizationcontent', $context, $USER->id)) {
                $canedit = true;
            }
        }
    }

    function html_footer() {
        echo "\n\n</body>\n</html>";
    }

    function html_header($course, $wdir, $formfield=""){
        global $CFG, $ME, $USER, $id, $shared, $userid, $uuid, $repo;

    /// Get the appropriate context for the site or a course.
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
                        $ucontext['block/repository:viewowncontent']) {

                        $personalfiles = true;
                    }
                }
            }

            if (!$personalfiles) {
                $capabilityname = get_capability_string('block/repository:viewowncontent');
                print_error('nopermissions', '', '', $capabilityname);
                exit;
            }

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
                        $ucontext['block/repository:viewsharedcontent']) {

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

        } else if (!empty($id) && $id != SITEID) {
            require_capability('block/repository:viewcoursecontent', $context, $USER->id);

            if (empty($uuid)) {
                $uuid = $repo->get_course_store($id);
            }
        } else {
            require_capability('block/repository:viewsitecontent', $context, $USER->id);
        }

        ?>
        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
            "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html>
        <head>
        <meta http-equiv="content-type" content="text/html; charset=utf-8" />
        <title>coursefiles</title>
        <script type="text/javascript">
//<![CDATA[


        function set_value(params) {
            /// function's argument is an object containing necessary values
            /// to export parent window (url,isize,itype,iwidth,iheight, imodified)
            /// set values when user click's an image name.
            var upper = window.parent;
            var insimg = upper.document.getElementById('f_url');

            try {
                if(insimg != null) {
                    if(params.itype.indexOf("image/gif") == -1 && params.itype.indexOf("image/jpeg") == -1 && params.itype.indexOf("image/png") == -1) {
                        alert("<?php print_string("notimage","editor");?>");
                        return false;
                    }
                    for(field in params) {
                        var value = params[field];
                        switch(field) {
                            case "url"   :   upper.document.getElementById('f_url').value = value;
                                     upper.ipreview.location.replace('<?php echo $CFG->wwwroot; ?>/lib/editor/htmlarea/popups/preview.php?id='+ <?php print($course->id);?> +'&imageurl='+ value);
                                break;
                            case "isize" :   upper.document.getElementById('isize').value = value; break;
                            case "itype" :   upper.document.getElementById('itype').value = value; break;
                            case "iwidth":    upper.document.getElementById('f_width').value = value; break;
                            case "iheight":   upper.document.getElementById('f_height').value = value; break;
                        }
                    }
                } else {
                    for(field in params) {
                        var value = params[field];
                        switch(field) {
                            case "url" :
                                //upper.document.getElementById('f_href').value = value;
                                upper.opener.document.getElementById('f_href').value = value;
                                upper.close();
                                break;
                            //case "imodified" : upper.document.getElementById('imodified').value = value; break;
                            //case "isize" : upper.document.getElementById('isize').value = value; break;
                            //case "itype" : upper.document.getElementById('itype').value = value; break;
                        }
                    }
                }
            } catch(e) {
                if ( window.tinyMCE != "undefined" || window.TinyMCE != "undefined" ) {
                    upper.opener.Dialog._return(params.url);
                    upper.close();
                } else {
                    alert("Something odd just occurred!!!");
                }
            }
            return false;
        }

        function set_dir(strdir) {
            // sets wdir values
            var upper = window.parent.document;
            if(upper) {
                for(var i = 0; i < upper.forms.length; i++) {
                    var f = upper.forms[i];
                    if(f.wdir != undefined) { //TODO: this needs to be replaced since it never seems to be used
                        try {
                            f.wdir.value = strdir;
                        } catch (e) {

                        }
                    }
                }
            }
        }

        function set_rename(strfile) {
            var upper = window.parent.document;
            //ERROR: element irename does not exist needs to be replaced with proper element
//            upper.getElementById('irename').value = strfile;  //this or next line
//            upper.getElementById('irename').file.value = strfile;
            return true;
        }

        function reset_value() {
            var upper = window.parent.document;
            for(var i = 0; i < upper.forms.length; i++) {
                var f = upper.forms[i];
                for(var j = 0; j < f.elements.length; j++) {
                	var e = f.elements[j];
                	// Do not reset submit, button, hidden, or select-one types
                    if(e.type != "submit" && e.type != "button" && e.type != "hidden" && e.type != "select-one") {
                    	try {
                            e.value = "";
                        } catch (e) {
                        }
                    }
                }
            }
            //ERROR: there is no element irename this needs to be replaced with the proper element
//            upper.getElementById('irename').value = 'xx';

            var prev = window.parent.ipreview;
            if(prev != null) {
                prev.location.replace('about:blank');
            }
            var uploader = window.parent.document.forms['uploader'];
            if(uploader != null) {
                uploader.reset();
            }
            set_dir('<?php print($wdir);?>');
            return true;
        }
//]]>
        </script>
        <style type="text/css">
        body {
            background-color: white;
            margin-top: 2px;
            margin-left: 4px;
            margin-right: 4px;
        }
        body,p,table,td,input,select,a {
            font-family: Tahoma, sans-serif;
            font-size: 11px;
        }
        select {
            position: absolute;
            top: -20px;
            left: 0px;
        }
        img.icon {
          vertical-align:middle;
          margin-right:4px;
          width:16px;
          height:16px;
          border:0px;
        }
        </style>
        </head>
        <body onload="reset_value();">

        <?php
    }

    $baseweb = $CFG->wwwroot;

//  End of configuration and access control


    if ($wdir == '') {
        $wdir='/';
    }

    switch ($action) {
        case "upload":
            if (!$canedit) {
                print_error('youdonothaveaccesstothisfunctionality', 'repository_alfresco');
            }

            html_header($course, $wdir);
            require_once($CFG->dirroot . '/lib/uploadlib.php');

            if ($save and confirm_sesskey()) {
                if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && !count($_POST)) {
                    /*  This situation is likely the result of the user
                        attempting to upload a file larger than POST_MAX_SIZE
                        See bug MDL-14000 */
                    notify(get_string('uploadserverlimit'));
                }

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

                // We need to get the last location we were browsing files from in order to upload the file there.
                if (($uuid = $repo->get_repository_location($id, $userid, $shared)) !== false) {
                    if (isset($_FILES['userfile'])) {
                        $issafe = true;

                        // Determine if this user has enough storage space left in their quota to upload this file.
                        if (!alfresco_quota_check($_FILES['userfile']['size'], $USER)) {
                            $issafe = false;

                            if ($quotadata = alfresco_quota_info($USER->username)) {
                                $a = new stdClass;
                                $a->current = display_size($quotadata->current);
                                $a->max     = display_size($quotadata->quota);

                                $msg = '<p class="errormessage">' . get_string('erroruploadquotasize', 'repository_alfresco', $a) . '</p>';
                            } else {
                                $msg = '<p class="errormessage">' . get_string('erroruploadquotasize', 'repository_alfresco') . '</p>';
                            }

                            print_simple_box($msg, '', '', '', '', 'errorbox');
                        }

                        // Make sure that the uploaded filename does not exist in the destination directory.
                        if ($dir = $repo->read_dir($uuid)) {
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
                                debugging(get_string('erroruploadingfile', 'repository_alfresco'), DEBUG_DEVELOPER);
                            }
                        }
                    }
                }

                if ($id != SITEID) {
                    displaydir($uuid, $wdir, $id);
                } else {
                    displaydir($uuid, $wdir);
                }

            } else {
                $upload_max_filesize = get_max_upload_file_size($CFG->maxbytes);
                $filesize            = display_size($upload_max_filesize);

                $struploadafile    = get_string("uploadafile");
                $struploadthisfile = get_string("uploadthisfile");
                $strmaxsize        = get_string("maxsize", "", $filesize);
                $strcancel         = get_string("cancel");

                $info = $repo->get_info($uuid);

               // Build up the URL used in the upload form.
                $vars = array(
                    'id'      => $id,
                    'shared'  => $shared,
                    'userid'  => $userid,
                    'uuid'    => $uuid,
                    'action'  => 'upload',
                    'sesskey' => $USER->sesskey,
                    'save'    => urlencode($struploadthisfile)
                );

                $action = 'coursefiles.php?';

                $count = count($vars);
                $i     = 0;
                foreach ($vars as $var => $val) {
                    $action .= $var . '=' . $val . ($i < $count - 1 ? '&amp;' : '');
                    $i++;
                }

                echo "<p>$struploadafile ($strmaxsize) --> <strong>{$info->title}</strong>";
                echo "<table border=\"0\"><tr><td colspan=\"2\">\n";
                echo "<form enctype=\"multipart/form-data\" method=\"post\" action=\"$action\">\n";

                // Include the form variables.
                unset($vars['save']);

                foreach ($vars as $var => $val) {
                    echo '    <input type="hidden" name="' . $var . '" value="' . $val . '" />' . "<br />";
                }

                upload_print_form_fragment(1,array('userfile'),null,false,null,$course->maxbytes,0,false);

                echo " </td><tr><td align=\"right\">";
                echo " <input type=\"submit\" name=\"save\" value=\"$struploadthisfile\" />\n";
                echo "</form>\n";
                echo "</td>\n<td>\n";
                echo "<form action=\"coursefiles.php\" method=\"get\">\n";
                echo " <input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
                echo " <input type=\"hidden\" name=\"userid\" value=\"$userid\" />";
                echo " <input type=\"hidden\" name=\"uuid\" value=\"$uuid\" />";
                echo " <input type=\"hidden\" name=\"action\" value=\"cancel\" />\n";
                echo " <input type=\"submit\" value=\"$strcancel\" />\n";
                echo "</form>\n";
                echo "</td>\n</tr>\n</table>\n";
            }
            html_footer();
            break;

        case "delete":
            if (!$canedit) {
                print_error('youdonothaveaccesstothisfunctionality', 'repository_alfresco');
            }

            if ($confirm and confirm_sesskey()) {
                html_header($course, $wdir);

                $puuid = '';

                if (!empty($USER->filelist)) {
                    foreach ($USER->filelist as $uuid) {
                        if (empty($puuid)) {
                            $puuid = $repo->get_parent($uuid)->uuid;
                        }

                        if (!alfresco_delete($uuid)) {
                            $node = alfresco_node_properties($uuid);
                            debugging(get_string('couldnotdeletefile', 'repository_alfresco', $a));
                        }
                    }
                }

                clearfilelist();
                if ($id != SITEID) {
                    displaydir($puuid, $wdir, $id);
                } else {
                    displaydir($puuid, $wdir);
                }
                html_footer();

            } else {
                html_header($course, $wdir);
                if (setfilelist($_POST)) {
                    echo "<p align=center>".get_string("deletecheckwarning").":</p>";
                    print_simple_box_start("center");
                    printfilelist($USER->filelist);
                    print_simple_box_end();
                    echo "<br />";
                    $frameold = $CFG->framename;
                    $CFG->framename = "ibrowser";
                    notice_yesno(get_string("deletecheckfiles"),
                                "coursefiles.php?id=$id&amp;userid=$userid&amp;wdir=$wdir&amp;action=delete&amp;confirm=1&amp;sesskey=$USER->sesskey",
                                "coursefiles.php?id=$id&amp;userid=$userid&amp;wdir=$wdir&amp;action=cancel");
                    $CFG->framename = $frameold;
                } else {
                    if ($id != SITEID) {
                        displaydir($uuid, $wdir, $id);
                    } else {
                        displaydir($uuid, $wdir);
                    }
                }
                html_footer();
            }
            break;
/*
        case "move":
            html_header($course, $wdir);
            if ($count = setfilelist($_POST) and confirm_sesskey()) {
                $USER->fileop     = $action;
                $USER->filesource = $wdir;
                echo "<p align=\"center\">";
                print_string("selectednowmove", "moodle", $count);
                echo "</p>";
            }
            displaydir($wdir);
            html_footer();
            break;

        case "paste":
            html_header($course, $wdir);
            if (isset($USER->fileop) and $USER->fileop == "move" and confirm_sesskey()) {
                foreach ($USER->filelist as $file) {
                    $shortfile = basename($file);
                    $oldfile = $basedir.$file;
                    $newfile = $basedir.$wdir."/".$shortfile;
                    if (!rename($oldfile, $newfile)) {
                        echo "<p>Error: $shortfile not moved";
                    }
                }
            }
            clearfilelist();
            displaydir($wdir);
            html_footer();
            break;

        case "rename":
            if (!empty($name) and confirm_sesskey()) {
                html_header($course, $wdir);
                $name    = clean_filename($name);
                if (file_exists($basedir.$wdir."/".$name)) {
                    echo "Error: $name already exists!";
                } else if (!@rename($basedir.$wdir."/".$oldname, $basedir.$wdir."/".$name)) {
                    echo "Error: could not rename $oldname to $name";
                }
                displaydir($wdir);

            } else {
                $strrename = get_string("rename");
                $strcancel = get_string("cancel");
                $strrenamefileto = get_string("renamefileto", "moodle", $file);
                html_header($course, $wdir, "form.name");
                echo "<p>$strrenamefileto:";
                echo "<table border=\"0\">\n<tr>\n<td>\n";
                echo "<form action=\"coursefiles.php\" method=\"post\" id=\"form\">\n";
                echo " <input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
                echo " <input type=\"hidden\" name=\"userid\" value=\"$userid\" />\n";
                echo " <input type=\"hidden\" name=\"uuid\" value=\"$uuid\" />\n";
                echo " <input type=\"hidden\" name=\"wdir\" value=\"$wdir\" />\n";
                echo " <input type=\"hidden\" name=\"action\" value=\"rename\" />\n";
                echo " <input type=\"hidden\" name=\"sesskey\" value=\"$USER->sesskey\" />\n";
                echo " <input type=\"hidden\" name=\"oldname\" value=\"$file\" />\n";
                echo " <input type=\"text\" name=\"name\" size=\"35\" value=\"$file\" />\n";
                echo " <input type=\"submit\" value=\"$strrename\" />\n";
                echo "</form>\n";
                echo "</td><td>\n";
                echo "<form action=\"coursefiles.php\" method=\"get\">\n";
                echo " <input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
                echo " <input type=\"hidden\" name=\"wdir\" value=\"$wdir\" />\n";
                echo " <input type=\"hidden\" name=\"action\" value=\"cancel\" />\n";
                echo " <input type=\"submit\" value=\"$strcancel\" />\n";
                echo "</form>";
                echo "</td></tr>\n</table>\n";
            }
            html_footer();
            break;
*/
        case "mkdir":
            if (!$canedit) {
                print_error('youdonothaveaccesstothisfunctionality', 'repository_alfresco');
            }

            if (!empty($name) and confirm_sesskey()) {
                html_header($course, $wdir);

                // We need to get the last location we were browsing files from in order to make the directory there.
                if (($uuid = $repo->get_repository_location($id, $userid, $shared)) !== false) {
                    $name = clean_filename($name);

                    if ($repo->dir_exists($name, $uuid)) {
                        debugging(get_string('errordirectorynameexists', 'repository_alfresco', $name));
                    } else if ($repo->create_dir($name, $uuid) === false) {
                        debugging(get_string('errorcouldnotcreatedirectory', 'repository_alfresco', $name));
                    }
                }

                if ($id != SITEID) {
                    displaydir($uuid, $wdir, $id);
                } else {
                    displaydir($uuid, $wdir);
                }

            } else {
                $strcreate = get_string("create");
                $strcancel = get_string("cancel");
                $strcreatefolder = get_string("createfolder", "moodle", $wdir);
                html_header($course, $wdir, "form.name");
                echo "<p>$strcreatefolder:";
                echo "<table border=\"0\">\n<tr><td>\n";
                echo "<form action=\"coursefiles.php\" method=\"post\" name=\"form\">\n";
                echo " <input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
                echo " <input type=\"hidden\" name=\"userid\" value=\"$userid\" />";
                echo " <input type=\"hidden\" name=\"uuid\" value\"$uuid\" />";
                echo " <input type=\"hidden\" name=\"action\" value=\"mkdir\" />\n";
                echo " <input type=\"hidden\" name=\"sesskey\" value=\"$USER->sesskey\" />\n";
                echo " <input type=\"text\" name=\"name\" size=\"35\" />\n";
                echo " <input type=\"submit\" value=\"$strcreate\" />\n";
                echo "</form>\n";
                echo "</td><td>\n";
                echo "<form action=\"coursefiles.php\" method=\"get\">\n";
                echo " <input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
                echo " <input type=\"hidden\" name=\"userid\" value=\"$userid\" />";
                echo " <input type=\"hidden\" name=\"uuid\" value\"$uuid\" />";
                echo " <input type=\"hidden\" name=\"action\" value=\"cancel\" />\n";
                echo " <input type=\"submit\" value=\"$strcancel\" />\n";
                echo "</form>\n";
                echo "</td>\n</tr>\n</table>\n";
            }
            html_footer();
            break;
/*
        case "edit":
            html_header($course, $wdir);
            if (($text != '') and confirm_sesskey()) {
                $fileptr = fopen($basedir.$file,"w");
                fputs($fileptr, stripslashes($text));
                fclose($fileptr);
                displaydir($wdir);

            } else {
                $streditfile = get_string("edit", "", "<strong>$file</strong>");
                $fileptr  = fopen($basedir.$file, "r");
                $contents = fread($fileptr, filesize($basedir.$file));
                fclose($fileptr);

                print_heading("$streditfile");

                echo "<table><tr><td colspan=\"2\">\n";
                echo "<form action=\"coursefiles.php\" method=\"post\" name=\"form\" $onsubmit>\n";
                echo " <input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
                echo " <input type=\"hidden\" name=\"wdir\" value=\"$wdir\" />\n";
                echo " <input type=\"hidden\" name=file value=\"$file\" />";
                echo " <input type=\"hidden\" name=\"action\" value=\"edit\" />\n";
                echo " <input type=\"hidden\" name=\"sesskey\" value=\"$USER->sesskey\" />\n";
                print_textarea(false, 25, 80, 680, 400, "text", $contents);
                echo "</td>\n</tr>\n<tr>\n<td>\n";
                echo " <input type=\"submit\" value=\"".get_string("savechanges")."\" />\n";
                echo "</form>\n";
                echo "</td>\n<td>\n";
                echo "<form action=\"coursefiles.php\" method=\"get\">\n";
                echo " <input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
                echo " <input type=\"hidden\" name=\"wdir\" value=\"$wdir\" />\n";
                echo " <input type=\"hidden\" name=\"action\" value=\"cancel\" />\n";
                echo " <input type=\"submit\" value=\"".get_string("cancel")."\" />\n";
                echo "</form>\n";
                echo "</td></tr></table>\n";

                if ($usehtmleditor) {
                    use_html_editor("text");
                }
            }
            html_footer();
            break;
*/
        case "zip":
            if (!$canedit) {
                print_error('youdonothaveaccesstothisfunctionality', 'repository_alfresco');
            }

            if (!empty($name) and confirm_sesskey()) {
                html_header($course, $wdir);

                // We need to get the last location we were browsing files from in order to upload the zip file there.
                if (($uuid = $repo->get_repository_location($id, $userid, $shared)) !== false) {
                    $name     = clean_filename($name);
                    $filepath = $CFG->dataroot . '/temp/alfresco/' . $name;
                    $destpath = $CFG->dataroot . '/temp/alfresco/zip-' . random_string(5) . '/';

                    if (!is_dir($destpath)) {
                        mkdir($destpath, $CFG->directorypermissions, true);
                    }

                    foreach ($USER->filelist as $fuuid) {
                        $file = $repo->get_info($fuuid);

                        if (alfresco_get_type($file->uuid) == 'folder') {
                            $dirfiles = $repo->download_dir($destpath, $fuuid);

                            if ($dirfiles !== false) {
                                $filelist[] = $destpath . $file->title;
                            }
                        } else {
                            if ($repo->read_file($fuuid, $destpath . $file->title)) {
                                $filelist[] = $destpath . $file->title;
                            }
                        }
                    }

                    if (!zip_files($filelist, $filepath)) {
                        print_error('zipfileserror');
                    }

                    $repo->upload_file('', $filepath, $uuid);

                    fulldelete($destpath);
                    fulldelete($filepath);
                }

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
                    echo "<p align=\"center\">".get_string("whattocallzip");
                    echo "<table border=\"0\">\n<tr>\n<td>\n";
                    echo "<form action=\"coursefiles.php\" method=\"post\" name=\"form\">\n";
                    echo " <input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
                    echo " <input type=\"hidden\" name=\"userid\" value=\"$userid\" />\n";
                    echo " <input type=\"hidden\" name=\"uuid\" value=\"$uuid\" />\n";
                    echo " <input type=\"hidden\" name=\"action\" value=\"zip\" />\n";
                    echo " <input type=\"hidden\" name=\"sesskey\" value=\"$USER->sesskey\" />\n";
                    echo " <INPUT type=\"text\" name=\"name\" size=\"35\" value=\"new.zip\" />\n";
                    echo " <input type=\"submit\" value=\"".get_string("createziparchive")."\" />";
                    echo "</form>\n";
                    echo "</td>\n<td>\n";
                    echo "<form action=\"coursefiles.php\" method=\"get\">\n";
                    echo " <input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
                    echo " <input type=\"hidden\" name=\"userid\" value=\"$userid\" />\n";
                    echo " <input type=\"hidden\" name=\"uuid\" value=\"$uuid\" />\n";
                    echo " <input type=\"hidden\" name=\"action\" value=\"cancel\" />\n";
                    echo " <input type=\"submit\" value=\"".get_string("cancel")."\" />\n";
                    echo "</form>\n";
                    echo "</td>\n</tr>\n</table>\n";
                } else {
                    displaydir($wdir);
                    clearfilelist();
                }
            }
            html_footer();
            break;

        case "unzip":
            if (!$canedit) {
                print_error('youdonothaveaccesstothisfunctionality', 'repository_alfresco');
            }

            html_header($course, $wdir);

            if (!empty($uuid) and confirm_sesskey()) {
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

                $strok        = get_string("ok");
                $strunpacking = get_string('unpacking', '');

                echo "<p align=\"center\">$strunpacking:</p>";

                if (!unzip_file($filepath, $destpath)) {
                    if (isset($fp)) {
                        fclose($fp);
                    }

                    fulldelete($filepath);
                    fulldelete($destpath);

                    print_error('unzipfileserror', 'error');
                }

                if (($duuid = $repo->create_dir(basename($filename, '.zip'), $puuid)) === false) {
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

                echo "<center><form action=\"coursefiles.php\" method=\"get\">\n";
                echo " <input type=\"hidden\" name=\"id\" value=\"$id\" />";
                echo " <input type=\"hidden\" name=\"userid\" value=\"$userid\" />";
                echo " <input type=\"hidden\" name=\"uuid\" value=\"$puuid\" />";
                echo " <input type=\"hidden\" name=\"action\" value=\"cancel\" />\n";
                echo " <input type=\"submit\" value=\"$strok\" />\n";
                echo "</form>\n";
                echo "</center>\n";
            } else {
                displaydir($wdir);
            }
            html_footer();
            break;

        case "listzip":
            html_header($course, $wdir);

            if (!empty($uuid) and confirm_sesskey()) {
                if (($parent = $repo->get_parent($uuid)) !== false) {
                    $puuid = $parent->uuid;
                } else {
                    $puuid = '';
                }

                $node = $repo->get_info($uuid);

                $filename = $node->title;
                $filepath = $CFG->dataroot . '/temp/alfresco/' . $filename;

            /// Write the file contents to a temporary file (if needed).
                if (!file_exists($filepath)) {
                    if (($filedata = $repo->read_file($uuid, $filepath)) == false) {
                        print_error('couldnotgetfiledataforuuid', 'repository_alfresco', '', $uuid);
                    }
                }

                $strname      = get_string("name");
                $strsize      = get_string("size");
                $strmodified  = get_string("modified");
                $strok        = get_string("ok");
                $strlistfiles = get_string("listfiles", "", $file);

                echo "<p align=\"center\">$strlistfiles:</p>";

                include_once($CFG->libdir . '/pclzip/pclzip.lib.php');
                $archive = new PclZip(cleardoubleslashes($filepath));
                if (!$list = $archive->listContent(cleardoubleslashes($filepath))) {
                    debugging($archive->errorInfo(true));

                } else {
                    echo "<table cellpadding=\"4\" cellspacing=\"2\" border=\"0\">\n";
                    echo "<tr>\n<th align=\"left\" scope=\"col\">$strname</th><th align=\"right\" scope=\"col\">$strsize</th><th align=\"right\" scope=\"col\">$strmodified</th></tr>";
                    foreach ($list as $item) {
                        echo "<tr>";
                        print_cell("left", $item['filename']);
                        if (! $item['folder']) {
                            print_cell("right", display_size($item['size']));
                        } else {
                            echo "<td>&nbsp;</td>\n";
                        }
                        $filedate  = userdate($item['mtime'], get_string("strftimedatetime"));
                        print_cell("right", $filedate);
                        echo "</tr>\n";
                    }
                    echo "</table>\n";
                }
                echo "<br /><center><form action=\"coursefiles.php\" method=\"get\">\n";
                echo " <input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
                echo " <input type=\"hidden\" name=\"userid\" value=\"$userid\" />\n";
                echo " <input type=\"hidden\" name=\"uuid\" value=\"$puuid\" />\n";
                echo " <input type=\"hidden\" name=\"action\" value=\"cancel\" />\n";
                echo " <input type=\"hidden\" name=\"sesskey\" value=\"$USER->sesskey\" />\n";
                echo " <input type=\"submit\" value=\"$strok\" />\n";
                echo "</form>\n";
                echo "</center>\n";
            } else {
                displaydir($wdir);
            }
            html_footer();
            break;

        case "cancel":
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
            $count++;
            $val = rawurldecode($val);
            if (!detect_munged_arguments($val, 0)) {
                $USER->filelist[] = $val;
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
            $filename = $file->filename;
            $fileurl  = $CFG->wwwroot . '/file/repository/alfresco/openfile.php?uuid=' . $uuid;

            echo "<img src=\"{$file->icon}\"  height=\"16\" width=\"16\" alt=\"\" /> " .
                 $file->filename . "<br />";
        }
    }
}


function print_cell($alignment="center", $text="&nbsp;") {
    echo "<td align=\"$alignment\" nowrap=\"nowrap\">\n";
    echo "$text";
    echo "</td>\n";
}

/// This function get's the image size
function get_image_size($uuid) {
    global $repo;

    /// Check if file exists
    if (!$finfo = $repo->get_info($uuid)) {
        return false;
    } else {
        /// Get the mime type so it really an image.
        if(mimeinfo("icon", $finfo->filename) != "image.gif") {
            return false;
        } else {
            $array_size = getimagesize(str_replace($finfo->title, rawurlencode($finfo->title), $finfo->fileurl) .
                                       '?alf_ticket=' . alfresco_utils_get_ticket());
            return $array_size;
        }
    }
    //unset($filepath, $array_size);
}

function displaydir($uuid, $wdir, $courseid = 0) {
    global $USER, $CFG;
    global $basedir;
    global $usecheckboxes;
    global $id;
    global $shared;
    global $userid;
    global $repo;

    $fullpath = $basedir.$wdir;

    $dirlist  = array();
    $filelist = array();

    $parentdir = new Object();

    if (!empty($userid)) {
        $ustore = $repo->get_user_store($userid);
    }

    if (!empty($userid) && ($uuid == '' || $uuid == $ustore)) {
        $parentdir->title = '';
        $parentdir->url   = '';

    } else if (!empty($shared) && ($uuid == '' || $uuid == $repo->suuid)) {
        $parentdir->title = '';
        $parentdir->url   = '';

    } else if ((($uuid == '') || (!$parent = $repo->get_parent($uuid))) ||
               (($id != SITEID) && ($uuid == '' || $uuid == $repo->get_course_store($id)))) {

        $parentdir->title = '';
        $parentdir->url   = '';

    } else {
        $parentdir->uuid  = $parent->uuid;
        $parentdir->name  = $parent->title;
        $parentdir->title = '..';
    }

    $dirlist[] = $parentdir;
    $catselect = array();

    if (empty($uuid) && $id != SITEID) {
        $uuid = $repo->get_course_store($id);
    }

    if (!empty($search)) {
        if (($data = data_submitted()) && confirm_sesskey()) {
            if (!empty($data->categories)) {
                $catselect = $data->categories;
            }
        }

        $search  = stripslashes($search);
        $repodir = $repo->search($search, $catselect);
    } else {
        $repodir = $repo->read_dir($uuid);
    }

    // Store the UUID value that we are currently browsing.
    $repo->set_repository_location($uuid, $id, $userid, $shared);

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

    $strfile                = get_string("file");
    $strname                = get_string("name");
    $strsize                = get_string("size");
    $strmodified            = get_string("modified");
    $straction              = get_string("action");
    $strmakeafolder         = get_string("makeafolder");
    $struploadafile         = get_string("uploadafile");
    $strwithchosenfiles     = get_string("withchosenfiles");
    $strmovetoanotherfolder = get_string("movetoanotherfolder");
    $strmovefilestohere     = get_string("movefilestohere");
    $strdeletecompletely    = get_string("deletecompletely");
    $strcreateziparchive    = get_string("createziparchive");
    $strrename              = get_string("rename");
    $stredit                = get_string("edit");
    $strunzip               = get_string("unzip");
    $strlist                = get_string("list");
    $strchoose              = get_string("choose");
    $strbrowserepo          = get_string('browserepository', 'repository');
    $strdownloadlocal       = get_string('downloadlocally', 'repository');


    echo "<form action=\"coursefiles.php\" method=\"post\" name=\"dirform\">\n";
    echo '<input type="hidden" name="shared" value="' . $shared . '" />' . "\n";
    echo '<input type="hidden" name="userid" value="' . $userid . '" />' . "\n";
    echo '<input type="hidden" name="uuid" value="' . $uuid . '" />' . "\n";
    echo "<table border=\"0\" cellspacing=\"2\" cellpadding=\"2\" width=\"100%\">\n";

    if (!empty($parentdir->uuid)) {
        print "<tr>\n<td colspan=\"5\">";
        print '<a href="coursefiles.php?id=' . $id . '&amp;uuid=' . $parentdir->uuid . '&amp;shared=' .
              $shared . '&amp;userid=' . $userid . '&amp;wdir=/&amp;usecheckboxes=' . $usecheckboxes . '" ' .
              'onclick="return reset_value();">';
        print "<img src=\"{$CFG->wwwroot}/lib/editor/htmlarea/images/folderup.gif\" height=\"14\" width=\"24\" border=\"0\" alt=\"".get_string('parentfolder')."\" />";
        print "</a></td>\n</tr>\n";
    }

    $count = 0;

    if (!empty($dirlist)) {
        foreach ($dirlist as $dir) {
            if (empty($dir->uuid)) {
                continue;
            }

            $count++;

            if (($dir->title == '..') || ($dir->title == $strbrowserepo)) {
                if (!empty($dir->url)) {
                    if ($usecheckboxes) {
                        print_cell();
                    }

                    if (!empty($search)) {
                        print_cell('left', '<a href="' . $dir->url .'"><img src="'.$CFG->pixpath.'/f/parent.gif" height="16" width="16" alt="'.$strbrowserepo.'" /></a> <a href="' . $dir->url . '">'.$strbrowserepo.'</a>', 'name');
                    } else {
                        print_cell('left', '<a href="' . $dir->url .'"><img src="'.$CFG->pixpath.'/f/parent.gif" height="16" width="16" alt="'.get_string('parentfolder').'" /></a> <a href="' . $dir->url . '">'.get_string('parentfolder').'</a>', 'name');
                    }
                    print_cell();
                    print_cell();
                }
            } else {
                $pdir    = urlencode($dir->title);
                $fileurl = $dir->uuid;

                //$filename = $fullpath."/".$dir;
                //$fileurl  = $wdir."/".$dir;
                $filedate = userdate($dir->modified, "%d %b %Y, %I:%M %p");

                echo "<tr>";

                if ($usecheckboxes) {
                    print_cell("center", "<input type=\"checkbox\" name=\"file$count\" value=\"$fileurl\" onclick=\"return set_rename('$dir->title');\" />");
                }

                $astr = 'coursefiles.php?id=' . $id . '&amp;uuid=' . $fileurl . '&amp;shared=' . $shared . '&amp;userid=' .
                        $userid . '&amp;wdir=/&amp;usecheckboxes=' . $usecheckboxes . '" ' . 'onclick=" return reset_value();"';

                print_cell('left', '<a href="' . $astr .'><img src="' . $CFG->pixpath . '/f/folder.gif" class="icon" ' .
                           'alt=""' . get_string('folder') . '" /></a> <a href="' . $astr . '>' .
                           htmlspecialchars($dir->title) . '</a>');
                print_cell("right", "&nbsp;");
                print_cell("right", $filedate);
            }

            echo "</tr>";
        }
    }


    if (!empty($filelist)) {
        foreach ($filelist as $file) {
            $icon    = mimeinfo('icon', $file->title);
            $imgtype = mimeinfo('type', $file->title);

            $count++;
            $filename = $fullpath."/".$file->title;
            $fileurl  = "$file->uuid";
            $filedate = userdate($file->modified, "%d %b %Y, %I:%M %p");

            $dimensions = get_image_size($file->uuid);
            if($dimensions) {
                $imgwidth = $dimensions[0];
                $imgheight = $dimensions[1];
            } else {
                $imgwidth = "Unknown";
                $imgheight = "Unknown";
            }
            unset($dimensions);
            echo "<tr>\n";

            if ($usecheckboxes) {
                print_cell("center", "<input type=\"checkbox\" name=\"file$count\" value=\"$fileurl\" onclick=\";return set_rename('{$file->title}');\" />");
            }
            echo "<td align=\"left\" nowrap=\"nowrap\">";
            $ffurl = $CFG->wwwroot . '/file/repository/' . $CFG->repository . '/openfile.php?uuid=' . $file->uuid;
            link_to_popup_window ($ffurl, "display",
                                  "<img src=\"{$file->icon}\" class=\"icon\" alt=\"$strfile\" />",
                                  480, 640);
            $file_size = $file->filesize;

            echo "<a onclick=\"return set_value(info = {url: '".$ffurl."',";
            echo " isize: '".$file_size."', itype: '".$imgtype."', iwidth: '".$imgwidth."',";
            echo " iheight: '".$imgheight."', imodified: '".$filedate."' })\" href=\"#\">$file->title</a>";
            echo "</td>\n";

            if ($icon == "zip.gif") {
                $edittext = "<a href=\"coursefiles.php?id=$id&amp;userid=$userid&amp;uuid={$file->uuid}&amp;action=unzip&amp;sesskey=$USER->sesskey\">$strunzip</a>&nbsp;";
                $edittext .= "<a href=\"coursefiles.php?id=$id&amp;userid=$userid&amp;uuid={$file->uuid}&amp;action=listzip&amp;sesskey=$USER->sesskey\">$strlist</a> ";
            } else {
                $edittext = "&nbsp;";
            }
            print_cell("right", "$edittext ");
            print_cell("right", $filedate);

            echo "</tr>\n";
        }
    }
    echo "</table>\n";

    if (empty($wdir)) {
        $wdir = "/";
    }

    echo "<table border=\"0\" cellspacing=\"2\" cellpadding=\"2\">\n";
    echo "<tr>\n<td>";
    echo "<input type=\"hidden\" name=\"id\" value=\"$id\" />\n";
    echo "<input type=\"hidden\" name=\"userid\" value=\"$userid\" />\n";
    echo "<input type=\"hidden\" name=\"uuid\" value=\"$uuid\" />\n";
    echo "<input type=\"hidden\" name=\"sesskey\" value=\"$USER->sesskey\" />\n";
    $options = array (
                   "delete" => "$strdeletecompletely",
                   "zip" => "$strcreateziparchive"
               );
    if (!empty($count)) {
        choose_from_menu ($options, "action", "", "$strwithchosenfiles...", "javascript:getElementById('dirform').submit()");
    }
    echo "</td></tr>\n";
    echo "</table>\n";
    echo "</form>\n";
}

?>
