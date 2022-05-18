<?php
// This file is part of Moodle - http://moodle.org/
//
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
 * Plugin version and other meta-data are defined here.
 *
 * @package     mod_pairs
 * @copyright   2021 Fernando Munoz <fernando_munoz@cuaieed.unam.mx>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

//moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");

class envio_form extends moodleform {
    //Add elements to form
    function definition() {
        $mform = $this->_form;
        $current        = $this->_customdata['current'];
        $attachmentopts = $this->_customdata['attachmentopts'];

        $mform->addElement('header', 'general', get_string('envio', 'mod_pairs'));

        $mform->addElement('text', 'title', get_string('title', 'mod_pairs'));
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');
        $mform->addRule('title', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('textarea', "comment", 'Comentario', array('cols' => 60, 'rows' => 3));

        $mform->addElement('static', 'filemanagerinfo', get_string('num_max_arch', 'mod_pairs'), 1);
        
        $mform->addElement('filemanager', 'attachment_filemanager', get_string('adjunto', 'pairs'),
                                null, $attachmentopts);

        $mform->addRule('attachment_filemanager', null, 'required', null, 'client');
        
        $this->add_action_buttons();

        $this->set_data($current);
    }

    
    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}
