<?php

namespace Tests\Feature;

use App\Models\Order\Order;
use App\Models\Product\Product;
use App\Models\User;
use Tests\TestCase;

class ManualOrderPosTest extends TestCase
{
    protected function tearDown(): void
    {
        \App\Models\Order\Order::where('shipping_address->name', 'Test POS Customer')->delete();
        parent::tearDown();
    }

    /**
     * Test manual order POS page loads cleanly for admin
     */
    public function test_manual_order_page_loads_cleanly(): void
    {
        $admin = User::first() ?? User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('admin.orders.manual'));

        $response->assertStatus(200);
        $response->assertSee('posSystem()', false);
        $response->assertSee('addToCart(product', false);
    }

    /**
     * Test product search endpoint returns product with sku, stock and correct product_type
     */
    public function test_product_search_endpoint_returns_valid_data(): void
    {
        $admin = User::first() ?? User::factory()->create(['is_admin' => true]);

        $product = Product::first();
        if (!$product) {
            $this->markTestSkipped('No products available for search test');
        }

        $searchTerm = substr($product->title, 0, 5);
        $response = $this->actingAs($admin)->getJson("/admin/dashboard/products/search?q=" . urlencode($searchTerm));

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertIsArray($json);
        $this->assertNotEmpty($json);

        $first = $json[0];
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('title', $first);
        $this->assertArrayHasKey('sku', $first);
        $this->assertArrayHasKey('product_type', $first);
        $this->assertArrayHasKey('stock', $first);
        $this->assertArrayHasKey('variants', $first);
    }

    /**
     * Test manual order submission creates order and order items
     */
    public function test_manual_order_submission_creates_order(): void
    {
        $admin = User::first() ?? User::factory()->create(['is_admin' => true]);
        $product = Product::first();

        if (!$product) {
            $this->markTestSkipped('No product available to test order submission');
        }

        $payload = [
            'is_draft' => false,
            'source' => 'Phone',
            'customer_contact' => '01700000000',
            'customer_name' => 'Test POS Customer',
            'shipping_address' => 'Mirpur 10, Dhaka',
            'notes' => 'Urgent POS delivery',
            'delivery_charge' => 80,
            'discount' => 50,
            'subtotal' => 1000,
            'total' => 1030,
            'items' => [
                [
                    'cartId' => 'p' . $product->id,
                    'product_id' => $product->id,
                    'name' => $product->title,
                    'qty' => 1,
                    'max_stock' => 10,
                    'price' => 1000,
                    'original_price' => 1000,
                    'image' => null,
                    'product_sku' => 'TEST-SKU',
                    'variant_id' => null,
                    'variant_sku' => 'TEST-SKU',
                    'variantLabel' => 'Standard',
                ]
            ],
        ];

        $response = $this->actingAs($admin)->postJson(route('admin.orders.store'), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('orders', [
            'shipping_address->phone' => '01700000000',
            'shipping_address->name' => 'Test POS Customer',
            'order_source' => 'Phone',
        ]);

        $this->assertDatabaseHas('order_items', [
            'name' => $product->title,
            'quantity' => 1,
        ]);
    }
}

