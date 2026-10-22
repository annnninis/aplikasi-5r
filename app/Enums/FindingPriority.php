<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Prioritas temuan; menentukan deadline default.
 */
enum FindingPriority: string implements HasColor, HasLabel
{
    case Tinggi = 'tinggi';
    case Sedang = 'sedang';
    case Rendah = 'rendah';

    public function getLabel(): string
    {
        return match ($this) {
            self::Tinggi => 'Tinggi',
            self::Sedang => 'Sedang',
            self::Rendah => 'Rendah',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Tinggi => 'danger',
            self::Sedang => 'warning',
            self::Rendah => 'gray',
        };
    }

    /**
     * Jumlah hari dari waktu penugasan sampai deadline.
     */
    public function deadlineDays(): int
    {
        return match ($this) {
            self::Tinggi => 1,
            self::Sedang => 7,
            self::Rendah => 14,
        };
    }
}
