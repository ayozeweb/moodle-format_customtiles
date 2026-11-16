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
 * Tests for the section -> unit (sub-section) hierarchy.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze M de Paz Paz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles;

use format_customtiles\local\context_info;
use format_customtiles\local\course_data;
use format_customtiles\local\course_map;

/**
 * Hierarchy tests.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze M de Paz Paz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \format_customtiles\local\course_data
 * @covers     \format_customtiles\local\context_info
 * @covers     \format_customtiles\local\course_map
 */
final class hierarchy_test extends \advanced_testcase {
    /**
     * Course: section 1 with one page, then unit A (2 pages), one more page and unit B (1 page); section 2 with a page.
     *
     * @return array [course, student, pages by key, unit sections by key]
     */
    private function setup_course(): array {
        global $DB;
        $gen = $this->getDataGenerator();
        $course = $gen->create_course(
            ['format' => 'customtiles', 'numsections' => 2, 'enablecompletion' => 1],
            ['createsections' => true]
        );
        $student = $gen->create_and_enrol($course, 'student');
        $page = fn(int $section, string $name) => $gen->create_module('page', ['course' => $course->id,
            'section' => $section, 'name' => $name, 'completion' => COMPLETION_TRACKING_MANUAL]);
        $unit = function (string $name) use ($gen, $course, $DB) {
            $sub = $gen->create_module('subsection', ['course' => $course->id, 'section' => 1, 'name' => $name]);
            return $DB->get_record('course_sections', ['course' => $course->id, 'component' => 'mod_subsection',
                'itemid' => $sub->id], '*', MUST_EXIST);
        };
        $pages = [];
        $units = [];
        $pages['own1'] = $page(1, 'Own 1');
        $units['a'] = $unit('Unit A');
        $pages['a1'] = $page((int)$units['a']->section, 'A1');
        $pages['a2'] = $page((int)$units['a']->section, 'A2');
        $pages['own2'] = $page(1, 'Own 2');
        $units['b'] = $unit('Unit B');
        $pages['b1'] = $page((int)$units['b']->section, 'B1');
        $pages['s2'] = $page(2, 'S2');
        return [$course, $student, $pages, $units];
    }

    /**
     * Mark an activity as done by a user.
     *
     * @param \stdClass $course
     * @param \stdClass $page
     * @param \stdClass $user
     */
    private function complete(\stdClass $course, \stdClass $page, \stdClass $user): void {
        $cm = get_fast_modinfo($course->id)->get_cm($page->cmid);
        (new \completion_info(get_course($course->id)))->update_state($cm, COMPLETION_COMPLETE, $user->id);
    }

    /**
     * A section counts its own activities plus those of its units; units keep their own figures.
     */
    public function test_progress_aggregates_units(): void {
        $this->resetAfterTest();
        [$course, $student, $pages, $units] = $this->setup_course();
        $this->complete($course, $pages['a1'], $student);
        $this->complete($course, $pages['a2'], $student);
        $this->complete($course, $pages['own1'], $student);
        $this->setUser($student);

        $model = new course_data(course_get_format($course->id));
        $sections = $model->get_sections();
        // Units are not listed as sections of their own.
        $this->assertCount(3, $sections);
        $module = $sections[1];
        $this->assertSame(5, $module['activities']);
        $this->assertSame(5, $module['tracked']);
        $this->assertSame(3, $module['done']);
        $this->assertSame(60, $module['percent']);
        $this->assertSame('inprogress', $module['state']);
        $this->assertSame('2 units', $module['unitslabel']);
        $this->assertTrue($module['hasunits']);

        [$a, $b] = $module['units'];
        $this->assertSame('Unit A', $a['name']);
        $this->assertSame('completed', $a['state']);
        $this->assertSame(100, $a['percent']);
        $this->assertSame('1.1', $a['displaynum']);
        $this->assertSame('pending', $b['state']);
        $this->assertSame('1.2', $b['displaynum']);
        $this->assertTrue($a['isunit']);

        // Nothing is counted twice in the course total.
        $this->assertSame(['done' => 3, 'total' => 6, 'percent' => 50], $model->get_overall());
        $figures = $model->get_context_figures();
        $this->assertSame(2, $figures['sections']);
        $this->assertSame(2, $figures['units']);
        $this->assertSame(6, $figures['activities']);
    }

