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
 * Event configuration.
 *
 * @package   logstore_selective
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2025
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use logstore_selective\table\events;

require_once( dirname(__FILE__, 6) . '/config.php');
require_once($CFG->dirroot . '/lib/adminlib.php');

navigation_node::override_active_url(new moodle_url('/admin/settings.php', ['section' => 'logsettingselective']));
admin_externalpage_setup('logstore_selective/events');

$PAGE->requires->css('/admin/tool/log/store/selective/styles.css');
$PAGE->requires->js_call_amd('logstore_selective/settings', 'init');

echo $OUTPUT->header();

// Get the events table.
$table = new events();
ob_start();
$table->out();
$eventtable = ob_get_clean();

// Render the template.
echo $OUTPUT->render_from_template(
    'logstore_selective/events',
    [
        'table' => $eventtable,
    ],
);

echo $OUTPUT->footer();
