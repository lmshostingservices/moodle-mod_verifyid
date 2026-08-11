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
 * AI Verify ID database upgrade script.
 *
 * @package    mod_verifyid
 * @copyright  2025 Essay Grader AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_verifyid_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    // Add new fields for enhanced verification (face + name matching) in v3.1.0.
    if ($oldversion < 2025121500) {
        $table = new xmldb_table('verifyid_attempts');

        // Add facesimilarity field.
        $field = new xmldb_field('facesimilarity', XMLDB_TYPE_NUMBER, '5, 2', null, null, null, null, 'similarity');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add namesimilarity field.
        $field = new xmldb_field('namesimilarity', XMLDB_TYPE_NUMBER, '5, 2', null, null, null, null, 'facesimilarity');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add extractedname field.
        $field = new xmldb_field('extractedname', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'namesimilarity');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add documenttype field.
        $field = new xmldb_field('documenttype', XMLDB_TYPE_CHAR, '50', null, null, null, null, 'extractedname');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2025121500, 'verifyid');
    }

    // v3.1.7: SYNC FIX — version.php was bumped to 202603021901 through several releases
    //   without a corresponding upgrade.php savepoint being added. Added retroactively
    //   to bring the DB version in sync with version.php. No DB schema changes.
    if ($oldversion < 202603021901) {
        upgrade_mod_savepoint(true, 202603021901, 'verifyid');
    }

    // v3.1.8: FIX — version number corrected to 13-digit YYYYMMDD00XXX format. No DB schema changes.
    if ($oldversion < 2026041000318) {
        upgrade_mod_savepoint(true, 2026041000318, 'verifyid');
    }

    // v3.1.10: FIX-CURL-BATCH — ajax.php switched from raw curl_init() to Moodle \curl
    //   wrapper + write_close(). No DB schema changes.
    if ($oldversion < 2026051200320) {
        upgrade_mod_savepoint(true, 2026051200320, 'verifyid');
    }

    if ($oldversion < 2026072300233) {
        // FIX-API-DOMAIN: Updated all API endpoint URLs from lms-labs.com to lms-labs.com.
        // lms-labs.com has no DNS resolution from Moodle server side; lms-labs.com is the
        // correct working domain. All ajax.php, api_client, unlock_verifier, lib.php calls updated.
        if (function_exists('opcache_invalidate')) {
            $_pluginDir = realpath(__DIR__ . '/..');
            foreach (['version.php', 'db/upgrade.php'] as $_f) {
                $_full = $_pluginDir . '/' . $_f;
                if (file_exists($_full)) {
                    opcache_invalidate($_full, true);
                }
            }
        } elseif (function_exists('opcache_reset')) {
            opcache_reset();
        }
        upgrade_mod_savepoint(true, 2026072300233, 'verifyid');
    }

    if ($oldversion < 2026072300234) {
        // FIX-API-DOMAIN: Reverted API endpoint to lms-labs.com (correct domain).
        // essaygraderai.app was the original single-plugin domain; lms-labs.com is correct.
        if (function_exists('opcache_invalidate')) {
            $_pluginDir = realpath(__DIR__ . '/..');
            foreach (['version.php', 'db/upgrade.php'] as $_f) {
                $_full = $_pluginDir . '/' . $_f;
                if (file_exists($_full)) { opcache_invalidate($_full, true); }
            }
        } elseif (function_exists('opcache_reset')) { opcache_reset(); }
        upgrade_mod_savepoint(true, 2026072300234, 'verifyid');
    }

    if ($oldversion < 2026072300235) {
        // Domain update: lms-labs.com → lms-labs.com
        if (function_exists('opcache_invalidate')) {
            $_pluginDir = realpath(__DIR__ . '/..');
            foreach (['version.php', 'lib.php', 'db/upgrade.php'] as $_f) {
                $_full = $_pluginDir . '/' . $_f;
                if (file_exists($_full)) { opcache_invalidate($_full, true); }
            }
        } elseif (function_exists('opcache_reset')) { opcache_reset(); }
        upgrade_mod_savepoint(true, 2026072300235, 'verifyid');
    }

    return true;
}