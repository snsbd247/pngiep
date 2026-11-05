<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduler (shared hosting friendly)
|--------------------------------------------------------------------------
| cPanel has no supervisor, so a single cron entry runs the scheduler every minute:
|   * * * * * /usr/local/bin/php /home/USER/app/artisan schedule:run >> /dev/null 2>&1
| The queue is drained by a short-lived worker each minute instead of a daemon.
*/

Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=50')
    ->everyMinute()
    ->withoutOverlapping(5);

Schedule::command('queue:prune-failed --hours=168')->daily();
Schedule::command('auth:clear-resets')->everyFifteenMinutes();
Schedule::command('sanctum:prune-expired --hours=24')->daily();
Schedule::command('model:prune')->daily();

// Backups (storage/app/backups, outside the web root): database daily, files weekly.
Schedule::command('app:backup --keep=14')->dailyAt('02:30')->withoutOverlapping();
Schedule::command('app:backup --files --keep=4')->weeklyOn(5, '03:00')->withoutOverlapping();
