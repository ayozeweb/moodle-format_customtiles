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
 * Colour maths (WCAG contrast) helpers.
 *
 * @package    format_customtiles
 * @copyright  2026 ayozeweb (https://github.com/ayozeweb)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles\local;

/**
 * Static colour helpers used by skins and the palette generator.
 *
 * @package    format_customtiles
 * @copyright  2026 ayozeweb (https://github.com/ayozeweb)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class color {
    /**
     * Normalise a hex colour to #rrggbb or return null when invalid.
     *
     * @param string $hex
     * @return string|null
     */
    public static function normalise(string $hex): ?string {
        $hex = strtolower(trim($hex));
        if (preg_match('/^#?([0-9a-f]{3})$/', $hex, $m)) {
            $hex = $m[1][0] . $m[1][0] . $m[1][1] . $m[1][1] . $m[1][2] . $m[1][2];
        } else if (preg_match('/^#?([0-9a-f]{6})$/', $hex, $m)) {
            $hex = $m[1];
        } else {
            return null;
        }
        return '#' . $hex;
    }

    /**
     * Hex to [r, g, b].
     *
     * @param string $hex
     * @return int[]
     */
    public static function to_rgb(string $hex): array {
        $hex = ltrim(self::normalise($hex) ?? '#000000', '#');
        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    /**
     * [r, g, b] to hex.
     *
     * @param int[] $rgb
     * @return string
     */
    public static function to_hex(array $rgb): string {
        return sprintf(
            '#%02x%02x%02x',
            max(0, min(255, (int)round($rgb[0]))),
            max(0, min(255, (int)round($rgb[1]))),
            max(0, min(255, (int)round($rgb[2])))
        );
    }

    /**
     * WCAG relative luminance.
     *
     * @param string $hex
     * @return float
     */
    public static function luminance(string $hex): float {
        [$r, $g, $b] = array_map(function ($c) {
            $c /= 255;
            return $c <= 0.03928 ? $c / 12.92 : pow(($c + 0.055) / 1.055, 2.4);
        }, self::to_rgb($hex));
        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * WCAG contrast ratio between two colours.
     *
     * @param string $a
     * @param string $b
     * @return float
     */
    public static function contrast(string $a, string $b): float {
        $la = self::luminance($a);
        $lb = self::luminance($b);
        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    /**
     * Mix two colours.
     *
     * @param string $a first colour
     * @param string $b second colour
     * @param float $weight weight of $b, 0..1
     * @return string
     */
    public static function mix(string $a, string $b, float $weight): string {
        $ra = self::to_rgb($a);
        $rb = self::to_rgb($b);
        $out = [];
        for ($i = 0; $i < 3; $i++) {
            $out[$i] = $ra[$i] * (1 - $weight) + $rb[$i] * $weight;
        }
        return self::to_hex($out);
    }

    /**
     * Pick black or white, whichever has more contrast on $bg.
     *
     * @param string $bg
     * @return string
     */
    public static function readable_on(string $bg): string {
        return self::contrast('#ffffff', $bg) >= self::contrast('#111827', $bg) ? '#ffffff' : '#111827';
    }

    /**
     * Move $hex towards black or white until it reaches $min contrast against $against.
     *
     * @param string $hex colour to adjust
     * @param string $against background colour
     * @param float $min minimum contrast
     * @return string adjusted colour (unchanged if it already complies)
     */
    public static function ensure_contrast(string $hex, string $against, float $min = 4.5): string {
        $hex = self::normalise($hex) ?? '#000000';
        if (self::contrast($hex, $against) >= $min) {
            return $hex;
        }
        $target = self::luminance($against) > 0.5 ? '#000000' : '#ffffff';
        for ($w = 0.05; $w <= 1.0001; $w += 0.05) {
            $candidate = self::mix($hex, $target, $w);
            if (self::contrast($candidate, $against) >= $min) {
                return $candidate;
            }
        }
        return $target;
    }
}
