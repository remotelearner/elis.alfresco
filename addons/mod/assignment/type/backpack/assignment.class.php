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
 * @package    mod-assignment
 * @subpackage Repository assignment type
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 */

require_once($CFG->dirroot . '/mod/assignment/type/upload/assignment.class.php');
require_once($CFG->libdir . '/formslib.php');
require_once(dirname(__FILE__) . '/chooserepositoryfile.php');
require_once($CFG->dirroot . '/file/repository/repository.class.php');


/**
 * Extend the 'upload' assignment class for assignments where you upload multiple files
 *
 */
class assignment_backpack extends assignment_upload {
    function assignment_backpack($cmid='staticonly', $assignment=NULL, $cm=NULL, $course=NULL) {
        parent::assignment_base($cmid, $assignment, $cm, $course);
        $this->type = 'backpack';
        $this->release = '1.9.2';
    }

    /**
     * Retrieves the repository object used by this assignment type, if applicable
     *
     * @return  mixed  An object representing the repository used to store files, or
     *                 false isf the appropriate configuration is not complete
     */
    function get_repository_object() {
        global $CFG;

        //make sure a default repository plugin is selected
        if (empty($CFG->repository)) {
            return false;
        }

        //make sure everything is configured as needed
        if (!isset($CFG->repository_plugins_enabled) || (strstr($CFG->repository_plugins_enabled, 'alfresco') === false) ||
        !($repo = repository_factory::factory('alfresco')) || !$repo->is_configured() || !$repo->verify_setup()) {
            //accessibility / configuration error
            return false;
        }

        //success
        return $repo;
    }

    /**
     * Displays the assignment content - this method overrides
     * the parent class, adding a repository availability check
     */
    function view() {
        global $USER;

        require_capability('mod/assignment:view', $this->context);

        //start of RL base-class modification
        $repo = $this->get_repository_object();
        if (!$repo) {
            //configuration is not complete
            $message = get_string('errorrepository', 'assignment_backpack');
            //wrap the error with further instructions
            print_error('errorrepositoryview', 'assignment_backpack', '', $message);
        }
        //end of RL base-class modification

        add_to_log($this->course->id, 'assignment', 'view', "view.php?id={$this->cm->id}", $this->assignment->id, $this->cm->id);

        $this->view_header();

        if ($this->assignment->timeavailable > time()
          and !has_capability('mod/assignment:grade', $this->context)      // grading user can see it anytime
          and $this->assignment->var3) {                                   // force hiding before available date
            print_simple_box_start('center', '', '', 0, 'generalbox', 'intro');
            print_string('notavailableyet', 'assignment');
            print_simple_box_end();
        } else {
            $this->view_intro();
        }

        $this->view_dates();

        $filecount = $this->count_user_files($USER->id);
        $submission = $this->get_submission($USER->id);

        $this->view_feedback();

        if (!$this->drafts_tracked() or !$this->isopen() or $this->is_finalized($submission)) {
            print_heading(get_string('submission', 'assignment'), '', 3);
        } else {
            print_heading(get_string('submissiondraft', 'assignment'), '', 3);
        }

        if ($filecount and $submission) {
            print_simple_box($this->print_user_files($USER->id, true), 'center');
        } else {
            if (!$this->isopen() or $this->is_finalized($submission)) {
                print_simple_box(get_string('nofiles', 'assignment'), 'center');
            } else {
                print_simple_box(get_string('nofilesyet', 'assignment'), 'center');
            }
        }

        if (has_capability('mod/assignment:submit', $this->context)) {
            $this->view_upload_form();
        }

        if ($this->notes_allowed()) {
            print_heading(get_string('notes', 'assignment'), '', 3);
            $this->view_notes();
        }

        $this->view_final_submission();
        $this->view_footer();
    }

    function view_upload_form() {
        global $CFG, $USER;

        $submission = $this->get_submission($USER->id);

        if ($this->is_finalized($submission)) {
            // no uploading
            return;
        }

        if ($this->can_upload_file($submission)) {
            $bp_form = new backpack_form('upload.php');
            $bp_form->set_data(array('id'=>$this->cm->id, 'a'=>$this->assignment->id, 'action'=>'uploadfile'));
            $bp_form->display();
        }
    }

