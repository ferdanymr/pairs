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
 * Prints an instance of mod_taller.
 *
 * @package     mod_taller
 * @copyright   2021 Fernando Munoz <fernando_munoz@cuaieed.unam.mx>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once('locallib.php');
require_once('localview/aspectos_form.php');

// Id del curso
$id = optional_param('id', 0, PARAM_INT);

//Si hay un envio aqui capturaremos su id
$envio->id = optional_param('env', 0, PARAM_INT);

// ... module instance id.
$e  = optional_param('e', 0, PARAM_INT);

//contador del numero de aspectos
$noAspectos = optional_param('no', 0, PARAM_INT);

$confirm_env = optional_param('confirm_env', 0, PARAM_INT);

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

$taller = new taller($moduleinstance, $cm, $course);

/////////////////////////////////////////////////////////////////////////////////////////////////
//bloque para configurar la vista de criterios en dado caso de que la fase sea 0               //
/////////////////////////////////////////////////////////////////////////////////////////////////
if($taller->fase == 0){

    //validamos si por get se mando el numero de aspectos al cual le agregaremos 2 más
    //si no se tienen se asignaran por defecto 2
    if($noAspectos){

        $noAspectos += 2; 
    
    }else{
    
        $noAspectos = taller::NO_ASPECTOS;
    
    }

    //se define el formulario con la url a la que mandara los datos a la hora de hacer submit
    //los parametros enviados son el id del curso y el numero de aspectos actual
    // al formulario por aparte le mandamos tambien el numero de aspectos
    $mform = new aspectos_form(new moodle_url('/mod/taller/view.php', array('id' => $cm->id,'no' => $noAspectos)), $noAspectos);

    if ($mform->is_cancelled()) {
        //Si se cancela el formulario se regrasara a la pantalla principal del curso
        redirect(new moodle_url('/course/view.php', array('id'=>$course->id)));

    }else if ($fromform = $mform->get_data()) {
        //si se hace submit se preparan los datos para insertarlos en la Base de Datos        
        $taller->add_criterios($fromform, $noAspectos);
        
        $moduleinstance->fase = 1;
        $DB->update_record('taller', $moduleinstance, $bulk=false);

        redirect(new moodle_url('/mod/taller/view.php', array('id' => $cm->id)));
    
    }

/////////////////////////////////////////////////////////////////////////////////////////////////
//bloque para configurar la vista de criterios en dado caso de que la fase sea 1               //
/////////////////////////////////////////////////////////////////////////////////////////////////
}else{
    //primero verificamos si el usuario ya hizo un envio o no para modificar la vista de acuerdo a su envio
    if(!$envio->id){
        $data = $taller->get_envio_by_userId($USER->id);
        $envio = end($data);
        if (empty($envio->id)) {
            $envio = new stdClass;
            $envio->id = null;
        }
    }
}

