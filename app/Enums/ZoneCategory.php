<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Kategori zona/area (PRD asli §4.4).
 */
enum ZoneCategory: string implements HasColor, HasLabel
{
    case Operasional = 'operasional';
    case Kolaborasi = 'kolaborasi';
    case Publik = 'publik';

    public function getLabel(): string
    {
        return match ($this) {
            self::Operasional => 'Operasional',
            self::Kolaborasi => 'Kolaborasi & Pertemuan',
            self::Publik => 'Publik & Akses',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Operasional => 'gray',
            self::Kolaborasi => 'info',
            self::Publik => 'success',
        };
    }
}
