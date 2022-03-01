<?php

require_once($CFG->dirroot . '/mod/taller/backup/moodle2/backup_taller_stepslib.php'); // Because it exists (must)
require_once($CFG->dirroot . '/mod/taller/backup/moodle2/backup_taller_settingslib.php'); // Because it exists (optional)

/**
 * taller backup task that provides all the settings and steps to perform one
 * complete backup of the activity
 */
class backup_taller_activity_task extends backup_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // taller only has one structure step
        $this->add_step(new backup_taller_activity_structure_step('taller_structure', 'taller.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot,"/");

        // Link to the list of tallers
        $search = "/(" . $base . "\/mod\/taller\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@tallerINDEX*$2@$', $content);

        //Link to taller view by moduleid
        $search = "/(" . $base . "\/mod\/taller\/view.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@tallerVIEWBYID*$2@$', $content);

        return $content;
    }
}