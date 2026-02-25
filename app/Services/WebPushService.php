<?php

namespace App\Services;

use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    protected WebPush $webPush;

    public function __construct()
    {
        $auth = [
            'VAPID' => [
                'subject' => config('webpush.vapid.subject'),
                'publicKey' => config('webpush.vapid.public_key'),
                'privateKey' => config('webpush.vapid.private_key'),
            ],
        ];

        $this->webPush = new WebPush($auth);
        $this->webPush->setAutomaticPadding(false);
    }

    /**
     * Send a Web Push notification to a single subscription.
     */
    public function sendNotification(PushSubscription $pushSubscription, string $title, string $body, array $data = [], ?string $icon = null, ?string $url = null): bool
    {
        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'icon' => $icon ?? '/apple-touch-icon.png',
            'url' => $url ?? '/',
            'data' => $data,
        ]);

        $subscription = Subscription::create([
            'endpoint' => $pushSubscription->endpoint,
            'publicKey' => $pushSubscription->p256dh_key,
            'authToken' => $pushSubscription->auth_key,
        ]);

        try {
            $report = $this->webPush->sendOneNotification($subscription, $payload);

            if ($report->isSuccess()) {
                Log::info('Web Push Notification Sent Successfully', [
                    'endpoint' => $pushSubscription->endpoint,
                ]);

                return true;
            }

            $reason = $report->getReason();
            Log::error('Web Push Send Error: ' . $reason, [
                'endpoint' => $pushSubscription->endpoint,
                'expired' => $report->isSubscriptionExpired(),
            ]);

            // Clean up expired/invalid subscriptions
            if ($report->isSubscriptionExpired()) {
                Log::warning('Web Push Subscription Expired: removing.', [
                    'endpoint' => $pushSubscription->endpoint,
                ]);
                $pushSubscription->delete();
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Web Push Exception: ' . $e->getMessage(), [
                'endpoint' => $pushSubscription->endpoint,
            ]);

            return false;
        }
    }

    /**
     * Send notifications to multiple subscriptions efficiently (batched).
     */
    public function sendBatch(array $notifications): array
    {
        $results = ['success' => 0, 'failed' => 0, 'expired' => 0];

        foreach ($notifications as $item) {
            /** @var PushSubscription $pushSubscription */
            $pushSubscription = $item['subscription'];
            $payload = json_encode([
                'title' => $item['title'],
                'body' => $item['body'],
                'icon' => $item['icon'] ?? '/apple-touch-icon.png',
                'url' => $item['url'] ?? '/',
                'data' => $item['data'] ?? [],
            ]);

            $subscription = Subscription::create([
                'endpoint' => $pushSubscription->endpoint,
                'publicKey' => $pushSubscription->p256dh_key,
                'authToken' => $pushSubscription->auth_key,
            ]);

            $this->webPush->queueNotification($subscription, $payload);
        }

        foreach ($this->webPush->flush() as $report) {
            if ($report->isSuccess()) {
                $results['success']++;
            } else {
                $results['failed']++;
                if ($report->isSubscriptionExpired()) {
                    $results['expired']++;
                    // Find and delete expired subscription
                    PushSubscription::where('endpoint', $report->getEndpoint())->delete();
                }
            }
        }

        return $results;
    }
}
