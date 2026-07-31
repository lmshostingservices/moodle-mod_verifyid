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
 * Language strings for AI Verify ID.
 *
 * @package    mod_verifyid
 * @copyright  2025 Essay Grader AI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Module name.
$string['modulename'] = 'AI Verify ID';
$string['modulenameplural'] = 'AI Verify IDs';
$string['modulename_help'] = 'AI Verify ID confirms student identity before assessments using AI-powered document reading and face comparison — ensuring the enrolled student is the one completing coursework.

Students complete a two-step process guided on screen. Step 1: upload a photo of a government-issued ID document (passport, driver\'s licence, or national ID card). Step 2: use their webcam to take a selfie, with a face-alignment oval guide and a live "face detected" indicator to help them position correctly. The AI reads the name from the uploaded ID using OCR, extracts the ID photo, and compares it to the webcam selfie using GPT-4o Vision to produce a percentage similarity score.

Three automatic decision bands are configurable: submissions at or above the Auto-approve threshold (default 80%) are verified instantly; submissions between the Review threshold (default 70%) and the Auto-approve threshold are placed in a pending queue for manual teacher review; submissions below the Review threshold are automatically rejected with a message directing the student to their teacher.

Teachers access a review dashboard showing all pending and historical submissions with the ID document, selfie, AI similarity score, name extracted from the ID, and registered Moodle name displayed side-by-side. Approvals or rejections can be accompanied by an optional comment returned to the student.

The verified ID photo integrates with the Webcam Proctoring quiz access rule, which can compare ongoing quiz snapshots against the student\'s approved ID photo for continuous identity assurance throughout an assessment. Activity completion can require a verified status. Credit cost: 1 credit per verification.';
$string['pluginname'] = 'AI Verify ID';
$string['pluginadministration'] = 'AI Verify ID administration';

// Settings.
$string['apiurl'] = 'API URL';
$string['apiurl_desc'] = 'The URL of the Essay Grader AI API server.';
$string['siteid'] = 'Site ID';
$string['siteid_desc'] = 'Your Moodle site identifier for the Essay Grader AI service.';
$string['apikey'] = 'API Key';
$string['apikey_desc'] = 'Your API key for the Essay Grader AI service.';
$string['thresholds_heading'] = 'Verification Thresholds';
$string['thresholds_desc'] = 'Configure the similarity thresholds for automatic verification decisions.';
$string['auto_approve_threshold'] = 'Auto-approve threshold';
$string['auto_approve_threshold_desc'] = 'Submissions with similarity scores at or above this percentage will be automatically approved (default: 80).';
$string['review_threshold'] = 'Review threshold';
$string['review_threshold_desc'] = 'Submissions with scores between this and auto-approve will require manual review. Below this will be auto-rejected (default: 70).';
$string['credits_heading'] = 'Credit Information';
$string['credits_info'] = 'Each verification costs 1 credit. Purchase credits at lms-labs.com.';

// Form fields.
$string['instructionsheader'] = 'Student Instructions';
$string['instructions'] = 'Custom instructions';
$string['instructions_help'] = 'Additional instructions to display to students before they verify their identity.';

// Completion.
$string['completionverified'] = 'Student must be verified';
$string['completionverified_help'] = 'If enabled, this activity is marked complete when the student\'s identity has been verified.';

// Capabilities.
$string['verifyid:view'] = 'View AI Verify ID activity';
$string['verifyid:submit'] = 'Submit identity verification';
$string['verifyid:review'] = 'Review identity verifications';
$string['verifyid:addinstance'] = 'Add a new AI Verify ID activity';

// View page.
$string['not_configured'] = 'AI Verify ID is not configured. Please contact your administrator to set up the API credentials.';
$string['credits_label'] = 'credits';
$string['step1_title'] = 'Step 1: Upload ID Document';
$string['step1_desc'] = 'Upload a clear photo of your government-issued ID (passport, driver\'s license, or national ID card).';
$string['step2_title'] = 'Step 2: Take Selfie';
$string['step2_desc'] = 'Position your face in the oval frame and take a photo. Make sure lighting is good and your face is clearly visible.';
$string['upload_id'] = 'Choose ID Document';
$string['start_camera'] = 'Start Camera';
$string['capture_photo'] = 'Capture Photo';
$string['retake_photo'] = 'Retake Photo';
$string['submit_verification'] = 'Submit for Verification';
$string['verifying'] = 'Verifying your identity...';
$string['face_hint_position'] = 'Position your face in the oval';
$string['face_hint_ready'] = 'Face detected - Ready to capture!';

// Status messages.
$string['status_pending'] = 'Pending Review';
$string['status_verified'] = 'Verified';
$string['status_rejected'] = 'Rejected';
$string['status_pending_desc'] = 'Your verification is being reviewed. You will be notified when it is complete.';
$string['status_verified_desc'] = 'Your identity has been verified. You can now proceed with your coursework.';
$string['status_rejected_desc'] = 'Your verification was not successful. Please contact your teacher for assistance.';
$string['similarity_score'] = 'Similarity Score';

// Review page.
$string['review_submissions'] = 'Review Submissions';
$string['pending_reviews'] = 'Pending Reviews';
$string['all_submissions'] = 'All Submissions';
$string['no_pending'] = 'No pending verifications to review.';
$string['no_submissions'] = 'No verification submissions yet.';
$string['student'] = 'Student';
$string['submitted'] = 'Submitted';
$string['id_document'] = 'ID Document';
$string['selfie'] = 'Selfie';
$string['webcam_photo'] = 'Webcam Photo';
$string['ai_score'] = 'AI Score';
$string['status'] = 'Status';
$string['actions'] = 'Actions';
$string['approve'] = 'Approve';
$string['reject'] = 'Reject';
$string['review_comment'] = 'Review Comment';
$string['review_comment_placeholder'] = 'Optional comment for the student...';

// Verification process.
$string['id_upload_guidance'] = 'Upload a clear photo of your ID document. The AI will read your name from the ID and compare your photo to verify your identity.';
$string['verification_complete'] = 'Verification complete';
$string['face_match'] = 'Face Match';
$string['name_match'] = 'Name Match';
$string['extracted_name'] = 'Name on ID';
$string['registered_name'] = 'Registered Name';
$string['document_type'] = 'Document Type';
$string['document_passport'] = 'Passport';
$string['document_drivers_license'] = 'Driver\'s License';
$string['document_national_id'] = 'National ID';
$string['document_student_id'] = 'Student ID';
$string['document_unknown'] = 'ID Document';
$string['match_excellent'] = 'Excellent match';
$string['match_good'] = 'Good match';
$string['match_partial'] = 'Partial match';
$string['match_poor'] = 'Poor match';

// Errors.
$string['error_no_id'] = 'Please upload your ID document.';
$string['error_no_selfie'] = 'Please take a selfie photo.';
$string['error_camera_access'] = 'Could not access camera. Please ensure you have granted camera permissions.';
$string['error_api'] = 'Verification failed. Please try again or contact support.';
$string['error_insufficient_credits'] = 'Insufficient credits for verification. Please contact your administrator.';

// Privacy.
$string['privacy:metadata:verifyid_attempts'] = 'Information about identity verification attempts.';
$string['privacy:metadata:verifyid_attempts:userid'] = 'The ID of the user who submitted the verification.';
$string['privacy:metadata:verifyid_attempts:idimage'] = 'The uploaded ID document image.';
$string['privacy:metadata:verifyid_attempts:selfie'] = 'The webcam selfie image.';
$string['privacy:metadata:verifyid_attempts:status'] = 'The verification status.';
$string['privacy:metadata:verifyid_attempts:similarity'] = 'The AI similarity score.';
$string['privacy:metadata:verifyid_attempts:timecreated'] = 'The time when the verification was submitted.';
