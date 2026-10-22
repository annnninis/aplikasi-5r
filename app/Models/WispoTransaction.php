<?php

namespace App\Models;

use App\Enums\WispoTransactionType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Riwayat stok: tidak pernah diubah atau dihapus setelah dibuat.
 */
class WispoTransaction extends Model
{
    use HasUuids;

    protected $fillable = [
        'item_id',
        'transaction_type',
        'qty',
        'stock_after',
        'transaction_date',
        'source_or_destination',
        'request_id',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'transaction_type' => WispoTransactionType::class,
            'qty' => 'integer',
            'stock_after' => 'integer',
            'transaction_date' => 'date',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(WispoItem::class, 'item_id');
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(WispoRequest::class, 'request_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
