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
 * Tests for the design view: palette generator, appearance forms, focal point and the tile fragment.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze M de Paz Paz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles;

use format_customtiles\form\palette as palette_form;
use format_customtiles\form\section_style as section_style_form;
use format_customtiles\local\color;
use format_customtiles\local\palette;
use format_customtiles\local\section_style;

/**
 * Design view tests.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze M de Paz Paz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \format_customtiles\form\section_style
 * @covers     \format_customtiles\form\palette
 * @covers     \format_customtiles\local\palette
 * @covers     \format_customtiles\local\color
 * @covers     \format_customtiles\local\section_style
 */
final class design_test extends \advanced_testcase {
    /**
     * HSL conversion round-trips.
     */
    public function test_hsl_round_trip(): void {
        foreach (['#1d4ed8', '#0f766e', '#b45309', '#7c3aed', '#000000', '#ffffff', '#808080'] as $hex) {
            [$h, $s, $l] = color::to_hsl($hex);
            $back = color::from_hsl($h, $s, $l);
            foreach ([0, 1, 2] as $i) {
                $this->assertEqualsWithDelta(color::to_rgb($hex)[$i], color::to_rgb($back)[$i], 1, $hex);
            }
        }
    }

    /**
     * The server colour maths match the shared parity table (the live preview is checked against
     * the same table in tests/js/colour.test.mjs).
     */
    public function test_colour_parity_table(): void {
        $table = json_decode(file_get_contents(__DIR__ . '/fixtures/colour_parity.json'), true);
        foreach ($table['normalise'] as [$input, $expected]) {
            $this->assertSame($expected, color::normalise($input), json_encode($input));
        }
        foreach ($table['luminance'] as [$hex, $expected]) {
            $this->assertEqualsWithDelta($expected, color::luminance($hex), 1e-6, $hex);
        }
        foreach ($table['contrast'] as [$a, $b, $expected]) {
            $this->assertEqualsWithDelta($expected, color::contrast($a, $b), 1e-4, "$a / $b");
        }
        foreach ($table['mix'] as [$a, $b, $weight, $expected]) {
            $this->assertSame($expected, color::mix($a, $b, (float)$weight), "$a + $b @ $weight");
        }
        foreach ($table['readable'] as [$bg, $expected]) {
            $this->assertSame($expected, color::readable_on($bg), $bg);
        }
    }

    /**
     * The automatic palette gives distinct colours, all readable with white text, from any base.
     */
    public function test_palette_series(): void {
        foreach (['#1d4ed8', '#ffff00', '#000000', '#ffffff', '#ff0000'] as $base) {
            $series = palette::series($base, 8);
            $this->assertCount(8, $series);
            $this->assertCount(8, array_unique(array_column($series, 'color')), $base);
            foreach ($series as $pair) {
                $this->assertGreaterThanOrEqual(4.5, color::contrast('#ffffff', $pair['color']), $base);
                $this->assertGreaterThanOrEqual(4.5, color::contrast('#ffffff', $pair['color2']), $base);
            }
        }
        $this->assertSame([], palette::series('#123456', 0));
    }

    /**
     * Focal point parsing and CSS.
     */
    public function test_focus(): void {
        $this->assertSame([20, 75], section_style::parse_focus('20,75'));
        $this->assertSame([100, 0], section_style::parse_focus(' 150 , 0 '));
        $this->assertNull(section_style::parse_focus('left'));
        $this->assertNull(section_style::parse_focus('10;20'));
        $this->assertSame('50% 50%', section_style::focus_css(''));
        $this->assertSame('20% 75%', section_style::focus_css('20,75'));
    }

    /**
     * Course with three sections and a unit inside section 1.
     *
     * @return array [course, teacher, student, section ids by number, unit section id]
     */
    private function setup_course(): array {
        global $DB;
        $gen = $this->getDataGenerator();
        $course = $gen->create_course(['format' => 'customtiles', 'numsections' => 3], ['createsections' => true]);
        $teacher = $gen->create_and_enrol($course, 'editingteacher');
        $student = $gen->create_and_enrol($course, 'student');
        $sub = $gen->create_module('subsection', ['course' => $course->id, 'section' => 1, 'name' => 'Unit']);
        $unitid = (int)$DB->get_field('course_sections', 'id', ['course' => $course->id, 'component' => 'mod_subsection',
            'itemid' => $sub->id]);
        $ids = [];
        foreach (get_fast_modinfo($course->id)->get_listed_section_info_all() as $num => $section) {
            $ids[$num] = (int)$section->id;
        }
        return [$course, $teacher, $student, $ids, $unitid];
    }

