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
 * Restore steps for mod_verifyid.
 *
 * @package    mod_verifyid
 * @copyright  2025 Essay Grader AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Structure step to restore one verifyid activity.
 */
class restore_verifyid_activity_structure_step extends restore_activity_structure_step {
    /**
     * Define the structure of the restore.
     *
     * @return array
     */
    protected function define_structure() {
        $paths = [];
        $paths[] = new restore_path_element('verifyid', '/activity/verifyid');
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process a verifyid restore.
     *
     * @param array $data The data from the backup file.
     */
    protected function process_verifyid($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('verifyid', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Define post-execution actions.
     */
    protected function after_execute() {
        $this->add_related_files('mod_verifyid', 'intro', null);
    }
}
