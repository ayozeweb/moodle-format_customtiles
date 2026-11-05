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
 * Backup of format_customtiles data: activity categories and section cover images.
 *
 * Course and section options live in course_format_options and are backed up by the core.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Backup plugin.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_format_customtiles_plugin extends backup_format_plugin {
    /**
     * Activity categories assigned in the course (stored by category code, which is site-wide).
     *
     * @return backup_plugin_element
     */
    protected function define_course_plugin_structure() {
        $plugin = $this->get_plugin_element(null, $this->get_format_condition(), 'customtiles');
        $wrapper = new backup_nested_element($this->get_recommended_name());
        $assignments = new backup_nested_element('cmcategories');
        $assignment = new backup_nested_element('cmcategory', ['id'], ['cmid', 'categorycode']);

        $plugin->add_child($wrapper);
        $wrapper->add_child($assignments);
        $assignments->add_child($assignment);

        $assignment->set_source_sql(
            'SELECT m.id, m.cmid, c.code AS categorycode
               FROM {format_customtiles_cmcat} m
               JOIN {format_customtiles_cat} c ON c.id = m.categoryid
              WHERE m.courseid = ?',
            [backup::VAR_COURSEID]
        );
        return $plugin;
    }

    /**
     * Section cover images.
     *
     * @return backup_plugin_element
     */
    protected function define_section_plugin_structure() {
        $plugin = $this->get_plugin_element(null, $this->get_format_condition(), 'customtiles');
        $wrapper = new backup_nested_element($this->get_recommended_name(), null, ['sectionid']);
        $plugin->add_child($wrapper);
        $wrapper->set_source_sql('SELECT id AS sectionid FROM {course_sections} WHERE id = ?', [backup::VAR_SECTIONID]);
        $wrapper->annotate_files('format_customtiles', 'sectioncover', 'sectionid');
        return $plugin;
    }
}
