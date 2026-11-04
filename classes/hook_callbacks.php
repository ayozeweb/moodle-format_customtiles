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
 * Hook callbacks.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles;

use core\hook\output\before_http_headers;

/**
 * Hook callbacks.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Remember the last activity a user opened in a Custom tiles course ("continue where you left off").
     *
     * @param before_http_headers $hook
     */
    public static function remember_last_activity(before_http_headers $hook): void {
        global $PAGE;
        if (CLI_SCRIPT || AJAX_SCRIPT || !isloggedin() || isguestuser()) {
            return;
        }
        $cm = $PAGE->cm;
        $course = $PAGE->course;
        if (!$cm || !$course || $course->format !== 'customtiles') {
            return;
        }
        if (!preg_match('/^mod-[a-z0-9_]+-(view|discuss)$/', (string)$PAGE->pagetype)) {
            return;
        }
        local\course_data::remember_last_activity((int)$course->id, (int)$cm->id);
    }
}
