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
 * Live preview of a section in its edit forms (the classic section form and the design view modal):
 * icon, colours and their text contrast, subtitle, time, and the focal point of the cover image.
 *
 * @module     format_customtiles/sectionpreview
 * @copyright  2026 Ayoze M de Paz Paz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getString} from 'core/str';
import {normalise, contrast, mix, readableOn} from 'format_customtiles/local/colour';

/**
 * Set up one preview.
 *
 * @param {HTMLElement} root
 */
const setup = (root) => {
    root.dataset.fctReady = '1';
    const form = root.closest('form');
    if (!form) {
        return;
    }
    const field = (name) => form.querySelector('[name="' + name + '"]');
    const region = (name) => root.querySelector('[data-region="' + name + '"]');
    const cover = region('cover');
    const icon = region('icon');
    const image = region('coverimg');
    const mark = region('focusmark');

    // A native colour picker next to each colour field, kept in sync with the text.
    form.querySelectorAll('[data-fct-colour]').forEach((input) => {
        if (input.nextElementSibling && input.nextElementSibling.dataset.fctPicker) {
            return;
        }
        const picker = document.createElement('input');
        picker.type = 'color';
        picker.className = 'fct-colourpicker';
        picker.dataset.fctPicker = '1';
        const label = form.querySelector('label[for="' + input.id + '"]');
        picker.setAttribute('aria-label', label ? label.textContent.trim() : input.name);
        picker.value = normalise(input.value) || '#1d4ed8';
        picker.addEventListener('input', () => {
            input.value = picker.value;
            input.dispatchEvent(new Event('input', {bubbles: true}));
        });
        input.addEventListener('input', () => {
            const hex = normalise(input.value);
            if (hex) {
                picker.value = hex;
            }
        });
        input.after(picker);
    });

    const updateFocus = () => {
        const x = Math.max(0, Math.min(100, parseInt((field('focusx') || {}).value, 10) || 50));
        const y = Math.max(0, Math.min(100, parseInt((field('focusy') || {}).value, 10) || 50));
        if (image) {
            image.style.objectPosition = x + '% ' + y + '%';
        }
        if (mark) {
            mark.style.insetInlineStart = x + '%';
            mark.style.insetBlockStart = y + '%';
        }
        return [x, y];
    };

    const setFocus = (x, y) => {
        if (field('focusx') && field('focusy')) {
            field('focusx').value = Math.round(Math.max(0, Math.min(100, x)));
            field('focusy').value = Math.round(Math.max(0, Math.min(100, y)));
            updateFocus();
        }
    };

    const updateText = () => {
        const title = region('title');
        const name = field('name');
        if (title && name) {
            title.textContent = name.value || name.placeholder || '';
        } else if (title && !title.textContent) {
            title.textContent = root.dataset.name || '';
        }
        region('subtitle').textContent = field('ct_subtitle') ? field('ct_subtitle').value : '';
        const minutes = field('ct_minutes') ? parseInt(field('ct_minutes').value, 10) : 0;
        const time = region('time');
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
        const iconName = field('ct_icon') ? field('ct_icon').value : '';
        icon.className = 'fa-solid fct-cover__icon' + (iconName ? ' fa-' + iconName : '');
    };

    // Messages are fetched asynchronously: only the answer to the latest change may be shown.
    let contrastRequest = 0;

    const updateColours = () => {
        const request = ++contrastRequest;
        const c1 = normalise(field('ct_color') ? field('ct_color').value : '');
        const note = region('contrast');
        if (!c1) {
            cover.style.background = '';
            cover.style.color = '';
            if (note) {
                note.textContent = '';
                delete note.dataset.state;
            }
            return;
        }
        const c2 = normalise(field('ct_color2') ? field('ct_color2').value : '') || mix(c1, '#000000', 0.35);
        const mid = mix(c1, c2, 0.5);
        const fg = readableOn(mid);
        cover.style.background = 'linear-gradient(135deg, ' + c1 + ', ' + c2 + ')';
        cover.style.color = fg;
        if (!note) {
            return;
        }
        const ratio = Math.min(contrast(fg, c1), contrast(fg, c2));
        const shown = (Math.floor(ratio * 10) / 10).toLocaleString(document.documentElement.lang || undefined);
        getString(ratio >= 4.5 ? 'contrastok' : 'contrastlow', 'format_customtiles', shown).then((text) => {
            if (request !== contrastRequest) {
                return text;
            }
            note.textContent = text;
            note.dataset.state = ratio >= 4.5 ? 'ok' : 'low';
            return text;
        }).catch(() => null);
    };

    const update = () => {
        updateText();
        updateColours();
    };

    // The cover the teacher picks in the file manager, shown before saving.
    const watchFiles = () => {
        const manager = form.querySelector('.filemanager');
        if (!manager || !image) {
            return;
        }
        const sync = () => {
            const thumb = manager.querySelector('.fp-file img, .fp-thumbnail img');
            if (thumb && thumb.src) {
                const url = new URL(thumb.src, window.location.href);
                url.searchParams.delete('preview');
                url.searchParams.delete('oid');
                if (image.src !== url.href) {
                    image.src = url.href;
                }
                image.hidden = false;
            } else if (manager.querySelector('.fp-content') && !manager.querySelector('.fp-file')) {
                image.hidden = true;
            }
            const hint = region('focushint');
            if (mark) {
                mark.hidden = image.hidden;
            }
            if (hint) {
                hint.hidden = image.hidden;
            }
        };
        new MutationObserver(sync).observe(manager, {childList: true, subtree: true, attributes: true,
            attributeFilter: ['src']});
        sync();
    };

    // Pick the focal point on the preview with a pointer or the arrow keys.
    if (image && field('focusx')) {
        cover.tabIndex = 0;
        cover.removeAttribute('aria-hidden');
        cover.setAttribute('role', 'group');
        getString('focuspoint', 'format_customtiles').then((text) => cover.setAttribute('aria-label', text)).catch(() => null);
        cover.addEventListener('click', (e) => {
            if (image.hidden) {
                return;
            }
            const box = cover.getBoundingClientRect();
            setFocus(100 * (e.clientX - box.left) / box.width, 100 * (e.clientY - box.top) / box.height);
        });
        cover.addEventListener('keydown', (e) => {
            const moves = {ArrowLeft: [-5, 0], ArrowRight: [5, 0], ArrowUp: [0, -5], ArrowDown: [0, 5]};
            if (!moves[e.key] || image.hidden) {
                return;
            }
            e.preventDefault();
            const [x, y] = updateFocus();
            setFocus(x + moves[e.key][0], y + moves[e.key][1]);
        });
        ['focusx', 'focusy'].forEach((name) => field(name).addEventListener('input', updateFocus));
        updateFocus();
        if (mark) {
            mark.hidden = image.hidden;
        }
    }

    ['name', 'ct_subtitle', 'ct_minutes', 'ct_icon', 'ct_color', 'ct_color2'].forEach((name) => {
        const el = field(name);
        if (el) {
            el.addEventListener('input', update);
            el.addEventListener('change', update);
        }
    });
    watchFiles();
    update();
};

/**
 * Initialise every preview not set up yet (the modal form adds a new one each time it opens).
 */
export const init = () => {
    document.querySelectorAll('[data-region="fct-sectionpreview"]:not([data-fct-ready])').forEach(setup);
};
