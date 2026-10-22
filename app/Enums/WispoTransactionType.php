<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Jenis transaksi stok gudang.
 */
enum WispoTransactionType: string implements HasColor, HasLabel
{
    case Masuk = 'masuk';
    case Keluar = 'keluar';
    case Reject = 'reject';
    case Pinjam = 'pinjam';
    case Kembali = 'kembali';

    public function getLabel(): string
    {
        return match ($this) {
            self::Masuk => 'Masuk',
            self::Keluar => 'Keluar',
            self::Reject => 'Reject',
            self::Pinjam => 'Dipinjam',
            self::Kembali => 'Dikembalikan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Masuk => 'success',
            self::Keluar => 'info',
            self::Reject => 'danger',
            self::Pinjam => 'warning',
            self::Kembali => 'success',
        };
    }

    /**
     * Apakah transaksi ini menambah stok (true) atau mengurangi (false).
     */
    public function increasesStock(): bool
    {
        return in_array($this, [self::Masuk, self::Kembali], true);
    }
}