    /**
     * Submitting the appearance form saves the section and copies colours and icon to the chosen ones.
     */
    public function test_section_style_form_saves_and_applies(): void {
        $this->resetAfterTest();
        [$course, $teacher, , $ids, $unitid] = $this->setup_course();
        $this->setUser($teacher);

        $formdata = section_style_form::mock_ajax_submit([
            'sectionid' => $ids[1],
            'ct_cover_filemanager' => file_get_unused_draft_itemid(),
            'focusx' => 30,
            'focusy' => 70,
            'ct_icon' => 'trophy',
            'ct_color' => '#7C3AED',
            'ct_color2' => '',
            'ct_subtitle' => 'First steps',
            'ct_minutes' => 45,
            'applyto' => [$ids[2], $unitid, 999999],
        ]);
        $form = new section_style_form(null, null, 'post', '', [], true, $formdata, true);
        $form->set_data_for_dynamic_submission();
        $this->assertTrue($form->is_validated());
        $result = $form->process_dynamic_submission();
        sort($result['updated']);
        $expected = [$ids[1], $ids[2], $unitid];
        sort($expected);
        $this->assertSame($expected, $result['updated']);

        $format = course_get_format($course->id);
        $one = $format->get_format_options(1);
        $this->assertSame('#7c3aed', $one['ct_color']);
        $this->assertSame('trophy', $one['ct_icon']);
        $this->assertSame('30,70', $one['ct_focus']);
        $this->assertSame('First steps', $one['ct_subtitle']);
        $this->assertEquals(45, $one['ct_minutes']);
        $two = $format->get_format_options(2);
        $this->assertSame('#7c3aed', $two['ct_color']);
        $this->assertSame('trophy', $two['ct_icon']);
        // Only colours and icon are copied.
        $this->assertSame('', $two['ct_subtitle']);
        $this->assertSame('', $format->get_format_options(3)['ct_color']);
        $unit = get_fast_modinfo($course->id)->get_section_info_by_id($unitid);
        $this->assertSame('#7c3aed', $format->get_format_options($unit)['ct_color']);
    }

    /**
     * Wrong values are rejected by the form validation.
     */
    public function test_section_style_form_validation(): void {
        $this->resetAfterTest();
        [, $teacher, , $ids] = $this->setup_course();
        $this->setUser($teacher);
        $formdata = section_style_form::mock_ajax_submit([
            'sectionid' => $ids[1],
            'ct_cover_filemanager' => file_get_unused_draft_itemid(),
            'focusx' => 150,
            'focusy' => 50,
            'ct_icon' => '',
            'ct_color' => 'javascript:alert(1)',
            'ct_color2' => '',
            'ct_subtitle' => '',
            'ct_minutes' => -5,
        ]);
        $form = new section_style_form(null, null, 'post', '', [], true, $formdata, true);
        $form->set_data_for_dynamic_submission();
        $this->assertFalse($form->is_validated());
    }

    /**
     * Students cannot use the design forms or fetch design tiles.
     */
    public function test_design_needs_capability(): void {
        global $CFG;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/course/format/customtiles/lib.php');
        [$course, , $student, $ids] = $this->setup_course();
        $this->setUser($student);
        $formdata = section_style_form::mock_ajax_submit(['sectionid' => $ids[1]]);
        try {
            $form = new section_style_form(null, null, 'post', '', [], true, $formdata, true);
            $form->set_data_for_dynamic_submission();
            $this->fail('A student opened the appearance form');
        } catch (\required_capability_exception $e) {
            $this->assertSame('nopermissions', $e->errorcode);
        }
        $formdata = palette_form::mock_ajax_submit(['courseid' => $course->id]);
        try {
            $form = new palette_form(null, null, 'post', '', [], true, $formdata, true);
            $form->set_data_for_dynamic_submission();
            $this->fail('A student opened the automatic colours form');
        } catch (\required_capability_exception $e) {
            $this->assertSame('nopermissions', $e->errorcode);
        }
        $this->expectException(\required_capability_exception::class);
        format_customtiles_output_fragment_designtile(['context' => \context_course::instance($course->id),
            'sectionid' => $ids[1]]);
    }

