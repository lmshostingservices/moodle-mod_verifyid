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

/**
 * Tests Bring your own AI: the identity check on the site's own AI, with the service's scoring, at no charge.
 *
 * @package    mod_verifyid
 * @copyright  2026 LMS Hosting Services
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \mod_verifyid\byok
 * @covers     \mod_verifyid\local\byok\engine
 */
#[\PHPUnit\Framework\Attributes\CoversClass(byok::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(engine::class)]
final class byok_test extends \advanced_testcase {
    /** @var array Request bodies the mocked OpenAI received. */
    private array $requests = [];

    /** @var array Answers the mocked OpenAI gives, in order. */
    private array $answers = [];

    /** @var string A small PNG as a data: URL. */
    private string $png = '';

    /**
     * Licence the site for Bring your own AI on a test key, with vision on OpenAI and no LMS Labs credentials.
     */
    protected function setUp(): void {
        parent::setUp();
        if (!class_exists('\local_aiconfig\byok\ai')) {
            $this->markTestSkipped('AI Grader Central Config with Bring your own AI is not installed.');
        }
        $this->resetAfterTest();
        $pair = sodium_crypto_sign_keypair();
        set_config('testpublickey', base64_encode(sodium_crypto_sign_publickey($pair)), 'local_aiconfig');
        $payload = json_encode(['v' => 1, 'id' => 'lic_t', 'site' => \local_aiconfig\byok\licence::site_host(),
            'plan' => 'pro', 'byok' => true, 'products' => ['*'], 'sub' => 'sub_t', 'exp' => time() + DAYSECS]);
        $b64 = fn(string $s): string => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
        set_config('licencekey', $b64($payload) . '.' .
            $b64(sodium_crypto_sign_detached($payload, sodium_crypto_sign_secretkey($pair))), 'local_aiconfig');
        set_config('src_vision', 'ownkey', 'local_aiconfig');
        set_config('vendor_vision', 'openai', 'local_aiconfig');
        set_config('key_openai', 'sk-test', 'local_aiconfig');
        $im = imagecreatetruecolor(4, 4);
        ob_start();
        imagepng($im);
        $this->png = 'data:image/png;base64,' . base64_encode((string)ob_get_clean());
        $this->requests = [];
        $this->answers = [];
        $GLOBALS['local_aiconfig_byok_mock'] = function ($url, $headers, $body, $vendor, $raw = false) {
            $this->requests[] = $body;
            $answer = array_shift($this->answers);
            if ($answer === null) {
                throw new \moodle_exception('byok_callfailed', 'local_aiconfig', '', (object)['vendor' => $vendor,
                    'detail' => 'Overloaded']);
            }
            return ['choices' => [['message' => ['content' => $answer], 'finish_reason' => 'stop']]];
        };
    }

    protected function tearDown(): void {
        unset($GLOBALS['local_aiconfig_byok_mock']);
        engine::$vision = null;
        parent::tearDown();
    }

    /**
     * The plugin's request for a learner.
     *
     * @param array $extra
     * @return string
     */
    private function request(array $extra = []): string {
        return json_encode($extra + ['siteId' => '', 'apiKey' => '', 'idPhoto' => $this->png, 'webcamPhoto' => $this->png,
            'userFirstName' => 'Mia', 'userLastName' => 'Tran', 'userFullName' => 'Mia Tran']);
    }

    public function test_check_on_own_ai(): void {
        $fence = str_repeat(chr(96), 3);
        $this->answers = ["Result:\n" . $fence . "json\n" . json_encode(['documentType' => 'drivers_license',
            'extractedName' => ['fullName' => 'TRAN, Mia', 'readable' => true],
            'nameMatch' => ['matches' => true, 'confidence' => 95, 'details' => 'Same name, surname first.'],
            'faceMatch' => ['similarity' => 82, 'idPhotoFaceDetected' => true, 'webcamFaceDetected' => true,
                'details' => 'Same person.', 'concerns' => 'None']]) . "\n" . $fence];
        [$status, $json] = byok::answer($this->request());
        $this->assertSame(200, $status);
        $result = json_decode($json, true);
        // The service's score: 60% face, 40% name, rounded.
        $this->assertSame(87, $result['similarity']);
        $this->assertSame('drivers_license', $result['documentType']);
        $this->assertSame(['idPhoto' => true, 'webcamPhoto' => true], $result['facesDetected']);
        $this->assertSame(['details' => 'Same person.', 'concerns' => 'None'], $result['analysis']);

        $body = $this->requests[0];
        $this->assertSame(800, $body['max_completion_tokens']);
        $this->assertSame(0.2, $body['temperature']);
        $content = end($body['messages'])['content'];
        $this->assertStringContainsString('The user\'s registered name is: "Mia Tran".', $content[0]['text']);
        $this->assertSame([$this->png, $this->png], [$content[1]['image_url']['url'], $content[2]['image_url']['url']]);
    }

    public function test_face_only_and_failures(): void {
        // Without a name the score is the face score as given.
        $this->answers = ['{"faceMatch":{"similarity":64}}'];
        $body = json_encode(['idPhoto' => $this->png, 'webcamPhoto' => $this->png]);
        [$status, $json] = byok::answer($body);
        $this->assertSame(200, $status);
        $this->assertSame(64, json_decode($json)->similarity);
        $this->assertStringNotContainsString('IMPORTANT: The user', end($this->requests[0]['messages'])['content'][0]['text']);

        // An answer without JSON, a failed AI call, missing photos and photos that are not images.
        $this->answers = ['I cannot help with that.'];
        $this->assertSame([500, 'PARSE_ERROR'], $this->outcome(byok::answer($this->request())));
        $this->assertSame([500, 'AI_ERROR'], $this->outcome(byok::answer($this->request())));
        $this->assertSame([400, 'VALIDATION_ERROR'], $this->outcome(byok::answer($this->request(['idPhoto' => '']))));
        $this->assertSame([400, 'VALIDATION_ERROR'], $this->outcome(byok::answer('[]')));
        $this->assertSame([500, 'AI_ERROR'], $this->outcome(byok::answer($this->request(['webcamPhoto' => 'http://x/y.png']))));
        $this->assertCount(3, $this->requests);
    }

    public function test_credits_when_not_chosen(): void {
        set_config('src_vision', 'credits', 'local_aiconfig');
        $this->assertFalse(byok::uses());
        $this->assertNull(byok::answer($this->request()));
        $this->assertSame([], $this->requests);
    }

    /**
     * Status and error code of an answer.
     *
     * @param array $answer
     * @return array
     */
    private function outcome(array $answer): array {
        return [$answer[0], json_decode($answer[1])->errorCode];
    }
}
