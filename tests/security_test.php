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
 * Security tests: capabilities, cover image sanitising, clean-up and hostile backups.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze M de Paz Paz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

use format_customtiles\local\section_style;

/**
 * Security tests.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze M de Paz Paz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \format_customtiles\api
 * @covers     \format_customtiles\local\section_style
 * @covers     \format_customtiles\observer
 */
final class security_test extends \advanced_testcase {
    /**
     * Only users with the capability can assign categories, and only to activities of that course.
     */
    public function test_assign_needs_capability_and_own_course(): void {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course(['format' => 'customtiles']);
        $other = $gen->create_course(['format' => 'customtiles']);
        $page = $gen->create_module('page', ['course' => $course->id]);
        $otherpage = $gen->create_module('page', ['course' => $other->id]);
        $student = $gen->create_and_enrol($course, 'student');
        $teacher = $gen->create_and_enrol($course, 'editingteacher');
        $this->setAdminUser();
        api::load_sepe_preset();
        $af = (int)api::get_category_by_code('AF')->id;

        $this->setUser($teacher);
        api::set_activity_category($course->id, (int)$page->cmid, $af);
        $this->assertSame('AF', api::get_course_assignments($course->id)[(int)$page->cmid]['code']);

        // An activity of another course cannot be reached through this course.
        try {
            api::set_activity_category($course->id, (int)$otherpage->cmid, $af);
            $this->fail('Expected an exception for an activity of another course');
        } catch (\coding_exception $e) {
            $this->assertStringContainsString('does not belong', $e->getMessage());
        }
        // Unknown category.
        $this->expectExceptionOnce(fn() => api::set_activity_category($course->id, (int)$page->cmid, 999999));
        // The teacher is not enrolled in the other course.
        $this->expectExceptionOnce(
            fn() => api::set_activity_category($other->id, (int)$otherpage->cmid, $af),
            \required_capability_exception::class
        );

        $this->setUser($student);
        $this->expectExceptionOnce(
            fn() => api::set_activity_category($course->id, (int)$page->cmid, null),
            \required_capability_exception::class
        );
        $this->assertSame('AF', api::get_course_assignments($course->id)[(int)$page->cmid]['code']);
    }

    /**
     * Managing categories needs its own capability: teachers cannot create, delete or load presets.
     */
    public function test_manage_categories_needs_capability(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['format' => 'customtiles']);
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setAdminUser();
        $id = api::save_category((object)['code' => 'ZZ', 'name' => 'Zed', 'color' => '#112233']);

