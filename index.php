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
 * TODO Add description
 *
 * @package   TODO Add package name
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2025
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../../config.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/admin/tool/log/store/index.php'));
echo $OUTPUT->header();
var_dump(get_config('logstore_selective', 'assignsubmission_comments_event_comment_created_enabled'));
var_dump(get_config('logstore_selective', 'assignsubmission_file_event_submission_created_enabled'));
echo $OUTPUT->footer();
