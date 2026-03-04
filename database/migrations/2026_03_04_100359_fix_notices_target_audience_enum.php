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
            if (DB::getDriverName() !== 'sqlite') {
                DB::statement("ALTER TABLE notices MODIFY COLUMN target_audience ENUM('ALL', 'TEACHERS', 'PARENTS', 'STUDENT') DEFAULT 'ALL'");
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                DB::statement("ALTER TABLE notices MODIFY COLUMN target_audience ENUM('ALL', 'TEACHERS', 'PARENTS') DEFAULT 'ALL'");
            }
        });
    }
};
