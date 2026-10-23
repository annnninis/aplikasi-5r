<?php

namespace App\Filament\Resources\WispoTransactions;

use App\Filament\Resources\WispoTransactions\Pages\ListWispoTransactions;
use App\Filament\Resources\WispoTransactions\Tables\WispoTransactionsTable;
use App\Models\WispoTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class WispoTransactionResource extends Resource
{
    protected static ?string $model = WispoTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|UnitEnum|null $navigationGroup = 'Gudang (WISPO)';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'transaksi-gudang';

    protected static ?string $modelLabel = 'transaksi';

    protected static ?string $pluralModelLabel = 'Transaksi Gudang';

    public static function table(Table $table): Table
    {
        return WispoTransactionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWispoTransactions::route('/'),
        ];
    }
}
