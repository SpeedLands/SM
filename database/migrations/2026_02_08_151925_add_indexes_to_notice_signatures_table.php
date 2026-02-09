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
            $table->index('student_id');
            // Composite index for the unique combination used in updateOrCreate
            $table->index(['notice_id', 'student_id', 'parent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notice_signatures', function (Blueprint $table) {
            $table->dropIndex(['student_id']);
            $table->dropIndex(['notice_id', 'student_id', 'parent_id']);
        });
    }
};
