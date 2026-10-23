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

    /**
     * Tahapan untuk stepper status (PRD §3.9.3): state = done | active | failed | pending.
     *
     * @return list<array{label: string, state: string}>
     */
    public function steps(): array
    {
        $isLoan = $this->request_type === WispoRequestType::Peminjaman;
        $labels = $isLoan
            ? ['Diajukan', 'Menunggu Persetujuan', 'Dipinjam', 'Dikembalikan']
            : ['Diajukan', 'Menunggu Persetujuan', 'Disetujui & Diambil'];

        // [jumlah tahap selesai, state tahap berikutnya, label pengganti tahap berikutnya]
        [$done, $nextState, $nextLabel] = match ($this->status) {
            WispoRequestStatus::MenungguPersetujuan => [1, 'active', null],
            WispoRequestStatus::Ditolak => [1, 'failed', 'Ditolak'],
            WispoRequestStatus::Disetujui => [3, null, null],
            WispoRequestStatus::Dipinjam => [2, 'active', null],
            WispoRequestStatus::Terlambat => [2, 'failed', 'Terlambat dikembalikan'],
            WispoRequestStatus::Dikembalikan => [4, null, null],
        };

        return collect($labels)->map(fn (string $label, int $i) => match (true) {
            $i < $done => ['label' => $label, 'state' => 'done'],
            $i === $done && $nextState !== null => ['label' => $nextLabel ?? $label, 'state' => $nextState],
            default => ['label' => $label, 'state' => 'pending'],
        })->all();
    }
}
