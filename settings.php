<?php
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
 * Settings file.
 *
 * @package   logstore_selective
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2025
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use logstore_selective\log\store;
use tool_monitor\eventlist;

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    $settings->add(
        new admin_setting_configcheckbox(
            'logstore_selective/jsonformat',
            new lang_string('jsonformat', 'logstore_selective'),
            new lang_string('jsonformat_desc', 'logstore_selective'),
            1
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'logstore_selective/buffersize',
            get_string('buffersize', 'logstore_selective'),
            '',
            '50',
            PARAM_INT
        )
    );

    $settings->add(
        new admin_setting_heading(
            'select_heading',
            'Select the events you would like to log below',
            'Select whether or not to log this event, and if so, how long it will remain in the database for.'
        )
    );

    $options = [
        2    => new lang_string('numdays', '', 2),
        5    => new lang_string('numdays', '', 5),
        10   => new lang_string('numdays', '', 10),
        35   => new lang_string('numdays', '', 35),
        60   => new lang_string('numdays', '', 60),
        90   => new lang_string('numdays', '', 90),
        120  => new lang_string('numdays', '', 120),
        150  => new lang_string('numdays', '', 150),
        180  => new lang_string('numdays', '', 180),
        365  => new lang_string('numdays', '', 365),
        1000 => new lang_string('numdays', '', 1000),
        0    => new lang_string('neverdeletelogs'),
    ];

    $eventgroups = eventlist::get_all_eventlist();
    foreach ($eventgroups as $eventgroup => $events) {
        $heading = str_replace('_', ' ', $eventgroup);
        $settings->add(new admin_setting_heading($eventgroup, "Component: $heading", ''));
        foreach ($events as $eventname => $displayname) {
            $setting = new admin_setting_configselect(
                'logstore_selective/' . store::get_processed_eventname($eventname),
                $displayname,
                '',
                0,
                $options
            );
            $setting->set_enabled_flag_options(admin_setting_flag::ENABLED, false);
            $settings->add($setting);
        }
    }
}
