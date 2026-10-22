<?php

namespace Database\Seeders;

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
    }
}
