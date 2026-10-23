<?php

namespace App\Models;

use Database\Factories\WispoItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class WispoItem extends Model
{
    /** @use HasFactory<WispoItemFactory> */
    use HasFactory, HasUuids;

    /**
     * item_code dan current_stock_qty sengaja tidak fillable:
     * kode dibuat otomatis, stok hanya diubah lewat StockService.
     */
    protected $fillable = [
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

    protected static function booted(): void
    {
        static::creating(function (self $item) {
            $next = DB::selectOne("select nextval('wispo_item_code_seq') as n")->n;
            $item->item_code = 'SPO-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $item->current_stock_qty = 0;
        });
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

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeLowStock(Builder $query): void
    {
        $query->whereColumn('current_stock_qty', '<=', 'safety_stock_qty');
    }

    /**
     * Kategori yang sudah pernah dipakai, untuk saran isian.
     *
     * @return list<string>
     */
    public static function knownCategories(): array
    {
        return static::query()->distinct()->orderBy('category')->pluck('category')->all();
    }
}
