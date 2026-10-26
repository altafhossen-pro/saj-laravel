@extends('layouts.app')

@section('title', 'My Dashboard - ' . \App\Models\Setting::get('site_name', 'SAJ'))

@section('content')
<div class="w-full px-4 lg:px-8 py-4 md:py-8 min-h-screen">
    
    <div class="flex flex-col md:flex-row gap-4 md:gap-8">
        
        <!-- Sidebar Menu -->
        <x-frontend.dashboard-sidebar :active-tab="$activeTab" />

        <!-- Main Content Area -->
        <main class="flex-1">
            
            @if($activeTab === 'overview')
            <!-- Overview Tab -->
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-gray-900 mb-4 md:mb-6">Dashboard Overview</h1>
                
                <!-- Stats Grid -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 mb-4 md:mb-8">
                    <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-center">
                        <div class="text-gray-500 text-sm mb-1">Total Orders</div>
                        <div class="text-2xl font-bold text-gray-900">{{ $totalOrders ?? 0 }}</div>
                    </div>
                    <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-center">
                        <div class="text-gray-500 text-sm mb-1">Pending</div>
                        <div class="text-2xl font-bold text-amber-500">{{ $pendingOrders ?? 0 }}</div>
                    </div>
                    <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-center">
                        <div class="text-gray-500 text-sm mb-1">Delivered</div>
                        <div class="text-2xl font-bold text-brand-600">{{ $deliveredOrders ?? 0 }}</div>
                    </div>
                    <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-center">
                        <div class="text-gray-500 text-sm mb-1">Wishlist</div>
                        <div class="text-2xl font-bold text-pink-500">0</div>
                    </div>
                </div>

                <!-- Recent Orders Preview -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                        <h2 class="font-bold text-gray-800">Recent Orders</h2>
                        <a href="{{ route('dashboard.orders') }}" class="text-sm font-medium text-brand-600 hover:underline">View All</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-600">
                            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                                <tr>
                                    <th class="px-6 py-3 font-medium">Order ID</th>
                                    <th class="px-6 py-3 font-medium">Date</th>
                                    <th class="px-6 py-3 font-medium">Items</th>
                                    <th class="px-6 py-3 font-medium">Total</th>
                                    <th class="px-6 py-3 font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($recentOrders ?? [] as $order)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-900">
                                        <a href="{{ route('dashboard.orders.show', $order->order_id) }}" class="hover:text-brand-600 hover:underline">#{{ $order->order_id }}</a>
                                    </td>
                                    <td class="px-6 py-4">{{ \Carbon\Carbon::parse($order->created_at)->format('M d, Y') }}</td>
                                    <td class="px-6 py-4">{{ $order->items->count() }} items</td>
                                    <td class="px-6 py-4 font-medium">৳{{ number_format($order->total) }}</td>
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold capitalize
                                            @if($order->status === 'pending') bg-amber-100 text-amber-700
                                            @elseif($order->status === 'delivered') bg-green-100 text-green-700
                                            @else bg-brand-100 text-brand-700 @endif
                                        ">
                                            {{ $order->status }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">No orders found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            @if($activeTab === 'orders')
            <!-- My Orders Tab -->
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-gray-900 mb-4 md:mb-6">Order History</h1>
                
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-600">
                            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                                <tr>
                                    <th class="px-6 py-3 font-medium">Order ID</th>
                                    <th class="px-6 py-3 font-medium">Date</th>
                                    <th class="px-6 py-3 font-medium">Items</th>
                                    <th class="px-6 py-3 font-medium">Total</th>
                                    <th class="px-6 py-3 font-medium">Payment</th>
                                    <th class="px-6 py-3 font-medium">Status</th>
                                    <th class="px-6 py-3 font-medium text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($orders ?? [] as $order)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-900">
                                        #{{ $order->order_id }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="whitespace-nowrap">{{ \Carbon\Carbon::parse($order->created_at)->format('M d, Y') }}</div>
                                        <div class="text-xs text-gray-400 mt-0.5">{{ \Carbon\Carbon::parse($order->created_at)->format('h:i A') }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            @if($order->items->first() && $order->items->first()->image)
                                                <img src="{{ asset('storage/' . $order->items->first()->image) }}" class="w-10 h-10 rounded-lg object-cover border border-gray-100 bg-white shadow-sm shrink-0">
                                            @else
                                                <div class="w-10 h-10 rounded-lg border border-gray-100 bg-gray-50 flex items-center justify-center text-gray-400 shrink-0"><i class="ph ph-image text-lg"></i></div>
                                            @endif
                                            <div class="min-w-0">
                                                <p class="truncate max-w-[150px] md:max-w-[200px] font-medium text-gray-800" title="{{ $order->items->first()?->name }}">{{ $order->items->first()?->name }}</p>
                                                @if($order->items->count() > 1)
                                                    <p class="text-xs text-gray-400 mt-0.5">+{{ $order->items->count() - 1 }} other items</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 font-medium text-gray-900">৳{{ number_format($order->total) }}</td>
                                    <td class="px-6 py-4">
                                        <div class="text-xs flex flex-col gap-0.5">
                                            @if($order->payment_status === 'paid')
                                                <span class="text-green-600 font-bold uppercase tracking-wider">Paid</span>
                                                <span class="text-gray-500">{{ strtoupper($order->payment_method) }}</span>
                                            @else
                                                <span class="text-amber-600 font-bold uppercase tracking-wider">Unpaid</span>
                                                <span class="text-gray-500">{{ strtoupper($order->payment_method) }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold capitalize whitespace-nowrap
                                            @if($order->status === 'pending') bg-amber-100 text-amber-700
                                            @elseif($order->status === 'delivered') bg-green-100 text-green-700
                                            @else bg-brand-100 text-brand-700 @endif
                                        ">
                                            {{ $order->status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('dashboard.orders.show', $order->order_id) }}" class="inline-block text-brand-600 hover:text-white font-medium text-xs border border-brand-200 bg-brand-50 hover:bg-brand-600 px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap shadow-sm">View Details</a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                            <i class="ph ph-shopping-bag text-3xl text-gray-400"></i>
                                        </div>
                                        <h3 class="text-base font-bold text-gray-900 mb-1">No orders found</h3>
                                        <p class="text-sm text-gray-500 mb-6">Looks like you haven't placed any orders yet.</p>
                                        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-brand-600 text-white text-sm font-medium rounded-lg hover:bg-brand-700 transition-colors shadow-sm"><i class="ph ph-storefront"></i> Start Shopping</a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                @if($orders->hasPages())
                <div class="mt-6 flex justify-end">
                    <x-pagination :paginator="$orders" />
                </div>
                @endif
            </div>
            @endif

            @if($activeTab === 'reviews')
            <!-- My Reviews Tab -->
            <div x-data="{ reviewTab: 'available' }">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900">My Reviews</h1>
                </div>

                <!-- Custom Tabs -->
                <div class="flex space-x-1 bg-gray-100/80 p-1 rounded-xl mb-6 w-max border border-gray-200/50">
                    <button @click="reviewTab = 'available'" :class="{ 'bg-white shadow-sm text-gray-900 font-bold': reviewTab === 'available', 'text-gray-500 hover:text-gray-700 font-medium': reviewTab !== 'available' }" class="px-5 py-2 text-sm rounded-lg transition-all cursor-pointer">Available for Review</button>
                    <button @click="reviewTab = 'my_reviews'" :class="{ 'bg-white shadow-sm text-gray-900 font-bold': reviewTab === 'my_reviews', 'text-gray-500 hover:text-gray-700 font-medium': reviewTab !== 'my_reviews' }" class="px-5 py-2 text-sm rounded-lg transition-all cursor-pointer">My Reviews</button>
                </div>

                <!-- Available for Review List -->
                <div x-show="reviewTab === 'available'" class="grid gap-4">
                    @php $hasAvailable = false; @endphp
                    @foreach($orderItems as $item)
                        @php
                            $review = $existingReviews->get($item->product_id);
                            $orderDate = $item->order->updated_at ?? $item->created_at;
                            // Available if no review exists OR the latest order is newer than the last review update
                            $canReview = !$review || $orderDate > $review->updated_at;
                        @endphp
                        @if($canReview)
                            @php $hasAvailable = true; @endphp
                            <!-- Product Card -->
                            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col sm:flex-row gap-6 items-center hover:border-gray-200 transition-colors">
                                @php
                                    $imageUrl = str_starts_with($item->product->featured_image, 'http') ? $item->product->featured_image : Storage::url($item->product->featured_image);
                                @endphp
                                <img src="{{ $imageUrl }}" class="w-20 h-20 rounded-xl object-cover shrink-0 border border-gray-50" alt="{{ $item->product->title }}">
                                <div class="flex-1 min-w-0 text-center sm:text-left">
                                    <a href="{{ route('product.show', $item->product->slug) }}" class="font-bold text-gray-900 text-base hover:text-brand-600 cursor-pointer mb-1 block truncate">{{ $item->product->title }}</a>
                                    <p class="text-gray-500 text-sm">
                                        {{ $review ? "You recently ordered this again. Care to update your review?" : "You haven't reviewed this product yet." }}
                                    </p>
                                </div>
                                <div class="shrink-0 w-full sm:w-auto">
                                    <a href="{{ route('dashboard.reviews.write', $item->product->slug) }}" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-brand-50 text-brand-700 hover:bg-brand-100 rounded-xl text-sm font-bold transition-colors w-full sm:w-auto">
                                        <i class="ph {{ $review ? 'ph-pencil-simple' : 'ph-star' }}"></i> {{ $review ? 'Update Review' : 'Write a Review' }}
                                    </a>
                                </div>
                            </div>
                        @endif
                    @endforeach

                    @if(!$hasAvailable)
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
                            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="ph-fill ph-check-circle text-3xl text-gray-300"></i>
                            </div>
                            <h3 class="text-base font-bold text-gray-900 mb-1">All caught up!</h3>
                            <p class="text-sm text-gray-500">You don't have any pending products to review.</p>
                        </div>
                    @endif
                </div>

                <!-- My Reviews List -->
                <div x-show="reviewTab === 'my_reviews'" class="grid gap-4" style="display: none;">
                    @php $hasReviewed = false; @endphp
                    @foreach($orderItems as $item)
                        @php $review = $existingReviews->get($item->product_id); @endphp
                        @if($review)
                            @php $hasReviewed = true; @endphp
                            <!-- Product Card -->
                            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col sm:flex-row gap-6 items-start sm:items-center hover:border-gray-200 transition-colors">
                                @php
                                    $imageUrl = str_starts_with($item->product->featured_image, 'http') ? $item->product->featured_image : Storage::url($item->product->featured_image);
                                @endphp
                                <img src="{{ $imageUrl }}" class="w-20 h-20 rounded-xl object-cover shrink-0 border border-gray-50" alt="{{ $item->product->title }}">
                                <div class="flex-1 min-w-0">
                                    <a href="{{ route('product.show', $item->product->slug) }}" class="font-bold text-gray-900 text-base hover:text-brand-600 cursor-pointer mb-2 block truncate">{{ $item->product->title }}</a>
                                    <div class="flex flex-wrap items-center gap-2 mb-2">
                                        <div class="flex text-amber-400 text-sm">
                                            @for($i=1; $i<=5; $i++)
                                                <i class="ph-fill ph-star {{ $i <= $review->rating ? 'text-amber-400' : 'text-gray-200' }}"></i>
                                            @endfor
                                        </div>
                                        <span class="text-gray-400 text-xs">Reviewed on {{ $review->created_at->format('M d, Y') }}</span>
                                        @if($review->status === 'pending')
                                            <span class="px-2 py-0.5 bg-amber-50 text-amber-600 rounded text-[10px] font-bold uppercase tracking-wider">Pending</span>
                                        @elseif($review->status === 'rejected')
                                            <span class="px-2 py-0.5 bg-red-50 text-red-600 rounded text-[10px] font-bold uppercase tracking-wider">Rejected</span>
                                        @endif
                                    </div>
                                    @if($review->review)
                                    <p class="text-gray-600 text-sm leading-relaxed line-clamp-2">{{ $review->review }}</p>
                                    @endif
                                </div>
                                <div class="shrink-0 w-full sm:w-auto mt-4 sm:mt-0">
                                    <a href="{{ route('dashboard.reviews.write', $item->product->slug) }}" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-gray-50 text-gray-700 hover:bg-gray-100 rounded-xl text-sm font-bold transition-colors w-full sm:w-auto border border-gray-200">
                                        <i class="ph ph-eye"></i> View/Edit Review
                                    </a>
                                </div>
                            </div>
                        @endif
                    @endforeach

                    @if(!$hasReviewed)
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
                            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="ph-fill ph-star-half text-3xl text-gray-300"></i>
                            </div>
                            <h3 class="text-base font-bold text-gray-900 mb-1">No reviews yet</h3>
                            <p class="text-sm text-gray-500">You haven't written any reviews yet. Go to 'Available for Review' to share your thoughts.</p>
                        </div>
                    @endif
                </div>
            </div>

            @endif

            @if($activeTab === 'profile')
            <!-- Profile Tab -->
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-gray-900 mb-4 md:mb-6">Profile Settings</h1>
                
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 md:p-6">
                    <div class="flex items-center gap-6 mb-8 pb-8 border-b border-gray-100">
                        <div class="relative group shrink-0">
                            <img src="{{ Auth::user()->avatar ?? '' }}" alt="Profile" class="update-user-avatar w-24 h-24 rounded-full object-cover border-4 border-gray-50 shadow-sm {{ Auth::user()->avatar ? '' : 'hidden' }}">
                            <div class="update-user-initial-wrapper w-24 h-24 rounded-full bg-brand-100 flex items-center justify-center text-brand-600 font-bold text-3xl border-4 border-gray-50 shadow-sm {{ Auth::user()->avatar ? 'hidden' : '' }}">
                                <span class="update-user-initial">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                            </div>
                            
                            <label for="avatar-input" class="absolute inset-0 flex items-center justify-center bg-black/50 text-white rounded-full opacity-0 group-hover:opacity-100 cursor-pointer transition-opacity">
                                <i class="ph ph-camera text-2xl"></i>
                            </label>
                            <input type="file" id="avatar-input" class="hidden" accept="image/png, image/jpeg, image/jpg, image/webp">
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 text-lg">Profile Picture</h3>
                            <p class="text-sm text-gray-500 mt-1">Recommended size 400x400px.</p>
                            <label for="avatar-input" class="inline-block mt-3 px-4 py-2 bg-brand-50 hover:bg-brand-100 text-brand-600 text-sm font-medium rounded-xl transition-colors cursor-pointer">
                                Change Picture
                            </label>
                        </div>
                    </div>

                    <div id="profile-success-msg" class="hidden mb-6 bg-green-50 text-green-700 border border-green-200 px-4 py-3 rounded-xl text-sm font-medium"></div>
                    <form id="profile-form" action="{{ route('dashboard.profile.update') }}" method="POST" class="space-y-6">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="relative pb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                <input type="text" name="name" id="name-input" value="{{ old('name', Auth::user()->name) }}" required maxlength="20" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500">
                                <span id="name-error" class="text-xs text-red-500 hidden absolute bottom-1 left-1"></span>
                            </div>
                            <div class="relative pb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                @if(!empty(Auth::user()->google_id))
                                    <input type="text" name="phone" id="phone-input" value="{{ old('phone', Auth::user()->phone) }}" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500" placeholder="e.g. 01XXXXXXXXX">
                                    <span id="phone-error" class="text-xs text-red-500 hidden absolute bottom-1 left-1"></span>
                                @else
                                    <input type="text" value="{{ Auth::user()->phone }}" disabled class="w-full px-4 py-2.5 bg-gray-100 border border-gray-200 rounded-xl text-gray-500 cursor-not-allowed">
                                    <p class="text-xs text-gray-500 absolute bottom-1 left-1">Phone number cannot be changed.</p>
                                @endif
                            </div>
                            <div class="md:col-span-2 relative pb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                <input type="email" value="{{ Auth::user()->email }}" disabled class="w-full px-4 py-2.5 bg-gray-100 border border-gray-200 rounded-xl text-gray-500 cursor-not-allowed">
                                <p class="text-xs text-gray-500 absolute bottom-1 left-1">Email cannot be changed.</p>
                            </div>
                        </div>

                        <div class="flex justify-end pt-2">
                            <button type="submit" id="save-profile-btn" class="inline-flex items-center gap-2 bg-brand-600 text-white font-semibold px-6 py-2.5 rounded-xl hover:bg-brand-700 transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                                <span>Save Changes</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Change Password Section -->
                <div class="mt-6 md:mt-8 bg-white rounded-2xl shadow-sm border border-gray-100 p-4 md:p-6">
                    <div class="flex items-center gap-3 mb-6 pb-6 border-b border-gray-100">
                        <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold shrink-0">
                            <i class="ph-bold ph-key text-2xl"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">Change Password</h2>
                            <p class="text-sm text-gray-500 mt-0.5">Ensure your account is using a secure and updated password.</p>
                        </div>
                    </div>

                    @if(!empty(Auth::user()->google_id))
                        <div class="p-4 rounded-xl bg-amber-50/70 border border-amber-200/60 text-sm text-amber-900 flex items-start gap-3">
                            <i class="ph-bold ph-google-logo text-xl text-red-500 shrink-0 mt-0.5"></i>
                            <div>
                                <p class="font-semibold text-gray-900">Signed in with Google</p>
                                <p class="text-xs text-amber-800/80 mt-1">Direct password login is managed via your Google account, so you don't need a custom password here.</p>
                            </div>
                        </div>
                    @else
                        <div id="password-success-msg" class="hidden mb-6 bg-green-50 text-green-700 border border-green-200 px-4 py-3 rounded-xl text-sm font-medium flex items-center gap-2">
                            <i class="ph-bold ph-check-circle text-lg"></i>
                            <span id="password-success-text">Password updated successfully.</span>
                        </div>

                        <form id="password-form" action="{{ route('dashboard.profile.password') }}" method="POST" class="space-y-6">
                            @csrf
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Current Password -->
                                <div class="relative pb-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Password <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <input type="password" name="current_password" id="current-password-input" required 
                                               placeholder="Enter current password"
                                               class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 outline-none text-sm transition-all">
                                        <button type="button" class="toggle-password-btn absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 cursor-pointer" data-target="current-password-input">
                                            <i class="ph ph-eye text-lg"></i>
                                        </button>
                                    </div>
                                    <span id="current-password-error" class="text-xs text-red-500 hidden absolute bottom-1 left-1"></span>
                                </div>

                                <!-- New Password -->
                                <div class="relative pb-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">New Password <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <input type="password" name="password" id="new-password-input" required minlength="6"
                                               placeholder="Min. 6 characters"
                                               class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 outline-none text-sm transition-all">
                                        <button type="button" class="toggle-password-btn absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 cursor-pointer" data-target="new-password-input">
                                            <i class="ph ph-eye text-lg"></i>
                                        </button>
                                    </div>
                                    <span id="new-password-error" class="text-xs text-red-500 hidden absolute bottom-1 left-1"></span>
                                </div>

                                <!-- Confirm New Password -->
                                <div class="relative pb-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <input type="password" name="password_confirmation" id="confirm-password-input" required minlength="6"
                                               placeholder="Re-type new password"
                                               class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 outline-none text-sm transition-all">
                                        <button type="button" class="toggle-password-btn absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 cursor-pointer" data-target="confirm-password-input">
                                            <i class="ph ph-eye text-lg"></i>
                                        </button>
                                    </div>
                                    <span id="confirm-password-error" class="text-xs text-red-500 hidden absolute bottom-1 left-1"></span>
                                </div>
                            </div>

                            <div class="flex justify-end pt-2">
                                <button type="submit" id="save-password-btn" class="inline-flex items-center gap-2 bg-amber-600 text-white font-semibold px-6 py-2.5 rounded-xl hover:bg-amber-700 transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                                    <i class="ph-bold ph-key"></i>
                                    <span>Update Password</span>
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const form = document.getElementById('profile-form');
                    const phoneInput = document.getElementById('phone-input');
                    const phoneError = document.getElementById('phone-error');
                    const saveBtn = document.getElementById('save-profile-btn');
                    const successMsg = document.getElementById('profile-success-msg');
                    const nameError = document.getElementById('name-error');
                    const nameInput = document.getElementById('name-input');

                    function checkValidation() {
                        let hasError = false;

                        // Check name
                        if (nameInput) {
                            const nVal = nameInput.value.trim();
                            if (nVal.length > 20) {
                                nameError.textContent = 'Name cannot exceed 20 characters.';
                                nameError.classList.remove('hidden');
                                hasError = true;
                            } else if (nVal.length === 0) {
                                nameError.textContent = 'Name is required.';
                                nameError.classList.remove('hidden');
                                hasError = true;
                            } else {
                                nameError.classList.add('hidden');
                            }
                        }

                        // Check phone
                        if (phoneInput) {
                            const pVal = phoneInput.value.trim();
                            let pErr = '';
                            if (pVal.length > 0) {
                                if (!pVal.startsWith('01')) {
                                    pErr = 'Phone number must start with 01.';
                                } else if (!/^[0-9]+$/.test(pVal)) {
                                    pErr = 'Phone number must contain only digits.';
                                } else if (pVal.length !== 11) {
                                    pErr = 'Phone number must be exactly 11 digits.';
                                }
                            }
                            
                            if (pErr) {
                                phoneError.textContent = pErr;
                                phoneError.classList.remove('hidden');
                                hasError = true;
                            } else {
                                phoneError.classList.add('hidden');
                            }
                        }

                        saveBtn.disabled = hasError;
                        return hasError;
                    }

                    if (nameInput) {
                        nameInput.addEventListener('input', checkValidation);
                    }

                    if (phoneInput) {
                        phoneInput.addEventListener('input', checkValidation);
                    }

                    if (form) {
                        form.addEventListener('submit', async function(e) {
                            e.preventDefault();
                            
                            saveBtn.disabled = true;
                            saveBtn.innerHTML = '<i class="ph ph-spinner animate-spin text-lg"></i> <span>Saving...</span>';
                            successMsg.classList.add('hidden');
                            if (phoneError) phoneError.classList.add('hidden');
                            if (nameError) nameError.classList.add('hidden');

                            try {
                                const formData = new FormData(form);
                                const response = await fetch(form.action, {
                                    method: 'POST',
                                    body: formData,
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest'
                                    }
                                });

                                const result = await response.json();

                                if (response.ok) {
                                    successMsg.textContent = result.message;
                                    successMsg.classList.remove('hidden');
                                    
                                    // Update names in UI
                                    const newName = formData.get('name');
                                    if (newName) {
                                        document.querySelectorAll('.update-user-name').forEach(el => {
                                            el.textContent = newName;
                                            if (el.hasAttribute('title')) el.setAttribute('title', newName);
                                        });
                                        document.querySelectorAll('.update-user-initial').forEach(el => {
                                            el.textContent = newName.charAt(0).toUpperCase();
                                        });
                                    }
                                } else {
                                    if (result.errors) {
                                        if (result.errors.name && nameError) {
                                            nameError.textContent = result.errors.name[0];
                                            nameError.classList.remove('hidden');
                                        }
                                        if (result.errors.phone && phoneError) {
                                            phoneError.textContent = result.errors.phone[0];
                                            phoneError.classList.remove('hidden');
                                        }
                                    }
                                }
                            } catch (error) {
                                console.error('Error:', error);
                            } finally {
                                checkValidation();
                                saveBtn.innerHTML = '<span>Save Changes</span>';
                            }
                        });
                    }

                    // Cropper Logic
                    const avatarInput = document.getElementById('avatar-input');
                    const cropperModal = document.getElementById('cropper-modal');
                    const cropperImage = document.getElementById('cropper-image');
                    const cancelCropBtn = document.getElementById('cancel-cropper-btn');
                    const saveCropBtn = document.getElementById('save-crop-btn');
                    let cropper = null;

                    if (avatarInput) {
                        avatarInput.addEventListener('change', function(e) {
                            const file = e.target.files[0];
                            if (file) {
                                const reader = new FileReader();
                                reader.onload = function(event) {
                                    cropperImage.src = event.target.result;
                                    cropperModal.classList.remove('hidden');
                                    cropperModal.classList.add('flex');
                                    
                                    if (cropper) cropper.destroy();
                                    
                                    cropper = new Cropper(cropperImage, {
                                        aspectRatio: 1,
                                        viewMode: 1,
                                        dragMode: 'move',
                                        autoCropArea: 1,
                                        restore: false,
                                        guides: true,
                                        center: true,
                                        highlight: false,
                                        cropBoxMovable: true,
                                        cropBoxResizable: true,
                                        toggleDragModeOnDblclick: false,
                                    });
                                };
                                reader.readAsDataURL(file);
                            }
                            // Reset input
                            this.value = '';
                        });
                    }

                    function closeCropper() {
                        cropperModal.classList.add('hidden');
                        cropperModal.classList.remove('flex');
                        if (cropper) {
                            cropper.destroy();
                            cropper = null;
                        }
                    }

                    if (cancelCropBtn) {
                        cancelCropBtn.addEventListener('click', closeCropper);
                    }

                    if (saveCropBtn) {
                        saveCropBtn.addEventListener('click', function() {
                            if (!cropper) return;
                            
                            const oldText = this.textContent;
                            this.textContent = 'Uploading...';
                            this.disabled = true;

                            cropper.getCroppedCanvas({
                                width: 400,
                                height: 400
                            }).toBlob((blob) => {
                                const formData = new FormData();
                                formData.append('avatar', blob, 'avatar.jpg');
                                formData.append('_token', document.querySelector('input[name="_token"]').value);

                                fetch('{{ route('dashboard.profile.avatar') }}', {
                                    method: 'POST',
                                    body: formData,
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest'
                                    }
                                })
                                .then(response => response.json())
                                .then(result => {
                                    if (result.success) {
                                        // Update images
                                        document.querySelectorAll('.update-user-avatar').forEach(img => {
                                            img.src = result.avatar_url;
                                            img.classList.remove('hidden');
                                        });
                                        document.querySelectorAll('.update-user-initial-wrapper').forEach(div => {
                                            div.classList.add('hidden');
                                        });
                                        
                                        successMsg.textContent = result.message;
                                        successMsg.classList.remove('hidden');
                                        closeCropper();
                                    } else {
                                        alert(result.message || 'Error uploading image.');
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    alert('Error uploading image.');
                                })
                                .finally(() => {
                                    this.textContent = oldText;
                                    this.disabled = false;
                                });
                            }, 'image/jpeg');
                        });
                    }

                    // Password Visibility Toggle
                    document.querySelectorAll('.toggle-password-btn').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const targetId = this.getAttribute('data-target');
                            const input = document.getElementById(targetId);
                            const icon = this.querySelector('i');
                            if (input) {
                                if (input.type === 'password') {
                                    input.type = 'text';
                                    icon.className = 'ph ph-eye-slash text-lg';
                                } else {
                                    input.type = 'password';
                                    icon.className = 'ph ph-eye text-lg';
                                }
                            }
                        });
                    });

                    // Password Form AJAX Submit
                    const passwordForm = document.getElementById('password-form');
                    const savePasswordBtn = document.getElementById('save-password-btn');
                    const passwordSuccessMsg = document.getElementById('password-success-msg');
                    const currentPasswordError = document.getElementById('current-password-error');
                    const newPasswordError = document.getElementById('new-password-error');
                    const confirmPasswordError = document.getElementById('confirm-password-error');

                    if (passwordForm) {
                        passwordForm.addEventListener('submit', async function(e) {
                            e.preventDefault();

                            if (currentPasswordError) currentPasswordError.classList.add('hidden');
                            if (newPasswordError) newPasswordError.classList.add('hidden');
                            if (confirmPasswordError) confirmPasswordError.classList.add('hidden');
                            if (passwordSuccessMsg) passwordSuccessMsg.classList.add('hidden');

                            const newPass = document.getElementById('new-password-input')?.value;
                            const confirmPass = document.getElementById('confirm-password-input')?.value;

                            if (newPass && confirmPass && newPass !== confirmPass) {
                                if (confirmPasswordError) {
                                    confirmPasswordError.textContent = 'Passwords do not match.';
                                    confirmPasswordError.classList.remove('hidden');
                                }
                                return;
                            }

                            savePasswordBtn.disabled = true;
                            const originalBtnHtml = savePasswordBtn.innerHTML;
                            savePasswordBtn.innerHTML = '<i class="ph ph-spinner animate-spin text-lg"></i> <span>Updating...</span>';

                            try {
                                const formData = new FormData(passwordForm);
                                const response = await fetch(passwordForm.action, {
                                    method: 'POST',
                                    body: formData,
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest'
                                    }
                                });

                                const result = await response.json();

                                if (response.ok && result.success) {
                                    passwordForm.reset();
                                    if (passwordSuccessMsg) {
                                        const successText = document.getElementById('password-success-text');
                                        if (successText) successText.textContent = result.message || 'Password updated successfully.';
                                        passwordSuccessMsg.classList.remove('hidden');
                                        passwordSuccessMsg.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                                    }
                                } else {
                                    if (result.errors) {
                                        if (result.errors.current_password && currentPasswordError) {
                                            currentPasswordError.textContent = result.errors.current_password[0];
                                            currentPasswordError.classList.remove('hidden');
                                        }
                                        if (result.errors.password && newPasswordError) {
                                            newPasswordError.textContent = result.errors.password[0];
                                            newPasswordError.classList.remove('hidden');
                                        }
                                        if (result.errors.password_confirmation && confirmPasswordError) {
                                            confirmPasswordError.textContent = result.errors.password_confirmation[0];
                                            confirmPasswordError.classList.remove('hidden');
                                        }
                                    } else if (result.message) {
                                        alert(result.message);
                                    }
                                }
                            } catch (err) {
                                console.error('Password update error:', err);
                                alert('An unexpected error occurred. Please try again.');
                            } finally {
                                savePasswordBtn.disabled = false;
                                savePasswordBtn.innerHTML = originalBtnHtml;
                            }
                        });
                    }
                });
            </script>
            @endif

            <!-- Cropper Modal -->
            <div id="cropper-modal" class="fixed inset-0 z-[9999] bg-black/80 hidden flex-col items-center justify-center p-4">
                <div class="bg-white rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
                    <div class="p-4 border-b border-gray-100 flex justify-between items-center shrink-0">
                        <h3 class="font-bold text-gray-900">Crop Profile Picture</h3>
                        <button type="button" onclick="document.getElementById('cancel-cropper-btn').click()" class="text-gray-400 hover:text-gray-600"><i class="ph ph-x text-xl"></i></button>
                    </div>
                    <div class="flex-1 min-h-[300px] bg-gray-900 p-2 relative overflow-hidden flex items-center justify-center">
                        <img id="cropper-image" src="" class="block max-w-full">
                    </div>
                    <div class="p-4 border-t border-gray-100 flex justify-end gap-3 shrink-0">
                        <button type="button" id="cancel-cropper-btn" class="px-4 py-2 text-gray-600 font-medium hover:bg-gray-100 rounded-xl transition-colors cursor-pointer">Cancel</button>
                        <button type="button" id="save-crop-btn" class="px-4 py-2 bg-brand-600 text-white font-medium rounded-xl hover:bg-brand-700 transition-colors cursor-pointer shadow-sm">Crop & Upload</button>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

@push('styles')
<link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
<style>
    .cropper-view-box,
    .cropper-face {
      border-radius: 50%;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
@endpush
@endsection
