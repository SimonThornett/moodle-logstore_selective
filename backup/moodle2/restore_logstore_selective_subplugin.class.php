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
 * Restore implementation for the (tool_log) logstore_selective subplugin.
 *
 * @package   logstore_selective
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2025
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class restore_logstore_selective_subplugin extends restore_tool_log_logstore_subplugin {

    /**
     * Returns the subplugin structure to attach to the 'logstore' XML element.
     *
     * @return restore_path_element[] array of elements to be processed on restore.
     */
    protected function define_logstore_subplugin_structure() {

        // If the logstore is not enabled we don't add structures for it.
        $enabledlogstores = explode(',', get_config('tool_log', 'enabled_stores'));
        if (!in_array('logstore_selective', $enabledlogstores)) {
            return []; // The logstore is not enabled, nothing to restore.
        }

        $paths = [];

        $elename = $this->get_namefor('log');
        $elepath = $this->get_pathfor('/logstore_selective_log');
        $paths[] = new restore_path_element($elename, $elepath);

        return $paths;
    }

    /**
     * Process logstore_selective_log entries.
     *
     * This method proceeds to read, complete, remap and, finally,
     * discard or save every log entry.
     *
     * @param [] $data log entry.
     */
    public function process_logstore_selective_log($data) {
        global $DB;

        $data = $this->process_log($data, get_config('logstore_selective', 'jsonformat'));

        if ($data) {
            $DB->insert_record('logstore_selective_log', $data);
        }
    }
}
