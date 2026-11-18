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
 * Parity of the colour maths of the live preview (amd/src/local/colour.js) with the server
 * (classes/local/color.php): both are checked against tests/fixtures/colour_parity.json
 * (the PHP side in tests/design_test.php). Run with: npm run test:js
 *
 * @copyright  2026 Ayoze M de Paz Paz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {test} from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {normalise, luminance, contrast, mix, readableOn} from '../../amd/src/local/colour.js';

const table = JSON.parse(readFileSync(new URL('../fixtures/colour_parity.json', import.meta.url)));

test('normalise', () => {
    for (const [input, expected] of table.normalise) {
        assert.equal(normalise(input), expected, JSON.stringify(input));
    }
});

test('luminance', () => {
    for (const [hex, expected] of table.luminance) {
        assert.ok(Math.abs(luminance(hex) - expected) < 1e-6, hex);
    }
});

test('contrast', () => {
    for (const [a, b, expected] of table.contrast) {
        assert.ok(Math.abs(contrast(a, b) - expected) < 1e-4, a + ' / ' + b);
    }
});

test('mix', () => {
    for (const [a, b, weight, expected] of table.mix) {
        assert.equal(mix(a, b, weight), expected, a + ' + ' + b + ' @ ' + weight);
    }
});

test('readable text colour', () => {
    for (const [bg, expected] of table.readable) {
        assert.equal(readableOn(bg), expected, bg);
    }
});
