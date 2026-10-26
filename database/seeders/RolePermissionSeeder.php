<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create predefined roles
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        // Create some basic permissions (dynamic role and permission setup)
        $permissions = [
            'manage_users',
            'manage_roles',
            'manage_products',
            'manage_orders'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign all permissions to super_admin
        $superAdminRole->syncPermissions(Permission::all());

        // Assign super_admin role to the specific user
        $user = User::where('email', 'altafhossen.pro@gmail.com')->first();
        if ($user) {
            $user->assignRole($superAdminRole);
        }
    }
}
