<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Student;
use App\Models\Regulation;
use App\Models\Infraction;
use App\Models\Cycle;
use App\Models\ClassGroup;
use App\Models\StudentParent;
use App\Models\StudentPii;
use Illuminate\Support\Facades\DB;

class ProductionDataSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/extracted_data.json');
        if (!file_exists($path)) {
            $this->command->error("Data file not found at: {$path}");
            return;
        }

        $data = json_decode(file_get_contents($path), true);

        // Disable foreign key checks for multiple DB engines
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        // Truncate tables (order matters or use disable checks)
        Infraction::truncate();
        Regulation::truncate();
        StudentParent::truncate();
        StudentPii::truncate();
        Student::truncate();
        User::truncate();
        ClassGroup::truncate();
        Cycle::truncate();

        // Re-insert data
        foreach ($data['cycles'] as $item) Cycle::create($item);
        foreach ($data['class_groups'] as $item) ClassGroup::create($item);
        foreach ($data['users'] as $item) User::create($item);
        foreach ($data['regulations'] as $item) Regulation::create($item);
        foreach ($data['infractions'] as $item) Infraction::create($item);
        foreach ($data['students'] as $item) Student::create($item);
        foreach ($data['student_piis'] as $item) StudentPii::create($item);
        foreach ($data['student_parents'] as $item) StudentParent::create($item);

        // Re-enable foreign key checks
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
        
        $this->command->info('Production data seeded successfully!');
    }
}