    function view_notes() {
        global $USER;

        if ($submission = $this->get_submission($USER->id)
          and !empty($submission->data1)) {
            print_simple_box(format_text($submission->data1, FORMAT_HTML), 'center', '630px');
        } else {
            print_simple_box(get_string('notesempty', 'assignment'), 'center');
        }
        if ($this->can_update_notes($submission)) {
            $options = array ('id' => $this->cm->id, 'action' => 'editnotes');
            echo '<div style="text-align:center">';
            print_single_button('upload.php', $options, get_string('edit'), 'post', '_self', false);
            echo '</div>';
        }
    }

    function upload_notes() {
        global $CFG, $USER;

        $action = required_param('action', PARAM_ALPHA);

        $returnurl = 'view.php?id='.$this->cm->id;

        $mform = new mod_assignment_backpack_notes_form();

        $defaults = new object();
        $defaults->id = $this->cm->id;

        if ($submission = $this->get_submission($USER->id)) {
            $defaults->text = clean_text($submission->data1);
        } else {
            $defaults->text = '';
        }

        $mform->set_data($defaults);

        if ($mform->is_cancelled()) {
            redirect('view.php?id='.$this->cm->id);
        }

        if (!$this->can_update_notes($submission)) {
            $this->view_header(get_string('upload'));
            notify(get_string('uploaderror', 'assignment'));
            print_continue($returnurl);
            $this->view_footer();
            die;
        }

        if ($data = $mform->get_data() and $action == 'savenotes') {
            $submission = $this->get_submission($USER->id, true); // get or create submission
            $updated = new object();
            $updated->id           = $submission->id;
            $updated->timemodified = time();
            $updated->data1        = $data->text;

            if (update_record('assignment_submissions', $updated)) {
                add_to_log($this->course->id, 'assignment', 'upload', 'view.php?a='.$this->assignment->id, $this->assignment->id, $this->cm->id);
                redirect($returnurl);
                $submission = $this->get_submission($USER->id);
                $this->update_grade($submission);

            } else {
                $this->view_header(get_string('notes', 'assignment'));
                notify(get_string('notesupdateerror', 'assignment'));
                print_continue($returnurl);
                $this->view_footer();
                die;
            }
        }

        // show notes edit form
        $this->view_header(get_string('notes', 'assignment'));

        print_heading(get_string('notes', 'assignment'), '');

        $mform->display();

        $this->view_footer();
        die;
    }

    function upload_file() {
        global $CFG, $USER;

        $mode   = optional_param('mode', '', PARAM_ALPHA);
        $offset = optional_param('offset', 0, PARAM_INT);

        $returnurl = 'view.php?id=' . $this->cm->id;
        $status    = false;  // Indicated whether the file was successfully moved or not.

        $form = new backpack_form();

        // Make sure that data was returned from the form.
        if (!$data = $form->get_data()) {
            $data = $form->get_submitted_data();
        }

        $dir = $this->file_area_name($USER->id);
        check_dir_exists($CFG->dataroot . '/' . $dir, true, true); // better to create now so that student submissions do not block it later

        $filecount  = $this->count_user_files($USER->id);
        $submission = $this->get_submission($USER->id);

        // Ensure that this user can actually submit a file to this assignment or not.
        if (!$this->can_upload_file($submission)) {
            $this->view_header(get_string('upload'));
            notify(get_string('uploaderror', 'assignment'));
            print_continue($returnurl);
            $this->view_footer();
            die;
        }

        //obtain the repository object, if possible
        //(should be obtainable since the config has already been checked)
        $repo = $this->get_repository_object();

        // If a repository file was chosen for upload
        if (!empty($data->alfrescoassignment) && isset($repo) && $repo->verify_setup() && $repo->is_configured()) {
            $file = $data->alfrescoassignment;

            // Get the UUID value from the repo file URL.
            if (preg_match('/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/', $file, $matches) > 0) {
                if (!empty($matches[0])) {
                    $uuid = $matches[0];

                    $info = $repo->get_info($uuid);
                    $status = $repo->copy_local($uuid, $info->title, $CFG->dataroot . '/' . $dir);
                }
            }

        // If a local file was chosen for upload
        } else if (!empty($data->addfile)) {
            require_once($CFG->dirroot . '/lib/uploadlib.php');
            $um = new upload_manager('localassignment', false, true, $this->course, false, $this->assignment->maxbytes, true);
            $status = $um->process_file_uploads($dir);
        }

        if ($status) {
            $submission = $this->get_submission($USER->id, true); //create new submission if needed
            $updated = new stdClass;
            $updated->id           = $submission->id;
            $updated->timemodified = time();

            if (update_record('assignment_submissions', $updated)) {
                add_to_log($this->course->id, 'assignment', 'upload',
                        'view.php?a='.$this->assignment->id, $this->assignment->id, $this->cm->id);
                $submission = $this->get_submission($USER->id);
                $this->update_grade($submission);
                if (!$this->drafts_tracked()) {
                    $this->email_teachers($submission);
                }
            } else {
                $new_filename = $um->get_new_filename();
                $this->view_header(get_string('upload'));
                notify(get_string('uploadnotregistered', 'assignment', $new_filename));
                print_continue($returnurl);
                $this->view_footer();
                die;
            }

            redirect('view.php?id=' . $this->cm->id);
        }

        $this->view_header(get_string('upload'));
        notify(get_string('uploaderror', 'assignment'));

        if (!empty($um)) {
            echo $um->get_errors();
        }

        print_continue($returnurl);
        $this->view_footer();
        die;
    }

