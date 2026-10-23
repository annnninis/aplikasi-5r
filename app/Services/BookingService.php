<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\Role;
use App\Models\Area;
use App\Models\RoomBooking;
use App\Models\User;
use App\Notifications\BookingNotification;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
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
            $booking = DB::transaction(function () use ($requester, $area, $start, $end, $purpose, $participants) {
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

        if ($booking->approval_status === BookingStatus::MenungguPersetujuan) {
            $this->notify($this->approversFor($area), $booking, 'submitted',
                'Booking ruangan perlu persetujuan',
                "{$requester->name} memesan {$area->name}. Silakan setujui atau tolak.",
                route('bookings.approvals'));
        } else {
            $this->notify(collect([$requester]), $booking, 'confirmed',
                'Booking ruangan terkonfirmasi',
                'Booking Anda sudah terkonfirmasi.',
                route('bookings.mine'));
        }

        return $booking;
    }

    public function approve(RoomBooking $booking, User $approver): RoomBooking
    {
        $this->ensureStatus($booking, [BookingStatus::MenungguPersetujuan]);

        if ($booking->start_time->isPast()) {
            throw ValidationException::withMessages(['status' => 'Waktu booking sudah lewat, tidak bisa disetujui.']);
        }

        $booking->update(['approval_status' => BookingStatus::Disetujui, 'approved_by' => $approver->id]);

        $this->notify(collect([$booking->requester]), $booking, 'approved',
            'Booking ruangan disetujui',
            "Booking Anda disetujui oleh {$approver->name}.",
            route('bookings.mine'));

        return $booking;
    }

    public function reject(RoomBooking $booking, User $approver, string $reason): RoomBooking
    {
        $this->ensureStatus($booking, [BookingStatus::MenungguPersetujuan]);

        $booking->update([
            'approval_status' => BookingStatus::Ditolak,
            'approved_by' => $approver->id,
            'rejection_reason' => $reason,
        ]);

        $this->notify(collect([$booking->requester]), $booking, 'rejected',
            'Booking ruangan ditolak',
            "Alasan: {$reason}",
            route('bookings.mine'));

        return $booking;
    }

    /**
     * Pemesan membatalkan sebelum jam mulai; slot langsung terbuka lagi.
     */
    public function cancel(RoomBooking $booking): RoomBooking
    {
        $this->ensureStatus($booking, BookingStatus::occupying());

        if ($booking->start_time->isPast()) {
            throw ValidationException::withMessages(['status' => 'Booking yang sudah dimulai tidak bisa dibatalkan.']);
        }

        $booking->update(['approval_status' => BookingStatus::Dibatalkan]);

        return $booking;
    }

    /**
     * Job: booking yang masih menunggu saat jam mulainya lewat menjadi kedaluwarsa.
     *
     * @return EloquentCollection<int, RoomBooking>
     */
    public function expireStalePending(): EloquentCollection
    {
        $stale = RoomBooking::with(['area', 'requester'])
            ->where('approval_status', BookingStatus::MenungguPersetujuan)
            ->where('start_time', '<=', now())
            ->get();

        foreach ($stale as $booking) {
            $booking->update(['approval_status' => BookingStatus::Kedaluwarsa]);

            $this->notify(collect([$booking->requester]), $booking, 'expired',
                'Booking ruangan kedaluwarsa',
                'Booking Anda tidak sempat disetujui sebelum jam mulai, jadi dinyatakan kedaluwarsa.',
                route('bookings.mine'));
        }

        return $stale;
    }

    /**
     * Job harian: ingatkan pemesan untuk booking besok yang sudah disetujui.
     *
     * @return EloquentCollection<int, RoomBooking>
     */
    public function remindTomorrow(): EloquentCollection
    {
        $tomorrow = RoomBooking::with(['area', 'requester'])
            ->where('approval_status', BookingStatus::Disetujui)
            ->whereDate('start_time', today()->addDay())
            ->get();

        $tomorrow->each(fn (RoomBooking $booking) => $this->notify(collect([$booking->requester]), $booking, 'reminder',
            'Pengingat: booking ruangan besok',
            'Anda punya booking ruangan besok.',
            route('bookings.mine')));

        return $tomorrow;
    }

    /**
     * Penyetuju: PIC ruangan (jika aktif) dan semua Admin.
     *
     * @return Collection<int, User>
     */
    public function approversFor(Area $area): Collection
    {
        return User::where('is_active', true)
            ->where(fn ($q) => $q->where('role', Role::Admin)->orWhere('id', $area->default_pic_id))
            ->get();
    }

    /**
     * @param  list<BookingStatus>  $allowed
     */
    private function ensureStatus(RoomBooking $booking, array $allowed): void
    {
        if (! in_array($booking->approval_status, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => "Booking berstatus \"{$booking->approval_status->getLabel()}\" tidak bisa diproses dengan aksi ini.",
            ]);
        }
    }

    /**
     * @param  Collection<int, User|null>  $users
     */
    private function notify(Collection $users, RoomBooking $booking, string $event, string $subject, string $message, string $url): void
    {
        $recipients = $users->filter(fn (?User $u) => $u?->is_active)->unique('id')->values();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new BookingNotification($booking, $event, $subject, $message, $url));
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
