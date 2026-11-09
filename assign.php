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
 * @copyright  2026 ayozeweb (https://github.com/ayozeweb)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

use core\output\notification;
use format_customtiles\api;

$courseid = required_param('id', PARAM_INT);
$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
require_capability('moodle/course:manageactivities', $context);
if ($course->format !== 'customtiles') {
    redirect(course_get_url($course));
}

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
    redirect($url, get_string('categorysavedall', 'format_customtiles'), null, notification::NOTIFY_SUCCESS);
}

$sections = [];
foreach ($modinfo->get_section_info_all() as $num => $section) {
    $activities = [];
    foreach ($modinfo->sections[$num] ?? [] as $cmid) {
        $cm = $modinfo->cms[$cmid];
        if (!$cm->url) {
            continue;
        }
        $selected = $assigned[$cmid]['id'] ?? 0;
        $options = [['value' => 0, 'label' => get_string('nocategory', 'format_customtiles'), 'selected' => $selected === 0]];
        foreach ($categories as $category) {
            $options[] = [
                'value' => (int)$category->id,
                'label' => $category->code . ' · ' . format_string($category->name),
                'selected' => $selected === (int)$category->id,
            ];
        }
        $activities[] = ['id' => (int)$cmid, 'name' => $cm->get_formatted_name(), 'options' => $options];
    }
    if ($activities) {
        $sections[] = ['name' => get_section_name($course, $section), 'activities' => $activities];
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('assigncategories', 'format_customtiles'));
echo $PAGE->get_renderer('format_customtiles')->render_assign_categories([
    'hascategories' => !empty($categories),
    'intro' => get_string('assigncategories_intro', 'format_customtiles'),
    'formurl' => $url->out(false),
    'sesskey' => sesskey(),
    'sections' => $sections,
]);
echo $OUTPUT->footer();
