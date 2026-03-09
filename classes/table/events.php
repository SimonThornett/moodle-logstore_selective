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
 * Events table for settings.
 *
 * @package   logstore_selective
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace logstore_selective\table;

use core\event\base;
use core\event\unknown_logged;
use core_component;
use flexible_table;
use html_writer;
use logstore_selective\log\store;
use ReflectionClass;
use stdClass;

class events extends flexible_table {

    /**
     * Constructor.
     *
     * @param $uniqueid
     */
    public function __construct() {
        parent::__construct('logstore_selective-events');

        $this->baseurl = new \moodle_url('/admin/tool/log/store/selective/index.php');

        $columnlist = [
            'fulleventname' => get_string('events:col:fulleventname', 'logstore_selective'),
            'component' => get_string('events:col:component', 'logstore_selective'),
            'edulevel' => get_string('events:col:edulevel', 'logstore_selective'),
            'crud' => get_string('events:col:crud', 'logstore_selective'),
            'objecttable' => get_string('events:col:objecttable', 'logstore_selective'),
            'enable' => get_string('events:col:enable', 'logstore_selective'),
            'duration' => get_string('events:col:duration', 'logstore_selective'),
        ];
        $this->define_columns(array_keys($columnlist));
        $this->define_headers(array_values($columnlist));

        $this->setup();
    }

    /**
     * Print the table.
     */
    public function out(): void {
        foreach ($this->get_all_events_list() as $event) {
            $this->add_data_keyed($this->format_row((object) $event));
        }

        $this->finish_output(false);
    }

    /**
     * Returns all the core events with details.
     *
     * @return array All events.
     */
    private function get_all_events_list(): array {
        global $CFG;

        // Disable developer debugging as deprecated events will fire warnings.
        // Setup backup variables to restore the following settings back to what they were when we are finished.
        $debuglevel          = $CFG->debug;
        $debugdisplay        = $CFG->debugdisplay;
        $debugdeveloper      = $CFG->debugdeveloper;
        $CFG->debug          = 0;
        $CFG->debugdisplay   = false;
        $CFG->debugdeveloper = false;

        // List of exceptional events that will cause problems if displayed.
        $eventsignore = [
            unknown_logged::class,
        ];

        $eventinformation = [];

        $events = core_component::get_component_classes_in_namespace(null, 'event');
        foreach (array_keys($events) as $event) {
            // We need to filter all classes that extend event base, or the base class itself.
            if (is_a($event, base::class, true) && !in_array($event, $eventsignore)) {
                $reflectionclass = new ReflectionClass($event);
                if (!$reflectionclass->isAbstract()) {
                    $eventinformation = self::format_data($eventinformation, "\\{$event}");
                }
            }
        }

        // Now enable developer debugging as event information has been retrieved.
        $CFG->debug          = $debuglevel;
        $CFG->debugdisplay   = $debugdisplay;
        $CFG->debugdeveloper = $debugdeveloper;

        return $eventinformation;
    }

    /**
     * Returns the event data list section with url links and other formatting.
     *
     * @param array $eventdata The event data list section.
     * @param string $eventfullpath Full path to the events for this plugin / subplugin.
     * @return array The event data list section with additional formatting.
     */
    private function format_data(array $eventdata, string $eventfullpath): array {
        // Get general event information.
        $eventdata[$eventfullpath] = $eventfullpath::get_static_info();
        $eventdata[$eventfullpath]['fulleventname'] = html_writer::span($eventfullpath::get_name_with_info());
        $eventdata[$eventfullpath]['fulleventname'] .= html_writer::empty_tag('br');
        $eventdata[$eventfullpath]['fulleventname'] .= html_writer::span(
            $eventdata[$eventfullpath]['eventname'],
            'report-eventlist-name',
        );

        // Human readable plugin information to go with the component.
        $pluginstring = explode('\\', $eventfullpath);
        if ($pluginstring[1] !== 'core') {
            $manager = get_string_manager();
            if ($manager->string_exists('pluginname', $pluginstring[1])) {
                $eventdata[$eventfullpath]['component'] = html_writer::span(get_string('pluginname', $pluginstring[1]));
            }
        }

        return $eventdata;
    }

    /**
     * Returns the appropriate string for the CRUD character.
     *
     * @param stdClass $row The for to format.
     * @return string
     */
    protected function col_crud(stdClass $row): string {
        return match ($row->crud) {
            'c' => get_string('create'),
            'u' => get_string('update'),
            'd' => get_string('delete'),
            default => get_string('view'),
        };
    }

    /**
     * Returns the appropriate string for the event education level.
     *
     * @param stdClass $row The for to format.
     * @return string
     */
    protected function col_edulevel(stdClass $row): string {
        return match ($row->edulevel) {
            base::LEVEL_PARTICIPATING => get_string('edulevelparticipating'),
            base::LEVEL_TEACHING => get_string('edulevelteacher'),
            default => get_string('edulevelother'),
        };
    }

    /**
     * Returns the enable checkbox for the row.
     *
     * @param stdClass $row
     * @return string
     */
    protected function col_enable(stdClass $row): string {
        $name = store::get_processed_eventname($row->eventname);
        $current = (bool)get_config('logstore_selective', $name . '_enabled');
        return html_writer::checkbox(
            'enable[]',
            1,
            $current,
            '',
            [
                'data-currentvalue' => $current,
                'data-eventname' => $name,
            ],
        );
    }

    /**
     * Returns the duration selector for the row.
     *
     * @param stdClass $row
     * @return string
     */
    protected function col_duration(stdClass $row): string {
        $options = [
            2    => get_string('numdays', '', 2),
            5    => get_string('numdays', '', 5),
            10   => get_string('numdays', '', 10),
            35   => get_string('numdays', '', 35),
            60   => get_string('numdays', '', 60),
            90   => get_string('numdays', '', 90),
            120  => get_string('numdays', '', 120),
            150  => get_string('numdays', '', 150),
            180  => get_string('numdays', '', 180),
            365  => get_string('numdays', '', 365),
            1000 => get_string('numdays', '', 1000),
            0    => get_string('neverdeletelogs'),
        ];
        $name = store::get_processed_eventname($row->eventname);
        $current = (int)get_config('logstore_selective', $name . '_duration');
        return html_writer::select(
            $options,
            'duration[]',
            $current,
            null,
            [
                'data-currentvalue' => $current,
                'data-eventname' => $name,
            ]
        );
    }
}
