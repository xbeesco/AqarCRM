<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule daily task to update expired contracts
Schedule::command('contracts:update-expired')
    ->daily()
    ->at('00:01')
    ->runInBackground()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/expired-contracts.log'));
