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
 * Design view of the course page in edit mode: switch views, edit the appearance of a section in a
 * modal form and refresh only its tile, and apply automatic colours to the whole course.
 *
 * @module     format_customtiles/designer
 * @copyright  2026 Ayoze M de Paz Paz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalForm from 'core_form/modalform';
import Fragment from 'core/fragment';
import Templates from 'core/templates';
import {add as addToast} from 'core/toast';
import {getString} from 'core/str';
import {setUserPreference} from 'core_user/repository';
import {init as initPreview} from 'format_customtiles/sectionpreview';

/**
 * Visible name of the section a button belongs to.
 *
 * @param {HTMLElement} button
 * @returns {String}
 */
const nameOf = (button) => {
    const holder = button.closest('.fct-designunits__item, [data-region="fct-designtile"]');
    const name = holder ? holder.querySelector('[data-region="name"]') : null;
    return name ? name.textContent.trim() : '';
};

/**
 * Re-render the tiles that contain the given sections (a unit lives inside its section's tile).
 *
 * @param {Number[]} sectionids
 * @param {String} focusid section whose edit button gets the focus back
 */
const refresh = async(sectionids, focusid) => {
    const grid = document.querySelector('[data-region="fct-design"]');
    if (!grid) {
        window.location.reload();
        return;
    }
    const tiles = new Set();
    sectionids.forEach((id) => {
        const button = grid.querySelector(`[data-action="fct-editstyle"][data-sectionid="${id}"]`);
        const tile = button ? button.closest('[data-region="fct-designtile"]') : null;
        if (tile) {
            tiles.add(tile);
        }
    });
    for (const tile of tiles) {
        const {html, js} = await Fragment.loadFragment('format_customtiles', 'designtile',
            parseInt(grid.dataset.contextid, 10), {sectionid: tile.dataset.sectionId})
            .then((html, js) => ({html, js}));
        Templates.replaceNodeContents(tile, html, js);
    }
    const target = grid.querySelector(`[data-action="fct-editstyle"][data-sectionid="${focusid}"]`);
    if (target) {
        target.focus();
    }
};

/**
 * Open the appearance form of a section.
 *
 * @param {HTMLElement} button
 */
const editStyle = (button) => {
    const sectionid = button.dataset.sectionid;
    const form = new ModalForm({
        formClass: 'format_customtiles\\form\\section_style',
        args: {sectionid},
        modalConfig: {title: getString('editappearanceof', 'format_customtiles', nameOf(button)), large: true},
        returnFocus: button,
    });
    form.addEventListener(form.events.FORM_SUBMITTED, async(e) => {
        const updated = (e.detail && e.detail.updated) || [sectionid];
        try {
            await refresh(updated, sectionid);
            addToast(await getString('stylesaved', 'format_customtiles'));
        } catch (error) {
            window.location.reload();
        }
    });
    form.show();
};

/**
 * Open the automatic colours form.
 *
 * @param {HTMLElement} button
 */
const palette = (button) => {
    const form = new ModalForm({
        formClass: 'format_customtiles\\form\\palette',
        args: {courseid: button.dataset.courseid},
        modalConfig: {title: getString('palette_title', 'format_customtiles')},
        returnFocus: button,
    });
    form.addEventListener(form.events.FORM_SUBMITTED, () => window.location.reload());
    form.show();
};

/**
 * Listen for the design controls of the page.
 */
export const init = () => {
    if (document.body.dataset.fctDesignReady) {
        return;
    }
    document.body.dataset.fctDesignReady = '1';
    // JavaScript added while a dynamic form is defined is not sent to the browser, so the live preview of the
    // appearance form starts here as soon as the form appears (also when it is redrawn after a validation error).
    new MutationObserver(() => {
        if (document.querySelector('[data-region="fct-sectionpreview"]:not([data-fct-ready])')) {
            initPreview();
        }
    }).observe(document.body, {childList: true, subtree: true});
    document.addEventListener('click', (e) => {
        const view = e.target.closest('[data-action="fct-view"]');
        if (view) {
            e.preventDefault();
            if (view.getAttribute('aria-pressed') !== 'true') {
                setUserPreference('format_customtiles_designview', view.dataset.value)
                    .then(() => window.location.reload())
                    .catch(() => window.location.reload());
            }
            return;
        }
        const edit = e.target.closest('[data-action="fct-editstyle"]');
        if (edit) {
            e.preventDefault();
            editStyle(edit);
            return;
        }
        const colours = e.target.closest('[data-action="fct-palette"]');
        if (colours) {
            e.preventDefault();
            palette(colours);
        }
    });
};
