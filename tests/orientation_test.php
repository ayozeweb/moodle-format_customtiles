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
 * Tests for the orientation features (context line, course map, skip link, logos).
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles;

use format_customtiles\local\context_info;
use format_customtiles\local\course_map;

/**
 * Orientation tests.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \format_customtiles\local\context_info
 * @covers     \format_customtiles\local\course_map
 * @covers     \format_customtiles\hook_callbacks
 */
final class orientation_test extends \advanced_testcase {
    /**
     * "Section · Activity x of y" for an activity.
     */
    public function test_context_line_for_activity(): void {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course(['format' => 'customtiles', 'numsections' => 1], ['createsections' => true]);
        $user = $gen->create_and_enrol($course, 'student');
        $pages = [];
        for ($i = 0; $i < 3; $i++) {
            $pages[] = $gen->create_module('page', ['course' => $course->id, 'section' => 1]);
        }
        $this->setUser($user);
        $format = course_get_format($course->id);
        $cm = get_fast_modinfo($course->id)->get_cm($pages[1]->cmid);
        $parts = context_info::for_cm($format, $cm);
        $this->assertCount(2, $parts);
        $this->assertSame('Activity 2 of 3', $parts[1]);

        $section = get_fast_modinfo($course->id)->get_section_info(1);
        $this->assertSame('3 activities', context_info::for_section($format, $section)[1]);
    }

    /**
     * The course map lists every section and activity.
     */
    public function test_course_map(): void {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course(
            ['format' => 'customtiles', 'numsections' => 2, 'enablecompletion' => 1],
            ['createsections' => true]
        );
        $user = $gen->create_and_enrol($course, 'student');
        $gen->create_module('page', ['course' => $course->id, 'section' => 2, 'completion' => COMPLETION_TRACKING_MANUAL]);
        $this->setUser($user);
        $map = course_map::export(course_get_format($course->id));
        $this->assertCount(3, $map['sections']);
        $this->assertTrue($map['sections'][2]['hasactivities']);
        $this->assertTrue($map['sections'][2]['activitylist'][0]['tracked']);
        $this->assertFalse($map['sections'][2]['activitylist'][0]['done']);
        $this->assertTrue($map['hasoverall']);
    }

    /**
     * The skip link is only added in Custom tiles courses.
     */
    public function test_skip_link(): void {
        global $PAGE;
        $this->resetAfterTest();
        $tiles = $this->getDataGenerator()->create_course(['format' => 'customtiles']);
        $topics = $this->getDataGenerator()->create_course(['format' => 'topics']);

        $PAGE->set_course($tiles);
        $hook = new \core\hook\output\before_standard_top_of_body_html_generation(
            $PAGE->get_renderer('core'),
            ''
        );
        hook_callbacks::add_skip_link($hook);
        $this->assertStringContainsString('#maincontent', $hook->get_output());

        $PAGE = new \moodle_page();
        $PAGE->set_course($topics);
        $hook = new \core\hook\output\before_standard_top_of_body_html_generation(
            $PAGE->get_renderer('core'),
            ''
        );
        hook_callbacks::add_skip_link($hook);
        $this->assertSame('', $hook->get_output());
    }

    /**
     * Institutional logos appear only when the course enables the strip.
     */
    public function test_logos_strip(): void {
        global $PAGE;
        $this->resetAfterTest();
        $this->setAdminUser();
        $ctx = \context_system::instance();
        get_file_storage()->create_file_from_string(['contextid' => $ctx->id, 'component' => 'format_customtiles',
            'filearea' => 'logos', 'itemid' => 0, 'filepath' => '/', 'filename' => 'Funder.png'], 'x');
        $course = $this->getDataGenerator()->create_course(['format' => 'customtiles']);
        $PAGE->set_url('/course/view.php', ['id' => $course->id]);
        $export = function () use ($course, $PAGE) {
            $content = new \format_customtiles\output\courseformat\content(course_get_format($course->id));
            return $content->export_for_template($PAGE->get_renderer('format_customtiles'));
        };
        $this->assertFalse($export()->haslogos);
        course_get_format($course->id)->update_course_format_options(['showlogos' => 1]);
        $data = $export();
        $this->assertTrue($data->haslogos);
        $this->assertSame('Funder', $data->logos[0]['alt']);
    }
}
