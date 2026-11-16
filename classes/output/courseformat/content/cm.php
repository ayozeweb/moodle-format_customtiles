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
 * @copyright  2026 Ayoze M de Paz Paz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles\output\courseformat\content;

use core_courseformat\output\local\content\cm as cm_base;
use format_customtiles\api;
use format_customtiles\local\due;
use renderer_base;
use stdClass;

/**
 * Course module (activity) with an optional category badge.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze M de Paz Paz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cm extends cm_base {
    /**
     * Export with the category badge.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $data = parent::export_for_template($output);
        $courseid = (int)$this->format->get_courseid();
        // One query per course and request: the API keeps the assignments in a request cache.
        $assignments = api::get_course_assignments($courseid);
        $cmid = (int)$this->mod->id;
        if (isset($assignments[$cmid])) {
            $category = $assignments[$cmid];
            $badge = api::export_badge($category);
            $badge['label'] = get_string('catbadgelabel', 'format_customtiles', $category['name']);
            // The core cm template has no badge slot for formats, so the badge goes in the after-link area.
            $data->afterlink = $output->render_from_template('format_customtiles/local/catbadge', $badge) .
                ($data->afterlink ?? '');
        }
        $due = $this->export_due();
        if ($due) {
            $data->afterlink = $output->render_from_template('format_customtiles/local/duechip', $due) .
                ($data->afterlink ?? '');
        }
        return $data;
    }

    /**
     * Deadline chip ("due in 3 days", "overdue") for the current user, when the activity has a close date.
     *
     * @return array|null
     */
    protected function export_due(): ?array {
        global $USER;
        if (!isloggedin() || isguestuser()) {
            return null;
        }
        $completed = null;
        $completion = new \completion_info($this->format->get_course());
        if ($completion->is_enabled($this->mod)) {
            $state = (int)$completion->get_data($this->mod, true)->completionstate;
            $completed = in_array($state, [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS]);
        }
        return due::for_cm($this->mod, (int)$USER->id, $completed);
    }
}
