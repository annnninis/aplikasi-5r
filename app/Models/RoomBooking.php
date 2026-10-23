<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomBooking extends Model
{
    use HasUuids;

    protected $fillable = [
        'area_id',
        'requester_user_id',
        'purpose',
        'participant_count',
        'start_time',
        'end_time',
        'approval_status',
        'approved_by',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'approval_status' => BookingStatus::class,
            'participant_count' => 'integer',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
        ];
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isFinished(): bool
    {
        return $this->approval_status === BookingStatus::Disetujui && $this->end_time->isPast();
    }

    public function isCancellable(): bool
    {
        return in_array($this->approval_status, BookingStatus::occupying(), true) && $this->start_time->isFuture();
    }

    /**
     * Tahapan untuk stepper status (PRD §3.9.3): state = done | active | failed | pending.
     *
     * @return list<array{label: string, state: string}>
     */
    public function steps(): array
    {
        $needsApproval = $this->area->requires_approval || $this->approved_by !== null;
        $labels = $needsApproval
            ? ['Diajukan', 'Menunggu Persetujuan', 'Disetujui', 'Selesai Dipakai']
            : ['Diajukan', 'Terkonfirmasi', 'Selesai Dipakai'];
        $confirmedSteps = $needsApproval ? 3 : 2;

        [$done, $nextState, $nextLabel] = match ($this->approval_status) {
            BookingStatus::MenungguPersetujuan => [1, 'active', null],
            BookingStatus::Disetujui => $this->isFinished() ? [count($labels), null, null] : [$confirmedSteps, 'active', null],
            BookingStatus::Ditolak => [1, 'failed', 'Ditolak'],
            BookingStatus::Kedaluwarsa => [1, 'failed', 'Kedaluwarsa'],
            BookingStatus::Dibatalkan => ! $needsApproval || $this->approved_by !== null
                ? [$confirmedSteps, 'failed', 'Dibatalkan']
                : [1, 'failed', 'Dibatalkan'],
        };

        return collect($labels)->map(fn (string $label, int $i) => match (true) {
            $i < $done => ['label' => $label, 'state' => 'done'],
            $i === $done && $nextState !== null => ['label' => $nextLabel ?? $label, 'state' => $nextState],
            default => ['label' => $label, 'state' => 'pending'],
        })->all();
    }
}
