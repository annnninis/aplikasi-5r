<?php

namespace App\Filament\Pages\Wispo\Widgets;

use App\Enums\WispoRequestStatus;
use App\Models\WispoRequest;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Peminjaman yang belum kembali, yang paling dekat/lewat tanggal kembali di atas.
 */
class ActiveLoans extends TableWidget
{
    protected static ?string $heading = 'Peminjaman belum dikembalikan';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => WispoRequest::with(['item', 'requester'])
                ->whereIn('status', [WispoRequestStatus::Dipinjam, WispoRequestStatus::Terlambat])
                ->orderBy('expected_return_at'))
            ->columns([
                TextColumn::make('requester.name')
                    ->label('Peminjam')
                    ->description(fn (WispoRequest $record): string => "{$record->qty} {$record->item->unit} {$record->item->name}"),
                TextColumn::make('expected_return_at')
                    ->label('Rencana kembali')
                    ->date('d M Y')
                    ->description(function (WispoRequest $record): string {
                        $days = (int) today()->diffInDays($record->expected_return_at, false);

                        return match (true) {
                            $days < 0 => 'Terlambat '.abs($days).' hari',
                            $days === 0 => 'Hari ini',
                            default => "{$days} hari lagi",
                        };
                    })
                    ->color(fn (WispoRequest $record): ?string => $record->status === WispoRequestStatus::Terlambat ? 'danger' : null),
            ])
            ->emptyStateHeading('Tidak ada barang yang sedang dipinjam')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->paginated([5, 10]);
    }
}
