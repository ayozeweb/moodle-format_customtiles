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
 * Tests for the format class and its options.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles;

/**
 * Format tests.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \format_customtiles
 */
final class format_customtiles_test extends \advanced_testcase {
    /**
     * Options have sane defaults and invalid values are corrected on save.
     */
    public function test_options(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['format' => 'customtiles']);
        $format = course_get_format($course);
        $opts = $format->get_format_options();
        $this->assertSame('tiles', $opts['layout']);
        $this->assertSame('light', $opts['skin']);
        $this->assertSame('', $opts['basecolor']);

        $format->update_course_format_options(['layout' => 'nope', 'skin' => 'nope', 'basecolor' => 'ABC']);
        $opts = course_get_format($course->id)->get_format_options();
        $this->assertSame('tiles', $opts['layout']);
        $this->assertSame('light', $opts['skin']);
        $this->assertSame('#aabbcc', $opts['basecolor']);
    }

    /**
     * The course is shown in multi-page mode (focused sections).
     */
    public function test_multipage(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['format' => 'customtiles']);
        $this->assertSame(COURSE_DISPLAY_MULTIPAGE, course_get_format($course)->get_course_display());
    }
}
