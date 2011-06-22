<?php
/**
 * Fix up the Alfresco SQL files created by the MySQL Migration Toolkit as documented here:
 *
 * http://wiki.alfresco.com/wiki/Migrating_from_HSQL
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

    define('FILE_CREATE', 'create.sql');
    define('FILE_INSERT', 'inserts.sql');


/// Make sure this is called from the command-line
    if (!empty($_SERVER['GATEWAY_INTERFACE'])){
        die('This script is not accessible from the webserver');
    }


    $changes = array(
        '`PUBLIC`'        => '`alfresco`',
        '`public`'        => '`alfresco`',
        'utf8_general_ci' => 'utf8_unicode_ci'
    );

    $search = array(
        '`ALF_ACCESS_CONTROL_ENTRY`',
        '`ALF_ACCESS_CONTROL_LIST`',
        '`ALF_ACE_CONTEXT`',
        '`ALF_ACL_CHANGE_SET`',
        '`ALF_ACL_MEMBER`',
        '`ALF_ACTIVITY_FEED`',
        '`ALF_ACTIVITY_FEED_CONTROL`',
        '`ALF_ACTIVITY_POST`',
        '`ALF_APPLIED_PATCH`',
        '`ALF_ATTRIBUTES`',
        '`ALF_AUDIT_CONFIG`',
        '`ALF_AUDIT_DATE`',
        '`ALF_AUDIT_FACT`',
        '`ALF_AUDIT_SOURCE`',
        '`ALF_AUTHORITY`',
        '`ALF_AUTHORITY_ALIAS`',
        '`ALF_AUTH_EXT_KEYS`',
        '`ALF_CHILD_ASSOC`',
        '`ALF_CONTENT_URL`',
        '`ALF_GLOBAL_ATTRIBUTES`',
        '`ALF_LIST_ATTRIBUTE_ENTRIES`',
        '`ALF_LOCALE`',
        '`ALF_MAP_ATTRIBUTE_ENTRIES`',
        '`ALF_NAMESPACE`',
        '`ALF_NODE`',
        '`ALF_NODE_ASPECTS`',
        '`ALF_NODE_ASSOC`',
        '`ALF_NODE_PROPERTIES`',
        '`ALF_NODE_STATUS`',
        '`ALF_PERMISSION`',
        '`ALF_QNAME`',
        '`ALF_SERVER`',
        '`ALF_STORE`',
        '`ALF_TRANSACTION`',
        '`ALF_USAGE_DELTA`',
        '`ALF_VERSION_COUNT`',
        '`AVM_ASPECTS`',
        '`AVM_CHILD_ENTRIES`',
        '`AVM_HISTORY_LINKS`',
        '`AVM_ISSUER_IDS`',
        '`AVM_MERGE_LINKS`',
        '`AVM_NODE_PROPERTIES`',
        '`AVM_NODES`',
        '`AVM_STORE_PROPERTIES`',
        '`AVM_STORES`',
        '`AVM_VERSION_LAYERED_NODE_ENTRY`',
        '`AVM_VERSION_ROOTS`',
    );

    if ($argc != 3) {
        die(print_usage());
    }

    if (!is_file($argv[1]) || !is_file($argv[2])) {
        die(print_usage());
    }

    if (!$fh = fopen($argv[1], 'r')) {
        die('Could not open file ' . $argv[1] . ' for reading.' . "\n");
    }

    echo ' Re-writing contents of ' . $argv[1] . '...';

    $output = '';
    $found  = false;
    $using  = '';
    $lc     = 0;

    while (!feof($fh)) {
        $line = fgets($fh, 4096);
        $lc++;

        if (!$found) {
            foreach ($search as $str) {
                if (!$found && strstr($line, $str) !== false) {
                    if (strpos($line, 'DROP') === 0 || strpos($line, 'CREATE') === 0) {
                        $found = true;
                        $using = $str;
                    }
                }
            }
        }

        if ($found) {
            if (strpos($line, ')') === 0) {
                $found = false;
                $using = '';
            } else {
                preg_match_all('/`[A-Z0-9_]+`/', $line, $matches);
                if (!empty($matches[0])) {
                    foreach ($matches[0] as $match) {
                        $fixed = '`' . strtolower(substr($match, 1, strlen($match) - 2)) . '`';
                        $line  = str_replace($match, $fixed, $line);

                    /// Store this value to look for it later.
                        if (!isset($changes[$match])) {
                            $changes[$match] = $fixed;
                        }
                    }
                }
            }
        }

        if (!empty($changes)) {
            foreach ($changes as $orig => $new) {
                $line = str_replace($orig, $new, $line);
            }
        }

    /// Ensure that we give a proper default for primary key (BIG)INT columns.
        if (strstr($line,'`id`') && strstr($line, 'INT')) {
            $line = preg_replace('/(NOT NULL),$/', 'NOT NULL auto_increment,', $line);
        }

        if (strstr($line,'`ID_`') && strstr($line, 'INT')) {
            $line = preg_replace('/(NOT NULL),$/', 'NOT NULL auto_increment,', $line);
        }

        $output .= $line;
    }

    fclose($fh);

    echo 'done!' . "\n";

    file_put_contents('./' . FILE_CREATE, $output);

    echo ' Modified contents written out to create.sql' . "\n";

/// STEP 2 - Handle the inserts file.
    if (!$fh = fopen($argv[2], 'r')) {
        die('Could not open file ' . $argv[2] . ' for writing.' . "\n");
    }

    echo ' Re-writing contents of ' . $argv[2] . '...';

    $output = '';

    while (!feof($fh)) {
        $line = fgets($fh, 4096);

        if (!empty($changes)) {
            foreach ($changes as $orig => $new) {
                $line = str_replace($orig, $new, $line);
            }
        }

    /// Make sure that we empty the table first.
        preg_match('/INSERT INTO (`[a-z]+`.`[a-zA-Z_]+`)\(/', $line, $matches);
        if (!empty($matches[1])) {
            $line = 'TRUNCATE ' . $matches[1] . ";\n" . $line;
        }

        $output .= $line;
    }

    echo 'done!' . "\n";

    file_put_contents('./' . FILE_INSERT, $output);

    echo ' Modified contents written out to inserts.sql' . "\n";

    exit;


    function print_usage() {
        return 'Usage: ' . basename(__FILE__) . ' [createfile.sql] [insertfile.sql]' . "\n";
    }

?>
