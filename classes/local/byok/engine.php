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

namespace mod_verifyid\local\byok;

/**
 * The LMS Labs identity check (POST /api/verify-id), ported to run on the site's own AI.
 *
 * The request checks, the instructions, the AI settings and the shaping of the answer (including the 60/40 face and
 * name score) are the service's. Only the call to the AI goes to the site's own AI, through the hook below. The
 * behaviour was compared with the service's code on generated requests and AI answers. The site credentials and
 * credit checks are left out: nothing is charged.
 *
 * @package    mod_verifyid
 * @copyright  2026 LMS Hosting Services
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class engine {
    /**
     * @var callable|null fn(string $prompt, array $images, int $maxtokens, float $temperature): string, where $images
     *      holds ['mime' => ..., 'data' => raw bytes] for the ID photo, then the webcam photo
     */
    public static $vision = null;

    /** @var array The request fields the service checks, in its order: [name, required message]. */
    const FIELDS = [['idPhoto', 'ID photo is required'], ['webcamPhoto', 'Webcam photo is required'],
        ['userFirstName', null], ['userLastName', null], ['userFullName', null]];

    /**
     * POST /api/verify-id: compare the face on the ID with the webcam photo, and the name on the ID with the user's.
     *
     * @param \stdClass $body the request the plugin sends to LMS Labs
     * @return array [HTTP status, response object]
     */
    public static function verify(\stdClass $body): array {
        foreach (self::FIELDS as [$field, $required]) {
            $exists = property_exists($body, $field);
            $value = $exists ? $body->$field : null;
            $message = null;
            if (!$exists) {
                $message = $required === null ? null : 'Required';
            } else if (!is_string($value)) {
                $message = 'Expected string, received ' . self::type_name($value);
            } else if ($required !== null && $value === '') {
                $message = $required;
            }
            if ($message !== null) {
                return [400, (object)['success' => false, 'error' => $message, 'errorCode' => 'VALIDATION_ERROR']];
            }
        }
        $first = $body->userFirstName ?? '';
        $last = $body->userLastName ?? '';
        $full = $body->userFullName ?? '';
        $hasname = $first !== '' || $last !== '' || $full !== '';
        $namecontext = $hasname ? "\n\nIMPORTANT: The user's registered name is: \"" .
            ($full !== '' ? $full : js::trim("{$first} {$last}")) . '". Compare this EXACTLY to the name on the ID document.' : '';
        $prompt = self::prompt($namecontext);
        $failed = [500, (object)['success' => false, 'error' => 'AI analysis failed', 'errorCode' => 'AI_ERROR']];
        $images = [self::image($body->idPhoto), self::image($body->webcamPhoto)];
        if (in_array(null, $images, true)) {
            // The service passes the photos to the AI as given, and the AI refuses one that is not an image.
            return $failed;
        }
        try {
            $content = (string)(self::$vision)($prompt, $images, 800, 0.2);
        } catch (\Throwable $e) {
            return $failed;
        }
        $analysis = null;
        if (preg_match('/\{.*\}/s', $content, $m)) {
            try {
                $analysis = js::parse($m[0]);
            } catch (\Throwable $e) {
                $analysis = null;
            }
        }
        if ($analysis === null) {
            return [500, (object)['success' => false, 'error' => 'Failed to analyze images', 'errorCode' => 'PARSE_ERROR']];
        }
        $face = js::get($analysis, 'faceMatch');
        $namematch = js::get($analysis, 'nameMatch');
        $facescore = js::get($face, 'similarity');
        $facescore = js::truthy($facescore) ? $facescore : 0;
        $namescore = js::get($namematch, 'confidence');
        $namescore = js::truthy($namescore) ? $namescore : 0;
        $similarity = $hasname ? self::round(self::number($facescore) * 0.6 + self::number($namescore) * 0.4) : $facescore;
        $or = fn($value, $default) => js::truthy($value) ? $value : $default;
        $response = (object)['success' => true, 'similarity' => $similarity];
        foreach (['documentType', 'extractedName', 'nameMatch', 'faceMatch'] as $key) {
            // JSON.stringify leaves out a missing value.
            if ($analysis instanceof \stdClass && property_exists($analysis, $key)) {
                $response->$key = $analysis->$key;
            }
        }
        $response->facesDetected = (object)['idPhoto' => $or(js::get($face, 'idPhotoFaceDetected'), false),
            'webcamPhoto' => $or(js::get($face, 'webcamFaceDetected'), false)];
        $response->analysis = (object)['details' => $or(js::get($face, 'details'), ''),
            'concerns' => $or(js::get($face, 'concerns'), '')];
        return [200, $response];
    }

    /**
     * The service's instructions to the AI.
     *
     * @param string $namecontext the registered-name paragraph, or ''
     * @return string
     */
    public static function prompt(string $namecontext): string {
        return "You are an identity verification expert analyzing an ID document and webcam photo.\n\n" .
            "TASKS:\n" .
            "1. IDENTIFY the type of ID document (driver's license, passport, national ID, student ID, etc.)\n" .
            "2. EXTRACT the full name from the ID document (read the text carefully)\n" .
            "3. COMPARE the face in the ID photo to the webcam selfie photo\n" .
            "4. Determine if they appear to be the SAME PERSON{$namecontext}\n\n" .
            "Respond with a JSON object containing:\n" .
            "{\n" .
            "  \"documentType\": \"<type of ID: 'passport', 'drivers_license', 'national_id', 'student_id', or 'unknown'>\",\n" .
            "  \"extractedName\": {\n" .
            "    \"fullName\": \"<full name as it appears on the ID>\",\n" .
            "    \"firstName\": \"<first name or given name>\",\n" .
            "    \"lastName\": \"<last name or family name>\",\n" .
            "    \"readable\": <boolean - was the name clearly readable>\n" .
            "  },\n" .
            "  \"nameMatch\": {\n" .
            "    \"matches\": <boolean - does the extracted name match the user's registered name>,\n" .
            "    \"confidence\": <number 0-100 representing confidence in name match>,\n" .
            "    \"details\": \"<explanation of any name differences or why it matches/doesn't match>\"\n" .
            "  },\n" .
            "  \"faceMatch\": {\n" .
            "    \"similarity\": <number 0-100 representing confidence the faces are the same person>,\n" .
            "    \"idPhotoFaceDetected\": <boolean>,\n" .
            "    \"webcamFaceDetected\": <boolean>,\n" .
            "    \"details\": \"<brief explanation of face comparison>\",\n" .
            "    \"concerns\": \"<any concerns, or 'None'>\"\n" .
            "  }\n" .
            "}\n\n" .
            "GUIDELINES:\n" .
            '- For name matching: Be flexible with name order (some IDs show LASTNAME, FIRSTNAME), accents, and minor ' .
            "spelling variations. Middle names may be present or absent.\n" .
            "- A name match confidence of 80%+ means the names clearly refer to the same person.\n" .
            '- For face matching: Focus on key facial features. Be conservative - only give 80%+ if confident it\'s the ' .
            "same person.\n" .
            '- If you cannot read the name or detect a face, explain in the appropriate details/concerns field.';
    }

    /**
     * A photo sent as a data: URL, as the AI receives it.
     *
     * @param string $url
     * @return array|null ['mime' => ..., 'data' => raw bytes], or null when it is not a base64 image data URL
     */
    public static function image(string $url): ?array {
        if (!preg_match('~^data:(image/[a-z0-9.+-]+)(?:;[^,;]*)*;base64,([A-Za-z0-9+/=\s]*)$~iD', $url, $m)) {
            return null;
        }
        $data = base64_decode($m[2], true);
        return $data === false || $data === '' ? null : ['mime' => strtolower($m[1]), 'data' => $data];
    }

    /**
     * The type name zod reports for a value that is not a string.
     *
     * @param mixed $value
     * @return string
     */
    private static function type_name($value): string {
        if ($value === null) {
            return 'null';
        }
        if (is_bool($value)) {
            return 'boolean';
        }
        if (is_int($value) || is_float($value)) {
            return is_float($value) && is_nan($value) ? 'nan' : 'number';
        }
        return is_array($value) ? 'array' : 'object';
    }

    /**
     * JavaScript's Math.round().
     *
     * @param float $x
     * @return float
     */
    private static function round(float $x): float {
        if (!is_finite($x)) {
            return $x;
        }
        $r = floor($x);
        return $x - $r >= 0.5 ? $r + 1 : $r;
    }

    /**
     * A value as JavaScript's arithmetic reads it (Number(value)).
     *
     * @param mixed $value
     * @return float
     */
    private static function number($value): float {
        if ($value === null || is_bool($value)) {
            return (float)(bool)$value;
        }
        if (is_int($value) || is_float($value)) {
            return (float)$value;
        }
        if ($value instanceof \stdClass) {
            return NAN;
        }
        $s = js::trim(is_array($value) ? js::str($value) : (string)$value);
        if ($s === '') {
            return 0.0;
        }
        if (preg_match('/^[+-]?(\d+\.?\d*|\.\d+)([eE][+-]?\d+)?$/D', $s)) {
            return (float)$s;
        }
        if (preg_match('/^0([xob])([0-9a-z]+)$/Di', $s, $m)) {
            $base = ['x' => 16, 'o' => 8, 'b' => 2][strtolower($m[1])];
            $valid = [16 => '/^[0-9a-f]+$/i', 8 => '/^[0-7]+$/', 2 => '/^[01]+$/'][$base];
            return preg_match($valid, $m[2]) ? (float)base_convert(strtolower($m[2]), $base, 10) : NAN;
        }
        if (preg_match('/^([+-]?)Infinity$/D', $s, $m)) {
            return $m[1] === '-' ? -INF : INF;
        }
        return NAN;
    }
}
