<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order\Order;
use App\Models\Product\Product;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class CouponCheckoutTest extends TestCase
{
    protected function tearDown(): void
    {
        // Automatically cleanup any test records created during test execution
        Coupon::where('id', '!=', 1)->where('code', '!=', 'SAJ5')->delete();
        CouponUsage::where('customer_phone', 'like', '018%')->delete();
        Order::where('shipping_address->name', 'Coupon Test Customer')->delete();

        parent::tearDown();
    }

    /**
     * 1. Test Admin Coupons Index Page renders cleanly without missing route exception
     */
    public function test_admin_coupons_index_page_loads_cleanly(): void
    {
        $admin = User::first() ?? User::factory()->create(['is_admin' => true]);

        // Access the coupons index page that previously crashed due to missing admin.coupons.destroy route
        $response = $this->actingAs($admin)->get(route('admin.coupons.index'));

        $response->assertStatus(200);
        $response->assertSee('Create Coupon');
    }

    /**
     * 2. Test Admin can toggle status and delete coupon via destroy route
     */
    public function test_admin_coupon_toggle_and_destroy_routes(): void
    {
        $admin = User::first() ?? User::factory()->create(['is_admin' => true]);

        $code = 'TEST_DEL_' . uniqid();
        $coupon = Coupon::create([
            'code' => $code,
            'description' => 'Temporary test coupon',
            'discount_type' => 'fixed',
            'discount_amount' => 50,
            'is_active' => true,
        ]);

        // Toggle Status
        $toggleRes = $this->actingAs($admin)->post(route('admin.coupons.toggle-status', $coupon));
        $toggleRes->assertRedirect();
        $this->assertFalse((bool)$coupon->fresh()->is_active);

        // Delete Coupon via admin.coupons.destroy
        $delRes = $this->actingAs($admin)->delete(route('admin.coupons.destroy', $coupon));
        $delRes->assertRedirect(route('admin.coupons.index'));
        $this->assertDatabaseMissing('coupons', ['id' => $coupon->id]);
    }

    /**
     * 3. Test Checkout Coupon validation error scenarios
     */
    public function test_checkout_coupon_validation_scenarios(): void
    {
        $product = Product::where('is_active', true)->first();
        if (!$product) {
            $this->markTestSkipped('Product required for coupon validation test');
        }

        $items = [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'price' => (float)$product->base_price,
            ]
        ];

        // Case A: Non-existent coupon code
        $resNotFound = $this->postJson(route('checkout.apply-coupon'), [
            'coupon_code' => 'INVALID_CODE_9999',
            'items' => $items,
            'phone' => '01711111111',
        ]);
        $resNotFound->assertStatus(404);
        $resNotFound->assertJson(['success' => false, 'message' => 'Invalid coupon code.']);

        // Case B: Inactive coupon
        $inactiveCoupon = Coupon::create([
            'code' => 'INACTIVE_' . uniqid(),
            'discount_type' => 'fixed',
            'discount_amount' => 50,
            'is_active' => false,
        ]);
        $resInactive = $this->postJson(route('checkout.apply-coupon'), [
            'coupon_code' => $inactiveCoupon->code,
            'items' => $items,
            'phone' => '01711111111',
        ]);
        $resInactive->assertStatus(422);
        $resInactive->assertJson(['success' => false]);
        $this->assertStringContainsString('inactive', strtolower($resInactive->json('message')));

        // Case C: Expired coupon
        $expiredCoupon = Coupon::create([
            'code' => 'EXPIRED_' . uniqid(),
            'discount_type' => 'fixed',
            'discount_amount' => 50,
            'is_active' => true,
            'expires_at' => Carbon::now()->subDays(2),
        ]);
        $resExpired = $this->postJson(route('checkout.apply-coupon'), [
            'coupon_code' => $expiredCoupon->code,
            'items' => $items,
            'phone' => '01711111111',
        ]);
        $resExpired->assertStatus(422);
        $this->assertStringContainsString('expired', strtolower($resExpired->json('message')));

        // Case D: Minimum order amount not met
        $minSpendCoupon = Coupon::create([
            'code' => 'MINSPEND_' . uniqid(),
            'discount_type' => 'fixed',
            'discount_amount' => 100,
            'min_order_amount' => 50000, // Very high threshold
            'is_active' => true,
        ]);
        $resMin = $this->postJson(route('checkout.apply-coupon'), [
            'coupon_code' => $minSpendCoupon->code,
            'items' => $items,
            'phone' => '01711111111',
        ]);
        $resMin->assertStatus(422);
        $this->assertStringContainsString('minimum order amount', strtolower($resMin->json('message')));

        // Case E: Total usage limit reached
        $maxedCoupon = Coupon::create([
            'code' => 'MAXED_' . uniqid(),
            'discount_type' => 'fixed',
            'discount_amount' => 50,
            'usage_limit_total' => 2,
            'used_count' => 2,
            'is_active' => true,
        ]);
        $resMaxed = $this->postJson(route('checkout.apply-coupon'), [
            'coupon_code' => $maxedCoupon->code,
            'items' => $items,
            'phone' => '01711111111',
        ]);
        $resMaxed->assertStatus(422);
        $this->assertStringContainsString('maximum total usage limit', strtolower($resMaxed->json('message')));
    }

    /**
     * 4. Test discount calculation for Fixed, Percentage with Max Cap, and Free Shipping
     */
    public function test_coupon_discount_calculation_varieties(): void
    {
        $product = Product::where('is_active', true)->first();
        if (!$product) {
            $this->markTestSkipped('Product required for discount calculation test');
        }

        $items = [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'price' => (float)$product->base_price,
            ]
        ];
        $subtotal = (float)$product->base_price;

        // Variety A: Fixed ৳100 discount
        $fixedCoupon = Coupon::create([
            'code' => 'FIXED100_' . uniqid(),
            'discount_type' => 'fixed',
            'discount_amount' => 100,
            'is_active' => true,
        ]);
        $resFixed = $this->postJson(route('checkout.apply-coupon'), [
            'coupon_code' => $fixedCoupon->code,
            'items' => $items,
            'delivery_type' => 'inside',
        ]);
        $resFixed->assertStatus(200);
        $resFixed->assertJson([
            'success' => true,
            'coupon_code' => $fixedCoupon->code,
            'discount' => 100,
            'discount_type' => 'fixed',
        ]);

        // Variety B: Percentage 20% discount with Max Cap of ৳80
        $percentCoupon = Coupon::create([
            'code' => 'PERC20_' . uniqid(),
            'discount_type' => 'percentage',
            'discount_amount' => 20,
            'max_discount_amount' => 80,
            'is_active' => true,
        ]);
        $resPercent = $this->postJson(route('checkout.apply-coupon'), [
            'coupon_code' => $percentCoupon->code,
            'items' => $items,
            'delivery_type' => 'inside',
        ]);
        $resPercent->assertStatus(200);
        // (subtotal * 0.20) if > 80 must cap at 80
        $expectedDiscount = min($subtotal * 0.20, 80);
        $this->assertEquals($expectedDiscount, (float)$resPercent->json('discount'));

        // Variety C: Free Shipping discount
        $freeShipCoupon = Coupon::create([
            'code' => 'FREESHIP_' . uniqid(),
            'discount_type' => 'free_shipping',
            'is_active' => true,
        ]);
        $resShip = $this->postJson(route('checkout.apply-coupon'), [
            'coupon_code' => $freeShipCoupon->code,
            'items' => $items,
            'delivery_type' => 'inside',
        ]);
        $resShip->assertStatus(200);
        $this->assertGreaterThan(0, (float)$resShip->json('discount'));
        $this->assertEquals('free_shipping', $resShip->json('discount_type'));

        // Variety D: Integer rounding - 5% on ৳850 (which is 42.5) must round up to full integer ৳43
        $fivePercentCoupon = Coupon::create([
            'code' => 'TEST5_' . uniqid(),
            'discount_type' => 'percentage',
            'discount_amount' => 5,
            'is_active' => true,
        ]);
        $resFive = $this->postJson(route('checkout.apply-coupon'), [
            'coupon_code' => $fivePercentCoupon->code,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'price' => 850.00,
                ]
            ],
            'delivery_type' => 'inside',
        ]);
        $resFive->assertStatus(200);
        $this->assertEquals(43, $resFive->json('discount'));
        $this->assertIsInt($resFive->json('discount') + 0);
    }

    /**
     * 5. Test Full Order Placement with Coupon: order created, coupon usage logged, stock updated
     */
    public function test_full_checkout_order_placement_with_coupon(): void
    {
        $product = Product::where('is_active', true)->where('total_stock', '>=', 5)->first();
        if (!$product) {
            $this->markTestSkipped('Product with stock required for full order placement test');
        }

        $initialStock = $product->total_stock;
        $productPrice = (float)$product->base_price;

        $coupon = Coupon::create([
            'code' => 'ORDERTEST_' . uniqid(),
            'discount_type' => 'fixed',
            'discount_amount' => 120,
            'is_active' => true,
            'usage_limit_per_user' => 1,
            'used_count' => 0,
        ]);

        $testPhone = '018' . rand(10000000, 99999999);
        $orderPayload = [
            'name' => 'Coupon Test Customer',
            'phone' => $testPhone,
            'address' => 'House 45, Road 7, Banani, Dhaka',
            'delivery_type' => 'inside',
            'payment_method' => 'cod',
            'coupon_code' => $coupon->code,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'price' => $productPrice,
                ]
            ],
            'notes' => 'Please test coupon order',
        ];

        $response = $this->postJson(route('checkout.process'), $orderPayload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $orderId = $response->json('order_id');
        $this->assertNotNull($orderId);

        // 1. Verify Order saved with coupon details
        $order = Order::where('order_id', $orderId)->first();
        $this->assertNotNull($order);
        $this->assertEquals($coupon->code, $order->coupon);
        $this->assertEquals(120.00, (float)$order->coupon_discount);

        $expectedTotal = max(0, $productPrice + $order->shipping_cost - 120.00);
        $this->assertEquals($expectedTotal, (float)$order->total);

        // 2. Verify CouponUsage record created
        $this->assertDatabaseHas('coupon_usages', [
            'coupon_id' => $coupon->id,
            'order_id' => $order->id,
            'customer_phone' => $testPhone,
            'discount_applied' => 120.00,
        ]);

        // 3. Verify Coupon used_count incremented
        $this->assertEquals(1, $coupon->fresh()->used_count);

        // 4. Verify Order Item created
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'price' => $productPrice,
            'quantity' => 1,
        ]);

        // 5. Verify same user/phone cannot reuse single-use coupon again
        $repeatRes = $this->postJson(route('checkout.apply-coupon'), [
            'coupon_code' => $coupon->code,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'price' => $productPrice,
                ]
            ],
            'phone' => $testPhone,
        ]);
        $repeatRes->assertStatus(422);
        $this->assertStringContainsString('maximum allowed times', strtolower($repeatRes->json('message')));
    }
}
