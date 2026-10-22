<?php

namespace App\Console\Commands;

use App\Services\FindingService;
use Illuminate\Console\Command;

class FindingsDailyCheck extends Command
{
    protected $signature = 'findings:daily-check';

    protected $description = 'Tandai temuan terlambat (+ eskalasi) dan ingatkan PIC yang deadline-nya besok';

    public function handle(FindingService $findings): int
    {
        $overdue = $findings->markOverdue();
        $reminded = $findings->remindDueTomorrow();

        $this->info("Temuan baru terlambat: {$overdue->count()}");
        $this->info("Pengingat deadline besok: {$reminded->count()}");

        return self::SUCCESS;
    }
}
