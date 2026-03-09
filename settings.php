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
    $eventurl = new moodle_url('/admin/tool/log/store/selective/index.php');

    $ADMIN->add(
        'logging',
        new admin_externalpage(
            'logstore_selective/events',
            get_string('setting:events', 'logstore_selective'),
            $eventurl,
            'moodle/site:config',
            true,
        ),
    );

    // General settings.
    $settings->add(new admin_setting_heading('general', get_string('setting:general', 'logstore_selective'), ''));

    $settings->add(
        new admin_setting_configcheckbox(
            'logstore_selective/jsonformat',
            new lang_string('setting:jsonformat', 'logstore_selective'),
            new lang_string('setting:jsonformat_desc', 'logstore_selective'),
            1
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'logstore_selective/buffersize',
            get_string('setting:buffersize', 'logstore_selective'),
            get_string('setting:buffersize_desc', 'logstore_selective'),
            '50',
            PARAM_INT
        )
    );

    // Event settings.
    $settings->add(
        new admin_setting_heading(
            'events',
            get_string('setting:events', 'logstore_selective'),
            get_string(
                'setting:events_desc',
                'logstore_selective',
                html_writer::link(
                    $eventurl,
                    get_string('setting:events_link', 'logstore_selective'),
                    ['target' => '_blank'],
                ),
            ),
        ),
    );
}
