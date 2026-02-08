<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    protected string $projectId;

    protected array $serviceAccount;

    public function __construct()
    {
        $path = base_path('educom-24ee8-firebase-adminsdk-fbsvc-9fe7a29913.json');
        if (! file_exists($path)) {
            throw new \Exception('Firebase service account file not found.');
        }
        $this->serviceAccount = json_decode(file_get_contents($path), true);
        $this->projectId = $this->serviceAccount['project_id'];
    }

    /**
     * Send a push notification via FCM API v1.
     */
    public function sendNotification(string $deviceToken, string $title, string $body, array $data = [], ?string $icon = null, ?string $image = null, ?string $url = null): bool
    {
        $accessToken = $this->getAccessToken();
        if (! $accessToken) {
            Log::error('Failed to generate FCM access token.');

            return false;
        }

        $fcmUrl = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        $message = [
            'token' => $deviceToken,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'webpush' => [
                'notification' => [
                    'icon' => $icon ?? '/apple-touch-icon.png',
                ],
                'fcm_options' => [
                    'link' => $url ?? '/',
                ],
            ],
        ];

        if ($image) {
            $message['notification']['image'] = $image;
            $message['webpush']['notification']['image'] = $image;
        }

        // FCM v1 requires 'data' to be a map of strings.
        if (! empty($data) || $url) {
            $message['data'] = array_map('strval', array_merge($data, ['url' => $url ?? '/']));
        }

        $response = Http::withToken($accessToken)->post($fcmUrl, ['message' => $message]);

        if ($response->failed()) {
            Log::error('FCM Send Error: '.$response->body(), [
                'token' => $deviceToken,
                'payload' => $message,
            ]);

            return false;
        }

        Log::info('FCM Notification Sent Successfully', ['token' => $deviceToken]);

        return true;
    }

    /**
     * Generate OAuth2 Access Token for Firebase API v1 using JWT.
     */
    protected function getAccessToken(): ?string
    {
        $now = time();
        $expiry = $now + 3600;

        $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
        $payload = json_encode([
            'iss' => $this->serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/cloud-platform',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $expiry,
            'iat' => $now,
        ]);

        $base64UrlHeader = $this->base64UrlEncode($header);
        $base64UrlPayload = $this->base64UrlEncode($payload);

        $signature = '';
        $privateKey = $this->serviceAccount['private_key'];

        if (! openssl_sign($base64UrlHeader.'.'.$base64UrlPayload, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            return null;
        }

        $base64UrlSignature = $this->base64UrlEncode($signature);
        $jwt = $base64UrlHeader.'.'.$base64UrlPayload.'.'.$base64UrlSignature;

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if ($response->failed()) {
            Log::error('OAuth2 Token Generation Failed: '.$response->body());

            return null;
        }

        return $response->json('access_token');
    }

    protected function base64UrlEncode(string $data): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }
}
