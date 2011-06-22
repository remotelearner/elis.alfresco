<?php
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
 * @copyright  (C) 2008-2010 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

function xmldb_repository_alfresco_upgrade($oldversion = 0) {

    global $CFG, $THEME, $db;

    $result = true;

    if ($result && ($oldversion < 2007011900)) {
        $result = install_from_xmldb_file($CFG->dirroot . '/repository/alfresco/db/install.xml');
    }

    if ($result && ($oldversion < 2010030901)) {
        $table = new XMLDBTable('alfresco_course_store');
        $table->comment = 'Stores course storage UUID values';

        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', false, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('courseid', XMLDB_TYPE_INTEGER, '10', false, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('uuid', XMLDB_TYPE_CHAR, '36', null, false, null, null, null, null);

        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->addKeyInfo('courseid', XMLDB_KEY_FOREIGN, array('courseid'), 'course', array('id'));
        $table->addIndexInfo('courseid-uuid', XMLDB_INDEX_UNIQUE, array('courseid', 'uuid'));

        $result = $result && create_table($table);

        // Only proceed here if the Alfresco plug-in is actually enabled.
        if (isset($CFG->repository_plugins_enabled) && strstr($CFG->repository_plugins_enabled, 'alfresco')) {
            // Handle upgrading some things on the Alfresco repository.
            require_once($CFG->dirroot . '/file/repository/repository.class.php');

            if (!$repo = repository_factory::factory('alfresco')) {
                debugging(get_string('couldnotcreaterepositoryobject', 'repository'), DEBUG_DEVELOPER);
                $result = false;
            }

            // Turn off "Inherit parent space permissions" for the special Moodle storage directories.
            $result = $result && $repo->node_inherit($repo->muuid, false);
            $result = $result && $repo->node_inherit($repo->suuid, false);
            $result = $result && $repo->node_inherit($repo->cuuid, false);

            // Make sure that all of the individual course directories are set to not interhit parent space permissions.
            $dir = $repo->read_dir($repo->cuuid);

            if (!empty($dir->folders)) {
                foreach ($dir->folders as $folder) {
                    if ((((int)$folder->title) != $folder->title ||
                         (int)$folder->title <= 1) ||
                        (!($course = get_record('course', 'id', $folder->title,
                            '', '', '', '', 'id,shortname')))) {

                        continue;
                    }

                    // Check if we need to add this node to the course store table.
                    if ($result && !record_exists('alfresco_course_store', 'courseid', $course->id)) {
                        $coursestore = new stdClass;
                        $coursestore->courseid = $course->id;
                        $coursestore->uuid     = $folder->uuid;
                        $coursestore->id       = insert_record('alfresco_course_store', $coursestore);

                        $result = !empty($coursestore->id);
                    }

                    $result = $result && $repo->node_inherit($folder->uuid, false);
                    $result = $result && alfresco_node_rename($folder->uuid, $course->shortname);
                }
            }
        }
    }

    if ($result && ($oldversion < 2010032900)) {
        // Only proceed here if the Alfresco plug-in is actually enabled.
        if (isset($CFG->repository_plugins_enabled) && strstr($CFG->repository_plugins_enabled, 'alfresco')) {
            // Handle upgrading some things on the Alfresco repository.
            require_once($CFG->dirroot . '/file/repository/repository.class.php');

            if (!$repo = repository_factory::factory('alfresco')) {
                debugging(get_string('couldnotcreaterepositoryobject', 'repository'), DEBUG_DEVELOPER);
                $result = false;
            }

            $root = $repo->get_root();

            if (!empty($root->uuid)) {
                $dir = $repo->read_dir($root->uuid, true);

                if (!empty($dir->folders)) {
                    foreach ($dir->folders as $folder) {
                        // Process each of these directories to make sure that any non-privileged user cannot directly
                        // access them.
                        if ($folder->title == 'Data Dictionary' || $folder->title == 'Guest Home' || $folder->title == 'Sites') {
                            $a = new stdClass;
                            $a->uuid = $folder->uuid;
                            $a->name = $folder->title;

                            echo '<p>' . get_string('lockingdownpermissionson', 'repository_alfresco', $a) . '</p>';

                            if ($permissions = alfresco_get_permissions($folder->uuid, 'GROUP_EVERYONE')) {
                                foreach ($permissions as $permission) {
                                    // Make sure the node isn't inheriting parent node permissions.
                                    $repo->node_inherit($folder->uuid, false);

                                    // Construct the post data
                                    $postdata = array(
                                        'username'   => 'GROUP_EVERYONE',
                                        'name'       => $permission,
                                        'capability' => ALFRESCO_CAPABILITY_DENIED
                                    );

                                    // We're not going to examine the response (we assume it worked).
                                    $response = alfresco_send('/moodle/setpermissions/' . $folder->uuid, $postdata, 'POST');
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    if ($result && ($oldversion < 2010090300)) {
        // Add the mapping table for organization shared spaces.
        $table = new XMLDBTable('alfresco_organization_store');
        $table->comment = 'Stores organization shared storage UUID values';

        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', false, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('organizationid', XMLDB_TYPE_INTEGER, '10', false, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('uuid', XMLDB_TYPE_CHAR, '36', null, false, null, null, null, null);

        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->addKeyInfo('organizationid', XMLDB_KEY_FOREIGN, array('organizationid'), 'crlm_cluster', array('id'));
        $table->addIndexInfo('organization-uuid', XMLDB_INDEX_UNIQUE, array('organizationid', 'uuid'));

        $result = $result && create_table($table);
    }

    return $result;
}

?>