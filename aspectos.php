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
require_once('localview/aspectos_form.php');
require_once('locallib.php');

defined('MOODLE_INTERNAL') || die();
global $DB;

// Course_module ID, or
$cmid = required_param('cmid', PARAM_INT);

// ... module instance id.
$e  = optional_param('e', 0, PARAM_INT);

//nomero de aspectos
$noAspectos = optional_param('no', 0, PARAM_INT);

if ($cmid) {
    $cm             = get_coursemodule_from_id('taller', $cmid, 0, false, MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('taller', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($e) {
    $moduleinstance = $DB->get_record('taller', array('id' => $n), '*', MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm             = get_coursemodule_from_instance('taller', $moduleinstance->id, $course->id, false, MUST_EXIST);
}

$taller =  new taller($moduleinstance, $cm, $course);

require_login($course, false, $cm);

$modulecontext = context_module::instance($cmid);
$PAGE->set_url(new moodle_url('/mod/taller/aspectos.php', array('cmid' => $cm->id)));

$PAGE->set_title(get_string('pluginname', 'mod_taller'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

$data = $DB->get_records_sql("SELECT * FROM {taller_criterio} WHERE taller_id = $taller->id;");


if($noAspectos){

    $noAspectos += 2; 

}else{

    $noAspectos = count($data);

}

$mform = new aspectos_form(new moodle_url('/mod/taller/aspectos.php', array('cmid' => $cm->id,'no' => $noAspectos)), $noAspectos);

if ($mform->is_cancelled()) {
    
    redirect(new moodle_url('/mod/taller/view.php', array('id' => $cm->id)));

} else if ($fromform = $mform->get_data()) {
    $criterio       = new stdClass();
    $opcionCriterio = new stdClass();

    for($i = 1; $i <= count($data); $i++){
        $des                          = "descripcion$i";
        $descripcionid                = "descripcionid$i";
        $descripcion                  = $fromform->$des;
        $criterio->id                 = $fromform->$descripcionid;
        $criterio->criterio           = $descripcion['text'];
        $criterio->criterioformat     = $descripcion['format'];
        $criterio->taller_id = $taller->id; 
        $DB->update_record('taller_criterio', $criterio, $bulk=false);
        
        for($j = 1; $j <= 4; $j++){
            $definicion                              = "calif_def$i$j";
            $calificacion                            = "calif_envio$i$j";
            $opcionid                                = "opcionid$i$j";
            
            if(strlen($fromform->$opcionid) != 0){
            
                $opcionCriterio->id                      = $fromform->$opcionid;
                $opcionCriterio->definicion              = $fromform->$definicion;
                $opcionCriterio->calificacion            = $fromform->$calificacion;
                $opcionCriterio->taller_criterio_id = $criterio->id;
                $DB->update_record('taller_opcion_cri', $opcionCriterio, $bulk=false);
            
            }else{
            
                if(strlen($fromform->$definicion) != 0){
                    
                    $opcionCriterio->definicion              = $fromform->$definicion;
                    $opcionCriterio->calificacion            = $fromform->$calificacion;
                    $opcionCriterio->taller_criterio_id = $criterio->id;

                    $DB->insert_record('taller_opcion_cri', $opcionCriterio);

                }

            }
        }
    }
    
    if($noAspectos-2 != count($data)){
    
        for($i = count($data)+1; $i <= $noAspectos-2; $i++){

            $des         = "descripcion$i";
            $descripcion = $fromform->$des;
            
            if(strlen($descripcion['text']) != 0){
                
                $criterio->criterio           = $descripcion['text'];
                $criterio->criterioformat     = $descripcion['format'];
                $criterio->taller_id = $taller->id;
                $idCriterio                   = $DB->insert_record('taller_criterio', $criterio);
                
                for($j = 1; $j <= 4; $j++){

                    $definicion = "calif_def$i$j";
                    
                    if(strlen($fromform->$definicion) != 0){
                        $calificacion                            = "calif_envio$i$j";
                        $opcionCriterio->definicion              = $fromform->$definicion;
                        $opcionCriterio->calificacion            = $fromform->$calificacion;
                        $opcionCriterio->taller_criterio_id = $idCriterio;

                        $DB->insert_record('taller_opcion_cri', $opcionCriterio);

                    }
    
                }

            }

        }

    }
    
    redirect(new moodle_url('/mod/taller/view.php', array('id' => $cm->id)),"Actualizacion exitosa");

}else{
    $i = 1;
    $cali = "";
    foreach ($data as $criterio) {
        $descripcion                    = "descripcion$i";
        $descripcionid                  = "descripcionid$i";
        $toform->$descripcionid         = $criterio->id; 
        $toform->$descripcion['text']   = $criterio->criterio;
        $toform->$descripcion['format'] = $criterio->criterioformat;
        
        $data2 = $DB->get_records_sql("SELECT * FROM {taller_opcion_cri} WHERE taller_criterio_id = $criterio->id;");
        $j     = 1;
        
        foreach($data2 as $opcion){
            $cali = "calif_envio$i$j";
            $def = "calif_def$i$j";
            $opcionid = "opcionid$i$j";
            $toform->$opcionid = $opcion->id;
            $toform->$cali = (int)$opcion->calificacion;
            $toform->$def = $opcion->definicion;
            $j++;
        }
        $j=1;
        $i++;
    }
    $mform->set_data($toform);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($moduleinstance->name));

$mform->display();

echo $OUTPUT->footer();