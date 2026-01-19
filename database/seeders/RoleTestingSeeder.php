<?php

namespace Database\Seeders;

use App\Models\Cycle;
use App\Models\Infraction;
use App\Models\Report;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoleTestingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create active cycle
        $cycle = Cycle::where('is_active', true)->first();
        if (! $cycle) {
            $cycle = Cycle::create([
                'name' => '2024-2025',
                'start_date' => '2024-08-26',
                'end_date' => '2025-07-16',
                'is_active' => true,
            ]);
        }

        // 1. ADMIN User
        User::updateOrCreate(
            ['email' => 'admin.test@escuela.edu.mx'],
            [
                'name' => 'Admin de Pruebas',
                'password' => Hash::make('password'),
                'role' => 'ADMIN',
                'status' => 'ACTIVE',
                'email_verified_at' => now(),
            ]
        );

        // 2. TEACHER User
        User::updateOrCreate(
            ['email' => 'teacher.test@escuela.edu.mx'],
            [
                'name' => 'Maestro de Pruebas',
                'password' => Hash::make('password'),
                'role' => 'TEACHER',
                'status' => 'ACTIVE',
                'email_verified_at' => now(),
            ]
        );

        // 3. PARENT & STUDENT
        $parent = User::updateOrCreate(
            ['email' => 'parent.test@escuela.edu.mx'],
            [
                'name' => 'Padre de Pruebas',
                'password' => Hash::make('password'),
                'role' => 'PARENT',
                'status' => 'ACTIVE',
                'email_verified_at' => now(),
                'phone' => '1234567890',
                'occupation' => 'Tester',
            ]
        );

        $student = Student::firstOrCreate(
            ['name' => 'ALUMNO DE PRUEBAS'],
            [
                'birth_date' => '2010-01-01',
                'grade' => '1º',
                'group_name' => 'A',
                'turn' => 'MATUTINO',
            ]
        );

        // Associate Parent and Student
        if (! $parent->students()->where('student_id', $student->id)->exists()) {
            $parent->students()->attach($student->id, ['relationship' => 'PADRE']);
        }

        // 4. Create Mock Report for testing
        $infraction = Infraction::first() ?? Infraction::create([
            'description' => 'Incumplimiento de tareas',
            'severity' => 'NORMAL',
        ]);

        Report::create([
            'student_id' => $student->id,
            'cycle_id' => $cycle->id,
            'teacher_id' => User::where('role', 'TEACHER')->first()->id,
            'infraction_id' => $infraction->id,
            'date' => now(),
            'status' => 'PENDING_SIGNATURE',
        ]);
    }
}
