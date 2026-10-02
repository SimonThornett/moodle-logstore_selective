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

use logstore_selective\output\events_page;

require_once(dirname(__FILE__, 6) . '/config.php');
require_once($CFG->dirroot . '/lib/adminlib.php');

navigation_node::override_active_url(new moodle_url('/admin/settings.php', ['section' => 'logsettingselective']));
admin_externalpage_setup('logstore_selective/events');

$PAGE->requires->js_call_amd('logstore_selective/settings', 'init');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('events:heading', 'logstore_selective'));
echo $OUTPUT->render_from_template('logstore_selective/events', (new events_page())->export_for_template($OUTPUT));
echo $OUTPUT->footer();
