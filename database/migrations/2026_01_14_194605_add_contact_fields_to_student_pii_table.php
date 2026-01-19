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
        Schema::table('student_pii', function (Blueprint $table) {
            $table->binary('mother_name_encrypted')->nullable();
            $table->binary('father_name_encrypted')->nullable();
            $table->binary('other_contact_encrypted')->nullable();
            $table->binary('mother_workplace_encrypted')->nullable();
            $table->binary('father_workplace_encrypted')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_pii', function (Blueprint $table) {
            $table->dropColumn([
                'mother_name_encrypted',
                'father_name_encrypted',
                'other_contact_encrypted',
                'mother_workplace_encrypted',
                'father_workplace_encrypted',
            ]);
        });
    }
};
