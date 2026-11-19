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
 * Event observers for format_customtiles.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze M de Paz Paz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\core\event\course_module_completion_updated',
        'callback' => '\format_customtiles\observer::completion_updated',
    ],
    [
        'eventname' => '\core\event\course_deleted',
        'callback' => '\format_customtiles\observer::course_deleted',
    ],
    [
        'eventname' => '\core\event\course_module_deleted',
        'callback' => '\format_customtiles\observer::course_module_deleted',
    ],
    [
        // Every "module viewed" event extends this class: it also covers the mobile app and web services,
        // which never load the activity page that the before_http_headers hook watches.
        'eventname' => '\core\event\course_module_viewed',
        'callback' => '\format_customtiles\observer::course_module_viewed',
    ],
    [
        'eventname' => '\core\event\course_completed',
        'callback' => '\format_customtiles\observer::course_completed',
    ],
];
