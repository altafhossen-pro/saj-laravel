<?php

namespace Tests\Feature;

use App\Models\Product\Product;
use App\Models\Product\ProductVariant;
use App\Models\User;
use App\Models\Category;
use App\Models\Order\Order;
use Tests\TestCase;

class ProductOfferTest extends TestCase
{
    protected ?Product $testProduct = null;
    protected ?User $adminUser = null;
    protected int $originalStock = 0;
    protected float $originalBasePrice = 1000.00;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::role('super_admin')->first() 
            ?? User::first();

        // Find or create test product
        $this->testProduct = Product::find(4);
        if (!$this->testProduct) {
            $category = Category::first() ?? Category::create(['name' => 'General', 'slug' => 'general']);
            $this->testProduct = Product::create([
                'title' => 'Test Offer Bracelet',
                'slug' => 'test-offer-bracelet-' . uniqid(),
                'category_id' => $category->id,
                'base_price' => 1000.00,
                'total_stock' => 50,
                'is_active' => true,
                'product_type' => 'simple',
            ]);
            ProductVariant::create([
                'product_id' => $this->testProduct->id,
                'sku' => 'TEST-OFFER-001',
                'current_price' => 1000.00,
                'original_price' => null,
                'stock_quantity' => 50,
            ]);
        }

