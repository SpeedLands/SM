<?php

namespace Database\Factories;

use App\Models\Cycle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cycle>
 */
class CycleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->year.'-'.($this->faker->year + 1),
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_active' => false,
        ];
    }
}
