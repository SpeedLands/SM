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
        Schema::table('reports', function (Blueprint $table) {
            $table->index(['cycle_id', 'status'], 'idx_reports_cycle_status');
        });

        Schema::table('community_services', function (Blueprint $table) {
            $table->index(['student_id', 'cycle_id'], 'idx_cs_student_cycle');
            $table->index(['cycle_id', 'status'], 'idx_cs_cycle_status');
        });

        Schema::table('citations', function (Blueprint $table) {
            $table->index(['student_id', 'cycle_id'], 'idx_citations_student_cycle');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex('idx_reports_cycle_status');
        });

        Schema::table('community_services', function (Blueprint $table) {
            $table->dropIndex('idx_cs_student_cycle');
            $table->dropIndex('idx_cs_cycle_status');
        });

        Schema::table('citations', function (Blueprint $table) {
            $table->dropIndex('idx_citations_student_cycle');
        });
    }
};
