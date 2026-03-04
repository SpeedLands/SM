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
            $table->string('target_student_id', 50)->nullable()->after('target_audience');
            $table->date('end_date')->nullable()->after('event_date');

            // Raw SQL to update enum (Only for MySQL/MariaDB)
            if (DB::getDriverName() !== 'sqlite') {
                DB::statement("ALTER TABLE notices MODIFY COLUMN type ENUM('GENERAL', 'URGENT', 'EVENT', 'TRABAJO_EN_CASA') DEFAULT 'GENERAL'");
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->dropColumn(['target_student_id', 'end_date']);
            if (DB::getDriverName() !== 'sqlite') {
                DB::statement("ALTER TABLE notices MODIFY COLUMN type ENUM('GENERAL', 'URGENT', 'EVENT') DEFAULT 'GENERAL'");
            }
        });
    }
};
