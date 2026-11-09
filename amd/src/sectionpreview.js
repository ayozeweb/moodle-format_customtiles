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
 * Live preview of a section tile in the section edit form.
 *
 * @module     format_customtiles/sectionpreview
 * @copyright  2026 ayozeweb (https://github.com/ayozeweb)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getString} from 'core/str';

const HEX = /^#?([0-9a-f]{3}|[0-9a-f]{6})$/i;

/**
 * Normalise a hex colour to #rrggbb or return null.
 *
 * @param {string} value
 * @returns {string|null}
 */
const normalise = (value) => {
    const m = HEX.exec(value.trim());
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
 * Relative luminance of a #rrggbb colour.
 *
 * @param {string} hex
 * @returns {number}
 */
const luminance = (hex) => {
    const channels = [1, 3, 5].map((i) => {
        const c = parseInt(hex.substr(i, 2), 16) / 255;
        return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
    });
    return 0.2126 * channels[0] + 0.7152 * channels[1] + 0.0722 * channels[2];
};

/**
 * Initialise the preview.
 */
export const init = () => {
    const preview = document.querySelector('[data-region="ct-sectionpreview"]');
    const form = preview ? preview.closest('form') : null;
    if (!form) {
        return;
    }
    const field = (name) => form.querySelector('[name="' + name + '"]');
    const cover = preview.querySelector('.ct-tile__cover');
    const icon = preview.querySelector('[data-region="icon"]');
    const title = preview.querySelector('[data-region="title"]');
    const subtitle = preview.querySelector('[data-region="subtitle"]');
    const time = preview.querySelector('[data-region="time"]');

    const update = () => {
        const name = field('name');
        title.textContent = (name && name.value) || (name && name.placeholder) || '';
        subtitle.textContent = field('ct_subtitle').value;
        const minutes = parseInt(field('ct_minutes').value, 10);
        if (minutes > 0) {
            getString('minutesshort', 'format_customtiles', minutes).then((text) => {
                time.textContent = '~' + text;
                return text;
            }).catch(() => {
                time.textContent = '';
            });
        } else {
            time.textContent = '';
        }
        const iconName = field('ct_icon').value;
        icon.className = 'fa-solid ct-tile__icon' + (iconName ? ' fa-' + iconName : '');
        const c1 = normalise(field('ct_color').value);
        let c2 = normalise(field('ct_color2').value);
        if (c1) {
            c2 = c2 || c1;
            const mid = luminance(c1) + luminance(c2);
            cover.style.background = 'linear-gradient(135deg, ' + c1 + ', ' + c2 + ')';
            cover.style.color = mid / 2 > 0.18 ? '#111827' : '#ffffff';
        } else {
            cover.style.background = '';
            cover.style.color = '';
        }
    };

    ['name', 'ct_subtitle', 'ct_minutes', 'ct_icon', 'ct_color', 'ct_color2'].forEach((name) => {
        const el = field(name);
        if (el) {
            el.addEventListener('input', update);
            el.addEventListener('change', update);
        }
    });
    update();
};
