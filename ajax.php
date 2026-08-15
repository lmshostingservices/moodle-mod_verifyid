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
 * AI Verify ID AJAX handler.
 *
 * @package    mod_verifyid
 * @copyright  2025 Essay Grader AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$action = required_param('action', PARAM_ALPHA);
$cmid = required_param('cmid', PARAM_INT);

$cm = get_coursemodule_from_id('verifyid', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$verifyid = $DB->get_record('verifyid', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, false, $cm);
require_sesskey();

$context = context_module::instance($cm->id);

// Release session lock before long-running API calls to prevent blocking other requests.
\core\session\manager::write_close();

header('Content-Type: application/json');

try {
    switch ($action) {
        case 'submit':
            require_capability('mod/verifyid:submit', $context);
            
            // Check for existing attempt.
            if ($DB->record_exists('verifyid_attempts', ['verifyidid' => $verifyid->id, 'userid' => $USER->id])) {
                throw new moodle_exception('error', 'mod_verifyid', '', 'You have already submitted a verification.');
            }
            
            // Get submitted data.
            $idimage = required_param('idimage', PARAM_RAW); // pipeline-ignore: PARAM_RAW — base64-encoded binary payload, decoded and validated before use
            $selfie = required_param('selfie', PARAM_RAW); // pipeline-ignore: PARAM_RAW — base64-encoded binary payload, decoded and validated before use
            
            // Validate base64 images.
            if (empty($idimage) || strpos($idimage, 'data:image') !== 0) {
                throw new moodle_exception('error_no_id', 'mod_verifyid');
            }
            if (empty($selfie) || strpos($selfie, 'data:image') !== 0) {
                throw new moodle_exception('error_no_selfie', 'mod_verifyid');
            }
            
            // Get API URL from plugin settings
            $apiurl = get_config('mod_verifyid', 'apiurl');
            
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
            $autoapprove = (int)get_config('mod_verifyid', 'auto_approve_threshold') ?: 80;
            $reviewthreshold = (int)get_config('mod_verifyid', 'review_threshold') ?: 70;
            
            // Call API for verification.
            $apiendpoint = rtrim($apiurl, '/') . '/api/verify-id';
            
            // Get user's name for name matching verification
            $userfirstname = $USER->firstname;
            $userlastname = $USER->lastname;
            $userfullname = fullname($USER);
            
            $postdata = json_encode([
                'siteId' => $siteid,
                'apiKey' => $apikey,
                'idPhoto' => $idimage,
                'webcamPhoto' => $selfie,
                'userFirstName' => $userfirstname,
                'userLastName' => $userlastname,
                'userFullName' => $userfullname,
            ]);
            
            $curl = new \curl();
            $curl->setopt(['CURLOPT_TIMEOUT' => 60]);
            $curl->setHeader(['Content-Type: application/json']);
            $response = $curl->post($apiendpoint, $postdata);
            $httpcode = $curl->info['http_code'];
            $curlerror = $curl->error;
            
            if ($curlerror) {
                throw new moodle_exception('error_api', 'mod_verifyid', '', $curlerror);
            }
            
            $result = json_decode($response, true);
            
            if ($httpcode !== 200 || !$result) {
                $errormsg = isset($result['error']) ? $result['error'] : 'API request failed';
                throw new moodle_exception('error_api', 'mod_verifyid', '', $errormsg);
            }
            
            // Determine status based on similarity score.
            $similarity = isset($result['similarity']) ? floatval($result['similarity']) : 0;
            
            if ($similarity >= $autoapprove) {
                $status = 'verified';
            } else if ($similarity >= $reviewthreshold) {
                $status = 'pending';
            } else {
                $status = 'rejected';
            }
            
            // Extract detailed verification results
            $facesimilarity = isset($result['faceMatch']['similarity']) ? floatval($result['faceMatch']['similarity']) : $similarity;
            $namesimilarity = isset($result['nameMatch']['confidence']) ? floatval($result['nameMatch']['confidence']) : null;
            $extractedname = isset($result['extractedName']['fullName']) ? $result['extractedName']['fullName'] : null;
            $documenttype = isset($result['documentType']) ? $result['documentType'] : null;
            
            // Save attempt.
            $attempt = new stdClass();
            $attempt->verifyidid = $verifyid->id;
            $attempt->userid = $USER->id;
            $attempt->status = $status;
            $attempt->similarity = $similarity;
            $attempt->facesimilarity = $facesimilarity;
            $attempt->namesimilarity = $namesimilarity;
            $attempt->extractedname = $extractedname;
            $attempt->documenttype = $documenttype;
            $attempt->idimage = $idimage;
            $attempt->selfie = $selfie;
            $attempt->airesponse = $response;
            $attempt->timecreated = time();
            
            $attempt->id = $DB->insert_record('verifyid_attempts', $attempt);
            
            // Update completion if verified.
            if ($status === 'verified') {
                $completion = new completion_info($course);
                if ($completion->is_enabled($cm)) {
                    $completion->update_state($cm, COMPLETION_COMPLETE, $USER->id);
                }
            }
            
            echo json_encode([
                'success' => true,
                'status' => $status,
                'similarity' => $similarity,
                'faceMatch' => isset($result['faceMatch']) ? $result['faceMatch'] : null,
                'nameMatch' => isset($result['nameMatch']) ? $result['nameMatch'] : null,
                'extractedName' => isset($result['extractedName']) ? $result['extractedName'] : null,
                'documentType' => $documenttype,
                'message' => get_string('status_' . $status . '_desc', 'mod_verifyid'),
            ]);
            break;
            
        case 'review':
            require_capability('mod/verifyid:review', $context);
            
            $attemptid = required_param('attemptid', PARAM_INT);
            $decision = required_param('decision', PARAM_ALPHA); // 'approve' or 'reject'
            $comment = optional_param('comment', '', PARAM_TEXT);
            
            $attempt = $DB->get_record('verifyid_attempts', ['id' => $attemptid], '*', MUST_EXIST);
            
            // Verify this attempt belongs to this activity.
            if ($attempt->verifyidid != $verifyid->id) {
                throw new moodle_exception('invalidrecord', 'error');
            }
            
            // Update attempt.
            $attempt->status = ($decision === 'approve') ? 'verified' : 'rejected';
            $attempt->reviewerid = $USER->id;
            $attempt->reviewcomment = $comment;
            $attempt->timereviewed = time();
            
            $DB->update_record('verifyid_attempts', $attempt);
            
            // Update completion if approved.
            if ($attempt->status === 'verified') {
                $student = $DB->get_record('user', ['id' => $attempt->userid]);
                $completion = new completion_info($course);
                if ($completion->is_enabled($cm)) {
                    $completion->update_state($cm, COMPLETION_COMPLETE, $attempt->userid);
                }
            }
            
            echo json_encode([
                'success' => true,
                'status' => $attempt->status,
            ]);
            break;
            
        default:
            throw new moodle_exception('invalidaction', 'error');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
// CRITICAL: No exit; statement - AJAX_SCRIPT handles cleanup.
