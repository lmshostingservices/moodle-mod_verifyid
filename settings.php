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
 * AI Verify ID settings.
 * 
 * Note: Site ID and API Key are managed via AI Grader Central Config (local_aiconfig).
 * These fallback settings are only used if Central Config is not installed.
 * This plugin has additional threshold settings that require configuration.
 *
 * @package    mod_verifyid
 * @copyright  2025 Essay Grader AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// CRITICAL: Use defensive pattern - check both $hassiteconfig AND isset($settings).
// This prevents errors when $settings is not pre-created by Moodle in some contexts.
if ($hassiteconfig && isset($settings)) {
    // Check if central config is available
    $centralconfigurl = new moodle_url('/admin/settings.php', ['section' => 'local_aiconfig']);
    $centralconfiginstalled = file_exists($CFG->dirroot . '/local/aiconfig/version.php');
    
    if ($centralconfiginstalled) {
        $settings->add(new admin_setting_heading(
            'mod_verifyid/centralconfig_notice',
            '',
            '<div style="padding: 12px; background: #ecfdf5; border: 1px solid #10b981; border-radius: 8px; margin-bottom: 16px;">' .
            '<strong style="color: #047857;">AI Grader Central Config is installed.</strong><br>' .
            'Site ID and API Key are managed centrally. ' .
            '<a href="' . $centralconfigurl->out() . '">Configure Central Settings</a>' .
            '</div>'
        ));
    } else {
        $settings->add(new admin_setting_heading(
            'mod_verifyid/centralconfig_notice',
            '',
            '<div style="padding: 12px; background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; margin-bottom: 16px;">' .
            '<strong style="color: #b45309;">Recommended: Install AI Grader Central Config</strong><br>' .
            'Configure Site ID and API Key once for all AI Grader plugins.' .
            '</div>'
        ));
    }

    // API URL setting.
    $settings->add(new admin_setting_configtext(
        'mod_verifyid/apiurl',
        get_string('apiurl', 'mod_verifyid'),
        get_string('apiurl_desc', 'mod_verifyid'),
        'https://lms-labs.com',
        PARAM_URL
    ));

    // Site ID setting.
    $settings->add(new admin_setting_configtext(
        'mod_verifyid/siteid',
        get_string('siteid', 'mod_verifyid'),
        get_string('siteid_desc', 'mod_verifyid') . ($centralconfiginstalled ? ' (Fallback - Central Config takes priority)' : ''),
        '',
        PARAM_TEXT
    ));

    // API Key setting.
    $settings->add(new admin_setting_configpasswordunmask(
        'mod_verifyid/apikey',
        get_string('apikey', 'mod_verifyid'),
        get_string('apikey_desc', 'mod_verifyid') . ($centralconfiginstalled ? ' (Fallback - Central Config takes priority)' : ''),
        ''
    ));

    // Threshold settings heading.
    $settings->add(new admin_setting_heading(
        'mod_verifyid/thresholds_heading',
        get_string('thresholds_heading', 'mod_verifyid'),
        get_string('thresholds_desc', 'mod_verifyid')
    ));

    // Auto-approve threshold.
    $settings->add(new admin_setting_configtext(
        'mod_verifyid/auto_approve_threshold',
        get_string('auto_approve_threshold', 'mod_verifyid'),
        get_string('auto_approve_threshold_desc', 'mod_verifyid'),
        '80',
        PARAM_INT
    ));

    // Review threshold.
    $settings->add(new admin_setting_configtext(
        'mod_verifyid/review_threshold',
        get_string('review_threshold', 'mod_verifyid'),
        get_string('review_threshold_desc', 'mod_verifyid'),
        '70',
        PARAM_INT
    ));

    // Credits info.
    $settings->add(new admin_setting_heading(
        'mod_verifyid/credits_heading',
        get_string('credits_heading', 'mod_verifyid'),
        get_string('credits_info', 'mod_verifyid')
    ));
}