    /**
     * The automatic colours form colours the sections (and units) without a colour, in page order.
     */
    public function test_palette_form(): void {
        $this->resetAfterTest();
        [$course, $teacher, , $ids, $unitid] = $this->setup_course();
        $this->setUser($teacher);
        course_get_format($course->id)->update_section_format_options(['id' => $ids[2], 'ct_color' => '#123456']);

        $formdata = palette_form::mock_ajax_submit(
            ['courseid' => $course->id, 'base' => '#1d4ed8', 'scope' => 'empty', 'units' => 1]
        );
        $form = new palette_form(null, null, 'post', '', [], true, $formdata, true);
        $form->set_data_for_dynamic_submission();
        $this->assertTrue($form->is_validated());
        // Section 1, its unit and section 3: section 2 keeps its colour.
        $this->assertSame(['count' => 3], $form->process_dynamic_submission());
        $format = course_get_format($course->id);
        $this->assertSame('#123456', $format->get_format_options(2)['ct_color']);
        foreach ([1, 3] as $num) {
            $colour = $format->get_format_options($num)['ct_color'];
            $this->assertNotSame('', $colour);
            $this->assertGreaterThanOrEqual(4.5, color::contrast('#ffffff', $colour));
        }
        $unit = get_fast_modinfo($course->id)->get_section_info_by_id($unitid);
        $this->assertNotSame('', $format->get_format_options($unit)['ct_color']);

        // Choosing "all" replaces every colour.
        $formdata = palette_form::mock_ajax_submit(
            ['courseid' => $course->id, 'base' => '#b45309', 'scope' => 'all', 'units' => 0]
        );
        $form = new palette_form(null, null, 'post', '', [], true, $formdata, true);
        $form->set_data_for_dynamic_submission();
        $this->assertSame(['count' => 3], $form->process_dynamic_submission());
        $this->assertNotSame('#123456', course_get_format($course->id)->get_format_options(2)['ct_color']);
    }

    /**
     * The design tile fragment renders the section with its edit buttons, units included.
     */
    public function test_design_tile_fragment(): void {
        global $CFG;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/course/format/customtiles/lib.php');
        [$course, $teacher, , $ids, $unitid] = $this->setup_course();
        $this->setUser($teacher);
        $html = format_customtiles_output_fragment_designtile(['context' => \context_course::instance($course->id),
            'sectionid' => $ids[1]]);
        $this->assertStringContainsString('data-sectionid="' . $ids[1] . '"', $html);
        $this->assertStringContainsString('data-sectionid="' . $unitid . '"', $html);
        $this->assertStringContainsString('Edit appearance', $html);
        $this->assertSame('', format_customtiles_output_fragment_designtile([
            'context' => \context_course::instance($course->id), 'sectionid' => 999999]));
    }

    /**
     * The design view replaces the core list only for teachers in edit mode who chose it.
     */
    public function test_design_view_export(): void {
        global $PAGE, $USER;
        $this->resetAfterTest();
        [$course, $teacher] = $this->setup_course();
        $this->setUser($teacher);
        $PAGE = new \moodle_page();
        $PAGE->set_course(get_course($course->id));
        $PAGE->set_url('/course/view.php', ['id' => $course->id]);
        $PAGE->set_pagelayout('course');
        $USER->editing = 1;
        $export = function () use ($course, $PAGE) {
            $content = new \format_customtiles\output\courseformat\content(course_get_format($course->id));
            return $content->export_for_template($PAGE->get_renderer('format_customtiles'));
        };
        $data = $export();
        $this->assertTrue($data->candesign);
        $this->assertFalse($data->designview);
        $this->assertNotEmpty($data->sections);

        set_user_preference('format_customtiles_designview', 1);
        $data = $export();
        $this->assertTrue($data->showdesign);
        $this->assertCount(4, $data->designtiles);
        // The core list is not exported in the design view.
        $this->assertSame([], $data->sections);
    }
}
