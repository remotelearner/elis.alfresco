<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2011 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @copyright  (C) 2008-2011 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(dirname(__FILE__)) . '/config.php');
require_once($CFG->dirroot . '/repository/lib.php');

//$PAGE->requires->js('/repository/elis_files/js/jquery-1.6.2.min.js', true);
//$PAGE->requires->js('/repository/elis_files/js/jquery-ui-1.8.16.custom.min.js', true);
//$PAGE->requires->js('/repository/elis_files/js/fileuploader.js', true);

/// Wait as long as it takes for this script to finish
set_time_limit(0);

// general parameters
$action      = optional_param('action', '',        PARAM_ALPHA);
$client_id   = optional_param('client_id', '', PARAM_RAW);    // client ID
$itemid      = optional_param('itemid', '',        PARAM_INT);

// parameters for repository
$callback    = optional_param('callback', '',      PARAM_CLEANHTML);
$contextid   = optional_param('ctx_id',    SYSCONTEXTID, PARAM_INT);    // context ID
$courseid    = optional_param('course',    SITEID, PARAM_INT);    // course ID
$env         = optional_param('env', 'filepicker', PARAM_ALPHA);  // opened in file picker, file manager or html editor
$filename    = optional_param('filename', '',      PARAM_FILE);
$fileurl     = optional_param('fileurl', '',       PARAM_RAW);
$thumbnail   = optional_param('thumbnail', '',     PARAM_RAW);
$targetpath  = optional_param('targetpath', '',    PARAM_PATH);
$repo_id     = optional_param('repo_id', 0,        PARAM_INT);    // repository ID
$req_path    = optional_param('p', '',             PARAM_RAW);    // the path in repository
$curr_page   = optional_param('page', '',          PARAM_RAW);    // What page in repository?
$search_text = optional_param('s', '',             PARAM_CLEANHTML);
$maxfiles    = optional_param('maxfiles', -1,      PARAM_INT);    // maxfiles
$maxbytes    = optional_param('maxbytes',  0,      PARAM_INT);    // maxbytes
$subdirs     = optional_param('subdirs',  0,       PARAM_INT);    // maxbytes

// the path to save files
$savepath = optional_param('savepath', '/',    PARAM_PATH);
// path in draft area
$draftpath = optional_param('draftpath', '/',    PARAM_PATH);

$url = new moodle_url('/repository/filemanager.php');

$baseurl = new moodle_url('/repository/filemanager.php');
$baseurl->param('sesskey', sesskey());

if ($contextid !== 0) {
    $url->param('ctx_id', $contextid);
    $baseurl->param('ctx_id', $contextid);
}
if ($courseid != SITEID) {
    $url->param('course', $courseid);
}

$context = get_context_instance_by_id($contextid);

$PAGE->set_url($url);
$PAGE->set_context($context);

if ($context->contextlevel == CONTEXT_COURSE) {
    $pagename = get_string("pluginname",'repository_elis_files');

    if ( !$course = $DB->get_record('course', array('id'=>$context->instanceid))) {
        print_error('invalidcourseid');
    }
    require_login($course, false);
    require_capability('repository/elis_files:view',  $context);
} else {
    print_error('invalidcontext');
}

/// Create navigation links
if (!empty($course)) {
    $PAGE->navbar->add($pagename);
    $fullname = $course->fullname;
} else {
    $fullname = fullname($user);
    $strrepos = get_string('repositories', 'repository');
    $PAGE->navbar->add($fullname, new moodle_url('/user/view.php', array('id'=>$USER->id)));
    $PAGE->navbar->add($strrepos);
}

$title = $pagename;

/// Display page header
$PAGE->set_title($title);
$PAGE->set_heading($fullname);
$PAGE->set_pagelayout('admin');

if ($context->contextlevel == CONTEXT_USER) {
    if ( !$course = $DB->get_record('course', array('id'=>$courseid))) {
        print_error('invalidcourseid');
    }
}

