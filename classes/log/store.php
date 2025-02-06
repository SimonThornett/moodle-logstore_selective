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
 * Standard log reader/writer.
 *
 * @package   logstore_selective
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2025
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace logstore_selective\log;

use core\dml\recordset_walk;
use core\event\base;
use core\log\sql_internal_table_reader;
use stdClass;
use tool_log\helper\buffered_writer;
use tool_log\helper\reader;
use tool_log\log\manager;
use tool_log\log\writer;

defined('MOODLE_INTERNAL') || die();

class store implements writer, sql_internal_table_reader {

    use \tool_log\helper\store,
        buffered_writer,
        reader;

    /** @var bool $logguests true if logging guest access */
    protected bool $logguests;

    public function __construct(manager $manager) {
        $this->helper_setup($manager);
        $this->logguests = (bool)$this->get_config('logguests', true);
        $this->jsonformat = (bool)$this->get_config('jsonformat', false);
    }

    /**
     * Should the event be ignored (== not logged)?
     *
     * @param base $event
     * @return bool
     */
    protected function is_event_ignored(base $event): bool {

        // Check the config to see if we ignore it. If so early return, otherwise do login check.
        $config = $this->get_config(self::get_processed_eventname($event->eventname));
        if (is_null($config)) {
            return true;
        }
        if ((!CLI_SCRIPT or PHPUNIT_TEST) and !$this->logguests) {
            // Always log inside CLI scripts because we do not login there.
            if (!isloggedin() or isguestuser()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Finally store the events into the database.
     *
     * @param array $evententries raw event data
     */
    protected function insert_event_entries($evententries): void {
        global $DB;

        // Add the configname for targetting in the scheduled task.
        foreach ($evententries as &$evententry) {
            $evententry['configname'] = self::get_processed_eventname($evententry['eventname']);
        }

        $DB->insert_records('logstore_selective_log', $evententries);
    }

    public function get_events_select($selectwhere, array $params, $sort, $limitfrom, $limitnum): array {
        global $DB;

        $sort = self::tweak_sort_by_id($sort);

        $events = array();
        $records = $DB->get_recordset_select('logstore_selective_log', $selectwhere, $params, $sort, '*', $limitfrom, $limitnum);

        foreach ($records as $data) {
            if ($event = $this->get_log_event($data)) {
                $events[$data->id] = $event;
            }
        }

        $records->close();

        return $events;
    }

    /**
     * Fetch records using given criteria returning a Traversable object.
     *
     * Note that the traversable object contains a moodle_recordset, so
     * remember that is important that you call close() once you finish
     * using it.
     *
     * @param string $selectwhere
     * @param array $params
     * @param string $sort
     * @param int $limitfrom
     * @param int $limitnum
     * @return recordset_walk
     */
    public function get_events_select_iterator($selectwhere, array $params, $sort, $limitfrom, $limitnum): recordset_walk {
        global $DB;

        $sort = self::tweak_sort_by_id($sort);

        $recordset = $DB->get_recordset_select('logstore_selective_log', $selectwhere, $params, $sort, '*', $limitfrom, $limitnum);

        return new recordset_walk($recordset, array($this, 'get_log_event'));
    }

    /**
     * Returns an event from the log data.
     *
     * @param stdClass $data Log data
     * @return base|null
     */
    public function get_log_event($data): ?base {

        $extra = ['origin' => $data->origin, 'ip' => $data->ip, 'realuserid' => $data->realuserid];
        $data = (array)$data;
        $id = $data['id'];
        $data['other'] = self::decode_other($data['other']);
        if ($data['other'] === false) {
            $data['other'] = [];
        }
        unset($data['origin']);
        unset($data['ip']);
        unset($data['realuserid']);
        unset($data['id']);

        if (!$event = base::restore($data, $extra)) {
            return null;
        }

        return $event;
    }

    /**
     * Get number of events present for the given select clause.
     *
     * @param string $selectwhere select conditions.
     * @param array $params params.
     *
     * @return int Number of events available for the given conditions
     */
    public function get_events_select_count($selectwhere, array $params): int {
        global $DB;
        return $DB->count_records_select('logstore_selective_log', $selectwhere, $params);
    }

    /**
     * Get whether events are present for the given select clause.
     *
     * @param string $selectwhere select conditions.
     * @param array $params params.
     *
     * @return bool Whether events available for the given conditions
     */
    public function get_events_select_exists(string $selectwhere, array $params): bool {
        global $DB;
        return $DB->record_exists_select('logstore_selective_log', $selectwhere, $params);
    }

    public function get_internal_log_table_name(): string {
        return 'logstore_selective_log';
    }

    /**
     * Are the new events appearing in the reader?
     *
     * @return bool true means new log events are being added, false means no new data will be added
     */
    public function is_logging(): bool {
        return true;
    }

    /**
     * Get the processed eventname to use in the config settings.
     * We remove the leading \ and replace all others with underscores.
     *
     * @param $eventname
     * @return string
     */
    public static function get_processed_eventname($eventname): string {
        return substr(
            str_replace('\\', '_', $eventname),
            1,
            strlen($eventname)
        );
    }

    /**
     * Api to get plugin config
     *
     * @param string $name name of the config.
     * @param null|mixed $default default value to return.
     *
     * @return mixed|null return config value.
     */
    protected function get_config($name, $default = null): mixed {
        $enabled = get_config($this->component, $name . '_enabled');
        $value = get_config($this->component, $name);
        if ($value !== false && $enabled) {
            return $value;
        }
        return $default;
    }

}
