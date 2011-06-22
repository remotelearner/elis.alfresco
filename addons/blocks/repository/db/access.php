<?php // $Id$

/**
 * Capabilities definitions.
 *
 * @version $Id$
 * @author Open Knowledge Technologies - http://www.oktech.ca/
 * @author Remote Learner - http://www.remote-learner.net/
 * @author Justin Filip <jfilip@oktech.ca>
 */

$block_repository_capabilities = array(

    'block/repository:createsitecontent' => array(

        'riskbitmask' => RISK_XSS| RISK_DATALOSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'admin' => CAP_ALLOW
        )
    ),

    'block/repository:viewsitecontent' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'admin' => CAP_ALLOW
        )
    ),

    'block/repository:viewsitecontent' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'admin' => CAP_ALLOW
        )
    ),

    'block/repository:createsharedcontent' => array(

        'riskbitmask' => RISK_XSS| RISK_DATALOSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    ),

    'block/repository:viewsharedcontent' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    ),


    'block/repository:createcoursecontent' => array(

        'riskbitmask' => RISK_XSS| RISK_DATALOSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    ),

    'block/repository:viewcoursecontent' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    ),

    'block/repository:createowncontent' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    ),

    'block/repository:viewowncontent' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    ),

    'block/repository:createorganizationcontent' => array(

        'riskbitmask' => RISK_XSS| RISK_DATALOSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'admin' => CAP_ALLOW
        )
    ),

    'block/repository:vieworganizationcontent' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'admin' => CAP_ALLOW
        )
    )

);


?>