// user context
$user_context = get_context_instance(CONTEXT_USER, $USER->id);
//$PAGE->set_context($user_context);

if (!$course = $DB->get_record('course', array('id'=>$courseid))) {
    print_error('invalidcourseid');
}
$PAGE->set_course($course);

// init repository plugin
$sql = 'SELECT i.name, i.typeid, r.type FROM {repository} r, {repository_instances} i '.
       'WHERE i.id=? AND i.typeid=r.id';

if ($repository = $DB->get_record_sql($sql, array($repo_id))) {
    $type = $repository->type;
    if (file_exists($CFG->dirroot.'/repository/'.$type.'/lib.php')) {
        require_once($CFG->dirroot.'/repository/'.$type.'/lib.php');
        $classname = 'repository_' . $type;
            try {
                $repo = new $classname($repo_id, $contextid, array('ajax'=>false, 'name'=>$repository->name, 'type'=>$type));
            } catch (repository_exception $e){
                print_error('pluginerror', 'repository');
            }
    } else {
        print_error('invalidplugin', 'repository');
    }
}

$moodle_maxbytes = get_max_upload_file_size();
// to prevent maxbytes greater than moodle maxbytes setting
if ($maxbytes == 0 || $maxbytes>=$moodle_maxbytes) {
    $maxbytes = $moodle_maxbytes;
}

$params = array('ctx_id' => $contextid, 'itemid' => $itemid, 'env' => $env, 'course'=>$courseid, 'maxbytes'=>$maxbytes, 'maxfiles'=>$maxfiles, 'subdirs'=>$subdirs, 'sesskey'=>sesskey());
$params['action'] = 'browse';
$params['draftpath'] = $draftpath;
$home_url = new moodle_url('/repository/draftfiles_manager.php', $params);

$params['savepath'] = $savepath;
$params['repo_id'] = $repo_id;
$url = new moodle_url($CFG->httpswwwroot."/repository/filemanager.php", $params);
$PAGE->set_url('/repository/filemanager.php', $params);

echo $OUTPUT->header();

echo '<div id="filepickerarea"></div>';

// Generate json encoded repository list
$repos = repository::get_instances($params);
$new_repos = array();
foreach ($repos as $repo) {
    $new_repos[$repo->id] = array(
            'id' => $repo->id,
            'name' => $repo->name,
            'type' => $repo->options['type'],
            'icon' => $CFG->wwwroot.'/theme/image.php?theme=standard&image=icon&rev=169&component='.get_class($repo),
            'supported_types' => '*',
            'return_types' => $repo->returntypes
        );
}
$json_repos = json_encode($new_repos);
$unique_id = uniqid();
$json_context = json_encode($context);

$js =<<<JS_END
var options = {
    "client_id" : "{$unique_id}",
    "itemid" : "0",
    "action" : "list",
    "context" : {$json_context},
    "repositories" : {$json_repos}
};
M.core_filepicker.init(Y, options);

var filepickerpage = M.core_filepicker.instances[options.client_id];

