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
 * @copyright  2026 Ayoze M de Paz Paz
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
 * @copyright  2026 Ayoze M de Paz Paz
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
        $data = \format_customtiles\local\skin::for_format($format) + [
            'wrapped' => true,
            'backurl' => (new \moodle_url('/course/section.php', ['id' => $cminfo->get_section_info()->id]))->out(false),
            'backlabel' => get_string('backto', 'format_customtiles', $format->get_section_name($cminfo->get_section_info())),
            'contextid' => \context_course::instance($PAGE->course->id)->id,
            'parts' => array_map(fn($text, $i) => ['text' => $text, 'last' => $i === $last], $parts, array_keys($parts)),
            'mapurl' => (new \moodle_url('/course/format/customtiles/map.php', ['id' => $PAGE->course->id]))->out(false),
        ];
        $PAGE->requires->js_call_amd('format_customtiles/mapsheet', 'init');
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

    /**
     * Pages of a Custom tiles course: tell the stylesheet whether the breadcrumb stays on phones.
     *
     * By default the one-line context bar replaces it on small screens (it shows the same position and
     * a way back, and leaves more room for content). A course can ask to keep the breadcrumb.
     *
     * @param before_http_headers $hook
     */
    public static function add_body_classes(before_http_headers $hook): void {
        global $PAGE;
        if (
            CLI_SCRIPT || AJAX_SCRIPT || !$PAGE->course || $PAGE->course->id == SITEID
                || $PAGE->course->format !== 'customtiles'
        ) {
            return;
        }
        $options = course_get_format($PAGE->course)->get_format_options();
        if (!empty($options['mobilebreadcrumb'])) {
            $PAGE->add_body_class('fct-keepcrumb');
        }
    }
}
