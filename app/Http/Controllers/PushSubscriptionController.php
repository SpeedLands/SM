<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PushSubscriptionController extends Controller
{
    /**
     * Store a push subscription (FCM or Web Push).
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'User not authenticated.'], 401);
        }

        $request->validate([
            'type' => 'required|in:fcm,webpush',
            // FCM fields
            'fcm_token' => 'required_if:type,fcm|string|nullable',
            // Web Push fields
            'endpoint' => 'required_if:type,webpush|string|nullable',
            'keys.p256dh' => 'required_if:type,webpush|string|nullable',
            'keys.auth' => 'required_if:type,webpush|string|nullable',
        ]);

        if ($request->type === 'fcm') {
            return $this->storeFcmSubscription($user, $request);
        }

        return $this->storeWebPushSubscription($user, $request);
    }

    /**
     * Store/update an FCM subscription.
     */
    private function storeFcmSubscription($user, Request $request): JsonResponse
    {
        $fcmToken = $request->fcm_token;
        $endpoint = 'https://fcm.googleapis.com/fcm/send/' . $fcmToken;

        // Remove any old FCM subscriptions for this user, then create/update
        PushSubscription::where('user_id', $user->id)
            ->where('type', 'fcm')
            ->where('fcm_token', '!=', $fcmToken)
            ->delete();

        PushSubscription::updateOrCreate(
            ['user_id' => $user->id, 'type' => 'fcm', 'fcm_token' => $fcmToken],
            [
                'endpoint' => $endpoint,
                'user_agent' => $request->userAgent(),
            ]
        );

        return response()->json(['message' => 'FCM subscription stored successfully.']);
    }

    /**
     * Store/update a Web Push subscription.
     */
    private function storeWebPushSubscription($user, Request $request): JsonResponse
    {
        PushSubscription::updateOrCreate(
            ['endpoint' => $request->endpoint],
            [
                'user_id' => $user->id,
                'type' => 'webpush',
                'p256dh_key' => $request->input('keys.p256dh'),
                'auth_key' => $request->input('keys.auth'),
                'user_agent' => $request->userAgent(),
            ]
        );

        return response()->json(['message' => 'Web Push subscription stored successfully.']);
    }

    /**
     * Remove a push subscription.
     */
    public function destroy(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'User not authenticated.'], 401);
        }

        $request->validate([
            'endpoint' => 'required|string',
        ]);

        PushSubscription::where('user_id', $user->id)
            ->where('endpoint', $request->endpoint)
            ->delete();

        return response()->json(['message' => 'Subscription removed successfully.']);
    }

    /**
     * Legacy endpoint — redirects FCM token storage to new system.
     */
    public function storeLegacyToken(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'User not authenticated.'], 401);
        }

        $fcmToken = $request->token;
        $endpoint = 'https://fcm.googleapis.com/fcm/send/' . $fcmToken;

        // Remove any old FCM subscriptions for this user, then create/update
        PushSubscription::where('user_id', $user->id)
            ->where('type', 'fcm')
            ->where('fcm_token', '!=', $fcmToken)
            ->delete();

        PushSubscription::updateOrCreate(
            ['user_id' => $user->id, 'type' => 'fcm', 'fcm_token' => $fcmToken],
            [
                'endpoint' => $endpoint,
                'user_agent' => $request->userAgent(),
            ]
        );

        return response()->json(['message' => 'Token stored successfully.']);
    }
}
