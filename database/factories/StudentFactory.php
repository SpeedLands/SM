<?php

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
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
            'name' => strtoupper($this->faker->name),
            'birth_date' => $this->faker->date(),
            'grade' => '1º',
            'group_name' => 'A',
            'turn' => 'MATUTINO',
        ];
    }
}
