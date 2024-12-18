<?php

use App\Jobs\ProcessRecap;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

// Schedule a job to run every minute
// app(Schedule::class)->job(new ProcessRecap(1, 2024, 1))->everyMinute(1);

// app(Schedule::class)->job(new ProcessRecap(1, 2024, 1))->everySecond(5);
app(Schedule::class)->job(new ProcessRecap)->everyMinute();
