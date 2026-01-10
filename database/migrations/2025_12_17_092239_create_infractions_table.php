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
        Schema::create('infractions', function (Blueprint $table) {
            $table->id(); // int(11) increment
            $table->string('description', 255);
            $table->enum('severity', ['NORMAL', 'GRAVE'])->default('NORMAL');
            $table->timestamp('created_at')->useCurrent();
            // SQL has no separate updated_at, but we can stick to SQL or add standard timestamps.
            // Following SQL strictly:
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('infractions');
    }
};
