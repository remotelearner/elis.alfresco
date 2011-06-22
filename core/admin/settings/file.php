<?php
/**
 * Contains settings links for the File / Repository / Portfolio system.
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

if ($hassiteconfig) { // speedup for non-admins, add all caps used on this page
    $ADMIN->add('root', new admin_category('file', get_string('filesystem', 'file')));
    $ADMIN->add('file', new admin_category('filerepositories', get_string('repositories', 'file')));

    $ADMIN->add('filerepositories', new admin_externalpage('repositories', get_string('managerepositories', 'file'),
                                                           "$CFG->wwwroot/$CFG->admin/file/repositories.php",
                                                           'moodle/site:config'));

    if ($modules = get_list_of_plugins('repository', '', $CFG->dirroot . '/file')) {
        foreach ($modules as $module) {
        /// Skip if directory is empty.
            if (!file_exists($CFG->dirroot . '/file/repository/' . $module . '/repository.php')) {
                continue;
            }

            if (file_exists($CFG->dirroot . '/file/repository/' . $module . '/settings.php')) {
                $settings = new admin_settingpage('repository' . $module, get_string('repository', 'repository_' . $module),
                                                 'moodle/site:config');

                if ($ADMIN->fulltree) {
                    include($CFG->dirroot . '/file/repository/' . $module . '/settings.php');
                }

                $ADMIN->add('filerepositories', $settings);
            } else if (file_exists($CFG->dirroot . '/file/repository/' . $module . '/config.html')) {
                //
            }
        }
    }

}

?>
