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
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('user_id', 36);
            $table->enum('type', ['fcm', 'webpush']);
            $table->text('endpoint');
            $table->string('fcm_token')->nullable();
            $table->text('p256dh_key')->nullable();
            $table->text('auth_key')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('user_id');
        });

        // Migrate existing fcm_token data from users table
        $users = \Illuminate\Support\Facades\DB::table('users')
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->get(['id', 'fcm_token']);

        foreach ($users as $user) {
            \Illuminate\Support\Facades\DB::table('push_subscriptions')->insert([
                'user_id' => $user->id,
                'type' => 'fcm',
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/' . $user->fcm_token,
                'fcm_token' => $user->fcm_token,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Remove old fcm_token column from users
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasIndex('users', 'idx_users_fcm_token')) {
                $table->dropIndex('idx_users_fcm_token');
            }
            $table->dropColumn('fcm_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('fcm_token')->nullable()->after('password');
            $table->index('fcm_token', 'idx_users_fcm_token');
        });

        // Restore fcm_tokens back to users table
        $subscriptions = \Illuminate\Support\Facades\DB::table('push_subscriptions')
            ->where('type', 'fcm')
            ->whereNotNull('fcm_token')
            ->get(['user_id', 'fcm_token']);

        foreach ($subscriptions as $sub) {
            \Illuminate\Support\Facades\DB::table('users')
                ->where('id', $sub->user_id)
                ->update(['fcm_token' => $sub->fcm_token]);
        }

        Schema::dropIfExists('push_subscriptions');
    }
};
