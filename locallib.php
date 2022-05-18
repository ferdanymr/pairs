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
 * Library of internal classes and functions for module pairs
 *
 * All the pairs specific functions, needed to implement the module
 * logic, should go to here. Instead of having bunch of function named
 * pairs_something() taking the pairs instance as the first
 * parameter, we use a class pairs that provides all methods.
 *
 * @package     mod_pairs
 * @copyright   2021 Fernando Munoz <fernando_munoz@cuaieed.unam.mx>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(__DIR__.'/lib.php');     // we extend this library here
require_once($CFG->libdir . '/gradelib.php');   // we use some rounding and comparing routines here
require_once($CFG->libdir . '/filelib.php');

class pairs{

    const NO_ASPECTOS = 4;

    public $dbrecord;

    public $fase;

    public $cm;

    public $course;

    public $id;

    public $name;

    public $intro;

    public $introformat;

    public $attachment;

    public $calif_aprobatoria;

    public $assessment;

    public $calif_aprov_valoracion;

    public $no_decimals;

    public $no_attachments;

    public $type_attachments = null;

    public $max_size;

    public $instruction_attachment;

    public $instruction_attachmentformat;

    public $instruction_assessment;

    public $instruction_assessmentformat;

    public $no_revisions;

    public $retro_conclusion;

    public $retro_conclusionformat;

    public $context;

    public $max_points_rubric;
    
    public function __construct(stdclass $dbrecord, $cm, $course, stdclass $context=null) {
        $this->dbrecord = $dbrecord;
        foreach ($this->dbrecord as $field => $value) {
            if (property_exists('pairs', $field)) {
                
                $this->{$field} = $value;

            }
        }

        if (is_null($cm) || is_null($course)) {
            throw new coding_exception('Must specify $cm and $course');
        }
        $this->course = $course;
        if ($cm instanceof cm_info) {
            $this->cm = $cm;
        } else {
            $modinfo = get_fast_modinfo($course);
            $this->cm = $modinfo->get_cm($cm->id);
        }
        if (is_null($context)) {
            $this->context = context_module::instance($this->cm->id);
        } else {
            $this->context = $context;
        }
    }

    
    public function url_view() {
        global $CFG;
        return new moodle_url('/mod/pairs/view.php', array('id' => $this->cm->id));
    }

