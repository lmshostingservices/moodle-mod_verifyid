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

namespace mod_verifyid;

use mod_verifyid\local\byok\engine;
use mod_verifyid\local\byok\js;

/**
 * Bring your own AI: the identity check made on this site's own AI instead of at LMS Labs.
 *
 * When the admin has chosen the site's own AI for vision in AI Grader Central Config (local_aiconfig), the face and
 * name check runs here, with the same instructions and scoring as the LMS Labs service, and uses no credits. It needs
 * an own key that can see pictures (OpenAI or Anthropic): Moodle's built-in AI cannot.
 *
 * @package    mod_verifyid
 * @copyright  2026 LMS Hosting Services
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class byok {
    /** @var string The component the site's own AI is chosen for. */
    const COMPONENT = 'mod_verifyid';

    /** @var int Seconds the check may take (the plugin waited 60 for LMS Labs). */
    const TIMEOUT = 60;

    /**
     * Whether the identity check runs on this site's own AI.
     *
     * @return bool
     */
    public static function uses(): bool {
        return class_exists('\\local_aiconfig\\byok\\ai') && \local_aiconfig\byok\ai::uses_own_ai(self::COMPONENT, 'vision');
    }

    /**
     * The answer to the plugin's /api/verify-id request, made on this site's own AI.
     *
     * @param string $body the JSON request the plugin would send to LMS Labs
     * @return array|null [HTTP status, JSON response], or null when the site uses LMS Labs credits
     */
    public static function answer(string $body): ?array {
        if (!self::uses()) {
            return null;
        }
        try {
            $request = js::parse($body);
        } catch (\Throwable $e) {
            $request = null;
        }
        if (!$request instanceof \stdClass) {
            return [400, js::stringify((object)['success' => false, 'error' => 'Invalid request',
                'errorCode' => 'VALIDATION_ERROR'])];
        }
        engine::$vision = function (string $prompt, array $images, int $maxtokens, float $temperature): string {
            return \local_aiconfig\byok\ai::text(self::COMPONENT, '', $prompt, ['images' => $images,
                'maxtokens' => $maxtokens, 'temperature' => $temperature, 'timeout' => self::TIMEOUT]);
        };
        [$status, $response] = engine::verify($request);
        return [$status, js::stringify($response)];
    }
}
