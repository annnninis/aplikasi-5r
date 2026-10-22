<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\EditRecord;

/**
 * Tanpa tombol hapus: user punya riwayat (temuan, transaksi), jadi cukup dinonaktifkan.
 */
class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;
}
