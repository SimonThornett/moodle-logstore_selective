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

namespace logstore_selective\output;

use core\event\base;
use core_collator;
use core_text;
use logstore_selective\local\event_list;
use renderable;
use renderer_base;
use templatable;

/**
 * Renderable for the event storage configuration page.
 *
 * @package   logstore_selective
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class events_page implements renderable, templatable {
    /**
     * Export the data for the template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $durationoptions = event_list::get_duration_options();
        $edulevels = self::get_edulevel_options();
        $cruds = self::get_crud_options();

        $rows = [];
        $components = [];
        $enabledcount = 0;
        foreach (event_list::get_events() as $event) {
            $components[$event['component']] = $event['componentname'];
            $enabledcount += (int) $event['enabled'];

            $durations = [];
            foreach ($durationoptions as $value => $label) {
                $durations[] = [
                    'value' => $value,
                    'label' => $label,
                    'selected' => $value === $event['duration'],
                ];
            }

            $rows[] = [
                'configname' => $event['configname'],
                'eventname' => $event['eventname'],
                'name' => $event['name'],
                'component' => $event['component'],
                'componentname' => $event['componentname'],
                'edulevel' => $event['edulevel'],
                'edulevelname' => $edulevels[$event['edulevel']] ?? $edulevels[base::LEVEL_OTHER],
                'crud' => $event['crud'],
                'crudname' => $cruds[$event['crud']] ?? $cruds['r'],
                'objecttable' => $event['objecttable'],
                'enabled' => $event['enabled'],
                'duration' => $event['duration'],
                'durations' => $durations,
                'searchtext' => core_text::strtolower(implode(' ', [
                    $event['name'],
                    $event['eventname'],
                    $event['componentname'],
                    $event['objecttable'],
                ])),
            ];
        }

        core_collator::asort($components);

        return [
            'events' => $rows,
            'total' => count($rows),
            'enabledcount' => $enabledcount,
            'components' => self::to_options($components),
            'edulevels' => self::to_options($edulevels),
            'cruds' => self::to_options($cruds),
            'durations' => self::to_options($durationoptions),
        ];
    }

    /**
     * Education level options.
     *
     * @return string[]
     */
    public static function get_edulevel_options(): array {
        return [
            base::LEVEL_TEACHING => get_string('edulevelteacher'),
            base::LEVEL_PARTICIPATING => get_string('edulevelparticipating'),
            base::LEVEL_OTHER => get_string('edulevelother'),
        ];
    }

    /**
     * CRUD options.
     *
     * @return string[]
     */
    public static function get_crud_options(): array {
        return [
            'c' => get_string('create'),
            'r' => get_string('view'),
            'u' => get_string('update'),
            'd' => get_string('delete'),
        ];
    }

    /**
     * Converts an associative array into a list of value/label pairs for mustache.
     *
     * @param array $options
     * @return array[]
     */
    private static function to_options(array $options): array {
        $result = [];
        foreach ($options as $value => $label) {
            $result[] = ['value' => $value, 'label' => $label];
        }
        return $result;
    }
}
