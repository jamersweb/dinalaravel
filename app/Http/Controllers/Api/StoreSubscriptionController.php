<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StoreSubscription;
use App\Models\Subscription;
use App\Models\UserDetail;
use App\Models\UserSub;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class StoreSubscriptionController extends Controller
{
    private const BASIC_PRODUCT = 'fwd_basic_plan';
    private const PREMIUM_PRODUCT = 'fwd_premium';

    public function verify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'platform' => 'required|in:ios,android',
            'product_id' => 'required|string|in:' . self::BASIC_PRODUCT . ',' . self::PREMIUM_PRODUCT,
            'base_plan_id' => 'nullable|string|max:128',
            'purchase_token' => 'required_if:platform,android|string|max:512',
            'receipt_data' => 'required_if:platform,ios|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $platform = $request->input('platform');
        $productId = $request->input('product_id');
        $result = $platform === 'android'
            ? $this->verifyGoogleSubscription($productId, $request->input('purchase_token'))
            : $this->verifyAppleReceipt($productId, $request->input('receipt_data'));

        if (!$result['active']) {
            $this->storeVerification($request, $result, 'expired');
            return response()->json([
                'status' => false,
                'message' => 'Subscription is not active.',
                'subscription_active' => false,
                'expires_at' => $result['expires_at'],
            ], 402);
        }

        $storeSub = $this->storeVerification($request, $result, 'active');
        $this->activateBackendSubscription($productId, $result['purchased_at'], $result['expires_at']);

        return response()->json([
            'status' => true,
            'message' => 'Subscription verified.',
            'subscription_active' => true,
            'product_id' => $storeSub->product_id,
            'access_type' => $this->accessTypeForProduct($productId),
            'expires_at' => optional($storeSub->expires_at)->toIso8601String(),
        ]);
    }

    public function status()
    {
        $active = StoreSubscription::where('user_id', Auth::id())
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest('verified_at')
            ->first();

        return response()->json([
            'status' => (bool) $active,
            'subscription_active' => (bool) $active,
            'product_id' => $active?->product_id,
            'access_type' => $active ? $this->accessTypeForProduct($active->product_id) : null,
            'expires_at' => optional($active?->expires_at)->toIso8601String(),
        ]);
    }

    public function mediaAccessToken()
    {
        $active = StoreSubscription::where('user_id', Auth::id())
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();

        if (!$active) {
            return response()->json([
                'status' => false,
                'message' => 'Active subscription required.',
            ], 402);
        }

        $expiresAt = now()->addMinutes(15);
        $payload = [
            'sub' => Auth::id(),
            'scope' => 'media',
            'exp' => $expiresAt->timestamp,
            'nonce' => Str::random(16),
        ];
        $body = $this->base64UrlEncode(json_encode($payload));
        $signature = hash_hmac('sha256', $body, (string) config('services.store_iap.media_token_secret'), true);

        return response()->json([
            'status' => true,
            'access_token' => $body . '.' . $this->base64UrlEncode($signature),
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toIso8601String(),
        ]);
    }

    private function verifyGoogleSubscription(string $productId, string $purchaseToken): array
    {
        $accessToken = $this->googleAccessToken();
        $packageName = config('services.store_iap.google_package_name');
        if (!$accessToken || !$packageName) {
            return $this->inactiveResult(['error' => 'Google Play verification is not configured.']);
        }

        $url = sprintf(
            'https://androidpublisher.googleapis.com/androidpublisher/v3/applications/%s/purchases/subscriptions/%s/tokens/%s',
            rawurlencode($packageName),
            rawurlencode($productId),
            rawurlencode($purchaseToken)
        );
        $response = Http::withToken($accessToken)->get($url);
        if (!$response->ok()) {
            return $this->inactiveResult($response->json() ?? ['body' => $response->body()]);
        }

        $json = $response->json();
        $expiresAt = isset($json['expiryTimeMillis'])
            ? Carbon::createFromTimestampMs((int) $json['expiryTimeMillis'])
            : null;
        $purchasedAt = isset($json['startTimeMillis'])
            ? Carbon::createFromTimestampMs((int) $json['startTimeMillis'])
            : now();
        $paymentState = (int) ($json['paymentState'] ?? -1);
        $cancelReason = $json['cancelReason'] ?? null;

        return [
            'active' => $expiresAt !== null && $expiresAt->isFuture() && in_array($paymentState, [1, 2], true),
            'transaction_id' => $json['orderId'] ?? null,
            'original_transaction_id' => $json['linkedPurchaseToken'] ?? null,
            'purchased_at' => $purchasedAt,
            'expires_at' => $expiresAt,
            'raw_payload' => $json + ['cancelReason' => $cancelReason],
        ];
    }

    private function verifyAppleReceipt(string $productId, string $receiptData): array
    {
        $payload = [
            'receipt-data' => $receiptData,
            'password' => config('services.store_iap.apple_shared_secret'),
            'exclude-old-transactions' => true,
        ];
        $json = $this->postAppleReceipt('https://buy.itunes.apple.com/verifyReceipt', $payload);
        if (($json['status'] ?? null) === 21007) {
            $json = $this->postAppleReceipt('https://sandbox.itunes.apple.com/verifyReceipt', $payload);
        }
        if (($json['status'] ?? null) !== 0) {
            return $this->inactiveResult($json);
        }

        $items = collect($json['latest_receipt_info'] ?? [])
            ->where('product_id', $productId)
            ->sortByDesc(fn ($item) => (int) ($item['expires_date_ms'] ?? 0));
        $latest = $items->first();
        if (!$latest) {
            return $this->inactiveResult($json);
        }

        $expiresAt = isset($latest['expires_date_ms'])
            ? Carbon::createFromTimestampMs((int) $latest['expires_date_ms'])
            : null;
        $purchasedAt = isset($latest['purchase_date_ms'])
            ? Carbon::createFromTimestampMs((int) $latest['purchase_date_ms'])
            : now();

        return [
            'active' => $expiresAt !== null && $expiresAt->isFuture(),
            'transaction_id' => $latest['transaction_id'] ?? null,
            'original_transaction_id' => $latest['original_transaction_id'] ?? null,
            'purchased_at' => $purchasedAt,
            'expires_at' => $expiresAt,
            'raw_payload' => $json,
        ];
    }

    private function postAppleReceipt(string $url, array $payload): array
    {
        return Http::timeout(15)->post($url, $payload)->json() ?? ['status' => -1];
    }

    private function storeVerification(Request $request, array $result, string $status): StoreSubscription
    {
        return StoreSubscription::updateOrCreate(
            [
                'platform' => $request->input('platform'),
                'purchase_token' => $request->input('purchase_token') ?: ($result['transaction_id'] ?? null),
            ],
            [
                'user_id' => Auth::id(),
                'product_id' => $request->input('product_id'),
                'base_plan_id' => $request->input('base_plan_id'),
                'transaction_id' => $result['transaction_id'] ?? null,
                'original_transaction_id' => $result['original_transaction_id'] ?? null,
                'status' => $status,
                'purchased_at' => $result['purchased_at'] ?? null,
                'expires_at' => $result['expires_at'] ?? null,
                'verified_at' => now(),
                'raw_payload' => $result['raw_payload'] ?? null,
            ]
        );
    }

    private function activateBackendSubscription(string $productId, ?Carbon $startAt, ?Carbon $expiresAt): void
    {
        $accessType = $this->accessTypeForProduct($productId);
        $sub = Subscription::where('access_type', $accessType)->where('status', 1)->first()
            ?: Subscription::where('status', 1)->first();
        if (!$sub) return;

        UserSub::where('user_id', Auth::id())->where('status', 'active')->update(['status' => 'expired']);

        $userSub = new UserSub();
        $userSub->sub_id = $sub->id;
        $userSub->user_id = Auth::id();
        $userSub->payment_id = null;
        $userSub->status = 'active';
        $userSub->sub_start_date = $startAt ?: now();
        $userSub->sub_expire_date = $expiresAt;
        $userSub->save();

        UserDetail::where('user_id', Auth::id())->update([
            'subscription' => $sub->id,
            'subscription_status' => 'active',
        ]);
    }

    private function accessTypeForProduct(string $productId): string
    {
        return $productId === self::PREMIUM_PRODUCT ? 'full_access' : 'half_access';
    }

    private function inactiveResult(array $payload): array
    {
        return [
            'active' => false,
            'transaction_id' => null,
            'original_transaction_id' => null,
            'purchased_at' => null,
            'expires_at' => null,
            'raw_payload' => $payload,
        ];
    }

    private function googleAccessToken(): ?string
    {
        $raw = config('services.store_iap.google_service_account_json');
        if (!$raw) return null;

        $serviceAccount = json_decode($raw, true);
        if (!is_array($serviceAccount) && is_file($raw)) {
            $serviceAccount = json_decode(file_get_contents($raw), true);
        }
        if (!is_array($serviceAccount) || empty($serviceAccount['client_email']) || empty($serviceAccount['private_key'])) {
            return null;
        }

        $now = time();
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $claims = [
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/androidpublisher',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ];
        $unsigned = $this->base64UrlEncode(json_encode($header)) . '.' . $this->base64UrlEncode(json_encode($claims));
        openssl_sign($unsigned, $signature, $serviceAccount['private_key'], 'sha256WithRSAEncryption');

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $unsigned . '.' . $this->base64UrlEncode($signature),
        ]);

        return $response->ok() ? ($response->json('access_token') ?: null) : null;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
