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
require_once('localview/envio_form.php');

defined('MOODLE_INTERNAL') || die();

global $DB,$USER;

$id = optional_param('id', 0, PARAM_INT);
$envio->id = optional_param('env', 0, PARAM_INT);
$edit = optional_param('edit', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_INT);

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

$pairs =  new pairs($moduleinstance, $cm, $course);

$data  = $pairs->get_delivery_by_userId($USER->id);
$envio = end($data);

if (empty($envio->id)) {

    $envio     = new stdClass;
    $envio->id = null;

}

if($delete){

    $envio->id = $delete;

    $fs = get_file_storage();
    $fs->delete_area_files($pairs->context->id, 'mod_pairs', 'submission_attachment', $envio->id);
    
    $DB->delete_records('pairs_delivery', array('id' => $envio->id));

    redirect(new moodle_url('/mod/pairs/view.php', array('id' => $cm->id)),"Envio eliminado con éxito");

} else if(!$envio->id || $edit){

    $envio = file_prepare_standard_filemanager($envio, 'attachment', $pairs->filemanager_options(),
        $pairs->context, 'mod_pairs', 'submission_attachment', $envio->id);

    if(!$envio->id){
        $mform = new envio_form(new moodle_url('/mod/pairs/envio.php', 
            array('id' => $id)), array('current' => $envio,
            'attachmentopts' => $pairs->filemanager_options())); 
    }else{
        $mform = new envio_form(new moodle_url('/mod/pairs/envio.php', 
            array('id' => $id, 'env' => $envio->id, 'edit' => '1')), array('current' => $envio,
            'attachmentopts' => $pairs->filemanager_options())); 
    }

    if ($mform->is_cancelled()) {

        redirect(new moodle_url('/mod/pairs/view.php', array('id' => $cm->id, 'env' => $envio->id)));

    }else if ($data = $mform->get_data()) {
        
        $data->id = $envio->id;
        // Creates or updates submission.
        $data->id = $pairs->edit_delivery($data);

        redirect(new moodle_url('/mod/pairs/view.php', array('id' => $cm->id, 'env' => $data->id)));

    }
}

$PAGE->set_url(new moodle_url('/mod/pairs/envio.php', array('id' => $cm->id)));

$PAGE->set_title(get_string('pluginname', 'mod_pairs'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($pairs->context);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($course->name));

if($envio->id && !$edit){

    $fs = get_file_storage();
    $files = $fs->get_area_files($pairs->context->id, 'mod_pairs', 'submission_attachment', $envio->id);
    
    $file = end($files);
    
    $context = $pairs->context->id;
    $filename = $file->get_filename();
    
    $archivoUrl = new moodle_url("/pluginfile.php/$context/mod_pairs/submission_attachment/$envio->id/$filename?forcedownload=1");

    echo '<h3>Su envio:</h3>';
    echo '<a class="btn btn-secondary" href="'. $archivoUrl.'">'.$filename.'</a>';
    echo '<br>';
    echo '<br>';
    $url = new moodle_url('/mod/pairs/envio.php', array('id' => $cm->id, 'env'=>$envio->id, 'edit'=>'1'));
    $urlDelete = new moodle_url('/mod/pairs/envio.php', array('id' => $cm->id, 'delete' => $envio->id));

    echo '<a class="btn btn-outline-primary" href="'. $url .'">'.get_string('setenvio','mod_pairs').'</a>';
    echo '<a class="btn ml-3 btn-outline-secondary" href="'. $urlDelete .'">'.get_string('deletenvio','mod_pairs').'</a>';

}else{
    if(strlen($pairs->instruction_attachment) != 0){
        print_collapsible_region_start('', 'instrucciones-envio', get_string('param_inst', 'mod_pairs'));
        echo '<div class="row ml-2 mr-2 border border-top-0 border-primary shadow p-3 mb-5 bg-white rounded">';
        echo '	<div class="col-12">';
        echo "      <p>$pairs->instruction_attachment</p>";
        echo '	</div>';
        echo '</div>';
        print_collapsible_region_end();
    }
    $mform->display();
}

echo $OUTPUT->footer();