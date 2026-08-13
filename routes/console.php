<?php

use App\Jobs\GenerateDueRecurringTasks;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new GenerateDueRecurringTasks)
    ->daily()
    ->withoutOverlapping()
    ->name('recurrences:generate');
