<?php

namespace App\Filament\Resources\WispoItems\Schemas;

use App\Models\WispoItem;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;

class WispoItemForm
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
                        TextInput::make('name')
                            ->label('Nama barang')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('category')
                            ->label('Kategori')
                            ->required()
                            ->maxLength(100)
                            ->datalist(fn (): array => array_values(array_unique([...WispoItem::knownCategories(), 'Tools', 'HT', 'PH Meter', 'Proyektor', 'Sparepart', 'APD'])))
                            ->helperText('Pilih dari saran atau ketik kategori baru.'),
                        TextInput::make('unit')
                            ->label('Satuan')
                            ->required()
                            ->maxLength(30)
                            ->datalist(['pcs', 'unit', 'box', 'roll', 'set', 'pasang', 'liter', 'meter']),
                        TextInput::make('storage_location')
                            ->label('Lokasi simpan')
                            ->placeholder('mis. Rak A-3')
                            ->maxLength(100),
                        TextInput::make('brand_model')
                            ->label('Merek / model')
                            ->maxLength(100),
                        TextInput::make('serial_number')
                            ->label('Nomor seri')
                            ->maxLength(100),
                        Textarea::make('notes')
                            ->label('Catatan')
                            ->placeholder('mis. Perlu dikalibrasi tiap 6 bulan')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
                Section::make('Stok & Foto')
                    ->columnSpan(1)
                    ->schema([
                        Text::make(fn (?WispoItem $record): string => $record
                            ? "Kode: {$record->item_code} · Stok saat ini: {$record->current_stock_qty} {$record->unit}"
                            : 'Kode barang (SPO-xxxx) dibuat otomatis.'),
                        TextInput::make('initial_stock')
                            ->label('Stok awal')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->visibleOn('create')
                            ->dehydrated(false)
                            ->helperText('Dicatat sebagai transaksi "Masuk". Setelah ini stok hanya berubah lewat transaksi.'),
                        TextInput::make('safety_stock_qty')
                            ->label('Safety stock (batas minimum)')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->helperText('Jika stok ≤ angka ini, barang ditandai "Menipis".'),
                        FileUpload::make('photo_path')
                            ->label('Foto barang')
                            ->image()
                            ->disk('public')
                            ->directory('wispo')
                            ->maxSize(5120)
                            ->automaticallyResizeImagesMode('contain')
                            ->automaticallyResizeImagesToWidth('1200')
                            ->automaticallyResizeImagesToHeight('1200')
                            ->helperText('Satu foto, latar polos.'),
                        Toggle::make('is_active')
                            ->label('Barang aktif')
                            ->default(true),
                    ]),
            ]);
    }
}
