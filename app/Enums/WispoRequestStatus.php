<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Status permintaan/peminjaman barang (PRD MVP §4.3).
 */
enum WispoRequestStatus: string implements HasColor, HasLabel
{
    case MenungguPersetujuan = 'menunggu_persetujuan';
    case Disetujui = 'disetujui';
    case Dipinjam = 'dipinjam';
    case Dikembalikan = 'dikembalikan';
    case Terlambat = 'terlambat';
    case Ditolak = 'ditolak';

    public function getLabel(): string
    {
        return match ($this) {
            self::MenungguPersetujuan => 'Menunggu Persetujuan',
            self::Disetujui => 'Disetujui',
            self::Dipinjam => 'Dipinjam',
            self::Dikembalikan => 'Dikembalikan',
            self::Terlambat => 'Terlambat',
            self::Ditolak => 'Ditolak',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::MenungguPersetujuan => 'warning',
            self::Disetujui => 'success',
            self::Dipinjam => 'info',
            self::Dikembalikan => 'success',
            self::Terlambat => 'danger',
            self::Ditolak => 'danger',
        };
    }
}
