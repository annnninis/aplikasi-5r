<?php

namespace App\Console\Commands;

use App\Services\BookingService;
use Illuminate\Console\Command;

class BookingsCheck extends Command
{
    protected $signature = 'bookings:check {--remind : Kirim juga pengingat booking besok}';

    protected $description = 'Kedaluwarsakan booking yang tidak sempat disetujui; opsional: ingatkan booking besok';

    public function handle(BookingService $bookings): int
    {
        $this->info('Booking kedaluwarsa: '.$bookings->expireStalePending()->count());

        if ($this->option('remind')) {
            $this->info('Pengingat booking besok: '.$bookings->remindTomorrow()->count());
        }

        return self::SUCCESS;
    }
}
