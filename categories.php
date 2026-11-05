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
 * @copyright  2026 Ayoze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use format_customtiles\api;

admin_externalpage_setup('format_customtiles_categories');

$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);
$pageurl = new moodle_url('/course/format/customtiles/categories.php');

if ($action === 'delete' && $id && confirm_sesskey()) {
    api::delete_category($id);
    redirect($pageurl, get_string('categorydeleted', 'format_customtiles'), null, \core\output\notification::NOTIFY_SUCCESS);
}
if ($action === 'sepe' && confirm_sesskey()) {
    $n = api::load_sepe_preset();
    redirect($pageurl, get_string('sepeloaded', 'format_customtiles', $n), null, \core\output\notification::NOTIFY_SUCCESS);
}

$form = null;
if ($action === 'edit' || $action === 'add') {
    $form = new \format_customtiles\form\category_form(new moodle_url($pageurl, ['action' => $action]));
    if ($form->is_cancelled()) {
        redirect($pageurl);
    }
    if ($data = $form->get_data()) {
        api::save_category($data);
        redirect($pageurl, get_string('categorysaved', 'format_customtiles'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    if (!$form->is_submitted()) {
        $form->set_data($action === 'edit' ? $DB->get_record('format_customtiles_cat', ['id' => $id], '*', MUST_EXIST) : null);
    }
}

$PAGE->set_url($pageurl);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('categoriesadmin', 'format_customtiles'));
echo html_writer::tag('p', get_string('categories_intro', 'format_customtiles'));

if ($form) {
    $form->display();
} else {
    $categories = api::get_categories();
    if (!$categories) {
        echo $OUTPUT->notification(get_string('nocategories', 'format_customtiles'), 'info');
    } else {
        $table = new html_table();
        $table->head = [get_string('catcode', 'format_customtiles'), get_string('catname', 'format_customtiles'),
            get_string('catcolor', 'format_customtiles'), get_string('catactions', 'format_customtiles')];
        foreach ($categories as $cat) {
            $badge = api::export_badge($cat);
            $editurl = new moodle_url($pageurl, ['action' => 'edit', 'id' => $cat->id]);
            $actions = html_writer::link($editurl, get_string('edit')) . ' · ' .
                html_writer::link(
                    new moodle_url($pageurl, ['action' => 'delete', 'id' => $cat->id, 'sesskey' => sesskey()]),
                    get_string('delete'),
                    ['onclick' => 'return confirm(' . json_encode(get_string(
                        'confirmdeletecat',
                        'format_customtiles'
                    )) . ');']
                );
            $table->data[] = [
                html_writer::span(s($cat->code), 'ct-catbadge', ['style' => $badge['style']]),
                format_string($cat->name), s($cat->color), $actions,
            ];
        }
        echo html_writer::table($table);
    }
    echo $OUTPUT->single_button(
        new moodle_url($pageurl, ['action' => 'add']),
        get_string('addcategory', 'format_customtiles'),
        'get'
    );
    echo html_writer::div($OUTPUT->single_button(
        new moodle_url($pageurl, ['action' => 'sepe', 'sesskey' => sesskey()]),
        get_string('loadsepe', 'format_customtiles')
    ), 'mt-2');
}
echo $OUTPUT->footer();
