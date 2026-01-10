<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'curp' => strtoupper($this->faker->bothify('????######??????##')),
            'name' => strtoupper($this->faker->name),
            'birth_date' => $this->faker->date(),
            'grade' => '1º',
            'group_name' => 'A',
            'turn' => 'MATUTINO',
        ];
    }
}
