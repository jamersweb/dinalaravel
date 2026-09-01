<?php

namespace App\Support;

class StoreSubscriptionProducts
{
    public const ESSENTIAL = 'essential';
    public const PREMIUM = 'fwd_basic_plan';
    public const VIP = 'fwd_premium';

    private const LABELS = [
        self::ESSENTIAL => 'Essential',
        self::PREMIUM => 'Premium',
        self::VIP => 'VIP Coaching',
    ];

    private const TIERS = [
        self::ESSENTIAL => 'essential',
        self::PREMIUM => 'premium',
        self::VIP => 'vip_coaching',
    ];

    private const ACCESS_TYPES = [
        self::ESSENTIAL => 'half_access',
        self::PREMIUM => 'half_access',
        self::VIP => 'full_access',
    ];

    public static function ids(): array
    {
        return array_keys(self::LABELS);
    }

    public static function label(?string $productId): ?string
    {
        if ($productId === null || $productId === '') {
            return null;
        }

        return self::LABELS[$productId] ?? $productId;
    }

    public static function tier(?string $productId): ?string
    {
        if ($productId === null || $productId === '') {
            return null;
        }

        return self::TIERS[$productId] ?? null;
    }

    public static function accessType(string $productId): string
    {
        return self::ACCESS_TYPES[$productId] ?? 'half_access';
    }

    public static function hasNutritionAccess(?string $productId): bool
    {
        return $productId !== self::ESSENTIAL;
    }

    public static function hasPrivateCoaching(?string $productId): bool
    {
        return $productId === self::VIP;
    }
}
