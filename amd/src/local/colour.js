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
 * Colour helpers shared by the live previews: the same WCAG maths as \format_customtiles\local\color,
 * so the contrast the teacher sees is the one the server applies (tests/js/colour.test.mjs checks both
 * against tests/fixtures/colour_parity.json).
 *
 * @module     format_customtiles/local/colour
 * @copyright  2026 Ayoze M de Paz Paz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const HEX = /^#?([0-9a-f]{3}|[0-9a-f]{6})$/i;
export const DARK = '#111827';

/**
 * Normalise a hex colour to #rrggbb or return null.
 *
 * @param {string} value
 * @returns {string|null}
 */
export const normalise = (value) => {
    const m = HEX.exec((value || '').trim());
    if (!m) {
        return null;
    }
    let hex = m[1].toLowerCase();
    if (hex.length === 3) {
        hex = hex.split('').map((c) => c + c).join('');
    }
    return '#' + hex;
};

/**
 * [r, g, b] of a #rrggbb colour.
 *
 * @param {string} hex
 * @returns {number[]}
 */
export const rgb = (hex) => [1, 3, 5].map((i) => parseInt(hex.substr(i, 2), 16));

/**
 * Relative luminance of a #rrggbb colour (WCAG).
 *
 * @param {string} hex
 * @returns {number}
 */
export const luminance = (hex) => {
    const c = rgb(hex).map((v) => {
        v /= 255;
        return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
    });
    return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
};

/**
 * Contrast ratio between two colours (WCAG).
 *
 * @param {string} a
 * @param {string} b
 * @returns {number}
 */
export const contrast = (a, b) => {
    const la = luminance(a);
    const lb = luminance(b);
    return (Math.max(la, lb) + 0.05) / (Math.min(la, lb) + 0.05);
};

/**
 * Mix two colours (weight of b from 0 to 1), as the server does.
 *
 * @param {string} a
 * @param {string} b
 * @param {number} weight
 * @returns {string}
 */
export const mix = (a, b, weight) => {
    const ca = rgb(a);
    const cb = rgb(b);
    return '#' + ca.map((v, i) => Math.round(v * (1 - weight) + cb[i] * weight).toString(16).padStart(2, '0')).join('');
};

/**
 * White or dark text, whichever reads better on a background (as color::readable_on()).
 *
 * @param {string} bg
 * @returns {string}
 */
export const readableOn = (bg) => (contrast('#ffffff', bg) >= contrast(DARK, bg) ? '#ffffff' : DARK);
