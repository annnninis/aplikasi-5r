<?php

namespace Database\Factories;

use App\Enums\FindingPriority;
use App\Enums\FindingStatus;
use App\Models\Area;
use App\Models\Finding;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Finding>
 */
class FindingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'source' => 'quick_report',
            'area_id' => Area::factory(),
            'reported_by' => User::factory(),
            'description' => fake()->sentence(8),
            'priority' => FindingPriority::Sedang,
            'status' => FindingStatus::BelumDitugaskan,
        ];
    }

    public function assignedTo(User $pic): static
    {
        return $this->state(fn (array $attributes) => [
            'pic_id' => $pic->id,
            'status' => FindingStatus::BelumSelesai,
            'assigned_at' => now(),
            'deadline' => now()->addDays(7)->toDateString(),
        ]);
    }
}
