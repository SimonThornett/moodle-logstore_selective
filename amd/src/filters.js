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
 * Client side filtering of the event storage configuration table.
 *
 * @module     logstore_selective/filters
 * @copyright  Catalyst IT, 2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {debounce} from 'core/utils';

/**
 * Read the current filter values.
 *
 * @param {HTMLElement} root The page root element.
 * @returns {Object}
 */
const getFilterValues = (root) => {
    const values = {};
    root.querySelectorAll('[data-filter]').forEach((element) => {
        values[element.dataset.filter] = element.value.trim().toLowerCase();
    });
    return values;
};

/**
 * Whether a row matches the given filters.
 *
 * @param {HTMLElement} row The table row.
 * @param {Object} filters The filter values.
 * @returns {Boolean}
 */
const rowMatches = (row, filters) => {
    if (filters.search && !row.dataset.search.includes(filters.search)) {
        return false;
    }
    if (filters.component && row.dataset.component.toLowerCase() !== filters.component) {
        return false;
    }
    if (filters.edulevel && row.dataset.edulevel !== filters.edulevel) {
        return false;
    }
    if (filters.crud && row.dataset.crud !== filters.crud) {
        return false;
    }
    if (filters.status) {
        const enabled = row.querySelector('[data-action="toggle-enabled"]').checked;
        if ((filters.status === 'enabled') !== enabled) {
            return false;
        }
    }
    return true;
};

/**
 * Apply the current filters to the table rows.
 *
 * @param {HTMLElement} root The page root element.
 * @returns {Object} The number of shown and total rows.
 */
export const applyFilters = (root) => {
    const filters = getFilterValues(root);
    const rows = root.querySelectorAll('[data-region="event-row"]');
    let shown = 0;
    rows.forEach((row) => {
        const matches = rowMatches(row, filters);
        row.classList.toggle('d-none', !matches);
        shown += matches ? 1 : 0;
    });
    root.querySelector('[data-region="empty"]').classList.toggle('d-none', shown > 0);
    return {shown, total: rows.length};
};

/**
 * Get the rows currently visible after filtering.
 *
 * @param {HTMLElement} root The page root element.
 * @returns {HTMLElement[]}
 */
export const getVisibleRows = (root) => Array.from(
    root.querySelectorAll('[data-region="event-row"]:not(.d-none)')
);

/**
 * Register the filter listeners.
 *
 * @param {HTMLElement} root The page root element.
 * @param {Function} onFiltered Callback receiving the shown/total counts.
 */
export const init = (root, onFiltered) => {
    const form = root.querySelector('[data-region="filters"]');
    const run = () => onFiltered(applyFilters(root));
    const debounced = debounce(run, 250, {pending: true});

    form.addEventListener('input', (e) => {
        if (e.target.dataset.filter === 'search') {
            debounced();
        }
    });
    form.addEventListener('change', (e) => {
        if (e.target.dataset.filter && e.target.dataset.filter !== 'search') {
            run();
        }
    });
    form.addEventListener('submit', (e) => e.preventDefault());
    form.addEventListener('reset', () => {
        // The reset event fires before the form values are cleared.
        setTimeout(run, 0);
    });
};
