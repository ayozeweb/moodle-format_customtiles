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
 * Accessible palette generator.
 *
 * @package    format_customtiles
 * @copyright  2026 ayozeweb (https://github.com/ayozeweb)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles\local;

/**
 * Builds a WCAG AA palette from a base colour.
 *
 * @package    format_customtiles
 * @copyright  2026 ayozeweb (https://github.com/ayozeweb)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class palette {
    /**
     * Apply a base colour to a skin token map, correcting it to keep AA contrast.
     *
     * @param string[] $tokens skin tokens
     * @param string $base normalised hex colour
     * @param bool $adjusted set to true when the colour was changed
     * @return string[]
     */
    public static function apply(array $tokens, string $base, &$adjusted = false): array {
        $primary = color::ensure_contrast($base, $tokens['surface'], 4.5);
        $primary = color::ensure_contrast($primary, $tokens['bg'], 4.5);
        $adjusted = ($primary !== $base);
        $tokens['primary'] = $primary;
        $tokens['onprimary'] = color::readable_on($primary);
        // Accent: hue-shifted look obtained by mixing the primary with the skin accent, kept AA.
        $accent = color::mix($primary, $tokens['accent'], 0.5);
        $tokens['accent'] = color::ensure_contrast($accent, $tokens['surface'], 4.5);
        return $tokens;
    }

    /**
     * Full tint scale (for section colours) derived from a base colour.
     *
     * @param string $base hex colour
     * @return string[] 'base', 'soft' (background tint), 'strong' (text-safe on soft)
     */
    public static function scale(string $base): array {
        $base = color::normalise($base) ?? '#1d4ed8';
        $soft = color::mix($base, '#ffffff', 0.88);
        return [
            'base' => $base,
            'soft' => $soft,
            'strong' => color::ensure_contrast($base, $soft, 4.5),
        ];
    }
}
