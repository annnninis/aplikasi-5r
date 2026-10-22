<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\Role;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->description(fn (User $record): string => $record->email)
                    ->searchable(['name', 'email'])
                    ->sortable(),
                TextColumn::make('role')
                    ->label('Peran')
                    ->badge()
                    ->sortable(),
                IconColumn::make('is_petugas_gudang')
                    ->label('Petugas Gudang')
                    ->boolean(),
                TextColumn::make('unit_kerja')
                    ->label('Unit Kerja')
                    ->searchable()
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Peran')
                    ->options(Role::class),
                TernaryFilter::make('is_petugas_gudang')
                    ->label('Petugas Gudang'),
                TernaryFilter::make('is_active')
                    ->label('Status akun')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('toggleActive')
                    ->label(fn (User $record): string => $record->is_active ? 'Nonaktifkan' : 'Aktifkan')
                    ->icon(fn (User $record): string => $record->is_active ? 'heroicon-o-no-symbol' : 'heroicon-o-check-circle')
                    ->color(fn (User $record): string => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalDescription(fn (User $record): string => $record->is_active
                        ? "{$record->name} tidak akan bisa login sampai diaktifkan kembali."
                        : "{$record->name} akan bisa login kembali.")
                    ->hidden(fn (User $record): bool => $record->is(Auth::user()))
                    ->authorize(fn (User $record): bool => Auth::user()->can('update', $record))
                    ->action(fn (User $record) => $record->update(['is_active' => ! $record->is_active])),
            ]);
    }
}
