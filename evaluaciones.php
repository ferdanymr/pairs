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
 * @package     mod_taller
 * @copyright   2021 Fernando Munoz <fernando_munoz@cuaieed.unam.mx>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once('locallib.php');
require_once('localview/envio_form.php');

defined('MOODLE_INTERNAL') || die();

global $DB,$USER;

$id = optional_param('id', 0, PARAM_INT);

$idTrabajo = optional_param('trabajo', 0, PARAM_INT);

$e  = optional_param('e', 0, PARAM_INT);

if ($id) {
    $cm             = get_coursemodule_from_id('taller', $id, 0, false, MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('taller', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($e) {
    $moduleinstance = $DB->get_record('taller', array('id' => $n), '*', MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm             = get_coursemodule_from_instance('taller', $moduleinstance->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);

$modulecontext   = context_module::instance($cm->id);
$taller =  new taller($moduleinstance, $cm, $course);

if($idTrabajo){

    $envio = $taller->get_envio_by_id($idTrabajo);

}else{
    
    $envio = $taller->get_envio_para_evaluar($USER->id);

    if(count($envio) != 0){

        $envio                          = current($envio);
        $evaluacion                     = new stdClass;
        $evaluacion->is_evaluado        = '0';
        $evaluacion->status             = '1';
        $evaluacion->edit_user_id       = '0';
        $evaluacion->entrega_id         = $envio->id;
        $evaluacion->evaluador_id       = $USER->id;
        $evaluacion->taller_id = $taller->id;
        $DB->insert_record('taller_evaluacion_user', $evaluacion);

    }
}

$PAGE->set_url(new moodle_url('/mod/taller/envio.php', array('id' => $cm->id)));

$PAGE->set_title(get_string('pluginname', 'mod_taller'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($course->name));

if(count($envio) == 0){
    
    echo '<div class="row">';
    echo '	<div class="col-12 text-center">';
    echo '      <h3>De momento no hay envios para evaluar vuelve un poco más tarde</h3>';
    echo '	</div>';
    echo '</div>';

}else{

    print_collapsible_region_start('','instrucciones-evaluacion','Instrucciones evaluacion');
    echo '<div class="row">';
    echo '	<div class="col-12">';
    echo "      <p>$taller->instruccion_valoracion</p>";
    echo '	</div>';
    echo '</div>';
    print_collapsible_region_end();

    $fs = get_file_storage();
    $files = $fs->get_area_files($modulecontext->id, 'mod_taller', 'attachments', $envio->id);

    $file = end($files);

    $data = $taller->get_archivos_by_content_hash($file->get_contenthash(), $envio->autor_id);
    
    $data = end($data);
    $archivoUrl = new moodle_url("/draftfile.php/$data->contextid/user/draft/$data->itemid/$data->filename");
    

    print_collapsible_region_start('','archivo','Descarga aquí el archivo a evaluar');
    echo '<div class="row">';
    echo '	<div class="col-12">';
    echo '     <p><a class="btn btn-secondary" href="'. $archivoUrl.'">'.$data->filename.'</a></p>';
    echo '	</div>';
    echo '</div>';
    print_collapsible_region_end();
    
}

echo $OUTPUT->footer();