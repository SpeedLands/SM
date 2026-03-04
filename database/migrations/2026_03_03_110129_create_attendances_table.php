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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->string('student_id', 50);
            $table->date('date');
            $table->time('entry_time')->nullable();
            $table->enum('status', ['PRESENTE', 'FALTA', 'RETARDO', 'TRABAJO_EN_CASA', 'JUSTIFICADO'])->default('FALTA');
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->unique(['student_id', 'date']);
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
