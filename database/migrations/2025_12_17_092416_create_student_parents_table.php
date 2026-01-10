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
        Schema::create('student_parents', function (Blueprint $table) {
            $table->string('student_id', 50);
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');

            $table->string('parent_id', 50)->index();
            $table->foreign('parent_id')->references('id')->on('users')->onDelete('cascade');

            $table->enum('relationship', ['PADRE', 'MADRE', 'TUTOR'])->default('TUTOR');

            $table->primary(['student_id', 'parent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_parents');
    }
};
