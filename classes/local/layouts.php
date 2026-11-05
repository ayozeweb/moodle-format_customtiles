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
 * Registry of course layouts.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles\local;

/**
 * Available layouts. All share data and tokens; only the composition changes.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class layouts {
    /** @var string Default layout. */
    public const DEFAULT = 'tiles';

    /**
     * Layout ids implemented so far.
     *
     * @return string[]
     */
    public static function get_ids(): array {
        return ['tiles', 'cards', 'path', 'carousel', 'bento', 'list'];
    }

    /**
     * Whether a layout id exists.
     *
     * @param string $id
     * @return bool
     */
    public static function exists(string $id): bool {
        return in_array($id, self::get_ids(), true);
    }

    /**
     * Select options id => name.
     *
     * @return string[]
     */
    public static function get_options(): array {
        $options = [];
        foreach (self::get_ids() as $id) {
            $options[$id] = get_string('layout_' . $id, 'format_customtiles');
        }
        return $options;
    }
}
