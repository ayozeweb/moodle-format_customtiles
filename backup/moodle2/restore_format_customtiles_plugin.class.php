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
 * Restore of format_customtiles data.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Restore plugin.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_format_customtiles_plugin extends restore_format_plugin {
    /**
     * Course level: activity category assignments.
     *
     * @return restore_path_element[]
     */
    protected function define_course_plugin_structure() {
        return [new restore_path_element('customtiles_cmcategory', $this->get_pathfor('/cmcategories/cmcategory'))];
    }

    /**
     * Section level: cover images.
     *
     * @return restore_path_element[]
     */
    protected function define_section_plugin_structure() {
        return [new restore_path_element('customtiles_section', $this->get_pathfor('/'))];
    }

    /**
     * Keep an assignment until the activities have been restored (see after_restore_course()).
     *
     * @param array $data
     */
    public function process_customtiles_cmcategory($data) {
        $data = (object)$data;
        restore_dbops::set_backup_ids_record(
            $this->get_restoreid(),
            'customtiles_cmcat',
            $data->id,
            0,
            (int)$data->cmid,
            ['code' => $data->categorycode]
        );
    }

    /**
     * Restore the cover image files of a section.
     *
     * @param array $data
     */
    public function process_customtiles_section($data) {
        $data = (object)$data;
        $this->add_related_files('format_customtiles', 'sectioncover', 'course_section', null, $data->sectionid);
    }

    /**
     * Once all activities exist, re-create the category assignments with the new activity ids.
     * Categories are matched by code; unknown codes are ignored.
     */
    public function after_restore_course() {
        global $DB;
        $rows = $DB->get_records('backup_ids_temp', ['backupid' => $this->get_restoreid(), 'itemname' => 'customtiles_cmcat']);
        $courseid = $this->task->get_courseid();
        foreach ($rows as $row) {
            $newcmid = $this->get_mappingid('course_module', $row->parentitemid);
            $info = backup_controller_dbops::decode_backup_temp_info($row->info);
            $category = \format_customtiles\api::get_category_by_code((string)($info['code'] ?? ''));
            if ($newcmid && $category) {
                \format_customtiles\api::set_activity_category($courseid, (int)$newcmid, (int)$category->id);
            }
        }
    }
}
