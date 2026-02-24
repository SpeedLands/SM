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
        // Clean up duplicates if any exist before adding the unique index
        \Illuminate\Support\Facades\DB::table('notice_signatures')
            ->whereIn('id', function ($query) {
                $query->select('id')
                    ->from('notice_signatures as ns1')
                    ->whereExists(function ($subquery) {
                        $subquery->select(\Illuminate\Support\Facades\DB::raw(1))
                            ->from('notice_signatures as ns2')
                            ->whereRaw('ns1.notice_id = ns2.notice_id')
                            ->whereRaw('ns1.student_id = ns2.student_id')
                            ->whereRaw('ns1.id > ns2.id');
                    });
            })
            ->delete();

        Schema::table('notice_signatures', function (Blueprint $table) {
            // Ensure only one signature per (notice_id, student_id)
            $table->unique(['notice_id', 'student_id'], 'uk_notice_student_signature');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notice_signatures', function (Blueprint $table) {
            $table->dropUnique('uk_notice_student_signature');
        });
    }
};
