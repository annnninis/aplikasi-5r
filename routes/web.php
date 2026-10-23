<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::redirect('/', '/dashboard')->name('home');

// Satu pintu login: halaman login bawaan Filament diarahkan ke /login.
Route::redirect('admin/login', '/login');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Volt::route('lapor', 'findings.quick-report')->name('findings.report');
    Volt::route('temuan-saya', 'findings.mine')->name('findings.mine');
    Volt::route('temuan/{finding}', 'findings.show')->name('findings.show');
    Volt::route('stok', 'wispo.stock')->name('wispo.stock');
    Volt::route('stok/{item}/ajukan', 'wispo.request-form')->name('wispo.requests.create');
    Volt::route('permintaan-saya', 'wispo.my-requests')->name('wispo.requests.mine');
    Volt::route('booking', 'bookings.index')->name('bookings.index');
    Volt::route('booking-saya', 'bookings.mine')->name('bookings.mine');
    Volt::route('booking/persetujuan', 'bookings.approvals')->name('bookings.approvals');

    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

require __DIR__.'/auth.php';
