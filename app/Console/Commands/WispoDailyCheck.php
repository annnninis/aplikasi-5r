<?php

namespace App\Console\Commands;

use App\Services\WispoRequestService;
use Illuminate\Console\Command;

class WispoDailyCheck extends Command
{
    protected $signature = 'wispo:daily-check';

    protected $description = 'Tandai peminjaman terlambat (+ eskalasi) dan ingatkan peminjam yang harus mengembalikan besok';

    public function handle(WispoRequestService $requests): int
    {
        $overdue = $requests->markOverdueLoans();
        $reminded = $requests->remindLoansDueTomorrow();

        $this->info("Peminjaman baru terlambat: {$overdue->count()}");
        $this->info("Pengingat kembali besok: {$reminded->count()}");

        return self::SUCCESS;
    }
}
