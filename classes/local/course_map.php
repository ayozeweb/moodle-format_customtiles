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
 * Data for the course map.
 *
 * @package    format_customtiles
 * @copyright  2026 ayozeweb (https://github.com/ayozeweb)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles\local;

use core_courseformat\base as format_base;

/**
 * Exports the whole course structure (sections and activities) with their state.
 *
 * @package    format_customtiles
 * @copyright  2026 ayozeweb (https://github.com/ayozeweb)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_map {
    /**
     * Template data.
     *
     * @param format_base $format
     * @return array
     */
    public static function export(format_base $format): array {
        $model = new course_data($format);
        $modinfo = $format->get_modinfo();
        $completion = new \completion_info($format->get_course());
        $tracking = $model->has_tracking();
        $sections = [];
        foreach ($model->get_sections() as $tile) {
            $section = $modinfo->get_section_info_by_id($tile['id']);
            $activities = [];
            if (!$tile['locked']) {
                foreach ($modinfo->sections[$section->section] ?? [] as $cmid) {
                    $cm = $modinfo->cms[$cmid];
                    if (!$cm->uservisible || !$cm->url || $cm->is_stealth()) {
                        continue;
                    }
                    $done = false;
                    $tracked = $tracking && $completion->is_enabled($cm);
                    if ($tracked) {
                        $state = (int)$completion->get_data($cm, true)->completionstate;
                        $done = in_array($state, [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS]);
                    }
                    $activities[] = [
                        'name' => $cm->get_formatted_name(),
                        'url' => $cm->url->out(false),
                        'icon' => $cm->get_icon_url()->out(false),
                        'tracked' => $tracked,
                        'done' => $done,
                        'statelabel' => get_string($done ? 'state_completed' : 'state_pending', 'format_customtiles'),
                    ];
                }
            }
            $tile['activitylist'] = $activities;
            $tile['hasactivities'] = !empty($activities);
            $sections[] = $tile;
        }
        $overall = $model->get_overall();
        return [
            'title' => format_string($format->get_course()->fullname),
            'courseurl' => (new \moodle_url('/course/view.php', ['id' => $format->get_courseid()]))->out(false),
            'sections' => $sections,
            'hasoverall' => (bool)$overall,
            'overall' => $overall,
            'overalllabel' => $overall ? get_string(
                'sectionprogress',
                'format_customtiles',
                ['done' => $overall['done'], 'total' => $overall['total']]
            ) : '',
        ];
    }
}
