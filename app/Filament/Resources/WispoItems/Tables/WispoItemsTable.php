<?php

namespace App\Filament\Resources\WispoItems\Tables;

use App\Models\WispoItem;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WispoItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('item_code')
            ->columns([
                ImageColumn::make('photo_path')
                    ->label('Foto')
                    ->disk('public')
                    ->square()
                    ->imageSize(44),
                TextColumn::make('item_code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),
                TextColumn::make('name')
                    ->label('Nama')
                    ->description(fn (WispoItem $record): ?string => $record->brand_model)
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('current_stock_qty')
                    ->label('Stok')
                    ->formatStateUsing(fn (WispoItem $record): string => "{$record->current_stock_qty} {$record->unit}")
                    ->badge()
                    ->color(fn (WispoItem $record): string => $record->isLowStock() ? 'danger' : 'success')
                    ->description(fn (WispoItem $record): string => "min. {$record->safety_stock_qty}")
                    ->sortable(),
                TextColumn::make('storage_location')
                    ->label('Lokasi')
                    ->placeholder('-')
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('low_stock')
                    ->label('Hanya stok menipis')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->lowStock()),
                SelectFilter::make('category')
                    ->label('Kategori')
                    ->options(fn (): array => array_combine(WispoItem::knownCategories(), WispoItem::knownCategories()) ?: []),
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif')
                    ->default(true),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
