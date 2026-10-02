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

namespace logstore_selective\external;

use core_external\external_api;
use core_external\tests\externallib_testcase;
use invalid_parameter_exception;
use required_capability_exception;

/**
 * Tests for the save settings web service.
 *
 * @package   logstore_selective
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \logstore_selective\external\save_settings
 */
final class save_settings_test extends externallib_testcase {
    /**
     * Valid settings are saved.
     */
    public function test_execute(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $result = save_settings::execute(json_encode([
            'core_event_user_loggedin_enabled' => true,
            'core_event_user_loggedin_duration' => 35,
            'core_event_course_viewed_enabled' => 0,
        ]));
        $result = external_api::clean_returnvalue(save_settings::execute_returns(), $result);

        $this->assertSame(['saved' => 3], $result);
        $this->assertEquals(1, get_config('logstore_selective', 'core_event_user_loggedin_enabled'));
        $this->assertEquals(35, get_config('logstore_selective', 'core_event_user_loggedin_duration'));
        $this->assertEquals(0, get_config('logstore_selective', 'core_event_course_viewed_enabled'));
    }

    /**
     * Data provider for test_execute_invalid.
     *
     * @return array
     */
    public static function invalid_settings_provider(): array {
        return [
            'Not JSON object' => ['"text"'],
            'Other plugin setting' => ['{"jsonformat": 0}'],
            'Unknown event' => ['{"core_event_doesnotexist_enabled": 1}'],
            'Unknown suffix' => ['{"core_event_user_loggedin_other": 1}'],
            'Invalid duration' => ['{"core_event_user_loggedin_duration": 3}'],
            'Invalid enabled' => ['{"core_event_user_loggedin_enabled": 5}'],
            'String value' => ['{"core_event_user_loggedin_enabled": "1"}'],
        ];
    }

    /**
     * Invalid settings are rejected and nothing is saved.
     *
     * @dataProvider invalid_settings_provider
     * @param string $settings
     */
    public function test_execute_invalid(string $settings): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $jsonformat = get_config('logstore_selective', 'jsonformat');

        try {
            save_settings::execute($settings);
            $this->fail('Exception expected');
        } catch (invalid_parameter_exception $e) {
            $this->assertFalse(get_config('logstore_selective', 'core_event_user_loggedin_enabled'));
            $this->assertSame($jsonformat, get_config('logstore_selective', 'jsonformat'));
        }
    }

    /**
     * A valid setting is not saved when another setting in the request is invalid.
     */
    public function test_execute_partial_invalid(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->expectException(invalid_parameter_exception::class);
        try {
            save_settings::execute(json_encode([
                'core_event_user_loggedin_enabled' => 1,
                'jsonformat' => 0,
            ]));
        } finally {
            $this->assertFalse(get_config('logstore_selective', 'core_event_user_loggedin_enabled'));
        }
    }

    /**
     * Users without site config capability cannot save settings.
     */
    public function test_execute_requires_capability(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());

        $this->expectException(required_capability_exception::class);
        save_settings::execute(json_encode(['core_event_user_loggedin_enabled' => 1]));
    }
}
