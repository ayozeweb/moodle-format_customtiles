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
 * Tests for the Moodle app support.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze M de Paz Paz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles;

use format_customtiles\output\mobile;

/**
 * Mobile app tests.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze M de Paz Paz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \format_customtiles\output\mobile
 * @covers     \format_customtiles\local\hero
 * @covers     \format_customtiles\observer::course_module_viewed
 */
final class mobile_test extends \advanced_testcase {
    /**
     * A course with two sections and a page in each, a student enrolled.
     *
     * @return array [course, student, cms]
     */
    private function setup_course(): array {
        $gen = $this->getDataGenerator();
        $course = $gen->create_course(
            ['format' => 'customtiles', 'numsections' => 2, 'enablecompletion' => 1],
            ['createsections' => true]
        );
        $user = $gen->create_and_enrol($course, 'student');
        $cms = [];
        foreach ([1, 2] as $section) {
            $cms[] = $gen->create_module('page', ['course' => $course->id, 'section' => $section,
                'completion' => COMPLETION_TRACKING_MANUAL]);
        }
        return [$course, $user, $cms];
    }

    /**
     * The handler the app reads from db/mobile.php points to an existing method.
     */
    public function test_handler_definition(): void {
        global $CFG;
        $addons = [];
        require($CFG->dirroot . '/course/format/customtiles/db/mobile.php');
        $handler = $addons['format_customtiles']['handlers']['customtiles'];
        $this->assertSame('CoreCourseFormatDelegate', $handler['delegate']);
        $this->assertTrue(method_exists(mobile::class, $handler['method']));
        $this->assertStringEndsWith('/course/format/customtiles/mobile.css', $handler['styles']['url']);
        $this->assertFileExists($CFG->dirroot . '/course/format/customtiles/mobile.css');
    }

    /**
     * The course page for the app: one tile per section, a section view per section, continue button.
     */
    public function test_course_view(): void {
        $this->resetAfterTest();
        [$course, $user, $cms] = $this->setup_course();
        $this->setUser($user);
        $result = mobile::mobile_course_view(['courseid' => $course->id]);
        $this->assertSame('main', $result['templates'][0]['id']);
        $this->assertSame(['section' => ''], $result['otherdata']);
        $this->assertStringContainsString('this.fctOpen = (id, event) =>', $result['javascript']);
        $html = $result['templates'][0]['html'];
        $this->assertSame(3, substr_count($html, 'class="fct-app-tile '));
        $this->assertSame(3, substr_count($html, 'class="fct-app__section"'));
        $this->assertStringContainsString(
            'CoreCourseHelperProvider.navigateToModule(' . $cms[0]->cmid . ', {courseId: ' . $course->id . '})',
            $html
        );
        $this->assertStringContainsString(get_string('startcourse', 'format_customtiles'), $html);
        $this->assertStringContainsString('<core-course-section', $html);
        // The "Section x of y" line counts as on the course page: the general section is not counted.
        $this->assertStringContainsString('>' . get_string('sectionxofy', 'format_customtiles', ['x' => 2, 'y' => 2]) . '<', $html);
        $this->assertStringNotContainsString(get_string('sectionxofy', 'format_customtiles', ['x' => 3, 'y' => 3]), $html);
    }

    /**
     * Text written by teachers can never become an Angular expression in the app.
     */
    public function test_escaping(): void {
        $this->assertSame("{{ '{' }}{{ '{' }} 7*7 {{ '}' }}{{ '}' }}", mobile::escape('{{ 7*7 }}'));
        $this->assertSame('&lt;b&gt;&quot;x&quot; &#039;y&#039;', mobile::escape('<b>"x" \'y\''));

        $this->resetAfterTest();
        [$course, $user] = $this->setup_course();
        $section = get_fast_modinfo($course)->get_section_info(1);
        course_update_section($course, $section, ['name' => "{{constructor.constructor('alert(1)')()}}"]);
        $this->setUser($user);
        $html = mobile::mobile_course_view(['courseid' => $course->id])['templates'][0]['html'];
        $this->assertStringNotContainsString('{{constructor', $html);
        $this->assertStringContainsString("{{ '{' }}{{ '{' }}constructor.constructor(&#039;alert(1)&#039;)()", $html);
    }

    /**
     * Only courses of this format.
     */
    public function test_other_format(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['format' => 'topics']);
        $this->setAdminUser();
        $this->expectException(\moodle_exception::class);
        mobile::mobile_course_view(['courseid' => $course->id]);
    }

    /**
     * Previous and next skip sections that cannot be opened.
     */
    public function test_neighbours(): void {
        $sections = [
            ['id' => 1, 'name' => 'A', 'locked' => false],
            ['id' => 2, 'name' => 'B', 'locked' => true],
            ['id' => 3, 'name' => 'C', 'locked' => false],
        ];
        $method = new \ReflectionMethod(mobile::class, 'neighbour');
        $this->assertSame(['id' => 3, 'name' => 'C'], $method->invoke(null, $sections, 0, 1));
        $this->assertSame(['id' => 1, 'name' => 'A'], $method->invoke(null, $sections, 2, -1));
        $this->assertNull($method->invoke(null, $sections, 0, -1));
        $this->assertNull($method->invoke(null, $sections, 2, 1));
    }

    /**
     * Viewing an activity through a web service (as the app does) is remembered for "continue".
     */
    public function test_continue_from_app_views(): void {
        global $CFG;
        require_once($CFG->dirroot . '/mod/page/lib.php');
        $this->resetAfterTest();
        [$course, $user, $cms] = $this->setup_course();
        $this->setUser($user);
        $cm = get_coursemodule_from_id('page', $cms[1]->cmid);
        $page = $GLOBALS['DB']->get_record('page', ['id' => $cm->instance]);
        page_view($page, $course, $cm, \context_module::instance($cm->id));
        $this->assertSame((string)$cms[1]->cmid, get_user_preferences('format_customtiles_last_' . $course->id));

        // Other formats are left alone.
        $other = $this->getDataGenerator()->create_course(['format' => 'topics']);
        $this->getDataGenerator()->enrol_user($user->id, $other->id, 'student');
        $otherpage = $this->getDataGenerator()->create_module('page', ['course' => $other->id]);
        $othercm = get_coursemodule_from_id('page', $otherpage->cmid);
        page_view($otherpage, $other, $othercm, \context_module::instance($othercm->id));
        $this->assertNull(get_user_preferences('format_customtiles_last_' . $other->id));
    }
}
