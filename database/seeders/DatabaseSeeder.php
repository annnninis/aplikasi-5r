<?php

namespace Database\Seeders;

use App\Enums\WispoTransactionType;
use App\Models\Area;
use App\Models\User;
use App\Models\WispoItem;
use App\Services\StockService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Akun contoh HANYA untuk development lokal. Semua memakai password
     * bawaan UserFactory: "password". Jangan jalankan seeder ini di server produksi.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            // Di server hanya data area awal; akun dibuat lewat `php artisan app:create-admin`.
            $this->call(AreaSeeder::class);
            $this->command?->warn('Produksi: akun & barang contoh TIDAK dibuat. Hanya data area awal.');

            return;
        }

        $accounts = [
            ['name' => 'Admin Contoh', 'email' => 'admin@5r.test', 'state' => fn ($f) => $f->admin()],
            ['name' => 'Koordinator 5R Contoh', 'email' => 'koordinator@5r.test', 'state' => fn ($f) => $f->koordinator()],
            ['name' => 'Manajemen Contoh', 'email' => 'manajemen@5r.test', 'state' => fn ($f) => $f->management()],
            ['name' => 'Petugas Gudang Contoh', 'email' => 'gudang@5r.test', 'state' => fn ($f) => $f->petugasGudang()],
            ['name' => 'Staff Contoh', 'email' => 'staff@5r.test', 'state' => fn ($f) => $f],
            ['name' => 'Staff Nonaktif', 'email' => 'nonaktif@5r.test', 'state' => fn ($f) => $f->inactive()],
        ];

        foreach ($accounts as $account) {
            $account['state'](User::factory())->create([
                'name' => $account['name'],
                'email' => $account['email'],
                'unit_kerja' => 'Operasi III A',
            ]);
        }

        $this->call(AreaSeeder::class);

        // Contoh pengaturan area untuk uji coba lokal.
        $staff = User::where('email', 'staff@5r.test')->first();
        $koordinator = User::where('email', 'koordinator@5r.test')->first();

        Area::query()->update(['default_pic_id' => $staff->id]);
        Area::bookable()->update(['capacity' => 12, 'facilities' => 'TV, Proyektor']);
        Area::where('name', 'Ruang Rapat Operasi Pabrik IIIA')->update([
            'requires_approval' => true,
            'default_pic_id' => $koordinator->id,
        ]);

        $this->seedSampleItems(User::where('email', 'gudang@5r.test')->first());
    }

    /**
     * Barang contoh untuk uji coba WISPO. Stok awal lewat StockService, sama seperti di aplikasi.
     */
    private function seedSampleItems(User $petugas): void
    {
        $items = [
            ['HT Motorola XiR P3688', 'HT', 'unit', 12, 4, 'Lemari HT'],
            ['PH Meter Hanna HI98107', 'PH Meter', 'unit', 3, 2, 'Rak B-1'],
            ['Proyektor Epson EB-X51', 'Proyektor', 'unit', 2, 1, 'Lemari Multimedia'],
            ['Kunci Pas Set 8-24 mm', 'Tools', 'set', 6, 2, 'Rak A-2'],
            ['Multimeter Fluke 117', 'Tools', 'unit', 1, 2, 'Rak A-3'],
            ['Sarung Tangan Kulit', 'APD', 'pasang', 40, 15, 'Rak C-1'],
            ['Masker Respirator 3M', 'APD', 'pcs', 8, 20, 'Rak C-2'],
            ['Baterai AA Alkaline', 'Sparepart', 'pcs', 60, 24, 'Laci Meja Gudang'],
        ];

        foreach ($items as [$name, $category, $unit, $stock, $safety, $location]) {
            $item = WispoItem::create([
                'name' => $name,
                'category' => $category,
                'unit' => $unit,
                'safety_stock_qty' => $safety,
                'storage_location' => $location,
                'added_by' => $petugas->id,
            ]);

            app(StockService::class)->record($item, WispoTransactionType::Masuk, $stock, $petugas, notes: 'Stok awal');
        }
    }
}
