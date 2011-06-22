<?php
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * Block global configuration settings file.
 *
 * @package    elis
 * @subpackage File system
 * @copyright  2010 Remote Learner - http://www.remote-learner.net/
 * @author     Justin Filip <jfilip@remote-learner.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$settings->add(new admin_setting_configtext('block_course_repository_webdav_client', get_string('webdavclient', 'block_repository'),
                   get_string('configwebdavclient', 'block_repository'), '', PARAM_URL, 75));

$settings->add(new admin_setting_configtext('block_course_repository_help_link', get_string('helplink', 'block_repository'),
                   get_string('confighelplink', 'block_repository'), '', PARAM_URL, 75));

?>