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
 * Library of functions for the event handlers.
 *
 * @package    elis
 * @subpackage File system
 * @copyright  2010 Remote Learner - http://www.remote-learner.net/
 * @author     Justin Filip <jfilip@remote-learner.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once($CFG->dirroot . '/file/repository/repository.class.php');

/**
 * Method to return authentication methods that DO NOT use passwords
 *
 * @return array  list of authentications that DO NOT use passwords
 */
function block_repository_nopasswd_auths() {
    // TBD: determine from auth plugin which don't support passwords ???
    return array('openid', 'cas');
}

/**
 * Handle the event when a user is created in Moodle.
 *
 * @uses $CFG
 * @param object $user Moodle user record object.
 * @return bool True on success, False otherwise.
 */
function block_repository_user_created($user) {
    global $CFG;
    $result = true;

    // Only proceed here if the Alfresco plug-in is actually enabled.
    if (!isset($CFG->repository_plugins_enabled) || (strstr($CFG->repository_plugins_enabled, 'alfresco') === false) ||
        !($repo = repository_factory::factory('alfresco')) || !$repo->is_configured() || !$repo->verify_setup()) {
        error_log("block_repository_user_created(): Alfresco NOT enabled!");
        return true; // TBD
    }

    // create a random password for certain authentications
    $auths = block_repository_nopasswd_auths();
    if (!empty($user->auth) && in_array($user->auth, $auths)) {
        $passwd = random_string(8);
        //$user->password = md5($passwd); // TBD: or reversible encrypt
        //update_record('user', $user);
        //error_log("block_repository_user_created(): generating password for {$user->id} ({$user->auth}) => {$passwd}");
        $result = $repo->migrate_user($user, $passwd);
    }

    return $result;
}

/**
 * Handle the event when a user is deleted in Moodle.
 *
 * @uses $CFG
 * @param object $user Moodle user record object.
 * @return bool True on success, False otherwise.
 */
function block_repository_user_deleted($user) {
    global $CFG;

    // Only proceed here if the Alfresco plug-in is actually enabled.
    if (!isset($CFG->repository_plugins_enabled) || (strstr($CFG->repository_plugins_enabled, 'alfresco') === false) ||
        !($repo = repository_factory::factory('alfresco')) || !$repo->is_configured() || !$repo->verify_setup()) {

        return true;
    }

    $repo->delete_user($user->username);

    return true;
}


/**
 * Handle the event when a user has a role unassigned in Moodle.
 *
 * @uses $CFG
 * @param object $ra The Moodle role_assignment record object.
 * @return bool True on success, False otherwise.
 */
