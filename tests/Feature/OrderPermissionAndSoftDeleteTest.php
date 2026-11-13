<?php

namespace Tests\Feature;

use App\Models\Order\Order;
use App\Models\Order\OrderLog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderPermissionAndSoftDeleteTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure permissions exist
        app(\App\Http\Controllers\Admin\RolePermissionController::class)->index();
    }

    /**
     * Test Case 1: Granular order permissions exist in database
     */
    public function test_granular_order_permissions_exist_in_database(): void
    {
        $requiredPermissions = [
            'view_orders',
            'create_manual_orders',
            'edit_orders',
            'manage_order_status',
            'cancel_orders',
            'delete_orders',
            'manage_courier',
        ];

        foreach ($requiredPermissions as $perm) {
            $this->assertTrue(
                Permission::where('name', $perm)->where('guard_name', 'web')->exists(),
                "Permission '{$perm}' should exist in database."
            );
        }
    }

    /**
     * Test Case 2: Staff without view_orders gets 403 on order list
     */
    public function test_staff_without_view_orders_is_denied(): void
    {
        $staffUser = User::factory()->create([
            'email' => 'restricted_staff_' . uniqid() . '@example.com',
        ]);

        $role = Role::firstOrCreate(['name' => 'test_limited_staff_' . uniqid(), 'guard_name' => 'web', 'is_customer' => false]);
        $role->syncPermissions(['view_products']); // no view_orders
        $staffUser->syncRoles([$role]);

        $response = $this->actingAs($staffUser)->get(route('admin.orders'));
        $response->assertStatus(403);
    }

    /**
     * Test Case 3: Staff with view_orders can view orders page
     */
    public function test_staff_with_view_orders_can_access(): void
    {
        $staffUser = User::factory()->create([
            'email' => 'viewer_staff_' . uniqid() . '@example.com',
        ]);

        $role = Role::firstOrCreate(['name' => 'test_order_viewer_' . uniqid(), 'guard_name' => 'web', 'is_customer' => false]);
        $role->syncPermissions(['view_orders']);
        $staffUser->syncRoles([$role]);

        $response = $this->actingAs($staffUser)->get(route('admin.orders'));
        $response->assertStatus(200);
    }

    /**
     * Test Case 4: Staff without delete_orders cannot delete order (403 Forbidden)
     */
    public function test_staff_without_delete_orders_cannot_delete(): void
    {
        $staffUser = User::factory()->create([
            'email' => 'no_delete_staff_' . uniqid() . '@example.com',
        ]);

        $role = Role::firstOrCreate(['name' => 'test_no_delete_' . uniqid(), 'guard_name' => 'web', 'is_customer' => false]);
        $role->syncPermissions(['view_orders', 'edit_orders']); // no delete_orders
        $staffUser->syncRoles([$role]);

        $testOrder = Order::create([
            'order_id' => 'ORD-TEST-' . uniqid(),
            'total' => 1500,
            'status' => 'pending',
            'shipping_address' => ['name' => 'Test Customer', 'phone' => '01700000000'],
        ]);

        $response = $this->actingAs($staffUser)
            ->deleteJson(route('admin.orders.destroy', $testOrder->order_id));

        $response->assertStatus(403);

        // Verify order is NOT soft deleted
        $this->assertNull(Order::withTrashed()->where('id', $testOrder->id)->value('deleted_at'));
    }

    /**
     * Test Case 5: Staff with delete_orders can soft delete order
     */
    public function test_staff_with_delete_orders_can_soft_delete(): void
    {
        $staffUser = User::factory()->create([
            'name' => 'Delete Staff',
            'email' => 'delete_staff_' . uniqid() . '@example.com',
        ]);

        $role = Role::firstOrCreate(['name' => 'test_can_delete_' . uniqid(), 'guard_name' => 'web', 'is_customer' => false]);
        $role->syncPermissions(['view_orders', 'delete_orders']);
        $staffUser->syncRoles([$role]);

        $testOrder = Order::create([
            'order_id' => 'ORD-TEST-' . uniqid(),
            'total' => 2500,
            'status' => 'pending',
            'shipping_address' => ['name' => 'Soft Delete Customer', 'phone' => '01800000000'],
        ]);

        $response = $this->actingAs($staffUser)
            ->deleteJson(route('admin.orders.destroy', $testOrder->order_id));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        // Verify soft delete in database
        $this->assertNull(Order::find($testOrder->id), 'Soft deleted order should NOT be returned by Order::find()');

        $trashedOrder = Order::withTrashed()->find($testOrder->id);
        $this->assertNotNull($trashedOrder, 'Order record must still exist in table');
        $this->assertNotNull($trashedOrder->deleted_at, 'deleted_at must be populated');

        // Verify order log
        $logExists = OrderLog::where('order_id', $testOrder->id)
            ->where('action', 'like', '%moved to trash (Soft Deleted)%')
            ->exists();
        $this->assertTrue($logExists, 'OrderLog must record the soft delete action');
    }

    /**
     * Test Case 6: manage_order_status vs cancel_orders permission gating
     */
    public function test_status_update_vs_cancel_order_permissions(): void
    {
        $staffUser = User::factory()->create([
            'email' => 'status_staff_' . uniqid() . '@example.com',
        ]);

        // Role with ONLY manage_order_status (NOT cancel_orders)
        $role = Role::firstOrCreate(['name' => 'test_status_only_' . uniqid(), 'guard_name' => 'web', 'is_customer' => false]);
        $role->syncPermissions(['view_orders', 'manage_order_status']);
        $staffUser->syncRoles([$role]);

        $testOrder = Order::create([
            'order_id' => 'ORD-TEST-' . uniqid(),
            'total' => 1200,
            'status' => 'pending',
            'shipping_address' => ['name' => 'Status Customer', 'phone' => '01900000000'],
        ]);

        // Attempt to cancel should be blocked with 403
        $cancelResponse = $this->actingAs($staffUser)
            ->postJson(route('admin.orders.update-status', $testOrder->order_id), [
                'status' => 'cancelled',
            ]);
        $cancelResponse->assertStatus(403);

        // Attempt to confirm should succeed with 200
        $confirmResponse = $this->actingAs($staffUser)
            ->postJson(route('admin.orders.update-status', $testOrder->order_id), [
                'status' => 'confirmed',
            ]);
        $confirmResponse->assertStatus(200);

        // Now assign cancel_orders permission
        $role->givePermissionTo('cancel_orders');

        $nowCancelResponse = $this->actingAs($staffUser)
            ->postJson(route('admin.orders.update-status', $testOrder->order_id), [
                'status' => 'cancelled',
            ]);
        $nowCancelResponse->assertStatus(200);
    }
}
