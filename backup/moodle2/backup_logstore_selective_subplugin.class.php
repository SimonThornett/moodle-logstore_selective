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
 * Backup implementation for the (tool_log) logstore_selective subplugin.
 *
 * @package   logstore_selective
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2025
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class backup_logstore_selective_subplugin extends backup_tool_log_logstore_subplugin {

    /**
     * Returns the subplugin structure to attach to the 'logstore' XML element.
     *
     * @return backup_subplugin_element the subplugin structure to be attached.
     */
    protected function define_logstore_subplugin_structure() {

        $subplugin = $this->get_subplugin_element();
        $subpluginwrapper = new backup_nested_element($this->get_recommended_name());

        // Create the custom (base64 encoded, xml safe) 'other' final element.
        $otherelement = new base64_encode_final_element('other');

        $subpluginlog = new backup_nested_element(
            'logstore_selective_log',
            ['id'],
            [
                'eventname', 'component', 'action', 'target', 'objecttable',
                'objectid', 'crud', 'edulevel', 'contextid', 'userid', 'relateduserid',
                'anonymous', $otherelement, 'timecreated', 'ip', 'realuserid'
            ]
        );

        $subplugin->add_child($subpluginwrapper);
        $subpluginwrapper->add_child($subpluginlog);

        $subpluginlog->set_source_table(
            'logstore_selective_log',
            ['contextid' => backup::VAR_CONTEXTID]
        );

        return $subplugin;
    }
}