function block_repository_role_unassigned($ra) {
    global $CFG;

    // Only proceed here if the Alfresco plug-in is actually enabled.
    if (!isset($CFG->repository_plugins_enabled) || (strstr($CFG->repository_plugins_enabled, 'alfresco') === false) ||
        !($repo = repository_factory::factory('alfresco')) || !$repo->is_configured() || !$repo->verify_setup()) {

        return true;
    }

    if (!$username = get_field('user', 'username', 'id', $ra->userid)) {
        return true;
    }

    if (!$context = get_record('context', 'id', $ra->contextid)) {
        return true;
    }

    $courses = array();

    if ($context->contextlevel == CONTEXT_COURSE) {
        // Get the course record based on the context instance from the role assignment data.
        $sql = "SELECT c.id, c.shortname
                FROM {$CFG->prefix}context ct
                INNER JOIN {$CFG->prefix}course c ON ct.instanceid = c.id
                AND ct.id = {$ra->contextid}";

        if ($course = get_record_sql($sql)) {
            $courses[$course->id] = $course;
        }
    } else if ($context->contextlevel == CONTEXT_COURSECAT) {
        $sql = "SELECT c.id, c.shortname
                FROM {$CFG->prefix}context ct
                INNER JOIN {$CFG->prefix}course_categories cat ON ct.instanceid = cat.id
                INNER JOIN {$CFG->prefix}course c ON cat.id = c.category
                AND ct.id = {$ra->contextid}
                ORDER BY c.sortorder ASC";

        $courses = get_records_sql($sql);
    }

    if (!empty($courses)) {
        foreach ($courses as $course) {
            $context = get_context_instance(CONTEXT_COURSE, $course->id);

            if (!has_capability('block/repository:viewcoursecontent', $context, $ra->userid, false) &&
                !has_capability('block/repository:createcoursecontent', $context, $ra->userid, false)) {

                if ($uuid = $repo->get_course_store($course->id)) {
                    // Look for Alfresco capabilities in this context for this user and assign permissions as required.
                    if ($permissions = alfresco_get_permissions($uuid, $username)) {
                        foreach ($permissions as $permission) {
                            alfresco_set_permission($username, $uuid, $permission, ALFRESCO_CAPABILITY_DENIED);
                        }
                    }
                }
            }
        }
    }

    $root = $repo->get_root();

    // Check to see if we need to remove root-level Alfresco repository permissions for this user.
    if (!empty($root->uuid)) {
        // If the role this user was just unassigned had the editing capability for the root of the
        // Alfresco repository space associated with it.
        if (record_exists('role_capabilities', 'roleid', $ra->roleid, 'permission', CAP_ALLOW,
                          'capability', 'block/repository:createsitecontent')) {

            // Check to see if this user still has this capapbility somewhere in the system and remove it if not.
            $sql = "SELECT ra.*
                    FROM {$CFG->prefix}role_assignments ra
                    INNER JOIN {$CFG->prefix}role_capabilities rc ON ra.roleid = rc.roleid
                    WHERE ra.userid = {$ra->userid}
                    AND rc.capability = 'block/repository:createsitecontent'";

            if (!record_exists_sql($sql)) {
                if (alfresco_has_permission($root->uuid, $username, true)) {
                    alfresco_set_permission($username, $root->uuid, ALFRESCO_ROLE_COLLABORATOR, ALFRESCO_CAPABILITY_DENIED);
                }
            }
        }

        // If the role this user was just unassigned had the view capability for the root of the
        // Alfresco repository space associated with it.
        if (record_exists('role_capabilities', 'roleid', $ra->roleid, 'permission', CAP_ALLOW,
                          'capability', 'block/repository:viewsitecontent')) {

            // Check to see if this user still has this capapbility somewhere in the system and remove it if not.
            $sql = "SELECT ra.*
                    FROM {$CFG->prefix}role_assignments ra
                    INNER JOIN {$CFG->prefix}role_capabilities rc ON ra.roleid = rc.roleid
                    WHERE ra.userid = {$ra->userid}
                    AND rc.capability = 'block/repository:viewsitecontent'";

            if (!record_exists_sql($sql)) {
                if ($permissions = alfresco_get_permissions($root->uuid, $username)) {
                    foreach ($permissions as $permission) {
                        alfresco_set_permission($username, $root->uuid, $permission, ALFRESCO_CAPABILITY_DENIED);
                    }
                }
            }
        }
    }


    // If the role this user was just unassigned had the editing capability for the Alfresco shared
    // storage space associated with it.
    if (record_exists('role_capabilities', 'roleid', $ra->roleid, 'permission', CAP_ALLOW,
                      'capability', 'block/repository:createsharedcontent')) {

        // Check to see if this user still has this capapbility somewhere in the system and remove it if not.
        $sql = "SELECT ra.*
                FROM {$CFG->prefix}role_assignments ra
                INNER JOIN {$CFG->prefix}role_capabilities rc ON ra.roleid = rc.roleid
                WHERE ra.userid = {$ra->userid}
                AND rc.capability = 'block/repository:createsharedcontent'";

        if (!record_exists_sql($sql)) {
            if (alfresco_has_permission($repo->suuid, $username, true)) {
                alfresco_set_permission($username, $repo->suuid, ALFRESCO_ROLE_COLLABORATOR, ALFRESCO_CAPABILITY_DENIED);
            }
        }
    }

    // If the role this user was just unassigned had the view capability for the Alfresco shared
    // storage space associated with it.
    if (record_exists('role_capabilities', 'roleid', $ra->roleid, 'permission', CAP_ALLOW,
                      'capability', 'block/repository:viewsharedcontent')) {

        // Check to see if this user still has this capapbility somewhere in the system and remove it if not.
        $sql = "SELECT ra.*
                FROM {$CFG->prefix}role_assignments ra
                INNER JOIN {$CFG->prefix}role_capabilities rc ON ra.roleid = rc.roleid
                WHERE ra.userid = {$ra->userid}
                AND rc.capability = 'block/repository:viewsharedcontent'";

        if (!record_exists_sql($sql)) {
            if ($permissions = alfresco_get_permissions($repo->suuid, $username)) {
                foreach ($permissions as $permission) {
                    alfresco_set_permission($username, $repo->suuid, $permission, ALFRESCO_CAPABILITY_DENIED);
                }
            }
        }
    }

    return true;
}


