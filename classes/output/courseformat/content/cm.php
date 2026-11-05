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
 * Activity output class adding the category badge.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles\output\courseformat\content;

use core_courseformat\output\local\content\cm as cm_base;
use format_customtiles\api;
use renderer_base;
use stdClass;

/**
 * Course module (activity) with an optional category badge.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cm extends cm_base {
    /** @var array[]|null Assignments of the course, loaded once per request. */
    protected static $assignments = [];

    /**
     * Export with the category badge.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $data = parent::export_for_template($output);
        $courseid = (int)$this->format->get_courseid();
        if (!isset(self::$assignments[$courseid])) {
            self::$assignments[$courseid] = api::get_course_assignments($courseid);
        }
        $cmid = (int)$this->mod->id;
        if (isset(self::$assignments[$courseid][$cmid])) {
            $category = self::$assignments[$courseid][$cmid];
            $badge = api::export_badge($category);
            $badge['label'] = get_string('catbadgelabel', 'format_customtiles', $category['name']);
            // The core cm template has no badge slot for formats, so the badge goes in the after-link area.
            $data->afterlink = $output->render_from_template('format_customtiles/local/catbadge', $badge) .
                ($data->afterlink ?? '');
        }
        return $data;
    }

    /**
     * Forget the cached assignments (tests).
     */
    public static function reset_cache(): void {
        self::$assignments = [];
    }
}
