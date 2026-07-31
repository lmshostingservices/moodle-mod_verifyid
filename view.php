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
 * AI Verify ID view page.
 *
 * v3.0.0 - MINIMAL SKELETON: No custom CSS, no JavaScript, pure Moodle output.
 *
 * @package    mod_verifyid
 * @copyright  2025 Essay Grader AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('verifyid', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$verifyid = $DB->get_record('verifyid', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/verifyid:view', $context);

$cansubmit = has_capability('mod/verifyid:submit', $context);
$canreview = has_capability('mod/verifyid:review', $context);

$PAGE->set_url('/mod/verifyid/view.php', ['id' => $id]);
$PAGE->set_title(format_string($verifyid->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');

verifyid_view($verifyid, $course, $cm, $context);

echo $OUTPUT->header();

// Explicitly include aiconfig lib.php if available
$aiconfiglib = $CFG->dirroot . '/local/aiconfig/lib.php';
if (file_exists($aiconfiglib)) {
    require_once($aiconfiglib);
}

// Priority 1: Central Config (recommended for multi-plugin setups)
$siteid = '';
$apikey = '';
if (function_exists('local_aiconfig_get_siteid')) {
    $siteid = local_aiconfig_get_siteid();
}
if (function_exists('local_aiconfig_get_apikey')) {
    $apikey = local_aiconfig_get_apikey();
}

// Priority 2: Plugin settings as fallback
if (empty($siteid)) {
    $siteid = get_config('mod_verifyid', 'siteid');
}
if (empty($apikey)) {
    $apikey = get_config('mod_verifyid', 'apikey');
}

if (empty($siteid) || empty($apikey)) {
    echo $OUTPUT->notification(get_string('not_configured', 'mod_verifyid'), 'warning');
    echo $OUTPUT->footer();
    return;
}

if ($canreview) {
    $pendingcount = $DB->count_records('verifyid_attempts', [
        'verifyidid' => $verifyid->id,
        'status' => 'pending'
    ]);
    
    $reviewurl = new moodle_url('/mod/verifyid/review.php', ['id' => $cm->id]);
    echo html_writer::start_div('mb-3');
    echo html_writer::link($reviewurl, get_string('review_submissions', 'mod_verifyid') . 
        ($pendingcount > 0 ? ' (' . $pendingcount . ')' : ''), 
        ['class' => 'btn btn-secondary']);
    echo html_writer::end_div();
}

if ($cansubmit) {
    $attempt = $DB->get_record('verifyid_attempts', [
        'verifyidid' => $verifyid->id,
        'userid' => $USER->id
    ], '*', IGNORE_MULTIPLE);
    
    if ($attempt) {
        echo html_writer::start_div('alert alert-' . ($attempt->status === 'verified' ? 'success' : 
            ($attempt->status === 'pending' ? 'warning' : 'danger')));
        echo html_writer::tag('h4', get_string('status_' . $attempt->status, 'mod_verifyid'));
        echo html_writer::tag('p', get_string('status_' . $attempt->status . '_desc', 'mod_verifyid'));
        if (!empty($attempt->similarity)) {
            echo html_writer::tag('p', get_string('similarity_score', 'mod_verifyid') . ': ' . $attempt->similarity . '%');
        }
        echo html_writer::tag('p', get_string('submitted', 'mod_verifyid') . ': ' . userdate($attempt->timecreated));
        echo html_writer::end_div();
    } else {
        echo $OUTPUT->render_from_template('mod_verifyid/student_view', [
            'cmid' => $cm->id,
            'sesskey' => sesskey(),
            'instructions' => format_text($verifyid->instructions, FORMAT_HTML),
            'hasinstructions' => !empty($verifyid->instructions),
        ]);
    }
} else if (!$canreview) {
    echo $OUTPUT->notification(get_string('nopermissions', 'error', 'view'), 'warning');
}

echo $OUTPUT->footer();
