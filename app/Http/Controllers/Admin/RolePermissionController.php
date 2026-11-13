<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    /**
     * Standard grouped permissions mapping for the e-commerce store.
     */
    public const PERMISSION_GROUPS = [
        'Products & Catalog' => [
            'icon' => 'ph-package',
            'permissions' => [
                'view_products' => 'View Products (Browse product catalog, view details & stock)',
                'create_products' => 'Create Products (Add new products, upload images & set pricing)',
                'edit_products' => 'Edit Products (Update product details, variants, stock & pricing)',
                'delete_products' => 'Delete Products (Permanently delete or archive products)',
                'manage_categories' => 'Manage Categories (Create, edit category hierarchy & displays)',
                'manage_brands' => 'Manage Brands (Create & manage product brands)',
                'manage_reviews' => 'Manage Reviews & Q&A (Moderate customer reviews and product questions)',
            ],
        ],
        'Orders & Deliveries' => [
            'icon' => 'ph-shopping-cart',
            'permissions' => [
                'manage_orders' => 'Manage Orders (View, edit status, shipping address & payments)',
                'create_orders' => 'Manual Orders (Create manual customer orders from admin panel)',
                'delete_orders' => 'Delete / Cancel Orders (Permanently remove or cancel orders)',
                'manage_courier' => 'Courier & Shipping (Steadfast consignment booking & shipping rates)',
            ],
        ],
        'Customers & Staff' => [
            'icon' => 'ph-users',
            'permissions' => [
                'manage_customers' => 'Manage Customers (View customer profiles, order history & addresses)',
                'manage_staff' => 'Manage Staff & Roles (Manage administrative staff accounts & assign roles)',
            ],
        ],
        'Marketing & Offers' => [
            'icon' => 'ph-tag',
            'permissions' => [
                'manage_coupons' => 'Coupons & Discounts (Create, edit and track coupon usage)',
                'manage_offers' => 'Category Offers & Templates (Discount popups, promotional banners)',
                'manage_homepage' => 'Homepage Layout (Hero sliders, trending products, showcase sections)',
            ],
        ],
        'Analytics & Visitors' => [
            'icon' => 'ph-chart-line-up',
            'permissions' => [
                'view_analytics' => 'Analytics Dashboard (Sales, revenue, searches & visitor statistics)',
                'view_visitors' => 'Live Shoppers & Carts (Live visitor monitor, footprints & abandoned carts)',
            ],
        ],
        'Settings & System' => [
            'icon' => 'ph-gear',
            'permissions' => [
                'manage_settings' => 'Store Settings (Branding, site information, custom scripts & APIs)',
                'manage_database' => 'Database & System Logs (Create backups, view server error logs)',
            ],
        ],
    ];

    /**
     * Display the Roles & Permissions management page.
     */
    public function index()
    {
        $this->ensurePermissionsExist();

        $roles = Role::with('permissions')
            ->withCount('users')
            ->orderBy('id', 'asc')
            ->get();

        $permissionGroups = self::PERMISSION_GROUPS;
        $allPermissions = Permission::orderBy('name', 'asc')->get();

        return view('admin.settings.roles.index', compact('roles', 'permissionGroups', 'allPermissions'));
    }

    /**
     * Store a new role.
     */
    public function storeRole(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'is_customer' => 'nullable|boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $cleanName = Str::slug($request->name, '_');

        if (empty($cleanName)) {
            return back()->with('error', 'Please provide a valid role name.');
        }

        if (Role::where('name', $cleanName)->exists()) {
            return back()->with('error', "A role with the name '{$cleanName}' already exists.");
        }

        $isCustomer = (bool) $request->boolean('is_customer');

        $role = Role::create([
            'name' => $cleanName,
            'guard_name' => 'web',
            'is_customer' => $isCustomer,
        ]);

        // If it's a customer role, it must never have administrative permissions
        if ($isCustomer) {
            $role->syncPermissions([]);
        } elseif ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        $roleTypeMsg = $isCustomer ? 'Customer Role (no admin access)' : 'Staff Role with ' . count($request->permissions ?? []) . ' permissions';

        return redirect()->route('admin.settings.roles.index')
            ->with('success', "Role '{$role->name}' created successfully as {$roleTypeMsg}.");
    }

    /**
     * Update an existing role.
     */
    public function updateRole(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'is_customer' => 'nullable|boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $cleanName = Str::slug($request->name, '_');
        $isCustomer = (bool) $request->boolean('is_customer');

        // Prevent modifying super_admin identifier or making it a customer
        if ($role->name === 'super_admin') {
            $cleanName = 'super_admin';
            $isCustomer = false;
        } else {
            // Check if name is taken by another role
            $exists = Role::where('name', $cleanName)->where('id', '!=', $role->id)->exists();
            if ($exists) {
                return back()->with('error', "Another role named '{$cleanName}' already exists.");
            }
            $role->name = $cleanName;
        }

        $role->is_customer = $isCustomer;
        $role->save();

        // Permissions sync logic:
        // 1. Customer roles must NEVER have admin permissions
        // 2. super_admin always maintains all permissions
        // 3. Other staff roles get selected permissions
        if ($isCustomer) {
            $role->syncPermissions([]);
        } elseif ($role->name === 'super_admin') {
            $role->syncPermissions(Permission::all());
        } else {
            $role->syncPermissions($request->permissions ?? []);
        }

        return redirect()->route('admin.settings.roles.index')
            ->with('success', "Role '{$role->name}' updated successfully.");
    }

    /**
     * Delete a role.
     */
    public function destroyRole(Role $role)
    {
        if ($role->name === 'super_admin') {
            return back()->with('error', 'The super_admin role is protected and cannot be deleted.');
        }

        $userCount = $role->users()->count();
        if ($userCount > 0) {
            return back()->with('error', "Cannot delete role '{$role->name}' because it is assigned to {$userCount} staff member(s). Please reassign them first.");
        }

        $roleName = $role->name;
        $role->delete();

        return redirect()->route('admin.settings.roles.index')
            ->with('success', "Role '{$roleName}' deleted successfully.");
    }

    /**
     * Ensure all predefined module permissions exist in the database.
     */
    protected function ensurePermissionsExist(): void
    {
        foreach (self::PERMISSION_GROUPS as $group) {
            foreach (array_keys($group['permissions']) as $permissionName) {
                Permission::firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                ]);
            }
        }

        // Always keep super_admin synced with all permissions
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }
    }
}