//seteamos la url de la pagina
$PAGE->set_url($taller->url_vista());
//seteamos el titulo de la pagina
$PAGE->set_title(get_string('pluginname', 'mod_taller'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($taller->context);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($course->name));

/////////////////////////////////////////////////////////////////////////////////////////////////
//Si la fase es 0 siginifa configuracion entonces mostramos el formulario                      //
/////////////////////////////////////////////////////////////////////////////////////////////////
if($taller->fase == 0){ 
    
    $mform->display();

}else{
    /////////////////////////////////////////////////////////////////////////////////////////////////
    //pantalla de confirmacion para pasar a la fase de evaluacion                                  //
    /////////////////////////////////////////////////////////////////////////////////////////////////
    if($confirm_env == 1){

        $urlConfirm = new moodle_url('/mod/taller/view.php', array('id' => $cm->id, 'confirm_env' => '2'));
        $urlCancel = new moodle_url('/mod/taller/view.php', array('id' => $cm->id));
        echo '<div class="row">';
        echo '	<div class="col-10 offset-1 text-center">';
        echo '      <h3>'. get_string('qevaluate_alum','mod_taller'). '</h3>';
        echo '      <p>'. get_string('adver_evaluar_alumn','mod_taller'). '</p>';
        echo '      <a class="btn btn-secondary" href="'. $urlCancel.'">'. 'Cancelar' .'</a>';
        echo '      <a class="btn btn-primary" href="'. $urlConfirm.'">'. 'Confirmar' .'</a>';
        echo '	</div>';
        echo '</div>';

    }else if($confirm_env == 2){
        /////////////////////////////////////////////////////////////////////////////////////////////////
        //se confirma el cambio de fase                                                                //
        /////////////////////////////////////////////////////////////////////////////////////////////////

        $envio->envio_listo = '1';
        $DB->update_record('taller_entrega', $envio);
        redirect(new moodle_url('/mod/taller/view.php', array('id' => $cm->id)));

    }else if($envio->envio_listo == 0){
        /////////////////////////////////////////////////////////////////////////////////////////////////
        //pantalla para mostrar informacion de subida de archivo                                       //
        /////////////////////////////////////////////////////////////////////////////////////////////////
        //si  no configuramos la vista para mostrar los envios
        print_collapsible_region_start('','instrucciones-envio',get_string('param_inst','mod_taller'));
        echo '<div class="row">';
        echo '	<div class="col-12">';
        echo "      <p>$taller->instruccion_envio</p>";
        echo '	</div>';
        echo '</div>';
        print_collapsible_region_end();
        
        print_collapsible_region_start('','envio',get_string('envio','mod_taller'));

        //verificamos si el alumno ya tienen un envio o aun no
        if($envio->id){
            //traemos los envios hechos
            $fs         = get_file_storage();
            //seleccionamos los de area taller y el id del envio
            $files      = $fs->get_area_files($taller->context->id, 'mod_taller', 'submission_attachment', $envio->id);
            //traemos el ultimo registro
            $file       = end($files);
            //traemos el nombre y un mensaje de que su envio ha sido registrado con exito
            echo '<div class="row">';
            echo '	<div class="col-12">';
            echo '      <br>';
            echo '      <p>'. $file->get_filename() .' '. get_string('successenvio','mod_taller').'</p>';
            echo '	</div>';
            echo '</div>';
            //mostramos un boton para que el usuario pueda ver su envio
            $url = new moodle_url('/mod/taller/envio.php', array('id' => $cm->id, 'env' => $envio->id));
            echo '<a class="btn btn-primary" href="'. $url.'">'. get_string('verenvio','mod_taller').'</a>';
            print_collapsible_region_end();
            echo '<br>';

            print_collapsible_region_start('','calificar', get_string('calif_ot_env','mod_taller'));
            echo '<div class="row">';
            echo '	<div class="col-12">';
            echo '      <br>';
            echo '      <p>'. get_string('info_envio','mod_taller') .'</p>';
            echo '	</div>';
            echo '</div>';

            $url = new moodle_url('/mod/taller/view.php', array('id' => $cm->id, 'confirm_env' => '1'));
            echo '<a class="btn btn-primary" href="'. $url.'">'. 'Evaluar trabajos' .'</a>';
            print_collapsible_region_end();

        }else{

            //si no tiene envio configuramos la vista para desplegar un mensaje de que aun no tiene ningun envio
            echo '<div class="row">';
            echo '	<div class="col-12">';
            echo '      <p>'.get_string('noenvio','mod_taller').'</p>';
            echo '	</div>';
            echo '</div>';
            //mostramos un boton para que pueda añadir su envio
            $url = new moodle_url('/mod/taller/envio.php', array('id' => $cm->id));
            echo '<a class="btn btn-primary" href="'. $url.'">'.get_string('addenvio','mod_taller').'</a>';
            print_collapsible_region_end();

        }

    }else if($envio->envio_listo == 1){
        /////////////////////////////////////////////////////////////////////////////////////////////////
        //pantalla de evaluacion de tareas                                                             //
        /////////////////////////////////////////////////////////////////////////////////////////////////
        
        $evaluacionesUser    = $taller->get_evaluaciones_completas_by_userId($USER->id);
        $noEvaluaciones      = count($evaluacionesUser);
        $evaluacionPendiente = $taller->get_evaluacion_pendiente_by_userId($USER->id);
        $evaluacionPendiente = current($evaluacionPendiente);
        $envio               = $taller->get_envio_by_userId($USER->id);
        $envio               = end($envio);

        if($envio->no_calificaciones == $taller->no_revisiones && $noEvaluaciones == $taller->no_revisiones){
            
            $taller->asignar_calif_final($envio);
            redirect($taller->url_vista());

        }else{

            //si  no configuramos la vista para mostrar las instrucciones de evaluacion
            print_collapsible_region_start('','instrucciones-evaluacion', get_string('instruc_evaluacion','mod_taller'));
            echo '<div class="row">';
            echo '	<div class="col-12">';
            echo "      <p>$taller->instruccion_valoracion</p>";
            echo '	</div>';
            echo '</div>';
            print_collapsible_region_end();

            print_collapsible_region_start('','evaluaciones-hechas',get_string('evaluate_done','mod_taller'));
            echo '<div class="row">';
            echo '	<div class="col-12">';
            echo "      <p>Trabajos evaluados: $noEvaluaciones de $taller->no_revisiones</p>";
            echo '	</div>';
            echo '</div>';

            if($noEvaluaciones != 0){
                echo '<ul>';
                $contador = 1;
                foreach($evaluacionesUser as $edit){
                    echo '<li>';
                    $url = new moodle_url('/mod/taller/evaluaciones.php', array('id' => $cm->id,
                        'trabajo' => $edit->taller_entrega_id, 'edit' => '1'));
                    echo '  <a href="'. $url.'">Editar evaluación numero '. $contador.'</a>';
                    echo '</li>';
                    $contador++;
                }
                echo '</ul>';
            }

            if($noEvaluaciones != $taller->no_revisiones){

                if($evaluacionPendiente){

                    $url = new moodle_url('/mod/taller/evaluaciones.php', array('id' => $cm->id, 'trabajo' => $evaluacionPendiente->taller_entrega_id));
                
                }else{
                
                    $url = new moodle_url('/mod/taller/evaluaciones.php', array('id' => $cm->id));
                
                }

                echo '<a class="btn btn-primary" href="'. $url.'">'. get_string('evaluarJob','mod_taller') .'</a>';

            }

            print_collapsible_region_end();
            echo  '<br>';

            print_collapsible_region_start('','calificacion-obtenidas', get_string('calificacion','mod_taller'));
            echo '<div class="row">';
            echo '	<div class="col-12">';

            if($envio->no_calificaciones == $taller->no_revisiones && $noEvaluaciones == $taller->no_revisiones){
            
                echo '      <p>'. get_string('calif_final','mod_taller') .'</p>';
                echo "      <p>$envio->calificacion</p>";
            
            }else{
            
                echo '      <p>'. get_string('info_calif','mod_taller') .'</p>';
                echo "      <p>Evaluaciones recibidas $envio->no_calificaciones de $taller->no_revisiones</p>";
            
            }

            echo '	</div>';
            echo '</div>';
            print_collapsible_region_end();

        }
    } else if ($envio->envio_listo == 2) {
        echo '<h2 class="text-center">Tu calificacion final es</h2>';
        $calificacion = round($envio->calificacion,$taller->no_decimales);
        echo "<h4 class='text-center'>$calificacion</h4>";
    }

    echo '<br>';
    $url = new moodle_url('/mod/taller/aspectos.php', array('cmid' => $cm->id));
    echo '<a class="btn btn-primary" href="'. $url.'">'.get_string('setcriterios','mod_taller').'</a>';
}

echo $OUTPUT->footer();