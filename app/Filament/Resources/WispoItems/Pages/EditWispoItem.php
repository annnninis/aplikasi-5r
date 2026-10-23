<?php

namespace App\Filament\Resources\WispoItems\Pages;

use App\Filament\Resources\WispoItems\WispoItemResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Tanpa tombol hapus: barang punya riwayat transaksi, jadi cukup dinonaktifkan.
 * Stok tidak bisa diedit di sini; gunakan transaksi.
 */
class EditWispoItem extends EditRecord
{
    protected static string $resource = WispoItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
