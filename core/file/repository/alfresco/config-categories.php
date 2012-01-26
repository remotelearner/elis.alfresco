<?php
/**
 * Configure the categories used when searching within the repository.
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
    require_once $CFG->libdir . '/HTML_TreeMenu-1.2.0/TreeMenu.php';
    require_once $CFG->dirroot . '/file/repository/repository.class.php';
    require_once $CFG->dirroot . '/file/repository/alfresco/lib.php';


    if (!$site = get_site()) {
        redirect($CFG->wwwroot . '/');
    }

    require_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM, SITEID));


    $strconfigcatfilter = get_string('configurecategoryfilter', 'repository_alfresco');

    print_header();

/// Initialize the repo object.
    $repo = repository_factory::factory('alfresco');

/// Process any form data submission
    if (($data = data_submitted($CFG->wwwroot . '/file/repository/alfresco/config-catlist.php')) &&
        confirm_sesskey()) {

        if (isset($data->reset)) {
            set_config('repository_alfresco_cron', 0);
            delete_records('alfresco_categories');
            $repo->cron();

        } else if (isset($data->categories)) {
            set_config('repository_alfresco_catfilter', serialize($data->categories));

        } else {
            set_config('repository_alfresco_catfilter', '');
        }


    }

/// Get (or create) the array of category IDs that are already selected in the filter.
    $catfilter = repository_alfresco_get_category_filter();

    print_simple_box_start('center', '75%');

    echo '<form method="post" action="' . $CFG->wwwroot . '/file//repository/alfresco/config-categories.php">';
    echo '<input type="hidden" name="sesskey" value="' . $USER->sesskey . '" />';

    echo '<center>';
    echo '<input type="submit" name="reset" value="' . get_string('resetcategories', 'repository_alfresco') .
         '" /><br />' . get_string('resetcategoriesdesc', 'repository_alfresco') . '<br /><br />';

    if ($categories = $repo->category_get_children(0)) {

        echo '<input type="button" value="' . get_string('selectall') . '" onclick="checkall();" />';
        echo '&nbsp;<input type="button" value="' . get_string('deselectall') . '" onclick="checknone();" /><br />';
        echo '<input type="submit" value="' . get_string('savechanges') . '" />';
        echo '</center><br />';

        if ($nodes = repository_alfresco_make_category_select_tree_choose($categories, $catfilter)) {
            $menu  = new HTML_TreeMenu();

            for ($i = 0; $i < count($nodes); $i++) {
                $menu->addItem($nodes[$i]);
            }

            $treemenu = &new HTML_TreeMenu_DHTML($menu, array(
                'images' => $CFG->wwwroot . '/lib/HTML_TreeMenu-1.2.0/images'
            ));

            echo '<script language="JavaScript" type="text/javascript">';
            echo "<!--\n";
            include($CFG->libdir . '/HTML_TreeMenu-1.2.0/TreeMenu.js');
            echo "\n// -->";
            echo '</script>';

            $treemenu->printMenu();
        }

        echo '<center><br />';
        echo '<input type="button" value="' . get_string('selectall') . '" onclick="checkall();" />';
        echo '&nbsp;<input type="button" value="' . get_string('deselectall') . '" onclick="checknone();" /><br />';
        echo '<input type="submit" value="' . get_string('savechanges') . '" /> ' . close_window_button('closewindow', true);
    } else {
        print_heading(get_string('nocategoriesfound', 'repository_alfresco'));
    }

        echo '</center>';

        echo '</form>';

    print_simple_box_end();

    print_footer('empty');

?>
