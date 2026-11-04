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
 * @copyright  2026 Ayoze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles\output\courseformat;

use core_courseformat\output\local\content as content_base;
use format_customtiles\local\layouts;
use format_customtiles\local\skin;
use renderer_base;

/**
 * Wraps the core course content with the skin tokens and the course hero.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze
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
        $data = parent::export_for_template($output);
        $options = $this->format->get_format_options();
        $course = $this->format->get_course();

        $layout = $options['layout'] ?? layouts::DEFAULT;
        $data->layout = layouts::exists($layout) ? $layout : layouts::DEFAULT;
        $skinid = skin::exists($options['skin'] ?? '') ? $options['skin'] : skin::DEFAULT;
        $data->skin = $skinid;
        $data->tokens = skin::css_variables($skinid, $options['basecolor'] ?? '');
        $data->isindex = empty($data->hasnavigation);
        $data->coursename = format_string($course->fullname, true, ['context' => \context_course::instance($course->id)]);
        return $data;
    }
}
