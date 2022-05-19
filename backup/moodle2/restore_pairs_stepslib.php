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

    $paths[] = new restore_path_element('pairs_criterio', '/activity/pairs/pairs_criterios/pairs_criterio');
    $paths[] = new restore_path_element('pairs_opcion_cri', '/activity/pairs/pairs_criterios/pairs_criterio/pairs_opciones_cri/pairs_opcion_cri');

    // End here if no-user data has been selected
    if (!$userinfo) {
        return $this->prepare_activity_structure($paths);
    }

    $paths[] = new restore_path_element('pairs_delivery', '/activity/pairs/pairs_deliverys/pairs_delivery');
    $paths[] = new restore_path_element('pairs_evaluacion_user', '/activity/pairs/pairs_deliverys/pairs_delivery/pairs_evaluacions_user/pairs_evaluacion_user');
    $paths[] = new restore_path_element('pairs_answer_rubric', '/activity/pairs/pairs_deliverys/pairs_delivery/pairs_evaluacions_user/pairs_evaluacion_user/pairs_answers_rubric/pairs_answer_rubric');
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

protected function process_pairs_criterio($data) {
    global $DB;

    $data = (object)$data;
    $oldid = $data->id;

    $data->pairs_id = $this->get_new_parentid('pairs');
    
    $newitemid = $DB->insert_record('pairs_criterio', $data);
    $this->set_mapping('pairs_criterio', $oldid, $newitemid);
}

protected function process_pairs_opcion_cri($data) {
    global $DB;

    $data = (object)$data;
    $oldid = $data->id;

    $data->pairs_criterio_id = $this->get_new_parentid('pairs_criterio');

    $newitemid = $DB->insert_record('pairs_opcion_cri', $data);
    $this->set_mapping('pairs_opcion_cri', $oldid, $newitemid);
}

protected function process_pairs_delivery($data) {
    global $DB;

    $data = (object)$data;
    $oldid = $data->id;

    $data->pairs_id = $this->get_new_parentid('pairs');
    $data->autor_id = $this->get_mappingid('user', $data->autor_id);

    $newitemid = $DB->insert_record('pairs_delivery', $data);
    $this->set_mapping('pairs_delivery', $oldid, $newitemid, true);
}

protected function process_pairs_evaluacion_user($data) {
    global $DB;

    $data = (object)$data;
    $oldid = $data->id;

    $data->pairs_delivery_id = $this->get_new_parentid('pairs_delivery');
    $data->evaluador_id = $this->get_mappingid('user', $data->evaluador_id);
    $data->pairs_id = $this->get_new_parentid('pairs');

    $newitemid = $DB->insert_record('pairs_evaluacion_user', $data);
    $this->set_mapping('pairs_evaluacion_user', $oldid, $newitemid);
}

protected function process_pairs_answer_rubric($data) {
    global $DB;

    $data = (object)$data;
    $oldid = $data->id;

    $data->pairs_evaluacion_user_id = $this->get_new_parentid('pairs_evaluacion_user');
    $data->pairs_opcion_cri_id = $this->get_mappingid('pairs_opcion_cri', $data->pairs_opcion_cri_id);

    $newitemid = $DB->insert_record('pairs_answer_rubric', $data);
    $this->set_mapping('pairs_answer_rubric', $oldid, $newitemid);
}

protected function after_execute() {
    // Add choice related files, no need to match by itemname (just internally handled context)
    $this->add_related_files('mod_pairs', 'intro', null);
    $this->add_related_files('mod_pairs', 'instruction_attachment', null);
    $this->add_related_files('mod_pairs', 'instruction_assessment', null);
    $this->add_related_files('mod_pairs', 'retro_conclusion', null);
    $this->add_related_files('mod_pairs', 'criterio', null);

    $this->add_related_files('mod_pairs', 'submission_attachment', 'pairs_delivery');
}

}