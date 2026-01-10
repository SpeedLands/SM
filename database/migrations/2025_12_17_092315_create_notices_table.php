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
        Schema::create('notices', function (Blueprint $table) {
            $table->uuid('id'); // char(36)
            $table->foreignId('cycle_id'); // Part of PK
            $table->string('author_id', 50)->index();
            $table->foreign('author_id')->references('id')->on('users');

            $table->string('title', 200);
            $table->text('content');
            $table->enum('type', ['GENERAL', 'URGENT', 'EVENT'])->default('GENERAL');
            $table->enum('target_audience', ['ALL', 'TEACHERS', 'PARENTS'])->default('ALL');
            $table->boolean('requires_authorization')->default(false);
            $table->date('event_date')->nullable();
            $table->time('event_time')->nullable();
            $table->dateTime('date')->useCurrent();

            $table->primary(['id', 'cycle_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notices');
    }
};
