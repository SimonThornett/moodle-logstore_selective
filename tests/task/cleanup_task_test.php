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

namespace logstore_selective\task;

use advanced_testcase;

/**
 * Tests for the cleanup scheduled task.
 *
 * @package   logstore_selective
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \logstore_selective\task\cleanup_task
 */
final class cleanup_task_test extends advanced_testcase {
    /**
     * Insert a selective log record.
     *
     * @param string $configname Processed event name.
     * @param int $timecreated Time created.
     */
    private function insert_log(string $configname, int $timecreated): void {
        global $DB;
        $DB->insert_record('logstore_selective_log', [
            'eventname' => '\\' . str_replace('_', '\\', $configname),
            'configname' => $configname,
            'component' => 'core',
            'action' => 'loggedin',
            'target' => 'user',
            'crud' => 'r',
            'edulevel' => 0,
            'contextid' => 1,
            'contextlevel' => CONTEXT_SYSTEM,
            'contextinstanceid' => 0,
            'userid' => 2,
            'anonymous' => 0,
            'timecreated' => $timecreated,
        ]);
    }

    /**
     * Records older than the event retention are removed, never delete events are kept.
     */
    public function test_execute(): void {
        global $DB;
        $this->resetAfterTest();

        set_config('core_event_user_loggedin_enabled', 1, 'logstore_selective');
        set_config('core_event_user_loggedin_duration', 2, 'logstore_selective');
        set_config('core_event_course_viewed_enabled', 1, 'logstore_selective');
        set_config('core_event_course_viewed_duration', 0, 'logstore_selective');

        $this->insert_log('core_event_user_loggedin', time() - (5 * DAYSECS));
        $this->insert_log('core_event_user_loggedin', time() - HOURSECS);
        $this->insert_log('core_event_course_viewed', time() - (500 * DAYSECS));

        ob_start();
        (new cleanup_task())->execute();
        ob_end_clean();

        $this->assertSame(1, $DB->count_records('logstore_selective_log', ['configname' => 'core_event_user_loggedin']));
        $this->assertSame(1, $DB->count_records('logstore_selective_log', ['configname' => 'core_event_course_viewed']));
    }
}
