# Selective log store (logstore_selective)

[![Moodle Plugin CI](https://github.com/SimonThornett/moodle-logstore_selective/actions/workflows/ci.yml/badge.svg)](https://github.com/SimonThornett/moodle-logstore_selective/actions/workflows/ci.yml)

A Moodle log store that records **only the events you choose**, with a **separate retention period for each event**.

## Why use it?

The standard log store (`logstore_standard`) records every event. On a busy site the table
gets very large, which makes it slow to query and hard to keep tidy. Usually only a few events
matter for reporting, compliance or troubleshooting.

The selective log store writes the events you enable into its own table, `logstore_selective_log`.
It can run alongside the standard log store, or replace it. Because it is a normal log store reader,
core reports such as **Logs**, **Live logs** and **Activity reports** can read from it.

## Features

- Enable or disable logging for each event. All events are disabled by default.
- A log duration (retention period) for each event, from 2 days up to "Never delete logs".
- An event configuration page with search, filters and bulk actions.
- A scheduled task that removes records older than each event's log duration.
- A one-off rebuild that refills the selective log from the standard log.
- Optional JSON encoding of the `other` field, and a configurable write buffer.
- Support for the Privacy API and for course backup and restore.

## Requirements

- Moodle 4.4 or later. CI tests Moodle 4.5, 5.0, 5.1 and 5.2 on PostgreSQL and MariaDB.
- Cron must be running. The cleanup task and the rebuild task both run from cron.

## Installation

1. Copy the plugin to `admin/tool/log/store/selective`.
2. Go to *Site administration > Notifications* and complete the upgrade.
3. Go to *Site administration > Plugins > Logging > Manage log stores* and enable **Selective log**.

## Configuration

### General settings

Go to *Site administration > Plugins > Logging > Selective log*.

| Setting | Description |
|---|---|
| JSON format | Stores the `other` field as JSON instead of PHP serialised data. |
| Write buffer size | The number of events to buffer before they are written to the database. |
| Rebuild selective log | Queues a rebuild of the selective log from the standard log. See [Rebuilding the selective log](#rebuilding-the-selective-log). |

### Choosing which events to log

On the settings page, follow the **Events Configuration** link. You can also go directly to
`/admin/tool/log/store/selective/index.php`.

The page lists every event on the site, including events added later by upgrades or new plugins.
New events are not enabled by default.

1. **Find events** with the filter bar:
   - **Search events** matches the event name, the event class (for example `\core\event\user_loggedin`), the component and the affected table.
   - **Component**, **Education level** and **Database query type** narrow the list down.
   - **Status** shows only enabled or only disabled events.
   - **Clear filters** shows all events again.
2. **Change settings** for each event with the **Enable** switch and the **Log duration** menu.
3. **Bulk actions** apply to every event currently shown. Filter the list first, then choose
   **Enable**, **Disable** or **Set log duration**.
4. Changed settings are highlighted, and the footer shows how many changes are unsaved.
   Select **Save changes** to save them, or **Discard changes** to undo them.
   If you try to leave the page with unsaved changes, the browser warns you first.

Only site administrators (`moodle/site:config`) can view the page and save changes.

### Log retention

The **Log table cleanup** scheduled task (`\logstore_selective\task\cleanup_task`) runs daily.
It deletes records older than each event's log duration. Events set to "Never delete logs" are
never removed. Each run stops after about 10 minutes, so a large backlog is cleared over several runs.

## Rebuilding the selective log

When you enable new events, the selective log only contains entries from that point on. If the
standard log store has been recording those events, you can backfill them:

1. Enable the events you want, and set their log durations, on the event configuration page.
2. Go to *Site administration > Plugins > Logging > Selective log*.
3. Tick **Rebuild selective log** and select **Save changes**.

This queues an ad hoc task (`\logstore_selective\task\rebuild_log_task`). The checkbox then resets
itself. If a rebuild is already queued, a second one is not added. When cron runs the task, it:

1. Deletes all existing records from `logstore_selective_log`. Records logged after the rebuild started are kept.
2. Copies records from `logstore_standard_log` for the **enabled events only**, limited to each event's
   **log duration**. Events set to "Never delete logs" have their full history copied.

> **Warning:** the rebuild permanently removes any selective log records that are no longer in the
> standard log, for example because the standard log has already been cleaned up. On large sites the
> rebuild can take a long time. Consider running it out of hours.

To run the rebuild straight away from the command line:

```bash
php admin/cli/adhoc_task.php --execute --classname='\logstore_selective\task\rebuild_log_task'
```

## Testing

```bash
# PHPUnit
php admin/tool/phpunit/cli/init.php
vendor/bin/phpunit --testsuite logstore_selective_testsuite

# Behat
php admin/tool/behat/cli/init.php
vendor/bin/behat --config <behat_dataroot>/behatrun/behat/behat.yml --tags=@logstore_selective
```

## Contributing and support

Issues and pull requests on GitHub are welcome and encouraged.
