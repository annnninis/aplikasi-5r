<?php

namespace App\Filament\Resources\WispoTransactions\Pages;

use App\Filament\Resources\WispoTransactions\StockActions;
use App\Filament\Resources\WispoTransactions\WispoTransactionResource;
use Filament\Resources\Pages\ListRecords;

class ListWispoTransactions extends ListRecords
{
    protected static string $resource = WispoTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            StockActions::record(),
        ];
    }
}
