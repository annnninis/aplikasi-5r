<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        // Akun dibuat Admin, jadi email dianggap sudah terverifikasi.
        $this->record->forceFill(['email_verified_at' => now()])->save();
    }
}
