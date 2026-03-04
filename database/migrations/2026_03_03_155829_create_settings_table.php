<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Default attendance threshold values
        DB::table('settings')->insert([
            ['key' => 'attendance.matutino_entry_time', 'value' => '07:30', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'attendance.vespertino_entry_time', 'value' => '13:30', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'attendance.grace_minutes', 'value' => '10', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
