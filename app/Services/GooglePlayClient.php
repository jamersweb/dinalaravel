<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GooglePlayClient
{
    public function packageName(): ?string
    {
        return config('services.store_iap.google_package_name') ?: null;
    }

    public function accessToken(): ?string
    {
        $raw = config('services.store_iap.google_service_account_json');
        if (!$raw) {
            return null;
        }

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
        if (!openssl_sign($unsigned, $signature, $serviceAccount['private_key'], 'sha256WithRSAEncryption')) {
            return null;
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $unsigned . '.' . $this->base64UrlEncode($signature),
        ]);

        return $response->ok() ? ($response->json('access_token') ?: null) : null;
    }

    public function fetchOrder(string $orderId): ?array
    {
        $packageName = $this->packageName();
        $accessToken = $this->accessToken();
        if (!$packageName || !$accessToken) {
            return null;
        }

        $url = sprintf(
            'https://androidpublisher.googleapis.com/androidpublisher/v3/applications/%s/orders/%s',
            rawurlencode($packageName),
            rawurlencode($orderId)
        );

        $response = Http::withToken($accessToken)->get($url);
        if (!$response->ok()) {
            Log::warning('Google Play order lookup failed', [
                'order_id' => $orderId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return $response->json();
    }

    public function acknowledgeSubscription(string $subscriptionId, string $purchaseToken): bool
    {
        $packageName = $this->packageName();
        $accessToken = $this->accessToken();
        if (!$packageName || !$accessToken) {
            return false;
        }

        $url = sprintf(
            'https://androidpublisher.googleapis.com/androidpublisher/v3/applications/%s/purchases/subscriptions/%s/tokens/%s:acknowledge',
            rawurlencode($packageName),
            rawurlencode($subscriptionId),
            rawurlencode($purchaseToken)
        );

        $response = Http::withToken($accessToken)->post($url, [
            'developerPayload' => '',
        ]);

        if ($response->ok() || $response->status() === 204) {
            return true;
        }

        Log::warning('Google Play subscription acknowledge failed', [
            'subscription_id' => $subscriptionId,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return false;
    }

    public function pricingFromOrder(?array $orderJson, ?string $productId = null): ?array
    {
        if (!$orderJson) {
            return null;
        }

        foreach ($orderJson['lineItems'] ?? [] as $item) {
            if ($productId && ($item['productId'] ?? null) !== $productId) {
                continue;
            }
            if (!empty($item['total']) && is_array($item['total'])) {
                return StoreSubscriptionPricing::fromGoogleMoney($item['total']);
            }
        }

        return null;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
