<?php

use App\Models\User;
use App\Services\FcmService;
use App\Jobs\SendBulkFcmNotifications;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a service account file for testing if it doesn't exist
    $path = base_path('educom-24ee8-firebase-adminsdk-fbsvc-9fe7a29913.json');
    if (!file_exists($path)) {
        file_put_contents($path, json_encode([
            'project_id' => 'test-project',
            'client_email' => 'test@test.com',
            'private_key' => "-----BEGIN PRIVATE KEY-----\nMIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQC7\n-----END PRIVATE KEY-----\n",
        ]));
    }
});

test('fcm service invalidates token on UNREGISTERED error', function () {
    $user = User::factory()->create(['fcm_token' => 'invalid-token']);
    
    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'fake-token']),
        'https://fcm.googleapis.com/v1/projects/*/messages:send' => Http::response([
            'error' => [
                'status' => 'UNREGISTERED',
                'message' => 'Token is expired or invalid'
            ]
        ], 404),
    ]);

    $service = new FcmService();
    $result = $service->sendNotification('invalid-token', 'Title', 'Body');

    expect($result)->toBeFalse();
    expect($user->fresh()->fcm_token)->toBeNull();
});

test('fcm service invalidates token on INVALID_ARGUMENT error', function () {
    $user = User::factory()->create(['fcm_token' => 'malformed-token']);
    
    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'fake-token']),
        'https://fcm.googleapis.com/v1/projects/*/messages:send' => Http::response([
            'error' => [
                'status' => 'INVALID_ARGUMENT',
                'message' => 'Token is malformed'
            ]
        ], 400),
    ]);

    $service = new FcmService();
    $result = $service->sendNotification('malformed-token', 'Title', 'Body');

    expect($result)->toBeFalse();
    expect($user->fresh()->fcm_token)->toBeNull();
});

test('fcm service caches access token', function () {
    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'cached-token']),
    ]);

    $service = new FcmService();
    
    // Call protected method via reflection or just call sendNotification
    // We'll use a helper to call protected method
    $reflection = new \ReflectionClass($service);
    $method = $reflection->getMethod('getAccessToken');
    $method->setAccessible(true);
    
    $token = $method->invoke($service);
    
    expect($token)->toBe('cached-token');
    expect(Cache::has('fcm_access_token'))->toBeTrue();
    expect(Cache::get('fcm_access_token'))->toBe('cached-token');
});

test('send bulk fcm notifications job processes users and calls fcm service', function () {
    $users = User::factory()->count(5)->create(['fcm_token' => 'token-test']);
    $userIds = $users->pluck('id')->toArray();
    
    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'fake-token']),
        'https://fcm.googleapis.com/v1/projects/*/messages:send' => Http::response(['name' => 'messages/123']),
    ]);

    $job = new SendBulkFcmNotifications($userIds, 'Bulk Title', 'Bulk Body');
    $job->handle(new FcmService());

    Http::assertSentCount(6); // 1 for OAuth + 5 for notifications
});