/**
 * Handle the event when a user is assigned to a cluster.
 *
 * @uses $CFG
 * @param object $clusterinfo The Moodle role_assignment record object.
 * @return bool True on success or failure (event handlers must always return true).
 */
function block_repository_cluster_assigned($clusterinfo) {
    global $CFG;

    // Only proceed here if the Alfresco plug-in is actually enabled.
    if (!isset($CFG->repository_plugins_enabled) || (strstr($CFG->repository_plugins_enabled, 'alfresco') === false) ||
        !($repo = repository_factory::factory('alfresco'))) {

        return true;
    }

    // Get the Moodle user ID from the CM user ID.
    if (!$muserid = cm_get_moodleuserid($clusterinfo->userid)) {
        return true;
    }

    if (!$username = get_field('user', 'username', 'id', $muserid)) {
        return true;
    }

    if (!$cluster = get_record('crlm_cluster', 'id', $clusterinfo->clusterid)) {
        return true;
    }

    if (!file_exists($CFG->dirroot . '/curriculum/plugins/cluster_classification/clusterclassification.class.php')) {
        return true;
    }

    require_once($CFG->dirroot . '/curriculum/plugins/cluster_classification/clusterclassification.class.php');

    // Get the extra cluster data and ensure it is present before proceeding.
    $clusterdata = clusterclassification::get_for_cluster($cluster);

    if (empty($clusterdata->params)) {
        return true;
    }

    $clusterparams = unserialize($clusterdata->params);

    // Make sure this cluster has the Alfresco shared folder property defined
    if (empty($clusterparams['alfresco_shared_folder'])) {
        return true;
    }

    // Make sure we can get the storage space from Alfresco for this organization.
    if (!$uuid = $repo->get_organization_store($cluster->id)) {
        return true;
    }

    $context = get_context_instance(context_level_base::get_custom_context_level('cluster', 'block_curr_admin'), $cluster->id);

    $sql = "SELECT rc.*
            FROM {$CFG->prefix}role_capabilities rc
            INNER JOIN {$CFG->prefix}role r ON r.id = rc.roleid
            INNER JOIN {$CFG->prefix}role_assignments ra ON ra.roleid = r.id
            WHERE ra.contextid = {$context->id}
            AND ra.userid = {$muserid}
            AND rc.capability = 'block/repository:createorganizationcontent'
            AND rc.permission = " . CAP_ALLOW;

    // Check if the user has the the editing capability for the Alfresco organization shared
    // storage space assigned at this cluster context level or is designated as a cluster leader in order to
    // enable editing permission on the Alfresco space.
    if (record_exists('crlm_usercluster', 'userid', $clusterinfo->userid, 'clusterid', $cluster->id, 'leader', 1) ||
        record_exists_sql($sql)) {

        // Ensure that this user already has an Alfresco account.
        if (!$repo->alfresco_userdir($username)) {
            if (!$repo->migrate_user($username)) {
                return true;
            }
        }

        if (!alfresco_has_permission($uuid, $username, true)) {
            alfresco_set_permission($username, $uuid, ALFRESCO_ROLE_COLLABORATOR, ALFRESCO_CAPABILITY_ALLOWED);
        }

    // Double-check tjhat the user is designated as a cluster member (we should not be here otherwise) in order to
    // enable viewinging permission on the Alfresco space.
    } else if (record_exists('crlm_usercluster', 'userid', $clusterinfo->userid, 'clusterid', $cluster->id, 'leader', 0)) {
        // Ensure that this user already has an Alfresco account.
        if (!$repo->alfresco_userdir($username)) {
            if (!$repo->migrate_user($username)) {
                return true;
            }
        }

        if (!alfresco_has_permission($uuid, $username, false)) {
            alfresco_set_permission($username, $uuid, ALFRESCO_ROLE_CONSUMER, ALFRESCO_CAPABILITY_ALLOWED);
        }
    }

    return true;
}


