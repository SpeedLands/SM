<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FcmController extends Controller
{
    /**
     * Store the FCM token for the authenticated user.
     */
    public function storeToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $user = Auth::user();
        if ($user) {
            $user->update(['fcm_token' => $request->token]);

            return response()->json(['message' => 'Token stored successfully.']);
        }

        return response()->json(['message' => 'User not authenticated.'], 401);
    }
}
