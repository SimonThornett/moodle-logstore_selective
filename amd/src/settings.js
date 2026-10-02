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
 * Tracks and saves changes made on the event storage configuration page.
 *
 * @module     logstore_selective/settings
 * @copyright  Catalyst IT, 2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import {getString} from 'core/str';
import {add as addToast} from 'core/toast';
import * as Filters from 'logstore_selective/filters';

const SELECTORS = {
    ROOT: '[data-region="logstore-selective-events"]',
    TOGGLE: '[data-action="toggle-enabled"]',
    DURATION: '[data-action="set-duration"]',
    CONTROL: '[data-action="toggle-enabled"], [data-action="set-duration"]',
};

/**
 * Get the config key and value represented by a control.
 *
 * @param {HTMLElement} control A toggle or duration control.
 * @returns {Object}
 */
const getControlState = (control) => {
    if (control.matches(SELECTORS.TOGGLE)) {
        return {
            key: control.dataset.configname + '_enabled',
            value: control.checked ? 1 : 0,
            initial: Number(control.dataset.initial),
        };
    }
    return {
        key: control.dataset.configname + '_duration',
        value: Number(control.value),
        initial: Number(control.dataset.initial),
    };
};

/**
 * Set a control to a value and notify listeners.
 *
 * @param {HTMLElement} control A toggle or duration control.
 * @param {Number} value The new value.
 */
const setControlValue = (control, value) => {
    if (control.matches(SELECTORS.TOGGLE)) {
        control.checked = !!value;
    } else {
        control.value = String(value);
    }
    control.dispatchEvent(new Event('change', {bubbles: true}));
};

/**
 * Initialise the page.
 */
export const init = () => {
    const root = document.querySelector(SELECTORS.ROOT);
    if (!root) {
        return;
    }

    const changes = new Map();
    const saveButton = root.querySelector('[data-action="save"]');
    const resetButton = root.querySelector('[data-action="reset"]');
    const changesRegion = root.querySelector('[data-region="changes"]');
    const showingRegion = root.querySelector('[data-region="showing"]');
    const enabledRegion = root.querySelector('[data-region="enabledcount"]');

    const updateShowing = async({shown, total}) => {
        showingRegion.textContent = await getString('events:showing', 'logstore_selective', {shown, total});
    };

    const updateCounters = async() => {
        const hasChanges = changes.size > 0;
        saveButton.disabled = !hasChanges;
        resetButton.disabled = !hasChanges;
        const enabled = root.querySelectorAll(SELECTORS.TOGGLE + ':checked').length;
        const [changesText, enabledText] = await Promise.all([
            getString('events:changes', 'logstore_selective', changes.size),
            getString('events:enabledcount', 'logstore_selective', enabled),
        ]);
        changesRegion.textContent = changesText;
        enabledRegion.textContent = enabledText;
    };

    root.addEventListener('change', (e) => {
        const control = e.target.closest(SELECTORS.CONTROL);
        if (!control) {
            return;
        }
        const {key, value, initial} = getControlState(control);
        if (value === initial) {
            changes.delete(key);
        } else {
            changes.set(key, value);
        }
        control.closest('td').classList.toggle('logstore-selective-changed', value !== initial);
        updateCounters().catch(Notification.exception);
    });

    const applyToVisible = (selector, value) => {
        Filters.getVisibleRows(root).forEach((row) => setControlValue(row.querySelector(selector), value));
    };
    root.querySelector('[data-action="bulk-enable"]').addEventListener('click', () => applyToVisible(SELECTORS.TOGGLE, 1));
    root.querySelector('[data-action="bulk-disable"]').addEventListener('click', () => applyToVisible(SELECTORS.TOGGLE, 0));
    root.querySelector('[data-action="bulk-duration"]').addEventListener('click', () => {
        applyToVisible(SELECTORS.DURATION, Number(root.querySelector('[data-region="bulk-duration"]').value));
    });

    resetButton.addEventListener('click', () => {
        root.querySelectorAll(SELECTORS.CONTROL).forEach((control) => {
            setControlValue(control, Number(control.dataset.initial));
        });
    });

    saveButton.addEventListener('click', async() => {
        saveButton.disabled = true;
        try {
            await Ajax.call([{
                methodname: 'logstore_selective_save_settings',
                args: {settings: JSON.stringify(Object.fromEntries(changes))},
            }])[0];
            root.querySelectorAll(SELECTORS.CONTROL).forEach((control) => {
                control.dataset.initial = String(getControlState(control).value);
                control.closest('td').classList.remove('logstore-selective-changed');
            });
            changes.clear();
            await updateCounters();
            addToast(await getString('setting:updated', 'logstore_selective'));
        } catch (error) {
            saveButton.disabled = false;
            Notification.exception(error);
        }
    });

    window.addEventListener('beforeunload', (e) => {
        if (changes.size > 0) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    Filters.init(root, (counts) => updateShowing(counts).catch(Notification.exception));
};
