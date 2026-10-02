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

use core\task\scheduled_task;
use logstore_selective\local\event_list;

/**
 * Removes selective log records older than each event's retention period.
 *
 * @package   logstore_selective
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2025
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleanup_task extends scheduled_task {
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

        // Get all enabled config.
        $config = $this->get_config();

        // Next iterate over each config item and remove matching events older than the defined period.
        foreach ($config as $eventname => $duration) {
            // Convert duration to days.
            $duration = time() - ($duration * DAYSECS);
            $selectparams = [$duration, $eventname];
            $start = time();

            $select = 'timecreated < ? AND configname = ?';
            while ($min = $DB->get_field_select('logstore_selective_log', 'MIN(timecreated)', $select, $selectparams)) {
                // Delete a days worth at a time.
                $params = [min($min + DAYSECS, $duration), $eventname];
                $DB->delete_records_select('logstore_selective_log', $select, $params);
                if (time() > $start + 600) {
                    // Do not churn on log deletion for too long each run.
                    break;
                }
            }
        }

        mtrace(" Deleted old log records from selective store.");
    }

    /**
     * Get the enabled events that have a retention period.
     *
     * @return int[] Duration in days keyed by configname.
     */
    private function get_config(): array {
        return array_filter(event_list::get_enabled_events());
    }
}
