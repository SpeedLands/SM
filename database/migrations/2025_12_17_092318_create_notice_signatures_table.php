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
        Schema::create('notice_signatures', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Notice ID is char(36), but notice PK is (id, cycle_id).
            // In notice_signatures, sm.sql only has notice_id, not cycle_id.
            // This implies no strict FK constraint to notices specific partition without the cycle_id,
            // OR the SQL didn't enforce it strictly or it was just an index.
            // SQL says: KEY `idx_notice` (`notice_id`) but NO CONSTRAINT defined in sm.sql for notice_signatures -> notices.
            // I will just add the column and index as per strict SQL.
            $table->char('notice_id', 36)->index();

            $table->string('parent_id', 50)->index();
            // $table->foreign('parent_id')->references('id')->on('users'); // Missing in SQL CREATE but likely distinct student_parents logic?
            // SQL: KEY `idx_parent` (`parent_id`)

            $table->string('student_id', 50);

            $table->dateTime('signed_at')->useCurrent();
            $table->boolean('authorized')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notice_signatures');
    }
};