filepickerpage.render = function() {
    var client_id = this.options.client_id;
    var scope = this;

    scope.isfilemanager = true;

    // Generate filepicker framed areas for buttons, listing and main panel
    var mainuinode = Y.Node.create('<div id="mainui"></div>');
    Y.one('#filepickerarea').appendChild(mainuinode);
    var fpnode = Y.Node.create('<div class="file-picker" id="filepicker-'+client_id+'"></div>');
    Y.one('#mainui').appendChild(fpnode);
    var bdnode = Y.Node.create('<div class="bd" id="filepickerbd-'+client_id+'"></div>');
    Y.one('#filepicker-'+client_id).appendChild(bdnode);
    var layoutnode = Y.Node.create('<div id="layout-'+client_id+'" class="yui-layout"></div>');
    Y.one('#filepickerbd-'+client_id).appendChild(layoutnode);

    // Add button bar in the top of layout
    var viewbarnode = Y.Node.create('<div id="fp-viewbar-'+client_id+'" class="yui-buttongroup fp-viewbar" style="width: 100%; border-bottom: 1px solid;"></div>');
    Y.one('#layout-'+client_id).appendChild(viewbarnode);

    // Add repository list column in the left of the layout
    var listwidth = 200;
    var listnode = Y.Node.create('<div id="fp-list-'+client_id+'" class="fp-list" style="float: left; width: '+listwidth+'px; top: 0px; border-right: 1px solid;"></div>');
    Y.one('#layout-'+client_id).appendChild(listnode);

    // Add files listing area in the right of the layout
    var panelwidth = $('#layout-'+client_id).width() - listwidth - 50;
    var panelnode = Y.Node.create('<div id="panel-'+client_id+'" class="fp-panel" style="float: left; width: '+panelwidth+'px;top: 0px;"></div>');
    Y.one('#layout-'+client_id).appendChild(panelnode);

    this.rendered = true;

    // Add buttons to viewbar
    var buttonnode = Y.Node.create('<div id="fp-viewbar-'+client_id+'"></div>');
    Y.one(document.body).appendChild(buttonnode);
    var view_icons = {label: M.str.repository.iconview, value: 't', checked: true,
        onclick: {
            fn: function(){
                scope.view_as_icons();
            }
        }
    };
    var view_listing = {label: M.str.repository.listview, value: 'l',
        onclick: {
            fn: function(){
                scope.view_as_list();
            }
        }
    };
    var view_details = {label: M.str.repository_elis_files.detailview, value: 'd',
        onclick: {
            fn: function(){
                scope.view_as_details();
            }
        }
    };
    this.viewbar = new YAHOO.widget.ButtonGroup({
        id: 'btngroup-'+client_id,
        name: 'buttons',
        disabled: true,
        container: 'fp-viewbar-'+client_id
    });
    this.viewbar.addButtons([view_icons, view_listing, view_details]);

    // Add repository list and main panel
    var r = this.options.repositories;
    Y.on('contentready', function(el) {
        var list = Y.one(el);
        var count = 0;
        var default_id = 0;
        for (var i in r) {
            var id = 'repository-'+client_id+'-'+r[i].id;
            var link_id = id + '-link';
            list.append('<li id="'+id+'"><a class="fp-repo-name" id="'+link_id+'" href="###">'+r[i].name+'</a></li>');
            Y.one('#'+link_id).prepend('<img src="'+r[i].icon+'" width="16" height="16" />&nbsp;');
            Y.one('#'+link_id).on('click', function(e, scope, repository_id) {
                YAHOO.util.Cookie.set('recentrepository', repository_id);
                scope.repository_id = repository_id;
                this.list({'repo_id':repository_id});
            }, this, this, r[i].id);
            count++;
            var elisfiles = /elis_files/;
            var matchpos = r[i].type.search(elisfiles);
            if(matchpos != -1) {
                default_id = r[i].id;
            }
        }
        if (count==0) {
            if (this.options.externallink) {
                list.set('innerHTML', M.str.repository.norepositoriesexternalavailable);
            } else {
                list.set('innerHTML', M.str.repository.norepositoriesavailable);
            }
        }

        // Set the initial page default to ELIS Files and detail list view
        if (default_id > 0) {
            YAHOO.util.Cookie.set('recentrepository', default_id);
            this.repository_id = default_id;
            this.viewmode = 1;
            this.list({'repo_id':default_id});
        }
    }, '#fp-list-'+client_id, this, '#fp-list-'+client_id);

}

filepickerpage.render();
JS_END;

$PAGE->requires->js_init_code($js, true,
                              array('name'=>'filepickerpage',
                                    'requires'=>array('core_filepicker'),
                                    'fullpath'=>'/repository/filepicker.js')
                             );

echo $OUTPUT->footer();
