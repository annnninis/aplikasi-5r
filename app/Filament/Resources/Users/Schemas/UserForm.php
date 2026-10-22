<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Role;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        // Admin tidak boleh menurunkan role atau menonaktifkan akunnya sendiri.
        $isSelf = fn (?User $record): bool => $record?->is(Auth::user()) ?? false;

        return $schema
            ->components([
                Section::make('Data Pengguna')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('unit_kerja')
                            ->label('Unit Kerja')
                            ->maxLength(255),
                        TextInput::make('password')
                            ->label('Kata Sandi')
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->helperText(fn (string $operation): string => $operation === 'create'
                                ? 'Minimal 8 karakter. Sampaikan ke pengguna secara pribadi.'
                                : 'Kosongkan jika tidak ingin mengganti kata sandi.'),
                    ]),
                Section::make('Peran & Akses')
                    ->columns(2)
                    ->schema([
                        Select::make('role')
                            ->label('Peran')
                            ->options(Role::class)
                            ->default(Role::Staff)
                            ->required()
                            ->disabled($isSelf)
                            ->helperText(fn (?User $record): ?string => $isSelf($record) ? 'Anda tidak bisa mengubah peran akun sendiri.' : null),
                        Toggle::make('is_petugas_gudang')
                            ->label('Petugas Gudang (WISPO)')
                            ->helperText('Bisa mencatat transaksi gudang dan menyetujui permintaan barang.'),
                        Toggle::make('is_active')
                            ->label('Akun aktif')
                            ->default(true)
                            ->disabled($isSelf)
                            ->helperText('Akun nonaktif tidak bisa login. Riwayatnya tetap tersimpan.'),
                    ]),
            ]);
    }
}
