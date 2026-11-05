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
 * Backup and restore of format_customtiles data.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

use format_customtiles\local\section_style;

/**
 * Backup/restore tests.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \backup_format_customtiles_plugin
 * @covers     \restore_format_customtiles_plugin
 */
final class backup_restore_test extends \advanced_testcase {
    /**
     * Course options, section options, cover images and activity categories survive a backup/restore.
     */
    public function test_backup_and_restore(): void {
        global $DB, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course(['format' => 'customtiles', 'numsections' => 2]);
        $page = $gen->create_module('page', ['course' => $course->id, 'section' => 1]);
        $format = course_get_format($course);
        $format->update_course_format_options(['layout' => 'path', 'skin' => 'dark', 'basecolor' => '#aa3300',
            'layoutmobile' => 'carousel']);
        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 1], '*', MUST_EXIST);

        // Section cover from a draft area.
        $usercontext = \context_user::instance($USER->id);
        $draftid = file_get_unused_draft_itemid();
        $fs = get_file_storage();
        $fs->create_file_from_string(
            ['contextid' => $usercontext->id, 'component' => 'user', 'filearea' => 'draft',
            'itemid' => $draftid, 'filepath' => '/', 'filename' => 'cover.png'],
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==')
        );
        $format->update_section_format_options(['id' => $section->id, 'ct_cover_filemanager' => $draftid,
            'ct_icon' => 'flask', 'ct_color' => '#112233', 'ct_subtitle' => 'Hello', 'ct_minutes' => 45]);

        api::load_sepe_preset();
        $af = api::get_category_by_code('AF');
        api::set_activity_category($course->id, (int)$page->cmid, (int)$af->id);

        // Backup.
        $bc = new \backup_controller(
            \backup::TYPE_1COURSE,
            $course->id,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $USER->id
        );
        $bc->execute_plan();
        $file = $bc->get_results()['backup_destination'];
        $backupid = $bc->get_backupid();
        $bc->destroy();
        $filepath = make_backup_temp_directory($backupid);
        $file->extract_to_pathname(get_file_packer('application/vnd.moodle.backup'), $filepath);

        // Restore into a new course.
        $newcourseid = \restore_dbops::create_new_course('Restored', 'RST', $course->category);
        $rc = new \restore_controller(
            $backupid,
            $newcourseid,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $USER->id,
            \backup::TARGET_NEW_COURSE
        );
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        rebuild_course_cache($newcourseid, true);
        $newformat = course_get_format($newcourseid);
        $this->assertSame('customtiles', $newformat->get_format());
        $opts = $newformat->get_format_options();
        $this->assertSame('path', $opts['layout']);
        $this->assertSame('dark', $opts['skin']);
        $this->assertSame('#aa3300', $opts['basecolor']);
        $this->assertSame('carousel', $opts['layoutmobile']);

        $newsection = $DB->get_record('course_sections', ['course' => $newcourseid, 'section' => 1], '*', MUST_EXIST);
        $secopts = $newformat->get_format_options((int)$newsection->section);
        $this->assertSame('flask', $secopts['ct_icon']);
        $this->assertSame('#112233', $secopts['ct_color']);
        $this->assertSame('Hello', $secopts['ct_subtitle']);
        $this->assertEquals(45, $secopts['ct_minutes']);
        $this->assertSame('cover.png', $secopts['ct_cover']);

        $ctx = \context_course::instance($newcourseid);
        $files = $fs->get_area_files($ctx->id, 'format_customtiles', section_style::FILEAREA, $newsection->id, 'id', false);
        $this->assertNotEmpty($files);

        $newcms = get_fast_modinfo($newcourseid)->get_instances_of('page');
        $newcm = reset($newcms);
        $assigned = api::get_course_assignments($newcourseid);
        $this->assertSame('AF', $assigned[(int)$newcm->id]['code']);
    }
}
