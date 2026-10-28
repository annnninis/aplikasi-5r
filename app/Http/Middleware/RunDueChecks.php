<?php

namespace App\Http\Middleware;

use App\Services\BookingService;
use App\Services\FindingService;
use App\Services\WispoRequestService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Status yang bergantung waktu (temuan/peminjaman terlambat, booking kedaluwarsa) diperbarui
 * saat ada yang membuka website, paling sering sekali per INTERVAL_MINUTES — jadi tetap akurat
 * walau cron/scheduler belum jalan (laptop, atau hosting tanpa scheduler).
 *
 * Aman dijalankan berulang: setiap pemeriksaan hanya mengubah data yang belum berubah,
 * jadi notifikasi eskalasi tidak terkirim dua kali. Pengingat H-1 tetap lewat scheduler.
 */
class RunDueChecks
{
    public const INTERVAL_MINUTES = 5;

    public const CACHE_KEY = 'due-checks:last-run';

    public function handle(Request $request, Closure $next): Response
    {
        // Dijalankan sebelum halaman dibuat supaya angka & status yang tampil sudah benar.
        if (Cache::add(self::CACHE_KEY, now()->toIso8601String(), now()->addMinutes(self::INTERVAL_MINUTES))) {
            try {
                app(FindingService::class)->markOverdue();
                app(WispoRequestService::class)->markOverdueLoans();
                app(BookingService::class)->expireStalePending();
            } catch (Throwable $e) {
                // Jangan sampai pemeriksaan mengganggu pengguna; scheduler tetap jadi cadangan.
                Log::warning('RunDueChecks gagal: '.$e->getMessage());
            }
        }

        return $next($request);
    }
}