        $this->originalStock = (int) $this->testProduct->total_stock;
        $this->originalBasePrice = (float) $this->testProduct->base_price;
    }

    protected function tearDown(): void
    {
        // Reset product 4 offer state cleanly
        if ($this->testProduct) {
            $this->testProduct->refresh();
            $regular = (float) ($this->testProduct->offer_regular_price ?? $this->originalBasePrice);
            if ($regular <= 0) $regular = 1000.00;

            $this->testProduct->update([
                'is_offer_active' => false,
                'offer_title' => null,
                'offer_discount_type' => 'percentage',
                'offer_discount_value' => null,
                'offer_regular_price' => null,
                'offer_start_date' => null,
                'offer_end_date' => null,
                'base_price' => $regular,
                'total_stock' => $this->originalStock,
            ]);

            $variant = $this->testProduct->variants()->first();
            if ($variant) {
                $variant->update([
                    'current_price' => $regular,
                    'original_price' => null,
                    'sale_price' => null,
                    'stock_quantity' => $this->originalStock,
                ]);
            }
        }

        parent::tearDown();
    }

    /**
     * Test products without active offer do not show discount badge even if old price exists
     */
    public function test_products_without_active_offer_do_not_show_discount_badge(): void
    {
        // Product has old price 550 and base price 450, but is_offer_active = false
        $this->testProduct->update([
            'is_offer_active' => false,
            'base_price' => 450.00,
        ]);
        $variant = $this->testProduct->variants()->first();
        $variant->update([
            'current_price' => 450.00,
            'original_price' => 550.00,
        ]);

        $this->assertNull($this->testProduct->fresh()->active_discount_percent);

        // Product details page should not contain the badge
        $response = $this->get('/product/' . $this->testProduct->slug);
        if ($response->status() === 200) {
            $response->assertDontSee('-18%');
        }
    }

    /**
     * Test homepage product cards include active_discount_percent in JSON for Alpine
     */
    public function test_homepage_product_cards_include_offer_discount_percent(): void
    {
        // Set test product as trending with 20% active offer
        $this->testProduct->update([
            'is_offer_active' => true,
            'is_trending' => true,
            'offer_discount_type' => 'percentage',
            'offer_discount_value' => 20,
            'offer_regular_price' => 1000.00,
            'base_price' => 800.00,
        ]);

        \Illuminate\Support\Facades\Cache::forget('homepage_trending');
        \Illuminate\Support\Facades\Cache::forget('homepage_sections');

        $response = $this->get('/');
        $response->assertStatus(200);

        // Verify active_discount_percent appears for the offered product
        $content = $response->getContent();
        $this->assertTrue(
            str_contains($content, '"active_discount_percent":20') || 
            str_contains($content, '&quot;active_discount_percent&quot;:20')
        );
    }

    /**
     * Test product edit page renders the Offer tab and its manager
     */
    public function test_admin_product_edit_screen_renders_offer_tab(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.products.edit', $this->testProduct->id));

        $response->assertStatus(200);
        $response->assertSee("activeTab = 'offer'", false);
        $response->assertSee('Product Offer & Promotional Discount', false);
        $response->assertSee('productOfferManager', false);
        $response->assertSee('name="is_offer_active"', false);
        $response->assertSee('name="offer_discount_type"', false);
        $response->assertSee('name="offer_discount_value"', false);
    }

    /**
     * Test admin can apply percentage offer (e.g. 20% off 1000 = 800)
     */
    public function test_admin_can_enable_percentage_offer(): void
    {
        $editUrl = route('admin.products.edit', $this->testProduct->id);

        $response = $this->from($editUrl)
            ->actingAs($this->adminUser)
            ->put(route('admin.products.update', $this->testProduct->id), [
                'name' => $this->testProduct->title,
                'slug' => $this->testProduct->slug,
                'category_id' => $this->testProduct->category_id,
                'regular_price' => 1000.00,
                'product_type' => 'simple',
                'is_active' => '1',
                'is_offer_active' => '1',
                'offer_title' => 'Flash Sale 20% Off',
                'offer_discount_type' => 'percentage',
                'offer_discount_value' => 20,
            ]);

        $response->assertRedirect($editUrl);
        $response->assertSessionHas('success', 'Product updated successfully!');

        $this->testProduct->refresh();

        $this->assertTrue((bool)$this->testProduct->is_offer_active);
        $this->assertEquals('Flash Sale 20% Off', $this->testProduct->offer_title);
        $this->assertEquals('percentage', $this->testProduct->offer_discount_type);
        $this->assertEquals(20.00, (float)$this->testProduct->offer_discount_value);
        $this->assertEquals(1000.00, (float)$this->testProduct->offer_regular_price);
        $this->assertEquals(800.00, (float)$this->testProduct->base_price);

        // Variant price check
        $variant = $this->testProduct->variants()->first();
        $this->assertNotNull($variant);
        $this->assertEquals(800.00, (float)$variant->current_price);
        $this->assertEquals(1000.00, (float)$variant->original_price);

        // Model helper check
        $this->assertTrue($this->testProduct->isProductOfferActive());
        $this->assertEquals(20, $this->testProduct->active_discount_percent);
        $this->assertEquals(1000.00, (float)$this->testProduct->original_price);
    }

    /**
     * Test admin can apply flat discount offer (e.g. 150 off 1000 = 850)
     */
    public function test_admin_can_enable_flat_discount_offer(): void
    {
        $editUrl = route('admin.products.edit', $this->testProduct->id);

        $response = $this->from($editUrl)
            ->actingAs($this->adminUser)
            ->put(route('admin.products.update', $this->testProduct->id), [
                'name' => $this->testProduct->title,
                'slug' => $this->testProduct->slug,
                'category_id' => $this->testProduct->category_id,
                'regular_price' => 1000.00,
                'product_type' => 'simple',
                'is_active' => '1',
                'is_offer_active' => '1',
                'offer_title' => 'Flat ৳150 Off',
                'offer_discount_type' => 'fixed',
                'offer_discount_value' => 150,
            ]);

        $response->assertRedirect($editUrl);
        $response->assertSessionHas('success', 'Product updated successfully!');

        $this->testProduct->refresh();

        $this->assertTrue((bool)$this->testProduct->is_offer_active);
        $this->assertEquals(850.00, (float)$this->testProduct->base_price);
        $this->assertEquals(1000.00, (float)$this->testProduct->offer_regular_price);

        $variant = $this->testProduct->variants()->first();
        $this->assertEquals(850.00, (float)$variant->current_price);
        $this->assertEquals(1000.00, (float)$variant->original_price);
        $this->assertEquals(15, $this->testProduct->active_discount_percent);
    }

    /**
     * Test admin can apply direct fixed price offer (e.g. sell directly at 699)
     */
    public function test_admin_can_enable_direct_price_offer(): void
    {
        $editUrl = route('admin.products.edit', $this->testProduct->id);

        $response = $this->from($editUrl)
            ->actingAs($this->adminUser)
            ->put(route('admin.products.update', $this->testProduct->id), [
                'name' => $this->testProduct->title,
                'slug' => $this->testProduct->slug,
                'category_id' => $this->testProduct->category_id,
                'regular_price' => 1000.00,
                'product_type' => 'simple',
                'is_active' => '1',
                'is_offer_active' => '1',
                'offer_title' => 'Super Deal ৳699',
                'offer_discount_type' => 'fixed_price',
                'offer_discount_value' => 699,
            ]);

        $response->assertRedirect($editUrl);
        $response->assertSessionHas('success', 'Product updated successfully!');

        $this->testProduct->refresh();

        $this->assertTrue((bool)$this->testProduct->is_offer_active);
        $this->assertEquals(699.00, (float)$this->testProduct->base_price);
        $this->assertEquals(1000.00, (float)$this->testProduct->offer_regular_price);

        $variant = $this->testProduct->variants()->first();
        $this->assertEquals(699.00, (float)$variant->current_price);
        $this->assertEquals(1000.00, (float)$variant->original_price);
    }

    /**
     * Test cart validation endpoint returns the active offer price
     */
    public function test_cart_validation_returns_offer_price(): void
    {
        // First activate offer at 25% off (1000 -> 750)
        $this->testProduct->update([
            'is_offer_active' => true,
            'offer_discount_type' => 'percentage',
            'offer_discount_value' => 25,
            'offer_regular_price' => 1000.00,
            'base_price' => 750.00,
            'total_stock' => 50,
        ]);
        $variant = $this->testProduct->variants()->first();
        $variant->update([
            'current_price' => 750.00,
            'original_price' => 1000.00,
            'sale_price' => 750.00,
            'stock_quantity' => 50,
        ]);

        $response = $this->postJson('/api/cart/validate', [
            'items' => [
                [
                    'product_id' => $this->testProduct->id,
                    'variant_id' => $variant->id,
                    'quantity' => 1,
                    'price' => 750.00,
                ]
            ]
        ]);

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertTrue($json['success'] ?? false);
        $this->assertNotEmpty($json['items'] ?? []);
        $this->assertEquals(750.00, (float)$json['items'][0]['price']);
    }

    /**
     * Test checkout process places order using the discounted offer price
     */
    public function test_checkout_places_order_at_offer_price(): void
    {
        // Activate offer: 800 price (200 off 1000)
        $this->testProduct->update([
            'is_offer_active' => true,
            'offer_discount_type' => 'fixed',
            'offer_discount_value' => 200,
            'offer_regular_price' => 1000.00,
            'base_price' => 800.00,
            'total_stock' => 50,
        ]);
        $variant = $this->testProduct->variants()->first();
        $variant->update([
            'current_price' => 800.00,
            'original_price' => 1000.00,
            'sale_price' => 800.00,
            'stock_quantity' => 50,
        ]);

        $orderPayload = [
            'name' => 'Offer Test Shopper',
            'phone' => '018' . rand(10000000, 99999999),
            'address' => 'Dhaka Test Address',
            'delivery_type' => 'inside',
            'payment_method' => 'cod',
            'items' => [
                [
                    'product_id' => $this->testProduct->id,
                    'variant_id' => $variant->id,
                    'quantity' => 2,
                    'price' => 800.00,
                ]
            ],
        ];

        $response = $this->postJson(route('checkout.process'), $orderPayload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $orderId = $response->json('order_id');
        $this->assertNotNull($orderId);

        // Find the placed order
        $order = Order::where('order_id', $orderId)->first();
        $this->assertNotNull($order);

        // Check order item unit price and subtotal
        $orderItem = $order->items()->first();
        $this->assertNotNull($orderItem);
        $this->assertEquals(800.00, (float)$orderItem->price);
        $this->assertEquals(1600.00, (float)$orderItem->subtotal);

        // Order total = 1600 + shipping_cost
        $this->assertEquals(1600.00 + (float)$order->shipping_cost, (float)$order->total);

        // Clean up created order
        $order->items()->delete();
        $order->delete();
    }

    /**
     * Test turning off the offer restores regular catalog price
     */
    public function test_deactivating_offer_restores_regular_price(): void
    {
        $editUrl = route('admin.products.edit', $this->testProduct->id);

        // First set offer active
        $this->testProduct->update([
            'is_offer_active' => true,
            'offer_discount_type' => 'percentage',
            'offer_discount_value' => 30,
            'offer_regular_price' => 1000.00,
            'base_price' => 700.00,
        ]);

        // Submit update with is_offer_active = false
        $response = $this->from($editUrl)
            ->actingAs($this->adminUser)
            ->put(route('admin.products.update', $this->testProduct->id), [
                'name' => $this->testProduct->title,
                'slug' => $this->testProduct->slug,
                'category_id' => $this->testProduct->category_id,
                'regular_price' => 1000.00,
                'product_type' => 'simple',
                'is_active' => '1',
                // is_offer_active omitted (turned off)
            ]);

        $response->assertRedirect($editUrl);
        $response->assertSessionHas('success', 'Product updated successfully!');

        $this->testProduct->refresh();

        $this->assertFalse((bool)$this->testProduct->is_offer_active);
        $this->assertNull($this->testProduct->offer_regular_price);
        $this->assertEquals(1000.00, (float)$this->testProduct->base_price);

        $variant = $this->testProduct->variants()->first();
        $this->assertEquals(1000.00, (float)$variant->current_price);
        $this->assertEquals(0, (float)($variant->original_price ?? 0));
        $this->assertFalse($this->testProduct->isProductOfferActive());
    }

    /**
     * Test date range expiration behavior
     */
    public function test_offer_date_range_expiration(): void
    {
        // Expired offer
        $this->testProduct->update([
            'is_offer_active' => true,
            'offer_discount_type' => 'percentage',
            'offer_discount_value' => 20,
            'offer_start_date' => now()->subDays(5),
            'offer_end_date' => now()->subDay(), // expired yesterday
        ]);

        $this->assertFalse($this->testProduct->isProductOfferActive());

        // Active offer in future window
        $this->testProduct->update([
            'offer_start_date' => now()->subDay(),
            'offer_end_date' => now()->addDays(5),
        ]);

        $this->assertTrue($this->testProduct->isProductOfferActive());
    }
}