    public function get_info_user($userId){
        global $DB;
        return $DB->get_records_sql("SELECT * FROM {user} 
                                        WHERE id = $userId;");

    }

    public function get_delivery_by_userId($userId){
        global $DB;
        return $DB->get_records_sql("SELECT * FROM {pairs_delivery} 
                                        WHERE pairs_id = $this->id 
                                        AND autor_id = $userId;");
    }

    public function get_deliverys(){
        global $DB;
        return $DB->get_records_sql("SELECT * FROM {pairs_delivery} 
                                        WHERE pairs_id = $this->id;");
    }

    public function get_no_deliverys(){
        global $DB;
        return $DB->get_records_sql("SELECT COUNT(id) AS no_deliverys FROM {pairs_delivery} 
                                        WHERE pairs_id = $this->id;");
    }

    public function get_complete_evaluations_by_userId($userId){
        global $DB;
        return $DB->get_records_sql("SELECT pairs_delivery_id 
                                        FROM {pairs_evaluacion_user} 
                                        WHERE pairs_id = $this->id 
                                        AND evaluador_id = $userId 
                                        AND is_evaluado = 1;");
    }

    public function get_complete_evaluations_by_report($userId){
        global $DB;
        return $DB->get_records_sql("SELECT * FROM {pairs_evaluacion_user} 
                                        WHERE pairs_id = $this->id 
                                        AND evaluador_id = $userId 
                                        AND is_evaluado = 1;");
    }
    
    public function get_complete_evaluation_by_deliveryId($deliveryId, $userId){
        global $DB;
        return $DB->get_records_sql("SELECT * FROM {pairs_evaluacion_user} 
                                        WHERE pairs_id = $this->id 
                                        AND evaluador_id = $userId 
                                        AND is_evaluado = 1 
                                        AND pairs_delivery_id = $deliveryId;");
    }

    public function get_evaluation_by_id($id){
        global $DB;
        return $DB->get_records_sql("SELECT * FROM {pairs_evaluacion_user} WHERE id = $id;");
    }

    public function get_pending_evaluation_by_userId($userId){
        global $DB;
        return $DB->get_records_sql("SELECT * FROM {pairs_evaluacion_user} 
                                        WHERE pairs_id = $this->id 
                                        AND evaluador_id = $userId 
                                        AND is_evaluado = 0;");
    }

    public function get_evaluation_answers($evaluacionId){
        global $DB;
        return $DB->get_records_sql("SELECT * FROM {pairs_answer_rubric} 
                                        WHERE pairs_evaluacion_user_id = $evaluacionId 
                                        ORDER BY id 
                                        ASC;");
    }
    
    public function get_delivery_for_evaluate($userId, $evaluacionesCompletas, $groupid){
        global $DB;
        if($groupid){

            $sql = "SELECT delivery.id, delivery.title, delivery.comment,
                        delivery.attachments, delivery.attachment_ready, delivery.rating,
                        delivery.no_ratings, delivery.pairs_id, delivery.autor_id
                        FROM {pairs_delivery} AS delivery
                        JOIN {groups_members} AS members
                        ON members.userid = delivery.autor_id
                        WHERE members.groupid = $groupid
                        AND delivery.pairs_id = $this->id 
                        AND delivery.attachment_ready = 1 
                        AND delivery.autor_id != $userId 
                        AND delivery.id NOT IN ($evaluacionesCompletas) 
                        ORDER BY delivery.no_ratings 
                        DESC LIMIT 1;";

        }else{

            $sql = "SELECT * FROM {pairs_delivery} 
                        WHERE pairs_id = $this->id 
                        AND attachment_ready = 1 
                        AND autor_id != $userId 
                        AND id NOT IN ($evaluacionesCompletas) 
                        ORDER BY no_ratings 
                        DESC LIMIT 1;";

        }

        return $DB->get_records_sql($sql);
    }

    public function get_delivery_by_id($id){
        global $DB;
        return $DB->get_record_sql("SELECT * FROM {pairs_delivery} 
                                        WHERE pairs_id = $this->id 
                                        AND id = $id;");
    }

    public function get_criterios(){
        global $DB;
        return  $DB->get_records_sql("SELECT * FROM {pairs_criterio} 
                                        WHERE pairs_id = $this->id;");
    }

    public function get_opciones_criterio($criterioId){
        global $DB;
        return $DB->get_records_sql("SELECT * FROM {pairs_opcion_cri} 
                                        WHERE pairs_criterio_id = $criterioId;");
    }

    public function update_evaluacion($evaluacion){
        global $DB;
        $DB->update_record('pairs_evaluacion_user', $evaluacion);
    }

    public function update_delivery($delivery){
        global $DB;
        $DB->update_record('pairs_delivery', $delivery);
    }

    public function get_evaluations_by_deliveryId($envioId){
        global $DB;
        return $DB->get_records_sql("SELECT * FROM {pairs_evaluacion_user} 
                                        WHERE pairs_id = $this->id 
                                        AND pairs_delivery_id = $envioId;");
    }

    public function get_resourse_for_report($groupid = 0, $contextid=0){
        global $DB;
        if($groupid){
            
            $sql = "SELECT user.id AS idalumno, user.firstname, user.lastname, 
                        delivery.id AS deliveryid, delivery.title,
                        delivery.rating, delivery.no_ratings,
                        delivery.autor_id AS autor
                        FROM {groups_members} AS members 
                        JOIN {user} AS user ON members.userid = user.id
                        LEFT JOIN {pairs_delivery} AS delivery ON user.id = delivery.autor_id AND delivery.pairs_id = $this->id
                        WHERE members.groupid = $groupid;";

        }else{
            $sql = "SELECT user.id AS idalumno, user.firstname, user.lastname, 
            delivery.id AS deliveryid, delivery.title,
            delivery.rating, delivery.no_ratings,
            delivery.autor_id AS autor
            FROM {user} AS user
            INNER JOIN {groups_members} AS members ON members.userid = user.id 
            INNER JOIN {role_assignments} AS role_assig ON role_assig.userid = user.id 
            INNER JOIN mdl_role ON mdl_role.id = role_assig.roleid
            LEFT JOIN {pairs_delivery} AS delivery ON user.id = delivery.autor_id AND delivery.pairs_id = $this->id;";

        }
        return $DB->get_records_sql($sql);
    }

    public function set_total_points_rubric(){
        global $DB;
        $criterios = $this->get_criterios();
        $puntos = 0;
        
        foreach($criterios as $criterio){
            $p = $DB->get_records_sql("SELECT * FROM {pairs_opcion_cri} 
                                        WHERE pairs_criterio_id = $criterio->id 
                                        ORDER BY rating 
                                        DESC 
                                        LIMIT 1;");
            $p = current($p);
            $puntos +=  $p->rating;
        }

        $this->dbrecord->max_points_rubric = $puntos;
        $DB->update_record('pairs', $this->dbrecord, $bulk=false);
    }

    public function assign_rating_by_valoracion($userId){
        $envio = $this->get_delivery_by_userId($userId);
        $envio = current($envio);
        $this->assign_rating($envio);
    }

    public function assign_rating($envio){
        global $DB;
        //las evaluaciones que ha recibido el trabajo junto con su rating
        $evaluaciones = $this->get_evaluations_by_deliveryId($envio->id);
        $rating = 0;
        if(!empty($evaluaciones)){
        
            foreach ($evaluaciones as $evaluacion) {

                $rating += $evaluacion->rating;

            }

            //la sumatoria de las evaluaciones sobre el numero de evaluaciones
            $rating = $rating /  count($evaluaciones);

            $rating = $rating * $this->attachment; 

            //rating total del envio
            $rating = $rating / $this->max_points_rubric;
        }
        
        //rating del envio más la rating de valoracion
        $valoraciones = $this->get_complete_evaluations_by_userId($envio->autor_id);
        $valoraciones = count($valoraciones);

        if($valoraciones > 0){
            
            $valorUnaEvaluacion = $this->assessment / $this->no_revisions;
            
            $ratingValoracion = $valoraciones * $valorUnaEvaluacion;

            $rating += $ratingValoracion;

        }

        $rating = round($rating, $this->no_decimals);
        
        $envio->rating = $rating;

        $DB->update_record('pairs_delivery', $envio);

        //$this->send_rating_gradebook($envio->autor_id);
    }

    public function end_pairs(){

        $this->send_rating_gradebook();
        
    }

    public function pairs_completed_by_user($envio){
        global $DB;

        $envio->attachment_ready = 2;
        $DB->update_record('pairs_delivery', $envio);

    }

    public function edit_opciones_criterio($opciones, $dataform, $evaluacion, $envio){
        global $DB;
        $rating = 0;
        if($opciones){
            $contador = 0;
            foreach ($dataform as $opcion) {
                
                $opcion = explode("-", $opcion);
                $rating += $opcion[1];

                if(strlen($opcion[0]) < 4){
                    $data->id                        = $opciones[$contador]->id;
                    $data->pairs_opcion_cri_id      = $opcion[0];
                    $data->pairs_evaluacion_user_id = $evaluacion->id;
                    $DB->update_record('pairs_answer_rubric', $data);
                }
                $contador++;
            }

        }else{
            
            foreach ($dataform as $opcion) {
                
                $opcion = explode("-", $opcion);
                $rating += $opcion[1];
                
                if(strlen($opcion[0]) < 4){
                    $data->pairs_opcion_cri_id      = $opcion[0];
                    $data->pairs_evaluacion_user_id = $evaluacion->id;
                    $DB->insert_record('pairs_answer_rubric', $data);
                }
                
            }

            $envio->no_ratings = $envio->no_ratings + 1;
            $this->update_delivery($envio);
            $evaluacion->is_evaluado = '1';
        }

        $evaluacion->rating = $rating;
        $this->update_evaluacion($evaluacion);
        
        $this->assign_rating($envio);
    }

    public function edit_delivery($data){
        global $USER, $DB;

        $data->attachments             = '0';
        $data->attachment_ready        = '0';
        $data->rating       = '0';
        $data->no_ratings  = '0';
        $data->pairs_id = $this->id;
        $data->autor_id           = $USER->id;
        
        if(is_null($data->id)){

            $data->id = $DB->insert_record('pairs_delivery', $data);

        }
        
        $data = file_postupdate_standard_filemanager($data, 'attachment', $this->filemanager_options(),
            $this->context, 'mod_pairs', 'submission_attachment', $data->id);
        
        if (empty($data->attachment)) {
                // Explicit cast to zero integer.
                $data->attachment = 0;
                $data->attachments     = '0';
        }else{
            $data->attachments = $data->attachment;
        }

        $DB->update_record('pairs_delivery', $data);
            
    }
    /**
     * Return the editor options for the submission content field.
     *
     * @return array
     */
    public function filemanager_options() {
        global $CFG;
        require_once($CFG->dirroot.'/repository/lib.php');

        return array(
            'subdirs' => 0,
            'maxbytes' => $this->max_size,
            'maxfiles' => $this->no_attachments,
            'accepted_types'=> $this->type_attachments,
            'return_types' => FILE_INTERNAL | FILE_EXTERNAL,
        );
    }

    public function editar_opciones_criterio($fromform, $idCriterio, $i){
        global $DB;
        $opcionCriterio = new stdClass();
        
        for($j = 1; $j <= 4; $j++){
            $definicion                              = "calif_def$i$j";
            $rating                            = "attachment$i$j";
            $opcionid                                = "opcionid$i$j";

            if(strlen($fromform->$opcionid) != 0){
            
                $opcionCriterio->id                 = $fromform->$opcionid;
                $opcionCriterio->definicion         = $fromform->$definicion;
                $opcionCriterio->rating       = $fromform->$rating;
                $opcionCriterio->pairs_criterio_id = $idCriterio;
                $DB->update_record('pairs_opcion_cri', $opcionCriterio, $bulk=false);
            
            }else{
            
                if(strlen($fromform->$definicion) != 0){

                    $opcionCriterio->definicion         = $fromform->$definicion;
                    $opcionCriterio->rating       = $fromform->$rating;
                    $opcionCriterio->pairs_criterio_id = $idCriterio;

                    $DB->insert_record('pairs_opcion_cri', $opcionCriterio);

                }

            }
        }
        
        $this->set_total_points_rubric();

    }

    public function add_opciones_criterio($fromform, $idCriterio, $i){
        global $DB;
        $opcionCriterio = new stdClass();
        
        for($j = 1; $j <= 4; $j++){

            $definicion = "calif_def$i$j";
            
            if(strlen($fromform->$definicion) != 0){
                $rating                       = "attachment$i$j";
                $opcionCriterio->definicion         = $fromform->$definicion;
                $opcionCriterio->rating       = $fromform->$rating;
                $opcionCriterio->pairs_criterio_id = $idCriterio;

                $DB->insert_record('pairs_opcion_cri', $opcionCriterio);

            }
        
        }

        $this->set_total_points_rubric();

    }

    public function add_criterios($fromform, $noAspectos){
        global $DB;
        $criterio       = new stdClass();
    
        for($i = 1; $i <= $noAspectos-2; $i++){

            $des         = "descripcion$i";
            $descripcion = $fromform->$des;
            
            if(strlen($descripcion['text']) != 0){
                
                $criterio->criterio           = $descripcion['text'];
                $criterio->criterioformat     = $descripcion['format'];
                $criterio->pairs_id = $this->id;
                $idCriterio                   = $DB->insert_record('pairs_criterio', $criterio);

                $this->add_opciones_criterio($fromform, $idCriterio, $i);
            }
        }

    }

    

    public function edit_criterios($fromform, $noAspectos, $data){
        global $DB;
        $criterio       = new stdClass();

        for($i = 1; $i <= count($data); $i++){
            $des                          = "descripcion$i";
            $descripcionid                = "descripcionid$i";
            $descripcion                  = $fromform->$des;
            $criterio->id                 = $fromform->$descripcionid;
            $criterio->criterio           = $descripcion['text'];
            $criterio->criterioformat     = $descripcion['format'];
            $criterio->pairs_id = $this->id; 
            $DB->update_record('pairs_criterio', $criterio, $bulk=false);

            $this->editar_opciones_criterio($fromform, $criterio->id, $i);
        }

        if($noAspectos-2 != count($data)){
        
            for($i = count($data)+1; $i <= $noAspectos-2; $i++){

                $des         = "descripcion$i";
                $descripcion = $fromform->$des;

                if(strlen($descripcion['text']) != 0){

                    $criterio->criterio           = $descripcion['text'];
                    $criterio->criterioformat     = $descripcion['format'];
                    $criterio->pairs_id          = $this->id;
                    $idCriterio                   = $DB->insert_record('pairs_criterio', $criterio);

                    $this->add_opciones_criterio($fromform, $idCriterio, $i);

                }

            }

        }
    }

    public function send_rating_gradebook($user=0){
        $pairs = new stdclass();
            foreach ($this as $property => $value) {
                $pairs->{$property} = $value;
            }
            $pairs->course     = $this->course->id;
            $pairs->cmidnumber = $this->cm->id;
            $pairs->modname    = 'pairs';
            pairs_update_grades($pairs, $user);
    }

    public function reset_userdata(stdClass $data){
        $this->reset_userdata_attachments($data);
    }

    protected function reset_userdata_attachments(stdClass $data) {
        global $DB;
        
        $DB->set_field('pairs', 'fase', '0', array('id' => $this->id));
        $this->fase = '0';

        $attachments = $this->get_deliverys();
        foreach ($attachments as $envio) {
            $this->delete_evaluacion_user($envio);
            $this->delete_delivery($envio);
            $DB->delete_records('pairs_delivery', array('id' => $envio->id));
        }

        return true;
    }

    protected function delete_evaluacion_user($envio){
        global $DB;
        $evaluaciones = $this->get_evaluations_by_deliveryId($envio->id);
        
        if(!empty($evaluaciones)){
        
            foreach ($evaluaciones as $evaluacion) {

                $DB->delete_records('pairs_answer_rubric', array('pairs_evaluacion_user_id' => $evaluacion->id));
    
            }
    
            $DB->delete_records('pairs_evaluacion_user', array('id' => $evaluacion->id));

        }
    }

    protected function delete_delivery($envio){
        $fs = get_file_storage();
        $fs->delete_area_files($this->context->id, 'mod_pairs', 'submission_attachment', $envio->id);
    }
}