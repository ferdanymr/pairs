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

class groups_form extends moodleform {
    //Add elements to form
    function definition() {
        $mform             = $this->_form;
        $groups            = $this->_customdata['groups'];
        $grupoSeleccionado = $this->_customdata['grupoSeleccionado'];
        $radiogrup=array();
        $grup = array();

        foreach($groups as $group){

            $grup[$group->id] = $group->name;
            $default = $group->id;

        }

        $select = $mform->addElement('select', 'groups', 'Grupo:', $grup);
        
        if($grupoSeleccionado){
            $select->setSelected("$grupoSeleccionado");
        }else{
            $select->setSelected("$default");
        }
        

        $opciones = array('10' => '10', '20' => '20', '50' => '50');

        $select = $mform->addElement('select', 'alumn', 'Alumnos por pagina', $opciones);
        $select->setSelected('10');
        
        $buttonarray=array();
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', 'Filtrar');
        $mform->addGroup($buttonarray, 'buttonar', '', ' ', false);
    }

    
    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}