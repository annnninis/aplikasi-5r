<?php

namespace App\Filament\Resources\Areas\Schemas;

use App\Enums\ZoneCategory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class AreaForm
{
    public static function configure(Schema $schema): Schema
    {
        $isKolaborasi = fn (Get $get): bool => self::categoryValue($get('zone_category')) === ZoneCategory::Kolaborasi->value;

        return $schema
            ->components([
                Section::make('Data Area')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama ruangan / area')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Select::make('zone_category')
                            ->label('Kategori zona')
                            ->options(ZoneCategory::class)
                            ->required()
                            ->live(),
                        TextInput::make('focus_5r')
                            ->label('Fokus 5R')
                            ->placeholder('mis. Ringkas, Rapi')
                            ->maxLength(255),
                        Select::make('default_pic_id')
                            ->label('PIC default')
                            ->relationship('defaultPic', 'name', fn (Builder $query) => $query->where('is_active', true))
                            ->searchable()
                            ->preload()
                            ->helperText('Otomatis dipilih sebagai PIC temuan di area ini, dan penyetuju booking ruangan.'),
                        Toggle::make('is_active')
                            ->label('Area aktif')
                            ->default(true),
                    ]),
                Section::make('Pengaturan Booking Ruangan')
                    ->description('Hanya untuk area kategori Kolaborasi & Pertemuan.')
                    ->columns(2)
                    ->visible($isKolaborasi)
                    ->schema([
                        TextInput::make('capacity')
                            ->label('Kapasitas (orang)')
                            ->numeric()
                            ->minValue(1)
                            ->required($isKolaborasi),
                        TextInput::make('facilities')
                            ->label('Fasilitas')
                            ->placeholder('mis. TV, Proyektor, Mic')
                            ->maxLength(255),
                        Toggle::make('requires_approval')
                            ->label('Booking perlu persetujuan PIC/Admin'),
                    ]),
            ]);
    }

    private static function categoryValue(mixed $state): ?string
    {
        return $state instanceof ZoneCategory ? $state->value : $state;
    }
}
