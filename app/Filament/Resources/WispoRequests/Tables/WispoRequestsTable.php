<?php

namespace App\Filament\Resources\WispoRequests\Tables;

use App\Enums\UsageCategory;
use App\Enums\WispoRequestStatus;
use App\Enums\WispoRequestType;
use App\Models\WispoRequest;
use App\Services\WispoRequestService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class WispoRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['item', 'requester', 'approver']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Diajukan')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                TextColumn::make('requester.name')
                    ->label('Pemohon')
                    ->description(fn (WispoRequest $record): ?string => $record->requester->unit_kerja)
                    ->searchable(),
                TextColumn::make('item.name')
                    ->label('Barang')
                    ->description(fn (WispoRequest $record): string => "{$record->item->item_code} · stok {$record->item->current_stock_qty} {$record->item->unit}")
                    ->searchable(['name', 'item_code'])
                    ->wrap(),
                TextColumn::make('request_type')
                    ->label('Jenis')
                    ->badge(),
                TextColumn::make('qty')
                    ->label('Jumlah')
                    ->formatStateUsing(fn (WispoRequest $record): string => "{$record->qty} {$record->item->unit}"),
                TextColumn::make('usage_category')
                    ->label('Pemakaian')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('purpose')
                    ->label('Tujuan')
                    ->limit(40)
                    ->tooltip(fn (WispoRequest $record): string => $record->purpose)
                    ->wrap(),
                TextColumn::make('expected_return_at')
                    ->label('Rencana kembali')
                    ->date('d M Y')
                    ->placeholder('-')
                    ->color(fn (WispoRequest $record): ?string => $record->status === WispoRequestStatus::Terlambat ? 'danger' : null)
                    ->description(fn (WispoRequest $record): ?string => $record->actual_return_at
                        ? 'Kembali '.$record->actual_return_at->translatedFormat('d M Y')
                        : null)
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('approver.name')
                    ->label('Diproses oleh')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('request_type')->label('Jenis')->options(WispoRequestType::class),
                SelectFilter::make('usage_category')->label('Kategori pemakaian')->options(UsageCategory::class),
            ])
            ->recordActions([
                self::approve(),
                self::reject(),
                self::confirmReturn(),
            ]);
    }

    public static function confirmReturn(): Action
    {
        return Action::make('confirmReturn')
            ->label('Konfirmasi Kembali')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('primary')
            ->button()
            ->visible(fn (WispoRequest $record): bool => in_array($record->status, [WispoRequestStatus::Dipinjam, WispoRequestStatus::Terlambat], true))
            ->authorize(fn (WispoRequest $record): bool => Auth::user()->can('decide', $record))
            ->modalHeading('Konfirmasi pengembalian')
            ->modalDescription(fn (WispoRequest $record): string => "{$record->requester->name} mengembalikan {$record->qty} {$record->item->unit} {$record->item->name}. Stok akan bertambah kembali.")
            ->schema([
                Textarea::make('notes')
                    ->label('Kondisi barang saat kembali')
                    ->placeholder('mis. Baik, lengkap dengan charger')
                    ->maxLength(500),
            ])
            ->action(function (WispoRequest $record, array $data): void {
                app(WispoRequestService::class)->confirmReturn($record, Auth::user(), $data['notes'] ?? null);

                Notification::make()->title('Pengembalian dikonfirmasi')->success()->send();
            });
    }

    public static function approve(): Action
    {
        return Action::make('approve')
            ->label(fn (WispoRequest $record): string => $record->request_type === WispoRequestType::Peminjaman ? 'Setujui & Serahkan' : 'Setujui')
            ->icon('heroicon-o-check')
            ->color('success')
            ->button()
            ->visible(fn (WispoRequest $record): bool => $record->status === WispoRequestStatus::MenungguPersetujuan)
            ->authorize(fn (WispoRequest $record): bool => Auth::user()->can('decide', $record))
            ->requiresConfirmation()
            ->modalHeading('Setujui pengajuan')
            ->modalDescription(fn (WispoRequest $record): string => "Barang dianggap diserahkan ke {$record->requester->name}. Stok {$record->item->name} berkurang {$record->qty} {$record->item->unit} (sisa ".max(0, $record->item->current_stock_qty - $record->qty).').')
            ->action(function (WispoRequest $record, Action $action): void {
                try {
                    app(WispoRequestService::class)->approve($record, Auth::user());
                } catch (ValidationException $e) {
                    Notification::make()->title('Tidak bisa disetujui')->body(collect($e->errors())->flatten()->first())->danger()->send();
                    $action->halt();
                }

                Notification::make()->title('Pengajuan disetujui')->success()->send();
            });
    }

    public static function reject(): Action
    {
        return Action::make('reject')
            ->label('Tolak')
            ->icon('heroicon-o-x-mark')
            ->color('danger')
            ->visible(fn (WispoRequest $record): bool => $record->status === WispoRequestStatus::MenungguPersetujuan)
            ->authorize(fn (WispoRequest $record): bool => Auth::user()->can('decide', $record))
            ->modalHeading('Tolak pengajuan')
            ->schema([
                Textarea::make('reason')->label('Alasan')->required()->maxLength(500),
            ])
            ->action(function (WispoRequest $record, array $data): void {
                app(WispoRequestService::class)->reject($record, Auth::user(), $data['reason']);

                Notification::make()->title('Pengajuan ditolak')->success()->send();
            });
    }
}
