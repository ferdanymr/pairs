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
        $criterios_evaluacion = new backup_nested_element('criterios_evaluacion');

        $criterio_evaluacion = new backup_nested_element('criterio_evaluacion', array('id'),array(
            'criterio','criterioformat'
        ));

        $opciones_criterio = new backup_nested_element('opciones_criterio');

        $opcion_criterio = new backup_nested_element('opcion_criterio',array('id'),array(
            'definicion','rating','pairs_criterio_id'
        ));

        $deliverys = new backup_nested_element('deliverys');

        $delivery = new backup_nested_element('delivery',array('id'),array(
            'title','comment','attachments','attachment_ready','rating','no_ratings','autor_id'
        ));

        $evaluaciones = new backup_nested_element('evaluaciones');

        $evaluacion = new backup_nested_element('evaluacion',array('id'),array(
            'is_evaluado','rating','status','edit_user_id','evaluador_id'
        ));

        $respuestasEvaluaciones = new backup_nested_element('respuestasEvaluaciones');

        $respuestaEvaluacion = new backup_nested_element('respuestaEvaluacion',array('id'),array(
        ));


        // Build the tree
        $pairs->add_child($criterios_evaluacion);
        $criterios_evaluacion->add_child($criterio_evaluacion);
        
        $pairs->add_child($deliverys);
        $deliverys->add_child($delivery);

        $criterio_evaluacion->add_child($opciones_criterio);
        $opciones_criterio->add_child($opcion_criterio);

        $delivery->add_child($evaluaciones);
        $evaluaciones->add_child($evaluacion);

        $evaluacion->add_child($respuestasEvaluaciones);
        $respuestasEvaluaciones->add_child($respuestaEvaluacion);
        
        // Define sources
        $pairs->set_source_table('pairs', array('id' => backup::VAR_ACTIVITYID));

        $criterio_evaluacion->set_source_sql('
            SELECT *
              FROM {pairs_criterio}
             WHERE pairs_id = ?',
            array(backup::VAR_PARENTID));

        $opcion_criterio->set_source_sql('
        SELECT *
          FROM {pairs_opcion_cri}
         WHERE pairs_criterio_id = ?',
        array(backup::VAR_PARENTID));
        
        // All the rest of elements only happen if we are including user info
        if ($userinfo) {
            
            $delivery->set_source_sql('
            SELECT *
              FROM {pairs_delivery}
             WHERE pairs_id = ?',
            array(backup::VAR_PARENTID));

            $evaluacion->set_source_sql('
            SELECT *
              FROM {pairs_evaluacion_user}
             WHERE pairs_delivery_id = ?',
            array(backup::VAR_PARENTID));

            $respuestaEvaluacion->set_source_sql('
            SELECT *
              FROM {pairs_answer_rubric}
             WHERE pairs_evaluacion_user_id = ?',
            array(backup::VAR_PARENTID));

        }

        // Define id annotations
        $delivery->annotate_ids('user','autor_id');
        $evaluacion->annotate_ids('user','evaluador_id');

        // Define file annotations
        $pairs->annotate_files('mod_pairs', 'intro', null);
        $pairs->annotate_files('mod_pairs', 'instruction_attachment', null);
        $pairs->annotate_files('mod_pairs', 'instruction_assessment', null);
        $pairs->annotate_files('mod_pairs', 'retro_conclusion', null);
        $criterio_evaluacion->annotate_files('mod_pairs', 'criterio', null);

        $delivery->annotate_files('mod_pairs', 'submission_attachment', 'id');
        // Return the root element (pairs), wrapped into standard activity structure
        return $this->prepare_activity_structure($pairs);
    }
}