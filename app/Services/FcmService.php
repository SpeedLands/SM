<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    protected string $projectId;

    protected array $serviceAccount;

    public function __construct()
    {
        $path = config('services.firebase.credentials');
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
            'android' => [
                'priority' => 'high',
                'notification' => [
                    'sound' => 'default',
                    'channel_id' => 'default_channel',
                    'click_action' => $url ?? null,
                ],
            ],
            'apns' => [
                'headers' => [
                    'apns-priority' => '10',
                ],
                'payload' => [
                    'aps' => [
                        'sound' => 'default',
                        'content-available' => 1,
                    ],
                ],
            ],
            'webpush' => [
                'headers' => [
                    'Urgency' => 'high',
                ],
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'icon' => $icon ?? '/apple-touch-icon.png',
                    'click_action' => $url ?? '/',
                ],
                'fcm_options' => [
                    'link' => $url ?? '/',
                ],
            ],
        ];

        if ($image) {
            $message['notification']['image'] = $image;
            $message['android']['notification']['image'] = $image;
            $message['apns']['fcm_options']['image'] = $image;
            $message['webpush']['notification']['image'] = $image;
        }

        if (! empty($data) || $url) {
            $message['data'] = array_map('strval', array_merge($data, ['url' => $url ?? '/']));
        }

        $response = Http::withToken($accessToken)->post($fcmUrl, ['message' => $message]);

        if ($response->failed()) {
            $error = $response->json('error');
            $errorCode = $error['status'] ?? null;
            $errorMessage = $error['message'] ?? $response->body();

            Log::error('FCM Send Error: '.$errorMessage, [
                'token' => $deviceToken,
                'status' => $errorCode,
            ]);

            // Hallazgo #2: Invalidar token obsoleto
            // UNREGISTERED: The token is no longer valid.
            // INVALID_ARGUMENT: The token is malformed.
            if ($errorCode === 'UNREGISTERED' || $errorCode === 'INVALID_ARGUMENT') {
                Log::warning('FCM Token Invalidated: Clearing token for user.', ['token' => $deviceToken]);
                User::where('fcm_token', $deviceToken)->update(['fcm_token' => null]);
            }

            return false;
        }

        Log::info('FCM Notification Sent Successfully', ['token' => $deviceToken]);

        return true;
    }

    /**
     * Send a test push notification and return detailed error info if it fails.
     * This is useful for debugging on devices like iOS where console is not available.
     */
    public function sendTestNotification(string $deviceToken): array
    {
        $accessToken = $this->getAccessToken();
        if (! $accessToken) {
            return [
                'success' => false,
                'status' => 'UNAUTHENTICATED',
                'code' => 401,
                'message' => 'Failed to generate FCM access token from service account credentials.',
                'details' => []
            ];
        }

        $fcmUrl = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        $message = [
            'token' => $deviceToken,
            'notification' => [
                'title' => 'Prueba de Notificación',
                'body' => 'Si estás viendo esto, las notificaciones funcionan correctamente.',
            ],
            'webpush' => [
                'headers' => [
                    'Urgency' => 'high',
                ],
                'notification' => [
                    'title' => 'Prueba de Notificación',
                    'body' => 'Si estás viendo esto, las notificaciones funcionan correctamente en Web/PWA.',
                    'icon' => '/apple-touch-icon.png',
                    'click_action' => '/',
                ],
            ],
            'apns' => [
                'payload' => [
                    'aps' => [
                        'sound' => 'default',
                    ],
                ],
            ],
        ];

        $response = Http::withToken($accessToken)->post($fcmUrl, ['message' => $message]);

        if ($response->failed()) {
            $error = $response->json('error') ?? [];
            return [
                'success' => false,
                'status' => $error['status'] ?? 'UNKNOWN_ERROR',
                'code' => $error['code'] ?? $response->status(),
                'message' => $error['message'] ?? $response->body(),
                'details' => $error['details'] ?? [],
            ];
        }

        return [
            'success' => true,
        ];
    }

    /**
     * Generate OAuth2 Access Token for Firebase API v1 using JWT.
     * Hallazgo #1: Implementar Caché
     */
    protected function getAccessToken(): ?string
    {
        return cache()->remember('fcm_access_token', 3500, function () {
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
        });
    }

    protected function base64UrlEncode(string $data): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }
}
