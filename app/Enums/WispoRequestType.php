<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Permintaan (tidak kembali) atau peminjaman (wajib kembali).
 */
enum WispoRequestType: string implements HasColor, HasLabel
{
    case Permintaan = 'permintaan';
    case Peminjaman = 'peminjaman';

    public function getLabel(): string
    {
        return match ($this) {
            self::Permintaan => 'Permintaan',
            self::Peminjaman => 'Peminjaman',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Permintaan => 'info',
            self::Peminjaman => 'warning',
        };
    }
}
