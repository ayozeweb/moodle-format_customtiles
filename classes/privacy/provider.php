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
 * Privacy provider for format_customtiles.
 *
 * @package    format_customtiles
 * @copyright  2026 ayozeweb (https://github.com/ayozeweb)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\writer;

/**
 * The only personal data is a user preference: the last activity opened in each course.
 *
 * @package    format_customtiles
 * @copyright  2026 ayozeweb (https://github.com/ayozeweb)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\user_preference_provider {
    /**
     * Describe the stored data.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_user_preference('format_customtiles_last_', 'privacy:metadata:preference:last');
        $collection->add_user_preference('format_customtiles_seen_', 'privacy:metadata:preference:seen');
        return $collection;
    }

    /**
     * Export the user preferences.
     *
     * @param int $userid
     */
    public static function export_user_preferences(int $userid) {
        $prefs = get_user_preferences(null, null, $userid);
        foreach ($prefs as $name => $value) {
            foreach (['last', 'seen'] as $kind) {
                if (strpos($name, 'format_customtiles_' . $kind . '_') === 0) {
                    writer::export_user_preference(
                        'format_customtiles',
                        $name,
                        $value,
                        get_string('privacy:metadata:preference:' . $kind, 'format_customtiles')
                    );
                }
            }
        }
    }
}
