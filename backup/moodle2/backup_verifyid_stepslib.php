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
 * Backup steps for mod_verifyid.
 *
 * @package    mod_verifyid
 * @copyright  2025 Essay Grader AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define the complete verifyid structure for backup.
 */
class backup_verifyid_activity_structure_step extends backup_activity_structure_step {
    /**
     * Define the structure of the backup file.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        // Define each element separated.
        $verifyid = new backup_nested_element('verifyid', ['id'], [
            'name', 'intro', 'introformat', 'instructions', 'completionverified',
            'timecreated', 'timemodified'
        ]);

        // Define sources.
        $verifyid->set_source_table('verifyid', ['id' => backup::VAR_ACTIVITYID]);

        // Return the root element (verifyid), wrapped into standard activity structure.
        return $this->prepare_activity_structure($verifyid);
    }
}
