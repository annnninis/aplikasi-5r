<?php

namespace App\Filament\Resources\Areas\Tables;

use App\Enums\ZoneCategory;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AreasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('zone_category')
                    ->label('Kategori')
                    ->badge()
                    ->sortable(),
                TextColumn::make('focus_5r')
                    ->label('Fokus 5R')
                    ->toggleable(),
                TextColumn::make('defaultPic.name')
                    ->label('PIC default')
                    ->placeholder('Belum ditentukan'),
                TextColumn::make('capacity')
                    ->label('Kapasitas')
                    ->placeholder('-')
                    ->toggleable(),
                IconColumn::make('requires_approval')
                    ->label('Perlu persetujuan')
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('zone_category')
                    ->label('Kategori')
                    ->options(ZoneCategory::class),
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
