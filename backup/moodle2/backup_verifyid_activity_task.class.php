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
 * Backup task for mod_verifyid.
 *
 * @package    mod_verifyid
 * @copyright  2025 Essay Grader AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/verifyid/backup/moodle2/backup_verifyid_stepslib.php');

/**
 * Backup task that provides all the settings and steps to perform one complete backup of the activity.
 */
class backup_verifyid_activity_task extends backup_activity_task {
    /**
     * Define particular settings for this activity.
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define particular steps for the backup process.
     */
    protected function define_my_steps() {
        $this->add_step(new backup_verifyid_activity_structure_step('verifyid_structure', 'verifyid.xml'));
    }

    /**
     * Encode content links in the activity.
     *
     * @param string $content The content to encode.
     * @return string The encoded content.
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');

        // Link to the list of verifyid instances.
        $search = '/(' . $base . '\/mod\/verifyid\/index.php\?id=)([0-9]+)/';
        $content = preg_replace($search, '$@VERIFYIDINDEX*$2@$', $content);

        // Link to verifyid view by moduleid.
        $search = '/(' . $base . '\/mod\/verifyid\/view.php\?id=)([0-9]+)/';
        $content = preg_replace($search, '$@VERIFYIDVIEWBYID*$2@$', $content);

        return $content;
    }
}
