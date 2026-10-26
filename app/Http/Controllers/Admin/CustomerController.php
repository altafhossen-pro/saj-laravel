<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class CustomerController extends Controller
{
    public function index()
    {
        return view('admin.customers.index', ['activeTab' => 'customers']);
    }

    public function staff()
    {
        // Just return the same view for now, maybe set a variable to filter by staff by default
        return view('admin.customers.index', ['activeTab' => 'staff']);
    }

    public function fetch(Request $request)
    {
        $query = User::with('roles');

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('tab') && $request->tab === 'staff') {
            $query->has('roles');
        } else {
            $query->doesntHave('roles');
        }

        if ($request->has('role') && $request->role !== '') {
            $query->role($request->role); // Spatie scope
        }

        $customers = $query->latest()->paginate(10);

        // Map Spatie roles to a display_role property
        $items = collect($customers->items())->map(function($user) {
            $roleName = $user->roles->first() ? $user->roles->first()->name : ($user->role ?: 'user');
            $user->display_role = str_replace('_', ' ', $roleName);
            return $user;
        });

        return response()->json([
            'customers' => $items,
            'pagination_html' => $customers->appends(request()->query())->links('vendor.pagination.tailwind')->render(),
            'pagination' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
            ]
        ]);
    }

    public function show(User $user)
    {
        $user->load(['roles']);

        $ordersQuery = $user->orders();
        $totalOrders = (clone $ordersQuery)->count();
        $totalSpent = (clone $ordersQuery)->whereNotIn('status', ['cancelled', 'returned'])->sum('total');
        $deliveredOrders = (clone $ordersQuery)->where('status', 'delivered')->count();
        $cancelledOrders = (clone $ordersQuery)->whereIn('status', ['cancelled', 'returned'])->count();
        $pendingOrders = (clone $ordersQuery)->whereIn('status', ['pending', 'confirmed', 'processing', 'shipped'])->count();

        $recentOrders = $user->orders()->latest()->paginate(10);

        // Latest shipping address from orders
        $latestOrderWithAddress = $user->orders()
            ->whereNotNull('shipping_address')
            ->latest()
            ->first();

        $shippingAddress = $latestOrderWithAddress ? $latestOrderWithAddress->shipping_address : null;

        $availableRoles = Role::pluck('name')->toArray();
        if (empty($availableRoles)) {
            $availableRoles = ['user', 'super_admin'];
        }

        $displayRole = $user->roles->first() ? $user->roles->first()->name : ($user->role ?: 'user');

        return view('admin.customers.show', compact(
            'user',
            'totalOrders',
            'totalSpent',
            'deliveredOrders',
            'cancelledOrders',
            'pendingOrders',
            'recentOrders',
            'shippingAddress',
            'availableRoles',
            'displayRole'
        ));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20|unique:users,phone,' . $user->id,
            'role' => 'nullable|string|max:50',
            'avatar' => 'nullable|image|max:2048',
            'password' => 'nullable|string|min:6',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;

        if ($request->has('email_verified')) {
            $user->email_verified_at = $request->boolean('email_verified') ? ($user->email_verified_at ?: now()) : null;
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            if ($user->avatar && !str_starts_with($user->avatar, 'http')) {
                $oldPath = str_replace('/storage/', '', $user->avatar);
                Storage::disk('public')->delete($oldPath);
            }
            $user->avatar = '/storage/' . $path;
        }

        // Update Spatie role & column role
        if ($request->filled('role')) {
            $newRole = $request->role;
            $user->role = $newRole;
            if ($newRole === 'user') {
                $user->syncRoles([]);
            } else {
                Role::firstOrCreate(['name' => $newRole]);
                $user->syncRoles([$newRole]);
            }
        }

        $user->save();

        return redirect()->route('admin.customers.show', $user->id)
            ->with('success', 'Customer profile updated successfully.');
    }

    public function updatePassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user->password = Hash::make($request->password);
        $user->save();

        // Dispatch security notification to customer
        try {
            if (\App\Services\MailConfigService::isMailEnabled() && !empty($user->email)) {
                \App\Services\MailConfigService::applyRuntimeConfig();
                \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\PasswordChangedMail($user, [
                    'changed_at' => now()->format('d M Y, h:i:s A'),
                    'ip' => $request->ip(),
                    'device' => 'Admin Security Console (Password Reset)',
                ]));

                \App\Models\EmailLog::record(
                    $user->email,
                    'Security Alert: Your Password Was Reset - ' . \App\Models\Setting::get('site_name', 'Ario Mart'),
                    'password_changed',
                    'sent',
                    null,
                    $user->name,
                    $user->id,
                    null,
                    'admin'
                );
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Admin Password Reset Mail Error: ' . $e->getMessage());
        }

        return redirect()->route('admin.customers.show', $user->id)
            ->with('success', 'Password updated successfully for ' . $user->name . '.');
    }

    public function loginAs(Request $request, User $user)
    {
        $currentAdmin = Auth::user();
        if (!$currentAdmin || !$currentAdmin->hasRole('super_admin')) {
            abort(403, 'Unauthorized action.');
        }

        session()->put('impersonator_admin_id', $currentAdmin->id);

        Auth::login($user);

        return redirect()->route('dashboard')
            ->with('success', 'You are now browsing as ' . $user->name . ' (Customer Impersonation)');
    }

    public function stopImpersonating(Request $request)
    {
        if (session()->has('impersonator_admin_id')) {
            $adminId = session()->pull('impersonator_admin_id');
            $admin = User::find($adminId);
            if ($admin) {
                Auth::login($admin);
                return redirect()->route('admin.customers')
                    ->with('success', 'Returned to Admin Panel successfully.');
            }
        }

        return redirect()->route('home');
    }
}

