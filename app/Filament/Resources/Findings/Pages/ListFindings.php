<?php

namespace App\Filament\Resources\Findings\Pages;

use App\Enums\FindingStatus;
use App\Exports\FindingsExport;
use App\Filament\Resources\Findings\FindingResource;
use App\Models\Finding;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Tanpa tombol "Buat": temuan hanya dibuat lewat Lapor Cepat.
 */
class ListFindings extends ListRecords
{
    protected static string $resource = FindingResource::class;

    public function getTabs(): array
    {
        $tab = fn (string $label, array $statuses): Tab => Tab::make($label)
            ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', $statuses))
            ->badge(Finding::whereIn('status', $statuses)->count() ?: null);

        return [
            'baru' => $tab('Baru', [FindingStatus::BelumDitugaskan]),
            'berjalan' => $tab('Berjalan', [FindingStatus::BelumSelesai]),
            'verifikasi' => $tab('Perlu Verifikasi', [FindingStatus::MenungguVerifikasi]),
            'terlambat' => $tab('Terlambat', [FindingStatus::Terlambat])->badgeColor('danger'),
            'selesai' => $tab('Selesai', [FindingStatus::Selesai]),
            'ditolak' => $tab('Ditolak', [FindingStatus::Ditolak]),
            'semua' => Tab::make('Semua'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->authorize(fn (): bool => Auth::user()->can('viewAny', Finding::class))
                ->tooltip('Mengikuti tab, filter & pencarian yang sedang aktif')
                ->action(fn () => Excel::download(
                    new FindingsExport($this->getFilteredTableQuery()),
                    'temuan-5r-'.now()->format('Ymd-Hi').'.xlsx',
                )),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'baru';
    }
}
