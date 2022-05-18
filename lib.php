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
 * @package     mod_pairs
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
function pairs_supports($feature) {
    switch ($feature) {
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_PLAGIARISM:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_pairs into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @param mod_pairs_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function pairs_add_instance(stdclass $pairs) {
    global $DB;
    $editor                               = $pairs->instruction_attachment;
    $pairs->fase                         = '0';
    $pairs->timecreated                  = time();
    $pairs->timemodified                 = $pairs->timecreated;
    $pairs->instruction_attachmentformat      = $editor['format'];
    $pairs->instruction_attachment            = $editor['text'];

    $editor                               = $pairs->instruction_assessment;

    $pairs->instruction_assessmentformat = $editor['format'];
    $pairs->instruction_assessment       = $editor['text'];

    $editor                               = $pairs->retro_conclusion;

    $pairs->retro_conclusionformat       = $editor['format'];
    $pairs->retro_conclusion             = $editor['text'];

    $id = $DB->insert_record('pairs', $pairs);

    // create gradebook items
    pairs_grade_item_update($pairs);
    pairs_grade_item_category_update($pairs);

    return $id;
}

/**
 * Updates an instance of the mod_pairs in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $pairs An object from the form in mod_form.php.
 * @param mod_pairs_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function pairs_update_instance(stdclass $pairs) {
    global $DB;
    $editor = $pairs->instruction_attachment;
    $pairs->timemodified = time();
    $pairs->id = $pairs->instance;
    $pairs->instruction_attachmentformat             = $editor['format'];
    $pairs->instruction_attachment       = $editor['text'];

    $editor = $pairs->instruction_assessment;

    $pairs->instruction_assessmentformat        = $editor['format'];
    $pairs->instruction_assessment  = $editor['text'];

    $editor = $pairs->retro_conclusion;

    $pairs->retro_conclusionformat        = $editor['format'];
    $pairs->retro_conclusion        = $editor['text'];

    $DB->update_record('pairs', $pairs);
    
    // create gradebook items
    pairs_grade_item_update($pairs);
    pairs_grade_item_category_update($pairs);

    return $pairs->id;
}

/**
 * Removes an instance of the mod_pairs from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function pairs_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('pairs', array('id' => $id));
    if (!$exists) {
        return false;
    }

    $DB->delete_records('pairs', array('id' => $id));

    return true;
}

/**
 * Serves the files from the pairs file areas
 *
 * Apart from module intro (handled by pluginfile.php automatically), pairs files may be
 * media inserted into submission content (like images) and submission attachments. For these two,
 * the fileareas submission_content and submission_attachment are used.
 * Besides that, areas instructauthors, instructreviewers and conclusion contain the media
 * embedded using the mod_form.php.
 *
 * @package  mod_pairs
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the pairs's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if the file not found, just send the file otherwise and do not return anything
 */
function pairs_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    global $DB, $CFG, $USER;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, true, $cm);

    if ($filearea === 'submission_attachment') {
        $itemid = (int)array_shift($args);
        if (!$pairs = $DB->get_record('pairs', array('id' => $cm->instance))) {
            return false;
        }
        if (!$submission = $DB->get_record('pairs_delivery', array('id' => $itemid, 'pairs_id' => $pairs->id))) {
            return false;
        }

        $fs = get_file_storage();
        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_pairs/$filearea/$itemid/$relativepath";
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
 * Creates or updates grade items for the give pairs instance
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php. Also used by
 * {@link pairs_update_grades()}.
 *
 * @param stdClass $pairs instance object with extra cmidnumber property
 * @param stdClass $envio data for the first grade item
 * @param stdClass $assessmentgrades data for the second grade item
 * @return void
 */
function pairs_grade_item_update(stdclass $pairs, $envio=null) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    $a = new stdclass();
    $a->pairsname = clean_param($pairs->name, PARAM_NOTAGS);

    $item = array();
    $item['itemname'] = $a->pairsname;
    $item['gradetype'] = GRADE_TYPE_VALUE;
    $item['grademax']  = 100.00;
    $item['grademin']  = 0;
    grade_update('mod/pairs', $pairs->course, 'mod', 'pairs', $pairs->id, 0, $envio , $item);
}

/**
 * Update pairs grades in the gradebook
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @category grade
 * @param stdClass $pairs instance object with extra cmidnumber and modname property
 * @param int $userid        update grade of specific user only, 0 means all participants
 * @return void
 */
function pairs_update_grades(stdclass $pairs, $userid=0) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    $whereuser = $userid ? ' AND autor_id = :userid' : '';
    $params = array('pairs_id' => $pairs->id, 'userid' => $userid);
    $sql = 'SELECT autor_id, rating
              FROM {pairs_delivery}
             WHERE pairs_id = :pairs_id' . $whereuser;
    $records = $DB->get_records_sql($sql, $params);
    $envio = array();
    foreach ($records as $record) {
        $grade = new stdclass();
        $grade->userid = $record->autor_id;
        $rating = round($record->rating,$pairs->no_decimals);
        $grade->rawgrade = $rating;
        $envio[$record->autor_id] = $grade;
    }

    pairs_grade_item_update($pairs, $envio);
}

function pairs_grade_item_category_update($pairs) {

    $gradeitems = grade_item::fetch_all(array(
        'itemtype'      => 'mod',
        'itemmodule'    => 'pairs',
        'iteminstance'  => $pairs->id,
        'courseid'      => $pairs->course));
    
    $gradeitem = current($gradeitems);
    if (!empty($gradeitem)) {
        if ($gradeitem->categoryid != $pairs->categoria) {
            $gradeitem->set_parent($pairs->categoria);
        }
    }
}

//////////////////////////////////////////
//Reset curse                           //
/////////////////////////////////////////
function pairs_reset_course_form_definition($mform) {
    $mform->addElement('header', 'pairsheader', get_string('modulenameplural', 'mod_pairs'));

    $mform->addElement('checkbox', 'reset_pairs_all', get_string('resetpairsall','mod_pairs'));

}


function pairs_reset_course_form_defaults(stdClass $course) {

    $defaults = array(
        'reset_pairs_all'    => 1,
    );

    return $defaults;
}

function pairs_reset_userdata($data) {
    global $CFG, $DB;
    $status[] = array('component' => get_string('modulenameplural', 'mod_pairs'), 'item' => get_string('resetpairs','mod_pairs'),
        'error' => false);
    if (!empty($data->reset_pairs_all)) {
        
        $pairsRecords = $DB->get_records('pairs', array('course' => $data->courseid));
        
        if(!empty($pairsRecords)){
            
            require_once($CFG->dirroot . '/mod/pairs/locallib.php');
            $course = $DB->get_record('course', array('id' => $data->courseid), '*', MUST_EXIST);

            foreach ($pairsRecords as $pairsRecord) {
                $cm = get_coursemodule_from_instance('pairs', $pairsRecord->id, $course->id, false, MUST_EXIST);
                $pairs = new pairs($pairsRecord, $cm, $course);
                $pairs->reset_userdata($data);
            }

            return $status;
        }

    }

    return $status;
}