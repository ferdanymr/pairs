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
 * Capability definitions for the taller module
 *
 * @package     mod_taller
 * @copyright   2021 Fernando Munoz <fernando_munoz@cuaieed.unam.mx>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    // Capacidad de ver que el taller existe, y la información básica sobre el mismo
    'mod/taller:view' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'guest' => CAP_ALLOW,
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    // Ability to add a new taller to the course.
    'mod/taller:addinstance' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'moodle/course:manageactivities'
    ),

    // Ability to change the current phase (stage) of the taller, for example
    // allow submitting, start assessment period, close taller etc.
    'mod/taller:switchphase' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    // Ability to modify the assessment forms, gives access to editform.php
    'mod/taller:editdimensions' => array(
        'riskbitmask' => RISK_XSS,              // can embed flash and javascript into wysiwyg
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    // Ability to submit own work. All users having this capability are expected to participate
    // in the taller as the authors
    'mod/taller:submit' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'student' => CAP_ALLOW,
        )
    ),

    // Ability to be a reviewer of a submission. All users with this capability are considered
    // as potential reviewers for the allocation purposes and can train assessment process on the
    // example submissions.
    'mod/taller:peerassess' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'student' => CAP_ALLOW,
        )
    ),


    // Ability to submit and referentially assess the examples and to see all other
    // assessments of these examples
    'mod/taller:manageexamples' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    // Ability to allocate (assign) a submission for a review
    'mod/taller:allocate' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    // Ability to publish submissions, i.e. make them available when taller is closed
    'mod/taller:publishsubmissions' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    // Ability to identify the author of the work that has been allocated to them for a review
    // Reviewers without this capability will see the author as Anonymous
    'mod/taller:viewauthornames' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    // Ability to identify the reviewer of the given submission (i.e. the owner of the assessment)
    'mod/taller:viewreviewernames' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    // Ability to view the work submitted by an other user. In separate groups mode,
    // the user has to be allowed to access all groups or be the member of the
    // submission author's group.
    'mod/taller:viewallsubmissions' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    // Ability to view published submission when the taller is closed. Group mode
    // restrictions do not apply here, published submissions are available in all
    // groups even in the separate groups mode.
    'mod/taller:viewpublishedsubmissions' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    // Ability to view the authors of published submissions.
    'mod/taller:viewauthorpublished' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    // Ability to always view the assessments of other users' work and the calculated grades,
    // regardless the phase. The separate groups membership is checked against the submission
    // author only, not against the reviewer. In other words, if the user has this capability
    // and is allowed to see some submission, then they are implicitly allowed to see all
    // assessments of that submissions even if they do not share a group with the reviewer.
    'mod/taller:viewallassessments' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    // Ability to override grade for submission or the calculated grades for assessment
    // and to run aggregation tasks that computes the total grade
    'mod/taller:overridegrades' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    // Ability to ignore time restrictions (submission start/end time and assessment
    // start/end time) if they are defined
    'mod/taller:ignoredeadlines' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    // Ability to delete other users' submissions.
    'mod/taller:deletesubmissions' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    // Ability to export submissions to a portfolio. Applies to all submissions
    // the user has access to.
    'mod/taller:exportsubmissions' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'student' => CAP_ALLOW,
        )
    ),
);
