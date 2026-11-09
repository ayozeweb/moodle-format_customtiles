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
 * Count labels with proper singular/plural strings.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles\local;

/**
 * Language strings cannot rely on a "s" suffix, so singular and plural have their own strings.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plural {
    /**
     * "1 activity" / "3 activities".
     *
     * @param int $count
     * @return string
     */
    public static function activities(int $count): string {
        return get_string($count === 1 ? 'oneactivity' : 'nactivities', 'format_customtiles', $count);
    }

    /**
     * "1 section" / "6 sections".
     *
     * @param int $count
     * @return string
     */
    public static function sections(int $count): string {
        return get_string($count === 1 ? 'onesection' : 'nsections', 'format_customtiles', $count);
    }
}
