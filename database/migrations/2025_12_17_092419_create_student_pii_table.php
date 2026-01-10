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
        Schema::create('student_pii', function (Blueprint $table) {
            $table->string('student_id', 50)->primary();
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');

            // Use binary for encrypted fields (varbinary equivalent)
            $table->binary('address_encrypted')->nullable(); // varbinary(512)
            $table->binary('contact_phone_encrypted')->nullable(); // varbinary(256)
            $table->binary('allergies_encrypted')->nullable(); // varbinary(1024)
            $table->binary('medical_conditions_encrypted')->nullable(); // varbinary(1024)
            $table->binary('emergency_contact_encrypted')->nullable(); // varbinary(512)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_pii');
    }
};
