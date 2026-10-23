<?php

use Illuminate\Support\Facades\Schedule;

// Job terjadwal (PRD MVP §4.5). Di server, jalankan `php artisan schedule:run` tiap menit lewat cron.
Schedule::command('findings:daily-check')->dailyAt('07:00')->withoutOverlapping();
Schedule::command('wispo:daily-check')->dailyAt('07:00')->withoutOverlapping();
Schedule::command('bookings:check --remind')->dailyAt('07:00')->withoutOverlapping();
// Booking yang belum disetujui saat jam mulai lewat langsung kedaluwarsa (slotnya terbuka lagi).
Schedule::command('bookings:check')->everyFifteenMinutes()->withoutOverlapping();
