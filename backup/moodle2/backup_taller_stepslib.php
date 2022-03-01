<?php

/**
 * Define all the backup steps that will be used by the backup_taller_activity_task
 */
class backup_taller_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $taller = new backup_nested_element('taller',array('id'),array(
            'fase','name','intro','introformat','timecreated','timemodified','calif_envio',
            'calif_aprobatoria','calif_valoracion','calif_aprob_valoracion','no_decimales',
            'no_archivos','tipo_arch','tam_max','instruccion_envio','instruccion_envioformat',
            'instruccion_valoracion','instruccion_valoracionformat','no_revisiones','puntos_max_rubrica',
            'retro_conclusion','retro_conclusionformat','course'
        ));
        
        //definimos la envoltura para los criterios de evaluacion
        $criterios_evaluacion = new backup_nested_element('criterios_evaluacion');

        $criterio_evaluacion = new backup_nested_element('criterio_evaluacion', array('id'),array(
            'criterio','criterioformat'
        ));

        $opciones_criterio = new backup_nested_element('opciones_criterio');

        $opcion_criterio = new backup_nested_element('opcion_criterio',array('id'),array(
            'definicion','calificacion','taller_criterio_id'
        ));

        $entregas = new backup_nested_element('entregas');

        $entrega = new backup_nested_element('entrega',array('id'),array(
            'titulo','comentario','envios','envio_listo','calificacion','no_calificaciones','autor_id'
        ));

        $evaluaciones = new backup_nested_element('evaluaciones');

        $evaluacion = new backup_nested_element('evaluacion',array('id'),array(
            'is_evaluado','calificacion','status','edit_user_id','evaluador_id'
        ));

        $respuestasEvaluaciones = new backup_nested_element('respuestasEvaluaciones');

        $respuestaEvaluacion = new backup_nested_element('respuestaEvaluacion',array('id'),array(
        ));


        // Build the tree
        $taller->add_child($criterios_evaluacion);
        $criterios_evaluacion->add_child($criterio_evaluacion);
        
        $taller->add_child($entregas);
        $entregas->add_child($entrega);

        $criterio_evaluacion->add_child($opciones_criterio);
        $opciones_criterio->add_child($opcion_criterio);

        $entrega->add_child($evaluaciones);
        $evaluaciones->add_child($evaluacion);

        $evaluacion->add_child($respuestasEvaluaciones);
        $respuestasEvaluaciones->add_child($respuestaEvaluacion);
        
        // Define sources
        $taller->set_source_table('taller', array('id' => backup::VAR_ACTIVITYID));

        $criterio_evaluacion->set_source_sql('
            SELECT *
              FROM {taller_criterio}
             WHERE taller_id = ?',
            array(backup::VAR_PARENTID));

        $opcion_criterio->set_source_sql('
        SELECT *
          FROM {taller_opcion_cri}
         WHERE taller_criterio_id = ?',
        array(backup::VAR_PARENTID));
        
        // All the rest of elements only happen if we are including user info
        if ($userinfo) {
            
            $entrega->set_source_sql('
            SELECT *
              FROM {taller_entrega}
             WHERE taller_id = ?',
            array(backup::VAR_PARENTID));

            $evaluacion->set_source_sql('
            SELECT *
              FROM {taller_evaluacion_user}
             WHERE taller_entrega_id = ?',
            array(backup::VAR_PARENTID));

            $respuestaEvaluacion->set_source_sql('
            SELECT *
              FROM {taller_respuesta_rubrica}
             WHERE taller_evaluacion_user_id = ?',
            array(backup::VAR_PARENTID));

        }

        // Define id annotations
        $entrega->annotate_ids('user','autor_id');
        $evaluacion->annotate_ids('user','evaluador_id');

        // Define file annotations
        $taller->annotate_files('mod_taller', 'intro', null);
        $taller->annotate_files('mod_taller', 'instruccion_envio', null);
        $taller->annotate_files('mod_taller', 'instruccion_valoracion', null);
        $taller->annotate_files('mod_taller', 'retro_conclusion', null);
        $criterio_evaluacion->annotate_files('mod_taller', 'criterio', null);

        $entrega->annotate_files('mod_taller', 'submission_attachment', 'id');
        // Return the root element (taller), wrapped into standard activity structure
        return $this->prepare_activity_structure($taller);
    }
}