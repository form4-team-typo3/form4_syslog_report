# Administrator Manual

First: Install the extension.

## Configuration Scheduler Task

Create a new scheduler task of the type "Execute console commands (scheduler)" and choose the "CommandController Command" "Form4SyslogReport Report: report".
You can have multiple tasks with different configuration.

![Add scheduler task 'Extbase CommandController Task (extbase)](/Images/form4SyslogReportSchedulerTask.png)

Add scheduler task "Execute console commands (scheduler)"

## Configuration Cron Task

Create a crontask on your server.

```
typo3/cli_dispatch.phpsh extbase report:report --period=<period> --receiver=<email addresses> [--errors=1] [--clearcachepages=1] [--clearcacheall=1] [--clearcachesystem=1] [--clearcachepage=<comma separated list of uids>]
Configuration Options
```

|  Parameter | Value / Examples  | Description  |
|---|---|---|
| period  | e.g. "-7 days midnight"  | Period of the data of the record with DateTime::modify format for the report  |
| receiver  | email@example.com  |  E-Mail addresses of the receivers (comma separated). |
| subject  | SysLog report from %s to %s  |  Subject of the email. (e.g. "SysLog report from %s to %s" markers will be substituted with "startdate" and "enddate" of the report.). The sitename will be prepended. |
|errors   | 1  | Adds the errors in the report. Disabled by default and optional.  |
| errorFilterRegex  |  	/exception/i | For filtering errors by their text.  |
| clearcachepages  |  1 | Adds the entries for "Flush frontend caches". Disabled by default and optional.  |
|  clearcacheall | 1  |  Adds the entries for "Flush general caches". Disabled by default and optional. |
| clearcachesystem  | 1  | Adds the entries for "Flush system caches". Disabled by default and optional.  |
|  clearcachepage | e.g. "12,17"  |  Adds the entries for the deletion of the cache of the specified pages. |

(Documentation DateTime::modify: http://php.net/manual/en/datetime.modify.php)