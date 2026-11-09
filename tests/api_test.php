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
 * Tests for the activity categories API and section styling.
 *
 * @package    format_customtiles
 * @copyright  2026 ayozeweb (https://github.com/ayozeweb)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles;

use format_customtiles\local\color;
use format_customtiles\local\section_style;

/**
 * API tests.
 *
 * @package    format_customtiles
 * @copyright  2026 ayozeweb (https://github.com/ayozeweb)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \format_customtiles\api
 * @covers     \format_customtiles\local\section_style
 */
final class api_test extends \advanced_testcase {
    /**
     * The SEPE preset loads once and respects existing categories.
     */
    public function test_sepe_preset(): void {
        $this->resetAfterTest();
        $this->assertSame(4, api::load_sepe_preset());
        $this->assertSame(0, api::load_sepe_preset());
        $codes = array_column(array_map(fn($c) => (array)$c, api::get_categories()), 'code');
        $this->assertSame(['AF', 'AE', 'PP', 'PO'], array_values($codes));
    }

    /**
     * Assign, change, clear and read course assignments; deleting a category removes them.
     */
    public function test_assignments(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['format' => 'customtiles']);
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        api::load_sepe_preset();
        $af = api::get_category_by_code('AF');
        $ae = api::get_category_by_code('AE');

        api::set_activity_category($course->id, (int)$page->cmid, (int)$af->id);
        $this->assertSame('AF', api::get_course_assignments($course->id)[(int)$page->cmid]['code']);
        api::set_activity_category($course->id, (int)$page->cmid, (int)$ae->id);
        $this->assertSame('AE', api::get_course_assignments($course->id)[(int)$page->cmid]['code']);
        api::set_activity_category($course->id, (int)$page->cmid, null);
        $this->assertSame([], api::get_course_assignments($course->id));

        api::set_activity_category($course->id, (int)$page->cmid, (int)$ae->id);
        api::delete_category((int)$ae->id);
        $this->assertSame([], api::get_course_assignments($course->id));
    }

    /**
     * Category badges always have a readable foreground.
     */
    public function test_badge_contrast(): void {
        foreach (['#f59e0b', '#1d4ed8', '#ffffff', '#000000', '#0f766e'] as $bg) {
            $badge = api::export_badge(['code' => 'X', 'name' => 'x', 'color' => $bg]);
            preg_match('/--ct-cat-bg:(#[0-9a-f]{6});--ct-cat-fg:(#[0-9a-f]{6})/', $badge['style'], $m);
            $this->assertGreaterThanOrEqual(4.5, color::contrast($m[1], $m[2]), $bg);
        }
    }

    /**
     * The category badge is rendered next to activities.
     */
    public function test_cm_badge_output(): void {
        global $PAGE;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(['format' => 'customtiles']);
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        api::load_sepe_preset();
        api::set_activity_category($course->id, (int)$page->cmid, (int)api::get_category_by_code('PP')->id);
        \format_customtiles\output\courseformat\content\cm::reset_cache();
        $format = course_get_format($course->id);
        $modinfo = get_fast_modinfo($course);
        $cm = $modinfo->get_cm($page->cmid);
        $PAGE->set_url('/course/view.php', ['id' => $course->id]);
        $class = $format->get_output_classname('content\\cm');
        $this->assertSame(\format_customtiles\output\courseformat\content\cm::class, $class);
        $output = new $class($format, $cm->get_section_info(), $cm);
        $data = $output->export_for_template($PAGE->get_renderer('format_customtiles'));
        $this->assertStringContainsString('ct-catbadge', $data->afterlink);
        $this->assertStringContainsString('PP', $data->afterlink);
    }

    /**
     * Section style export: colours, icon, time and an unreadable colour is still given a readable foreground.
     */
    public function test_section_style(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(
            ['format' => 'customtiles', 'numsections' => 1],
            ['createsections' => true]
        );
        $format = course_get_format($course);
        $section = get_fast_modinfo($course)->get_section_info(1);
        $format->update_section_format_options(['id' => $section->id, 'ct_color' => '#FFFF00', 'ct_icon' => 'nope',
            'ct_minutes' => 95]);
        $section = get_fast_modinfo($course->id)->get_section_info(1);
        $style = section_style::export($section, (int)$course->id);
        $this->assertTrue($style['hascolor']);
        $this->assertFalse($style['hasicon']);
        $this->assertSame('1 h 35 min', $style['timelabel']);
        $this->assertStringContainsString('--ct-fg:#111827', $style['coverstyle']);
    }

    /**
     * Category input is sanitised: no markup in names, codes limited, unknown icons dropped, colours normalised.
     */
    public function test_save_category_sanitises(): void {
        $this->resetAfterTest();
        $id = api::save_category((object)['code' => 'a<b>"x', 'name' => 'Name <script>alert(1)</script>',
            'color' => 'javascript:1', 'icon' => 'fa-spin evil']);
        $cat = (array)api::get_categories()[$id];
        $this->assertSame('ABX', $cat['code']);
        $this->assertStringNotContainsString('<', $cat['name']);
        $this->assertSame('#1d4ed8', $cat['color']);
        $this->assertSame('', $cat['icon']);
    }

    /**
     * Covers of sections the user cannot see are not served.
     */
    public function test_pluginfile_hidden_section(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(
            ['format' => 'customtiles', 'numsections' => 1],
            ['createsections' => true]
        );
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 1], '*', MUST_EXIST);
        $sectioninfo = get_fast_modinfo($course)->get_section_info(1);
        \core_courseformat\formatactions::section($course->id)->set_visibility($sectioninfo, false);
        $this->setUser($student);
        $context = \context_course::instance($course->id);
        $this->assertFalse(format_customtiles_pluginfile(
            $course,
            null,
            $context,
            'sectioncover',
            [$section->id, 'w480', 'cover.png'],
            false
        ));
    }
}
