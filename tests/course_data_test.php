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
 * Tests for the course data model (progress, states, continue).
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles;

use format_customtiles\local\course_data;

/**
 * Course data tests.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \format_customtiles\local\course_data
 */
final class course_data_test extends \advanced_testcase {
    /**
     * Build a course with 2 sections of 2 tracked pages each and a student.
     *
     * @return array [course, user, cms]
     */
    private function setup_course(): array {
        $gen = $this->getDataGenerator();
        $course = $gen->create_course(['format' => 'customtiles', 'numsections' => 2, 'enablecompletion' => 1]);
        $user = $gen->create_and_enrol($course, 'student');
        $cms = [];
        foreach ([1, 1, 2, 2] as $section) {
            $cms[] = $gen->create_module('page', ['course' => $course->id, 'section' => $section,
                'completion' => COMPLETION_TRACKING_MANUAL]);
        }
        return [$course, $user, $cms];
    }

    /**
     * Progress per section and overall, and cache invalidation on completion.
     */
    public function test_progress_and_cache(): void {
        $this->resetAfterTest();
        [$course, $user, $cms] = $this->setup_course();
        $this->setUser($user);
        $format = course_get_format($course);
        $data = new course_data($format);
        $this->assertSame(['done' => 0, 'total' => 4, 'percent' => 0], $data->get_overall());

        $completion = new \completion_info(get_course($course->id));
        $cm = get_fast_modinfo($course)->get_cm($cms[0]->cmid);
        $completion->update_state($cm, COMPLETION_COMPLETE, $user->id);

        $data = new course_data(course_get_format($course->id));
        $this->assertSame(25, $data->get_overall()['percent']);
        $sections = $data->get_sections();
        $this->assertSame('inprogress', $sections[1]['state']);
        $this->assertSame(50, $sections[1]['percent']);
        $this->assertSame(0, $sections[2]['percent']);

        $completion->update_state(get_fast_modinfo($course)->get_cm($cms[1]->cmid), COMPLETION_COMPLETE, $user->id);
        $data = new course_data(course_get_format($course->id));
        $this->assertSame('completed', $data->get_sections()[1]['state']);
    }

    /**
     * The continue target is the first pending activity, then the remembered one.
     */
    public function test_continue(): void {
        $this->resetAfterTest();
        [$course, $user, $cms] = $this->setup_course();
        $this->setUser($user);
        $data = new course_data(course_get_format($course));
        $target = $data->get_continue();
        $this->assertSame((int)$cms[0]->cmid, $target['cmid']);
        $this->assertTrue($target['isfirst']);

        course_data::remember_last_activity((int)$course->id, (int)$cms[2]->cmid);
        $data = new course_data(course_get_format($course->id));
        $target = $data->get_continue();
        $this->assertSame((int)$cms[2]->cmid, $target['cmid']);
        $this->assertFalse($target['isfirst']);

        // Completing the remembered one moves on to the next pending activity after it.
        $completion = new \completion_info(get_course($course->id));
        $completion->update_state(get_fast_modinfo($course)->get_cm($cms[2]->cmid), COMPLETION_COMPLETE, $user->id);
        $data = new course_data(course_get_format($course->id));
        $this->assertSame((int)$cms[3]->cmid, $data->get_continue()['cmid']);
    }

    /**
     * Restricted sections are reported as locked with a reason.
     */
    public function test_locked_section(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $user] = $this->setup_course();
        $future = time() + YEARSECS;
        $restriction = json_encode(['op' => '&', 'c' => [['type' => 'date', 'd' => '>=', 't' => $future]], 'showc' => [true]]);
        $DB->set_field('course_sections', 'availability', $restriction, ['course' => $course->id, 'section' => 2]);
        rebuild_course_cache($course->id, true);
        $this->setUser($user);
        $sections = (new course_data(course_get_format($course->id)))->get_sections();
        $this->assertSame('locked', $sections[2]['state']);
        $this->assertTrue($sections[2]['locked']);
        $this->assertNotEmpty($sections[2]['reason']);
        $this->assertSame('', $sections[2]['url']);
    }

    /**
     * Privacy export of the last-activity preference.
     */
    public function test_privacy_export(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        set_user_preference('format_customtiles_last_5', 12, $user);
        $provider = new \ReflectionClass(\format_customtiles\privacy\provider::class);
        $this->assertTrue($provider->implementsInterface(\core_privacy\local\request\user_preference_provider::class));
        \format_customtiles\privacy\provider::export_user_preferences($user->id);
        $writer = \core_privacy\local\request\writer::with_context(\context_system::instance());
        $prefs = $writer->get_user_preferences('format_customtiles');
        $this->assertSame('12', (string)$prefs->format_customtiles_last_5->value);
    }
}
