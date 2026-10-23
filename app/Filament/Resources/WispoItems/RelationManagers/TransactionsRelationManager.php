<?php

namespace App\Filament\Resources\WispoItems\RelationManagers;

use App\Filament\Resources\WispoTransactions\StockActions;
use App\Filament\Resources\WispoTransactions\Tables\WispoTransactionsTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

/**
 * Riwayat transaksi satu barang + tombol Catat Transaksi untuk barang ini.
 */
class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $title = 'Riwayat Transaksi';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return WispoTransactionsTable::configure($table, showItem: false)
            ->headerActions([
                StockActions::record(fn (self $livewire) => $livewire->getOwnerRecord())
                    ->after(fn (self $livewire) => $livewire->dispatch('refresh-item')),
            ]);
    }
}
