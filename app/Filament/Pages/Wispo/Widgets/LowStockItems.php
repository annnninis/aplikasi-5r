<?php

namespace App\Filament\Pages\Wispo\Widgets;

use App\Filament\Resources\WispoItems\WispoItemResource;
use App\Models\WispoItem;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Barang stok menipis, paling kritis (paling jauh di bawah batas) di atas.
 */
class LowStockItems extends TableWidget
{
    protected static ?string $heading = 'Stok menipis';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => WispoItem::active()->lowStock()
                ->orderByRaw('current_stock_qty - safety_stock_qty')
                ->orderBy('name'))
            ->columns([
                TextColumn::make('name')
                    ->label('Barang')
                    ->description(fn (WispoItem $record): string => $record->item_code.($record->storage_location ? " · {$record->storage_location}" : '')),
                TextColumn::make('current_stock_qty')
                    ->label('Stok')
                    ->formatStateUsing(fn (WispoItem $record): string => "{$record->current_stock_qty} / min. {$record->safety_stock_qty} {$record->unit}")
                    ->badge()
                    ->color('danger'),
            ])
            ->recordUrl(fn (WispoItem $record): string => WispoItemResource::getUrl('view', ['record' => $record]))
            ->emptyStateHeading('Semua stok aman')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->paginated([5, 10]);
    }
}
