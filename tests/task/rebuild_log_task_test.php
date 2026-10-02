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
use core\task\manager;
use logstore_selective\local\rebuild;

/**
 * Tests for the rebuild log adhoc task.
 *
 * @package   logstore_selective
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \logstore_selective\task\rebuild_log_task
 */
final class rebuild_log_task_test extends advanced_testcase {
    /**
     * The queued task refills the selective log when run.
     */
    public function test_execute(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        set_config('core_event_user_loggedin_enabled', 1, 'logstore_selective');
        $DB->delete_records('logstore_standard_log');
        $DB->insert_record('logstore_standard_log', [
            'eventname' => '\\core\\event\\user_loggedin',
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
            'anonymous' => 0,
            'other' => '[]',
            'timecreated' => time() - HOURSECS,
        ]);

        $this->assertTrue(rebuild::queue_task());
        $this->assertSame('Rebuild selective log from standard log', (new rebuild_log_task())->get_name());

        ob_start();
        $this->runAdhocTasks(rebuild_log_task::class);
        $output = ob_get_clean();

        $this->assertStringContainsString('1 records copied', $output);
        $this->assertSame(1, $DB->count_records('logstore_selective_log'));
        $this->assertEmpty(manager::get_adhoc_tasks(rebuild_log_task::class));
    }
}
