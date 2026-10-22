<?php

namespace App\Models;

use App\Enums\FindingPhotoType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FindingPhoto extends Model
{
    use HasUuids;

    protected $fillable = [
        'finding_id',
        'type',
        'photo_path',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => FindingPhotoType::class,
        ];
    }

    public function finding(): BelongsTo
    {
        return $this->belongsTo(Finding::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
