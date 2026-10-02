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

use core\event\base;
use core\event\unknown_logged;
use core_component;
use logstore_selective\log\store;
use ReflectionClass;

/**
 * Helper for discovering events and reading their selective log configuration.
 *
 * @package   logstore_selective
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class event_list {
    /** @var string The plugin component. */
    public const COMPONENT = 'logstore_selective';

    /** @var string[] Events that cause problems if listed. */
    private const IGNORED_EVENTS = [
        unknown_logged::class,
    ];

    /** @var array|null Static cache of discovered events for the request. */
    private static ?array $cache = null;

    /**
     * Returns information about every concrete event class, sorted by component then name.
     *
     * Each item contains: classname, eventname, configname, name, component, componentname,
     * edulevel, crud, objecttable, enabled and duration.
     *
     * @return array[] Keyed by configname.
     */
    public static function get_events(): array {
        $events = [];
        foreach (self::get_static_event_info() as $configname => $info) {
            $info['enabled'] = (bool) get_config(self::COMPONENT, $configname . '_enabled');
            $info['duration'] = (int) get_config(self::COMPONENT, $configname . '_duration');
            $events[$configname] = $info;
        }
        return $events;
    }

    /**
     * Returns the static (config independent) information for every event.
     *
     * @return array[] Keyed by configname.
     */
    private static function get_static_event_info(): array {
        global $CFG;

        if (self::$cache !== null) {
            return self::$cache;
        }

        // Deprecated events fire debugging warnings when their static info is read.
        $debuglevel = $CFG->debug;
        $debugdisplay = $CFG->debugdisplay;
        $debugdeveloper = $CFG->debugdeveloper;
        $CFG->debug = 0;
        $CFG->debugdisplay = false;
        $CFG->debugdeveloper = false;

        $events = [];
        try {
            $classes = core_component::get_component_classes_in_namespace(null, 'event');
            foreach (array_keys($classes) as $classname) {
                if (!is_a($classname, base::class, true) || in_array($classname, self::IGNORED_EVENTS)) {
                    continue;
                }
                if ((new ReflectionClass($classname))->isAbstract()) {
                    continue;
                }
                $info = self::build_event_info('\\' . $classname);
                $events[$info['configname']] = $info;
            }
        } finally {
            $CFG->debug = $debuglevel;
            $CFG->debugdisplay = $debugdisplay;
            $CFG->debugdeveloper = $debugdeveloper;
        }

        uasort($events, function (array $a, array $b): int {
            return [$a['componentname'], $a['name']] <=> [$b['componentname'], $b['name']];
        });

        self::$cache = $events;
        return $events;
    }

    /**
     * Builds the static information for a single event class.
     *
     * @param string $classname Fully qualified event class name with leading backslash.
     * @return array
     */
    private static function build_event_info(string $classname): array {
        $info = $classname::get_static_info();
        $component = $info['component'];
        $componentname = $component;
        if ($component !== 'core' && get_string_manager()->string_exists('pluginname', $component)) {
            $componentname = get_string('pluginname', $component);
        }

        return [
            'classname' => $classname,
            'eventname' => $info['eventname'],
            'configname' => store::get_processed_eventname($info['eventname']),
            'name' => $classname::get_name_with_info(),
            'component' => $component,
            'componentname' => $componentname,
            'edulevel' => (int) $info['edulevel'],
            'crud' => $info['crud'],
            'objecttable' => $info['objecttable'] ?? '',
        ];
    }

    /**
     * Clears the static event cache.
     */
    public static function reset_cache(): void {
        self::$cache = null;
    }

    /**
     * Whether the given configname belongs to a known event.
     *
     * @param string $configname The processed event name.
     * @return bool
     */
    public static function is_valid_configname(string $configname): bool {
        return array_key_exists($configname, self::get_static_event_info());
    }

    /**
     * Returns the retention options, in days, available for an event.
     *
     * @return string[] Keyed by number of days (0 = never delete).
     */
    public static function get_duration_options(): array {
        $options = [];
        foreach ([2, 5, 10, 35, 60, 90, 120, 150, 180, 365, 1000] as $days) {
            $options[$days] = get_string('numdays', '', $days);
        }
        $options[0] = get_string('neverdeletelogs');
        return $options;
    }

    /**
     * Whether the given duration is one of the allowed options.
     *
     * @param int $duration Duration in days.
     * @return bool
     */
    public static function is_valid_duration(int $duration): bool {
        return array_key_exists($duration, self::get_duration_options());
    }

    /**
     * Returns the enabled events with their retention durations from the plugin config.
     *
     * @return int[] Duration in days (0 = never delete) keyed by configname.
     */
    public static function get_enabled_events(): array {
        $enabled = [];
        foreach ((array) get_config(self::COMPONENT) as $name => $value) {
            if (!$value || !str_ends_with($name, '_enabled')) {
                continue;
            }
            $configname = substr($name, 0, -strlen('_enabled'));
            $enabled[$configname] = (int) get_config(self::COMPONENT, $configname . '_duration');
        }
        return $enabled;
    }
}
