<?php

namespace Database\Seeders;

use App\Enums\ZoneCategory;
use App\Models\Area;
use Illuminate\Database\Seeder;

/**
 * Data awal area/zona dari PRD asli §4.4. Aman dijalankan ulang (tidak menduplikasi).
 * Kapasitas, fasilitas, dan PIC default diisi Admin lewat Panel Kerja.
 */
class AreaSeeder extends Seeder
{
    public function run(): void
    {
        $zones = [
            ZoneCategory::Operasional->value => [
                'focus' => 'Ringkas, Rapi',
                'names' => [
                    'Ruang VP Operasi Pabrik IIIA',
                    'Ruang SVP Operasi III',
                    'Ruang Washift 3A',
                    'Ruang AVP SASU 3A',
                    'Ruang AVP PA 3A',
                    'Ruang AVP ZA II',
                    'Kantor SPO',
                ],
            ],
            ZoneCategory::Kolaborasi->value => [
                'focus' => 'Rapi, Rawat',
                'names' => [
                    'Ruang Sharing',
                    'Ruang Rapat Operasi Pabrik IIIA',
                    'Ruang Level Up Space',
                    'Ruang Operation Analytic Center',
                ],
            ],
            ZoneCategory::Publik->value => [
                'focus' => 'Resik',
                'names' => [
                    'Lobby Kantor Operasi III A',
                    'Koridor Kantor Operasi III A',
                    'Toilet Lt. 2',
                    'Locker Room',
                ],
            ],
        ];

        foreach ($zones as $category => $zone) {
            foreach ($zone['names'] as $name) {
                Area::firstOrCreate(
                    ['name' => $name],
                    ['zone_category' => $category, 'focus_5r' => $zone['focus']],
                );
            }
        }
    }
}
