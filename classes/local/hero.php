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
 * Course header ("hero") data, shared by the course page and the mobile app.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze M de Paz Paz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles\local;

/**
 * Course header data: cover, summary, context pills, overall progress and the continue target.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze M de Paz Paz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hero {
    /**
     * Data for the hero header.
     *
     * @param course_data $model
     * @param \core_courseformat\base $format
     * @param bool $withcontinue include the continue button
     * @return \stdClass
     */
    public static function export(course_data $model, \core_courseformat\base $format, bool $withcontinue): \stdClass {
        $course = $format->get_course();
        $context = \context_course::instance($course->id);
        $hero = new \stdClass();
        $hero->title = format_string($course->fullname, true, ['context' => $context]);
        // The theme already prints the course name as the page heading, so the hero repeats it only on request.
        $hero->showtitle = !empty($format->get_format_options()['showherotitle']);
        $summary = '';
        if (trim((string)$course->summary) !== '') {
            $text = file_rewrite_pluginfile_urls($course->summary, 'pluginfile.php', $context->id, 'course', 'summary', null);
            $text = format_text($text, $course->summaryformat, ['context' => $context]);
            $plain = html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8');
            $summary = shorten_text(trim(preg_replace('/\s+/u', ' ', $plain)), 220, true);
        }
        $hero->summary = $summary;
        $hero->image = self::get_cover_url($course);
        $hero->hasimage = !empty($hero->image);

        $figures = $model->get_context_figures();
        $pills = [];
        if ($figures['sections']) {
            $pills[] = [
                'icon' => 'fa-layer-group',
                'text' => plural::sections((int)$figures['sections']),
            ];
        }
        if (!empty($figures['units'])) {
            $pills[] = [
                'icon' => 'fa-diagram-project',
                'text' => plural::units((int)$figures['units']),
            ];
        }
        if ($figures['activities']) {
            $pills[] = [
                'icon' => 'fa-list-check',
                'text' => plural::activities((int)$figures['activities']),
            ];
        }
        if (!empty($figures['minutes'])) {
            $pills[] = [
                'icon' => 'fa-clock',
                'text' => get_string('duration', 'format_customtiles', section_style::format_minutes($figures['minutes'])),
            ];
        }
        if (!empty($course->startdate)) {
            $pills[] = ['icon' => 'fa-calendar', 'text' => get_string(
                'startsonx',
                'format_customtiles',
                userdate($course->startdate, get_string('strftimedatefullshort', 'langconfig'))
            )];
        }
        if (!empty($course->enddate)) {
            $pills[] = ['icon' => 'fa-flag-checkered', 'text' => get_string(
                'endsonx',
                'format_customtiles',
                userdate($course->enddate, get_string('strftimedatefullshort', 'langconfig'))
            )];
        }
        $hero->pills = $pills;
        $hero->haspills = !empty($pills);

        $overall = $model->get_overall();
        if ($overall) {
            $hero->hasprogress = true;
            $hero->percent = $overall['percent'];
            $hero->done = $overall['done'];
            $hero->total = $overall['total'];
            $hero->progresslabel = get_string(
                'sectionprogress',
                'format_customtiles',
                ['done' => $overall['done'], 'total' => $overall['total']]
            );
            $hero->ringoffset = round(100 - $overall['percent'], 1);
        }
        if ($withcontinue && ($target = $model->get_continue())) {
            $hero->continue = (object)$target;
            $hero->continuelabel = $target['iscomplete']
                ? get_string('reviewcourse', 'format_customtiles')
                : ($target['isfirst'] ? get_string('startcourse', 'format_customtiles')
                    : get_string('continue', 'format_customtiles'));
        }
        return $hero;
    }

    /**
     * URL of the course cover image (first course overview file).
     *
     * @param \stdClass $course
     * @return string empty when there is none
     */
    public static function get_cover_url(\stdClass $course): string {
        global $CFG;
        $files = (new \core_course_list_element($course))->get_course_overviewfiles();
        foreach ($files as $file) {
            if ($file->is_valid_image()) {
                return \moodle_url::make_file_url(
                    "$CFG->wwwroot/pluginfile.php",
                    '/' . $file->get_contextid() . '/' . $file->get_component() . '/' .
                    $file->get_filearea() . $file->get_filepath() . $file->get_filename()
                )->out(false);
            }
        }
        return '';
    }
}
