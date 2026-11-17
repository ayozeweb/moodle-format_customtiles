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
 * Course content output class.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze M de Paz Paz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles\output\courseformat;

use core_courseformat\output\local\content as content_base;
use format_customtiles\local\course_data;
use format_customtiles\local\layouts;
use format_customtiles\local\section_style;
use format_customtiles\local\skin;
use renderer_base;

/**
 * Wraps the core course content with the skin tokens and the course hero.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze M de Paz Paz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content extends content_base {
    /** @var bool The add-section button is shown after the list. */
    protected $hasaddsection = true;

    /**
     * Template name.
     *
     * @param renderer_base $renderer
     * @return string
     */
    public function get_template_name(renderer_base $renderer): string {
        return 'format_customtiles/local/content';
    }

    /**
     * Export data for the template.
     *
     * @param renderer_base $output
     * @return \stdClass
     */
    public function export_for_template(renderer_base $output) {
        if ($this->is_design_view()) {
            // The design view does not print the core list of sections and activities, so it is not
            // exported either: exporting it would also register JavaScript for elements that are not
            // on the page (the completion dropdowns, for instance).
            $data = (object)[
                'title' => $this->format->page_title(),
                'format' => $this->format->get_format(),
                'sectionreturn' => 'null',
                'pagesectionid' => 'null',
                'sections' => [],
            ];
        } else {
            $data = parent::export_for_template($output);
        }
        $options = $this->format->get_format_options();
        $course = $this->format->get_course();

        $layout = $options['layout'] ?? layouts::DEFAULT;
        $data->layout = layouts::exists($layout) ? $layout : layouts::DEFAULT;
        $skin = skin::for_format($this->format);
        $data->skin = $skin['skin'];
        $data->tokens = $skin['tokens'];
        $data->isindex = empty($data->hasnavigation);
        $data->coursename = format_string($course->fullname, true, ['context' => \context_course::instance($course->id)]);

        $legend = [];
        foreach (\format_customtiles\api::get_course_assignments((int)$course->id) as $category) {
            $legend[$category['code']] = \format_customtiles\api::export_badge($category);
        }
        $data->legend = array_values($legend);
        $data->haslegend = !empty($legend);

        $data->mapurl = (new \moodle_url('/course/format/customtiles/map.php', ['id' => $course->id]))->out(false);
        $data->contextid = \context_course::instance($course->id)->id;
        $editing = $this->format->show_editor();
        if (!$data->isindex && !$editing && ($sectionid = $this->format->get_sectionid())) {
            $this->export_section_view($data, (int)$sectionid);
        }
        if (!$data->isindex && ($sectionid = $this->format->get_sectionid())) {
            $section = $this->format->get_modinfo()->get_section_info_by_id($sectionid);
            $parts = $section ? \format_customtiles\local\context_info::for_section($this->format, $section) : [];
            $last = count($parts) - 1;
            $data->parts = array_map(
                fn($text, $i) => ['text' => $text, 'last' => $i === $last],
                $parts,
                array_keys($parts)
            );
            $data->hasctx = !empty($parts);
            if (empty($data->backurl)) {
                $data->backurl = (new \moodle_url('/course/view.php', ['id' => $course->id]))->out(false);
                $data->backlabel = get_string('backto', 'format_customtiles', $data->coursename);
            }
        }

        $data->logos = !empty($options['showlogos']) ? $this->get_logos() : [];
        $data->haslogos = !empty($data->logos);

        if ($data->isindex) {
            if (!$editing) {
                // Before reading the progress, so "new since your previous visit" is up to date.
                course_data::register_visit((int)$course->id);
            }
            $model = new course_data($this->format);
            $data->hero = $this->export_hero($model, $course, !$editing);
            $data->showtiles = !$editing;
            if ($editing && has_capability('moodle/course:update', \context_course::instance($course->id))) {
                // Edit mode: the teacher switches between the core structure view and the design view.
                $data->candesign = true;
                $data->courseid = (int)$course->id;
                $data->designview = (bool)get_user_preferences('format_customtiles_designview', 0);
                if ($data->designview) {
                    $data->showdesign = true;
                    $data->designtiles = $model->get_sections();
                }
            }
            if ($data->showtiles) {
                $data->tiles = $model->get_sections();
                $data->hastiles = !empty($data->tiles);
                $data->emptycourse = empty($data->tiles);
                $data->tilecount = count($data->tiles);
                $this->export_variants($data, $options);
            }
        }
        return $data;
    }

    /**
     * Whether the teacher is looking at the design view of the course page.
     *
     * @return bool
     */
    protected function is_design_view(): bool {
        return $this->format->get_sectionid() === null
            && $this->format->show_editor()
            && get_user_preferences('format_customtiles_designview', 0)
            && has_capability('moodle/course:update', \context_course::instance($this->format->get_courseid()));
    }

    /**
     * Focused section page: header with the look and progress of the section, neighbours and empty state.
     *
     * @param \stdClass $data
     * @param int $sectionid
     */
    protected function export_section_view(\stdClass $data, int $sectionid): void {
        $data->sectionview = true;
        $view = (new course_data($this->format))->get_section_view($sectionid);
        if (!$view) {
            return;
        }
        $data->sechead = $view['item'];
        $data->emptysection = $view['item']['activities'] === 0 && !$view['item']['hasunits'];
        $previous = $view['previous'];
        $next = $view['next'];
        $data->secnav = [
            'hasprevious' => (bool)$previous,
            'previousurl' => $previous['url'] ?? '',
            'previousname' => $previous['name'] ?? '',
            'hasnext' => (bool)$next,
            'nexturl' => $next['url'] ?? '',
            'nextname' => $next['name'] ?? '',
        ];
        $data->hassecnav = $previous || $next;
        if ($view['parent']) {
            // A unit goes back to the section that contains it.
            $data->backurl = $view['parent']['url'];
            $data->backlabel = get_string('backto', 'format_customtiles', $view['parent']['name']);
        }
    }

    /**
     * Institutional logos uploaded by the administrator (the plugin ships none).
     *
     * @return array[] src and alt text (the file name without extension)
     */
    protected function get_logos(): array {
        $context = \context_system::instance();
        $files = get_file_storage()->get_area_files($context->id, 'format_customtiles', 'logos', 0, 'filepath, filename', false);
        $logos = [];
        foreach ($files as $file) {
            $logos[] = [
                'src' => \moodle_url::make_pluginfile_url(
                    $context->id,
                    'format_customtiles',
                    'logos',
                    0,
                    $file->get_filepath(),
                    $file->get_filename()
                )->out(false),
                'alt' => pathinfo($file->get_filename(), PATHINFO_FILENAME),
            ];
        }
        return $logos;
    }

    /**
     * Layout variants to render: the main layout and, when different, the mobile one.
     *
     * @param \stdClass $data
     * @param array $options course format options
     */
    protected function export_variants(\stdClass $data, array $options): void {
        $mobile = $options['layoutmobile'] ?? '';
        $ids = [['id' => $data->layout, 'class' => '']];
        if ($mobile !== '' && layouts::exists($mobile) && $mobile !== $data->layout) {
            $ids = [
                ['id' => $data->layout, 'class' => 'fct-variant--desktop'],
                ['id' => $mobile, 'class' => 'fct-variant--mobile'],
            ];
        }
        $data->variants = [];
        $data->hascarousel = false;
        foreach ($ids as $item) {
            $variant = ['cssclass' => $item['class'], 'is' . $item['id'] => true];
            $data->hascarousel = $data->hascarousel || $item['id'] === 'carousel';
            $data->variants[] = $variant;
        }
    }

    /**
     * Data for the hero header.
     *
     * @param course_data $model
     * @param \stdClass $course
     * @param bool $withcontinue include the continue button
     * @return \stdClass
     */
    protected function export_hero(course_data $model, \stdClass $course, bool $withcontinue): \stdClass {
        $context = \context_course::instance($course->id);
        $hero = new \stdClass();
        $hero->title = format_string($course->fullname, true, ['context' => $context]);
        // The theme already prints the course name as the page heading, so the hero repeats it only on request.
        $hero->showtitle = !empty($this->format->get_format_options()['showherotitle']);
        $summary = '';
        if (trim((string)$course->summary) !== '') {
            $text = file_rewrite_pluginfile_urls($course->summary, 'pluginfile.php', $context->id, 'course', 'summary', null);
            $text = format_text($text, $course->summaryformat, ['context' => $context]);
            $plain = html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8');
            $summary = shorten_text(trim(preg_replace('/\s+/u', ' ', $plain)), 220, true);
        }
        $hero->summary = $summary;
        $hero->image = $this->get_cover_url($course);
        $hero->hasimage = !empty($hero->image);

        $figures = $model->get_context_figures();
        $pills = [];
        if ($figures['sections']) {
            $pills[] = [
                'icon' => 'fa-layer-group',
                'text' => \format_customtiles\local\plural::sections((int)$figures['sections']),
            ];
        }
        if (!empty($figures['units'])) {
            $pills[] = [
                'icon' => 'fa-diagram-project',
                'text' => \format_customtiles\local\plural::units((int)$figures['units']),
            ];
        }
        if ($figures['activities']) {
            $pills[] = [
                'icon' => 'fa-list-check',
                'text' => \format_customtiles\local\plural::activities((int)$figures['activities']),
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
    protected function get_cover_url(\stdClass $course): string {
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