    /**
     * "Continue" follows the order on the page: own activities and units interleaved.
     */
    public function test_continue_follows_page_order(): void {
        $this->resetAfterTest();
        [$course, $student, $pages] = $this->setup_course();
        $this->setUser($student);
        $order = ['own1', 'a1', 'a2', 'own2', 'b1', 's2'];
        foreach ($order as $index => $key) {
            $target = (new course_data(course_get_format($course->id)))->get_continue();
            $this->assertSame((int)$pages[$key]->cmid, $target['cmid'], "Step $index should be $key");
            $this->complete($course, $pages[$key], $student);
        }
    }

    /**
     * The estimated time of a section without its own value is the sum of its units.
     */
    public function test_time_is_summed_from_units(): void {
        $this->resetAfterTest();
        [$course, $student, , $units] = $this->setup_course();
        $format = course_get_format($course->id);
        $format->update_section_format_options(['id' => $units['a']->id, 'ct_minutes' => 60]);
        $format->update_section_format_options(['id' => $units['b']->id, 'ct_minutes' => 45]);
        $this->setUser($student);
        $module = (new course_data(course_get_format($course->id)))->get_sections()[1];
        $this->assertSame(105, $module['minutes']);
        $this->assertSame('1 h 45 min', $module['timelabel']);

        // An explicit value on the section wins.
        $sectionid = get_fast_modinfo($course->id)->get_section_info(1)->id;
        course_get_format($course->id)->update_section_format_options(['id' => $sectionid, 'ct_minutes' => 30]);
        $module = (new course_data(course_get_format($course->id)))->get_sections()[1];
        $this->assertSame(30, $module['minutes']);
    }

    /**
     * Moving around: a unit goes back to its section, forward to the next unit, and after the last one to the next section.
     */
    public function test_unit_navigation_and_context(): void {
        $this->resetAfterTest();
        [$course, $student, $pages, $units] = $this->setup_course();
        $this->setUser($student);
        $format = course_get_format($course->id);
        $model = new course_data($format);
        $modinfo = get_fast_modinfo($course->id);
        $module = $modinfo->get_section_info(1);

        $view = $model->get_section_view((int)$units['a']->id);
        $this->assertSame('Unit A', $view['item']['name']);
        $this->assertSame((int)$module->id, $view['parent']['id']);
        $this->assertSame((int)$module->id, $view['previous']['id']);
        $this->assertSame((int)$units['b']->id, $view['next']['id']);

        $view = $model->get_section_view((int)$units['b']->id);
        $this->assertSame((int)$units['a']->id, $view['previous']['id']);
        $this->assertSame((int)$modinfo->get_section_info(2)->id, $view['next']['id']);

        $this->assertNull($model->get_section_view(999999));

        // Context line: unit page and activity inside a unit.
        $unit = $modinfo->get_section_info_by_id($units['b']->id);
        $this->assertSame([$format->get_section_name($module), 'Unit 2 of 2'], context_info::for_section($format, $unit));
        $cm = $modinfo->get_cm($pages['a2']->cmid);
        $this->assertSame([$format->get_section_name($module), 'Unit A', 'Activity 2 of 2'], context_info::for_cm($format, $cm));
    }

    /**
     * The course map nests units under their section, with their own activities.
     */
    public function test_map_nests_units(): void {
        $this->resetAfterTest();
        [$course, $student] = $this->setup_course();
        $this->setUser($student);
        $map = course_map::export(course_get_format($course->id));
        $module = $map['sections'][1];
        $this->assertSame(['Own 1', 'Own 2'], array_column($module['activitylist'], 'name'));
        $this->assertSame(['Unit A', 'Unit B'], array_column($module['units'], 'name'));
        $this->assertSame(['A1', 'A2'], array_column($module['units'][0]['activitylist'], 'name'));
        $this->assertTrue($module['units'][1]['hasactivities']);
    }

    /**
     * A unit hidden from the student is not counted and not listed.
     */
    public function test_hidden_unit_is_ignored(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $student, , $units] = $this->setup_course();
        $cmid = $DB->get_field('course_modules', 'id', ['course' => $course->id, 'instance' => $units['b']->itemid,
            'module' => $DB->get_field('modules', 'id', ['name' => 'subsection'])]);
        set_coursemodule_visible($cmid, 0);
        rebuild_course_cache($course->id, true);
        $this->setUser($student);
        $module = (new course_data(course_get_format($course->id)))->get_sections()[1];
        $this->assertSame(['Unit A'], array_column($module['units'], 'name'));
        $this->assertSame(4, $module['activities']);
    }
}
