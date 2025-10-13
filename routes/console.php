<?php

use App\Http\Controllers\SwapController;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::call(function () {
    Log::info('Running scheduled task 5 seconds.');
    app(SwapController::class)->calculate('ADA');
})->everyFiveSeconds();
