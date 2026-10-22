<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Status booking ruangan (PRD MVP §4.4).
 */
enum BookingStatus: string implements HasColor, HasLabel
{
    case MenungguPersetujuan = 'menunggu_persetujuan';
    case Disetujui = 'disetujui';
    case Ditolak = 'ditolak';
    case Dibatalkan = 'dibatalkan';
    case Kedaluwarsa = 'kedaluwarsa';

    public function getLabel(): string
    {
        return match ($this) {
            self::MenungguPersetujuan => 'Menunggu Persetujuan',
            self::Disetujui => 'Disetujui',
            self::Ditolak => 'Ditolak',
            self::Dibatalkan => 'Dibatalkan',
            self::Kedaluwarsa => 'Kedaluwarsa',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::MenungguPersetujuan => 'warning',
            self::Disetujui => 'success',
            self::Ditolak => 'danger',
            self::Dibatalkan => 'gray',
            self::Kedaluwarsa => 'gray',
        };
    }

    /**
     * Status yang memakai slot ruangan (dijaga exclusion constraint).
     *
     * @return list<self>
     */
    public static function occupying(): array
    {
        return [self::MenungguPersetujuan, self::Disetujui];
    }
}
