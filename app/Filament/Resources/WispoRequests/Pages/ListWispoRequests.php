<?php

namespace App\Filament\Resources\WispoRequests\Pages;

use App\Enums\WispoRequestStatus;
use App\Filament\Resources\WispoRequests\WispoRequestResource;
use App\Models\WispoRequest;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

/**
 * Tanpa tombol "Buat": pengajuan dibuat user dari halaman Stok Barang.
 */
class ListWispoRequests extends ListRecords
{
    protected static string $resource = WispoRequestResource::class;

    public function getTabs(): array
    {
        $tab = fn (string $label, array $statuses): Tab => Tab::make($label)
            ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', $statuses))
            ->badge(WispoRequest::whereIn('status', $statuses)->count() ?: null);

        return [
            'menunggu' => $tab('Menunggu Persetujuan', [WispoRequestStatus::MenungguPersetujuan])->badgeColor('warning'),
            'dipinjam' => $tab('Sedang Dipinjam', [WispoRequestStatus::Dipinjam, WispoRequestStatus::Terlambat]),
            'selesai' => $tab('Selesai', [WispoRequestStatus::Disetujui, WispoRequestStatus::Dikembalikan]),
            'ditolak' => $tab('Ditolak', [WispoRequestStatus::Ditolak]),
            'semua' => Tab::make('Semua'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'menunggu';
    }
}