    /**
     * Sets up form elements specific to this assignment type for use in a special section
     * of the activity editing form
     *
     * @param  object  $mform  The form to update in-place with additional fields
     */
    function setup_elements(&$mform) {
        $repo = $this->get_repository_object();

        if ($repo === false) {
            //core repository error message
            $message = get_string('errorrepository', 'assignment_backpack');
            //wrap it with helpful instructions
            $message = get_string('errorrepositoryconfig', 'assignment_backpack', $message);

            //this will set text color to red
            $message = '<span class="notifyproblem">'.$message.'</span>';

            //add the message as text
            $mform->addElement('static', 'repositoryerror', '', $message);
        } else {
            //config is ok, so add the appropriate elements
            return parent::setup_elements($mform);
        }
    }
}

class mod_assignment_backpack_notes_form extends moodleform {
    function definition() {
        $mform =& $this->_form;

        // visible elements
        $mform->addElement('htmleditor', 'text', get_string('notes', 'assignment'), array('cols'=>85, 'rows'=>30));
        $mform->setType('text', PARAM_RAW); // to be cleaned before display
        $mform->setHelpButton('text', array('reading', 'writing'), false, 'editorhelpbutton');

        // hidden params
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'action', 'savenotes');
        $mform->setType('id', PARAM_ALPHA);

        // buttons
        $this->add_action_buttons();
    }
}

class backpack_form extends moodleform {
    function definition() {
        global $CFG, $USER;

        $mform = $this->_form;

        $mform->registerElementType('chooserepositoryfile', dirname(__FILE__) . '/chooserepositoryfile.php',
                                    'MoodleQuickForm_chooserepositoryfile');

        $mform->addElement('hidden', 'id');
        $mform->addElement('hidden', 'a');
        $mform->addElement('hidden', 'action');

        $mform->addElement('header', 'filepickerheader', 'Choose repository file or upload from your system');

        $options = array('userid' => $USER->id);
        $attrs   = array(
            'maxlength' => 255,
            'size'      => 48
        );

        $mform->addElement('chooserepositoryfile', 'alfrescoassignment', get_string('choosefrommyfiles', 'repository_alfresco'), $options, $attrs);

        $mform->addElement('static');
        $mform->addElement('file', 'localassignment', get_string('chooselocalfile', 'repository_alfresco'));

        $mform->addElement('static');
        $mform->addElement('submit', 'addfile', 'Add File');
    }


    function validation($data, $files) {
        if (empty($data->alfrescoassignment) && empty($files->localassignment)) {
            return array(
                'alfrescoassignment' => 'Must include a file of one type',
                'localassignment'    => 'Must include a file of one type'
            );
        }

        return array();
    }
}

?>
