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
 * @package     mod_taller
 * @copyright   2021 Fernando Munoz <fernando_munoz@cuaieed.unam.mx>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_workshop_activity_task
 */

/**
 * Structure step to restore one workshop activity
 */

 class restore_taller_activity_structure_step extends restore_activity_structure_step {

protected function define_structure() {

    $paths = array();
    $userinfo = $this->get_setting_value('userinfo');

    $paths[]  = new restore_path_element('taller', '/activity/taller');

    $paths[] = new restore_path_element('criterio_evaluacion', '/activity/taller/criterios_evaluacion/criterio_evaluacion');
    $paths[] = new restore_path_element('opcion_criterio', '/activity/taller/criterios_evaluacion/criterio_evaluacion/opciones_criterio/opcion_criterio');

    // End here if no-user data has been selected
    if (!$userinfo) {
        return $this->prepare_activity_structure($paths);
    }

    $paths[] = new restore_path_element('entrega', '/activity/taller/entregas/entrega');
    $paths[] = new restore_path_element('evaluacion', '/activity/taller/entregas/entrega/evaluaciones/evaluacion');
    $paths[] = new restore_path_element('respuestaEvaluacion', '/activity/taller/entregas/entrega/evaluaciones/evaluacion/respuestasEvaluaciones/respuestaEvaluacion');
    // Return the paths wrapped into standard activity structure
    return $this->prepare_activity_structure($paths);
}

protected function process_taller($data) {
    global $DB;

    $data = (object)$data;
    $oldid = $data->id;
    $data->course = $this->get_courseid();
    


    // insert the choice record
    $newitemid = $DB->insert_record('taller', $data);
    // immediately after inserting "activity" record, call this
    $this->apply_activity_instance($newitemid);
}

protected function process_criterio_evaluacion($data) {
    global $DB;

    $data = (object)$data;
    $oldid = $data->id;

    $data->taller_id = $this->get_new_parentid('taller');
    
    $newitemid = $DB->insert_record('taller_criterio', $data);
    $this->set_mapping('criterio_evaluacion', $oldid, $newitemid);
}

protected function process_opcion_criterio($data) {
    global $DB;

    $data = (object)$data;
    $oldid = $data->id;

    $data->taller_criterio_id = $this->get_new_parentid('criterio_evaluacion');

    $newitemid = $DB->insert_record('taller_opcion_cri', $data);
    $this->set_mapping('opcion_criterio', $oldid, $newitemid);
}

protected function process_entrega($data) {
    global $DB;

    $data = (object)$data;
    $oldid = $data->id;

    $data->taller_id = $this->get_new_parentid('taller');
    $data->autor_id = $this->get_mappingid('user', $data->autor_id);

    $newitemid = $DB->insert_record('taller_entrega', $data);
    $this->set_mapping('entrega', $oldid, $newitemid, true);
}

protected function process_evaluacion($data) {
    global $DB;

    $data = (object)$data;
    $oldid = $data->id;

    $data->taller_entrega_id = $this->get_new_parentid('entrega');
    $data->evaluador_id = $this->get_mappingid('user', $data->evaluador_id);
    $data->taller_id = $this->get_mappingid('taller', $data->taller_id);

    $newitemid = $DB->insert_record('taller_evaluacion_user', $data);
    $this->set_mapping('evaluacion', $oldid, $newitemid);
}

protected function process_respuestaEvaluacion($data) {
    global $DB;

    $data = (object)$data;
    $oldid = $data->id;

    $data->taller_evaluacion_user_id = $this->get_new_parentid('evaluacion');
    $data->taller_opcion_cri_id = $this->get_mappingid('opcion_criterio', $data->taller_opcion_cri_id);

    $newitemid = $DB->insert_record('taller_respuesta_rubrica', $data);
    $this->set_mapping('respuestaEvaluacion', $oldid, $newitemid);
}

protected function after_execute() {
    // Add choice related files, no need to match by itemname (just internally handled context)
    $this->add_related_files('mod_taller', 'intro', null);
    $this->add_related_files('mod_taller', 'instruccion_envio', null);
    $this->add_related_files('mod_taller', 'instruccion_valoracion', null);
    $this->add_related_files('mod_taller', 'retro_conclusion', null);
    $this->add_related_files('mod_taller', 'criterio', null);

    $this->add_related_files('mod_taller', 'submission_attachment', 'entrega');
}
}