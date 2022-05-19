<?php

/**
 * Define all the backup steps that will be used by the backup_pairs_activity_task
 */
class backup_pairs_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $pairs = new backup_nested_element('pairs',array('id'),array(
            'fase','name','intro','introformat','timecreated','timemodified','attachment',
            'calif_aprobatoria','assessment','calif_aprob_valoracion','no_decimals',
            'no_attachments','type_attachments','max_size','instruction_attachment','instruction_attachmentformat',
            'instruction_assessment','instruction_assessmentformat','no_revisions','max_points_rubric',
            'retro_conclusion','retro_conclusionformat','course'
        ));
        
        //definimos la envoltura para los criterios de evaluacion
        $pairs_criterios = new backup_nested_element('pairs_criterios');

        $pairs_criterio = new backup_nested_element('pairs_criterio', array('id'),array(
            'criterio','criterioformat'
        ));

        $pairs_opciones_cri = new backup_nested_element('pairs_opciones_cri');

        $pairs_opcion_cri = new backup_nested_element('pairs_opcion_cri',array('id'),array(
            'definicion','rating','pairs_criterio_id'
        ));

        $pairs_deliverys = new backup_nested_element('pairs_deliverys');

        $pairs_delivery = new backup_nested_element('pairs_delivery',array('id'),array(
            'title','comment','attachments','attachment_ready','rating','no_ratings','autor_id'
        ));

        $pairs_evaluacions_user = new backup_nested_element('pairs_evaluacions_user');

        $pairs_evaluacion_user = new backup_nested_element('pairs_evaluacion_user',array('id'),array(
            'is_evaluado','rating','status','edit_user_id','evaluador_id'
        ));

        $pairs_answers_rubric = new backup_nested_element('pairs_answers_rubric');

        $pairs_answer_rubric = new backup_nested_element('pairs_answer_rubric',array('id'),array(
            'pairs_opcion_cri_id','pairs_evaluacion_user_id'
        ));


        // Build the tree
        $pairs->add_child($pairs_criterios);
        $pairs_criterios->add_child($pairs_criterio);
        
        $pairs->add_child($pairs_deliverys);
        $pairs_deliverys->add_child($pairs_delivery);

        $pairs_criterio->add_child($pairs_opciones_cri);
        $pairs_opciones_cri->add_child($pairs_opcion_cri);

        $pairs_delivery->add_child($pairs_evaluacions_user);
        $pairs_evaluacions_user->add_child($pairs_evaluacion_user);

        $pairs_evaluacion_user->add_child($pairs_answers_rubric);
        $pairs_answers_rubric->add_child($pairs_answer_rubric);
        
        // Define sources
        $pairs->set_source_table('pairs', array('id' => backup::VAR_ACTIVITYID));

        $pairs_criterio->set_source_sql('
            SELECT *
              FROM {pairs_criterio}
             WHERE pairs_id = ?',
            array(backup::VAR_PARENTID));

        $pairs_opcion_cri->set_source_sql('
        SELECT *
          FROM {pairs_opcion_cri}
         WHERE pairs_criterio_id = ?',
        array(backup::VAR_PARENTID));
        
        // All the rest of elements only happen if we are including user info
        if ($userinfo) {
            
            $pairs_delivery->set_source_sql('
            SELECT *
              FROM {pairs_delivery}
             WHERE pairs_id = ?',
            array(backup::VAR_PARENTID));

            $pairs_evaluacion_user->set_source_sql('
            SELECT *
              FROM {pairs_evaluacion_user}
             WHERE pairs_delivery_id = ?',
            array(backup::VAR_PARENTID));

            $pairs_answer_rubric->set_source_sql('
            SELECT *
              FROM {pairs_answer_rubric}
             WHERE pairs_evaluacion_user_id = ?',
            array(backup::VAR_PARENTID));

        }

        // Define id annotations
        $pairs_delivery->annotate_ids('user','autor_id');
        $pairs_evaluacion_user->annotate_ids('user','evaluador_id');

        // Define file annotations
        $pairs->annotate_files('mod_pairs', 'intro', null);
        $pairs->annotate_files('mod_pairs', 'instruction_attachment', null);
        $pairs->annotate_files('mod_pairs', 'instruction_assessment', null);
        $pairs->annotate_files('mod_pairs', 'retro_conclusion', null);
        $pairs_criterio->annotate_files('mod_pairs', 'criterio', null);

        $pairs_delivery->annotate_files('mod_pairs', 'submission_attachment', 'id');
        // Return the root element (pairs), wrapped into standard activity structure
        return $this->prepare_activity_structure($pairs);
    }
}