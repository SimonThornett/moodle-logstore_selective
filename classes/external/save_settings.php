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

use core\notification;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

class save_settings extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'settings' => new external_value(PARAM_RAW, 'JSON string of setting'),
        ]);
    }

    /**
     * External function to delete custom presets.
     *
     * @param $settings
     */
    public static function execute($settings): void {
        // Parameter validation.
        self::validate_parameters(self::execute_parameters(), ['settings' => $settings]);

        $settings = json_decode($settings, true);

        foreach ($settings as $settingname => $settingvalue) {
            set_config($settingname, $settingvalue, 'logstore_selective');
        }
        notification::add(get_string('setting:updated', 'logstore_selective'), notification::SUCCESS);
    }

    /**
     * Describes the data returned from the external function.
     */
    public static function execute_returns(): void {}
}