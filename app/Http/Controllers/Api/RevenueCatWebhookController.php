<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessRevenueCatWebhookJob;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class RevenueCatWebhookController extends Controller
{
    /**
     * Handle incoming RevenueCat webhook.
     * Configure this URL in RevenueCat dashboard: Integrations > Webhooks.
     * Set REVENUECAT_WEBHOOK_AUTH in .env and the same value as Authorization header in RevenueCat.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request): Response
    {
        $authHeader = config('services.revenuecat.webhook_auth');
        if (empty($authHeader)) {
            if (config('app.env') === 'production') {
                Log::error('RevenueCat webhook: REVENUECAT_WEBHOOK_AUTH not set in production');
                return response('', 500);
            }
        } elseif ($request->header('Authorization') !== $authHeader) {
            Log::warning('RevenueCat webhook: Invalid or missing authorization');
            return response('', 401);
        }

        $payload = $request->all();
        if (empty($payload)) {
            Log::warning('RevenueCat webhook: Empty payload');
            return response('', 400);
        }

        $event = $payload['event'] ?? $payload;
        $appUserId = $event['original_app_user_id'] ?? $event['app_user_id'] ?? 'unknown';
        $eventType = $event['type'] ?? $event['event_type'] ?? 'unknown';

        // Debug: log key fields to verify what RevenueCat sends (check storage/logs/laravel.log)
        Log::info('RevenueCat webhook: Received', [
            'app_user_id' => $appUserId,
            'original_app_user_id' => $event['original_app_user_id'] ?? null,
            'aliases' => $event['aliases'] ?? [],
            'event_type' => $eventType,
            'environment' => $event['environment'] ?? null,
            'store' => $event['store'] ?? null,
        ]);
        if (config('services.revenuecat.webhook_debug')) {
            Log::info('RevenueCat webhook: Full payload', ['payload' => $payload]);
        }

        try {
            // Dispatch job (sync driver runs immediately; database/redis needs queue:work)
            ProcessRevenueCatWebhookJob::dispatch($payload);
        } catch (\Throwable $e) {
            Log::error('RevenueCat webhook: Job failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Return 200 so RevenueCat does not retry; error is logged
        }

        return response('', 200);
    }
}
