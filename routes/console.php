<?php

use Illuminate\Support\Facades\Schedule;

// Job harian (PRD MVP §4.5). Di server, jalankan `php artisan schedule:run` tiap menit lewat cron.
Schedule::command('findings:daily-check')->dailyAt('07:00')->withoutOverlapping();
Schedule::command('wispo:daily-check')->dailyAt('07:00')->withoutOverlapping();
