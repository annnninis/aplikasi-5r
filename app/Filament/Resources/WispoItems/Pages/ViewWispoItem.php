<?php

namespace App\Filament\Resources\WispoItems\Pages;

use App\Filament\Resources\WispoItems\WispoItemResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewWispoItem extends ViewRecord
{
    protected static string $resource = WispoItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
