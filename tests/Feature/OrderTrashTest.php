<?php

namespace Tests\Feature;

use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Models\Order\OrderLog;
use App\Models\Product\Product;
use App\Models\Product\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderTrashTest extends TestCase
{
    protected ?User $superAdmin = null;
    protected ?User $regularUser = null;
    protected ?Order $testOrder = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure super_admin role exists
        if (!Role::where('name', 'super_admin')->exists()) {
            Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        }

        // Get or create super admin user
        $this->superAdmin = User::role('super_admin')->first();
        if (!$this->superAdmin) {
            $this->superAdmin = User::first() ?? User::create([
                'name' => 'Test Super Admin',
                'email' => 'test_super_admin_' . uniqid() . '@example.com',
                'password' => bcrypt('password123'),
                'role' => 'admin',
            ]);
            $this->superAdmin->assignRole('super_admin');
        }

        // Create regular user without super_admin role
        $this->regularUser = User::create([
            'name' => 'Regular User ' . uniqid(),
            'email' => 'regular_user_' . uniqid() . '@example.com',
            'phone' => '01700' . rand(100000, 999999),
            'password' => bcrypt('password123'),
            'role' => 'user',
        ]);

        // Create an isolated dummy order for testing
        $uniqueOrderId = 'TRASH-TEST-' . rand(100000, 999999);
        $this->testOrder = Order::create([
            'order_id' => $uniqueOrderId,
            'user_id' => $this->regularUser->id,
            'total' => 1500.00,
            'subtotal' => 1400.00,
            'shipping_cost' => 100.00,
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'cod',
            'shipping_address' => [
                'name' => $this->regularUser->name,
                'phone' => $this->regularUser->phone,
                'address' => '123 Test Street, Dhaka',
            ],
            'order_source' => 'website',
        ]);

        OrderItem::create([
            'order_id' => $this->testOrder->id,
            'product_id' => 1,
            'name' => 'Test Order Item',
            'quantity' => 1,
            'price' => 1400.00,
            'subtotal' => 1400.00,
        ]);
    }

    protected function tearDown(): void
    {
        // Clean up test order completely if still exists
        if ($this->testOrder) {
            OrderItem::where('order_id', $this->testOrder->id)->delete();
            OrderLog::where('order_id', $this->testOrder->id)->delete();
            Order::withTrashed()->where('id', $this->testOrder->id)->forceDelete();
        }

        // Clean up regular user
        if ($this->regularUser && $this->regularUser->exists) {
            $this->regularUser->delete();
        }

        parent::tearDown();
    }

    /**
     * Test non-super admin staff cannot access the trash page (403 Forbidden).
     */
    public function test_non_super_admin_cannot_access_order_trash_and_receives_403(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.orders.trash'));
        $response->assertStatus(403);

        $apiResponse = $this->actingAs($this->regularUser)->getJson(route('admin.orders.trash.fetch'));
        $apiResponse->assertStatus(403);
    }

    /**
     * Test super admin can access the trash page.
     */
    public function test_super_admin_can_access_order_trash_page(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.orders.trash'));
        $response->assertStatus(200);
        $response->assertSee('Order Trash');
        $response->assertSee('Super Admin Only');
    }

    /**
     * Test soft-deleted order does NOT show in customer portal or live tracking.
     */
    public function test_trashed_order_hidden_from_customer_portal_and_tracking(): void
    {
        // 1. Initially active order shows in customer orders and tracking
        $activeTrackRes = $this->getJson('/api/order-tracking/' . $this->testOrder->order_id);
        $activeTrackRes->assertStatus(200);

        // 2. Move order to trash (Soft Delete)
        $this->testOrder->delete();
        $this->assertSoftDeleted('orders', ['id' => $this->testOrder->id]);

        // 3. Customer portal should NOT show trashed order
        $customerPortalRes = $this->actingAs($this->regularUser)->get(route('dashboard.orders'));
        $customerPortalRes->assertStatus(200);
        $customerPortalRes->assertDontSee($this->testOrder->order_id);

        // 4. Customer order details page should return 404 for trashed order
        $customerOrderRes = $this->actingAs($this->regularUser)->get(route('dashboard.orders.show', $this->testOrder->order_id));
        $customerOrderRes->assertStatus(404);

        // 5. Live tracking should return 404
        $trackingRes = $this->getJson('/api/order-tracking/' . $this->testOrder->order_id);
        $trackingRes->assertStatus(404);
    }

    /**
     * Test soft-deleted order does NOT show in standard admin order list or global search.
     */
    public function test_trashed_order_hidden_from_standard_admin_orders_and_search(): void
    {
        $this->testOrder->delete();

        // Standard orders API fetch
        $adminFetchRes = $this->actingAs($this->superAdmin)->getJson(route('admin.orders.fetch', [
            'search' => $this->testOrder->order_id,
        ]));
        $adminFetchRes->assertStatus(200);
        $adminFetchRes->assertJsonMissing(['order_id' => $this->testOrder->order_id]);

        // Global search API
        $globalSearchRes = $this->actingAs($this->superAdmin)->getJson(route('admin.api.global-search', [
            'q' => $this->testOrder->order_id,
        ]));
        $globalSearchRes->assertStatus(200);
        $globalSearchRes->assertJsonMissing(['order_id' => $this->testOrder->order_id]);
    }

    /**
     * Test trashed order appears in Super Admin trash API query.
     */
    public function test_trashed_order_appears_in_trash_api_query(): void
    {
        $this->testOrder->delete();

        $trashApiRes = $this->actingAs($this->superAdmin)->getJson(route('admin.orders.trash.fetch', [
            'search' => $this->testOrder->order_id,
        ]));

        $trashApiRes->assertStatus(200);
        $trashApiRes->assertJsonFragment(['order_id' => $this->testOrder->order_id]);
    }

    /**
     * Test Super Admin can restore a trashed order back to active state.
     */
    public function test_super_admin_can_restore_trashed_order(): void
    {
        $this->testOrder->delete();
        $this->assertSoftDeleted('orders', ['id' => $this->testOrder->id]);

        $restoreRes = $this->actingAs($this->superAdmin)->postJson(route('admin.orders.restore', $this->testOrder->order_id));
        $restoreRes->assertStatus(200);
        $restoreRes->assertJson(['success' => true]);

        // Verify order is restored in database
        $this->assertNotSoftDeleted('orders', ['id' => $this->testOrder->id]);

        // Order is now visible in tracking again
        $trackRes = $this->getJson('/api/order-tracking/' . $this->testOrder->order_id);
        $trackRes->assertStatus(200);
    }

    /**
     * Test permanent force delete fails when Math CAPTCHA is wrong or missing.
     */
    public function test_force_delete_fails_with_invalid_math_captcha(): void
    {
        $this->testOrder->delete();

        // 1. Fetch CAPTCHA token
        $captchaRes = $this->actingAs($this->superAdmin)->getJson(route('admin.orders.trash.captcha'));
        $captchaRes->assertStatus(200);
        $question = $captchaRes->json('question');
        $this->assertMatchesRegularExpression('/^[1-9] \+ [1-9] = \?$/', $question);
        $token = $captchaRes->json('token');

        // 2. Submit wrong answer
        $wrongAnswerRes = $this->actingAs($this->superAdmin)->deleteJson(route('admin.orders.force-delete', $this->testOrder->order_id), [
            'captcha_answer' => 999999, // definitely wrong
            'captcha_token' => $token,
        ]);

        $wrongAnswerRes->assertStatus(422);
        $wrongAnswerRes->assertJson(['success' => false]);

        // Order still exists in trash
        $this->assertDatabaseHas('orders', ['id' => $this->testOrder->id]);
    }

    /**
     * Test permanent force delete succeeds when Math CAPTCHA is correct and wipes data permanently.
     */
    public function test_force_delete_succeeds_with_correct_math_captcha(): void
    {
        $this->testOrder->delete();
        $orderId = $this->testOrder->id;
        $orderCode = $this->testOrder->order_id;

        // 1. Create a valid CAPTCHA token with known answer
        $answer = 42;
        $token = Crypt::encryptString(json_encode([
            'answer' => $answer,
            'time' => time(),
            'user_id' => $this->superAdmin->id,
        ]));

        // 2. Submit with correct answer
        $deleteRes = $this->actingAs($this->superAdmin)->deleteJson(route('admin.orders.force-delete', $orderCode), [
            'captcha_answer' => $answer,
            'captcha_token' => $token,
        ]);

        $deleteRes->assertStatus(200);
        $deleteRes->assertJson(['success' => true]);

        // 3. Verify order is permanently removed from database (not even soft-deleted)
        $this->assertDatabaseMissing('orders', ['id' => $orderId]);
        $this->assertDatabaseMissing('order_items', ['order_id' => $orderId]);

        // Mark testOrder as null so tearDown doesn't complain
        $this->testOrder = null;
    }

    /**
     * Test Super Admin can restore using internal ID.
     */
    public function test_super_admin_can_restore_by_internal_id(): void
    {
        $this->testOrder->delete();
        $this->assertSoftDeleted('orders', ['id' => $this->testOrder->id]);

        $restoreRes = $this->actingAs($this->superAdmin)->postJson(route('admin.orders.restore', $this->testOrder->id));
        $restoreRes->assertStatus(200);
        $restoreRes->assertJson(['success' => true]);

        $this->assertNotSoftDeleted('orders', ['id' => $this->testOrder->id]);
    }

    /**
     * Test Super Admin can bulk restore multiple trashed orders.
     */
    public function test_super_admin_can_bulk_restore_trashed_orders(): void
    {
        // Create second test order
        $order2 = Order::create([
            'order_id' => 'TRASH-TEST-2-' . rand(100000, 999999),
            'user_id' => $this->regularUser->id,
            'total' => 2000.00,
            'status' => 'pending',
            'order_source' => 'website',
        ]);

        $this->testOrder->delete();
        $order2->delete();

        $this->assertSoftDeleted('orders', ['id' => $this->testOrder->id]);
        $this->assertSoftDeleted('orders', ['id' => $order2->id]);

        $bulkRes = $this->actingAs($this->superAdmin)->postJson(route('admin.orders.trash.bulk-restore'), [
            'order_ids' => [$this->testOrder->id, $order2->id],
        ]);

        $bulkRes->assertStatus(200);
        $bulkRes->assertJson(['success' => true, 'count' => 2]);

        $this->assertNotSoftDeleted('orders', ['id' => $this->testOrder->id]);
        $this->assertNotSoftDeleted('orders', ['id' => $order2->id]);

        // Cleanup second order
        $order2->forceDelete();
    }

    /**
     * Test bulk force delete fails with invalid math CAPTCHA.
     */
    public function test_bulk_force_delete_fails_with_invalid_math_captcha(): void
    {
        $this->testOrder->delete();

        $captchaRes = $this->actingAs($this->superAdmin)->getJson(route('admin.orders.trash.captcha'));
        $token = $captchaRes->json('token');

        $bulkDeleteRes = $this->actingAs($this->superAdmin)->deleteJson(route('admin.orders.trash.bulk-force-delete'), [
            'order_ids' => [$this->testOrder->id],
            'captcha_answer' => 1234567, // wrong answer
            'captcha_token' => $token,
        ]);

        $bulkDeleteRes->assertStatus(422);
        $this->assertDatabaseHas('orders', ['id' => $this->testOrder->id]);
    }

    /**
     * Test bulk force delete permanently wipes selected orders when CAPTCHA is correct.
     */
    public function test_bulk_force_delete_succeeds_with_correct_math_captcha(): void
    {
        $order2 = Order::create([
            'order_id' => 'TRASH-TEST-BULK-' . rand(100000, 999999),
            'user_id' => $this->regularUser->id,
            'total' => 500.00,
            'status' => 'pending',
            'order_source' => 'website',
        ]);

        $this->testOrder->delete();
        $order2->delete();

        $id1 = $this->testOrder->id;
        $id2 = $order2->id;

        $answer = 55;
        $token = Crypt::encryptString(json_encode([
            'answer' => $answer,
            'time' => time(),
            'user_id' => $this->superAdmin->id,
        ]));

        $bulkDeleteRes = $this->actingAs($this->superAdmin)->deleteJson(route('admin.orders.trash.bulk-force-delete'), [
            'order_ids' => [$id1, $id2],
            'captcha_answer' => $answer,
            'captcha_token' => $token,
        ]);

        $bulkDeleteRes->assertStatus(200);
        $bulkDeleteRes->assertJson(['success' => true, 'count' => 2]);

        $this->assertDatabaseMissing('orders', ['id' => $id1]);
        $this->assertDatabaseMissing('orders', ['id' => $id2]);

        $this->testOrder = null;
    }

    /**
     * Test product details shows Out of Stock when product stock is 0.
     */
    public function test_product_details_displays_out_of_stock_when_stock_is_zero(): void
    {
        $testProduct = Product::create([
            'title' => 'Zero Stock Product ' . uniqid(),
            'slug' => 'zero-stock-product-' . uniqid(),
            'base_price' => 500.00,
            'total_stock' => 0,
            'is_active' => true,
            'product_type' => 'simple',
        ]);
        ProductVariant::create([
            'product_id' => $testProduct->id,
            'sku' => 'ZERO-STOCK-' . uniqid(),
            'current_price' => 500.00,
            'stock_quantity' => 0,
        ]);

        $response = $this->get(route('product.show', $testProduct->slug));
        $response->assertStatus(200);
        $response->assertSee('Out of Stock');

        // Cleanup
        $testProduct->variants()->delete();
        $testProduct->delete();
    }
}
