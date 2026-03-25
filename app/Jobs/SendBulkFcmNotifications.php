<?php

namespace App\Jobs;

use App\Models\PushSubscription;
use App\Models\User;
use App\Services\FcmService;
use App\Services\WebPushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendBulkFcmNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public array $backoff = [60, 300, 600];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $userIds,
        public string $title,
        public string $body,
        public array $data = [],
        public ?string $url = null
    ) {}

    /**
     * Split a large list of user IDs into smaller jobs to avoid timeouts.
     */
    public static function dispatchInChunks(array $userIds, string $title, string $body, array $data = [], ?string $url = null, int $chunkSize = 50): void
    {
        if (empty($userIds)) {
            return;
        }

        $chunks = array_chunk($userIds, $chunkSize);

        Log::info('Dispatching push notifications in chunks', [
            'total_users' => count($userIds),
            'chunks' => count($chunks),
            'chunk_size' => $chunkSize,
        ]);

        foreach ($chunks as $index => $chunk) {
            static::dispatch($chunk, $title, $body, $data, $url)
                ->delay(now()->addSeconds($index * 5));
        }
    }

    /**
     * Execute the job.
     */
    public function handle(FcmService $fcmService, WebPushService $webPushService): void
    {
        Log::info('Processing push notification chunk', ['users_in_chunk' => count($this->userIds)]);

        // Send FCM notifications
        PushSubscription::whereIn('user_id', $this->userIds)
            ->where('type', 'fcm')
            ->whereNotNull('fcm_token')
            ->chunkById(100, function ($subscriptions) use ($fcmService) {
                foreach ($subscriptions as $subscription) {
                    try {
                        $fcmService->sendNotification(
                            $subscription->fcm_token,
                            $this->title,
                            $this->body,
                            $this->data,
                            null,
                            null,
                            $this->url
                        );

                        usleep(50000); // 50ms rate-limit delay
                    } catch (\Exception $e) {
                        Log::error('Individual FCM Sending Failed', [
                            'user_id' => $subscription->user_id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        // Send Web Push notifications (iOS Safari, Firefox, etc.)
        $webPushBatch = [];
        PushSubscription::whereIn('user_id', $this->userIds)
            ->where('type', 'webpush')
            ->whereNotNull('p256dh_key')
            ->whereNotNull('auth_key')
            ->chunk(100, function ($subscriptions) use (&$webPushBatch) {
                foreach ($subscriptions as $subscription) {
                    $webPushBatch[] = [
                        'subscription' => $subscription,
                        'title' => $this->title,
                        'body' => $this->body,
                        'url' => $this->url,
                        'data' => $this->data,
                    ];
                }
            });

        if (! empty($webPushBatch)) {
            $results = $webPushService->sendBatch($webPushBatch);
            Log::info('Web Push Batch Results', $results);
        }

        Log::info('Push notification chunk completed', ['users_in_chunk' => count($this->userIds)]);
    }
}
