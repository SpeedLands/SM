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
        Schema::table('notice_signatures', function (Blueprint $table) {
            // Ensure only one signature per (notice_id, student_id)
            $table->unique(['notice_id', 'student_id'], 'uk_notice_student_signature');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notice_signatures', function (Blueprint $table) {
            $table->dropUnique('uk_notice_student_signature');
        });
    }
};
