<?php

namespace App\Models;

use App\Enums\FindingPriority;
use App\Enums\FindingStatus;
use Database\Factories\FindingFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Finding extends Model
{
    /** @use HasFactory<FindingFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'source',
        'area_id',
        'reported_by',
        'pic_id',
        'description',
        'priority',
        'deadline',
        'status',
        'resolution_note',
        'revision_note',
        'rejection_reason',
        'verified_by',
        'assigned_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'priority' => FindingPriority::class,
            'status' => FindingStatus::class,
            'deadline' => 'date',
            'assigned_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(FindingPhoto::class);
    }

    /**
     * Sisa hari sampai deadline (negatif = lewat). Null jika belum ada deadline.
     */
    public function daysLeft(): ?int
    {
        return $this->deadline ? (int) today()->diffInDays($this->deadline, false) : null;
    }

    /**
     * Warna urgensi untuk PIC: hijau (>3 hari), kuning (0-3 hari), merah (lewat deadline).
     */
    public function urgencyColor(): string
    {
        $days = $this->daysLeft();

        return match (true) {
            $this->status === FindingStatus::Terlambat, $days !== null && $days < 0 => 'red',
            $days !== null && $days <= 3 => 'amber',
            default => 'green',
        };
    }

    public function isOpenForPic(): bool
    {
        return in_array($this->status, [FindingStatus::BelumSelesai, FindingStatus::Terlambat], true);
    }
}
