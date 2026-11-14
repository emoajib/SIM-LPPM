<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule notification commands
Schedule::command('reviews:send-reminders')
    ->dailyAt('08:00')
    ->onOneServer()
    ->withoutOverlapping()
    ->description('Send review reminders to reviewers 3 days before deadline');

Schedule::command('reviews:check-overdue')
    ->hourly()
    ->onOneServer()
    ->withoutOverlapping()
    ->description('Check and send notifications for overdue reviews');

Schedule::command('reports:send-daily-summary')
    ->dailyAt('08:00')
    ->onOneServer()
    ->withoutOverlapping()
    ->description('Send daily summary reports to role-specific users');

Schedule::command('reports:send-weekly-summary')
    ->weeklyOn(1, '08:00')  // Monday at 08:00
    ->onOneServer()
    ->withoutOverlapping()
    ->description('Send weekly summary reports to role-specific users');

// Prune Telescope entries older than 48 hours to prevent DB bloat
Schedule::command('telescope:prune --hours=48')
    ->daily()
    ->onOneServer()
    ->withoutOverlapping()
    ->description('Prune Telescope entries older than 48 hours');

// Retensi tabel observability (W4-H2.2/H2.3): cegah tabel tumbuh tanpa batas
Schedule::command('observability:prune')
    ->dailyAt('03:30')
    ->onOneServer()
    ->withoutOverlapping()
    ->description('Prune old dev_events and resolved error_signatures');
