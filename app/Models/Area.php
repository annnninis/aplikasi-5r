<?php

namespace App\Models;

use App\Enums\ZoneCategory;
use Database\Factories\AreaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    /** @use HasFactory<AreaFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'zone_category',
        'focus_5r',
        'default_pic_id',
        'capacity',
        'facilities',
        'requires_approval',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'zone_category' => ZoneCategory::class,
            'capacity' => 'integer',
            'requires_approval' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function defaultPic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'default_pic_id');
    }

    public function findings(): HasMany
    {
        return $this->hasMany(Finding::class);
    }

    public function roomBookings(): HasMany
    {
        return $this->hasMany(RoomBooking::class);
    }

    public function isBookable(): bool
    {
        return $this->zone_category === ZoneCategory::Kolaborasi;
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
    public function scopeBookable(Builder $query): void
    {
        $query->where('zone_category', ZoneCategory::Kolaborasi);
    }
}
