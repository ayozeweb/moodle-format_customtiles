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
 * Clean-up when the plugin is uninstalled.
 *
 * @package    format_customtiles
 * @copyright  2026 ayozeweb (https://github.com/ayozeweb)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Remove every file owned by the plugin (institutional logos and section covers) and the
 * "last visited activity" user preferences. Tables and settings are removed by the core.
 *
 * @return bool
 */
function xmldb_format_customtiles_uninstall() {
    global $DB;
    $fs = get_file_storage();
    $contextids = $DB->get_fieldset_sql("SELECT DISTINCT contextid FROM {files} WHERE component = ?", ['format_customtiles']);
    foreach ($contextids as $contextid) {
        $fs->delete_area_files($contextid, 'format_customtiles');
    }
    $DB->delete_records_select('user_preferences', $DB->sql_like('name', '?'), ['format\_customtiles\_last\_%']);
    return true;
}
