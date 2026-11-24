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

namespace mod_verifyid\completion;

#[\PHPUnit\Framework\Attributes\CoversClass(\mod_verifyid\completion\custom_completion::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\mod_verifyid\task\recalculate_completion::class)]
/**
 * Tests for the AI Verify ID custom completion rule.
 *
 * @package    mod_verifyid
 * @category   test
 * @copyright  2026 LMS Labs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \mod_verifyid\completion\custom_completion
 * @covers     \mod_verifyid\task\recalculate_completion
 */
final class custom_completion_test extends \advanced_testcase {
    /**
     * Creates a course with completion on, a learner and an activity that needs verification.
     *
     * @param int $rule completionverified value.
     * @return array [course, cm_info, learner, instance]
     */
    protected function setup_activity(int $rule = 1): array {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course(['enablecompletion' => 1]);
        $learner = $gen->create_and_enrol($course, 'student');
        $instance = $gen->create_module('verifyid', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionverified' => $rule,
        ]);
        $cm = get_fast_modinfo($course)->get_cm($instance->cmid);
        return [$course, $cm, $learner, $instance];
    }

    /**
     * The rule is defined, described and read from the cached course module data.
     */
    public function test_rule_definition(): void {
        [, $cm, $learner] = $this->setup_activity();
        $this->assertSame(['completionverified'], custom_completion::get_defined_custom_rules());
        $this->assertEquals(1, $cm->customdata['customcompletionrules']['completionverified']);
        $cc = new custom_completion($cm, (int) $learner->id);
        $this->assertTrue($cc->is_defined('completionverified'));
        $this->assertSame(['completionverified'], $cc->get_available_custom_rules());
        $this->assertArrayHasKey('completionverified', $cc->get_custom_rule_descriptions());
    }

    /**
     * Pending and rejected attempts do not complete the activity; a verified one does.
     */
    public function test_get_state(): void {
        [, $cm, $learner, $instance] = $this->setup_activity();
        $gen = $this->getDataGenerator()->get_plugin_generator('mod_verifyid');
        $cc = new custom_completion($cm, (int) $learner->id);
        $this->assertSame(COMPLETION_INCOMPLETE, $cc->get_state('completionverified'));

        $gen->create_attempt(['verifyidid' => $instance->id, 'userid' => $learner->id, 'status' => 'pending']);
        $gen->create_attempt(['verifyidid' => $instance->id, 'userid' => $learner->id, 'status' => 'rejected']);
        $this->assertSame(COMPLETION_INCOMPLETE, $cc->get_state('completionverified'));

        $gen->create_attempt(['verifyidid' => $instance->id, 'userid' => $learner->id, 'status' => 'verified']);
        $this->assertSame(COMPLETION_COMPLETE, $cc->get_state('completionverified'));

        // Another learner's verification does not count.
        $other = $this->getDataGenerator()->create_and_enrol($cm->get_course(), 'student');
        $this->assertSame(COMPLETION_INCOMPLETE, (new custom_completion($cm, (int) $other->id))->get_state('completionverified'));
    }

    /**
     * Moodle's own completion API marks the activity complete once the learner is verified.
     */
    public function test_completion_info_end_to_end(): void {
        [$course, $cm, $learner, $instance] = $this->setup_activity();
        $completion = new \completion_info($course);
        $this->assertEquals(COMPLETION_INCOMPLETE, $completion->get_data($cm, false, $learner->id)->completionstate);

        $this->getDataGenerator()->get_plugin_generator('mod_verifyid')->create_attempt(
            ['verifyidid' => $instance->id, 'userid' => $learner->id, 'status' => 'verified']
        );
        $completion->update_state($cm, COMPLETION_COMPLETE, $learner->id);
        $this->assertEquals(COMPLETION_COMPLETE, $completion->get_data($cm, false, $learner->id)->completionstate);
    }

    /**
     * With the rule switched off the activity has no custom rule to wait for.
     */
    public function test_rule_off(): void {
        [, $cm, $learner] = $this->setup_activity(0);
        $cc = new custom_completion($cm, (int) $learner->id);
        $this->assertFalse($cc->is_available('completionverified'));
        $this->assertSame([], $cc->get_available_custom_rules());
    }

    /**
     * The upgrade task completes the activity for learners who met the rule before it worked.
     */
    public function test_recalculate_completion_task(): void {
        [$course, $cm, $learner, $instance] = $this->setup_activity();
        $pending = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->getDataGenerator()->get_plugin_generator('mod_verifyid')->create_attempt(
            ['verifyidid' => $instance->id, 'userid' => $learner->id, 'status' => 'verified']
        );
        $this->getDataGenerator()->get_plugin_generator('mod_verifyid')->create_attempt(
            ['verifyidid' => $instance->id, 'userid' => $pending->id, 'status' => 'pending']
        );
        $completion = new \completion_info($course);
        $this->assertEquals(COMPLETION_INCOMPLETE, $completion->get_data($cm, false, $learner->id)->completionstate);

        $this->expectOutputRegex('/re-checked completion for 1 learner/');
        (new \mod_verifyid\task\recalculate_completion())->execute();

        $this->assertEquals(COMPLETION_COMPLETE, $completion->get_data($cm, false, $learner->id)->completionstate);
        $this->assertEquals(COMPLETION_INCOMPLETE, $completion->get_data($cm, false, $pending->id)->completionstate);
    }
}
