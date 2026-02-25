<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fix push_subscriptions table — drop old webpush package schema
     * and recreate with the correct columns.
     */
    public function up(): void
    {
        // If old webpush package table exists, drop it completely
        if (Schema::hasTable('push_subscriptions') && Schema::hasColumn('push_subscriptions', 'subscribable_type')) {
            Schema::dropIfExists('push_subscriptions');
        }

        // Recreate only if it doesn't exist (covers both fresh install and post-drop)
        if (! Schema::hasTable('push_subscriptions')) {
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
        }

        // Migrate existing fcm_token data from users table (if column still exists)
        if (Schema::hasColumn('users', 'fcm_token')) {
            $users = \Illuminate\Support\Facades\DB::table('users')
                ->whereNotNull('fcm_token')
                ->where('fcm_token', '!=', '')
                ->get(['id', 'fcm_token']);

            foreach ($users as $user) {
                \Illuminate\Support\Facades\DB::table('push_subscriptions')->updateOrInsert(
                    ['user_id' => $user->id, 'type' => 'fcm', 'fcm_token' => $user->fcm_token],
                    [
                        'endpoint' => 'https://fcm.googleapis.com/fcm/send/' . $user->fcm_token,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasIndex('users', 'idx_users_fcm_token')) {
                    $table->dropIndex('idx_users_fcm_token');
                }
                $table->dropColumn('fcm_token');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to reverse — previous migration handles rollback
    }
};
