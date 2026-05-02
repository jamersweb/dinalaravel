<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppIntegration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppIntegrationsController extends Controller
{
    /**
     * Store Fitbit OAuth tokens for the authenticated user.
     * Accepts JSON: access_token, refresh_token, user_id (matches mobile FitbitAuth.toJson()).
     * Legacy: { "data": "<json string>" } or { "data": { ... } }.
     */
    function setFitbitAuth(Request $request)
    {
        $payload = null;

        if ($request->has('access_token') || $request->has('refresh_token') || $request->has('user_id')) {
            $payload = [
                'access_token' => $request->input('access_token'),
                'refresh_token' => $request->input('refresh_token'),
                'user_id' => $request->input('user_id'),
            ];
        } elseif ($request->has('data')) {
            $data = $request->input('data');
            if (is_string($data)) {
                $decoded = json_decode($data, true);
                $payload = is_array($decoded) ? $decoded : null;
            } elseif (is_array($data)) {
                $payload = $data;
            }
        }

        if (!is_array($payload)
            || empty($payload['access_token'])
            || empty($payload['refresh_token'])
            || (!array_key_exists('user_id', $payload) || $payload['user_id'] === null || $payload['user_id'] === '')) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid Fitbit auth payload. Required: access_token, refresh_token, user_id.',
            ], 422);
        }

        $json = json_encode([
            'access_token' => (string) $payload['access_token'],
            'refresh_token' => (string) $payload['refresh_token'],
            'user_id' => (string) $payload['user_id'],
        ], JSON_UNESCAPED_SLASHES);

        $exist = AppIntegration::where('user_id', Auth::id())->first();
        if ($exist) {
            $exist->fitbit_auth = $json;
            $exist->save();
        } else {
            $integ = new AppIntegration();
            $integ->user_id = Auth::id();
            $integ->fitbit_auth = $json;
            $integ->save();
        }

        return response()->json([
            'status' => true,
            'message' => 'Fitbit auth saved.',
        ]);
    }

    /**
     * Return stored Fitbit credentials as a flat object (mobile FitbitAuth.fromJson expects root keys).
     */
    function getFitbitAuth()
    {
        $raw = AppIntegration::where('user_id', Auth::id())->value('fitbit_auth');

        if ($raw === null || $raw === '') {
            return response()->json([
                'access_token' => '',
                'refresh_token' => '',
                'user_id' => '',
            ]);
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return response()->json([
                'access_token' => '',
                'refresh_token' => '',
                'user_id' => '',
            ]);
        }

        return response()->json([
            'access_token' => isset($decoded['access_token']) ? (string) $decoded['access_token'] : '',
            'refresh_token' => isset($decoded['refresh_token']) ? (string) $decoded['refresh_token'] : '',
            'user_id' => isset($decoded['user_id']) ? (string) $decoded['user_id'] : '',
        ]);
    }
}
