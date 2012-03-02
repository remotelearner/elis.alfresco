<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2012 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @subpackage block_repository (Alfresco)
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once("{$CFG->dirroot}/blocks/repository/lib.php");
require_once("{$CFG->dirroot}/file/repository/alfresco/repository.php");

function xmldb_block_repository_upgrade($oldversion = 0) {
    $result = true;

    if ($oldversion < 2010090901) {
        $errors = false;
        $auths = block_repository_nopasswd_auths();
        $authlist = "'". implode("', '", $auths) ."'";
        $users = get_records_select('user', "auth IN ({$authlist})", '', 'id, auth');
        if (!empty($users)) {
            foreach ($users as $user) {
                $user = get_complete_user_data('id', $user->id);
                $migrate_ok = block_repository_user_created($user);
                if (!$migrate_ok) {
                    $errors = true;
                    error_log("xmldb_block_repository_upgrade({$oldversion}) - failed migrating user ({$user->id}) to Alfresco.");
                }
            }
        }
        if (!$errors) {
            set_config('initialized', 1, repository_plugin_alfresco::$plugin_name);
        }
    }

    return $result;
}

?>
