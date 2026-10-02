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

use core\task\adhoc_task;
use logstore_selective\local\rebuild;

/**
 * Adhoc task that clears and refills the selective log from the standard log.
 *
 * @package   logstore_selective
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rebuild_log_task extends adhoc_task {
    /**
     * Get a descriptive name for this task.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('taskrebuild', 'logstore_selective');
    }

    /**
     * Run the rebuild.
     */
    public function execute(): void {
        $total = rebuild::execute(function (string $message): void {
            mtrace('  ' . $message);
        });
        mtrace(get_string('rebuild:complete', 'logstore_selective', $total));
    }
}
