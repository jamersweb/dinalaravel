<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\UserSub;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessRevenueCatWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var array Raw webhook payload */
    protected array $payload;

    /**
     * Create a new job instance.
     *
     * @param  array  $payload  RevenueCat webhook JSON payload
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->processEvent();
        } catch (\Throwable $e) {
            Log::error('RevenueCat webhook: Processing failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Do not rethrow - controller returns 200 so RevenueCat does not retry
        }
    }

    protected function processEvent(): void
    {
        $event = $this->payload['event'] ?? $this->payload;
        $eventId = $event['id'] ?? ($event['event_timestamp_ms'] ?? null) . ($event['app_user_id'] ?? '');
        $eventType = $event['type'] ?? $event['event_type'] ?? null;

        // Idempotency: skip if we already processed this event
        if ($eventId && Cache::has('revenuecat_processed:' . $eventId)) {
            Log::info('RevenueCat webhook: Skipping duplicate event', ['id' => $eventId]);
            return;
        }

        $appUserId = $event['original_app_user_id'] ?? $event['app_user_id'] ?? null;
        if (! $appUserId) {
            Log::warning('RevenueCat webhook: Missing app_user_id in payload', ['payload' => $this->payload]);
            return;
        }

        // Resolve userId: prefer numeric ID; if app_user_id is anonymous ($RCAnonymousID:...), check aliases
        $userId = null;
        if (is_numeric($appUserId)) {
            $userId = (int) $appUserId;
        }
        if ($userId === null || $userId === 0) {
            $aliases = $event['aliases'] ?? [];
            foreach ($aliases as $alias) {
                if (is_numeric($alias)) {
                    $userId = (int) $alias;
                    Log::info('RevenueCat webhook: Resolved user from aliases', ['alias' => $alias]);
                    break;
                }
            }
        }
        if ($userId === null || $userId === 0) {
            Log::warning('RevenueCat webhook: Could not resolve user ID (app_user_id may be anonymous or UUID from Test Event). Call Purchases.logIn(userId) before purchase.', [
                'app_user_id' => $appUserId,
                'event_type' => $eventType ?? 'unknown',
            ]);
            return;
        }

        if (! User::find($userId)) {
            Log::warning('RevenueCat webhook: User not found', ['user_id' => $userId]);
            return;
        }

        $fullAccessSub = Subscription::where('access_type', 'full_access')
            ->where('status', 1)
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $fullAccessSub) {
            Log::error('RevenueCat webhook: No full_access subscription found in database');
            return;
        }

        switch (strtoupper((string) $eventType)) {
            case 'INITIAL_PURCHASE':
            case 'RENEWAL':
            case 'PRODUCT_CHANGE':
            case 'UNCANCELLATION':
                $this->activateSubscription($userId, $fullAccessSub->id, $event);
                break;
            case 'EXPIRATION':
            case 'CANCELLATION':
            case 'BILLING_ISSUE':
                $this->deactivateSubscription($userId);
                break;
            case 'NON_RENEWING_PURCHASE':
                // One-time purchase - treat as active
                $this->activateSubscription($userId, $fullAccessSub->id, $event);
                break;
            case 'TEST':
                Log::info('RevenueCat webhook: Received TEST event', ['app_user_id' => $appUserId]);
                break;
            default:
                Log::info('RevenueCat webhook: Unhandled event type', ['type' => $eventType, 'app_user_id' => $appUserId]);
        }

        if ($eventId) {
            Cache::put('revenuecat_processed:' . $eventId, true, now()->addDays(7));
        }
    }

    protected function activateSubscription(int $userId, int $subId, array $event): void
    {
        $expirationMs = $event['expiration_at_ms'] ?? null;
        $purchasedMs = $event['purchased_at_ms'] ?? $event['event_timestamp_ms'] ?? null;

        $subStartDate = $purchasedMs ? date('Y-m-d H:i:s', (int) ($purchasedMs / 1000)) : now();
        $subExpireDate = $expirationMs ? date('Y-m-d H:i:s', (int) ($expirationMs / 1000)) : now()->addYear();

        // Deactivate any current active sub for this user
        UserSub::where('user_id', $userId)->where('status', 'active')->update(['status' => 'replaced']);

        // Create new active UserSub (payment_id null for RevenueCat)
        $userSub = new UserSub;
        $userSub->user_id = $userId;
        $userSub->sub_id = $subId;
        $userSub->payment_id = null;
        $userSub->status = 'active';
        $userSub->sub_start_date = $subStartDate;
        $userSub->sub_expire_date = $subExpireDate;
        $userSub->save();

        // Sync user_details so app-start-checks returns subscription_active=true
        UserDetail::where('user_id', $userId)->update([
            'subscription' => $subId,
            'subscription_status' => 'active',
        ]);

        Log::info('RevenueCat webhook: Subscription activated', [
            'user_id' => $userId,
            'sub_id' => $subId,
            'expires' => $subExpireDate,
        ]);
    }

    protected function deactivateSubscription(int $userId): void
    {
        $updated = UserSub::where('user_id', $userId)
            ->where('status', 'active')
            ->update(['status' => 'expired']);

        if ($updated > 0) {
            UserDetail::where('user_id', $userId)->update(['subscription_status' => 'expired']);
            Log::info('RevenueCat webhook: Subscription deactivated', ['user_id' => $userId]);
        }
    }
}
