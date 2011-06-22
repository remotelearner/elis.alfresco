<?php
/**
 * A repository factory that is used to create an instance of a repository
 * plug-in.
 *
 * Note: shamelessly "borrowed" from /enrol/enrol.class.php
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

/*
 * This file was based on /files/index.php from Moodle, with the following
 * copyright and license:
 */
///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.org                                            //
//                                                                       //
// Copyright (C) 2004  Martin Dougiamas  http://moodle.com               //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

    class repository_factory {
        
        function factory($repository = '') {
            global $CFG, $USER;
            if (!$repository) {
                $repository = $CFG->repository;
            }
            if (file_exists("$CFG->dirroot/file/repository/$repository/repository.php")) {
                require_once("$CFG->dirroot/file/repository/$repository/repository.php");
                $class = "repository_plugin_$repository";                
                if (!(isset($USER->repo))) {
                    $USER->repo = new $class;
                } else {
                    $USER->repo = unserialize(serialize($USER->repo));      
                }
                return $USER->repo;
            } else {
                trigger_error("$CFG->dirroot/file/repository/$repository/repository.php does not exist");
                error("$CFG->dirroot/file/repository/$repository/repository.php does not exist");
            }
        }
    }
?>
