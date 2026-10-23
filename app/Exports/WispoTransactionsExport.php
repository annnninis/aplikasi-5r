<?php

namespace App\Exports;

use App\Models\WispoTransaction;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Riwayat transaksi gudang ke Excel. Menerima query yang sudah terfilter dari tabel Transaksi Gudang.
 */
class WispoTransactionsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @param  Builder<WispoTransaction>  $query
     */
    public function __construct(private Builder $query) {}

    public function query(): Builder
    {
        // Urutan unik (id sebagai pemutus seri) wajib agar ekspor per-chunk tidak dobel/hilang baris.
        return $this->query
            ->with(['item', 'recorder', 'request.requester'])
            ->reorder()
            ->orderByDesc('transaction_date')
            ->orderByDesc('created_at')
            ->orderBy('id');
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Waktu catat',
            'Kode barang',
            'Nama barang',
            'Kategori',
            'Jenis',
            'Jumlah',
            'Satuan',
            'Stok sesudah',
            'Sumber / tujuan',
            'Pemohon (permintaan)',
            'Catatan',
            'Dicatat oleh',
        ];
    }

    /**
     * @param  WispoTransaction  $row
     */
    public function map($row): array
    {
        $sign = $row->transaction_type->increasesStock() ? 1 : -1;

        return [
            $row->transaction_date->format('Y-m-d'),
            $row->created_at->format('Y-m-d H:i'),
            $row->item->item_code,
            $row->item->name,
            $row->item->category,
            $row->transaction_type->getLabel(),
            $sign * $row->qty,
            $row->item->unit,
            $row->stock_after,
            $row->source_or_destination,
            $row->request?->requester?->name,
            $row->notes,
            $row->recorder->name,
        ];
    }
}
