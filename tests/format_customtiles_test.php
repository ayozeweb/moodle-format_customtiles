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
 * @copyright  2026 ayozeweb (https://github.com/ayozeweb)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles;

/**
 * Format tests.
 *
 * @package    format_customtiles
 * @copyright  2026 ayozeweb (https://github.com/ayozeweb)
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

    /**
     * All layouts are selectable and a mobile layout produces two variants.
     */
    public function test_layouts_and_variants(): void {
        global $PAGE;
        $this->resetAfterTest();
        $this->assertSame(['tiles', 'cards', 'path', 'carousel', 'bento', 'list'], \format_customtiles\local\layouts::get_ids());
        $course = $this->getDataGenerator()->create_course(['format' => 'customtiles', 'numsections' => 2]);
        $user = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($user);
        $format = course_get_format($course);
        $format->update_course_format_options(['layout' => 'cards', 'layoutmobile' => 'carousel']);
        $format = course_get_format($course->id);
        $PAGE->set_url('/course/view.php', ['id' => $course->id]);
        $content = new \format_customtiles\output\courseformat\content($format);
        $data = $content->export_for_template($PAGE->get_renderer('format_customtiles'));
        $this->assertCount(2, $data->variants);
        $this->assertTrue($data->variants[0]['iscards']);
        $this->assertTrue($data->variants[1]['iscarousel']);
        $this->assertTrue($data->hascarousel);
        $this->assertSame(3, $data->tilecount);
    }

    /**
     * The upgrade script runs from the first released version and records the new version.
     */
    public function test_upgrade_from_first_release(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/course/format/customtiles/db/upgrade.php');
        require_once($CFG->libdir . '/upgradelib.php');
        set_config('version', 2026110400, 'format_customtiles');
        $this->assertTrue(xmldb_format_customtiles_upgrade(2026110400));
        $this->assertTrue($DB->get_manager()->table_exists('format_customtiles_cat'));
        $this->assertTrue($DB->get_manager()->table_exists('format_customtiles_cmcat'));
        $this->assertEquals(2026111400, get_config('format_customtiles', 'version'));
    }

    /**
     * Uninstalling removes the plugin files and the last-activity preferences, and nothing else.
     */
    public function test_uninstall_cleanup(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/course/format/customtiles/db/uninstall.php');
        $user = $this->getDataGenerator()->create_user();
        set_user_preference('format_customtiles_last_7', 3, $user);
        set_user_preference('other_preference', 1, $user);
        $context = \context_system::instance();
        get_file_storage()->create_file_from_string(['contextid' => $context->id, 'component' => 'format_customtiles',
            'filearea' => 'logos', 'itemid' => 0, 'filepath' => '/', 'filename' => 'logo.png'], 'x');

        $this->assertTrue(xmldb_format_customtiles_uninstall());
        $this->assertFalse($DB->record_exists('files', ['component' => 'format_customtiles']));
        $this->assertFalse($DB->record_exists('user_preferences', ['name' => 'format_customtiles_last_7']));
        $this->assertTrue($DB->record_exists('user_preferences', ['name' => 'other_preference']));
    }
}
