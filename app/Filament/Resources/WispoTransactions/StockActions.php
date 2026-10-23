<?php

namespace App\Filament\Resources\WispoTransactions;

use App\Enums\WispoTransactionType;
use App\Models\WispoItem;
use App\Models\WispoTransaction;
use App\Services\StockService;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class StockActions
{
    /**
     * Catat transaksi manual oleh Petugas Gudang. Pinjam/kembali dicatat otomatis dari permintaan barang.
     *
     * @param  (Closure(mixed): ?WispoItem)|null  $fixedItem  barang yang sudah pasti (mis. dari halaman detail barang)
     */
    public static function record(?Closure $fixedItem = null): Action
    {
        $itemFor = fn (mixed $livewire, Get $get): ?WispoItem => $fixedItem
            ? $fixedItem($livewire)
            : WispoItem::find($get('item_id'));

        return Action::make('recordTransaction')
            ->label('Catat Transaksi')
            ->icon('heroicon-o-arrows-right-left')
            ->authorize(fn (): bool => Auth::user()->can('create', WispoTransaction::class))
            ->modalHeading('Catat transaksi gudang')
            ->modalSubmitActionLabel('Simpan')
            ->schema([
                Select::make('item_id')
                    ->label('Barang')
                    ->options(fn (): array => WispoItem::active()->orderBy('name')->get()
                        ->mapWithKeys(fn (WispoItem $i) => [$i->id => "{$i->item_code} · {$i->name} (stok {$i->current_stock_qty} {$i->unit})"])
                        ->all())
                    ->searchable()
                    ->required()
                    ->live()
                    ->hidden($fixedItem !== null),
                ToggleButtons::make('transaction_type')
                    ->label('Jenis')
                    ->options([
                        WispoTransactionType::Masuk->value => 'Masuk',
                        WispoTransactionType::Keluar->value => 'Keluar',
                        WispoTransactionType::Reject->value => 'Reject / Rusak',
                    ])
                    ->colors([
                        WispoTransactionType::Masuk->value => 'success',
                        WispoTransactionType::Keluar->value => 'info',
                        WispoTransactionType::Reject->value => 'danger',
                    ])
                    ->inline()
                    ->required()
                    ->default(WispoTransactionType::Masuk->value)
                    ->live(),
                TextInput::make('qty')
                    ->label('Jumlah')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->required()
                    ->suffix(fn (mixed $livewire, Get $get): ?string => $itemFor($livewire, $get)?->unit)
                    ->helperText(function (mixed $livewire, Get $get) use ($itemFor): ?string {
                        $item = $itemFor($livewire, $get);

                        return $item ? "Stok saat ini: {$item->current_stock_qty} {$item->unit}" : null;
                    }),
                DatePicker::make('transaction_date')
                    ->label('Tanggal')
                    ->default(today())
                    ->maxDate(today())
                    ->required()
                    ->native(false)
                    ->displayFormat('d M Y'),
                TextInput::make('source_or_destination')
                    ->label(fn (Get $get): string => $get('transaction_type') === WispoTransactionType::Masuk->value
                        ? 'Sumber (dari siapa/vendor)'
                        : 'Tujuan (untuk siapa/keperluan)')
                    ->maxLength(255),
                Textarea::make('notes')
                    ->label('Catatan')
                    ->rows(2)
                    ->maxLength(1000),
            ])
            ->action(function (array $data, mixed $livewire, Action $action) use ($fixedItem): void {
                $item = $fixedItem ? $fixedItem($livewire) : WispoItem::findOrFail($data['item_id']);

                try {
                    $transaction = app(StockService::class)->record(
                        $item,
                        WispoTransactionType::from($data['transaction_type']),
                        (int) $data['qty'],
                        Auth::user(),
                        $data['source_or_destination'] ?? null,
                        $data['notes'] ?? null,
                        date: Carbon::parse($data['transaction_date']),
                    );
                } catch (ValidationException $e) {
                    // Mis. stok tidak cukup: tampilkan pesan, biarkan form tetap terbuka untuk dikoreksi.
                    Notification::make()->title('Transaksi tidak bisa disimpan')->body(collect($e->errors())->flatten()->first())->danger()->send();
                    $action->halt();
                }

                Notification::make()
                    ->title('Transaksi tersimpan')
                    ->body("Stok {$item->name} sekarang {$transaction->stock_after} {$item->unit}.")
                    ->success()
                    ->send();
            });
    }
}
