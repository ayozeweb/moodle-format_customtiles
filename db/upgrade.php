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
 * @copyright  2026 Ayoze M de Paz Paz
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

    if ($oldversion < 2026111400) {
        // 0.2.0: CSS prefix renamed and new request cache; nothing to migrate in the database.
        upgrade_plugin_savepoint(true, 2026111400, 'format', 'customtiles');
    }

    if ($oldversion < 2026111600) {
        // 0.3.0: section and activity views; new course option with a default, nothing to migrate.
        upgrade_plugin_savepoint(true, 2026111600, 'format', 'customtiles');
    }

    if ($oldversion < 2026111601) {
        // 0.3.1: new hook callback and course option; nothing to migrate.
        upgrade_plugin_savepoint(true, 2026111601, 'format', 'customtiles');
    }

    if ($oldversion < 2026111700) {
        // 0.4.0: own capabilities and new observers. Covers saved before this version keep their
        // original upload until a new image is chosen; nothing to migrate.
        upgrade_plugin_savepoint(true, 2026111700, 'format', 'customtiles');
    }

    if ($oldversion < 2026111701) {
        // 0.5.0: sections aggregate their units (sub-sections). The progress cache changes shape; the
        // caches are purged by the upgrade itself, so there is nothing to migrate.
        upgrade_plugin_savepoint(true, 2026111701, 'format', 'customtiles');
    }

    if ($oldversion < 2026111702) {
        // 0.6.0: design view; new section option (focal point) with a default, nothing to migrate.
        upgrade_plugin_savepoint(true, 2026111702, 'format', 'customtiles');
    }

    if ($oldversion < 2026111800) {
        // 0.7.0: WebP copies of the section covers, created in the background.
        \core\task\manager::queue_adhoc_task(new \format_customtiles\task\webp_covers(), true);
        upgrade_plugin_savepoint(true, 2026111800, 'format', 'customtiles');
    }

    if ($oldversion < 2026111801) {
        // 0.7.1: the design view preference is declared to the Privacy API; nothing to migrate.
        upgrade_plugin_savepoint(true, 2026111801, 'format', 'customtiles');
    }

    if ($oldversion < 2026111900) {
        // 0.8.0: Moodle app support (db/mobile.php) and a new event observer; nothing to migrate.
        upgrade_plugin_savepoint(true, 2026111900, 'format', 'customtiles');
    }

    return true;
}
