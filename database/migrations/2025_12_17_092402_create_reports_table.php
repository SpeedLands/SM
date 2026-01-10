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
        Schema::create('reports', function (Blueprint $table) {
            $table->uuid('id');
            $table->foreignId('cycle_id'); // Part of PK

            $table->string('student_id', 50)->index();
            // Adding explicit FK to students even if SQL dump missed it in ALTER block, it's safer.
            $table->foreign('student_id')->references('id')->on('students');

            $table->string('teacher_id', 50)->index();
            $table->foreign('teacher_id')->references('id')->on('users');

            $table->foreignId('infraction_id')->constrained('infractions');

            $table->string('subject', 100)->nullable();
            $table->text('description')->nullable();
            $table->dateTime('date');
            $table->enum('status', ['PENDING_SIGNATURE', 'SIGNED'])->default('PENDING_SIGNATURE');
            $table->dateTime('signed_at')->nullable();
            $table->string('signed_by_parent_id', 50)->nullable();

            $table->primary(['id', 'cycle_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
