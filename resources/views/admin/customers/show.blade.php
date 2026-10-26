@extends('layouts.admin')

@section('title', 'Customer Details - ' . $user->name . ' - Admin Panel')
@section('header_title', 'Customer Details')

@section('content')
<div x-data="{
    editModal: false,
    passwordModal: false,
    loginModal: false,
    avatarPreview: null,
    fileChosen(event) {
        const file = event.target.files[0];
        if (file) {
            this.avatarPreview = URL.createObjectURL(file);
        }
    }
}">

    <!-- Top Navigation / Breadcrumb & Header -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
        <div>
            <a href="{{ route('admin.customers') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600 hover:text-brand-700 transition-colors mb-2">
                <i class="ph ph-arrow-left text-base"></i>
                <span>Back to Customers</span>
            </a>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold text-gray-900">{{ $user->name }}</h1>
                <span class="text-sm font-mono text-gray-400 bg-gray-100 px-2 py-0.5 rounded-md">#{{ $user->id }}</span>
                
                @if($user->google_id)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200" title="Registered with Google OAuth">
                        <i class="ph-bold ph-google-logo text-sm"></i>
                        <span>Google Account</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200" title="Direct Email/Password Account">
                        <i class="ph-bold ph-shield-check text-sm"></i>
                        <span>Custom Account</span>
                    </span>
                @endif

                @if($displayRole === 'super_admin' || $displayRole === 'super admin' || $displayRole === 'admin')
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-brand-100 text-brand-700 capitalize">
                        {{ str_replace('_', ' ', $displayRole) }}
                    </span>
                @else
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 capitalize">
                        Customer
                    </span>
                @endif
            </div>
            <p class="text-sm text-gray-500 mt-1">
                Joined on {{ $user->created_at ? $user->created_at->format('F d, Y \a\t h:i A') : 'N/A' }} 
                @if($user->created_at)
                    ({{ $user->created_at->diffForHumans() }})
                @endif
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto">
            <!-- Login As Customer Button -->
            <button type="button" @click="loginModal = true" 
                    class="px-4 py-2 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl text-sm font-semibold hover:bg-emerald-100 hover:border-emerald-300 transition-all cursor-pointer flex items-center gap-1.5 shadow-sm active:scale-95"
                    title="Login as this customer in impersonation mode">
                <i class="ph-bold ph-sign-in text-base"></i>
                <span>Login as Customer</span>
            </button>

            <!-- Change Password Button (Only for Custom Login) -->
            @if(empty($user->google_id))
                <button type="button" @click="passwordModal = true" 
                        class="px-4 py-2 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl text-sm font-semibold hover:bg-amber-100 hover:border-amber-300 transition-all cursor-pointer flex items-center gap-1.5 shadow-sm active:scale-95">
                    <i class="ph-bold ph-key text-base"></i>
                    <span>Change Password</span>
                </button>
            @else
                <div class="px-3 py-2 bg-gray-50 border border-gray-200 text-gray-500 rounded-xl text-xs font-medium flex items-center gap-1.5 cursor-help" 
                     title="This customer logged in via Google OAuth. Direct password is not used.">
                    <i class="ph ph-info text-sm text-gray-400"></i>
                    <span>Google Auth (No Password)</span>
                </div>
            @endif

            <!-- Edit User Button -->
            <button type="button" @click="editModal = true" 
                    class="px-4 py-2 bg-brand-600 border border-brand-600 text-white rounded-xl text-sm font-semibold hover:bg-brand-700 transition-all cursor-pointer flex items-center gap-1.5 shadow-sm shadow-brand-500/20 active:scale-95">
                <i class="ph-bold ph-pencil-simple text-base"></i>
                <span>Edit User</span>
            </button>
        </div>
    </div>

    <!-- Error Messages Display -->
    @if(isset($errors) && $errors->any())
        <div class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-900 text-sm shadow-sm">
            <div class="font-bold flex items-center gap-2 mb-2 text-red-700">
                <i class="ph-bold ph-warning-circle text-xl"></i>
                <span>There were errors with your submission:</span>
            </div>
            <ul class="list-disc list-inside space-y-1 text-red-700 pl-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Metrics Stats Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
        <!-- Card 1: Total Orders -->
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center shrink-0">
                <i class="ph-bold ph-shopping-bag text-2xl"></i>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Orders</p>
                <h3 class="text-xl font-bold text-gray-900 mt-0.5">{{ $totalOrders }}</h3>
            </div>
        </div>

        <!-- Card 2: Total Spent -->
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <i class="ph-bold ph-wallet text-2xl"></i>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Spent</p>
                <h3 class="text-xl font-bold text-gray-900 mt-0.5">৳{{ number_format($totalSpent, 2) }}</h3>
            </div>
        </div>

        <!-- Card 3: Delivered Orders -->
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <i class="ph-bold ph-check-circle text-2xl"></i>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Delivered Orders</p>
                <h3 class="text-xl font-bold text-gray-900 mt-0.5">{{ $deliveredOrders }}</h3>
            </div>
        </div>

        <!-- Card 4: Active / In-Transit -->
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                <i class="ph-bold ph-hourglass-medium text-2xl"></i>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">In-Progress</p>
                <h3 class="text-xl font-bold text-gray-900 mt-0.5">{{ $pendingOrders }}</h3>
            </div>
        </div>
    </div>

    <!-- Main Grid: Left Profile Card + Right Order History -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left Column: User Profile & Shipping Address -->
        <div class="space-y-6">
            
            <!-- User Info Card -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gradient-to-br from-gray-50/50 to-white flex flex-col items-center text-center">
                    <!-- Avatar -->
                    <div class="relative mb-3">
                        @if($user->avatar)
                            <img src="{{ str_starts_with($user->avatar, 'http') ? $user->avatar : (str_starts_with($user->avatar, '/storage') ? $user->avatar : '/storage/' . $user->avatar) }}" 
                                 alt="{{ $user->name }}" 
                                 class="w-20 h-20 rounded-full object-cover border-4 border-white shadow-md ring-1 ring-gray-100">
                        @else
                            <div class="w-20 h-20 rounded-full bg-brand-100 border-4 border-white shadow-md ring-1 ring-gray-100 flex items-center justify-center text-brand-600 font-extrabold text-2xl">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                        @endif

                        @if($user->email_verified_at)
                            <div class="absolute bottom-0 right-0 w-6 h-6 rounded-full bg-emerald-500 text-white flex items-center justify-center ring-2 ring-white" title="Email Verified">
                                <i class="ph-bold ph-check text-xs"></i>
                            </div>
                        @endif
                    </div>

                    <h2 class="text-lg font-bold text-gray-900">{{ $user->name }}</h2>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $user->email }}</p>

                    <div class="mt-4 flex flex-wrap justify-center gap-2">
                        @if($user->email_verified_at)
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                                <i class="ph-fill ph-check-circle text-xs"></i> Verified
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700">
                                <i class="ph-fill ph-clock text-xs"></i> Unverified
                            </span>
                        @endif

                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 capitalize">
                            {{ str_replace('_', ' ', $displayRole) }}
                        </span>
                    </div>
                </div>

                <!-- Info Details Table -->
                <div class="p-6 divide-y divide-gray-100 text-sm">
                    <div class="py-3 first:pt-0 flex justify-between items-center gap-2">
                        <span class="text-gray-500 flex items-center gap-2">
                            <i class="ph ph-envelope text-gray-400"></i> Email:
                        </span>
                        <span class="font-medium text-gray-900 break-all text-right">{{ $user->email }}</span>
                    </div>
                    <div class="py-3 flex justify-between items-center gap-2">
                        <span class="text-gray-500 flex items-center gap-2">
                            <i class="ph ph-phone text-gray-400"></i> Phone:
                        </span>
                        <span class="font-medium text-gray-900 text-right">{{ $user->phone ?: 'Not provided' }}</span>
                    </div>
                    <div class="py-3 flex justify-between items-center gap-2">
                        <span class="text-gray-500 flex items-center gap-2">
                            <i class="ph ph-shield text-gray-400"></i> Login Method:
                        </span>
                        <span class="font-medium text-gray-900 text-right">
                            @if($user->google_id)
                                Google OAuth
                            @else
                                Custom Password
                            @endif
                        </span>
                    </div>
                    <div class="py-3 flex justify-between items-center gap-2">
                        <span class="text-gray-500 flex items-center gap-2">
                            <i class="ph ph-calendar text-gray-400"></i> Joined:
                        </span>
                        <span class="font-medium text-gray-900 text-right">
                            {{ $user->created_at ? $user->created_at->format('M d, Y') : 'N/A' }}
                        </span>
                    </div>
                    <div class="py-3 last:pb-0 flex justify-between items-center gap-2">
                        <span class="text-gray-500 flex items-center gap-2">
                            <i class="ph ph-clock-counter-clockwise text-gray-400"></i> Last Updated:
                        </span>
                        <span class="font-medium text-gray-900 text-right">
                            {{ $user->updated_at ? $user->updated_at->format('M d, Y') : 'N/A' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Shipping / Delivery Address Card -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <i class="ph ph-map-pin text-brand-600 text-lg"></i>
                        <span>Recent Delivery Address</span>
                    </h3>
                </div>

                @if($shippingAddress && is_array($shippingAddress))
                    <div class="bg-gray-50/70 rounded-xl p-4 border border-gray-100 space-y-2 text-sm">
                        @if(!empty($shippingAddress['name']))
                            <div class="flex items-center gap-2 text-gray-900 font-semibold">
                                <i class="ph ph-user text-gray-400"></i>
                                <span>{{ $shippingAddress['name'] }}</span>
                            </div>
                        @endif

                        @if(!empty($shippingAddress['phone']))
                            <div class="flex items-center gap-2 text-gray-600">
                                <i class="ph ph-phone text-gray-400"></i>
                                <span>{{ $shippingAddress['phone'] }}</span>
                            </div>
                        @endif

                        @if(!empty($shippingAddress['address']))
                            <div class="flex items-start gap-2 text-gray-600">
                                <i class="ph ph-house text-gray-400 mt-0.5"></i>
                                <span>{{ $shippingAddress['address'] }}</span>
                            </div>
                        @endif

                        @if(!empty($shippingAddress['city']) || !empty($shippingAddress['area']) || !empty($shippingAddress['zone']))
                            <div class="text-xs text-gray-500 pt-1 border-t border-gray-200/60">
                                <span>{{ $shippingAddress['area'] ?? '' }}</span>
                                @if(!empty($shippingAddress['city']))
                                    <span>{{ !empty($shippingAddress['area']) ? ', ' : '' }}{{ $shippingAddress['city'] }}</span>
                                @endif
                            </div>
                        @endif
                    </div>
                @else
                    <div class="text-center py-6 text-gray-400">
                        <i class="ph ph-map-pin-line text-3xl mb-1 text-gray-300"></i>
                        <p class="text-xs font-medium text-gray-500">No delivery address saved from orders yet.</p>
                    </div>
                @endif
            </div>

        </div>

        <!-- Right Column: Order History Table -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <i class="ph ph-receipt text-brand-600 text-xl"></i>
                            <span>Orders History</span>
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">Showing all orders placed by this customer</p>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 bg-gray-100 text-gray-700 rounded-full">
                        {{ $recentOrders->total() }} Total Orders
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-gray-50 text-gray-500 font-medium border-b border-gray-100 uppercase text-xs tracking-wider">
                            <tr>
                                <th class="px-6 py-4">Order ID</th>
                                <th class="px-6 py-4">Date</th>
                                <th class="px-6 py-4">Total</th>
                                <th class="px-6 py-4">Payment</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($recentOrders as $order)
                                <tr class="hover:bg-gray-50/70 transition-colors">
                                    <td class="px-6 py-4 font-mono font-medium text-gray-900">
                                        <a href="{{ route('admin.orders.show', $order->order_id) }}" class="text-brand-600 hover:text-brand-800 hover:underline">
                                            #{{ $order->order_id }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 text-gray-500 text-xs">
                                        {{ $order->created_at->format('M d, Y') }}
                                        <div class="text-[11px] text-gray-400">{{ $order->created_at->format('h:i A') }}</div>
                                    </td>
                                    <td class="px-6 py-4 font-bold text-gray-900">
                                        ৳{{ number_format($order->total, 2) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-xs font-medium text-gray-700 capitalize">
                                            {{ $order->payment_method ?: 'Cash on Delivery' }}
                                        </div>
                                        <span class="text-[10px] uppercase font-bold {{ $order->payment_status === 'paid' ? 'text-emerald-600' : 'text-amber-600' }}">
                                            {{ $order->payment_status ?: 'Pending' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        @php
                                            $status = strtolower($order->status);
                                            $badgeColors = [
                                                'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
                                                'confirmed' => 'bg-blue-50 text-blue-700 border-blue-200',
                                                'processing' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                                'shipped' => 'bg-purple-50 text-purple-700 border-purple-200',
                                                'delivered' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                                'cancelled' => 'bg-red-50 text-red-700 border-red-200',
                                                'returned' => 'bg-rose-50 text-rose-700 border-rose-200',
                                            ];
                                            $badgeClass = $badgeColors[$status] ?? 'bg-gray-100 text-gray-700 border-gray-200';
                                        @endphp
                                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold border capitalize {{ $badgeClass }}">
                                            {{ $order->status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('admin.orders.show', $order->order_id) }}" 
                                           class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1.5 rounded-lg bg-gray-50 text-gray-700 border border-gray-200 hover:bg-brand-50 hover:text-brand-600 hover:border-brand-200 transition-colors"
                                           title="View Order Details">
                                            <i class="ph ph-eye text-sm"></i>
                                            <span>View</span>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                        <div class="flex flex-col items-center justify-center">
                                            <i class="ph ph-shopping-bag-open text-4xl text-gray-300 mb-2"></i>
                                            <p class="text-sm font-semibold text-gray-700">No orders placed yet</p>
                                            <p class="text-xs text-gray-400 mt-0.5">When this customer places an order, it will appear here.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($recentOrders->hasPages())
                    <div class="p-4 border-t border-gray-100">
                        {{ $recentOrders->links() }}
                    </div>
                @endif
            </div>
        </div>

    </div>

    <!-- Edit User Modal -->
    <div x-show="editModal" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         style="display: none;"
         @keydown.escape.window="editModal = false">
        
        <!-- Backdrop -->
        <div x-show="editModal" 
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="editModal = false"
             class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>

        <!-- Dialog Container -->
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="editModal" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                 @click.stop
                 class="relative bg-white rounded-2xl shadow-2xl border border-gray-100 max-w-lg w-full overflow-hidden my-8 z-10">
                
                <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                    <div class="flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center font-bold">
                            <i class="ph-bold ph-pencil-simple text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Edit Customer Profile</h3>
                            <p class="text-xs text-gray-500">Update customer data, role, and details</p>
                        </div>
                    </div>
                    <button type="button" @click="editModal = false" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
                        <i class="ph ph-x text-lg"></i>
                    </button>
                </div>

                <form action="{{ route('admin.customers.update', $user->id) }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                    @csrf
                    @method('PUT')

                    <!-- Name -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Full Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                               class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-all">
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Email Address <span class="text-red-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                               class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-all">
                    </div>

                    <!-- Phone -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Phone Number</label>
                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="e.g. 01712345678"
                               class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-all">
                    </div>

                    <!-- Role -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Role / Account Type</label>
                        <select name="role" class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-all bg-white">
                            <option value="user" {{ $displayRole === 'user' ? 'selected' : '' }}>Customer (Regular User)</option>
                            <option value="super_admin" {{ ($displayRole === 'super_admin' || $displayRole === 'super admin') ? 'selected' : '' }}>Super Admin (Staff)</option>
                        </select>
                    </div>

                    <!-- Avatar Upload -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Profile Picture / Avatar</label>
                        <div class="flex items-center gap-4">
                            <template x-if="avatarPreview">
                                <img :src="avatarPreview" class="w-12 h-12 rounded-full object-cover border border-gray-200">
                            </template>
                            <template x-if="!avatarPreview">
                                @if($user->avatar)
                                    <img src="{{ str_starts_with($user->avatar, 'http') ? $user->avatar : (str_starts_with($user->avatar, '/storage') ? $user->avatar : '/storage/' . $user->avatar) }}" class="w-12 h-12 rounded-full object-cover border border-gray-200">
                                @else
                                    <div class="w-12 h-12 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center font-bold text-base">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                @endif
                            </template>
                            <input type="file" name="avatar" accept="image/*" @change="fileChosen"
                                   class="text-xs text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 cursor-pointer">
                        </div>
                    </div>

                    <!-- Optional: Reset Password inline -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">New Password (Optional)</label>
                        <input type="password" name="password" placeholder="Leave empty to keep current password"
                               class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-all">
                        <p class="text-[11px] text-gray-400 mt-1">Provide at least 6 characters if you want to change it.</p>
                    </div>

                    <!-- Email Verified Checkbox -->
                    <div class="flex items-center gap-2 pt-2">
                        <input type="checkbox" id="email_verified" name="email_verified" value="1" {{ $user->email_verified_at ? 'checked' : '' }}
                               class="rounded text-brand-600 focus:ring-brand-500 w-4 h-4 border-gray-300">
                        <label for="email_verified" class="text-xs font-medium text-gray-700 cursor-pointer">
                            Mark email as verified
                        </label>
                    </div>

                    <!-- Modal Actions -->
                    <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-3">
                        <button type="button" @click="editModal = false" class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-colors cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-600 text-sm font-semibold text-white hover:bg-brand-700 transition-colors shadow-sm shadow-brand-500/20 cursor-pointer flex items-center gap-2">
                            <i class="ph-bold ph-check text-base"></i>
                            <span>Save Changes</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div x-show="passwordModal" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         style="display: none;"
         @keydown.escape.window="passwordModal = false">
        
        <!-- Backdrop -->
        <div x-show="passwordModal" 
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="passwordModal = false"
             class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>

        <!-- Dialog Container -->
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="passwordModal" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                 @click.stop
                 class="relative bg-white rounded-2xl shadow-2xl border border-gray-100 max-w-md w-full overflow-hidden my-8 z-10">
                
                <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-amber-50/50">
                    <div class="flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold">
                            <i class="ph-bold ph-key text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Change Password</h3>
                            <p class="text-xs text-gray-500">Set a new login password for {{ $user->name }}</p>
                        </div>
                    </div>
                    <button type="button" @click="passwordModal = false" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
                        <i class="ph ph-x text-lg"></i>
                    </button>
                </div>

                <form action="{{ route('admin.customers.password.update', $user->id) }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">New Password <span class="text-red-500">*</span></label>
                        <input type="password" name="password" required minlength="6" placeholder="Enter new password (min. 6 chars)"
                               class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Confirm Password <span class="text-red-500">*</span></label>
                        <input type="password" name="password_confirmation" required minlength="6" placeholder="Re-type new password"
                               class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-all">
                    </div>

                    <div class="p-3 rounded-xl bg-amber-50 border border-amber-200/60 text-xs text-amber-800 flex items-start gap-2">
                        <i class="ph-bold ph-shield-warning text-base text-amber-600 shrink-0 mt-0.5"></i>
                        <span>Updating this will allow the customer to immediately log in with this new password.</span>
                    </div>

                    <!-- Modal Actions -->
                    <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-3">
                        <button type="button" @click="passwordModal = false" class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-colors cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-amber-600 text-sm font-semibold text-white hover:bg-amber-700 transition-colors shadow-sm shadow-amber-500/20 cursor-pointer flex items-center gap-2">
                            <i class="ph-bold ph-check text-base"></i>
                            <span>Update Password</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Login as Customer Custom Confirmation Modal -->
    <div x-show="loginModal" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         style="display: none;"
         @keydown.escape.window="loginModal = false">
        
        <!-- Backdrop -->
        <div x-show="loginModal" 
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="loginModal = false"
             class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>

        <!-- Dialog Container -->
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="loginModal" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                 @click.stop
                 class="relative bg-white rounded-3xl shadow-2xl border border-emerald-100 max-w-md w-full overflow-hidden my-8 z-10">
                
                <!-- Decorative Top Header -->
                <div class="h-28 bg-gradient-to-br from-emerald-50 via-emerald-100/40 to-white flex items-center justify-center pt-2">
                    <div class="w-16 h-16 rounded-2xl bg-white shadow-lg shadow-emerald-600/10 border border-emerald-100 flex items-center justify-center text-emerald-600">
                        <i class="ph-bold ph-user-switch text-3xl"></i>
                    </div>
                </div>

                <div class="px-6 pb-6 pt-2 text-center">
                    <h3 class="text-xl font-bold text-gray-900">Login as Customer?</h3>
                    <p class="text-sm text-gray-500 mt-2 leading-relaxed">
                        You are about to browse the site as <strong class="text-gray-900 font-semibold">{{ $user->name }}</strong>. You will be able to view their orders, profile, and cart exactly as they see it.
                    </p>
                    <div class="mt-4 p-3 bg-emerald-50/70 border border-emerald-200/60 rounded-xl text-xs text-emerald-800 text-left flex items-start gap-2.5">
                        <i class="ph-bold ph-shield-check text-base text-emerald-600 shrink-0 mt-0.5"></i>
                        <span>An amber bar will remain visible at the top, allowing you to return to the Admin Panel at any time with one click.</span>
                    </div>

                    <div class="mt-6 flex items-center gap-3">
                        <button type="button" @click="loginModal = false" 
                                class="flex-1 py-2.5 px-4 rounded-xl border border-gray-200 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer">
                            Cancel
                        </button>
                        <form action="{{ route('admin.customers.login_as', $user->id) }}" method="POST" class="flex-1">
                            @csrf
                            <button type="submit" 
                                    class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-md shadow-emerald-600/20 transition-all cursor-pointer flex items-center justify-center gap-2">
                                <i class="ph-bold ph-sign-in text-base"></i>
                                <span>Yes, Login</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
