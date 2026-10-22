<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Status temuan (PRD MVP §4.2).
 */
enum FindingStatus: string implements HasColor, HasLabel
{
    case BelumDitugaskan = 'belum_ditugaskan';
    case BelumSelesai = 'belum_selesai';
    case MenungguVerifikasi = 'menunggu_verifikasi';
    case Selesai = 'selesai';
    case Terlambat = 'terlambat';
    case Ditolak = 'ditolak';

    public function getLabel(): string
    {
        return match ($this) {
            self::BelumDitugaskan => 'Belum Ditugaskan',
            self::BelumSelesai => 'Belum Selesai',
            self::MenungguVerifikasi => 'Menunggu Verifikasi',
            self::Selesai => 'Selesai',
            self::Terlambat => 'Terlambat',
            self::Ditolak => 'Ditolak',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::BelumDitugaskan => 'gray',
            self::BelumSelesai => 'info',
            self::MenungguVerifikasi => 'warning',
            self::Selesai => 'success',
            self::Terlambat => 'danger',
            self::Ditolak => 'danger',
        };
    }
}
