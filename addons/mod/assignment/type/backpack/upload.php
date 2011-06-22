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
    require_once($CFG->dirroot . '/file/repository/repository.class.php');


/// Remote Learner Edit -- BEGIN

    $id     = optional_param('id', SITEID, PARAM_INT);
    $shared = optional_param('shared', '', PARAM_ALPHA);
    $userid = optional_param('userid', 0, PARAM_INT);
    $uuid   = optional_param('uuid', '', PARAM_TEXT);
    $alf    = optional_param('alf', 0, PARAM_INT);

/// Remote Learner Edit -- END

    require_login($id);
//    require_capability('block/repository:viewowncontent', get_context_instance(CONTEXT_COURSE, $id));

    require_course_login($id);
    @header('Content-Type: text/html; charset=utf-8');

    $upload_max_filesize = get_max_upload_file_size($CFG->maxbytes);

    if (!empty($httpsrequired) or (!empty($_SERVER['HTTPS']) and $_SERVER['HTTPS'] != 'off')) {
        $url = preg_replace('|https?://[^/]+|', '', $CFG->wwwroot).'/lib/editor/htmlarea/';
    } else {
        $url = $CFG->wwwroot.'/lib/editor/htmlarea/';
    }

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
  <title><?php print_string("insertimage","editor");?></title>

<script type="text/javascript" src="popup.js"></script>

<script type="text/javascript">
//<![CDATA[
var preview_window = null;

function Init() {
  __dlg_init();
  var param = window.dialogArguments;
  if (param) {
      document.getElementById("f_url").value = param["f_url"];
  }
  document.getElementById("f_url").focus();
};

function onOK() {
  var required = {
    "f_url": "<?php print_string("mustenterurl", "editor");?>"
  };

  for (var i in required) {
    var el = document.getElementById(i);
    if (!el.value) {
      alert(required[i]);
      el.focus();
      return false;
    }
  }
  // pass data back to the calling window
  var fields = ["f_url"];
  var param = new Object();
  for (var i in fields) {
    var id = fields[i];
    var el = document.getElementById(id);
    param[id] = el.value;
  }
  if (preview_window) {
    preview_window.close();
  }
  __dlg_close(param);
  return false;
};

function onCancel() {
  if (preview_window) {
    preview_window.close();
  }
  __dlg_close(null);
  return false;
};

//function onPreview() {
//  var f_url = document.getElementById("f_url");
//  var url = f_url.value;
//  if (!url) {
//    alert("<?php// print_string("enterurlfirst","editor");?>");
//    f_url.focus();
//    return false;
//  }
//  var img = new Image();
//  img.src = url;
//  var win = null;
//  if (!document.all) {
//    win = window.open("<?php// echo $url ?>blank.html", "ha_imgpreview", "toolbar=no,menubar=no,personalbar=no,innerWidth=100,innerHeight=100,scrollbars=no,resizable=yes");
//  } else {
//    win = window.open("<?php// echo $url ?>blank.html", "ha_imgpreview", "channelmode=no,directories=no,height=100,width=100,location=no,menubar=no,resizable=yes,scrollbars=no,toolbar=no");
//  }
//  preview_window = win;
//  var doc = win.document;
//  var body = doc.body;
//  if (body) {
//    body.innerHTML = "";
//    body.style.padding = "0px";
//    body.style.margin = "0px";
//    var el = doc.createElement("img");
//    el.src = url;
//
//    var table = doc.createElement("table");
//    body.appendChild(table);
//    table.style.width = "100%";
//    table.style.height = "100%";
//    var tbody = doc.createElement("tbody");
//    table.appendChild(tbody);
//    var tr = doc.createElement("tr");
//    tbody.appendChild(tr);
//    var td = doc.createElement("td");
//    tr.appendChild(td);
//    td.style.textAlign = "center";
//
//    td.appendChild(el);
//    win.resizeTo(el.offsetWidth + 30, el.offsetHeight + 30);
//  }
//  win.focus();
//  return false;
//};

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
        window.ibrowser.document.dirform.action.value = "delete";
    }
    if(dothis == "move") {
        window.ibrowser.document.dirform.action.value = "move";
    }
    if(dothis == "zip") {
        window.ibrowser.document.dirform.action.value = "zip";
    }

    window.ibrowser.document.dirform.submit();
    return false;
}

//]]>
</script>

<style type="text/css">
html, body {
margin: 2px;
background-color: rgb(212,208,200);
font-family: Tahoma, Verdana, sans-serif;
font-size: 11px;
}
.title {
background-color: #ddddff;
padding: 5px;
border-bottom: 1px solid black;
font-family: Tahoma, sans-serif;
font-weight: bold;
font-size: 14px;
color: black;
}
td, input, select, button {
font-family: Tahoma, Verdana, sans-serif;
font-size: 11px;
}
button { width: 70px; }
.space { padding: 2px; }
form { margin-bottom: 0px; margin-top: 0px; }
</style>
</head>
<body onload="Init()">
  <div class="title"><?php print_string("submitfile","editor");?></div>
  <div class="space"></div>
  <div class="space"></div>
  <div class="space"></div>
  <form action="" method="get" id="first">
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
        <td width="15%" align="right"><?php print_string("fileurl","editor");?>:</td>
        <td width="60%"><input name="f_url" type="text" id="f_url" style="width: 100%;" /></td>
          <td width="23%" align="center">
            <button name="btnOK" type="button" id="btnOK" onclick="return onOK();"><?php print_string("ok","editor") ?></button>
            <button name="btnCancel" type="button" id="btnCancel" onclick="return onCancel();"><?php print_string("cancel","editor") ?></button>
          </td>
        </tr>
    </table>
  </form>
  <table width="100%" border="0" cellspacing="0" cellpadding="0">
