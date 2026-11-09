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
 * @copyright  2026 ayozeweb (https://github.com/ayozeweb)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles\local;

/**
 * Registry of skins. Each skin is a flat map of colour tokens; shape/typography tokens
 * are shared and defined in SCSS. Tokens are exposed as CSS variables under the --ct- namespace.
 *
 * @package    format_customtiles
 * @copyright  2026 ayozeweb (https://github.com/ayozeweb)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class skin {
    /** @var string Default skin id. */
    public const DEFAULT = 'light';

    /** @var string Font stack using the bundled Inter. */
    private const FONT_INTER = '"CT Inter", var(--ct-font-base)';

    /** @var string[] Colour tokens every skin must define. */
    public const REQUIRED = [
        'bg', 'surface', 'surface2', 'text', 'muted', 'border', 'primary', 'onprimary', 'accent',
        'success', 'warning', 'danger', 'info',
    ];

    /**
     * Built-in skins (colour tokens).
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
            'corporate' => [
                'bg' => '#eef1f4', 'surface' => '#ffffff', 'surface2' => '#e4e9ef',
                'text' => '#16212c', 'muted' => '#43505e', 'border' => '#6f7d8c',
                'primary' => '#0b4f6c', 'onprimary' => '#ffffff', 'accent' => '#7a4a00',
                'success' => '#14532d', 'warning' => '#7c3d06', 'danger' => '#991b1b', 'info' => '#0b4f6c',
            ],
            'vibrant' => [
                'bg' => '#fbf5ff', 'surface' => '#ffffff', 'surface2' => '#f3e8ff',
                'text' => '#2a1140', 'muted' => '#59407a', 'border' => '#8466ad',
                'primary' => '#6d28d9', 'onprimary' => '#ffffff', 'accent' => '#be185d',
                'success' => '#15803d', 'warning' => '#9a3412', 'danger' => '#b91c1c', 'info' => '#4338ca',
            ],
            'minimal' => [
                'bg' => '#ffffff', 'surface' => '#ffffff', 'surface2' => '#f4f4f5',
                'text' => '#18181b', 'muted' => '#52525b', 'border' => '#71717a',
                'primary' => '#18181b', 'onprimary' => '#ffffff', 'accent' => '#3f3f46',
                'success' => '#166534', 'warning' => '#854d0e', 'danger' => '#b91c1c', 'info' => '#27272a',
            ],
            'highcontrast' => [
                'bg' => '#000000', 'surface' => '#000000', 'surface2' => '#1a1a1a',
                'text' => '#ffffff', 'muted' => '#e5e5e5', 'border' => '#ffffff',
                'primary' => '#ffe600', 'onprimary' => '#000000', 'accent' => '#00e5ff',
                'success' => '#5cff8a', 'warning' => '#ffd24d', 'danger' => '#ff9a9a', 'info' => '#9cc4ff',
            ],
        ];
    }

    /**
     * Shape/typography tokens of the built-in skins (CSS variable => value). Others use the SCSS defaults.
     *
     * @return array[]
     */
    public static function get_shapes(): array {
        return [
            'corporate' => ['radius-s' => '.25rem', 'radius' => '.375rem', 'radius-l' => '.5rem', 'font' => self::FONT_INTER],
            'vibrant' => ['radius-s' => '.75rem', 'radius' => '1.5rem', 'radius-l' => '2rem', 'font' => self::FONT_INTER],
            'minimal' => [
                'radius-s' => '.125rem', 'radius' => '.25rem', 'radius-l' => '.25rem', 'font' => self::FONT_INTER,
                'shadow-1' => 'none', 'shadow-2' => '0 0 0 1px var(--ct-border)',
            ],
            'highcontrast' => [
                'radius-s' => '.25rem', 'radius' => '.25rem', 'radius-l' => '.25rem',
                'font' => '"CT Atkinson", var(--ct-font-base)', 'shadow-1' => '0 0 0 2px var(--ct-border)',
                'shadow-2' => '0 0 0 3px var(--ct-border)', 'lh' => '1.7',
            ],
        ];
    }

    /**
     * All skins available (built-in; admin-defined ones are merged in later milestones).
     *
     * @return array[]
     */
    public static function get_all(): array {
        return self::get_builtin() + self::get_custom();
    }

    /**
     * Skins defined by the administrator: one per line, "id|Name|basepreset|#basecolour".
     *
     * @param string|null $raw raw setting (defaults to the stored one)
     * @return array[] id => tokens, with a 'name' entry kept apart in get_custom_names()
     */
    public static function get_custom(?string $raw = null): array {
        $skins = [];
        foreach (self::parse_custom($raw)['skins'] as $id => $def) {
            $skins[$id] = palette::apply(self::get_builtin()[$def['base']], $def['color']);
        }
        return $skins;
    }

    /**
     * Display names of the administrator skins.
     *
     * @return string[]
     */
    public static function get_custom_names(): array {
        $names = [];
        foreach (self::parse_custom()['skins'] as $id => $def) {
            $names[$id] = $def['name'];
        }
        return $names;
    }

    /**
     * Parse and validate the custom skins setting.
     *
     * @param string|null $raw
     * @return array ['skins' => [id => [name, base, color]], 'errors' => string[]]
     */
    public static function parse_custom(?string $raw = null): array {
        $raw ??= (string)get_config('format_customtiles', 'customskins');
        $builtin = self::get_builtin();
        $result = ['skins' => [], 'errors' => []];
        foreach (preg_split('/\R/', $raw) as $n => $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            $parts = array_map('trim', explode('|', $line));
            $id = $parts[0] ?? '';
            $color = color::normalise($parts[3] ?? '');
            if (
                count($parts) !== 4 || !preg_match('/^[a-z0-9_]{2,20}$/', $id) || isset($builtin[$id])
                    || isset($result['skins'][$id]) || $parts[1] === '' || !isset($builtin[$parts[2]]) || $color === null
            ) {
                $result['errors'][] = get_string('customskinsbadline', 'format_customtiles', $n + 1);
                continue;
            }
            $result['skins'][$id] = ['name' => clean_param($parts[1], PARAM_TEXT), 'base' => $parts[2], 'color' => $color];
        }
        return $result;
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
        return array_merge($options, self::get_custom_names());
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
        foreach (self::get_shapes()[$id] ?? [] as $name => $value) {
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
