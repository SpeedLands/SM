<?php

namespace Database\Factories;

use App\Models\Cycle;
use App\Models\Infraction;
use App\Models\Report;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
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
            'student_id' => Student::factory(),
            'cycle_id' => Cycle::factory(),
            'teacher_id' => User::factory(),
            'infraction_id' => Infraction::factory(),
            'date' => now(),
            'status' => 'PENDING_SIGNATURE',
        ];
    }
}
