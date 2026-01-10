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
        Schema::create('community_services', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->foreignId('cycle_id')->constrained('cycles');

            $table->string('student_id', 50)->index();
            $table->foreign('student_id')->references('id')->on('students');

            $table->string('assigned_by_id', 50)->index();
            $table->foreign('assigned_by_id')->references('id')->on('users');

            $table->string('activity', 255);
            $table->text('description')->nullable();
            $table->date('scheduled_date');
            $table->enum('status', ['PENDING', 'COMPLETED', 'MISSED'])->default('PENDING');
            $table->boolean('parent_signature')->default(false);
            $table->dateTime('parent_signed_at')->nullable();

            $table->string('authority_signature_id', 50)->nullable()->index();
            $table->foreign('authority_signature_id')->references('id')->on('users');

            $table->dateTime('completed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('community_services');
    }
};
