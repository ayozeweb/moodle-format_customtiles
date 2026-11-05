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
 * Admin settings for format_customtiles.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $ADMIN->add('formatsettings', new admin_externalpage(
        'format_customtiles_categories',
        get_string('categoriesadmin', 'format_customtiles'),
        new moodle_url('/course/format/customtiles/categories.php'),
        'moodle/site:config'
    ));
}

if ($ADMIN->fulltree) {
    $settings->add(new \format_customtiles\admin\setting_customskins(
        'format_customtiles/customskins',
        new lang_string('customskins', 'format_customtiles'),
        new lang_string('customskins_desc', 'format_customtiles'),
        '',
        PARAM_RAW,
        60,
        6
    ));
    $settings->add(new admin_setting_configselect(
        'format_customtiles/enablelinearnav',
        new lang_string('linearnavigationsettings', 'core_courseformat'),
        new lang_string('linearnavigationsettings_help', 'core_courseformat'),
        1,
        [1 => get_string('yes'), 0 => get_string('no')],
    ));
}
