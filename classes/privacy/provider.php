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
 * Privacy provider for AI Verify ID.
 *
 * @package    mod_verifyid
 * @copyright  2025 Essay Grader AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_verifyid\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\transform;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider implementation.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {
    /**
     * Get metadata about data stored by this plugin.
     *
     * @param collection $collection The metadata collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'verifyid_attempts',
            [
                'userid' => 'privacy:metadata:verifyid_attempts:userid',
                'idimage' => 'privacy:metadata:verifyid_attempts:idimage',
                'selfie' => 'privacy:metadata:verifyid_attempts:selfie',
                'status' => 'privacy:metadata:verifyid_attempts:status',
                'similarity' => 'privacy:metadata:verifyid_attempts:similarity',
                'timecreated' => 'privacy:metadata:verifyid_attempts:timecreated',
            ],
            'privacy:metadata:verifyid_attempts'
        );

        return $collection;
    }

    /**
     * Get contexts that contain user data.
     *
     * @param int $userid The user ID.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {verifyid} v ON v.id = cm.instance
                  JOIN {verifyid_attempts} va ON va.verifyidid = v.id
                 WHERE va.userid = :userid";

        $params = [
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'verifyid',
            'userid' => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get users in a context.
     *
     * @param userlist $userlist The userlist.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $sql = "SELECT va.userid
                  FROM {verifyid_attempts} va
                  JOIN {verifyid} v ON v.id = va.verifyidid
                  JOIN {course_modules} cm ON cm.instance = v.id
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                 WHERE cm.id = :cmid";

        $params = [
            'modname' => 'verifyid',
            'cmid' => $context->instanceid,
        ];

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export user data.
     *
     * @param approved_contextlist $contextlist The approved contexts.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }

            $cm = get_coursemodule_from_id('verifyid', $context->instanceid);
            if (!$cm) {
                continue;
            }

            $attempts = $DB->get_records('verifyid_attempts', [
                'verifyidid' => $cm->instance,
                'userid' => $userid,
            ]);

            foreach ($attempts as $attempt) {
                $data = (object) [
                    'status' => $attempt->status,
                    'similarity' => $attempt->similarity,
                    'timecreated' => transform::datetime($attempt->timecreated),
                ];

                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'mod_verifyid')],
                    $data
                );
            }
        }
    }

    /**
     * Delete data for all users in a context.
     *
     * @param \context $context The context.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $cm = get_coursemodule_from_id('verifyid', $context->instanceid);
        if (!$cm) {
            return;
        }

        $DB->delete_records('verifyid_attempts', ['verifyidid' => $cm->instance]);
    }

    /**
     * Delete data for a specific user.
     *
     * @param approved_contextlist $contextlist The approved contexts.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }

            $cm = get_coursemodule_from_id('verifyid', $context->instanceid);
            if (!$cm) {
                continue;
            }

            $DB->delete_records('verifyid_attempts', [
                'verifyidid' => $cm->instance,
                'userid' => $userid,
            ]);
        }
    }

    /**
     * Delete data for users in a context.
     *
     * @param approved_userlist $userlist The approved userlist.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $cm = get_coursemodule_from_id('verifyid', $context->instanceid);
        if (!$cm) {
            return;
        }

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        list($insql, $inparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params = array_merge(['verifyidid' => $cm->instance], $inparams);

        $DB->delete_records_select(
            'verifyid_attempts',
            "verifyidid = :verifyidid AND userid $insql",
            $params
        );
    }
}
