<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\User;
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
    }
}
