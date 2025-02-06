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
 * Clean up task.
 *
 * @package   logstore_selective
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2025
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace logstore_selective\task;

defined('MOODLE_INTERNAL') || die();

class cleanup_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('taskcleanup', 'logstore_selective');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute(): void {
        global $DB;

        $settings = get_config('logstore_selective');

        // We need to unset some of the generic config.
        unset($settings->version);
        unset($settings->logguests);
        unset($settings->jsonformat);
        unset($settings->buffersize);

        // Next iterate over each config item and remove matching events older than the defined period.
        foreach ((array) $settings as $eventname => $loglifetime) {
            // If 0 then "Never delete logs" selected so move on.
            if (empty($loglifetime)) {
                continue;
            }

            // Check if the event is enabled.
            if (isset($settings->{$eventname . '_enabled'}) && $settings->{$eventname . '_enabled'}) {
                $loglifetime = time() - ($loglifetime * 3600 * 24); // Value in days.
                $selectparams = [$loglifetime, $eventname];
                $start = time();

                while ($min = $DB->get_field_select('logstore_selective_log', "MIN(timecreated)", "timecreated < ? AND configname = ?", $selectparams)) {
                    $params = [min($min + (3600 * 24), $loglifetime), $eventname];
                    $DB->delete_records_select('logstore_selective_log', "timecreated < ? AND configname = ?", $params);
                    if (time() > $start + 600) {
                        // Do not churn on log deletion for too long each run.
                        break;
                    }
                }
            }
        }

        mtrace(" Deleted old log records from selective store.");
    }
}
