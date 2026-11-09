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
 * Course map: the full structure of the course on a single, printable page.
 *
 * @package    format_customtiles
 * @copyright  2026 ayozeweb (https://github.com/ayozeweb)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

$courseid = required_param('id', PARAM_INT);
$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
if ($course->format !== 'customtiles') {
    redirect(course_get_url($course));
}

$url = new moodle_url('/course/format/customtiles/map.php', ['id' => $courseid]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('coursemap', 'format_customtiles'));
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add(get_string('coursemap', 'format_customtiles'));

$format = course_get_format($course);
$renderer = $PAGE->get_renderer('format_customtiles');
echo $OUTPUT->header();
echo $renderer->render_from_template(
    'format_customtiles/local/map',
    \format_customtiles\local\course_map::export($format)
);
echo $OUTPUT->footer();
