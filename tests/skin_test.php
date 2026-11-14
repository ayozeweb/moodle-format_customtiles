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
 * Tests for skins, colours and the palette generator.
 *
 * @package    format_customtiles
 * @copyright  2026 ayozeweb (https://github.com/ayozeweb)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles;

use format_customtiles\local\color;
use format_customtiles\local\palette;
use format_customtiles\local\skin;

/**
 * Skin tests.
 *
 * @package    format_customtiles
 * @copyright  2026 ayozeweb (https://github.com/ayozeweb)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \format_customtiles\local\skin
 * @covers     \format_customtiles\local\color
 * @covers     \format_customtiles\local\palette
 */
final class skin_test extends \advanced_testcase {
    /**
     * Every skin defines every token.
     */
    public function test_skins_are_complete(): void {
        foreach (skin::get_all() as $id => $tokens) {
            foreach (skin::REQUIRED as $name) {
                $this->assertArrayHasKey($name, $tokens, "Skin $id lacks token $name");
                $this->assertNotNull(color::normalise($tokens[$name]), "Skin $id token $name is not a colour");
            }
        }
    }

    /**
     * Every skin passes AA contrast on all pairs.
     */
    public function test_skins_have_aa_contrast(): void {
        foreach (skin::get_all() as $id => $tokens) {
            foreach (skin::contrast_pairs() as [$fg, $bg, $min]) {
                $ratio = color::contrast($tokens[$fg], $tokens[$bg]);
                $this->assertGreaterThanOrEqual($min, $ratio, "Skin $id: $fg on $bg has contrast $ratio");
            }
        }
    }

    /**
     * The high contrast skin reaches AAA (7:1) for text.
     */
    public function test_highcontrast_is_aaa(): void {
        $t = skin::get_builtin()['highcontrast'];
        foreach (['text', 'muted', 'primary', 'accent', 'success', 'warning', 'danger', 'info'] as $name) {
            $this->assertGreaterThanOrEqual(7.0, color::contrast($t[$name], $t['surface']), $name);
        }
        $this->assertGreaterThanOrEqual(7.0, color::contrast($t['onprimary'], $t['primary']));
    }

    /**
     * Administrator skins are parsed, validated and kept AA.
     */
    public function test_custom_skins(): void {
        $this->resetAfterTest();
        $raw = "ocean|Ocean|light|#ffff00\nbad line\nlight|Dup|light|#000000\nnight|Night|dark|#112233";
        $parsed = skin::parse_custom($raw);
        $this->assertSame(['ocean', 'night'], array_keys($parsed['skins']));
        $this->assertCount(2, $parsed['errors']);
        set_config('customskins', $raw, 'format_customtiles');
        $all = skin::get_all();
        $this->assertArrayHasKey('ocean', $all);
        foreach (['ocean', 'night'] as $id) {
            foreach (skin::contrast_pairs() as [$fg, $bg, $min]) {
                if ($fg === 'border') {
                    continue;
                }
                $this->assertGreaterThanOrEqual($min, color::contrast($all[$id][$fg], $all[$id][$bg]), "$id $fg/$bg");
            }
        }
        $this->assertSame('Ocean', skin::get_options()['ocean']);
    }

    /**
     * A base colour without contrast is corrected; one with contrast is untouched.
     */
    public function test_palette_adjusts_low_contrast(): void {
        $adjusted = false;
        $tokens = skin::get_tokens('light', '#ffff00', $adjusted);
        $this->assertTrue($adjusted);
        $this->assertGreaterThanOrEqual(4.5, color::contrast($tokens['primary'], $tokens['surface']));
        $this->assertGreaterThanOrEqual(4.5, color::contrast($tokens['onprimary'], $tokens['primary']));

        $tokens = skin::get_tokens('light', '#1d4ed8', $adjusted);
        $this->assertFalse($adjusted);
        $this->assertSame('#1d4ed8', $tokens['primary']);
    }

    /**
     * Palettes derived on the dark skin keep AA contrast too.
     */
    public function test_palette_dark_skin(): void {
        foreach (['#000000', '#ff0000', '#00ff00', '#0000ff', '#777777'] as $base) {
            $tokens = skin::get_tokens('dark', $base);
            $this->assertGreaterThanOrEqual(4.5, color::contrast($tokens['primary'], $tokens['surface']), $base);
            $this->assertGreaterThanOrEqual(4.5, color::contrast($tokens['onprimary'], $tokens['primary']), $base);
            $this->assertGreaterThanOrEqual(4.5, color::contrast($tokens['accent'], $tokens['surface']), $base);
        }
    }

    /**
     * The tint scale used for section colours is text-safe.
     */
    public function test_palette_scale(): void {
        $scale = palette::scale('#f59e0b');
        $this->assertGreaterThanOrEqual(4.5, color::contrast($scale['strong'], $scale['soft']));
    }

    /**
     * Colour normalisation.
     */
    public function test_normalise(): void {
        $this->assertSame('#aabbcc', color::normalise('ABC'));
        $this->assertSame('#112233', color::normalise('#112233'));
        $this->assertNull(color::normalise('red'));
    }

    /**
     * CSS variables are namespaced.
     */
    public function test_css_variables(): void {
        $css = skin::css_variables('light');
        $this->assertStringContainsString('--fct-primary:#1d4ed8', $css);
    }
}
