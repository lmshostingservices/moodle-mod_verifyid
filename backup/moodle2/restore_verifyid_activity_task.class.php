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
 * Restore task for mod_verifyid.
 *
 * @package    mod_verifyid
 * @copyright  2025 Essay Grader AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/verifyid/backup/moodle2/restore_verifyid_stepslib.php');

/**
 * Restore task that provides all the settings and steps to perform one complete restore of the activity.
 */
class restore_verifyid_activity_task extends restore_activity_task {
    /**
     * Define particular settings for the restore.
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define particular steps for the restore process.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_verifyid_activity_structure_step('verifyid_structure', 'verifyid.xml'));
    }

    /**
     * Define the contents in the activity that must be processed by the link decoder.
     *
     * @return array
     */
    public static function define_decode_contents() {
        $contents = [];
        $contents[] = new restore_decode_content('verifyid', ['intro', 'instructions'], 'verifyid');
        return $contents;
    }

    /**
     * Define the decoding rules for links.
     *
     * @return array
     */
    public static function define_decode_rules() {
        $rules = [];

        $rules[] = new restore_decode_rule('VERIFYIDVIEWBYID', '/mod/verifyid/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('VERIFYIDINDEX', '/mod/verifyid/index.php?id=$1', 'course');

        return $rules;
    }

    /**
     * Define the restore log rules.
     *
     * @return array
     */
    public static function define_restore_log_rules() {
        $rules = [];
        $rules[] = new restore_log_rule('verifyid', 'add', 'view.php?id={course_module}', '{verifyid}');
        $rules[] = new restore_log_rule('verifyid', 'update', 'view.php?id={course_module}', '{verifyid}');
        $rules[] = new restore_log_rule('verifyid', 'view', 'view.php?id={course_module}', '{verifyid}');
        return $rules;
    }

    /**
     * Define the restore log rules for course.
     *
     * @return array
     */
    public static function define_restore_log_rules_for_course() {
        $rules = [];
        $rules[] = new restore_log_rule('verifyid', 'view all', 'index.php?id={course}', null);
        return $rules;
    }
}
