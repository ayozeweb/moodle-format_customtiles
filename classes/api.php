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
 * Public API: activity categories.
 *
 * Other plugins (for example local_customreport) can use this class to read and import the
 * categories assigned to the activities of a course.
 *
 * @package    format_customtiles
 * @copyright  2026 ayozeweb (https://github.com/ayozeweb)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles;

use format_customtiles\local\color;

/**
 * Activity categories API.
 *
 * @package    format_customtiles
 * @copyright  2026 ayozeweb (https://github.com/ayozeweb)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api {
    /** @var array[] The SEPE preset: code => [name string id, colour, icon]. */
    public const SEPE_PRESET = [
        'AF' => ['sepe_af', '#1d4ed8', 'book-open'],
        'AE' => ['sepe_ae', '#b45309', 'pen-to-square'],
        'PP' => ['sepe_pp', '#0f766e', 'wrench'],
        'PO' => ['sepe_po', '#7e22ce', 'clipboard-check'],
    ];

    /**
     * All categories ordered for display.
     *
     * @return \stdClass[] indexed by id
     */
    public static function get_categories(): array {
        global $DB;
        return $DB->get_records('format_customtiles_cat', null, 'sortorder, code');
    }

    /**
     * One category by code.
     *
     * @param string $code
     * @return \stdClass|null
     */
    public static function get_category_by_code(string $code): ?\stdClass {
        global $DB;
        return $DB->get_record('format_customtiles_cat', ['code' => $code]) ?: null;
    }

    /**
     * Categories assigned to the activities of a course, in one query.
     *
     * @param int $courseid
     * @return array[] cmid => ['id', 'code', 'name', 'color', 'icon']
     */
    public static function get_course_assignments(int $courseid): array {
        global $DB;
        $cache = \cache::make('format_customtiles', 'assignments');
        $cached = $cache->get($courseid);
        if (is_array($cached)) {
            return $cached;
        }
        $rows = $DB->get_records_sql(
            "SELECT m.cmid, c.id, c.code, c.name, c.color, c.icon
               FROM {format_customtiles_cmcat} m
               JOIN {format_customtiles_cat} c ON c.id = m.categoryid
              WHERE m.courseid = :courseid",
            ['courseid' => $courseid]
        );
        $out = [];
        foreach ($rows as $row) {
            $out[(int)$row->cmid] = [
                'id' => (int)$row->id, 'code' => $row->code, 'name' => format_string($row->name),
                'color' => $row->color, 'icon' => $row->icon,
            ];
        }
        $cache->set($courseid, $out);
        return $out;
    }

    /**
     * Assign (or clear, with null) the category of an activity.
     *
     * @param int $courseid
     * @param int $cmid
     * @param int|null $categoryid
     */
    public static function set_activity_category(int $courseid, int $cmid, ?int $categoryid): void {
        global $DB;
        \cache::make('format_customtiles', 'assignments')->delete($courseid);
        $existing = $DB->get_record('format_customtiles_cmcat', ['cmid' => $cmid]);
        if (!$categoryid) {
            if ($existing) {
                $DB->delete_records('format_customtiles_cmcat', ['id' => $existing->id]);
            }
            return;
        }
        if ($existing) {
            if ((int)$existing->categoryid !== $categoryid) {
                $DB->update_record('format_customtiles_cmcat', (object)[
                    'id' => $existing->id, 'categoryid' => $categoryid, 'timemodified' => time()]);
            }
        } else {
            $DB->insert_record('format_customtiles_cmcat', (object)[
                'courseid' => $courseid, 'cmid' => $cmid, 'categoryid' => $categoryid, 'timemodified' => time()]);
        }
    }

    /**
     * Create or update a category.
     *
     * @param \stdClass $data code, name, color, icon (id to update)
     * @return int id
     */
    public static function save_category(\stdClass $data): int {
        global $DB;
        $now = time();
        $icon = (string)($data->icon ?? '');
        $record = (object)[
            'code' => \core_text::substr(preg_replace('/[^A-Za-z0-9_-]/', '', strtoupper(trim($data->code))), 0, 20),
            'name' => \core_text::substr(trim(clean_param($data->name, PARAM_TEXT)), 0, 100),
            'color' => color::normalise($data->color ?? '') ?? '#1d4ed8',
            'icon' => isset(\format_customtiles\local\section_style::get_icons()[$icon]) ? $icon : '',
            'timemodified' => $now,
        ];
        if (!empty($data->id)) {
            $record->id = $data->id;
            \cache::make('format_customtiles', 'assignments')->purge();
            $DB->update_record('format_customtiles_cat', $record);
            return (int)$data->id;
        }
        $record->sortorder = (int)$DB->get_field_sql('SELECT COALESCE(MAX(sortorder), 0) + 1 FROM {format_customtiles_cat}');
        $record->timecreated = $now;
        return (int)$DB->insert_record('format_customtiles_cat', $record);
    }

    /**
     * Delete a category and its assignments.
     *
     * @param int $id
     */
    public static function delete_category(int $id): void {
        global $DB;
        \cache::make('format_customtiles', 'assignments')->purge();
        $DB->delete_records('format_customtiles_cmcat', ['categoryid' => $id]);
        $DB->delete_records('format_customtiles_cat', ['id' => $id]);
    }

    /**
     * Load the SEPE preset (AF, AE, PP, PO); existing codes are left untouched.
     *
     * @return int number of categories created
     */
    public static function load_sepe_preset(): int {
        $created = 0;
        foreach (self::SEPE_PRESET as $code => [$stringid, $colour, $icon]) {
            if (self::get_category_by_code($code)) {
                continue;
            }
            self::save_category((object)[
                'code' => $code, 'name' => get_string($stringid, 'format_customtiles'), 'color' => $colour, 'icon' => $icon]);
            $created++;
        }
        return $created;
    }

    /**
     * Badge presentation of a category: background and a readable foreground.
     *
     * @param array|\stdClass $category
     * @return array
     */
    public static function export_badge($category): array {
        $category = (array)$category;
        $bg = color::normalise($category['color'] ?? '') ?? '#1d4ed8';
        return [
            'code' => $category['code'], 'name' => $category['name'], 'icon' => $category['icon'] ?? '',
            'hasicon' => !empty($category['icon']), 'style' => '--fct-cat-bg:' . $bg . ';--fct-cat-fg:' . color::readable_on($bg),
        ];
    }
}
