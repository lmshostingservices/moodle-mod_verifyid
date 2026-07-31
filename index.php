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
 * List of AI Verify ID instances in a course.
 *
 * @package    mod_verifyid
 * @copyright  2025 Essay Grader AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT); // Course ID.

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);

require_login($course);

$PAGE->set_url('/mod/verifyid/index.php', ['id' => $id]);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('modulenameplural', 'mod_verifyid'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'mod_verifyid'));

$verifyids = get_all_instances_in_course('verifyid', $course);

if (empty($verifyids)) {
    notice(get_string('thereareno', 'moodle', get_string('modulenameplural', 'mod_verifyid')),
           new moodle_url('/course/view.php', ['id' => $course->id]));
}

$table = new html_table();
$table->head = [
    get_string('name'),
    get_string('description'),
];
$table->align = ['left', 'left'];

foreach ($verifyids as $verifyid) {
    $link = html_writer::link(
        new moodle_url('/mod/verifyid/view.php', ['id' => $verifyid->coursemodule]),
        format_string($verifyid->name)
    );
    $description = format_text($verifyid->intro, $verifyid->introformat);
    $table->data[] = [$link, $description];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
