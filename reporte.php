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

$id = optional_param('id', 0, PARAM_INT);

$e  = optional_param('e', 0, PARAM_INT);

$puntosDados  = optional_param('puntosDados', 0, PARAM_INT);

$evaluacion  = optional_param('evaluacion', 0, PARAM_INT);

$puntosRecibidos  = optional_param('puntosRecibidos', 0, PARAM_INT);

$evaluador  = optional_param('evaluador', 0, PARAM_INT);

$profesor  = optional_param('profesor', 0, PARAM_INT);

$alumno  = optional_param('alumno', 0, PARAM_INT);

$trabajo  = optional_param('trabajo', 0, PARAM_INT);

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


$alumno = $pairs->get_info_user($alumno);
$alumno = current($alumno);
$evaluador = $pairs->get_info_user($evaluador);
$evaluador = current($evaluador);

$fs = get_file_storage();
$files = $fs->get_area_files($modulecontext->id, 'mod_pairs', 'submission_attachment', $trabajo);

$file = end($files);

$context = $pairs->context->id;
$filename = $file->get_filename();

$archivoUrl = new moodle_url("/pluginfile.php/$context/mod_pairs/submission_attachment/$trabajo/$filename?forcedownload=1");


$PAGE->set_url(new moodle_url('/mod/pairs/reporte.php', array('id' => $cm->id)));

$PAGE->set_title(get_string('pluginname', 'mod_pairs'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($course->name));

$a = new stdclass();
$a->firstname = $alumno->firstname;
$a->lastname  = $alumno->lastname;

echo '<h2>Detalles</h2>';
echo '<h5 class="mb-5">' . get_string('alumn_p', 'mod_pairs', $a) . '</h5>';
echo '<h5>' . get_string('work', 'mod_pairs') . '</h5>';
echo '<p class="mb-5">
        <span class="ml-5 mr-5"></span><a class="ml-5 btn btn-secondary" href="'. $archivoUrl . '">' . $filename . '</a>
    </p>';

$a = new stdclass();
$a->firstname = $evaluador->firstname;
$a->lastname  = $evaluador->lastname;

echo '<h5 class="mb-5">' . get_string('evaluador', 'mod_pairs', $a) . '</h5>';

if($profesor){

    $profesor = $pairs->get_info_user($profesor);
    $profesor = current($profesor);

    $a = new stdclass();
    $a->firstname = $profesor->firstname;
    $a->lastname  = $profesor->lastname;

    echo '<h5 class="mb-5">' . get_string('profesor', 'mod_pairs', $a) . '</h5>';
}

$opcionesSelec = $pairs->get_evaluation_answers($evaluacion);
$opcionesSelec = array_values($opcionesSelec);

$evaluacion = $pairs->get_evaluation_by_id($evaluacion);
$evaluacion = current($evaluacion);
$evaluacion->edit_user_id = $USER->id;

$envio = $pairs->get_delivery_by_id($trabajo);
$mform = new rubrica_form(new moodle_url('/mod/pairs/reporte.php', array('id' => $cm->id, 'trabajo' => $trabajo, 'evaluacion' => $evaluacion->id)), 
    array('criterios' => $pairs->get_criterios(), 'pairs'=> $pairs, 'opcionesSelec' => $opcionesSelec));

if ($mform->is_cancelled()) {

    redirect($pairs->url_view());    

}else if ($fromform = $mform->get_data()) {    

    $pairs->edit_opciones_criterio($opcionesSelec, $fromform, $evaluacion, $envio);
    
    redirect($pairs->url_view());
}

$mform->display();

echo $OUTPUT->footer();