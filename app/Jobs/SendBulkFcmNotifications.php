<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\FcmService;
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
    public function handle(FcmService $fcmService): void
    {
        Log::info('Starting Bulk FCM Sending', ['count' => count($this->userIds)]);

        User::whereIn('id', $this->userIds)
            ->whereNotNull('fcm_token')
            ->chunkById(100, function ($users) use ($fcmService) {
                foreach ($users as $user) {
                    try {
                        $fcmService->sendNotification(
                            $user->fcm_token,
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

        Log::info('Bulk FCM Sending Completed');
    }
}
