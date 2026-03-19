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
            $table->index('name', 'idx_students_name');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->index(['date', 'status'], 'idx_reports_date_status');
        });

        Schema::table('citations', function (Blueprint $table) {
            $table->index(['citation_date', 'status'], 'idx_citations_date_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('idx_students_name');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex('idx_reports_date_status');
        });

        Schema::table('citations', function (Blueprint $table) {
            $table->dropIndex('idx_citations_date_status');
        });
    }
};
