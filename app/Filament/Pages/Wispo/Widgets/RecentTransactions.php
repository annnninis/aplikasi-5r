<?php

namespace App\Filament\Pages\Wispo\Widgets;

use App\Models\WispoTransaction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentTransactions extends TableWidget
{
    protected static ?string $heading = '10 transaksi terakhir';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => WispoTransaction::with(['item', 'recorder'])->latest()->limit(10))
            ->columns([
                TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y, H:i'),
                TextColumn::make('item.name')->label('Barang'),
                TextColumn::make('transaction_type')->label('Jenis')->badge(),
                TextColumn::make('qty')
                    ->label('Jumlah')
                    ->formatStateUsing(fn (WispoTransaction $record): string => ($record->transaction_type->increasesStock() ? '+' : '−').$record->qty)
                    ->color(fn (WispoTransaction $record): string => $record->transaction_type->increasesStock() ? 'success' : 'danger'),
                TextColumn::make('stock_after')->label('Stok sesudah'),
                TextColumn::make('recorder.name')->label('Dicatat oleh'),
            ])
            ->paginated(false);
    }
}
