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
 * @copyright  (C) 2008-2010 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/lib/adminlib.php';
require_once $CFG->dirroot . '/file/repository/repository.class.php';
require_once $CFG->libdir . '/alfresco30/lib.php';


class admin_setting_alfresco_root_folder extends admin_setting_configdirectory {
    function write_setting($data) {
        global $CFG;

    /// TODO: Need code here that will move the current Moodle directory structure to the new location.
        if (empty($data)) {
            $data = $this->get_defaultsetting();
        }

    /// Validate the path, if we can.
        if ($repo = repository_factory::factory('alfresco')) {
            if ($repo->is_configured() && $repo->verify_setup()) {
                if (alfresco_validate_path($data)) {
                    $newuuid = alfresco_uuid_from_path($data);

                    if (($newuuid != $repo->muuid) && !alfresco_root_move($repo->muuid, $newuuid)) {
                        return get_string('couldnotmoveroot', 'repository_alfresco');
                    } else {
                        return parent::write_setting($data);
                    }
                } else {
                    return get_string('invalidpath', 'repository_alfresco');
                }
            }
        }

    /// If the repository is not configured correctly, we just have to assume the
    /// path is valid as we can't connect to verify.
        return parent::write_setting($data);
    }

    function output_html($data, $query = '') {
        global $CFG;

        require_js($CFG->wwwroot . '/file/repository/alfresco/rootfolder.js');

        $default = $this->get_defaultsetting();

        $repoisup = false;

    /// Validate the path, if we can.
        if ($repo = repository_factory::factory('alfresco')) {
            $repoisup = $repo->is_configured() && $repo->verify_setup();

            if ($repoisup) {
                if (alfresco_validate_path($data)) {
                    $valid = '<span class="pathok">&#x2714;</span>';
                } else {
                    $valid = '<span class="patherror">&#x2718;</span>';
                }
            }
        }

        if (!isset($valid)) {
            $valid = '';
        }

        $inputs = '<div class="form-file defaultsnext"><input type="text" size="48" id="' . $this->get_id() .
                  '" name="' . $this->get_full_name() . '" value="' . s($data) . '" /> <input type="button" ' .
                  'onclick="return chooseRootFolder(document.getElementById(\'adminsettings\'));" value="' .
                  get_string('chooserootfolder', 'repository_alfresco') . '" name="' . $this->get_full_name() .
                  '"' . (!$repoisup ? ' disabled="disabled"' : '') .' />' . $valid . '</div>';

        return format_admin_setting($this, $this->visiblename, $inputs, $this->description,
                                    true, '', $default, $query);
    }
}


class admin_setting_alfresco_category_select extends admin_setting {
    function admin_setting_alfresco_category_select($name, $heading, $information) {
        parent::admin_setting($name, $heading, $information, '');
    }

    function get_setting() {
        return false;
    }

    function write_setting() {
        return false;
    }

    function output_html($data, $query='') {
        $default = $this->get_defaultsetting();

        $button = button_to_popup_window('/file/repository/alfresco/config-categories.php',
                                         'config-categories', get_string('configurecategoryfilter', 'repository_alfresco'),
                                         480, 640, '', '', true);

        return format_admin_setting($this, $this->visiblename, $button, $this->description, true, '', NULL, $query);
    }

}

?>