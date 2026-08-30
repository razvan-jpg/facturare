<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\ScheduleRunController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class)->name('health');
Route::get('/cron/run', ScheduleRunController::class)->name('cron.run');
