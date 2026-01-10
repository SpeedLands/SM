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
        Schema::create('students', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('curp', 18)->unique();
            $table->string('name', 100);
            $table->date('birth_date');
            $table->string('grade', 10);
            $table->string('group_name', 10); // Note: SQL calls it group_name, distinct from class_groups table logic?
            $table->enum('turn', ['MATUTINO', 'VESPERTINO']);
            $table->integer('siblings_count')->default(0);
            $table->integer('birth_order')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
