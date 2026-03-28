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
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 180;

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
        $totalCount = count($this->userIds);

        // If we have more than 100 users, split into smaller independent jobs
        // to avoid exceeding the worker timeout (especially given the 50ms sleep delay).
        if ($totalCount > 100) {
            $chunks = array_chunk($this->userIds, 100);

            Log::info('Chunking Bulk FCM Sending', [
                'total_users' => $totalCount,
                'total_chunks' => count($chunks),
            ]);

            foreach ($chunks as $chunk) {
                static::dispatch(
                    $chunk,
                    $this->title,
                    $this->body,
                    $this->data,
                    $this->url
                );
            }

            return;
        }

        Log::info('Processing FCM Notification Chunk', ['count' => $totalCount]);

        // Send FCM notifications
        PushSubscription::whereIn('user_id', $this->userIds)
            ->where('type', 'fcm')
            ->whereNotNull('fcm_token')
            ->chunkById(50, function ($users) use ($fcmService) {
                foreach ($users as $user) {
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

                        // Small delay to prevent hitting FCM rate limits too fast (50ms)
                        usleep(50000);
                    } catch (\Exception $e) {
                        Log::error('Individual FCM Sending Failed', [
                            'user_id' => $subscription->user_id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        Log::info('FCM Notification Chunk Completed');
    }
}
