<?php

namespace Database\Factories;

use App\Enums\ZoneCategory;
use App\Models\Area;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Area>
 */
class AreaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Ruang '.fake()->unique()->words(2, true),
            'zone_category' => ZoneCategory::Operasional,
            'focus_5r' => 'Ringkas, Rapi',
            'default_pic_id' => null,
            'capacity' => null,
            'facilities' => null,
            'requires_approval' => false,
            'is_active' => true,
        ];
    }

    public function kolaborasi(int $capacity = 10, bool $requiresApproval = false): static
    {
        return $this->state(fn (array $attributes) => [
            'zone_category' => ZoneCategory::Kolaborasi,
            'focus_5r' => 'Rapi, Rawat',
            'capacity' => $capacity,
            'requires_approval' => $requiresApproval,
        ]);
    }
}
