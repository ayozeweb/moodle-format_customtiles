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
 * Development tool: create a large Custom tiles course to measure performance.
 *
 * Not part of the released plugin (see .gitattributes). Example:
 *   php public/course/format/customtiles/cli/generate_test_course.php --sections=60 --activities=10 --covers=20 \
 *       --student=student1
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze M de Paz Paz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/testing/generator/lib.php');
require_once($CFG->libdir . '/testing/generator/data_generator.php');

[$options, $unrecognised] = cli_get_params(
    ['sections' => 60, 'activities' => 10, 'covers' => 20, 'units' => 2, 'student' => '', 'help' => false],
    ['h' => 'help']
);
if ($options['help'] || $unrecognised) {
    echo "Create a large Custom tiles course.\n\n" .
        "--sections=N    sections (default 60)\n" .
        "--activities=N  activities per section (default 10)\n" .
        "--covers=N      sections with a real JPEG cover of about 1-2 MB (default 20)\n" .
        "--units=N       sections that get one unit with activities (default 2)\n" .
        "--student=USER  enrol this existing user as a student\n";
    exit(0);
}

if (!$CFG->debugdeveloper) {
    cli_error('This development tool only runs with developer debugging enabled (never on a production site).');
}

\core\session\manager::set_user(get_admin());
$gen = new testing_data_generator();
$sections = max(1, (int)$options['sections']);
$course = $gen->create_course([
    'format' => 'customtiles',
    'fullname' => "Performance course ($sections sections)",
    'shortname' => 'perf' . time(),
    'numsections' => $sections,
    'enablecompletion' => 1,
    'summary' => 'Large course generated to measure the Custom tiles format.',
], ['createsections' => true]);
$format = course_get_format($course);
$usercontext = context_user::instance(get_admin()->id);
$fs = get_file_storage();

/**
 * A noisy JPEG of roughly 1-2 MB (noise defeats compression, as a real photo does).
 *
 * @param int $seed
 * @return string
 */
function format_customtiles_test_cover(int $seed): string {
    mt_srand($seed);
    $w = 2400;
    $h = 1600;
    $image = imagecreatetruecolor($w, $h);
    for ($y = 0; $y < $h; $y += 2) {
        for ($x = 0; $x < $w; $x += 2) {
            $c = imagecolorallocate($image, ($x / 10 + mt_rand(0, 90)) % 256, ($y / 7 + mt_rand(0, 90)) % 256,
                ($seed * 40 + mt_rand(0, 90)) % 256);
            imagefilledrectangle($image, $x, $y, $x + 1, $y + 1, $c);
        }
    }
    ob_start();
    imagejpeg($image, null, 92);
    return ob_get_clean();
}

for ($num = 1; $num <= $sections; $num++) {
    $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => $num], '*', MUST_EXIST);
    $data = ['id' => $section->id, 'ct_subtitle' => "Subtitle $num", 'ct_minutes' => 30 + ($num % 5) * 15,
        'ct_icon' => array_keys(\format_customtiles\local\section_style::get_icons())[$num % 20]];
    if ($num <= (int)$options['covers']) {
        $draftid = file_get_unused_draft_itemid();
        $fs->create_file_from_string(['contextid' => $usercontext->id, 'component' => 'user', 'filearea' => 'draft',
            'itemid' => $draftid, 'filepath' => '/', 'filename' => "cover$num.jpg"], format_customtiles_test_cover($num));
        $data['ct_cover_filemanager'] = $draftid;
    }
    $DB->set_field('course_sections', 'name', "Section $num", ['id' => $section->id]);
    $format->update_section_format_options($data);
    for ($i = 1; $i <= (int)$options['activities']; $i++) {
        $gen->create_module('page', ['course' => $course->id, 'section' => $num, 'name' => "Page $num.$i",
            'completion' => COMPLETION_TRACKING_MANUAL]);
    }
    if ($num <= (int)$options['units']) {
        $sub = $gen->create_module('subsection', ['course' => $course->id, 'section' => $num, 'name' => "Unit $num.1"]);
        $unit = $DB->get_record('course_sections', ['course' => $course->id, 'component' => 'mod_subsection',
            'itemid' => $sub->id], '*', MUST_EXIST);
        for ($i = 1; $i <= 3; $i++) {
            $gen->create_module('page', ['course' => $course->id, 'section' => $unit->section, 'name' => "Unit page $i",
                'completion' => COMPLETION_TRACKING_MANUAL]);
        }
    }
    cli_write("\rSection $num/$sections");
}
if ($options['student'] !== '') {
    $user = $DB->get_record('user', ['username' => $options['student']], '*', MUST_EXIST);
    $gen->enrol_user($user->id, $course->id, 'student');
}
rebuild_course_cache($course->id, true);
cli_writeln("\nCourse {$course->id} created.");
