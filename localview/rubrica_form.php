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
 * @package     mod_taller
 * @copyright   2021 Fernando Munoz <fernando_munoz@cuaieed.unam.mx>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

//moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");

class rubrica_form extends moodleform {
    //Add elements to form
    function definition() {
        $mform         = $this->_form;
        $taller        = $this->_customdata['taller'];
        $criterios     = $this->_customdata['criterios'];
        $opcionesSelec = $this->_customdata['opcionesSelec'];
        $noCriterios   = count($criterios);
        $opcionSeleccionada;
        $mform->addElement('header', 'rubrica', get_string('rub','mod_taller'));
        $i = 1;
        foreach ($criterios as $criterio) { 
            
            $radioarray=array();
            
            $opciones = $taller->get_opciones_criterio($criterio->id);

            foreach ($opciones as $opcion) {

                $radioarray[] = $mform->createElement('radio', "opcion$i", '', $opcion->definicion, $opcion->id, $attributes);
                $opcionSeleccionada = $opcion->id;
            }

            if($opcionesSelec[$i - 1]){
                $mform->setDefault("opcion$i", $opcionesSelec[$i - 1]->taller_opcion_cri_id);
            }else{
                $mform->setDefault("opcion$i", $opcionSeleccionada);
            }
        
            $mform->addGroup($radioarray, "criterio$i", $criterio->criterio, array(''), false);
            
            $i++;
        }

        $this->add_action_buttons();
    }

    
    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}
