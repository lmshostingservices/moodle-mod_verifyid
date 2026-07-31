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
 * AI Verify ID module form.
 *
 * @package    mod_verifyid
 * @copyright  2025 Essay Grader AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Module instance settings form.
 */
class mod_verifyid_mod_form extends moodleform_mod {

    /**
     * Defines forms elements.
     */
    public function definition() {
        $mform = $this->_form;

        // General section.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Activity name.
        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Description.
        $this->standard_intro_elements();

        // Instructions for students.
        $mform->addElement('header', 'instructionsheader', get_string('instructionsheader', 'mod_verifyid'));

        $mform->addElement('textarea', 'instructions', get_string('instructions', 'mod_verifyid'), [
            'rows' => 4,
            'cols' => 60
        ]);
        $mform->setType('instructions', PARAM_TEXT);
        $mform->addHelpButton('instructions', 'instructions', 'mod_verifyid');

        // Standard course module elements.
        $this->standard_coursemodule_elements();

        // Action buttons.
        $this->add_action_buttons();
    }

    /**
     * Add completion rules.
     *
     * @return array Array of completion rule elements
     */
    public function add_completion_rules() {
        $mform = $this->_form;

        $mform->addElement('checkbox', 'completionverified', '', get_string('completionverified', 'mod_verifyid'));
        $mform->addHelpButton('completionverified', 'completionverified', 'mod_verifyid');

        return ['completionverified'];
    }

    /**
     * Check if completion rules are enabled.
     *
     * @param array $data Form data
     * @return bool
     */
    public function completion_rule_enabled($data) {
        return !empty($data['completionverified']);
    }

    /**
     * Get data from the form.
     *
     * @return stdClass|null Form data
     */
    public function get_data() {
        $data = parent::get_data();
        if ($data) {
            if (!isset($data->completionverified)) {
                $data->completionverified = 0;
            }
        }
        return $data;
    }
}
