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
 * Course map as a panel: a bottom sheet on phones and a side panel on wider screens.
 * Without JavaScript (or without <dialog>) the link opens the full, printable map page.
 *
 * @module     format_customtiles/mapsheet
 * @copyright  2026 ayozeweb (https://github.com/ayozeweb)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Fragment from 'core/fragment';
import Templates from 'core/templates';
import {getStrings} from 'core/str';

let dialog = null;

/**
 * Build the dialog once.
 *
 * @param {String[]} strings title, close label
 * @returns {HTMLDialogElement}
 */
const build = (strings) => {
    const element = document.createElement('dialog');
    element.className = 'fct-sheet';
    element.setAttribute('aria-labelledby', 'fct-sheet-title');

    const bar = document.createElement('div');
    bar.className = 'fct-sheet__bar';
    const title = document.createElement('h2');
    title.id = 'fct-sheet-title';
    title.className = 'fct-sheet__title';
    title.textContent = strings[0];
    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'fct-sheet__close';
    close.setAttribute('aria-label', strings[1]);
    close.textContent = '×';
    close.addEventListener('click', () => element.close());
    bar.append(title, close);

    const body = document.createElement('div');
    body.className = 'fct-sheet__body';
    body.dataset.region = 'body';
    element.append(bar, body);

    // A click on the backdrop (the dialog element itself) closes the panel.
    element.addEventListener('click', (e) => {
        if (e.target === element) {
            element.close();
        }
    });
    document.body.appendChild(element);
    return element;
};

/**
 * Open the panel and load the map. If anything fails, fall back to the full page.
 *
 * @param {HTMLAnchorElement} link the link that was activated
 */
const open = async(link) => {
    try {
        const strings = await getStrings([
            {key: 'coursemap', component: 'format_customtiles'},
            {key: 'closemap', component: 'format_customtiles'},
            {key: 'loadingmap', component: 'format_customtiles'},
        ]);
        if (!dialog) {
            dialog = build(strings);
        }
        const body = dialog.querySelector('[data-region="body"]');
        body.textContent = strings[2];
        dialog.showModal();
        // The map template carries no JavaScript, so only the HTML of the fragment is needed.
        const html = await Fragment.loadFragment('format_customtiles', 'map', parseInt(link.dataset.contextid, 10), {});
        Templates.replaceNodeContents(body, html, '');
    } catch (error) {
        window.location.assign(link.href);
    }
};

/**
 * Listen for the map links of the page.
 */
export const init = () => {
    if (document.body.dataset.fctMapReady || !window.HTMLDialogElement) {
        return;
    }
    document.body.dataset.fctMapReady = '1';
    document.addEventListener('click', (e) => {
        const link = e.target.closest('[data-action="fct-map"]');
        if (!link || !link.dataset.contextid || e.ctrlKey || e.metaKey || e.shiftKey) {
            return;
        }
        e.preventDefault();
        open(link);
    });
};
