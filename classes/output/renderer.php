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
 * Renderer for format_customtiles.
 *
 * @package    format_customtiles
 * @copyright  2026 ayozeweb (https://github.com/ayozeweb)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles\output;

use core_courseformat\output\section_renderer;
use moodle_page;

/**
 * Basic renderer for the format.
 *
 * @package    format_customtiles
 * @copyright  2026 ayozeweb (https://github.com/ayozeweb)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends section_renderer {
    /**
     * Constructor.
     *
     * @param moodle_page $page
     * @param string $target
     */
    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);
        $page->set_other_editing_capability('moodle/course:setcurrentsection');
    }

    /**
     * Section title with in-place editing.
     *
     * @param \section_info|\stdClass $section
     * @param \stdClass $course
     * @return string
     */
    public function section_title($section, $course) {
        return $this->render(course_get_format($course)->inplace_editable_render_section_name($section));
    }

    /**
     * Section title without link.
     *
     * @param \section_info|\stdClass $section
     * @param int|\stdClass $course
     * @return string
     */
    public function section_title_without_link($section, $course) {
        return $this->render(course_get_format($course)->inplace_editable_render_section_name($section, false));
    }

    /**
     * Administration page listing the activity categories.
     *
     * @param array $data template data
     * @return string
     */
    public function render_admin_categories(array $data): string {
        return $this->render_from_template('format_customtiles/local/admin_categories', $data);
    }

    /**
     * Teacher page to assign a category to each activity.
     *
     * @param array $data template data
     * @return string
     */
    public function render_assign_categories(array $data): string {
        return $this->render_from_template('format_customtiles/local/assign_categories', $data);
    }
}
