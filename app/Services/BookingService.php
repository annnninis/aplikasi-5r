<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Area;
use App\Models\RoomBooking;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Satu-satunya tempat transisi status booking ruangan (PRD MVP §4.4).
 * Anti-bentrok dua lapis: pengecekan di sini + exclusion constraint PostgreSQL.
 */
class BookingService
{
    public const OPEN_HOUR = 6;

    public const CLOSE_HOUR = 22;

    public function book(User $requester, Area $area, Carbon $start, Carbon $end, string $purpose, int $participants): RoomBooking
    {
        $this->validateSlot($area, $start, $end, $participants);

        try {
            return DB::transaction(function () use ($requester, $area, $start, $end, $purpose, $participants) {
                // Kunci baris ruangan supaya dua pemesan bersamaan diproses bergantian.
                Area::whereKey($area->getKey())->lockForUpdate()->first();

                if ($clash = $this->findClash($area, $start, $end)) {
                    throw $this->clashException($clash);
                }

                return RoomBooking::create([
                    'area_id' => $area->id,
                    'requester_user_id' => $requester->id,
                    'purpose' => $purpose,
                    'participant_count' => $participants,
                    'start_time' => $start,
                    'end_time' => $end,
                    'approval_status' => $area->requires_approval ? BookingStatus::MenungguPersetujuan : BookingStatus::Disetujui,
                ]);
            });
        } catch (QueryException $e) {
            // 23P01 = exclusion_violation: pengaman terakhir jika dua request lolos bersamaan.
            if ($e->getCode() === '23P01') {
                throw ValidationException::withMessages(['start_time' => 'Slot ini baru saja dipesan orang lain. Silakan pilih jam lain.']);
            }

            throw $e;
        }
    }

    /**
     * Booking aktif yang tumpang tindih dengan rentang waktu (batas bersentuhan tidak dihitung bentrok).
     */
    public function findClash(Area $area, Carbon $start, Carbon $end, ?RoomBooking $ignore = null): ?RoomBooking
    {
        return RoomBooking::with('requester')
            ->where('area_id', $area->id)
            ->whereIn('approval_status', BookingStatus::occupying())
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->when($ignore, fn ($q) => $q->whereKeyNot($ignore->getKey()))
            ->orderBy('start_time')
            ->first();
    }

    private function validateSlot(Area $area, Carbon $start, Carbon $end, int $participants): void
    {
        $errors = [];

        if (! $area->is_active || ! $area->isBookable()) {
            $errors['area_id'] = 'Ruangan ini tidak bisa dipesan.';
        }

        if ($end->lte($start)) {
            $errors['end_time'] = 'Jam selesai harus setelah jam mulai.';
        } elseif (! $start->isSameDay($end)) {
            $errors['end_time'] = 'Booking harus selesai di hari yang sama.';
        } elseif ($start->hour < self::OPEN_HOUR || $end->gt($start->copy()->setTime(self::CLOSE_HOUR, 0))) {
            $errors['start_time'] = sprintf('Ruangan bisa dipesan antara %02d.00 dan %02d.00.', self::OPEN_HOUR, self::CLOSE_HOUR);
        }

        if ($start->lt(now()->subMinutes(5))) {
            $errors['start_time'] = 'Waktu mulai sudah lewat.';
        }

        if ($participants < 1) {
            $errors['participant_count'] = 'Jumlah peserta minimal 1.';
        } elseif ($area->capacity !== null && $participants > $area->capacity) {
            $errors['participant_count'] = "Kapasitas {$area->name} hanya {$area->capacity} orang.";
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function clashException(RoomBooking $clash): ValidationException
    {
        $status = $clash->approval_status === BookingStatus::MenungguPersetujuan ? ' (menunggu persetujuan)' : '';

        return ValidationException::withMessages([
            'start_time' => sprintf(
                'Bentrok dengan booking %s, %s–%s%s. Silakan pilih jam lain.',
                $clash->requester->name,
                $clash->start_time->format('H:i'),
                $clash->end_time->format('H:i'),
                $status,
            ),
        ]);
    }
}
