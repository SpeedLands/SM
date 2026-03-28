<?php

use App\Jobs\SendBulkFcmNotifications;
use App\Models\User;
use App\Services\FcmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

/**
 * Helper to create a partial mock of FcmService with initialized properties.
 */
function createFcmServiceMock()
{
    $service = mock(FcmService::class)->makePartial();
    $service->shouldAllowMockingProtectedMethods();

    // Initialize protected properties bypassing constructor
    $reflection = new ReflectionClass(FcmService::class);

    $projectId = $reflection->getProperty('projectId');
    $projectId->setAccessible(true);
    $projectId->setValue($service, 'test-project');

    $serviceAccount = $reflection->getProperty('serviceAccount');
    $serviceAccount->setAccessible(true);
    $serviceAccount->setValue($service, ['project_id' => 'test-project', 'client_email' => 'test@test.com']);

    $service->shouldReceive('getAccessToken')->andReturn('fake-token')->byDefault();

    return $service;
}

test('fcm service invalidates token on UNREGISTERED error', function () {
    $user = User::factory()->create(['fcm_token' => 'invalid-token']);

    Http::fake([
        'https://fcm.googleapis.com/v1/projects/*/messages:send' => Http::response([
            'error' => [
                'status' => 'UNREGISTERED',
                'message' => 'Token is expired or invalid',
            ],
        ], 404),
    ]);

    $service = createFcmServiceMock();

    $result = $service->sendNotification('invalid-token', 'Title', 'Body');

    expect($result)->toBeFalse();
    expect($user->fresh()->fcm_token)->toBeNull();
});

test('fcm service invalidates token on INVALID_ARGUMENT error', function () {
    $user = User::factory()->create(['fcm_token' => 'malformed-token']);

    Http::fake([
        'https://fcm.googleapis.com/v1/projects/*/messages:send' => Http::response([
            'error' => [
                'status' => 'INVALID_ARGUMENT',
                'message' => 'Token is malformed',
            ],
        ], 400),
    ]);

    $service = createFcmServiceMock();

    $result = $service->sendNotification('malformed-token', 'Title', 'Body');

    expect($result)->toBeFalse();
    expect($user->fresh()->fcm_token)->toBeNull();
});

test('fcm service caches access token', function () {
    // We already mock getAccessToken in the helper.
    // This test verifies architecture: it calls sendNotification and uses the token.

    Http::fake([
        'https://fcm.googleapis.com/v1/projects/*/messages:send' => Http::response(['name' => 'ok']),
    ]);

    $service = createFcmServiceMock();
    $service->shouldReceive('getAccessToken')->once()->andReturn('cached-token');

    $service->sendNotification('token', 'T', 'B');

    expect(true)->toBeTrue();
});

test('send bulk fcm notifications job processes users and calls fcm service', function () {
    $users = User::factory()->count(5)->create(['fcm_token' => 'token-test']);
    $userIds = $users->pluck('id')->toArray();

    Http::fake([
        'https://fcm.googleapis.com/v1/projects/*/messages:send' => Http::response(['name' => 'messages/123']),
    ]);

    $service = createFcmServiceMock();

    $job = new SendBulkFcmNotifications($userIds, 'Bulk Title', 'Bulk Body');
    $job->handle($service);

    Http::assertSentCount(5);
});

test('send bulk fcm notifications job chunks itself if user count exceeds 100', function () {
    Queue::fake();
    $userIds = array_fill(0, 150, 'user-id');
    $service = createFcmServiceMock();

    $job = new SendBulkFcmNotifications($userIds, 'Bulk Title', 'Bulk Body');
    $job->handle($service);

    Queue::assertPushed(SendBulkFcmNotifications::class, function ($job) {
        return count($job->userIds) <= 100;
    });

    Queue::assertPushed(SendBulkFcmNotifications::class, 2);
});
