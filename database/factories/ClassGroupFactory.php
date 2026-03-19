<?php

namespace Database\Factories;

use App\Models\ClassGroup;
use App\Models\Cycle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassGroup>
 */
class ClassGroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'grade' => $this->faker->randomElement(['1º', '2º', '3º']),
            'section' => $this->faker->randomElement(['A', 'B', 'C', 'D', 'G', 'H', 'I']),
            'cycle_id' => Cycle::factory(),
        ];
    }
}
