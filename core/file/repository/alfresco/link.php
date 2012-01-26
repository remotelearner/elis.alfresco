<?php
/**
 * Link to content from Alfresco from the HTML editor.
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

    require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php';
    require_once $CFG->dirroot . '/file/repository/repository.class.php';

    $id     = optional_param('id', SITEID, PARAM_INT);
    $shared = optional_param('shared', '', PARAM_ALPHA);
    $userid = optional_param('userid', 0, PARAM_INT);
    $uuid   = optional_param('uuid', '', PARAM_TEXT);
    $ouuid  = optional_param('ouuid', '', PARAM_TEXT);
    $dd     = optional_param('dd', 0, PARAM_INT);

    $upload_max_filesize = get_max_upload_file_size($CFG->maxbytes);

    require_course_login($id);

    // Make sure the plug-in is enabled and setup correctly.
    if (isset($CFG->repository_plugins_enabled) && strstr($CFG->repository_plugins_enabled, 'alfresco')) {
        if (!$repo = repository_factory::factory('alfresco')) {
            debugging('Could not create repository object.', DEBUG_DEVELOPER);
        }

        // If we don't have something explicitly to load and we didn't get here from the drop-down...
        if (empty($uuid) && empty($dd)) {
            if ($uuid = $repo->get_repository_location($id, $userid, $shared)) {
                redirect($CFG->wwwroot . '/file/repository/alfresco/link.php?id=' . $id . '&amp;userid=' . $userid .
                         '&amp;shared=' . $shared . '&amp;uuid=' . $uuid, '', 0);
            }

            if ($uuid = $repo->get_default_browsing_location($id, $userid, $shared)) {
                redirect($CFG->wwwroot . '/file/repository/alfresco/link.php?id=' . $id . '&amp;userid=' . $userid .
                         '&amp;shared=' . $shared . '&amp;uuid=' . $uuid, '', 0);
            }
        }
    }

    @header('Content-Type: text/html; charset=utf-8');

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
                if (isset($rdef['block/repository:createsharedcontent'])) {
                    $canedit = true;
                }
            }
        }
    } else {
        if ($USER->id == $userid) {
            if (!empty($USER->access['rdef'])) {
                foreach ($USER->access['rdef'] as $rdef) {
                    if (isset($rdef['block/repository:createowncontent'])) {
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


/// Make sure that we have the correct 'base' UUID for a course or user storage area as well
/// as checking for correct permissions.
    if (!empty($userid) && !empty($id)) {
        $personalfiles = false;

        if (!empty($USER->access['rdef'])) {
            foreach ($USER->access['rdef'] as $ucontext) {
                if ($personalfiles) {
                    continue;
                }

                if (isset($ucontext['block/repository:createowncontent']) &&
                    $ucontext['block/repository:createowncontent']) {

                    $personalfiles = true;
                }
            }
        }

        if (!$personalfiles) {
            $capabilityname = get_capability_string('block/repository:createowncontent');
            print_error('nopermissions', '', '', $capabilityname);
            exit;
        }

        if (empty($uuid)) {
            $uuid = $repo->get_user_store($userid);
        }

        $view = 'viewowncontent';

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

        $view = 'viewsharedcontent';

    } else if (!empty($id) && empty($shared) && $id != SITEID) {
        require_capability('block/repository:viewcoursecontent', $context, $USER->id);
        $view = 'viewcoursecontent';

        if (empty($uuid)) {
            $uuid = $repo->get_course_store($id);
        }
    } else {
        require_capability('block/repository:viewsitecontent', $context, $USER->id);
        $view = 'viewsitecontent';
    }

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title><?php print_string("insertlink","editor");?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<script type="text/javascript">
//<![CDATA[

function onCancel() {
  window.close();
  return false;
}

function checkvalue(elm,formname) {
    var el = document.getElementById(elm);
    if(!el.value) {
        alert("Nothing to do!");
        el.focus();
        return false;
    }
}

function submit_form(dothis) {
    if(dothis == "delete") {
        window.fbrowser.document.dirform.action.value = "delete";
    }
    if(dothis == "move") {
        window.fbrowser.document.dirform.action.value = "move";
    }
    if(dothis == "zip") {
        window.fbrowser.document.dirform.action.value = "zip";
    }

    window.fbrowser.document.dirform.submit();
    return false;
}
//]]>
</script>
<style type="text/css">
html, body { background-color: rgb(212,208,200); }
.title {
background-color: #ddddff;
padding: 5px;
border-bottom: 1px solid black;
font-family: Tahoma, sans-serif;
font-weight: bold;
font-size: 14px;
color: black;
}
input,select { font-family: Tahoma, sans-serif; font-size: 11px; }
legend { font-family: Tahoma, sans-serif; font-size: 11px; }
p { margin-left: 10px;
background-color: transparent; font-family: Tahoma, sans-serif;
font-size: 11px; color: black; }
td { font-family: Tahoma, sans-serif; font-size: 11px; }
button { width: 70px; font-family: Tahoma, sans-serif; font-size: 11px; }
#imodified,#itype,#isize {
background-color: rgb(212,208,200);
border: none;
font-family: Tahoma, sans-serif;
font-size: 11px;
color: black;
}
.space { padding: 2px; }
form { margin-bottom: 1px; margin-top: 1px; }
</style>
</head>
<body>
<div class="title"><?php print_string("insertlink","editor");?></div>
  <table width="450" border="0" cellspacing="0" cellpadding="2">
<?php

/// Remote Learner Edit -- BEGIN

/// Build an array of options for a navigation drop-down menu.

/// Make sure the plug-in is enabled and setup correctly.
    if (!empty($repo)) {
        // Build an array of options for a navigation drop-down menu.
        $default = '';

        $opts = $repo->file_browse_options($id, $userid, $ouuid, $shared, '',
                                           'file/repository/alfresco/link.php',
                                           'lib/editor/htmlarea/popups/link.php',
                                           'file/repository/alfresco/link.php', $default);

        if (!empty($opts)) {
            $has_any_capability = true;
            echo '<tr><td colspan="2" align="right">' . get_string('browsefilesfrom', 'repository') . ': ';
            popup_form($CFG->wwwroot . '/', $opts, 'filepluginselect', $default, '');
        }
    }

/// Remote Learner Edit -- END


?>
    <tr>
      <td width="450" valign="top"><fieldset>
        <legend><?php
            if (!empty($has_any_capability)) {
                print_string("filebrowser","editor");
            }
        ?></legend>

        <div class="space"></div>
        <?php

            if (!empty($has_any_capability)) {
                echo "<iframe id=\"fbrowser\" name=\"fbrowser\" src=\"coursefiles.php?id=$id&amp;shared=$shared" .
                     "&amp;userid=$userid&amp;uuid=$uuid\" width=\"420\" height=\"180\"></iframe>";
            }

        ?>
        <p>
        </p>
        <div class="space"></div>
        </fieldset>&nbsp;</td>
    </tr>
  </table>
  <table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
  <td>
    <table border="0" cellpadding="2" cellspacing="0">
          <tr><td><?php print_string("selection","editor");?>: </td>
          <td><form id="idelete">
          <input name="btnDelete" type="submit" id="btnDelete" value="<?php print_string("delete","editor");?>" onclick="return submit_form('delete');" /></form></td>
          <td><form id="izip">
          <input name="btnZip" type="submit" id="btnZip" value="<?php print_string("zip","editor");?>" onclick="return submit_form('zip');" /></form></td>
          </tr>
    </table>
  </td>
  <td>
    <button type="button" name="close" onclick="return onCancel();"><?php print_string("close","editor");?></button>
  </td>
  </tr>
  </table>
    <table border="0" cellpadding="1" cellspacing="1">
    <tr>
      <td height="22"><?php
      if ($canedit) {
          $upload_max_filesize = get_max_upload_file_size($CFG->maxbytes);
?>
          <form id="cfolder" action="coursefiles.php" method="post" target="fbrowser">
          <input type="hidden" name="id" value="<?php print($id);?>" />
          <input type="hidden" name="shared" value="<?php print($shared); ?>" />
          <input type="hidden" name="userid" value="<?php print($userid);?>" />
          <input type="hidden" name="action" value="mkdir" />
          <input type="hidden" name="sesskey" value="<?php p($USER->sesskey) ?>" />
          <input name="name" type="text" id="foldername" size="35" />
          <input name="btnCfolder" type="submit" id="btnCfolder" value="<?php print_string("createfolder","editor");?>" onclick="return checkvalue('foldername','cfolder');" />
          </form>
<?php
    require_once($CFG->libdir . '/uploadlib.php');

    // Build up the URL used in the upload form.
    $vars = array(
        'id'      => $id,
        'shared'  => $shared,
        'userid'  => $userid,
        'uuid'    => $uuid,
        'action'  => 'upload',
        'sesskey' => $USER->sesskey,
        'save'    => urlencode(get_string('upload', 'editor'))
    );

    $action = $CFG->wwwroot . '/file/repository/alfresco/coursefiles.php?';

    $count = count($vars);
    $i     = 0;
    foreach ($vars as $var => $val) {
        $action .= $var . '=' . $val . ($i < $count - 1 ? '&amp;' : '');
        $i++;
    }

    echo '<form action="' . $action . '" method="post" enctype="multipart/form-data" target="fbrowser" id="uploader">';

    // Include the form variables.
    unset($vars['save']);

    foreach ($vars as $var => $val) {
        echo '    <input type="hidden" name="' . $var . '" value="' . $val . '" />' . "\n";
    }

    upload_print_form_fragment(1, array('userfile'), null, false, null, $upload_max_filesize, 0, false);

    echo '<input name="save" type="submit" id="save" onclick="return checkvalue(\'userfile\',\'uploader\');" ' .
         'value="' . get_string('upload', 'editor') . '" />';

?>
          <?php
          } else {
              print "";
          } ?>
          </td>
    </tr>
  </table>
<p>&nbsp;</p>
</body>
</html>
