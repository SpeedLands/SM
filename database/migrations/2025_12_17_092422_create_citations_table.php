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
        Schema::create('citations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('cycle_id')->constrained('cycles');

            $table->string('student_id', 50)->index();
            $table->foreign('student_id')->references('id')->on('students');

            $table->string('teacher_id', 50)->index();
            $table->foreign('teacher_id')->references('id')->on('users');

            $table->text('reason');
            $table->dateTime('citation_date');
            $table->enum('status', ['PENDING', 'ATTENDED', 'NO_SHOW'])->default('PENDING');
            $table->boolean('parent_signature')->default(false);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('citations');
    }
};
