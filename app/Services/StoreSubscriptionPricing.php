<?php

namespace App\Services;

class StoreSubscriptionPricing
{
    public static function fromPayload(?array $payload, string $platform, ?string $productId = null): ?array
    {
        if (!$payload) {
            return null;
        }

        return $platform === 'ios'
            ? self::fromApplePayload($payload, $productId)
            : self::fromGooglePayload($payload, $productId);
    }

    private static function fromGooglePayload(array $payload, ?string $productId): ?array
    {
        foreach ($payload['lineItems'] ?? [] as $item) {
            if ($productId && ($item['productId'] ?? null) !== $productId) {
                continue;
            }
            if (!empty($item['total']) && is_array($item['total'])) {
                return self::fromGoogleMoney($item['total']);
            }
        }

        if (isset($payload['priceAmountMicros'])) {
            $amount = ((int) $payload['priceAmountMicros']) / 1000000;
            $currency = (string) ($payload['priceCurrencyCode'] ?? 'USD');

            return self::build($amount, $currency);
        }

        return null;
    }

    private static function fromApplePayload(array $payload, ?string $productId): ?array
    {
        $items = collect($payload['latest_receipt_info'] ?? [])
            ->when($productId, fn ($collection) => $collection->where('product_id', $productId))
            ->sortByDesc(fn ($item) => (int) ($item['expires_date_ms'] ?? 0));

        $latest = $items->first();
        if (!$latest) {
            return null;
        }

        if (isset($latest['price'])) {
            $amount = ((int) $latest['price']) / 1000;
            $currency = (string) ($latest['currency'] ?? 'USD');

            return self::build($amount, $currency);
        }

        return null;
    }

    public static function fromGoogleMoney(array $money): array
    {
        $units = (int) ($money['units'] ?? 0);
        $nanos = (int) ($money['nanos'] ?? 0);
        $amount = $units + ($nanos / 1000000000);
        $currency = (string) ($money['currencyCode'] ?? 'USD');

        return self::build($amount, $currency);
    }

    public static function build(float $amount, string $currency): array
    {
        return [
            'amount' => $amount,
            'currency' => $currency,
            'formatted' => self::formatAmount($amount, $currency),
        ];
    }

    public static function formatAmount(float $amount, string $currency): string
    {
        $symbol = match ($currency) {
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            default => $currency . ' ',
        };

        return $symbol . number_format($amount, 2);
    }
}
