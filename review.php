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
 * AI Verify ID review page for teachers.
 *
 * v3.0.5 - Compact layout with search functionality.
 *
 * @package    mod_verifyid
 * @copyright  2025 Essay Grader AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT);
$showpending = optional_param('pending', 1, PARAM_INT);

$cm = get_coursemodule_from_id('verifyid', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$verifyid = $DB->get_record('verifyid', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/verifyid:review', $context);

$PAGE->set_url('/mod/verifyid/review.php', ['id' => $id, 'pending' => $showpending]);
$PAGE->set_title(format_string($verifyid->name) . ' - ' . get_string('review_submissions', 'mod_verifyid'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->navbar->add(get_string('review_submissions', 'mod_verifyid'));

echo $OUTPUT->header();

$params = ['verifyidid' => $verifyid->id];
if ($showpending) {
    $params['status'] = 'pending';
}

$attempts = $DB->get_records('verifyid_attempts', $params, 'timecreated DESC');
$pendingcount = $DB->count_records('verifyid_attempts', [
    'verifyidid' => $verifyid->id,
    'status' => 'pending'
]);

echo html_writer::tag('h3', get_string('review_submissions', 'mod_verifyid'));

$pendingurl = new moodle_url('/mod/verifyid/review.php', ['id' => $id, 'pending' => 1]);
$allurl = new moodle_url('/mod/verifyid/review.php', ['id' => $id, 'pending' => 0]);

echo html_writer::start_div('mb-3');
echo html_writer::link($pendingurl, get_string('pending_reviews', 'mod_verifyid') . 
    ($pendingcount > 0 ? ' (' . $pendingcount . ')' : ''), 
    ['class' => 'btn ' . ($showpending ? 'btn-primary' : 'btn-secondary') . ' mr-2']);
echo html_writer::link($allurl, get_string('all_submissions', 'mod_verifyid'), 
    ['class' => 'btn ' . (!$showpending ? 'btn-primary' : 'btn-secondary')]);
echo html_writer::end_div();

// Search box
echo html_writer::start_div('', ['style' => 'margin-bottom: 20px;']);
echo html_writer::tag('input', '', [
    'type' => 'text',
    'id' => 'student-search',
    'placeholder' => 'Search student name...',
    'style' => 'width: 100%; max-width: 400px; padding: 10px 16px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; outline: none; transition: border-color 0.2s;',
    'oninput' => 'filterStudents(this.value)'
]);
echo html_writer::end_div();

if (empty($attempts)) {
    echo html_writer::div(
        $showpending ? get_string('no_pending', 'mod_verifyid') : get_string('no_submissions', 'mod_verifyid'),
        '',
        ['style' => 'background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); border: 1px solid #cbd5e1; border-radius: 12px; padding: 40px; text-align: center; color: #64748b; font-size: 1.1rem;']
    );
} else {
    echo html_writer::start_div('', ['id' => 'submissions-container']);
    
    foreach ($attempts as $attempt) {
        $user = $DB->get_record('user', ['id' => $attempt->userid]);
        $fullname = fullname($user);
        
        // Status-specific styling - light borders only
        if ($attempt->status === 'verified') {
            $bordercolor = '#10b981';
            $iconsvg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>';
            $statuscolor = '#10b981';
        } else if ($attempt->status === 'pending') {
            $bordercolor = '#f59e0b';
            $iconsvg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>';
            $statuscolor = '#f59e0b';
        } else {
            $bordercolor = '#ef4444';
            $iconsvg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
            $statuscolor = '#ef4444';
        }
        
        // Compact card container with thin border
        echo html_writer::start_div('submission-card', [
            'style' => "margin-bottom: 16px; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border: 0.5px solid {$bordercolor}; background: #ffffff;",
            'data-student-name' => strtolower($fullname)
        ]);
        
        // Compact header - white background with colored icon
        echo html_writer::start_div('', ['style' => "padding: 14px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9;"]);
        
        // Student name with icon
        echo html_writer::start_div('', ['style' => 'display: flex; align-items: center; gap: 10px;']);
        echo html_writer::div($iconsvg, '', ['style' => 'display: flex; align-items: center;']);
        echo html_writer::tag('span', $fullname, ['style' => 'font-weight: 600; font-size: 1rem; color: #1e293b;']);
        echo html_writer::end_div();
        
        // Score and status badges
        echo html_writer::start_div('', ['style' => 'display: flex; align-items: center; gap: 10px;']);
        if (!empty($attempt->similarity)) {
            echo html_writer::span($attempt->similarity . '%', '', ['style' => 'background: #f1f5f9; color: #475569; padding: 4px 12px; border-radius: 16px; font-weight: 600; font-size: 0.85rem;']);
        }
        echo html_writer::span(get_string('status_' . $attempt->status, 'mod_verifyid'), '', ['style' => "color: {$statuscolor}; padding: 4px 12px; border-radius: 16px; font-weight: 500; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.03em; border: 1px solid {$bordercolor};"]);
        echo html_writer::end_div();
        echo html_writer::end_div();
        
        // Compact card body with images
        echo html_writer::start_div('', ['style' => 'padding: 16px 20px;']);
        echo html_writer::start_div('', ['style' => 'display: grid; grid-template-columns: 1fr 1fr; gap: 16px;']);
        
        // ID Image column
        echo html_writer::start_div('', ['style' => 'text-align: center;']);
        echo html_writer::tag('p', get_string('id_document', 'mod_verifyid'), ['style' => 'margin: 0 0 8px 0; color: #94a3b8; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;']);
        if (!empty($attempt->idimage)) {
            echo html_writer::img($attempt->idimage, 'ID Document', [
                'style' => 'max-height: 160px; max-width: 100%; object-fit: contain; border-radius: 8px; border: 1px solid #e2e8f0;'
            ]);
        } else {
            echo html_writer::div('No image', '', ['style' => 'background: #f8fafc; padding: 40px 20px; border-radius: 8px; color: #94a3b8; font-size: 0.85rem;']);
        }
        echo html_writer::end_div();
        
        // Selfie column
        echo html_writer::start_div('', ['style' => 'text-align: center;']);
        echo html_writer::tag('p', get_string('webcam_photo', 'mod_verifyid'), ['style' => 'margin: 0 0 8px 0; color: #94a3b8; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;']);
        if (!empty($attempt->selfie)) {
            echo html_writer::img($attempt->selfie, 'Webcam Photo', [
                'style' => 'max-height: 160px; max-width: 100%; object-fit: contain; border-radius: 8px; border: 1px solid #e2e8f0;'
            ]);
        } else {
            echo html_writer::div('No image', '', ['style' => 'background: #f8fafc; padding: 40px 20px; border-radius: 8px; color: #94a3b8; font-size: 0.85rem;']);
        }
        echo html_writer::end_div();
        
        echo html_writer::end_div(); // grid
        
        // Submission time - compact footer
        echo html_writer::tag('p', get_string('submitted', 'mod_verifyid') . ': ' . userdate($attempt->timecreated), ['style' => 'margin: 12px 0 0 0; color: #94a3b8; font-size: 0.8rem;']);
        
        echo html_writer::end_div(); // card-body
        
        // Action buttons for pending
        if ($attempt->status === 'pending') {
            echo html_writer::start_div('', ['style' => 'background: #fafafa; padding: 12px 20px; border-top: 1px solid #f1f5f9; display: flex; gap: 10px;']);
            echo html_writer::link('#', get_string('approve', 'mod_verifyid'), 
                ['style' => 'display: inline-flex; align-items: center; gap: 6px; background: #10b981; color: #ffffff; padding: 8px 18px; border-radius: 6px; font-weight: 500; font-size: 0.9rem; text-decoration: none;', 'onclick' => "doReview({$attempt->id}, 'approve'); return false;"]);
            echo html_writer::link('#', get_string('reject', 'mod_verifyid'), 
                ['style' => 'display: inline-flex; align-items: center; gap: 6px; background: #ef4444; color: #ffffff; padding: 8px 18px; border-radius: 6px; font-weight: 500; font-size: 0.9rem; text-decoration: none;', 'onclick' => "doReview({$attempt->id}, 'reject'); return false;"]);
            echo html_writer::end_div();
        }
        
        echo html_writer::end_div(); // card
    }
    
    echo html_writer::end_div(); // submissions-container
}

echo html_writer::script("
function filterStudents(query) {
    var cards = document.querySelectorAll('.submission-card');
    var lowerQuery = query.toLowerCase().trim();
    cards.forEach(function(card) {
        var name = card.getAttribute('data-student-name');
        if (lowerQuery === '' || name.indexOf(lowerQuery) !== -1) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

function doReview(attemptId, decision) {
    var formData = new FormData();
    formData.append('action', 'review');
    formData.append('cmid', {$cm->id});
    formData.append('attemptid', attemptId);
    formData.append('decision', decision);
    formData.append('sesskey', '" . sesskey() . "');
    
    fetch(M.cfg.wwwroot + '/mod/verifyid/ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.error || 'Review failed');
        }
    });
}
");

echo $OUTPUT->footer();
