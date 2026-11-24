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
 * Data generator for mod_verifyid.
 *
 * @package    mod_verifyid
 * @copyright  2026 LMS Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_verifyid_generator extends testing_module_generator {
    /**
     * Creates an instance of the module for testing purposes.
     *
     * @param array|stdClass|null $record Data for the module instance.
     * @param array|null $options General options for the course module.
     * @return stdClass The record from the verifyid table, with cmid.
     */
    public function create_instance($record = null, ?array $options = null) {
        $record = (object) (array) $record;
        $defaults = [
            'instructions' => '',
            'completionverified' => 0,
        ];
        foreach ($defaults as $name => $value) {
            if (!isset($record->{$name})) {
                $record->{$name} = $value;
            }
        }
        return parent::create_instance($record, (array) $options);
    }

    /**
     * Creates an identity check attempt for a learner.
     *
     * @param array|stdClass $record Must contain verifyidid and userid. status defaults to pending.
     * @return stdClass The attempt record.
     */
    public function create_attempt($record): stdClass {
        global $DB;
        $record = (object) (array) $record;
        if (empty($record->verifyidid) || empty($record->userid)) {
            throw new coding_exception('create_attempt needs verifyidid and userid');
        }
        $record->status = $record->status ?? 'pending';
        $record->timecreated = $record->timecreated ?? time();
        $record->id = $DB->insert_record('verifyid_attempts', $record);
        return $record;
    }
}
