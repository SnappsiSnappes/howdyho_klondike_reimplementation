<?php

use App\Console\Commands\SendDailyStatsReport;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Console\Commands\CleanupUnusedImages;
use App\Console\Commands\GenerateSitemap;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();


Schedule::command(GenerateSitemap::class)->daily();
Schedule::command(SendDailyStatsReport::class)->dailyAt('07:00');

Schedule::command('queue:work --stop-when-empty --tries=3 --timeout=30')
        ->everyMinute()
        ->withoutOverlapping();