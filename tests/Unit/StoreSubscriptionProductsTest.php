<?php

namespace Tests\Unit;

use App\Support\StoreSubscriptionProducts;
use PHPUnit\Framework\TestCase;

class StoreSubscriptionProductsTest extends TestCase
{
    public function test_it_lists_direct_iap_product_ids(): void
    {
        $this->assertSame([
            'essential',
            'fwd_basic_plan',
            'fwd_premium',
        ], StoreSubscriptionProducts::ids());
    }

    public function test_it_maps_product_labels_and_tiers(): void
    {
        $this->assertSame('Essential', StoreSubscriptionProducts::label('essential'));
        $this->assertSame('Premium', StoreSubscriptionProducts::label('fwd_basic_plan'));
        $this->assertSame('VIP Coaching', StoreSubscriptionProducts::label('fwd_premium'));

        $this->assertSame('essential', StoreSubscriptionProducts::tier('essential'));
        $this->assertSame('premium', StoreSubscriptionProducts::tier('fwd_basic_plan'));
        $this->assertSame('vip_coaching', StoreSubscriptionProducts::tier('fwd_premium'));
    }

    public function test_it_maps_access_flags(): void
    {
        $this->assertSame('half_access', StoreSubscriptionProducts::accessType('essential'));
        $this->assertSame('half_access', StoreSubscriptionProducts::accessType('fwd_basic_plan'));
        $this->assertSame('full_access', StoreSubscriptionProducts::accessType('fwd_premium'));

        $this->assertFalse(StoreSubscriptionProducts::hasNutritionAccess('essential'));
        $this->assertTrue(StoreSubscriptionProducts::hasNutritionAccess('fwd_basic_plan'));
        $this->assertTrue(StoreSubscriptionProducts::hasNutritionAccess('fwd_premium'));

        $this->assertFalse(StoreSubscriptionProducts::hasPrivateCoaching('essential'));
        $this->assertFalse(StoreSubscriptionProducts::hasPrivateCoaching('fwd_basic_plan'));
        $this->assertTrue(StoreSubscriptionProducts::hasPrivateCoaching('fwd_premium'));
    }
}
