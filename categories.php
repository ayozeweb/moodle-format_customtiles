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
 * Administration page: activity categories.
 *
 * @package    format_customtiles
 * @copyright  2026 ayozeweb (https://github.com/ayozeweb)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use core\output\notification;
use format_customtiles\api;

admin_externalpage_setup('format_customtiles_categories');

$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);
$pageurl = new moodle_url('/course/format/customtiles/categories.php');

// Loading the preset changes data: POST with sesskey only.
if ($action === 'sepe' && data_submitted() && confirm_sesskey()) {
    $n = api::load_sepe_preset();
    redirect($pageurl, get_string('sepeloaded', 'format_customtiles', $n), null, notification::NOTIFY_SUCCESS);
}

$PAGE->set_url($pageurl);
$output = $PAGE->get_renderer('format_customtiles');

// Deleting asks for confirmation and is then done with a POST request.
if ($action === 'delete' && $id) {
    $category = $DB->get_record('format_customtiles_cat', ['id' => $id], '*', MUST_EXIST);
    if (optional_param('confirm', 0, PARAM_BOOL) && data_submitted() && confirm_sesskey()) {
        api::delete_category($id);
        redirect($pageurl, get_string('categorydeleted', 'format_customtiles'), null, notification::NOTIFY_SUCCESS);
    }
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(
        get_string('confirmdeletecat', 'format_customtiles', s($category->code . ' · ' . format_string($category->name))),
        new single_button(
            new moodle_url($pageurl, ['action' => 'delete', 'id' => $id, 'confirm' => 1, 'sesskey' => sesskey()]),
            get_string('delete'),
            'post',
            single_button::BUTTON_DANGER
        ),
        $pageurl
    );
    echo $OUTPUT->footer();
    exit;
}

if ($action === 'edit' || $action === 'add') {
    $form = new \format_customtiles\form\category_form(new moodle_url($pageurl, ['action' => $action]));
    if ($form->is_cancelled()) {
        redirect($pageurl);
    }
    if ($data = $form->get_data()) {
        api::save_category($data);
        redirect($pageurl, get_string('categorysaved', 'format_customtiles'), null, notification::NOTIFY_SUCCESS);
    }
    if (!$form->is_submitted()) {
        $form->set_data($action === 'edit' ? $DB->get_record('format_customtiles_cat', ['id' => $id], '*', MUST_EXIST) : null);
    }
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string($action === 'edit' ? 'editcategory' : 'addcategory', 'format_customtiles'));
    $form->display();
    echo $OUTPUT->footer();
    exit;
}

$rows = [];
foreach (api::get_categories() as $category) {
    $badge = api::export_badge($category);
    $rows[] = [
        'code' => $category->code,
        'name' => format_string($category->name),
        'color' => $category->color,
        'style' => $badge['style'],
        'editurl' => (new moodle_url($pageurl, ['action' => 'edit', 'id' => $category->id]))->out(false),
        'deleteurl' => (new moodle_url($pageurl, ['action' => 'delete', 'id' => $category->id]))->out(false),
    ];
}
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('categoriesadmin', 'format_customtiles'));
echo $output->render_admin_categories([
    'intro' => get_string('categories_intro', 'format_customtiles'),
    'hascategories' => !empty($rows),
    'categories' => $rows,
    'addurl' => (new moodle_url($pageurl, ['action' => 'add']))->out(false),
    'pageurl' => $pageurl->out(false),
    'sesskey' => sesskey(),
]);
echo $OUTPUT->footer();
