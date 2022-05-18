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
 * Prints an instance of mod_pairs.
 *
 * @package     mod_pairs
 * @copyright   2021 Fernando Munoz <fernando_munoz@cuaieed.unam.mx>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once('locallib.php');
require_once('localview/aspectos_form.php');
require_once('localview/groups_form.php');

// Id del curso
$id = optional_param('id', 0, PARAM_INT);

//Si hay un envio aqui capturaremos su id
$envio->id = optional_param('env', 0, PARAM_INT);

// ... module instance id.
$e  = optional_param('e', 0, PARAM_INT);

//contador del numero de aspectos
$noAspectos = optional_param('no', 0, PARAM_INT);

$confirm_env = optional_param('confirm_env', 0, PARAM_INT);

$grupo = optional_param('grupo', 0, PARAM_INT);

$noAlumnos = optional_param('noAlumnos', 0, PARAM_INT);

$fin = optional_param('fin', 0, PARAM_INT);

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

$pairs = new pairs($moduleinstance, $cm, $course);

/////////////////////////////////////////////////////////////////////////////////////////////////
//bloque para configurar la vista de criterios en dado caso de que la fase sea 0               //
/////////////////////////////////////////////////////////////////////////////////////////////////
if ($pairs->fase == 0) {

    //validamos si por get se mando el numero de aspectos al cual le agregaremos 2 más
    //si no se tienen se asignaran por defecto 2
    if ($noAspectos) {

        $noAspectos += 2;
    } else {

        $noAspectos = pairs::NO_ASPECTOS;
    }

    //se define el formulario con la url a la que mandara los datos a la hora de hacer submit
    //los parametros enviados son el id del curso y el numero de aspectos actual
    // al formulario por aparte le mandamos tambien el numero de aspectos
    $mform = new aspectos_form(new moodle_url('/mod/pairs/view.php', array('id' => $cm->id, 'no' => $noAspectos)), $noAspectos);

    if ($mform->is_cancelled()) {
        //Si se cancela el formulario se regrasara a la pantalla principal del curso
        redirect(new moodle_url('/course/view.php', array('id' => $course->id)));
    } else if ($fromform = $mform->get_data()) {
        //si se hace submit se preparan los datos para insertarlos en la Base de Datos        
        $pairs->add_criterios($fromform, $noAspectos);

        $moduleinstance->fase = 1;
        $DB->update_record('pairs', $moduleinstance, $bulk = false);

        redirect(new moodle_url('/mod/pairs/view.php', array('id' => $cm->id)));
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////
    //bloque para configurar la vista de criterios en dado caso de que la fase sea 1               //
    /////////////////////////////////////////////////////////////////////////////////////////////////
} else {
    //primero verificamos si el usuario ya hizo un envio o no para modificar la vista de acuerdo a su envio
    if (!$envio->id) {
        $data = $pairs->get_delivery_by_userId($USER->id);
        $envio = end($data);
        if (empty($envio->id)) {
            $envio = new stdClass;
            $envio->id = null;
        }
    }
}

//seteamos la url de la pagina
$PAGE->set_url($pairs->url_view());
//seteamos el title de la pagina
$PAGE->set_title(get_string('pluginname', 'mod_pairs'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($pairs->context);
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/pairs/styles.css'));

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($course->name));

/////////////////////////////////////////////////////////////////////////////////////////////////
//Si la fase es 0 siginifa configuracion entonces mostramos el formulario                      //
/////////////////////////////////////////////////////////////////////////////////////////////////
if ($pairs->fase == 0) {

    if (has_capability('mod/pairs:criterios', $PAGE->context)) {
        
        $mform->display();

    }else{
        
        echo '<h3 class="text-center">' . get_string('setcriterios', 'mod_pairs') . '</h3>';
        
    }

    echo $OUTPUT->footer();

    return 0;
} else if ($pairs->fase == 1) {
    if (has_capability('mod/pairs:criterios', $PAGE->context)) {
        $url = new moodle_url('/mod/pairs/aspectos.php', array('cmid' => $cm->id));
        $url2 = new moodle_url('/mod/pairs/view.php', array('id' => $cm->id, 'fin' => '1'));
        echo '<div class="row mb-5 text-center">';
        echo '	<div class="col-6">';
        
        $no_deliverys = current($pairs->get_no_deliverys());
        if($no_deliverys->no_deliverys == '0'){
            echo '      <a class="btn btn-outline-info btn-sm" href="' . $url . '">' . get_string('setcriterios', 'mod_pairs') . '</a>';
        }

        echo '	</div>';
        echo '	<div class="col-6">';
        echo '      <a class="btn btn-outline-info btn-sm" href="' . $url2 . '">' . get_string('finpairs', 'mod_pairs') . '</a>';
        echo '	</div>';
        echo '</div>';
    }
    /////////////////////////////////////////////////////////////////////////////////////////////////
    //pantalla de confirmacion para pasar a la fase de evaluacion                                  //
    /////////////////////////////////////////////////////////////////////////////////////////////////
    if($fin){
        
        $moduleinstance->fase = 2;
        $DB->update_record('pairs', $moduleinstance, $bulk = false);
        $pairs->end_pairs();
        redirect(new moodle_url('/mod/pairs/view.php', array('id' => $cm->id)));

    }
    if ($confirm_env == 1) {

        $urlConfirm = new moodle_url('/mod/pairs/view.php', array('id' => $cm->id, 'confirm_env' => '2'));
        $urlCancel = new moodle_url('/mod/pairs/view.php', array('id' => $cm->id));
        echo '<div class="row mb-5">';
        echo '	<div class="col-8 offset-2 text-center border border-primary shadow p-3 bg-white rounded">';
        echo '      <h3 class="mt-3 mb-5">' . get_string('qevaluate_alum', 'mod_pairs') . '</h3>';
        echo '      <p class="mb-4">' . get_string('adver_evaluar_alumn', 'mod_pairs') . '</p>';
        echo '      <a class="btn btn-secondary" href="' . $urlCancel . '">' . get_string('cancelar', 'mod_pairs') . '</a>';
        echo '      <a class="btn btn-primary" href="' . $urlConfirm . '">' . get_string('confirmar', 'mod_pairs') . '</a>';
        echo '	</div>';
        echo '</div>';
    } else if ($confirm_env == 2) {
        /////////////////////////////////////////////////////////////////////////////////////////////////
        //se confirma el cambio de fase                                                                //
        /////////////////////////////////////////////////////////////////////////////////////////////////

        $envio->attachment_ready = '1';
        $DB->update_record('pairs_delivery', $envio);
        redirect(new moodle_url('/mod/pairs/view.php', array('id' => $cm->id)));
    } else if ($envio->attachment_ready == 0) {
        /////////////////////////////////////////////////////////////////////////////////////////////////
        //pantalla para mostrar informacion de subida de archivo                                       //
        /////////////////////////////////////////////////////////////////////////////////////////////////
        //si  no configuramos la vista para mostrar los attachments
        if(strlen($pairs->instruction_attachment) != 0){
            print_collapsible_region_start('', 'instrucciones-envio', get_string('param_inst', 'mod_pairs'));
            echo '<div class="row ml-2 mr-2 border border-top-0 border-primary shadow p-3 mb-5 bg-white rounded">';
            echo '	<div class="col-12">';
            echo "      <p>$pairs->instruction_attachment</p>";
            echo '	</div>';
            echo '</div>';
            print_collapsible_region_end();
        }

        print_collapsible_region_start('', 'envio', get_string('envio', 'mod_pairs'));

        //verificamos si el alumno ya tienen un envio o aun no
        if ($envio->id) {
            //traemos los attachments hechos
            $fs         = get_file_storage();
            //seleccionamos los de area pairs y el id del envio
            $files      = $fs->get_area_files($pairs->context->id, 'mod_pairs', 'submission_attachment', $envio->id);
            //traemos el ultimo registro
            $file       = end($files);
            //traemos el nombre y un mensaje de que su envio ha sido registrado con éxito
            //mostramos un boton para que el usuario pueda ver su envio
            $url = new moodle_url('/mod/pairs/envio.php', array('id' => $cm->id, 'env' => $envio->id));
            echo '<div class="row ml-2 mr-2 border border-top-0 border-primary shadow p-3 mb-5 bg-white rounded">';
            echo '	<div class="col-12">';
            echo '      <p>' . $file->get_filename() . ' ' . get_string('successenvio', 'mod_pairs') . '</p>';
            echo '      <p><a class="btn btn-outline-secondary btn-sm" href="' . $url . '">' . get_string('verenvio', 'mod_pairs') . '</a></p>';
            echo '	</div>';
            echo '</div>';
            print_collapsible_region_end();

            $url = new moodle_url('/mod/pairs/view.php', array('id' => $cm->id, 'confirm_env' => '1'));
            print_collapsible_region_start('', 'calificar', get_string('calif_ot_env', 'mod_pairs'));
            echo '<div class="row ml-2 mr-2 border border-top-0 border-primary shadow p-3 mb-5 bg-white rounded">';
            echo '	<div class="col-12">';
            echo '      <br>';
            echo '      <p>' . get_string('info_envio', 'mod_pairs') . '</p>';
            echo '      <p class="text-center"><a class="btn btn-primary btn-lg" href="' . $url . '">' . get_string('publicar', 'mod_pairs') . '</a></p>';
            echo '	</div>';
            echo '</div>';
            print_collapsible_region_end();
        } else {

            //mostramos un boton para que pueda añadir su envio
            $url = new moodle_url('/mod/pairs/envio.php', array('id' => $cm->id));

            //si no tiene envio configuramos la vista para desplegar un mensaje de que aun no tiene ningun envio
            echo '<div class="row ml-2 mr-2 border border-top-0 border-primary shadow p-3 mb-5 bg-white rounded">';
            echo '	<div class="col-12">';
            echo '      <p>' . get_string('noenvio', 'mod_pairs') . '</p>';
            echo '      <p><a class="btn btn-outline-primary" href="' . $url . '">' . get_string('addenvio', 'mod_pairs') . '</a></p>';
            echo '	</div>';
            echo '</div>';

            print_collapsible_region_end();
        }
    } else if ($envio->attachment_ready == 1) {
        /////////////////////////////////////////////////////////////////////////////////////////////////
        //pantalla de evaluacion de tareas                                                             //
        /////////////////////////////////////////////////////////////////////////////////////////////////

        $evaluacionesUser    = $pairs->get_complete_evaluations_by_userId($USER->id);
        $noEvaluaciones      = count($evaluacionesUser);
        $evaluacionPendiente = $pairs->get_pending_evaluation_by_userId($USER->id);
        $evaluacionPendiente = current($evaluacionPendiente);
        $envio               = $pairs->get_delivery_by_userId($USER->id);
        $envio               = end($envio);

        if ($envio->no_ratings == $pairs->no_revisions && $noEvaluaciones == $pairs->no_revisions) {

            $pairs->pairs_completed_by_user($envio);

            redirect($pairs->url_view());
        } else {

            //si  no configuramos la vista para mostrar las instrucciones de evaluacion
            print_collapsible_region_start('', 'instrucciones-evaluacion', get_string('instruc_evaluacion', 'mod_pairs'));
            echo '<div class="row ml-2 mr-2 border border-top-0 border-primary shadow p-3 mb-5 bg-white rounded">';
            echo '	<div class="col-12">';
            echo "      <p>$pairs->instruction_assessment</p>";
            echo '	</div>';
            echo '</div>';
            print_collapsible_region_end();

            $a = new stdclass();
            $a->noEvaluaciones = $noEvaluaciones;
            $a->no_revisions  = $pairs->no_revisions;

            print_collapsible_region_start('', 'evaluaciones-hechas', get_string('evaluate_done', 'mod_pairs'));
            echo '<div class="row ml-2 mr-2 border border-top-0 border-primary shadow p-3 mb-5 bg-white rounded">';
            echo '	<div class="col-12">';
            echo '      <p>' . get_string('evaluados', 'mod_pairs', $a) . '</p>';
            if ($noEvaluaciones != $pairs->no_revisions) {

                if ($evaluacionPendiente) {

                    $url = new moodle_url('/mod/pairs/evaluaciones.php', array('id' => $cm->id, 'trabajo' => $evaluacionPendiente->pairs_delivery_id));
                } else {

                    $url = new moodle_url('/mod/pairs/evaluaciones.php', array('id' => $cm->id));
                }

                echo '<p><a class="btn btn-outline-primary" href="' . $url . '">' . get_string('evaluarJob', 'mod_pairs') . '</a><p>';
            }
            echo '	</div>';
            echo '</div>';

            //Editar evaluaciones
            //if($noEvaluaciones != 0){
            //    echo '<ul>';
            //    $contador = 1;
            //    foreach($evaluacionesUser as $edit){
            //        echo '<li>';
            //        $url = new moodle_url('/mod/pairs/evaluaciones.php', array('id' => $cm->id,
            //            'trabajo' => $edit->pairs_delivery_id, 'edit' => '1'));
            //        echo '  <a href="'. $url.'">Editar evaluación numero '. $contador.'</a>';
            //        echo '</li>';
            //        $contador++;
            //    }
            //    echo '</ul>';
            //}

            print_collapsible_region_end();

            $a->no_ratings = $envio->no_ratings;

            print_collapsible_region_start('', 'rating-obtenidas', get_string('rating', 'mod_pairs'));
            echo '<div class="row ml-2 mr-2 border border-top-0 border-primary shadow p-3 mb-5 bg-white rounded">';
            echo '	<div class="col-12">';
            echo '      <p>' . get_string('info_calif', 'mod_pairs') . '</p>';
            echo '      <p>' . get_string('recibidas', 'mod_pairs', $a) . '</p>';
            echo '	</div>';
            echo '</div>';
            print_collapsible_region_end();
        }
    } else if ($envio->attachment_ready == 2) {
        echo '<div class="row">';
        echo '	<div class="col-12">';
        echo '      <h2 class="text-center">' . get_string('califinal', 'mod_pairs') . '</h2>';
        $rating = round($envio->rating, $pairs->no_decimals);
        echo "      <h4 class='text-center'>$rating</h4>";
        echo '	</div>';
        echo '</div>';

        if (strlen($pairs->retro_conclusion) != 0) {
            echo '<div class="row ml-2 mr-2 border border-top-0 border-primary shadow p-3 mb-5 mt-5 bg-white rounded">';
            echo '	<div class="col-12">';
            echo '      <h3 class="text-center">' . get_string('retro_con', 'mod_pairs') . '</h3>';
            echo "      <p class='text-center'>$pairs->retro_conclusion</p>";
            echo '	</div>';
            echo '</div>';
        }
    }

} else if($pairs->fase == 2){

    echo '<div class="row">';
    echo '	<div class="col-12">';
    echo '      <h2 class="text-center">' . get_string('califinal', 'mod_pairs') . '</h2>';
    $rating = round($envio->rating, $pairs->no_decimals);
    echo "      <h4 class='text-center'>$rating</h4>";
    echo '	</div>';
    echo '</div>';

    if (strlen($pairs->retro_conclusion) != 0) {
        echo '<div class="row ml-2 mr-2 border border-top-0 border-primary shadow p-3 mb-5 mt-5 bg-white rounded">';
        echo '	<div class="col-12">';
        echo '      <h3 class="text-center">' . get_string('retro_con', 'mod_pairs') . '</h3>';
        echo "      <p class='text-center'>$pairs->retro_conclusion</p>";
        echo '	</div>';
        echo '</div>';
    }

}

if (has_capability('mod/pairs:criterios', $PAGE->context)) {
    //nos trae en que modalidad de grupo estamos
    $groupmode = groups_get_activity_groupmode($pairs->cm);

    //0 si no hay grupos; 1 si hay grupos separados
    if ($groupmode) {

        //verificamos quien esta entrando a ver el reporte si un profesor o un admin
        if (has_capability('mod/pairs:viewReporAdmin', $PAGE->context)) {
            //Si es admin obtenemos todos los grupos del curso
            $g = groups_get_all_groups($course->id, 0, 0, $fields = 'g.*');

            $groupform = new groups_form(new moodle_url('/mod/pairs/view.php', array('id' => $cm->id)), array('groups' => $g, 'grupoSeleccionado' => $grupo));
        } else {

            $g = groups_get_user_groups($course->id, $USER->id);
            $g2 = current($g);
            $g = array();
            foreach ($g2 as $grup) {
                $objeto = new stdClass();
                $objeto->id = $grup;
                $objeto->name = groups_get_group_name($grup);
                array_push($g, $objeto);
            }

            if (count($g) == 0) {
                return false;
            }

            $groupform = new groups_form(new moodle_url('/mod/pairs/view.php', array('id' => $cm->id)), array('groups' => $g, 'grupoSeleccionado' => $grupo));
        }

        //si no se ha seleccionado ningun grupo se le mostrara el ultimo grupo
        if ($grupo) {
            $resource = $pairs->get_resourse_for_report($grupo);
        } else {
            $grupo = end($g);
            $resource = $pairs->get_resourse_for_report($grupo->id);
        }

        if ($groupform->is_cancelled()) {
        } else if ($fromform = $groupform->get_data()) {

            redirect(new moodle_url('/mod/pairs/view.php', array('id' => $cm->id, 'grupo' => $fromform->groups, 'noAlumnos' => $fromform->alumn)));
        }

        print_collapsible_region_start('', 'grupo', 'Grupos');
        echo '<div class="row ml-2 mr-2 border border-top-0 border-primary shadow p-3 mb-5 mt-5 bg-white rounded">';
        echo '	<div class="col-12">';
        $groupform->display();
        echo '  </div>';
        echo '</div>';
        print_collapsible_region_end();
    } else {

        $resource = $pairs->get_resourse_for_report($groupmode, $pairs->context->id);

    }

    print_collapsible_region_start('', 'reporte-alumnos', 'Reporte');
    echo '<div class="row ml-2 mr-2 border border-top-0 border-primary shadow p-3 mb-5 mt-5 bg-white rounded">';
    echo '	<div class="col-12">';
    echo '<table class="table">';
    echo '  <thead>';
    echo '    <tr>';
    echo '      <th scope="col">' . get_string('alumn', 'mod_pairs') . '</th>';
    echo '      <th scope="col">' . get_string('homework', 'mod_pairs') . '</th>';
    echo '      <th scope="col">' . get_string('points_r', 'mod_pairs') . '</th>';
    echo '      <th scope="col">' . get_string('points_o', 'mod_pairs') . '</th>';
    echo '      <th scope="col">' . get_string('rating', 'mod_pairs') . '</th>';
    echo '    </tr>';
    echo '  </thead>';
    echo '  <tbody class="text-center">';
    foreach ($resource as $alumno) {

        echo '    <tr>';
        echo "      <th scope='row'>$alumno->firstname $alumno->lastname</th>";

        if ($alumno->title) {

            echo "      <td>$alumno->title</td>";

            if ($alumno->rating > 0) {

                if ($alumno->no_ratings) {
                    $ratinges_recibidas = $pairs->get_evaluations_by_deliveryId($alumno->deliveryid);
                    echo "      <td>";

                    foreach ($ratinges_recibidas as $rating) {

                        if($pairs->fase != 2){
                            
                            $url = new moodle_url('/mod/pairs/reporte.php', array(
                                'id' => $cm->id, 'puntosRecibidos' => 1, 'evaluacion' => $rating->id,
                                'evaluador' => $rating->evaluador_id, 'alumno' => $alumno->idalumno, 'trabajo' => $alumno->deliveryid, 'profesor' => $rating->edit_user_id
                            ));
                            
                            echo '      <p><a class="btn btn-outline-primary btn-lg" href="' . $url . '">' . round($rating->rating, $pairs->no_decimals) . '</a></p>';

                        }else{
                            echo '      <p>' . round($rating->rating, $pairs->no_decimals) . '</p>';
                        }
                    }

                    echo "      </td>";
                } else {

                    echo '      <td>' . get_string('no_points', 'mod_pairs') . '</td>';
                }

                $evaluacionesHechas = $pairs->get_complete_evaluations_by_report($alumno->autor);
                $noEvaluacionesHechas = count($evaluacionesHechas);
                if ($noEvaluacionesHechas) {

                    echo "      <td>";

                    foreach ($evaluacionesHechas as $rating) {
                        
                        if($pairs->fase != 2){
                            $envio = $pairs->get_delivery_by_id($rating->pairs_delivery_id);

                            $url = new moodle_url('/mod/pairs/reporte.php', array(
                                'id' => $cm->id, 'puntosDados' => 1, 'evaluacion' => $rating->id,
                                'evaluador' => $rating->evaluador_id, 'alumno' => $envio->autor_id, 'trabajo' => $rating->pairs_delivery_id, 'profesor' => $rating->edit_user_id
                            ));

                            echo '      <p><a class="btn btn-outline-primary btn-lg" href="' . $url . '">' . round($rating->rating, $pairs->no_decimals) . '</a></p>';
                        }else{
                            echo '      <p>' . round($rating->rating, $pairs->no_decimals) . '</p>';
                        }
                    }

                    echo "      </td>";
                } else {
                    echo "      <td>$noEvaluacionesHechas</td>";
                }

                echo '      <td>' . round($alumno->rating, $pairs->no_decimals) . '</td>';
            } else {

                echo '      <td>' . get_string('no_points', 'mod_pairs') . '</td>';
                echo '      <td>' . get_string('no_points', 'mod_pairs') . '</td>';
                echo '      <td>' . get_string('no_rating', 'mod_pairs') . '</td>';
            }
        } else {
            echo '      <td>' . get_string('no_delivery', 'mod_pairs') . '</td>';
            echo '      <td>' . get_string('no_points', 'mod_pairs') . '</td>';
            echo '      <td>' . get_string('no_points', 'mod_pairs') . '</td>';
            echo '      <td>' . get_string('no_rating', 'mod_pairs') . '</td>';
        }
        echo '    </tr>';
    }
    echo ' </tbody>';
    echo '</table>';
    echo '	</div>';
    echo '</div>';
}

echo $OUTPUT->footer();