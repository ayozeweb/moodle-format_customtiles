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
 * Performance guards: the number of database queries to paint a course must not grow with its size.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze M de Paz Paz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles;

/**
 * Query budget tests.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze M de Paz Paz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \format_customtiles\output\courseformat\content
 * @covers     \format_customtiles\local\course_data
 * @covers     \format_customtiles\local\section_style
 * @covers     \format_customtiles\task\webp_covers
 */
final class performance_test extends \advanced_testcase {
    /**
     * Build a course with the given number of sections, each with activities, colours, categories and a unit.
     *
     * @param int $sections
     * @param \stdClass $student
     * @return \stdClass course
     */
    private function make_course(int $sections, \stdClass $student): \stdClass {
        global $DB;
        $gen = $this->getDataGenerator();
        $course = $gen->create_course(['format' => 'customtiles', 'numsections' => $sections, 'enablecompletion' => 1],
            ['createsections' => true]);
        $gen->enrol_user($student->id, $course->id, 'student');
        $format = course_get_format($course);
        $af = (int)api::get_category_by_code('AF')->id;
        for ($num = 1; $num <= $sections; $num++) {
            $sectionid = (int)$DB->get_field('course_sections', 'id', ['course' => $course->id, 'section' => $num]);
            $format->update_section_format_options(['id' => $sectionid, 'ct_color' => '#1d4ed8', 'ct_icon' => 'flask',
                'ct_subtitle' => 'Subtitle ' . $num, 'ct_minutes' => 30]);
            for ($i = 0; $i < 3; $i++) {
                $page = $gen->create_module('page', ['course' => $course->id, 'section' => $num,
                    'completion' => COMPLETION_TRACKING_MANUAL]);
                api::set_activity_category((int)$course->id, (int)$page->cmid, $af, false);
            }
            $gen->create_module('assign', ['course' => $course->id, 'section' => $num, 'duedate' => time() + DAYSECS]);
        }
        // One unit with activities in the first section.
        $sub = $gen->create_module('subsection', ['course' => $course->id, 'section' => 1]);
        $unit = $DB->get_record('course_sections', ['course' => $course->id, 'component' => 'mod_subsection',
            'itemid' => $sub->id], '*', MUST_EXIST);
        $gen->create_module('page', ['course' => $course->id, 'section' => $unit->section,
            'completion' => COMPLETION_TRACKING_MANUAL]);
        return $course;
    }

    /**
     * Database reads used to render a page of the course for the student (caches warm, as on a second visit).
     *
     * @param \stdClass $course
     * @param int|null $sectionnum null for the course page
     * @return int
     */
    private function reads_to_render(\stdClass $course, ?int $sectionnum): int {
        global $DB, $PAGE;
        $render = function () use ($course, $sectionnum) {
            global $PAGE;
            $PAGE = new \moodle_page();
            $PAGE->set_course(get_course($course->id));
            $PAGE->set_url('/course/view.php', ['id' => $course->id]);
            $format = course_get_format($course->id);
            if ($sectionnum !== null) {
                $format->set_sectionnum($sectionnum);
            }
            $renderer = $PAGE->get_renderer('format_customtiles');
            return $renderer->render(new \format_customtiles\output\courseformat\content($format));
        };
        $render();
        $start = $DB->perf_get_reads();
        $html = $render();
        $this->assertNotEmpty($html);
        return $DB->perf_get_reads() - $start;
    }

    /**
     * Painting the course page or a section costs the same number of queries with 5 or 40 sections.
     */
    public function test_queries_do_not_grow_with_sections(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        api::load_sepe_preset();
        $student = $this->getDataGenerator()->create_user();
        $small = $this->make_course(5, $student);
        $large = $this->make_course(40, $student);
        $this->setUser($student);

        $smallindex = $this->reads_to_render($small, null);
        $largeindex = $this->reads_to_render($large, null);
        $smallsection = $this->reads_to_render($small, 2);
        $largesection = $this->reads_to_render($large, 2);

        // A few reads of slack for things that legitimately vary (session, context preloading).
        $this->assertLessThanOrEqual($smallindex + 3, $largeindex,
            "Course page: $smallindex reads with 5 sections, $largeindex with 40");
        $this->assertLessThanOrEqual($smallsection + 3, $largesection,
            "Section page: $smallsection reads with 5 sections, $largesection with 40");
    }

    /**
     * Covers get WebP copies when saved (and from the upgrade task for older ones); the first row of
     * the grid is not lazy-loaded.
     */
    public function test_webp_covers_and_first_row(): void {
        global $DB, $USER;
        if (!function_exists('imagewebp')) {
            $this->markTestSkipped('GD without WebP support.');
        }
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(['format' => 'customtiles', 'numsections' => 6],
            ['createsections' => true]);
        $format = course_get_format($course);
        $section = get_fast_modinfo($course)->get_section_info(1);
        $image = imagecreatetruecolor(800, 500);
        imagefill($image, 0, 0, imagecolorallocate($image, 200, 80, 20));
        ob_start();
        imagejpeg($image);
        $draftid = file_get_unused_draft_itemid();
        get_file_storage()->create_file_from_string(['contextid' => \context_user::instance($USER->id)->id,
            'component' => 'user', 'filearea' => 'draft', 'itemid' => $draftid, 'filepath' => '/',
            'filename' => 'photo.jpg'], ob_get_clean());
        $format->update_section_format_options(['id' => $section->id, 'ct_cover_filemanager' => $draftid]);

        $items = (new local\course_data(course_get_format($course)))->get_sections();
        $first = $items[1]; // Section 0 is the first tile.
        $this->assertTrue($first['hascover']);
        $this->assertTrue($first['haswebp']);
        $this->assertStringContainsString('/w480/photo.jpg.webp 480w', $first['coverwebpsrcset']);
        $this->assertSame([true, true, true, true, false, false, false], array_map(fn($i) => $i['eager'], $items));

        // A cover from before 0.7.0: no flag and no WebP files. The upgrade task restores both.
        $ctx = \context_course::instance($course->id);
        $fs = get_file_storage();
        foreach ($fs->get_area_files($ctx->id, 'format_customtiles', local\section_style::FILEAREA, $section->id) as $f) {
            if ($f->get_mimetype() === 'image/webp') {
                $f->delete();
            }
        }
        $DB->delete_records('course_format_options', ['sectionid' => $section->id, 'name' => 'ct_coverwebp']);
        rebuild_course_cache($course->id, true);
        $items = (new local\course_data(course_get_format($course)))->get_sections();
        $this->assertArrayNotHasKey('haswebp', $items[1]);

        $this->expectOutputRegex('/WebP covers created in 1 course/');
        (new task\webp_covers())->execute();
        $items = (new local\course_data(course_get_format($course)))->get_sections();
        $this->assertTrue($items[1]['haswebp']);
    }
}
