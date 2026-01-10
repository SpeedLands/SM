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
        Schema::create('student_cycle_association', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('student_id', 50);
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');

            $table->foreignId('cycle_id')->constrained('cycles');

            $table->char('class_group_id', 36);
            $table->foreign('class_group_id')->references('id')->on('class_groups');

            $table->enum('status', ['ACTIVE', 'DROPPED', 'GRADUATED'])->default('ACTIVE');

            $table->unique(['student_id', 'cycle_id'], 'uk_student_cycle');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_cycle_association');
    }
};
