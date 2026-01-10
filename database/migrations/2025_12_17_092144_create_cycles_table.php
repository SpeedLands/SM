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
        Schema::create('cycles', function (Blueprint $table) {
            $table->id(); // bigint(20) unsigned AUTO_INCREMENT
            $table->string('name', 50);
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(false);
            // created_at / updated_at not strictly in SQL but good practice in Laravel,
            // but strict SQL adherence means maybe not? SQL didn't have them.
            // I'll add them as they are standard Laravel. If user strictly wants SQL clone I can remove.
            // The SQL DID NOT have timestamps for cycles. I will OMIT timestamps to match SQL strictly as requested.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cycles');
    }
};
