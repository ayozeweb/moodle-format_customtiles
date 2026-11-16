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
 * "Due soon" and "overdue" state of an activity.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze M de Paz Paz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles\local;

/**
 * Reads the dates every activity module declares to the core (core\activity_dates) and turns the
 * next deadline into a short label: due today, tomorrow, in N days, or overdue.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze M de Paz Paz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class due {
    /** @var int A deadline within this number of days is "due soon". */
    public const SOON_DAYS = 7;

    /** @var string[] Identifiers the core modules use for dates that close something for the student. */
    public const DEADLINE_IDS = ['duedate', 'timeclose', 'deadline', 'submissionend', 'assessmentend', 'timeavailableto'];

    /**
     * Deadline state of an activity for a user.
     *
     * @param \cm_info $cm
     * @param int $userid
     * @param bool|null $completed true/false when completion is tracked, null when it is not
     * @param int|null $now for tests
     * @return array|null ['state' => 'soon'|'overdue', 'label', 'date', 'timestamp'] or null when there is nothing to say
     */
    public static function for_cm(\cm_info $cm, int $userid, ?bool $completed, ?int $now = null): ?array {
        if ($completed === true) {
            return null;
        }
        $now ??= time();
        $future = [];
        $past = [];
        foreach (\core\activity_dates::get_dates_for_module($cm, $userid) as $date) {
            $timestamp = (int)($date['timestamp'] ?? 0);
            if (!$timestamp || !in_array($date['dataid'] ?? '', self::DEADLINE_IDS, true)) {
                continue;
            }
            if ($timestamp >= $now) {
                $future[] = $timestamp;
            } else {
                $past[] = $timestamp;
            }
        }
        if ($future) {
            $timestamp = min($future);
            $days = (int)floor((usergetmidnight($timestamp) - usergetmidnight($now)) / DAYSECS);
            if ($days > self::SOON_DAYS) {
                return null;
            }
            if ($days <= 0) {
                $label = get_string('duetoday', 'format_customtiles');
            } else if ($days === 1) {
                $label = get_string('duetomorrow', 'format_customtiles');
            } else {
                $label = get_string('dueindays', 'format_customtiles', $days);
            }
            return self::export('soon', $label, $timestamp);
        }
        // A past deadline is only "overdue" when we know the activity is still to be done.
        if ($past && $completed === false) {
            return self::export('overdue', get_string('overdue', 'format_customtiles'), max($past));
        }
        return null;
    }

    /**
     * Template data.
     *
     * @param string $state
     * @param string $label
     * @param int $timestamp
     * @return array
     */
    private static function export(string $state, string $label, int $timestamp): array {
        return [
            'state' => $state,
            'label' => $label,
            'date' => userdate($timestamp, get_string('strftimedatetimeshort', 'langconfig')),
            'timestamp' => $timestamp,
            'icon' => $state === 'overdue' ? 'fa-triangle-exclamation' : 'fa-clock',
        ];
    }
}
