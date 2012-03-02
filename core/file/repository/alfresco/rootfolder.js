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
 * @package    elis
 * @subpackage File system
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2009 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

function chooseRootFolder(obj) {
/// This function will open a popup window to test the server paramters for
/// successful connection.
    if ((obj.s__repository_alfresco_server_host.value.length == 0) ||
    		(obj.s__repository_alfresco_server_host.value == '')) {

        return false;
    }

    var queryString = "";

    queryString += "url=" + escape(obj.id_s__repository_alfresco_server_host.value);
    queryString += "&port=" + obj.id_s__repository_alfresco_server_port.value;
    queryString += "&username=" + escape(obj.id_s__repository_alfresco_server_username.value);
    queryString += "&password=" + escape(obj.id_s__repository_alfresco_server_password.value);
    queryString += "&choose=id_s__repository_alfresco_root_folder";

    return openpopup('/file/repository/alfresco/rootfolder.php?' + queryString, 'rootfolder',
                      'scrollbars=yes,resizable=no,width=640,height=480', 0);
}
