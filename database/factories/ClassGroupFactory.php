<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClassGroup>
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
            'section' => $this->faker->randomElement(['A', 'B', 'C', 'D']),
            'cycle_id' => \App\Models\Cycle::factory(),
        ];
    }
}
