<?php

namespace App\Exports;

use App\Models\Finding;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Daftar temuan ke Excel (PRD MVP A6). Menerima query yang sudah terfilter dari tabel Semua Temuan.
 */
class FindingsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @param  Builder<Finding>  $query
     */
    public function __construct(private Builder $query) {}

    public function query(): Builder
    {
        return $this->query
            ->with(['area', 'reporter', 'pic', 'verifier'])
            ->reorder()
            ->orderByDesc('created_at')
            ->orderBy('id');
    }

    public function headings(): array
    {
        return [
            'Dilaporkan',
            'Area',
            'Kategori zona',
            'Deskripsi',
            'Pelapor',
            'Prioritas',
            'Status',
            'PIC',
            'Ditugaskan',
            'Deadline',
            'Selesai',
            'Hari penyelesaian',
            'Diverifikasi oleh',
            'Catatan PIC',
            'Alasan ditolak',
        ];
    }

    /**
     * @param  Finding  $row
     */
    public function map($row): array
    {
        return [
            $row->created_at->format('Y-m-d H:i'),
            $row->area->name,
            $row->area->zone_category->getLabel(),
            $row->description,
            $row->reporter->name,
            $row->priority->getLabel(),
            $row->status->getLabel(),
            $row->pic?->name,
            $row->assigned_at?->format('Y-m-d H:i'),
            $row->deadline?->format('Y-m-d'),
            $row->resolved_at?->format('Y-m-d H:i'),
            $row->assigned_at && $row->resolved_at ? round($row->assigned_at->diffInHours($row->resolved_at) / 24, 1) : null,
            $row->verifier?->name,
            $row->resolution_note,
            $row->rejection_reason,
        ];
    }
}
