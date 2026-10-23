<?php

namespace App\Filament\Resources\WispoItems\Pages;

use App\Enums\WispoTransactionType;
use App\Filament\Resources\WispoItems\WispoItemResource;
use App\Services\StockService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateWispoItem extends CreateRecord
{
    protected static string $resource = WispoItemResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['added_by'] = Auth::id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $initial = (int) ($this->data['initial_stock'] ?? 0);

        if ($initial > 0) {
            app(StockService::class)->record(
                $this->record,
                WispoTransactionType::Masuk,
                $initial,
                Auth::user(),
                notes: 'Stok awal',
            );
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
