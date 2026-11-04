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
 * Visual skins (design token presets).
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles\local;

/**
 * Registry of skins. Each skin is a flat map of colour tokens; shape/typography tokens
 * are shared and defined in SCSS. Tokens are exposed as CSS variables under the --ct- namespace.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class skin {
    /** @var string Default skin id. */
    public const DEFAULT = 'light';

    /** @var string[] Colour tokens every skin must define. */
    public const REQUIRED = [
        'bg', 'surface', 'surface2', 'text', 'muted', 'border', 'primary', 'onprimary', 'accent',
        'success', 'warning', 'danger', 'info',
    ];

    /**
     * Built-in skins.
     *
     * @return array[] id => token map
     */
    public static function get_builtin(): array {
        return [
            'light' => [
                'bg' => '#f5f7fb', 'surface' => '#ffffff', 'surface2' => '#eef2f8',
                'text' => '#1b2433', 'muted' => '#4a5568', 'border' => '#7a889e',
                'primary' => '#1d4ed8', 'onprimary' => '#ffffff', 'accent' => '#0f766e',
                'success' => '#166534', 'warning' => '#92400e', 'danger' => '#b91c1c', 'info' => '#1e40af',
            ],
            'dark' => [
                'bg' => '#0b1220', 'surface' => '#141d2f', 'surface2' => '#1c2740',
                'text' => '#e8edf6', 'muted' => '#b4bfd3', 'border' => '#7084a8',
                'primary' => '#8ab4ff', 'onprimary' => '#0b1220', 'accent' => '#5eead4',
                'success' => '#86efac', 'warning' => '#fcd34d', 'danger' => '#fca5a5', 'info' => '#93c5fd',
            ],
        ];
    }

    /**
     * All skins available (built-in; admin-defined ones are merged in later milestones).
     *
     * @return array[]
     */
    public static function get_all(): array {
        return self::get_builtin();
    }

    /**
     * Whether a skin id exists.
     *
     * @param string $id
     * @return bool
     */
    public static function exists(string $id): bool {
        return array_key_exists($id, self::get_all());
    }

    /**
     * Options for a select: id => localised name.
     *
     * @return string[]
     */
    public static function get_options(): array {
        $options = [];
        foreach (array_keys(self::get_all()) as $id) {
            $options[$id] = get_string_manager()->string_exists('skin_' . $id, 'format_customtiles')
                ? get_string('skin_' . $id, 'format_customtiles') : $id;
        }
        return $options;
    }

    /**
     * Token map for a skin, with an optional base colour applied as primary (adjusted for AA contrast).
     *
     * @param string $id skin id
     * @param string $basecolor optional hex colour
     * @param bool $adjusted set to true when the base colour had to be corrected
     * @return string[]
     */
    public static function get_tokens(string $id, string $basecolor = '', &$adjusted = false): array {
        $all = self::get_all();
        $tokens = $all[$id] ?? $all[self::DEFAULT];
        $adjusted = false;
        $base = color::normalise($basecolor);
        if ($base !== null) {
            $tokens = palette::apply($tokens, $base, $adjusted);
        }
        return $tokens;
    }

    /**
     * Inline style declaring the CSS variables of a skin.
     *
     * @param string $id
     * @param string $basecolor
     * @return string
     */
    public static function css_variables(string $id, string $basecolor = ''): string {
        $css = [];
        foreach (self::get_tokens($id, $basecolor) as $name => $value) {
            $css[] = '--ct-' . $name . ':' . $value;
        }
        return implode(';', $css);
    }

    /**
     * Pairs (foreground, background, minimum contrast) every skin has to satisfy; used by tests.
     *
     * @return array[]
     */
    public static function contrast_pairs(): array {
        return [
            ['text', 'bg', 4.5], ['text', 'surface', 4.5], ['text', 'surface2', 4.5],
            ['muted', 'surface', 4.5], ['muted', 'bg', 4.5], ['muted', 'surface2', 4.5],
            ['onprimary', 'primary', 4.5], ['primary', 'surface', 4.5], ['primary', 'bg', 4.5],
            ['accent', 'surface', 4.5], ['success', 'surface', 4.5], ['warning', 'surface', 4.5],
            ['danger', 'surface', 4.5], ['info', 'surface', 4.5], ['border', 'surface', 3.0],
        ];
    }
}
