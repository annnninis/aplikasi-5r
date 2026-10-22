<?php

namespace App\Filament\Resources\Areas\Pages;

use App\Filament\Resources\Areas\AreaResource;
use Filament\Resources\Pages\EditRecord;

/**
 * Tanpa tombol hapus: area dipakai temuan & booking, jadi cukup dinonaktifkan.
 */
class EditArea extends EditRecord
{
    protected static string $resource = AreaResource::class;
}
