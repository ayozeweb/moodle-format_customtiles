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
 * Event observers: keep the progress cache fresh.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze M de Paz Paz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles;

use format_customtiles\local\course_data;

/**
 * Observer methods.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze M de Paz Paz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * A completion state changed: drop the cached progress of that user in that course.
     *
     * @param \core\event\course_module_completion_updated $event
     */
    public static function completion_updated(\core\event\course_module_completion_updated $event): void {
        course_data::invalidate((int)$event->relateduserid, (int)$event->courseid);
    }

    /**
     * Remember the last activity a user opened ("continue where you left off"), also from the mobile app.
     *
     * @param \core\event\course_module_viewed $event
     */
    public static function course_module_viewed(\core\event\course_module_viewed $event): void {
        global $USER;
        // Only the user who views the activity, and only in courses of this format.
        if (
            empty($event->courseid) || empty($event->contextinstanceid) || (int)$event->userid !== (int)$USER->id
                || !isloggedin() || isguestuser()
        ) {
            return;
        }
        $format = \core_courseformat\base::instance((int)$event->courseid);
        if ($format->get_format() !== 'customtiles') {
            return;
        }
        course_data::remember_last_activity((int)$event->courseid, (int)$event->contextinstanceid);
    }

    /**
     * A course was completed: drop the cached progress.
     *
     * @param \core\event\course_completed $event
     */
    public static function course_completed(\core\event\course_completed $event): void {
        course_data::invalidate((int)$event->relateduserid, (int)$event->courseid);
    }

    /**
     * A course was deleted: remove what the plugin kept about it (category assignments and the
     * per-user preferences "last activity" and "last visit"). Files go with the course context.
     *
     * @param \core\event\course_deleted $event
     */
    public static function course_deleted(\core\event\course_deleted $event): void {
        global $DB;
        $courseid = (int)$event->objectid;
        $DB->delete_records('format_customtiles_cmcat', ['courseid' => $courseid]);
        $DB->delete_records_select('user_preferences', 'name = ? OR name = ?', [
            course_data::preference_name($courseid),
            course_data::visit_preference_name($courseid),
        ]);
    }

    /**
     * An activity was deleted: forget its category.
     *
     * @param \core\event\course_module_deleted $event
     */
    public static function course_module_deleted(\core\event\course_module_deleted $event): void {
        global $DB;
        $DB->delete_records('format_customtiles_cmcat', ['cmid' => (int)$event->objectid]);
        \cache::make('format_customtiles', 'assignments')->delete((int)$event->courseid);
    }
}
