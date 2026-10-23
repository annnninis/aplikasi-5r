<?php

namespace App\Filament\Resources\WispoTransactions\Tables;

use App\Enums\WispoTransactionType;
use App\Models\WispoTransaction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Riwayat transaksi gudang (read-only). Dipakai di halaman Transaksi Gudang dan di detail barang.
 */
class WispoTransactionsTable
{
    public static function configure(Table $table, bool $showItem = true): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['item', 'recorder', 'request.requester']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('transaction_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->description(fn (WispoTransaction $record): string => $record->created_at->format('H:i'))
                    ->sortable(),
                TextColumn::make('item.name')
                    ->label('Barang')
                    ->description(fn (WispoTransaction $record): string => $record->item->item_code)
                    ->searchable(['name', 'item_code'])
                    ->visible($showItem),
                TextColumn::make('transaction_type')
                    ->label('Jenis')
                    ->badge(),
                TextColumn::make('qty')
                    ->label('Jumlah')
                    ->formatStateUsing(fn (WispoTransaction $record): string => ($record->transaction_type->increasesStock() ? '+' : '−').$record->qty)
                    ->color(fn (WispoTransaction $record): string => $record->transaction_type->increasesStock() ? 'success' : 'danger')
                    ->weight('bold'),
                TextColumn::make('stock_after')
                    ->label('Stok sesudah'),
                TextColumn::make('source_or_destination')
                    ->label('Sumber / tujuan')
                    ->state(fn (WispoTransaction $record): ?string => $record->source_or_destination
                        ?? ($record->request ? 'Permintaan: '.$record->request->requester->name : null))
                    ->placeholder('-')
                    ->wrap(),
                TextColumn::make('notes')
                    ->label('Catatan')
                    ->placeholder('-')
                    ->limit(40)
                    ->tooltip(fn (WispoTransaction $record): ?string => $record->notes)
                    ->toggleable(),
                TextColumn::make('recorder.name')
                    ->label('Dicatat oleh')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('transaction_type')
                    ->label('Jenis')
                    ->options(WispoTransactionType::class)
                    ->multiple(),
                SelectFilter::make('item_id')
                    ->label('Barang')
                    ->relationship('item', 'name')
                    ->searchable()
                    ->preload()
                    ->visible($showItem),
                Filter::make('period')
                    ->label('Periode')
                    ->schema([
                        DatePicker::make('from')->label('Dari tanggal'),
                        DatePicker::make('until')->label('Sampai tanggal'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('transaction_date', '>=', $d))
                        ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('transaction_date', '<=', $d))),
            ])
            ->recordActions([]);
    }
}
