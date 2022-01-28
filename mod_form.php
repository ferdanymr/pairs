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

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->libdir . '/filelib.php');

/**
 * Plugin version and other meta-data are defined here.
 *
 * @package     mod_taller
 * @copyright   2021 Fernando Munoz <fernando_munoz@cuaieed.unam.mx>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class mod_taller_mod_form extends moodleform_mod {

    protected $course = null;

    /**
     * Constructor
     */
    public function __construct($current, $section, $cm, $course) {
        $this->course = $course;
        parent::__construct($current, $section, $cm, $course);
    }

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('tallername', 'mod_taller'), array('size' => '64'));

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        //introduccion o descripcion
        $this->standard_intro_elements('Descripcion');
        
        //añadimos cabecera de Ajustes Calificación
        $mform->addElement('header', 'ajustescalif', get_string('ajustescalif', 'mod_taller'));
        
        //Añadimos etiqueta de estatedia y etiqueta de rubrica
        $mform->addElement('static', 'label1', get_string('estrategia','mod_taller'), get_string('rub', 'mod_taller'));
        $mform->addHelpButton('label1', 'estrategia', 'mod_taller');
        
        //listado del 100 al 0
        $opciones = array();
        for($i = 100; $i >= 0; $i--){
            $opciones[$i] = $i;
        }

        $select = $mform->addElement('select', 'calif_envio', get_string('calif_env', 'mod_taller'), $opciones);
        $select->setSelected('80');
        $mform->addHelpButton('calif_envio', 'calif_env', 'mod_taller');

        $mform->addElement('float', 'calif_aprobatoria', get_string('calif_aprob', 'mod_taller'));
        $mform->addHelpButton('calif_aprobatoria', 'calif_aprob', 'mod_taller');

        $select1 = $mform->addElement('select', 'calif_valoracion', get_string('calif_val', 'mod_taller'), $opciones);
        $select1->setSelected('20');
        $mform->addHelpButton('calif_valoracion', 'calif_val', 'mod_taller');

        $mform->addElement('float', 'calif_aprob_valoracion', get_string('calif_aprob_val', 'mod_taller'));
        $mform->addHelpButton('calif_aprob_valoracion', 'calif_aprob_val', 'mod_taller');
        
        $opciones = array();
        for($i = 5; $i >= 0; $i--){
            $opciones[$i] = $i;
        }

        $select2 = $mform->addElement('select', 'no_decimales', get_string('no_decimales', 'mod_taller'), $opciones);
        $select2->setSelected('0');
        
        //parametros de envio ---------------------
        $mform->addElement('header', 'param_env', get_string('param_env', 'mod_taller'));
        //editor de instrucciones de envio
        $mform->addElement('editor', 'instruccion_envio', get_string('param_inst', 'mod_taller'));

        $options = array();
        for ($i = 7; $i >= 1; $i--) {
            $options[$i] = $i;
        }

        $select2 = $mform->addElement('select', 'no_archivos', get_string('param_max', 'mod_taller'), $options);
        $select2->setSelected('1');

        $mform->addElement('filetypes', 'tipo_arch', get_string('param_type_arch', 'mod_taller'));
        $mform->addHelpButton('tipo_arch', 'param_type_arch', 'mod_taller');

        $options = get_max_upload_sizes($CFG->maxbytes, $this->course->maxbytes);
        $mform->addElement('select', 'tam_max', get_string('param_tam_max', 'mod_taller'), $options);
        
        //Configuración de la valoración---------------------------------------
        $mform->addElement('header', 'conf_val', get_string('conf_val', 'mod_taller'));
        
        $mform->addElement('editor', 'instruccion_valoracion', get_string('conf_val_inst', 'mod_taller'));


        $options = array();
        for ($i = 10; $i >= 1; $i--) {
            $options[$i] = $i;
        }

        $select2 = $mform->addElement('select', 'no_revisiones', get_string('no_revisiones', 'mod_taller'), $options);
        $select2->setSelected('3');
        $mform->addHelpButton('no_revisiones', 'no_revisiones', 'mod_taller');

        //Retroalimentación---------------------------------------
        $mform->addElement('header', 'retro', get_string('retro', 'mod_taller'));
        
        $mform->addElement('editor', 'retro_conclusion', get_string('retro_con', 'mod_taller'));
        
        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }

    /**
     * Prepares the form before data are set
     *
     * Additional wysiwyg editor are prepared here, the introeditor is prepared automatically by core.
     * Grade items are set here because the core modedit supports single grade item only.
     *
     * @param array $data to be set
     * @return void
     */
    public function data_preprocessing(&$data) {
        if ($this->current->instance) {
            $editor = array('text'=>$data['instruccion_envio'], 'format'=>$data['instruccion_envioformat']);
            $data['instruccion_envio'] = $editor;
            
            $editor = array('text'=>$data['instruccion_valoracion'], 'format'=>$data['instruccion_valoracionformat']);
            $data['instruccion_valoracion'] = $editor;

            $editor = array('text'=>$data['retro_conclusion'], 'format'=>$data['retro_conclusionformat']);
            $data['retro_conclusion'] = $editor;
        }
    }
}