        $this->setUser($teacher);
        $this->assertFalse(has_capability('format/customtiles:managecategories', \context_system::instance()));
        $this->assertTrue(has_capability('format/customtiles:assigncategories', \context_course::instance($course->id)));
        $this->expectExceptionOnce(
            fn() => api::save_category((object)['code' => 'YY', 'name' => 'Y', 'color' => '#112233']),
            \required_capability_exception::class
        );
        $this->expectExceptionOnce(fn() => api::delete_category($id), \required_capability_exception::class);
        $this->expectExceptionOnce(fn() => api::load_sepe_preset(), \required_capability_exception::class);
        $this->assertNotNull(api::get_category_by_code('ZZ'));
    }

    /**
     * Run a callable and assert it throws.
     *
     * @param callable $callable
     * @param string $class expected exception class
     */
    private function expectexceptiononce(callable $callable, string $class = \Throwable::class): void {
        try {
            $callable();
        } catch (\Throwable $e) {
            $this->assertInstanceOf($class, $e);
            return;
        }
        $this->fail('Expected exception ' . $class);
    }

    /**
     * Put a file in a new draft area of the current user.
     *
     * @param string $filename
     * @param string $content
     * @return int draft item id
     */
    private function draft_with(string $filename, string $content): int {
        global $USER;
        $draftid = file_get_unused_draft_itemid();
        get_file_storage()->create_file_from_string(['contextid' => \context_user::instance($USER->id)->id,
            'component' => 'user', 'filearea' => 'draft', 'itemid' => $draftid, 'filepath' => '/',
            'filename' => $filename], $content);
        return $draftid;
    }

    /**
     * A PNG with a text chunk carrying a payload.
     *
     * @param int $width
     * @param int $height
     * @return string
     */
    private function png_with_payload(int $width = 64, int $height = 40): string {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 10, 120, 200));
        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        // Insert a tEXt chunk right after the header chunk (IHDR ends at byte 33).
        $data = "Comment\0<script>EVILPAYLOAD</script>";
        $chunk = pack('N', strlen($data)) . 'tEXt' . $data . pack('N', crc32('tEXt' . $data));
        return substr($png, 0, 33) . $chunk . substr($png, 33);
    }

    /**
     * The cover is re-encoded: metadata and embedded payloads never reach the stored files,
     * and saving the form again without changing the image keeps it.
     */
    public function test_cover_is_reencoded(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(
            ['format' => 'customtiles', 'numsections' => 1],
            ['createsections' => true]
        );
        $section = get_fast_modinfo($course)->get_section_info(1);
        $payload = $this->png_with_payload();
        $this->assertStringContainsString('EVILPAYLOAD', $payload);

        $name = section_style::save_cover($this->draft_with('cover.png', $payload), (int)$course->id, (int)$section->id);
        $this->assertSame('cover.png', $name);
        $fs = get_file_storage();
        $context = \context_course::instance($course->id);
        $files = $fs->get_area_files($context->id, 'format_customtiles', section_style::FILEAREA, $section->id, 'id', false);
        $this->assertCount(3, $files);
        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->get_filepath();
            $this->assertStringNotContainsString('EVILPAYLOAD', $file->get_content());
            $this->assertNotFalse($file->get_imageinfo());
            // Small images are not upscaled.
            $this->assertSame(64, $file->get_imageinfo()['width']);
        }
        sort($paths);
        $this->assertSame(['/', '/w480/', '/w960/'], $paths);

        // Editing the section again with the same (sanitised) image keeps it untouched.
        $draftid = 0; // Empty: file_prepare_draft_area() creates the draft and copies the stored files.
        file_prepare_draft_area(
            $draftid,
            $context->id,
            'format_customtiles',
            section_style::FILEAREA,
            $section->id,
            section_style::filemanager_options()
        );
        $before = array_map(fn($f) => $f->get_id(), $files);
        $this->assertSame('cover.png', section_style::save_cover($draftid, (int)$course->id, (int)$section->id));
        $after = $fs->get_area_files($context->id, 'format_customtiles', section_style::FILEAREA, $section->id, 'id', false);
        $this->assertSame(array_values($before), array_values(array_map(fn($f) => $f->get_id(), $after)));

        // Removing the image in the form removes every file.
        $this->assertSame('', section_style::save_cover(file_get_unused_draft_itemid(), (int)$course->id, (int)$section->id));
        $this->assertEmpty($fs->get_area_files(
            $context->id,
            'format_customtiles',
            section_style::FILEAREA,
            $section->id,
            'id',
            false
        ));
    }

    /**
     * Things that are not safe raster images are rejected and nothing is stored.
     */
    public function test_cover_rejects_unsafe_files(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(
            ['format' => 'customtiles', 'numsections' => 1],
            ['createsections' => true]
        );
        $section = get_fast_modinfo($course)->get_section_info(1);
        $context = \context_course::instance($course->id);
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><script>alert(1)</script></svg>';
        $cases = [
            'evil.svg' => $svg,
            'fake.png' => '<?php echo "not an image";',
            'page.png' => '<html><script>alert(1)</script></html>',
        ];
        foreach ($cases as $filename => $content) {
            $name = section_style::save_cover($this->draft_with($filename, $content), (int)$course->id, (int)$section->id);
            $this->assertSame('', $name, $filename);
            $this->assertEmpty(get_file_storage()->get_area_files(
                $context->id,
                'format_customtiles',
                section_style::FILEAREA,
                $section->id,
                'id',
                false
            ), $filename);
        }
        $this->assertContains('.png', section_style::ACCEPTED_TYPES);
        $this->assertNotContains('.svg', section_style::ACCEPTED_TYPES);
    }

    /**
     * Deleting an activity or a course removes what the plugin stored about them.
     */
    public function test_cleanup_on_delete(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course(['format' => 'customtiles']);
        $keep = $gen->create_course(['format' => 'customtiles']);
        $user = $gen->create_and_enrol($course, 'student');
        $page1 = $gen->create_module('page', ['course' => $course->id]);
        $page2 = $gen->create_module('page', ['course' => $course->id]);
        $keeppage = $gen->create_module('page', ['course' => $keep->id]);
        api::load_sepe_preset();
        $af = (int)api::get_category_by_code('AF')->id;
        foreach ([[$course, $page1], [$course, $page2], [$keep, $keeppage]] as [$c, $p]) {
            api::set_activity_category($c->id, (int)$p->cmid, $af);
        }
        foreach ([$course, $keep] as $c) {
            set_user_preference('format_customtiles_last_' . $c->id, 5, $user);
            set_user_preference('format_customtiles_seen_' . $c->id, '1,2', $user);
        }

        \core_courseformat\formatactions::cm($course->id)->delete((int)$page1->cmid);
        $this->assertFalse($DB->record_exists('format_customtiles_cmcat', ['cmid' => $page1->cmid]));
        $this->assertTrue($DB->record_exists('format_customtiles_cmcat', ['cmid' => $page2->cmid]));

        delete_course($course->id, false);
        $this->assertFalse($DB->record_exists('format_customtiles_cmcat', ['courseid' => $course->id]));
        $this->assertFalse($DB->record_exists('user_preferences', ['name' => 'format_customtiles_last_' . $course->id]));
        $this->assertFalse($DB->record_exists('user_preferences', ['name' => 'format_customtiles_seen_' . $course->id]));
        // The other course is untouched.
        $this->assertTrue($DB->record_exists('format_customtiles_cmcat', ['courseid' => $keep->id]));
        $this->assertTrue($DB->record_exists('user_preferences', ['name' => 'format_customtiles_last_' . $keep->id]));
    }

    /**
     * A backup edited by hand (markup in options, bad colours, path-like file names) cannot inject
     * anything into the course page.
     */
    public function test_hostile_backup_is_neutralised(): void {
        global $DB, $USER, $PAGE;
        $this->resetAfterTest();
        $this->setAdminUser();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course(['format' => 'customtiles', 'numsections' => 1], ['createsections' => true]);
        $gen->create_module('page', ['course' => $course->id, 'section' => 1]);
        $format = course_get_format($course);
        $format->update_course_format_options(['layout' => 'cards', 'skin' => 'dark', 'basecolor' => '#aa3300']);
        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 1], '*', MUST_EXIST);
        $format->update_section_format_options(['id' => $section->id, 'ct_icon' => 'flask', 'ct_color' => '#112233',
            'ct_color2' => '#445566', 'ct_subtitle' => 'SUBTITLEMARK', 'ct_minutes' => 30]);

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
        $dir = make_backup_temp_directory($backupid);
        $file->extract_to_pathname(get_file_packer('application/vnd.moodle.backup'), $dir);

        // Tamper with the stored options.
        $evil = '&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;';
        $swaps = [
            '<value>#112233</value>' => '<value>red;background:url(javascript:alert(1))' . $evil . '</value>',
            '<value>#445566</value>' => '<value>' . $evil . '</value>',
            '<value>flask</value>' => '<value>x&quot; onmouseover=&quot;alert(1)</value>',
            '<value>SUBTITLEMARK</value>' => '<value>' . $evil . 'SUBTITLEMARK</value>',
            '<value>30</value>' => '<value>30&quot;&gt;&lt;b&gt;</value>',
            '<value>#aa3300</value>' => '<value>expression(alert(1))' . $evil . '</value>',
            '<value>dark</value>' => '<value>dark&quot; onload=&quot;alert(1)</value>',
            '<value>cards</value>' => '<value>../../evil</value>',
        ];
        $touched = 0;
        foreach (array_merge(glob($dir . '/sections/section_*/section.xml'), [$dir . '/course/course.xml']) as $xml) {
            $content = file_get_contents($xml);
            $new = strtr($content, $swaps);
            if ($new !== $content) {
                file_put_contents($xml, $new);
                $touched++;
            }
        }
        $this->assertGreaterThanOrEqual(2, $touched);

        $newcourseid = \restore_dbops::create_new_course('Hostile', 'HST', $course->category);
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

        // Render the course page and both templates that print section data.
        $PAGE->set_url('/course/view.php', ['id' => $newcourseid]);
        $renderer = $PAGE->get_renderer('format_customtiles');
        $newformat = course_get_format($newcourseid);
        $content = new \format_customtiles\output\courseformat\content($newformat);
        // Restore writes the options without the plugin's validation, so they are validated again on output.
        $data = $content->export_for_template($renderer);
        $this->assertContains($data->layout, \format_customtiles\local\layouts::get_ids());
        $this->assertTrue(\format_customtiles\local\skin::exists($data->skin));
        $html = $renderer->render($content);
        $html .= $renderer->render_from_template(
            'format_customtiles/local/map',
            \format_customtiles\local\course_map::export($newformat)
        );
        $this->assertStringContainsString('SUBTITLEMARK', $html);
        foreach (['<script>alert(1)', 'javascript:', 'expression(', 'onmouseover="alert', 'onload="alert', '../../evil'] as $bad) {
            $this->assertStringNotContainsString($bad, $html, $bad);
        }
    }
}
