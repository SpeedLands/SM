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
        Schema::table('students', function (Blueprint $table) {
            $table->index('curp', 'idx_students_curp');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->index(['status', 'date'], 'idx_attendances_status_date');
        });

        Schema::table('student_cycle_association', function (Blueprint $table) {
            $table->index(['cycle_id', 'class_group_id', 'status'], 'idx_sca_cycle_group_status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('phone', 'idx_users_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('idx_students_curp');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('idx_attendances_status_date');
        });

        Schema::table('student_cycle_association', function (Blueprint $table) {
            $table->dropIndex('idx_sca_cycle_group_status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_phone');
        });
    }
};
