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
use core\event\unknown_logged;
use core\event\user_loggedin;

/**
 * Tests for the event list helper.
 *
 * @package   logstore_selective
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \logstore_selective\local\event_list
 */
final class event_list_test extends advanced_testcase {
    /**
     * Events are discovered with their static information and default config.
     */
    public function test_get_events(): void {
        $this->resetAfterTest();
        event_list::reset_cache();

        $events = event_list::get_events();

        $this->assertArrayHasKey('core_event_user_loggedin', $events);
        $event = $events['core_event_user_loggedin'];
        $this->assertSame('\\' . user_loggedin::class, $event['classname']);
        $this->assertSame('\\core\\event\\user_loggedin', $event['eventname']);
        $this->assertSame('core', $event['component']);
        $this->assertSame('user', $event['objecttable']);
        $this->assertFalse($event['enabled']);
        $this->assertSame(0, $event['duration']);

        $classnames = array_column($events, 'classname');
        $this->assertNotContains('\\' . unknown_logged::class, $classnames);
        $this->assertNotContains('\\core\\event\\base', $classnames);
    }

    /**
     * Event config values are read from the plugin settings.
     */
    public function test_get_events_reads_config(): void {
        $this->resetAfterTest();
        set_config('core_event_user_loggedin_enabled', 1, 'logstore_selective');
        set_config('core_event_user_loggedin_duration', 35, 'logstore_selective');

        $event = event_list::get_events()['core_event_user_loggedin'];

        $this->assertTrue($event['enabled']);
        $this->assertSame(35, $event['duration']);
    }

    /**
     * Config names are validated against the discovered events.
     */
    public function test_is_valid_configname(): void {
        $this->assertTrue(event_list::is_valid_configname('core_event_course_viewed'));
        $this->assertFalse(event_list::is_valid_configname('jsonformat'));
        $this->assertFalse(event_list::is_valid_configname('core_event_doesnotexist'));
    }

    /**
     * Data provider for test_is_valid_duration.
     *
     * @return array
     */
    public static function duration_provider(): array {
        return [
            'Never delete' => [0, true],
            'Two days' => [2, true],
            'A year' => [365, true],
            'Not an option' => [3, false],
            'Negative' => [-1, false],
        ];
    }

    /**
     * Durations are limited to the available options.
     *
     * @dataProvider duration_provider
     * @param int $duration
     * @param bool $expected
     */
    public function test_is_valid_duration(int $duration, bool $expected): void {
        $this->assertSame($expected, event_list::is_valid_duration($duration));
    }

    /**
     * Only enabled events are returned with their durations.
     */
    public function test_get_enabled_events(): void {
        $this->resetAfterTest();
        set_config('core_event_user_loggedin_enabled', 1, 'logstore_selective');
        set_config('core_event_user_loggedin_duration', 10, 'logstore_selective');
        set_config('core_event_course_viewed_enabled', 1, 'logstore_selective');
        set_config('core_event_user_loggedout_enabled', 0, 'logstore_selective');
        set_config('core_event_user_loggedout_duration', 5, 'logstore_selective');

        $this->assertEquals(
            [
                'core_event_user_loggedin' => 10,
                'core_event_course_viewed' => 0,
            ],
            event_list::get_enabled_events(),
        );
    }
}
