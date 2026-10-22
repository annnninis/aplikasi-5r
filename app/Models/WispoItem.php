<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WispoItem extends Model
{
    use HasUuids;

    /**
     * current_stock_qty sengaja tidak fillable: stok hanya diubah lewat StockService.
     */
    protected $fillable = [
        'item_code',
        'name',
        'category',
        'unit',
        'photo_path',
        'safety_stock_qty',
        'storage_location',
        'brand_model',
        'serial_number',
        'notes',
        'is_active',
        'added_by',
    ];

    protected function casts(): array
    {
        return [
            'current_stock_qty' => 'integer',
            'safety_stock_qty' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WispoTransaction::class, 'item_id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(WispoRequest::class, 'item_id');
    }

    public function isLowStock(): bool
    {
        return $this->current_stock_qty <= $this->safety_stock_qty;
    }
}
