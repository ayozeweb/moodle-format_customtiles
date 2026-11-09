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
 * Upgrade steps for format_customtiles.
 *
 * @package    format_customtiles
 * @copyright  2026 ayozeweb (https://github.com/ayozeweb)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade script.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_format_customtiles_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026110403) {
        // Activity categories tables.
        if (!$dbman->table_exists('format_customtiles_cat')) {
            $dbman->install_one_table_from_xmldb_file(__DIR__ . '/install.xml', 'format_customtiles_cat');
        }
        if (!$dbman->table_exists('format_customtiles_cmcat')) {
            $dbman->install_one_table_from_xmldb_file(__DIR__ . '/install.xml', 'format_customtiles_cmcat');
        }
        upgrade_plugin_savepoint(true, 2026110403, 'format', 'customtiles');
    }

    return true;
}
