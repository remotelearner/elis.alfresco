<?php
/**
 * Allows admin to edit all repository variables
 *
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
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


require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');


admin_externalpage_setup('repositories');

if (!isset($CFG->repository)) {
    $CFG->repository = '';
}
if (!isset($CFG->repository_plugins_enabled)) {
    $CFG->repository_plugins_enabled = '';
}

$repository    = optional_param('repository', $CFG->repository, PARAM_SAFEDIR);
$CFG->pagepath = 'repository';

require_login();

require_once("$CFG->dirroot/file/repository/repository.class.php");   /// Open the factory class

// Save settings
if ($frm = data_submitted()) {
    if (empty($frm->enable)) {
        $frm->enable = array();
    }
    if (empty($frm->default)) {
        $frm->default = '';
    }

    if (!in_array($frm->default, $frm->enable)) {
        $frm->default = '';
    }

    asort($frm->enable);
    set_config('repository_plugins_enabled', implode(',', $frm->enable));
    set_config('repository', $frm->default);
    redirect("repositories.php", get_string("changessaved"), 1);
}


// Print the form
admin_externalpage_print_header();

$modules = get_list_of_plugins('repository', '', $CFG->dirroot . '/file');
$options = array();
foreach ($modules as $module) {
    $options[$module] = get_string("enrolname", "enrol_$module");
}
asort($options);

print_simple_box(get_string('configrepositoryplugins', 'file'), 'center', '700');

echo '<form target="' . $CFG->framename . '" name="repositorymenu" method="post" action="repositories.php">';

$table        = new stdClass();
$table->head  = array(get_string('name'), get_string('enable'), get_string('default'), get_string('settings'));
$table->align = array('left', 'center', 'center', 'center');
$table->size  = array('60%', '', '', '15%');
$table->width = '700';
$table->data  = array();

$modules = get_list_of_plugins('repository', '', $CFG->dirroot . '/file');

foreach ($modules as $module) {
    // skip if directory is empty
    if (!file_exists("$CFG->dirroot/file/repository/$module/repository.php")) {
        continue;
    }

    $name = get_string('repository', "repository_$module");
    $plugin = repository_factory::factory($module);

    $enable = '<input type="checkbox" name="enable[]" value="'.$module.'"';
    if (stristr($CFG->repository_plugins_enabled, $module) !== false) {
        $enable .= ' checked="checked"';
    }
    if ($module == 'manual') {
        $enable .= ' disabled="disabled"';
    }
    $enable .= ' />';

    $default = '<input type="radio" name="default" value="'.$module.'"';
    if ($CFG->repository == $module) {
        $default .= ' checked="checked"';
    }
    $default .= ' />';

    if (file_exists($CFG->dirroot . '/file/repository/' . $module . '/settings.php')) {
        $settings = $CFG->wwwroot . '/admin/settings.php?section=repository' . $module;
    } else if (file_exists($CFG->dirroot . '/file/repository/' . $module . '/config.html')) {
         $settings = $CFG->wwwroot . '/admin/file/repository.php?repository=' . $module;
    } else {
         $settings = "";
    }

    $table->data[$name] = array(
        $name,
        $enable,
        $default,
        '<a href="' . $settings . '">' . get_string('edit') . '</a>'
    );
}

asort($table->data);
print_table($table);

echo '<center><input type="submit" name="savechanges" value="' . get_string('savechanges') . '"></center>';
echo '</form>';

admin_externalpage_print_footer();

?>