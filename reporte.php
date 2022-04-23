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
require_once('localview/rubrica_form.php');

defined('MOODLE_INTERNAL') || die();

$id = optional_param('id', 0, PARAM_INT);

$e  = optional_param('e', 0, PARAM_INT);

$puntosDados  = optional_param('puntosDados', 0, PARAM_INT);

$evaluacion  = optional_param('evaluacion', 0, PARAM_INT);

$puntosRecibidos  = optional_param('puntosRecibidos', 0, PARAM_INT);

$evaluador  = optional_param('evaluador', 0, PARAM_INT);

$alumno  = optional_param('alumno', 0, PARAM_INT);

$trabajo  = optional_param('trabajo', 0, PARAM_INT);

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


$alumno = $taller->get_info_user($alumno);
$alumno = current($alumno);
$evaluador = $taller->get_info_user($evaluador);
$evaluador = current($evaluador);

$fs = get_file_storage();
$files = $fs->get_area_files($modulecontext->id, 'mod_taller', 'submission_attachment', $trabajo);

$file = end($files);

$context = $taller->context->id;
$filename = $file->get_filename();

$archivoUrl = new moodle_url("/pluginfile.php/$context/mod_taller/submission_attachment/$trabajo/$filename?forcedownload=1");


$PAGE->set_url(new moodle_url('/mod/taller/reporte.php', array('id' => $cm->id)));

$PAGE->set_title(get_string('pluginname', 'mod_taller'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($course->name));

echo '<h2>Detalles</h2>';
echo "<h5 class='mb-5'>Alumno: $alumno->firstname $alumno->lastname</h5>";
echo '<h5>Trabajo:</h5>';
echo '<p class="mb-5">
        <span class="ml-5 mr-5"></span><a class="ml-5 btn btn-secondary" href="'. $archivoUrl . '">' . $filename . '</a>
    </p>';
echo "<h5 class='mb-5'>Evaluador: $evaluador->firstname $evaluador->lastname</h5>";

$opcionesSelec = $taller->get_respuestas_evaluacion($evaluacion);
$opcionesSelec = array_values($opcionesSelec);

$mform = new rubrica_form(new moodle_url('/mod/taller/evaluaciones.php', array('id' => $cm->id, 'trabajo' => $idTrabajo, 'edit' => '1')), 
    array('criterios' => $taller->get_criterios(), 'taller'=> $taller, 'opcionesSelec' => $opcionesSelec));

$mform->display();

echo $OUTPUT->footer();