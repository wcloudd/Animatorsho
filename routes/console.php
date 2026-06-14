<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Prune security_events past the configured retention window once a day.
// Requires the system cron entry that runs `php artisan schedule:run`.
Schedule::command('security:prune-events')->daily();
