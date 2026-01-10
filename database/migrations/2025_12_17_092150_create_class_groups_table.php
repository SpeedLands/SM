<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('class_groups', function (Blueprint $table) {
            $table->uuid('id')->primary(); // char(36)
            $table->foreignId('cycle_id')->constrained('cycles'); // bigint(20) matches cycles.id
            $table->string('grade', 10);
            $table->string('section', 10);
            $table->string('tutor_teacher_id', 50)->nullable()->index();
            // Manual FK definition because referenced col is string
            $table->foreign('tutor_teacher_id')->references('id')->on('users');

            $table->unique(['cycle_id', 'grade', 'section'], 'uk_group');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_groups');
    }
};
