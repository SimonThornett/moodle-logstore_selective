
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

import Ajax from 'core/ajax';
import Notification from 'core/notification';

/**
 * Module to send the modified settings.
 *
 * @module     logstore_selective/settings
 * @copyright  2020 Peter Burnett <peterburnett@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
export const init = () => {

    const changedSettings = {};

    const submit = document.querySelector("#logstore-selective-submit");

    const enableCheckboxes = document.getElementsByName("enable[]");
    const durationSelects = document.getElementsByName("duration[]");

    const changes = document.querySelector("#logstore-selective-setting-changes");

    /**
     * Monitor each of the checkboxes for changes and increment the change counter.
     */
    enableCheckboxes.forEach(function(enableCheckbox) {
        enableCheckbox.addEventListener('change', function(e) {
            const checked = !!e.target.checked;
            const eventname = e.target.dataset.eventname;
            const currentvalue = !!Number(e.target.dataset.currentvalue);
            changes.dataset.value = Number(changes.dataset.value);
            if (checked !== currentvalue) {
                changes.dataset.value++;
                e.target.classList.add('changed');
                changedSettings[eventname + '_enabled'] = checked;
            } else {
                changes.dataset.value--;
                e.target.classList.remove('changed');
                delete changedSettings[eventname + '_enabled'];
            }
            changes.innerHTML = changes.dataset.value;
        });
    });

    /**
     * Monitor each of the checkboxes for changes and increment the change counter.
     */
    durationSelects.forEach(function(durationSelect) {
        durationSelect.addEventListener('change', function(e) {
            const selected = Number(e.target.value);
            const eventname = e.target.dataset.eventname;
            const currentvalue = Number(e.target.dataset.currentvalue);
            changes.dataset.value = Number(changes.dataset.value);
            if (selected !== currentvalue) {
                // It's different from current, but has already been changed in this session.
                if (!changedSettings.hasOwnProperty(eventname + '_duration')) {
                    changes.dataset.value++;
                    e.target.classList.add('changed');
                }
                changedSettings[eventname + '_duration'] = selected;
            } else {
                changes.dataset.value--;
                e.target.classList.remove('changed');
                delete changedSettings[eventname + '_duration'];
            }
            changes.innerHTML = changes.dataset.value;
        });
    });

    submit.addEventListener('click', function() {
        // Reload the page once saved.
        let request = Ajax.call([{
            methodname: 'logstore_selective_save_settings',
            args: {settings: JSON.stringify(changedSettings)}
        }]);
        request[0].done(async function() {
            window.location.reload();
        }).fail(Notification.exception);
    });
};
