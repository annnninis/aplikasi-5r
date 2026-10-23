<?php

namespace App\Filament\Resources\WispoItems\Schemas;

use App\Models\WispoItem;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WispoItemInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Data Barang')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('item_code')->label('Kode')->fontFamily('mono'),
                        TextEntry::make('name')->label('Nama'),
                        TextEntry::make('category')->label('Kategori')->badge()->color('gray'),
                        TextEntry::make('unit')->label('Satuan'),
                        TextEntry::make('storage_location')->label('Lokasi simpan')->placeholder('-'),
                        TextEntry::make('brand_model')->label('Merek / model')->placeholder('-'),
                        TextEntry::make('serial_number')->label('Nomor seri')->placeholder('-'),
                        IconEntry::make('is_active')->label('Aktif')->boolean(),
                        TextEntry::make('notes')->label('Catatan')->placeholder('-')->columnSpanFull(),
                        TextEntry::make('addedBy.name')->label('Ditambahkan oleh'),
                        TextEntry::make('created_at')->label('Ditambahkan')->dateTime('d M Y'),
                    ]),
                Section::make('Stok')
                    ->columnSpan(1)
                    ->schema([
                        ImageEntry::make('photo_path')
                            ->hiddenLabel()
                            ->disk('public')
                            ->imageHeight(200)
                            ->placeholder('Belum ada foto'),
                        TextEntry::make('current_stock_qty')
                            ->label('Stok saat ini')
                            ->formatStateUsing(fn (WispoItem $record): string => "{$record->current_stock_qty} {$record->unit}")
                            ->badge()
                            ->size('lg')
                            ->color(fn (WispoItem $record): string => $record->isLowStock() ? 'danger' : 'success'),
                        TextEntry::make('safety_stock_qty')
                            ->label('Safety stock')
                            ->formatStateUsing(fn (WispoItem $record): string => "{$record->safety_stock_qty} {$record->unit}"),
                        TextEntry::make('stock_status')
                            ->label('Status')
                            ->state(fn (WispoItem $record): string => $record->isLowStock() ? 'Menipis' : 'Aman')
                            ->badge()
                            ->color(fn (string $state): string => $state === 'Menipis' ? 'danger' : 'success'),
                    ]),
            ]);
    }
}
