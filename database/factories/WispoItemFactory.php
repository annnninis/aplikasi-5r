<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WispoItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WispoItem>
 *
 * Stok awal selalu 0; tambah stok lewat StockService di test/seeder.
 */
class WispoItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['HT Motorola', 'PH Meter', 'Kunci Pas 17', 'Proyektor', 'Senter LED', 'Sarung Tangan', 'Obeng Set', 'Multimeter']).' '.fake()->unique()->numberBetween(1, 9999),
            'category' => fake()->randomElement(['Tools', 'HT', 'PH Meter', 'Proyektor', 'Sparepart', 'APD']),
            'unit' => 'pcs',
            'safety_stock_qty' => 2,
            'storage_location' => 'Rak '.fake()->randomElement(['A', 'B', 'C']).'-'.fake()->numberBetween(1, 5),
            'is_active' => true,
            'added_by' => User::factory(),
        ];
    }
}
