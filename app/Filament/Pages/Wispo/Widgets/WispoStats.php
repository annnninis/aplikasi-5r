<?php

namespace App\Filament\Pages\Wispo\Widgets;

use App\Enums\WispoRequestStatus;
use App\Filament\Resources\WispoItems\WispoItemResource;
use App\Filament\Resources\WispoRequests\WispoRequestResource;
use App\Models\WispoItem;
use App\Models\WispoRequest;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class WispoStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $low = WispoItem::active()->lowStock()->count();
        $pending = WispoRequest::where('status', WispoRequestStatus::MenungguPersetujuan)->count();
        $borrowed = WispoRequest::where('status', WispoRequestStatus::Dipinjam)->count();
        $late = WispoRequest::where('status', WispoRequestStatus::Terlambat)->count();

        return [
            Stat::make('Barang aktif', WispoItem::active()->count())
                ->url(WispoItemResource::getUrl()),
            Stat::make('Stok menipis', $low)
                ->color($low > 0 ? 'danger' : 'success')
                ->description($low > 0 ? 'Perlu restock' : 'Semua aman')
                ->url(WispoItemResource::getUrl(parameters: ['filters' => ['low_stock' => ['isActive' => true]]])),
            Stat::make('Menunggu persetujuan', $pending)
                ->color($pending > 0 ? 'warning' : 'gray')
                ->url(WispoRequestResource::getUrl(parameters: ['activeTab' => 'menunggu'])),
            Stat::make('Sedang dipinjam', $borrowed + $late)
                ->description($late > 0 ? "{$late} terlambat" : 'Tidak ada yang terlambat')
                ->color($late > 0 ? 'danger' : 'gray')
                ->url(WispoRequestResource::getUrl(parameters: ['activeTab' => 'dipinjam'])),
        ];
    }
}
