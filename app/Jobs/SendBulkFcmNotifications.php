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
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [60, 300, 600];

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
     * Execute the job.
     */
    public function handle(FcmService $fcmService, WebPushService $webPushService): void
    {
        Log::info('Starting Bulk Push Sending', ['count' => count($this->userIds)]);

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

                        // Small delay to prevent hitting rate limits too fast
                        usleep(50000); // 50ms
                    } catch (\Exception $e) {
                        Log::error('Individual FCM Sending Failed', [
                            'user_id' => $user->id,
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
            ->chunk(100, function ($subscriptions) use ($webPushService, &$webPushBatch) {
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

        Log::info('Bulk Push Sending Completed');
    }
}
