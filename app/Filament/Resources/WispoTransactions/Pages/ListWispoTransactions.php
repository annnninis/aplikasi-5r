<?php

namespace App\Filament\Resources\WispoTransactions\Pages;

use App\Exports\WispoTransactionsExport;
use App\Filament\Resources\WispoTransactions\StockActions;
use App\Filament\Resources\WispoTransactions\WispoTransactionResource;
use App\Models\WispoTransaction;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ListWispoTransactions extends ListRecords
{
    protected static string $resource = WispoTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->authorize(fn (): bool => Auth::user()->can('viewAny', WispoTransaction::class))
                ->tooltip('Mengikuti filter & pencarian yang sedang aktif')
                ->action(fn () => Excel::download(
                    new WispoTransactionsExport($this->getFilteredTableQuery()),
                    'riwayat-transaksi-gudang-'.now()->format('Ymd-Hi').'.xlsx',
                )),
            StockActions::record(),
        ];
    }
}
