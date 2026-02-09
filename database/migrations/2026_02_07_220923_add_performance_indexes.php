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
        Schema::table('notices', function (Blueprint $table) {
            $table->index(['cycle_id', 'target_audience', 'date'], 'idx_notices_filtering');
            $table->index(['cycle_id', 'type', 'date'], 'idx_notices_type_filter');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->index(['cycle_id', 'status', 'date'], 'idx_reports_filtering');
            $table->index(['student_id', 'cycle_id'], 'idx_reports_student_cycle');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->index(['grade', 'turn'], 'idx_students_grade_turn');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index(['role', 'status'], 'idx_users_role_status');
            $table->index('fcm_token', 'idx_users_fcm_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->dropIndex('idx_notices_filtering');
            $table->dropIndex('idx_notices_type_filter');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex('idx_reports_filtering');
            $table->dropIndex('idx_reports_student_cycle');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('idx_students_grade_turn');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_role_status');
            $table->dropIndex('idx_users_fcm_token');
        });
    }
};