<?php

/// Remote Learner Edit -- BEGIN

/// Build an array of options for a navigation drop-down menu.

/// Make sure the plug-in is enabled and setup correctly.
    if (isset($CFG->repository_plugins_enabled) &&
        strstr($CFG->repository_plugins_enabled, 'alfresco')) {

        if (!$repo = repository_factory::factory('alfresco')) {
            debugging('Could not create repository object.', DEBUG_DEVELOPER);
        }

        if ($repo->verify_setup() && $repo->is_configured()) {
            if ($id === SITEID) {
                $context = get_context_instance(CONTEXT_SYSTEM, SITEID);
            } else {
                $context = get_context_instance(CONTEXT_COURSE, $id);
            }

            if (has_capability('block/repository:viewowncontent', $context)) {
                $uurl       = 'mod/assignment/type/backpack/upload.php?id=' . $id .
                              '&amp;userid=' . $USER->id . '&amp;alf=1';
                $ops[$uurl] = get_string('repositoryuserfiles', 'repository');

                echo '<tr><td width="450" align="right">Browse files from: ';

                $selected = $uurl;
                popup_form($CFG->wwwroot . '/', $ops, 'filepluginselect', $selected, '');

                echo '</td></tr>';
            }
        }
    }

/// Remote Learner Edit -- END


?>
    <tr>
      <td width="55%" valign="top"><?php
        print_string("filebrowser","editor");
        echo "<br />";

/// Remote Learner Edit -- BEGIN

        echo "<iframe id=\"ibrowser\" name=\"ibrowser\" src=\"{$CFG->wwwroot}/file/repository/index.php?usecheckboxes=1&id=$id&amp;userid=$userid\" style=\"width: 100%; height: 200px;\"></iframe>";

/// Remote Learner Edit -- END

      ?>
      </td>
    </tr>
  </table>
  <table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td width="55%"><div class="space"></div>
        <?php if(has_capability('moodle/course:managefiles', get_context_instance(CONTEXT_COURSE, $id))) { ?>
        <table border="0" cellpadding="2" cellspacing="0">
          <tr><td><?php print_string("selection","editor");?>: </td>
          <td><form id="idelete">
          <input name="btnDelete" type="submit" id="btnDelete" value="<?php print_string("delete","editor");?>" onclick="return submit_form('delete');" /></form></td>
<?php if (empty($alf)) { ?>
          <td><form id="imove">
          <input name="btnMove" type="submit" id="btnMove" value="<?php print_string("move","editor");?>" onclick="return submit_form('move');" /></td>
<?php } ?>
          <td><form id="izip">
          <input name="btnZip" type="submit" id="btnZip" value="<?php print_string("zip","editor");?>" onclick="return submit_form('zip');" /></form></td>
<?php if (empty($alf)) { ?>
          <td><form method="post" action="../coursefiles.php" target="ibrowser">
          <input type="hidden" name="id" value="<?php print($id);?>" />
          <input type="hidden" name="wdir" value="" />
          <input type="hidden" id="irename" name="file" value="" />
          <input type="hidden" name="action" value="rename" />
          <input type="hidden" name="sesskey" value="<?php p($USER->sesskey) ?>" />
          <input name="btnRename" type="submit" id="btnRename" value="<?php print_string("rename","editor");?>" /></form></td>
<?php } ?>
          <tr></table>
          <br />
          <?php
          } else {
              print "";
          } ?>
        </td>
      <td width="45%" rowspan="2" valign="top"><fieldset>
          <legend><?php print_string("properties","editor");?></legend>
          <div class="space"></div>
          <div class="space"></div>
          &nbsp;&nbsp;<?php print_string("size","editor");?>:
          <input type="text" id="isize" name="isize" size="10" style="background: transparent; border: none;" />
      <?php print_string("type","editor");?>: <input type="text" id="itype" name="itype" size="10" style="background: transparent; border: none;" />
      <div class="space"></div>
      <div class="space"></div>
      </fieldset></td>
    </tr>
    <tr>
      <td height="22">
        <form id="cfolder" action="<?php echo $CFG->wwwroot . '/file/repository/alfresco/'; ?>coursefiles.php" method="post" target="ibrowser">
          <input type="hidden" name="userid" value="<?php print($userid); ?>" />
          <input type="hidden" name="id" value="<?php print($id);?>" />
          <input type="hidden" name="wdir" value="" />
          <input type="hidden" name="action" value="mkdir" />
          <input type="hidden" name="sesskey" value="<?php p($USER->sesskey) ?>" />
          <input name="name" type="text" id="foldername" size="35" />
          <input name="btnCfolder" type="submit" id="btnCfolder" value="<?php print_string("createfolder","editor");?>" onclick="return checkvalue('foldername','cfolder');" />
        </form>
        <div class="space"></div>
        <form action="<?php echo $CFG->wwwroot . '/file/repository/alfresco/'; ?>coursefiles.php?id=<?php print($id);?>" method="post" enctype="multipart/form-data" target="ibrowser" id="uploader">
          <input type="hidden" name="userid" value="<?php print($userid); ?>" />
          <input type="hidden" name="MAX_FILE_SIZE" value="<?php print($upload_max_filesize);?>" />
          <input type="hidden" name="id" VALUE="<?php print($id);?>" />
          <input type="hidden" name="wdir" value="" />
          <input type="hidden" name="action" value="upload" />
          <input type="hidden" name="sesskey" value="<?php p($USER->sesskey) ?>" />
          <input type="file" name="userfile" id="userfile" size="35" />
          <input name="save" type="submit" id="save" onclick="return checkvalue('userfile','uploader');" value="<?php print_string("upload","editor");?>" />
        </form>
      </td>
    </tr>
  </table>
  <p>&nbsp;</p>
</body>
</html>
