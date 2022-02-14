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
 * Plugin version and other meta-data are defined here.
 *
 * @package     mod_taller
 * @copyright   2021 Fernando Munoz <fernando_munoz@cuaieed.unam.mx>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function taller_supports($feature) {
    switch ($feature) {
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_taller into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @param mod_taller_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function taller_add_instance(stdclass $taller) {
    global $DB;
    $editor                               = $taller->instruccion_envio;
    $taller->fase                         = '0';
    $taller->timecreated                  = time();
    $taller->timemodified                 = $taller->timecreated;
    $taller->instruccion_envioformat      = $editor['format'];
    $taller->instruccion_envio            = $editor['text'];

    $editor                               = $taller->instruccion_valoracion;

    $taller->instruccion_valoracionformat = $editor['format'];
    $taller->instruccion_valoracion       = $editor['text'];

    $editor                               = $taller->retro_conclusion;

    $taller->retro_conclusionformat       = $editor['format'];
    $taller->retro_conclusion             = $editor['text'];

    $id = $DB->insert_record('taller', $taller);

    return $id;
}

/**
 * Updates an instance of the mod_taller in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_taller_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function taller_update_instance($moduleinstance, $mform = null) {
    global $DB;
    $editor = $moduleinstance->instruccion_envio;
    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;
    $moduleinstance->instruccion_envioformat             = $editor['format'];
    $moduleinstance->instruccion_envio       = $editor['text'];

    $editor = $moduleinstance->instruccion_valoracion;

    $moduleinstance->instruccion_valoracionformat        = $editor['format'];
    $moduleinstance->instruccion_valoracion  = $editor['text'];

    $editor = $moduleinstance->retro_conclusion;

    $moduleinstance->retro_conclusionformat        = $editor['format'];
    $moduleinstance->retro_conclusion        = $editor['text'];

    return $DB->update_record('taller', $moduleinstance);
}

/**
 * Removes an instance of the mod_taller from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function taller_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('taller', array('id' => $id));
    if (!$exists) {
        return false;
    }

    $DB->delete_records('taller', array('id' => $id));

    return true;
}

/**
 * Serves the files from the taller file areas
 *
 * Apart from module intro (handled by pluginfile.php automatically), taller files may be
 * media inserted into submission content (like images) and submission attachments. For these two,
 * the fileareas submission_content and submission_attachment are used.
 * Besides that, areas instructauthors, instructreviewers and conclusion contain the media
 * embedded using the mod_form.php.
 *
 * @package  mod_taller
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the taller's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if the file not found, just send the file otherwise and do not return anything
 */
function taller_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    global $DB, $CFG, $USER;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, true, $cm);

    if ($filearea === 'submission_attachment') {
        $itemid = (int)array_shift($args);
        if (!$taller = $DB->get_record('taller', array('id' => $cm->instance))) {
            return false;
        }
        if (!$submission = $DB->get_record('taller_entrega', array('id' => $itemid, 'taller_id' => $taller->id))) {
            return false;
        }

        $fs = get_file_storage();
        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_taller/$filearea/$itemid/$relativepath";
        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
            return false;
        }
        // finally send the file
        // these files are uploaded by students - forcing download for security reasons
        send_stored_file($file, 0, 0, true, $options);

    }

    return false;
}

////////////////////////////////////////////////////////////////////////////////
// Gradebook API                                                              //
////////////////////////////////////////////////////////////////////////////////

/**
 * Creates or updates grade items for the give taller instance
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php. Also used by
 * {@link taller_update_grades()}.
 *
 * @param stdClass $taller instance object with extra cmidnumber property
 * @param stdClass $envio data for the first grade item
 * @param stdClass $assessmentgrades data for the second grade item
 * @return void
 */
function taller_grade_item_update(stdclass $taller, $envio=null) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    $a = new stdclass();
    $a->tallername = clean_param($taller->name, PARAM_NOTAGS);

    $item = array();
    $item['itemname'] = $a->tallername;
    $item['gradetype'] = GRADE_TYPE_VALUE;
    $item['grademax']  = 10.00;
    $item['grademin']  = 0;
    grade_update('mod/taller', $taller->course, 'mod', 'taller', $taller->id, 0, $envio , $item);
}

/**
 * Update taller grades in the gradebook
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @category grade
 * @param stdClass $taller instance object with extra cmidnumber and modname property
 * @param int $userid        update grade of specific user only, 0 means all participants
 * @return void
 */
function taller_update_grades(stdclass $taller, $userid=0) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    $whereuser = $userid ? ' AND autor_id = :userid' : '';
    $params = array('taller_id' => $taller->id, 'userid' => $userid);
    $sql = 'SELECT autor_id, calificacion
              FROM {taller_entrega}
             WHERE taller_id = :taller_id' . $whereuser;
    $records = $DB->get_records_sql($sql, $params);
    $envio = array();
    foreach ($records as $record) {
        $grade = new stdclass();
        $grade->userid = $record->autor_id;
        $calificacion = round($record->calificacion,$taller->no_decimales);
        $grade->rawgrade = $calificacion;
        $envio[$record->autor_id] = $grade;
    }

    taller_grade_item_update($taller, $envio);
}