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
 * Teacher page: assign a category to each activity of the course.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

use format_customtiles\api;

$courseid = required_param('id', PARAM_INT);
$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
require_capability('moodle/course:manageactivities', $context);

$url = new moodle_url('/course/format/customtiles/assign.php', ['id' => $courseid]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('assigncategories', 'format_customtiles'));
$PAGE->set_heading($course->fullname);

$categories = api::get_categories();
$modinfo = get_fast_modinfo($course);
$assigned = api::get_course_assignments($courseid);

if ($categories && data_submitted() && confirm_sesskey()) {
    foreach ($modinfo->cms as $cm) {
        $value = optional_param('cat_' . $cm->id, null, PARAM_INT);
        if ($value !== null && ($value === 0 || isset($categories[$value]))) {
            api::set_activity_category($courseid, (int)$cm->id, $value ?: null);
        }
    }
    redirect($url, get_string('categorysavedall', 'format_customtiles'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('assigncategories', 'format_customtiles'));
if (!$categories) {
    echo $OUTPUT->notification(get_string('nocategoriesadmin', 'format_customtiles'), 'info');
    echo $OUTPUT->footer();
    exit;
}
echo html_writer::tag('p', get_string('assigncategories_intro', 'format_customtiles'));
$options = [0 => get_string('nocategory', 'format_customtiles')];
foreach ($categories as $cat) {
    $options[$cat->id] = $cat->code . ' · ' . format_string($cat->name);
}
echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url->out(false)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
foreach ($modinfo->get_section_info_all() as $num => $section) {
    $cmids = $modinfo->sections[$num] ?? [];
    if (!$cmids) {
        continue;
    }
    echo html_writer::tag('h3', get_section_name($course, $section), ['class' => 'h5 mt-4']);
    foreach ($cmids as $cmid) {
        $cm = $modinfo->cms[$cmid];
        if (!$cm->url) {
            continue;
        }
        $selected = $assigned[$cmid]['id'] ?? 0;
        echo html_writer::start_div('row align-items-center mb-2');
        echo html_writer::tag('label', $cm->get_formatted_name(), ['for' => 'cat_' . $cmid, 'class' => 'col-md-6 col-form-label']);
        echo html_writer::div(html_writer::select(
            $options,
            'cat_' . $cmid,
            $selected,
            false,
            ['id' => 'cat_' . $cmid, 'class' => 'form-select']
        ), 'col-md-6');
        echo html_writer::end_div();
    }
}
echo html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-primary mt-3', 'value' => get_string('savechanges')]);
echo html_writer::end_tag('form');
echo $OUTPUT->footer();
