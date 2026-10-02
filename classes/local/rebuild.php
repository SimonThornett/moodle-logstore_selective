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

use core\clock;
use core\di;
use core\notification;
use core\task\manager;
use logstore_selective\task\rebuild_log_task;

/**
 * Rebuilds the selective log table from the standard log table.
 *
 * @package   logstore_selective
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rebuild {
    /** @var string Name of the rebuild trigger setting. */
    public const SETTING = 'rebuildlog';

    /** @var string Columns shared by the standard and selective log tables. */
    private const COLUMNS = 'eventname, component, action, target, objecttable, objectid, crud, edulevel, contextid, '
        . 'contextlevel, contextinstanceid, userid, courseid, relateduserid, anonymous, other, timecreated, origin, ip, '
        . 'realuserid';

    /**
     * Admin setting updated callback for the rebuild checkbox.
     *
     * Resets the checkbox and queues the rebuild task when it has been ticked.
     */
    public static function setting_updated(): void {
        if (!get_config(event_list::COMPONENT, self::SETTING)) {
            return;
        }
        set_config(self::SETTING, 0, event_list::COMPONENT);

        if (self::queue_task()) {
            notification::success(get_string('rebuild:queued', 'logstore_selective'));
        } else {
            notification::info(get_string('rebuild:alreadyqueued', 'logstore_selective'));
        }
    }

    /**
     * Queue the rebuild task unless one is already pending.
     *
     * @return bool True if a new task was queued.
     */
    public static function queue_task(): bool {
        return (bool) manager::queue_adhoc_task(new rebuild_log_task(), true);
    }

    /**
     * Clear the selective log table and refill it from the standard log.
     *
     * Only enabled events are copied, limited to their configured retention period.
     * Rows logged after the rebuild started are retained and not duplicated.
     *
     * @param callable|null $trace Optional callback receiving progress messages.
     * @return int Number of records copied.
     */
    public static function execute(?callable $trace = null): int {
        global $DB;

        $trace = function (string $message) use ($trace): void {
            if ($trace !== null) {
                $trace($message);
            }
        };

        if (!$DB->get_manager()->table_exists('logstore_standard_log')) {
            $trace(get_string('rebuild:nostandardlog', 'logstore_selective'));
            return 0;
        }

        $now = di::get(clock::class)->time();
        $maxid = (int) $DB->get_field_sql('SELECT MAX(id) FROM {logstore_standard_log}');

        $DB->delete_records_select('logstore_selective_log', 'timecreated <= :now', ['now' => $now]);
        $trace(get_string('rebuild:cleared', 'logstore_selective'));

        $events = event_list::get_events();
        $total = 0;
        foreach (event_list::get_enabled_events() as $configname => $duration) {
            if (!isset($events[$configname])) {
                continue;
            }

            $where = 'eventname = :eventname AND id <= :maxid';
            $params = ['eventname' => $events[$configname]['eventname'], 'maxid' => $maxid];
            if ($duration > 0) {
                $where .= ' AND timecreated >= :cutoff';
                $params['cutoff'] = $now - ($duration * DAYSECS);
            }

            $count = $DB->count_records_select('logstore_standard_log', $where, $params);
            if ($count) {
                $params['configname'] = $configname;
                $columns = self::COLUMNS;
                $DB->execute(
                    "INSERT INTO {logstore_selective_log} ({$columns}, configname)
                     SELECT {$columns}, :configname
                       FROM {logstore_standard_log}
                      WHERE {$where}",
                    $params,
                );
            }
            $total += $count;
            $trace(get_string('rebuild:copied', 'logstore_selective', (object) [
                'count' => $count,
                'event' => $events[$configname]['eventname'],
            ]));
        }

        return $total;
    }
}
