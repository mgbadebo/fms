<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule the missing daily logs check
// Run every 15 minutes between 18:00 and 20:00 (after cutoff time)
Schedule::command('production-cycles:check-missing-daily-logs')
    ->everyFifteenMinutes()
    ->between('18:00', '20:00')
    ->timezone(config('app.timezone'));
