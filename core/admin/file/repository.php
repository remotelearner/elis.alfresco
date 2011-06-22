<?php
/**
 * Allows admin to edit all repository variables
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

    require_once '../../config.php';
    require_once $CFG->libdir . '/adminlib.php';


    admin_externalpage_setup('repositories');

    $repository = required_param('repository', PARAM_ALPHA);
    $CFG->pagepath = 'repository/' . $repository;

    require_login();

    if (!$site = get_site()) {
        redirect("index.php");
    }

    if (!isadmin()) {
        error("Only the admin can use this page");
    }

    require_once("$CFG->dirroot/file/repository/repository.class.php");   /// Open the factory class

    $repositoryobj = repository_factory::factory($repository);

/// If data submitted, then process and store.

    if ($frm = data_submitted()) {
        if (!confirm_sesskey()) {
            error(get_string('confirmsesskeybad', 'error'));
        }

        if ($repositoryobj->process_config($frm)) {
            redirect("repository.php?repository=$repository", get_string("changessaved"), 1);
        }
    } else {
        $frm = $CFG;
    }

/// Otherwise fill and print the form.
    $modules = get_list_of_plugins('repository', '', $CFG->dirroot . '/file');
    foreach ($modules as $module) {
        $options[$module] = get_string("repositoryname", "repository_$module");
    }
    asort($options);

    admin_externalpage_print_header();

    echo "<form target=\"{$CFG->framename}\" id=\"repositorysettings\" name=\"repositorysettings\" method=\"post\" action=\"repository.php\">";
    echo "<input type=\"hidden\" name=\"repository\" value=\"$repository\">";
    echo "<input type=\"hidden\" name=\"sesskey\" value=\"{$USER->sesskey}\">";

    echo "<div align=\"center\"><p><b>";


/// Choose an repository method
    echo get_string('chooserepositorysystem', 'repository') . ': ';
    choose_from_menu ($options, "repository", $repository, "",
                      "document.location='repository_config.php?sesskey=$USER->sesskey&repository='+document.repositorymenu.repository.options[document.repositorymenu.repository.selectedIndex].value", "");

    echo "</b></p></div>";

/// Print current repository type description
    print_simple_box_start("center", "80%");
    print_heading($options[$repository]);

    print_simple_box_start("center", "60%", '', 5, 'informationbox');
    print_string("description", "repository_$repository");
    print_simple_box_end();

    echo "<hr />";

    $repositoryobj->config_form($frm);

    echo "<center><p><input type=\"submit\" value=\"".get_string("savechanges")."\"></p></center>\n";
    echo "</form>";

    print_simple_box_end();

    admin_externalpage_print_footer();

?>
