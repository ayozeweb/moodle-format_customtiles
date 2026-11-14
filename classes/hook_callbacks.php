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
 * @copyright  2026 ayozeweb (https://github.com/ayozeweb)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles;

use core\hook\output\before_http_headers;
use core\hook\output\before_standard_top_of_body_html_generation;
use format_customtiles\local\context_info;

/**
 * Hook callbacks.
 *
 * @package    format_customtiles
 * @copyright  2026 ayozeweb (https://github.com/ayozeweb)
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

    /**
     * Activity pages of a Custom tiles course: add the always-visible context line next to the page heading.
     *
     * @param before_http_headers $hook
     */
    public static function add_activity_context(before_http_headers $hook): void {
        global $PAGE;
        if (CLI_SCRIPT || AJAX_SCRIPT || !$PAGE->cm || !$PAGE->course || $PAGE->course->format !== 'customtiles') {
            return;
        }
        $format = course_get_format($PAGE->course);
        $cminfo = get_fast_modinfo($PAGE->course)->get_cm($PAGE->cm->id);
        $parts = context_info::for_cm($format, $cminfo);
        $last = count($parts) - 1;
        $data = [
            'parts' => array_map(fn($text, $i) => ['text' => $text, 'last' => $i === $last], $parts, array_keys($parts)),
            'mapurl' => (new \moodle_url('/course/format/customtiles/map.php', ['id' => $PAGE->course->id]))->out(false),
        ];
        $PAGE->add_header_action($PAGE->get_renderer('format_customtiles')->render_from_template(
            'format_customtiles/local/ctxbar',
            $data
        ));
    }

    /**
     * Skip link at the very start of the page (first focusable element) in Custom tiles courses.
     *
     * @param before_standard_top_of_body_html_generation $hook
     */
    public static function add_skip_link(before_standard_top_of_body_html_generation $hook): void {
        global $PAGE;
        if (!$PAGE->course || $PAGE->course->id == SITEID || $PAGE->course->format !== 'customtiles') {
            return;
        }
        $hook->add_html(\html_writer::link(
            '#maincontent',
            get_string('skiptocontent', 'format_customtiles'),
            ['class' => 'fct-skip']
        ));
    }
}
