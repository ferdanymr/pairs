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
 * @package     mod_pairs
 * @copyright   2021 Fernando Munoz <fernando_munoz@cuaieed.unam.mx>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_workshop_activity_task
 */

/**
 * Structure step to restore one workshop activity
 */

 class restore_pairs_activity_structure_step extends restore_activity_structure_step {

protected function define_structure() {

    $paths = array();
    $userinfo = $this->get_setting_value('userinfo');

    $paths[]  = new restore_path_element('pairs', '/activity/pairs');

    $paths[] = new restore_path_element('criterio_evaluacion', '/activity/pairs/criterios_evaluacion/criterio_evaluacion');
    $paths[] = new restore_path_element('opcion_criterio', '/activity/pairs/criterios_evaluacion/criterio_evaluacion/opciones_criterio/opcion_criterio');

    // End here if no-user data has been selected
    if (!$userinfo) {
        return $this->prepare_activity_structure($paths);
    }

    $paths[] = new restore_path_element('delivery', '/activity/pairs/deliverys/delivery');
    $paths[] = new restore_path_element('evaluacion', '/activity/pairs/deliverys/delivery/evaluaciones/evaluacion');
    $paths[] = new restore_path_element('respuestaEvaluacion', '/activity/pairs/deliverys/delivery/evaluaciones/evaluacion/respuestasEvaluaciones/respuestaEvaluacion');
    // Return the paths wrapped into standard activity structure
    return $this->prepare_activity_structure($paths);
}

protected function process_pairs($data) {
    global $DB;

    $data = (object)$data;
    $oldid = $data->id;
    $data->course = $this->get_courseid();
    


    // insert the choice record
    $newitemid = $DB->insert_record('pairs', $data);
    // immediately after inserting "activity" record, call this
    $this->apply_activity_instance($newitemid);
}

protected function process_criterio_evaluacion($data) {
    global $DB;

    $data = (object)$data;
    $oldid = $data->id;

    $data->pairs_id = $this->get_new_parentid('pairs');
    
    $newitemid = $DB->insert_record('pairs_criterio', $data);
    $this->set_mapping('criterio_evaluacion', $oldid, $newitemid);
}

protected function process_opcion_criterio($data) {
    global $DB;

    $data = (object)$data;
    $oldid = $data->id;

    $data->pairs_criterio_id = $this->get_new_parentid('criterio_evaluacion');

    $newitemid = $DB->insert_record('pairs_opcion_cri', $data);
    $this->set_mapping('opcion_criterio', $oldid, $newitemid);
}

protected function process_delivery($data) {
    global $DB;

    $data = (object)$data;
    $oldid = $data->id;

    $data->pairs_id = $this->get_new_parentid('pairs');
    $data->autor_id = $this->get_mappingid('user', $data->autor_id);

    $newitemid = $DB->insert_record('pairs_delivery', $data);
    $this->set_mapping('delivery', $oldid, $newitemid, true);
}

protected function process_evaluacion($data) {
    global $DB;

    $data = (object)$data;
    $oldid = $data->id;

    $data->pairs_delivery_id = $this->get_new_parentid('delivery');
    $data->evaluador_id = $this->get_mappingid('user', $data->evaluador_id);
    $data->pairs_id = $this->get_mappingid('pairs', $data->pairs_id);

    $newitemid = $DB->insert_record('pairs_evaluacion_user', $data);
    $this->set_mapping('evaluacion', $oldid, $newitemid);
}

protected function process_respuestaEvaluacion($data) {
    global $DB;

    $data = (object)$data;
    $oldid = $data->id;

    $data->pairs_evaluacion_user_id = $this->get_new_parentid('evaluacion');
    $data->pairs_opcion_cri_id = $this->get_mappingid('opcion_criterio', $data->pairs_opcion_cri_id);

    $newitemid = $DB->insert_record('pairs_answer_rubric', $data);
    $this->set_mapping('respuestaEvaluacion', $oldid, $newitemid);
}

protected function after_execute() {
    // Add choice related files, no need to match by itemname (just internally handled context)
    $this->add_related_files('mod_pairs', 'intro', null);
    $this->add_related_files('mod_pairs', 'instruction_attachment', null);
    $this->add_related_files('mod_pairs', 'instruction_assessment', null);
    $this->add_related_files('mod_pairs', 'retro_conclusion', null);
    $this->add_related_files('mod_pairs', 'criterio', null);

    $this->add_related_files('mod_pairs', 'submission_attachment', 'delivery');
}
}