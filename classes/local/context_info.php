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
 * Context information: "Module 2 · UF 3 · Activity 4 of 9".
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles\local;

/**
 * Builds the orientation line shown on course, section and activity pages.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class context_info {
    /**
     * Context of a section: parent section (when it is a delegated sub-section) and itself.
     *
     * @param \core_courseformat\base $format
     * @param \section_info $section
     * @return string[] names
     */
    public static function section_trail(\core_courseformat\base $format, \section_info $section): array {
        $trail = [];
        if ($section->is_delegated() && ($component = $section->get_component_instance())) {
            $parent = $component->get_parent_section();
            if ($parent) {
                $trail[] = $format->get_section_name($parent);
            }
        }
        $trail[] = $format->get_section_name($section);
        return $trail;
    }

    /**
     * Text parts for a section page.
     *
     * @param \core_courseformat\base $format
     * @param \section_info $section
     * @return string[]
     */
    public static function for_section(\core_courseformat\base $format, \section_info $section): array {
        $parts = self::section_trail($format, $section);
        $count = count(self::activities_of($format, $section));
        if ($count) {
            $parts[] = get_string('nactivities', 'format_customtiles', $count);
        }
        return $parts;
    }

    /**
     * Text parts for an activity page.
     *
     * @param \core_courseformat\base $format
     * @param \cm_info $cm
     * @return string[]
     */
    public static function for_cm(\core_courseformat\base $format, \cm_info $cm): array {
        $section = $cm->get_section_info();
        $parts = self::section_trail($format, $section);
        $ids = self::activities_of($format, $section);
        $pos = array_search((int)$cm->id, $ids, true);
        if ($pos !== false) {
            $parts[] = get_string('activityxofy', 'format_customtiles', ['x' => $pos + 1, 'y' => count($ids)]);
        }
        return $parts;
    }

    /**
     * Visible activities (with a page of their own) of a section, in order.
     *
     * @param \core_courseformat\base $format
     * @param \section_info $section
     * @return int[] cm ids
     */
    public static function activities_of(\core_courseformat\base $format, \section_info $section): array {
        $modinfo = $format->get_modinfo();
        $ids = [];
        foreach ($modinfo->sections[$section->section] ?? [] as $cmid) {
            $cm = $modinfo->cms[$cmid];
            if ($cm->uservisible && $cm->url && !$cm->is_stealth()) {
                $ids[] = (int)$cmid;
            }
        }
        return $ids;
    }
}
