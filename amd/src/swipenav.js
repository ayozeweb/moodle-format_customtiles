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
 * Swipe left or right on a section page to go to the next or previous section.
 * The same navigation is always available as links (mouse, keyboard, assistive technology).
 *
 * @module     format_customtiles/swipenav
 * @copyright  2026 Ayoze M de Paz Paz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const MIN_DISTANCE = 80;
const MAX_DRIFT = 50;
const MAX_TIME = 600;
const IGNORE = 'input, textarea, select, button, [contenteditable="true"], [data-fct-noswipe], .fct-carousel__track';

/**
 * Whether the element or an ancestor scrolls horizontally (tables, code, embedded content).
 *
 * @param {Element} element
 * @returns {Boolean}
 */
const scrollsSideways = (element) => {
    for (let node = element; node && node !== document.body; node = node.parentElement) {
        if (node.scrollWidth > node.clientWidth + 1 && getComputedStyle(node).overflowX !== 'visible') {
            return true;
        }
    }
    return false;
};

/**
 * Initialise swipe navigation.
 */
export const init = () => {
    const nav = document.querySelector('[data-region="fct-secnav"]');
    if (!nav || nav.dataset.fctReady) {
        return;
    }
    nav.dataset.fctReady = '1';
    let start = null;

    document.addEventListener('touchstart', (e) => {
        start = null;
        const target = e.target;
        if (e.touches.length !== 1 || !target.closest('#region-main') || target.closest(IGNORE) || scrollsSideways(target)) {
            return;
        }
        start = {x: e.touches[0].clientX, y: e.touches[0].clientY, time: Date.now()};
    }, {passive: true});

    document.addEventListener('touchend', (e) => {
        if (!start) {
            return;
        }
        const touch = e.changedTouches[0];
        const dx = touch.clientX - start.x;
        const dy = touch.clientY - start.y;
        const quick = Date.now() - start.time < MAX_TIME;
        start = null;
        if (!quick || Math.abs(dx) < MIN_DISTANCE || Math.abs(dy) > MAX_DRIFT) {
            return;
        }
        // Swiping towards the start of the line moves forward, as when turning a page.
        const forward = (dx < 0) !== (document.dir === 'rtl');
        const link = nav.querySelector(forward ? '[data-action="fct-next"]' : '[data-action="fct-prev"]');
        if (link) {
            window.location.assign(link.href);
        }
    }, {passive: true});
};
