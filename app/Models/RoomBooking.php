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
}
