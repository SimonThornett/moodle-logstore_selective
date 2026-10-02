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
 * Save settings webservice.
 *
 * @package   logstore_selective
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace logstore_selective\external;

use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use invalid_parameter_exception;
use logstore_selective\local\event_list;

/**
 * Saves the per event enabled and retention settings.
 *
 * @package   logstore_selective
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_settings extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'settings' => new external_value(
                PARAM_RAW,
                'JSON object of <eventconfigname>_enabled and <eventconfigname>_duration settings',
            ),
        ]);
    }

    /**
     * Validate and save the given event settings.
     *
     * @param string $settings JSON encoded object of setting name => value.
     * @return array
     */
    public static function execute(string $settings): array {
        ['settings' => $settings] = self::validate_parameters(self::execute_parameters(), ['settings' => $settings]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        $decoded = json_decode($settings, true);
        if (!is_array($decoded)) {
            throw new invalid_parameter_exception('Settings must be a JSON object');
        }

        $tosave = [];
        foreach ($decoded as $name => $value) {
            $tosave[$name] = self::clean_setting((string) $name, $value);
        }

        foreach ($tosave as $name => $value) {
            set_config($name, $value, event_list::COMPONENT);
        }

        return ['saved' => count($tosave)];
    }

    /**
     * Validate a single setting and return its cleaned value.
     *
     * @param string $name Setting name.
     * @param mixed $value Setting value.
     * @return int
     * @throws invalid_parameter_exception
     */
    private static function clean_setting(string $name, mixed $value): int {
        if (!preg_match('/^(.+)_(enabled|duration)$/', $name, $matches) || !event_list::is_valid_configname($matches[1])) {
            throw new invalid_parameter_exception("Invalid setting: {$name}");
        }
        if (!is_bool($value) && !is_int($value)) {
            throw new invalid_parameter_exception("Invalid value for setting: {$name}");
        }

        $value = (int) $value;
        if ($matches[2] === 'enabled') {
            if ($value !== 0 && $value !== 1) {
                throw new invalid_parameter_exception("Invalid value for setting: {$name}");
            }
            return $value;
        }

        if (!event_list::is_valid_duration($value)) {
            throw new invalid_parameter_exception("Invalid duration for setting: {$name}");
        }
        return $value;
    }

    /**
     * Describes the data returned from the external function.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'saved' => new external_value(PARAM_INT, 'Number of settings saved'),
        ]);
    }
}
