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
 * Display information about all the mod_evaluatebypair modules in the requested course.
 *
 * @package     mod_pairs
 * @copyright   2021 Fernando Munoz <fernando_munoz@cuaieed.unam.mx>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once('locallib.php');
require_once('localview/rubrica_form.php');

defined('MOODLE_INTERNAL') || die();

global $DB,$USER;

$id = optional_param('id', 0, PARAM_INT);

$idTrabajo = optional_param('trabajo', 0, PARAM_INT);

$edit = optional_param('edit', 0, PARAM_INT);

$e  = optional_param('e', 0, PARAM_INT);

if ($id) {
    $cm             = get_coursemodule_from_id('pairs', $id, 0, false, MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('pairs', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($e) {
    $moduleinstance = $DB->get_record('pairs', array('id' => $n), '*', MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm             = get_coursemodule_from_instance('pairs', $moduleinstance->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);

$modulecontext   = context_module::instance($cm->id);
$pairs =  new pairs($moduleinstance, $cm, $course);

if($idTrabajo){

    $envio = $pairs->get_delivery_by_id($idTrabajo);

}else{
    
    $evaluacion = $pairs->get_pending_evaluation_by_userId($USER->id);
    $evaluacion = current($evaluacion);

    if(!$evaluacion){
        
        $evaluacionesUser    = $pairs->get_complete_evaluations_by_userId($USER->id);

        if(count($evaluacionesUser) != 0){

            $ids = array();
            foreach ($evaluacionesUser as $eva) {
                array_push($ids, $eva->pairs_delivery_id);
            }

            $ids_separados_comas = implode(",",$ids);

        }else{
            $ids = array('0');
            $ids_separados_comas = implode(",",$ids);

        }

        $groupmode = groups_get_activity_groupmode($pairs->cm);
        //0 si no hay grupos; 1 si hay grupos separados
        if($groupmode){
            
            $groupid = groups_get_activity_group($pairs->cm, true);

            $envio = $pairs->get_delivery_for_evaluate($USER->id, $ids_separados_comas, $groupid);

        }else{

            $envio = $pairs->get_delivery_for_evaluate($USER->id, $ids_separados_comas, $groupmode);

        }

        
        
        if(count($envio) != 0){
            $envio                          = current($envio);
            $evaluacion                     = new stdClass;
            $evaluacion->is_evaluado        = '0';
            $evaluacion->rating       = '0';
            $evaluacion->status             = '1';
            $evaluacion->edit_user_id       = '0';
            $evaluacion->pairs_delivery_id  = $envio->id;
            $evaluacion->evaluador_id       = $USER->id;
            $evaluacion->pairs_id          = $pairs->id;
            $DB->insert_record('pairs_evaluacion_user', $evaluacion);

        }
    }else{
        $envio = $pairs->get_delivery_by_id($evaluacion->pairs_delivery_id);
    }
}

if($edit){

    $evaluacion = $pairs->get_complete_evaluation_by_deliveryId($envio->id, $USER->id);
    $evaluacion = current($evaluacion);
    $opcionesSelec = $pairs->get_evaluation_answers($evaluacion->id);
    $opcionesSelec = array_values($opcionesSelec);

    $mform = new rubrica_form(new moodle_url('/mod/pairs/evaluaciones.php', array('id' => $cm->id, 'trabajo' => $idTrabajo, 'edit' => '1')), 
    array('criterios' => $pairs->get_criterios(), 'pairs'=> $pairs, 'opcionesSelec' => $opcionesSelec));

}else{

    $mform = new rubrica_form(new moodle_url('/mod/pairs/evaluaciones.php', array('id' => $cm->id)), 
    array('criterios' => $pairs->get_criterios(), 'pairs'=> $pairs, 'opcionesSelec' => $opcionesSelec));

}

if ($mform->is_cancelled()) {

    redirect($pairs->url_view());

}else if ($fromform = $mform->get_data()) {

    $pairs->edit_opciones_criterio($opcionesSelec, $fromform, $evaluacion, $envio);
    $pairs->assign_rating_by_valoracion($USER->id);
    redirect($pairs->url_view());
}

$PAGE->set_url(new moodle_url('/mod/pairs/envio.php', array('id' => $cm->id)));

$PAGE->set_title(get_string('pluginname', 'mod_pairs'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($course->name));

if(count($envio) == 0){
    
    echo '<div class="row">';
    echo '	<div class="col-12 text-center">';
    echo '      <h3>'. get_string('no_env', 'mod_pairs') . '</h3>';
    echo '	</div>';
    echo '</div>';

}else{

    print_collapsible_region_start('','instrucciones-evaluacion', get_string('instruc_evaluacion','mod_pairs'));
    echo '<div class="row ml-2 mr-2 border border-top-0 border-primary shadow p-3 mb-5 bg-white rounded">';
    echo '	<div class="col-12">';
    echo "      <p>$pairs->instruction_assessment</p>";
    echo '	</div>';
    echo '</div>';
    print_collapsible_region_end();

    $fs = get_file_storage();
    $files = $fs->get_area_files($modulecontext->id, 'mod_pairs', 'submission_attachment', $envio->id);

    $file = end($files);
    
    $context = $pairs->context->id;
    $filename = $file->get_filename();
    
    $archivoUrl = new moodle_url("/pluginfile.php/$context/mod_pairs/submission_attachment/$envio->id/$filename?forcedownload=1");
    

    print_collapsible_region_start('','archivo',get_string('download_arch','mod_pairs'));
    echo '<div class="row ml-2 mr-2 border border-top-0 border-primary shadow p-3 mb-5 bg-white rounded">';
    echo '	<div class="col-12">';
    echo '     <p><a class="btn btn-secondary" href="'. $archivoUrl.'">'.$filename.'</a></p>';
    echo '	</div>';
    echo '</div>';
    print_collapsible_region_end();

    $mform->display();
    
}

echo $OUTPUT->footer();