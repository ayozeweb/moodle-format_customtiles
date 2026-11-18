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

namespace format_customtiles\task;

use format_customtiles\local\section_style;

/**
 * Create the WebP copies of the section covers stored before they existed (queued on upgrade).
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze M de Paz Paz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class webp_covers extends \core\task\adhoc_task {
    /**
     * Run the task.
     */
    public function execute(): void {
        global $DB;
        if (!function_exists('imagewebp')) {
            mtrace('format_customtiles: GD has no WebP support, nothing to do.');
            return;
        }
        $sql = "SELECT cfo.sectionid, cfo.courseid, cfo.value AS filename
                  FROM {course_format_options} cfo
                 WHERE cfo.format = :format AND cfo.name = :name AND cfo.sectionid > 0
                   AND " . $DB->sql_isnotempty('course_format_options', 'cfo.value', true, true);
        $rs = $DB->get_recordset_sql($sql, ['format' => 'customtiles', 'name' => 'ct_cover']);
        $courses = [];
        foreach ($rs as $row) {
            $ctx = \context_course::instance((int)$row->courseid, IGNORE_MISSING);
            if (!$ctx || !section_style::add_webp_variants($ctx->id, (int)$row->sectionid, (string)$row->filename)) {
                continue;
            }
            $params = ['courseid' => $row->courseid, 'format' => 'customtiles', 'sectionid' => $row->sectionid,
                'name' => 'ct_coverwebp'];
            $id = $DB->get_field('course_format_options', 'id', $params);
            if ($id) {
                $DB->set_field('course_format_options', 'value', '1', ['id' => $id]);
            } else {
                $DB->insert_record('course_format_options', (object)($params + ['value' => '1']));
            }
            $courses[(int)$row->courseid] = true;
        }
        $rs->close();
        foreach (array_keys($courses) as $courseid) {
            rebuild_course_cache($courseid, true);
        }
        mtrace('format_customtiles: WebP covers created in ' . count($courses) . ' course(s).');
    }
}
