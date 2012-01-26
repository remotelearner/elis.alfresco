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


require_once dirname(__FILE__) . '/admin_settings_alfresco.php';


$settings->add(new admin_setting_configtext('repository_alfresco_server_host', get_string('serverurl', 'repository_alfresco'),
                   get_string('repository_alfresco_server_host', 'repository_alfresco'), 'http://localhost', PARAM_URL, 30));


$settings->add(new admin_setting_configtext('repository_alfresco_server_port', get_string('serverport', 'repository_alfresco'),
                   get_string('repository_alfresco_server_port', 'repository_alfresco'), '8080', PARAM_INT, 30));


$settings->add(new admin_setting_configtext('repository_alfresco_server_username', get_string('serverusername', 'repository_alfresco'),
                   get_string('repository_alfresco_server_username', 'repository_alfresco'), '', PARAM_NOTAGS, 30));


$settings->add(new admin_setting_configpasswordunmask('repository_alfresco_server_password', get_string('serverpassword', 'repository_alfresco'),
                   get_string('repository_alfresco_server_password', 'repository_alfresco'), ''));


$settings->add(new admin_setting_alfresco_category_select('repository_alfresco_category_filter', get_string('categoryfilter', 'repository_alfresco'),
                    get_string('repository_alfresco_category_filter', 'repository_alfresco')));


$settings->add(new admin_setting_alfresco_root_folder('repository_alfresco_root_folder', get_string('rootfolder', 'repository_alfresco'),
                    get_string('repository_alfresco_root_folder', 'repository_alfresco'), '/moodle'));


// Display time period options to control browser caching
$cacheoptions = array(
    7  * DAYSECS  => get_string('numdays', '', 7),
    1  * DAYSECS  => get_string('numdays', '', 1),
    12 * HOURSECS => get_string('numhours', '', 12),
    3  * HOURSECS => get_string('numhours', '', 3),
    2  * HOURSECS => get_string('numhours', '', 2),
    1  * HOURSECS => get_string('numhours', '', 1),
    45 * MINSECS  => get_string('numminutes', '', 45),
    30 * MINSECS  => get_string('numminutes', '', 30),
    15 * MINSECS  => get_string('numminutes', '', 15),
    10 * MINSECS  => get_string('numminutes', '', 10),
    0 => get_string('no')
);

$settings->add(new admin_setting_configselect('repository_alfresco_cachetime', get_string('cachetime', 'repository_alfresco'),
                    get_string('configcachetime', 'repository_alfresco'), 0, $cacheoptions));


// Generate the list of options for choosing a quota limit size.
$bytes_1mb = 1048576;

$sizelist = array(
    -1,
    0,
    $bytes_1mb * 10,
    $bytes_1mb * 20,
    $bytes_1mb * 30,
    $bytes_1mb * 40,
    $bytes_1mb * 50,
    $bytes_1mb * 100,
    $bytes_1mb * 200,
    $bytes_1mb * 500
);

foreach ($sizelist as $sizebytes) {
    if ($sizebytes == 0) {
        $filesize[$sizebytes] = get_string('quotanotset', 'repository_alfresco');;
    } else if ($sizebytes == -1 ) {
        $filesize[$sizebytes] = get_string('quotaunlimited', 'repository_alfresco');
    } else {
        $filesize[$sizebytes] = display_size($sizebytes);
    }
}

krsort($filesize, SORT_NUMERIC);

$settings->add(new admin_setting_configselect('repository_alfresco_user_quota', get_string('userquota', 'repository_alfresco'),
                    get_string('configuserquota', 'repository_alfresco'), 0, $filesize));


// Add a toggle to control whether we will delete a user's home directory in Alfresco when their account is deleted.
$options = array(1 => get_string('yes'), 0 => get_string('no'));

$settings->add(new admin_setting_configselect('repository_alfresco_deleteuserdir', get_string('deleteuserdir', 'repository_alfresco'),
                    get_string('configdeleteuserdir', 'repository_alfresco'), 0, $options));


// Menu setting about choosing the default location where users will end up if they don't have a previous file
// browsing location saved.
$options = array(
    ALFRESCO_BROWSE_MOODLE_FILES          => get_string('moodlefiles', 'repository'),
    ALFRESCO_BROWSE_ALFRESCO_SITE_FILES   => get_string('repositorysitefiles', 'repository'),
    ALFRESCO_BROWSE_ALFRESCO_SHARED_FILES => get_string('repositorysharedfiles', 'repository'),
    ALFRESCO_BROWSE_ALFRESCO_COURSE_FILES => get_string('repositorycoursefiles', 'repository'),
    ALFRESCO_BROWSE_ALFRESCO_USER_FILES   => get_string('repositoryuserfiles', 'repository')
);

$settings->add(new admin_setting_configselect('repository_alfresco_default_browse',
                    get_string('defaultfilebrowsinglocation', 'repository_alfresco'),
                    get_string('configdefaultfilebrowsinglocation', 'repository_alfresco'),
                    ALFRESCO_BROWSE_MOODLE_FILES, $options));

// Display menu option about overriding the Moodle 'admin' account when creating an Alfresco user account.

// Check for the existence of a user that will conflict with the default Alfresco administrator account.
$hasadmin = record_exists('user', 'username', 'admin', 'mnethostid', $CFG->mnet_localhost_id);

if (empty($CFG->repository_alfresco_admin_username)) {
    $adminusername = 'moodleadmin';
    set_config('repository_alfresco_admin_username', $adminusername);
} else {
    $adminusername = $CFG->repository_alfresco_admin_username;
}

// Only proceed here if the Alfresco plug-in is actually enabled.
if (isset($CFG->repository_plugins_enabled) && strstr($CFG->repository_plugins_enabled, 'alfresco')) {
    require_once($CFG->dirroot . '/file/repository/repository.class.php');

    if ($repo = repository_factory::factory('alfresco')) {
        if (alfresco_get_home_directory($adminusername) == false) {
            // If the specified username does not exist in Alfresco yet, allow the value to be changed here.
            $settings->add(new admin_setting_configtext('repository_alfresco_admin_username', get_string('adminusername', 'repository_alfresco'),
                                get_string('configadminusername', 'repository_alfresco'), 'moodleadmin'));
        } else {
            // An Alfresco account with the specified username has been created, check if a Moodle account exists with that
            // username and display a warning if that is the case.
            if (($userid = get_field('user', 'id', 'username', $adminusername, 'mnethostid', $CFG->mnet_localhost_id)) !== false) {
                $a = new stdClass;
                $a->username = $adminusername;
                $a->url      = $CFG->wwwroot . '/user/editadvanced.php?id=' . $userid . '&amp;course=' . SITEID;

                $settings->add(new admin_setting_heading('repository_alfresco_admin_username', get_string('adminusername', 'repository_alfresco'),
                                    get_string('configadminusernameconflict', 'repository_alfresco', $a)));
            } else {
                $settings->add(new admin_setting_heading('repository_alfresco_admin_username', get_string('adminusername', 'repository_alfresco'),
                                    get_string('configadminusernameset', 'repository_alfresco', $adminusername)));
            }
        }
    }
}

?>