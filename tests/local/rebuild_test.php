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

namespace logstore_selective\local;

use advanced_testcase;
use core\notification;
use core\task\manager;
use logstore_selective\task\rebuild_log_task;

/**
 * Tests for rebuilding the selective log from the standard log.
 *
 * @package   logstore_selective
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \logstore_selective\local\rebuild
 */
final class rebuild_test extends advanced_testcase {
    /**
     * Insert a record into a log table.
     *
     * @param string $table Table name.
     * @param string $eventname Fully qualified event name.
     * @param int $timecreated Time created.
     * @return int Record id.
     */
    private function insert_log(string $table, string $eventname, int $timecreated): int {
        global $DB;
        $record = [
            'eventname' => $eventname,
            'component' => 'core',
            'action' => 'loggedin',
            'target' => 'user',
            'objecttable' => 'user',
            'objectid' => 2,
            'crud' => 'r',
            'edulevel' => 0,
            'contextid' => 1,
            'contextlevel' => CONTEXT_SYSTEM,
            'contextinstanceid' => 0,
            'userid' => 2,
            'courseid' => 0,
            'relateduserid' => null,
            'anonymous' => 0,
            'other' => json_encode(['username' => 'admin']),
            'timecreated' => $timecreated,
            'origin' => 'web',
            'ip' => '127.0.0.1',
            'realuserid' => null,
        ];
        if ($table === 'logstore_selective_log') {
            $record['configname'] = substr(str_replace('\\', '_', $eventname), 1);
        }
        return $DB->insert_record($table, $record);
    }

    /**
     * Ticking the setting queues a task, resets the setting and adds a notification.
     */
    public function test_setting_updated_queues_task(): void {
        $this->resetAfterTest();
        set_config(rebuild::SETTING, 1, 'logstore_selective');

        rebuild::setting_updated();

        $this->assertEquals(0, get_config('logstore_selective', rebuild::SETTING));
        $this->assertCount(1, manager::get_adhoc_tasks(rebuild_log_task::class));
        $notifications = notification::fetch();
        $this->assertCount(1, $notifications);
        $this->assertSame(notification::SUCCESS, $notifications[0]->get_message_type());
    }

    /**
     * A second rebuild is not queued while one is pending.
     */
    public function test_setting_updated_does_not_queue_duplicate(): void {
        $this->resetAfterTest();
        set_config(rebuild::SETTING, 1, 'logstore_selective');
        rebuild::setting_updated();
        notification::fetch();

        set_config(rebuild::SETTING, 1, 'logstore_selective');
        rebuild::setting_updated();

        $this->assertCount(1, manager::get_adhoc_tasks(rebuild_log_task::class));
        $notifications = notification::fetch();
        $this->assertCount(1, $notifications);
        $this->assertSame(notification::INFO, $notifications[0]->get_message_type());
    }

    /**
     * Unticking the setting does nothing.
     */
    public function test_setting_updated_unchecked(): void {
        $this->resetAfterTest();
        set_config(rebuild::SETTING, 0, 'logstore_selective');

        rebuild::setting_updated();

        $this->assertEmpty(manager::get_adhoc_tasks(rebuild_log_task::class));
        $this->assertEmpty(notification::fetch());
    }

    /**
     * Only enabled events within their retention period are copied, and old selective rows are cleared.
     */
    public function test_execute_copies_enabled_events_within_retention(): void {
        global $DB;
        $this->resetAfterTest();
        $now = $this->mock_clock_with_frozen()->time();

        set_config('core_event_user_loggedin_enabled', 1, 'logstore_selective');
        set_config('core_event_user_loggedin_duration', 10, 'logstore_selective');
        set_config('core_event_user_loggedout_enabled', 0, 'logstore_selective');

        $DB->delete_records('logstore_standard_log');
        $this->insert_log('logstore_standard_log', '\\core\\event\\user_loggedin', $now - DAYSECS);
        $this->insert_log('logstore_standard_log', '\\core\\event\\user_loggedin', $now - (5 * DAYSECS));
        $this->insert_log('logstore_standard_log', '\\core\\event\\user_loggedin', $now - (20 * DAYSECS));
        $this->insert_log('logstore_standard_log', '\\core\\event\\user_loggedout', $now - DAYSECS);
        $this->insert_log('logstore_selective_log', '\\core\\event\\user_loggedout', $now - DAYSECS);
        $this->insert_log('logstore_selective_log', '\\core\\event\\user_loggedin', $now - DAYSECS);

        $messages = [];
        $copied = rebuild::execute(function (string $message) use (&$messages): void {
            $messages[] = $message;
        });

        $this->assertSame(2, $copied);
        $this->assertSame(2, $DB->count_records('logstore_selective_log'));
        $this->assertSame(2, $DB->count_records('logstore_selective_log', ['configname' => 'core_event_user_loggedin']));
        $this->assertNotEmpty($messages);

        $record = $DB->get_record('logstore_selective_log', ['timecreated' => $now - DAYSECS]);
        $this->assertSame('127.0.0.1', $record->ip);
        $this->assertSame(['username' => 'admin'], json_decode($record->other, true));
    }

    /**
     * Events set to never delete have their full history copied.
     */
    public function test_execute_never_delete_copies_all(): void {
        global $DB;
        $this->resetAfterTest();
        $now = $this->mock_clock_with_frozen()->time();

        set_config('core_event_user_loggedin_enabled', 1, 'logstore_selective');
        set_config('core_event_user_loggedin_duration', 0, 'logstore_selective');

        $DB->delete_records('logstore_standard_log');
        $this->insert_log('logstore_standard_log', '\\core\\event\\user_loggedin', $now - DAYSECS);
        $this->insert_log('logstore_standard_log', '\\core\\event\\user_loggedin', $now - (2000 * DAYSECS));

        $this->assertSame(2, rebuild::execute());
        $this->assertSame(2, $DB->count_records('logstore_selective_log'));
    }

    /**
     * Selective rows logged after the rebuild started are kept.
     */
    public function test_execute_keeps_rows_logged_after_start(): void {
        global $DB;
        $this->resetAfterTest();
        $now = $this->mock_clock_with_frozen()->time();

        set_config('core_event_user_loggedin_enabled', 1, 'logstore_selective');
        $DB->delete_records('logstore_standard_log');
        $this->insert_log('logstore_selective_log', '\\core\\event\\user_loggedin', $now + 1);

        $this->assertSame(0, rebuild::execute());
        $this->assertSame(1, $DB->count_records('logstore_selective_log'));
    }

    /**
     * Nothing is copied when no events are enabled.
     */
    public function test_execute_no_enabled_events(): void {
        global $DB;
        $this->resetAfterTest();
        $now = $this->mock_clock_with_frozen()->time();

        $this->insert_log('logstore_standard_log', '\\core\\event\\user_loggedin', $now);
        $this->insert_log('logstore_selective_log', '\\core\\event\\user_loggedin', $now);

        $this->assertSame(0, rebuild::execute());
        $this->assertSame(0, $DB->count_records('logstore_selective_log'));
    }
}
