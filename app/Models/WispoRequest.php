<?php

namespace App\Models;

use App\Enums\UsageCategory;
use App\Enums\WispoRequestStatus;
use App\Enums\WispoRequestType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WispoRequest extends Model
{
    use HasUuids;

    protected $fillable = [
        'item_id',
        'request_type',
        'usage_category',
        'qty',
        'requester_user_id',
        'purpose',
        'expected_return_at',
        'actual_return_at',
        'status',
        'approved_by',
        'decided_at',
        'rejection_reason',
        'return_notes',
    ];

    protected function casts(): array
    {
        return [
            'request_type' => WispoRequestType::class,
            'usage_category' => UsageCategory::class,
            'status' => WispoRequestStatus::class,
            'qty' => 'integer',
            'expected_return_at' => 'date',
            'actual_return_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(WispoItem::class, 'item_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WispoTransaction::class, 'request_id');
    }
}
