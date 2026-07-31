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
 * Library of functions for AI Verify ID.
 *
 * @package    mod_verifyid
 * @copyright  2025 Essay Grader AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Returns the information on whether the module supports a feature.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function verifyid_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_ASSESSMENT;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the module into the database.
 *
 * @param stdClass $data Form data
 * @param mod_verifyid_mod_form $mform The form
 * @return int The id of the newly inserted record
 */
function verifyid_add_instance($data, ?object $mform = null) {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = time();

    // Set defaults for optional fields.
    if (!isset($data->instructions)) {
        $data->instructions = '';
    }

    $data->id = $DB->insert_record('verifyid', $data);

    return $data->id;
}

/**
 * Updates an instance of the module in the database.
 *
 * @param stdClass $data Form data
 * @param mod_verifyid_mod_form $mform The form
 * @return bool Success/Failure
 */
function verifyid_update_instance($data, ?object $mform = null) {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;

    if (!isset($data->instructions)) {
        $data->instructions = '';
    }

    return $DB->update_record('verifyid', $data);
}

/**
 * Removes an instance of the module from the database.
 *
 * @param int $id Id of the module instance
 * @return bool Success/Failure
 */
function verifyid_delete_instance($id) {
    global $DB;

    if (!$DB->record_exists('verifyid', ['id' => $id])) {
        return false;
    }

    // Delete all verification attempts.
    $DB->delete_records('verifyid_attempts', ['verifyidid' => $id]);

    // Delete the main record.
    $DB->delete_records('verifyid', ['id' => $id]);

    return true;
}

/**
 * Trigger module viewed event.
 *
 * @param stdClass $verifyid The module instance
 * @param stdClass $course The course
 * @param stdClass $cm Course module
 * @param context_module $context Module context
 */
function verifyid_view($verifyid, $course, $cm, $context) {
    // Update completion state only - no custom event logging needed for v3.0.0 skeleton.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Add a get_coursemodule_info function to add extra info to course listing.
 *
 * @param stdClass $coursemodule The coursemodule object
 * @return cached_cm_info|false
 */
function verifyid_get_coursemodule_info($coursemodule) {
    global $DB;

    $dbparams = ['id' => $coursemodule->instance];
    $fields = 'id, name, intro, introformat';
    if (!$verifyid = $DB->get_record('verifyid', $dbparams, $fields)) {
        return false;
    }

    $info = new cached_cm_info();
    $info->name = $verifyid->name;

    if ($coursemodule->showdescription) {
        $info->content = format_module_intro('verifyid', $verifyid, $coursemodule->id, false);
    }

    return $info;
}

/**
 * Obtains the automatic completion state for this module.
 *
 * @param stdClass $course Course
 * @param stdClass $cm Course module
 * @param int $userid User ID
 * @param bool $type Type of comparison
 * @return bool True if completed, false otherwise
 */
function verifyid_get_completion_state($course, $cm, $userid, $type) {
    global $DB;

    $verifyid = $DB->get_record('verifyid', ['id' => $cm->instance], '*', MUST_EXIST);

    // Check if user has a verified attempt.
    $verified = $DB->record_exists('verifyid_attempts', [
        'verifyidid' => $verifyid->id,
        'userid' => $userid,
        'status' => 'verified'
    ]);

    return $verified;
}