/**
 * Handle the event when a user is unassigned to a cluster.
 *
 * @uses $CFG
 * @param object $clusterinfo The Moodle role_assignment record object.
 * @return bool True on success or failure (event handlers must always return true).
 */
function block_repository_cluster_deassigned($clusterinfo) {
    global $CFG;

    // Only proceed here if the Alfresco plug-in is actually enabled.
    if (!isset($CFG->repository_plugins_enabled) || (strstr($CFG->repository_plugins_enabled, 'alfresco') === false) ||
        !($repo = repository_factory::factory('alfresco'))) {

        return true;
    }

    // Get the Moodle user ID from the CM user ID.
    if (!$muserid = cm_get_moodleuserid($clusterinfo->userid)) {
        return true;
    }

    if (!$username = get_field('user', 'username', 'id', $muserid)) {
        return true;
    }

    if (!$cluster = get_record('crlm_cluster', 'id', $clusterinfo->clusterid)) {
        return true;
    }

    // Does this organization have an Alfresco storage space?
    if (!$uuid = $repo->get_organization_store($cluster->id, false)) {
        return true;
    }

    $context = get_context_instance(context_level_base::get_custom_context_level('cluster', 'block_curr_admin'), $cluster->id);

    $sql = "SELECT rc.*
            FROM {$CFG->prefix}role_capabilities rc
            INNER JOIN {$CFG->prefix}role r ON r.id = rc.roleid
            INNER JOIN {$CFG->prefix}role_assignments ra ON ra.roleid = r.id
            WHERE ra.contextid = {$context->id}
            AND ra.userid = {$muserid}
            AND rc.capability = 'block/repository:createorganizationcontent'
            AND rc.permission = " . CAP_ALLOW;

    // Check if the user has a specific role assignment on the cluster context with the editing capability
    if (!record_exists_sql($sql)) {
        // Remove all non-editing permissions for this user on the organization shared space.
        if ($permissions = alfresco_get_permissions($uuid, $username)) {
            foreach ($permissions as $permission) {
                // Do not remove editing permissions if this user still actually has a cluster membership.
                if ($permission == ALFRESCO_ROLE_COLLABORATOR) {
                    continue;
                }

                alfresco_set_permission($username, $uuid, $permission, ALFRESCO_CAPABILITY_DENIED);
            }
        }

    // Remove all permissions for this user on the organization shared space.
    } else if ($permissions = alfresco_get_permissions($uuid, $username)) {
        foreach ($permissions as $permission) {
            // Do not remove view permissions if this user still actually has a cluster membership.
            if ($permission == ALFRESCO_ROLE_CONSUMER &&
                record_exists('crlm_usercluster', 'userid', $clusterinfo->userid, 'clusterid', $cluster->id, 'leader', 0)) {

                continue;
            }

            alfresco_set_permission($username, $uuid, $permission, ALFRESCO_CAPABILITY_DENIED);
        }
    }

    return true;
}

?>
