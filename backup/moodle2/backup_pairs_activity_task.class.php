<?php

require_once($CFG->dirroot . '/mod/pairs/backup/moodle2/backup_pairs_stepslib.php'); // Because it exists (must)
require_once($CFG->dirroot . '/mod/pairs/backup/moodle2/backup_pairs_settingslib.php'); // Because it exists (optional)

/**
 * pairs backup task that provides all the settings and steps to perform one
 * complete backup of the activity
 */
class backup_pairs_activity_task extends backup_activity_task {

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
        // pairs only has one structure step
        $this->add_step(new backup_pairs_activity_structure_step('pairs_structure', 'pairs.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot,"/");

        // Link to the list of pairss
        $search = "/(" . $base . "\/mod\/pairs\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@pairsINDEX*$2@$', $content);

        //Link to pairs view by moduleid
        $search = "/(" . $base . "\/mod\/pairs\/view.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@pairsVIEWBYID*$2@$', $content);

        return $content;
    }
}