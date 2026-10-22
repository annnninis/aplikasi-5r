<?php

namespace App\Filament\Resources\Findings\Pages;

use App\Enums\FindingStatus;
use App\Filament\Resources\Findings\FindingResource;
use App\Models\Finding;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

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

    public function getDefaultActiveTab(): string|int|null
    {
        return 'baru';
    }
}
