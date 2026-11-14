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
 * Scroll-snap carousel of course sections: buttons, position dots and keyboard support.
 * Without JavaScript the track still scrolls natively and the controls stay hidden.
 *
 * @module     format_customtiles/carousel
 * @copyright  2026 ayozeweb (https://github.com/ayozeweb)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/**
 * Set up one carousel.
 *
 * @param {HTMLElement} root carousel element
 */
const setup = (root) => {
    if (root.dataset.fctReady) {
        return;
    }
    root.dataset.fctReady = '1';
    const track = root.querySelector('[data-region="track"]');
    const nav = root.querySelector('[data-region="nav"]');
    const dots = root.querySelector('[data-region="dots"]');
    const slides = Array.from(track.children);
    const prev = root.querySelector('[data-action="prev"]');
    const next = root.querySelector('[data-action="next"]');
    if (slides.length < 2) {
        return;
    }
    nav.hidden = false;

    const dotButtons = slides.map((slide, index) => {
        const item = document.createElement('li');
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'fct-carousel__dot';
        button.setAttribute('aria-label', slide.getAttribute('aria-label'));
        button.addEventListener('click', () => goTo(index));
        item.appendChild(button);
        dots.appendChild(item);
        return button;
    });

    const current = () => {
        const left = track.scrollLeft;
        let best = 0;
        let distance = Infinity;
        slides.forEach((slide, index) => {
            const d = Math.abs(slide.offsetLeft - track.offsetLeft - left);
            if (d < distance) {
                distance = d;
                best = index;
            }
        });
        return best;
    };

    const goTo = (index) => {
        const target = slides[Math.max(0, Math.min(slides.length - 1, index))];
        track.scrollTo({
            left: target.offsetLeft - track.offsetLeft,
            behavior: reducedMotion() ? 'auto' : 'smooth',
        });
    };

    let frame = 0;
    const update = () => {
        frame = 0;
        const index = current();
        dotButtons.forEach((button, i) => {
            if (i === index) {
                button.setAttribute('aria-current', 'true');
            } else {
                button.removeAttribute('aria-current');
            }
        });
        prev.disabled = index === 0;
        next.disabled = index === slides.length - 1;
    };

    track.addEventListener('scroll', () => {
        if (!frame) {
            frame = window.requestAnimationFrame(update);
        }
    }, {passive: true});
    prev.addEventListener('click', () => goTo(current() - 1));
    next.addEventListener('click', () => goTo(current() + 1));
    track.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
            const rtl = document.dir === 'rtl';
            const forward = (e.key === 'ArrowRight') !== rtl;
            goTo(current() + (forward ? 1 : -1));
            e.preventDefault();
        }
    });
    update();
};

/**
 * Initialise every carousel on the page.
 */
export const init = () => {
    document.querySelectorAll('[data-region="fct-carousel"]').forEach(setup);
};
