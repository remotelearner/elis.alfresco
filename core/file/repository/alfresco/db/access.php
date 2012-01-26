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
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

    $file_repository_alfresco_capabilities = array(

        'repository/alfresco:createsitecontent' => array(

            'captype' => 'write',
            'contextlevel' => CONTEXT_SYSTEM,
            'legacy' => array(
                'admin' => CAP_ALLOW
            )
        ),

        'repository/alfresco:viewsitecontent' => array(

            'riskbitmask' => RISK_PERSONAL,

            'captype' => 'read',
            'contextlevel' => CONTEXT_SYSTEM,
            'legacy' => array(
                'admin' => CAP_ALLOW
            )
        ),

        'repository/alfresco:createcoursecontent' => array(

            'captype' => 'write',
            'contextlevel' => CONTEXT_COURSE,
            'legacy' => array(
                'editingteacher' => CAP_ALLOW,
                'coursecreator' => CAP_ALLOW,
                'admin' => CAP_ALLOW
            )
        ),

        'repository/alfresco:viewcoursecontent' => array(

            'riskbitmask' => RISK_PERSONAL,

            'captype' => 'read',
            'contextlevel' => CONTEXT_COURSE,
            'legacy' => array(
                'editingteacher' => CAP_ALLOW,
                'coursecreator' => CAP_ALLOW,
                'admin' => CAP_ALLOW
            )
        ),

        'repository/alfresco:createowncontent' => array(
            'captype' => 'write',
            'contextlevel' => CONTEXT_SYSTEM,
            'legacy' => array(
                'student' => CAP_ALLOW,
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'coursecreator' => CAP_ALLOW,
                'admin' => CAP_ALLOW
            )
        ),

        'repository/alfresco:viewowncontent' => array(
            'captype' => 'read',
            'contextlevel' => CONTEXT_SYSTEM,
            'legacy' => array(
                'student' => CAP_ALLOW,
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'coursecreator' => CAP_ALLOW,
                'admin' => CAP_ALLOW
            )
        ),

        'repository/alfresco:createorganizationcontent' => array(
            'captype' => 'write',
            'contextlevel' => CONTEXT_SYSTEM,
            'legacy' => array(
                'editingteacher' => CAP_ALLOW,
                'coursecreator' => CAP_ALLOW,
                'admin' => CAP_ALLOW
            )
        ),

        'repository/alfresco:vieworganizationcontent' => array(
            'captype' => 'read',
            'contextlevel' => CONTEXT_SYSTEM,
            'legacy' => array(
                'teacher' => CAP_ALLOW,
                'editingteacher' => CAP_ALLOW,
                'coursecreator' => CAP_ALLOW,
                'admin' => CAP_ALLOW
            )
        )

    );

?>
