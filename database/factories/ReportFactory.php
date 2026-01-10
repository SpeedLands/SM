<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Report>
 */
class ReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => \App\Models\Student::factory(),
            'cycle_id' => \App\Models\Cycle::factory(),
            'teacher_id' => \App\Models\User::factory(),
            'infraction_id' => \App\Models\Infraction::factory(),
            'date' => now(),
            'status' => 'PENDING_SIGNATURE',
        ];
    }
}
