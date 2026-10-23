<?php

namespace App\Filament\Resources\WispoItems\Pages;

use App\Filament\Resources\WispoItems\WispoItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWispoItems extends ListRecords
{
    protected static string $resource = WispoItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
