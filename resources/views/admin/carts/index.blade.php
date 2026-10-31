@extends('layouts.admin')

@section('header_title', 'Abandoned Carts & Cart Activity')

@section('content')
<div class="space-y-6" x-data="cartLiveManager()">

    <!-- Page Header & Realtime Toggle -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <span>Abandoned Carts & Activity</span>
                <span x-show="isFetching" x-cloak class="inline-flex items-center text-xs text-brand-600 font-normal">
                    <i class="ph ph-arrows-clockwise animate-spin mr-1"></i> Syncing...
                </span>
            </h1>
            <p class="text-sm text-gray-500 mt-1">Real-time cart tracking, abandoned cart recovery, and customer purchase intent.</p>
        </div>

        <!-- Realtime Auto-Polling Toggle (Default OFF) -->
        <div class="flex items-center gap-3 bg-white p-2.5 px-4 rounded-2xl border border-gray-200/80 shadow-xs self-start sm:self-auto">
            <div class="flex flex-col select-none">
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full transition-colors"
                          :class="isPolling ? 'bg-emerald-500 animate-pulse' : 'bg-gray-300'"></span>
                    <span class="text-xs font-bold transition-colors"
                          :class="isPolling ? 'text-emerald-700' : 'text-gray-700'"
                          x-text="isPolling ? 'Live Auto-Poll ON' : 'Live Auto-Poll (Off)'"></span>
                </div>
                <span class="text-[10px] text-gray-400 font-medium"
                      x-text="isPolling ? 'Auto-syncing every 6s' : 'Click to enable live stream'"></span>
            </div>

            <!-- Toggle Switch -->
            <button type="button" @click="togglePolling()"
                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                    :class="isPolling ? 'bg-emerald-600' : 'bg-gray-200'"
                    :title="isPolling ? 'Turn off live polling' : 'Turn on live polling'">
                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                      :class="isPolling ? 'translate-x-5' : 'translate-x-0'"></span>
            </button>
        </div>
    </div>

    <!-- Metric KPI Cards (Reactivity bound to Alpine state) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Active Carts -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Active Live Carts</p>
                <h3 class="text-2xl font-bold text-gray-900 mt-1" x-text="formatNumber(stats.active_carts)">{{ number_format($stats['active_carts']) }}</h3>
                <p class="text-xs text-emerald-600 font-medium mt-1 flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block" :class="stats.active_carts > 0 ? 'animate-pulse' : ''"></span>
                    Currently in bags
                </p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl shrink-0">
                <i class="ph-fill ph-bag"></i>
            </div>
        </div>

        <!-- Abandoned Carts -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Abandoned Carts</p>
                <h3 class="text-2xl font-bold text-amber-600 mt-1" x-text="formatNumber(stats.abandoned_carts)">{{ number_format($stats['abandoned_carts']) }}</h3>
                <p class="text-xs text-gray-500 font-medium mt-1">Left without completing</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-2xl shrink-0">
                <i class="ph-fill ph-shopping-cart-simple"></i>
            </div>
        </div>

        <!-- Abandoned Revenue -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Abandoned Value</p>
                <h3 class="text-2xl font-bold text-red-600 mt-1" x-text="'৳' + formatNumber(stats.abandoned_value)">৳{{ number_format($stats['abandoned_value'], 0) }}</h3>
                <p class="text-xs text-gray-500 font-medium mt-1">Recoverable revenue</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-red-50 text-red-600 flex items-center justify-center text-2xl shrink-0">
                <i class="ph-fill ph-currency-circle-dollar"></i>
            </div>
        </div>

        <!-- Conversion Rate -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Cart Conversion Rate</p>
                <h3 class="text-2xl font-bold text-brand-600 mt-1" x-text="stats.conversion_rate + '%'">{{ $stats['conversion_rate'] }}%</h3>
                <p class="text-xs text-gray-500 font-medium mt-1" x-text="formatNumber(stats.converted_carts) + ' carts converted'">{{ number_format($stats['converted_carts']) }} carts converted</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center text-2xl shrink-0">
                <i class="ph-fill ph-trend-up"></i>
            </div>
        </div>
    </div>

    <!-- Filters & Status Tabs -->
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col md:flex-row gap-4 items-center justify-between">
        
        <!-- Status Filter Tabs -->
        <div class="flex items-center gap-1.5 overflow-x-auto w-full md:w-auto p-1 bg-gray-50 rounded-xl border border-gray-100">
            <a href="{{ route('admin.carts.index', array_merge(request()->except('page', 'status'), ['status' => 'all'])) }}"
               class="px-3.5 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors {{ $statusFilter === 'all' ? 'bg-white text-gray-900 shadow-xs' : 'text-gray-500 hover:text-gray-800' }}">
                All (<span x-text="stats.total_carts">{{ $stats['total_carts'] }}</span>)
            </a>
            <a href="{{ route('admin.carts.index', array_merge(request()->except('page', 'status'), ['status' => 'abandoned'])) }}"
               class="px-3.5 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors {{ $statusFilter === 'abandoned' ? 'bg-white text-amber-600 shadow-xs' : 'text-gray-500 hover:text-amber-600' }}">
                Abandoned (<span x-text="stats.abandoned_carts">{{ $stats['abandoned_carts'] }}</span>)
            </a>
            <a href="{{ route('admin.carts.index', array_merge(request()->except('page', 'status'), ['status' => 'active'])) }}"
               class="px-3.5 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors {{ $statusFilter === 'active' ? 'bg-white text-emerald-600 shadow-xs' : 'text-gray-500 hover:text-emerald-600' }}">
                Active (<span x-text="stats.active_carts">{{ $stats['active_carts'] }}</span>)
            </a>
            <a href="{{ route('admin.carts.index', array_merge(request()->except('page', 'status'), ['status' => 'converted'])) }}"
               class="px-3.5 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors {{ $statusFilter === 'converted' ? 'bg-white text-blue-600 shadow-xs' : 'text-gray-500 hover:text-blue-600' }}">
                Converted (<span x-text="stats.converted_carts">{{ $stats['converted_carts'] }}</span>)
            </a>
        </div>

        <!-- Search Bar -->
        <form action="{{ route('admin.carts.index') }}" method="GET" class="flex items-center gap-2 w-full md:w-auto">
            <input type="hidden" name="status" value="{{ $statusFilter }}">
            <div class="relative flex-1 md:w-64">
                <i class="ph ph-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-base"></i>
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="Search phone or name..."
                       class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-9 pr-4 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-colors">
            </div>
            @if(!empty($search))
                <a href="{{ route('admin.carts.index', ['status' => $statusFilter]) }}"
                   class="p-2 bg-gray-100 text-gray-600 rounded-xl hover:bg-gray-200 transition text-xs">
                    <i class="ph ph-x"></i>
                </a>
            @endif
            <button type="submit" class="px-3.5 py-2 bg-brand-600 text-white rounded-xl text-xs font-semibold hover:bg-brand-700 transition">
                Search
            </button>
        </form>
    </div>

    <!-- Live Toast Bar when new items are added via polling -->
    <div x-show="newItemsBanner" x-transition.opacity x-cloak
         class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-2.5 rounded-xl text-xs font-semibold flex items-center justify-between shadow-xs">
        <div class="flex items-center gap-2">
            <i class="ph-bold ph-bell-ringing text-emerald-600 text-base"></i>
            <span x-text="newItemsBannerText"></span>
        </div>
        <button type="button" @click="newItemsBanner = false" class="text-emerald-600 hover:text-emerald-900">
            <i class="ph ph-x"></i>
        </button>
    </div>

    <!-- Carts Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-gray-50/80 text-gray-500 font-semibold border-b border-gray-100 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Customer</th>
                        <th class="px-6 py-4">Items In Cart</th>
                        <th class="px-6 py-4">Total Value</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Last Activity</th>
                        <th class="px-6 py-4 text-right">Recovery Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    <template x-for="cart in carts" :key="cart.id">
                        <tr class="transition-colors duration-300"
                            :class="cart._isNew ? 'bg-emerald-50/80 hover:bg-emerald-50' : 'hover:bg-gray-50/60'">
                            
                            <!-- Customer Info -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-brand-50 text-brand-600 flex items-center justify-center font-bold text-sm shrink-0 border border-brand-100">
                                        <template x-if="cart.is_user">
                                            <i class="ph-bold ph-user-check text-base"></i>
                                        </template>
                                        <template x-if="!cart.is_user">
                                            <i class="ph-bold ph-user text-base"></i>
                                        </template>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900 flex items-center gap-1.5">
                                            <span x-text="cart.customer_name"></span>
                                            <template x-if="cart.is_user">
                                                <span class="px-1.5 py-0.2 bg-brand-50 text-brand-700 text-[10px] font-bold rounded">User</span>
                                            </template>
                                            <template x-if="!cart.is_user">
                                                <span class="px-1.5 py-0.2 bg-gray-100 text-gray-600 text-[10px] font-semibold rounded">Guest</span>
                                            </template>
                                            <template x-if="cart._isNew">
                                                <span class="px-1.5 py-0.2 bg-emerald-500 text-white text-[9px] font-extrabold rounded animate-pulse">NEW</span>
                                            </template>
                                        </div>
                                        <div class="text-xs text-gray-500 font-mono mt-0.5">
                                            <template x-if="cart.customer_phone">
                                                <a :href="'tel:' + cart.customer_phone" class="hover:text-brand-600 transition-colors font-semibold text-gray-700" x-text="cart.customer_phone"></a>
                                            </template>
                                            <template x-if="!cart.customer_phone">
                                                <span class="text-gray-400" x-text="'Token: ' + (cart.visitor_token ? cart.visitor_token.substring(0, 10) + '...' : 'Unknown')"></span>
                                            </template>
                                        </div>
                                        <template x-if="cart.customer_address">
                                            <div class="text-[11px] text-gray-600 mt-0.5 flex items-center gap-1 max-w-[220px] truncate" :title="cart.customer_address">
                                                <i class="ph ph-map-pin text-rose-500 shrink-0"></i>
                                                <span class="truncate" x-text="cart.customer_address"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </td>

                            <!-- Cart Items Preview -->
                            <td class="px-6 py-4">
                                <div class="space-y-1.5 max-w-md">
                                    <template x-for="(item, idx) in (cart.items || []).slice(0, 2)" :key="idx">
                                        <div class="flex items-center gap-2">
                                            <template x-if="item.image">
                                                <img :src="item.image" class="w-8 h-8 rounded-lg object-cover border border-gray-200 shrink-0" alt="">
                                            </template>
                                            <template x-if="!item.image">
                                                <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400 shrink-0">
                                                    <i class="ph ph-image"></i>
                                                </div>
                                            </template>
                                            <div class="min-w-0 flex-1">
                                                <p class="text-xs font-medium text-gray-800 truncate" x-text="item.name || 'Product'"></p>
                                                <p class="text-[11px] text-gray-400">
                                                    Qty: <span x-text="item.quantity || 1"></span> &times; ৳<span x-text="formatNumber(item.price || 0)"></span>
                                                    <template x-if="item.variant_name">
                                                        <span>&bull; <span x-text="item.variant_name"></span></span>
                                                    </template>
                                                </p>
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="(cart.items || []).length > 2">
                                        <span class="text-[11px] text-gray-400 font-medium pl-1"
                                              x-text="'+ ' + ((cart.items || []).length - 2) + ' more items'">
                                        </span>
                                    </template>
                                </div>
                            </td>

                            <!-- Total Value -->
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-900 text-sm" x-text="'৳' + formatNumber(cart.total_amount)"></div>
                                <div class="text-xs text-gray-500" x-text="cart.total_items + ' item' + (cart.total_items > 1 ? 's' : '')"></div>
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4">
                                <template x-if="cart.status === 'active'">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Active Cart
                                    </span>
                                </template>
                                <template x-if="cart.status === 'abandoned'">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        <i class="ph-bold ph-clock text-xs"></i>
                                        Abandoned
                                    </span>
                                </template>
                                <template x-if="cart.status === 'converted'">
                                    <div class="flex flex-col gap-1">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                            <i class="ph-bold ph-check-circle text-xs"></i>
                                            Converted
                                        </span>
                                        <template x-if="cart.order_url">
                                            <a :href="cart.order_url"
                                               class="text-[11px] text-brand-600 hover:underline font-semibold"
                                               x-text="'Order #' + cart.order_id">
                                            </a>
                                        </template>
                                    </div>
                                </template>
                            </td>

                            <!-- Last Activity -->
                            <td class="px-6 py-4">
                                <div class="text-xs font-medium text-gray-800" x-text="cart.last_activity_human"></div>
                                <div class="text-[11px] text-gray-400" x-text="cart.last_activity_formatted"></div>
                            </td>

                            <!-- Recovery Actions -->
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- WhatsApp Button -->
                                    <template x-if="cart.wa_link">
                                        <a :href="cart.wa_link" target="_blank" rel="noopener noreferrer"
                                           class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition shadow-xs"
                                           title="Chat & Offer Coupon on WhatsApp">
                                            <i class="ph-fill ph-whatsapp-logo text-sm"></i>
                                            <span>WhatsApp</span>
                                        </a>
                                    </template>

                                    <!-- Footprint Link -->
                                    <template x-if="cart.visitor_token">
                                        <a :href="'{{ route('admin.visitors.footprint') }}?search=' + encodeURIComponent(cart.visitor_token)"
                                           class="p-2 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 transition"
                                           title="View Shopper Footprint / History">
                                            <i class="ph ph-footprints text-base"></i>
                                        </a>
                                    </template>

                                    <!-- Phone Call Button -->
                                    <template x-if="cart.customer_phone">
                                        <a :href="'tel:' + cart.customer_phone"
                                           class="p-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 transition"
                                           title="Call Customer">
                                            <i class="ph-fill ph-phone-call text-base"></i>
                                        </a>
                                    </template>

                                    <!-- Delete Button -->
                                    <button type="button" @click="deleteCart(cart)"
                                            class="p-2 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition"
                                            title="Delete Cart">
                                        <i class="ph ph-trash text-base"></i>
                                    </button>
                                </div>
                            </td>

                        </tr>
                    </template>

                    <!-- Empty state -->
                    <tr x-show="carts.length === 0" x-cloak>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="w-12 h-12 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center mx-auto text-2xl mb-3">
                                <i class="ph ph-shopping-cart"></i>
                            </div>
                            <h4 class="text-sm font-semibold text-gray-700">No Cart Sessions Found</h4>
                            <p class="text-xs text-gray-400 mt-1">When customers add items to their cart, they will appear here in real time.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        @if($carts->hasPages())
            <div class="p-4 border-t border-gray-100">
                {{ $carts->links() }}
            </div>
        @endif
    </div>

</div>

<script>
    function cartLiveManager() {
        return {
            isPolling: false, // Default is OFF
            isFetching: false,
            pollTimer: null,
            newItemsBanner: false,
            newItemsBannerText: '',
            stats: @json($stats),
            carts: @json($initialCartsJson),
            statusFilter: '{{ $statusFilter }}',
            search: '{{ addslashes($search) }}',

            formatNumber(num) {
                if (!num) return '0';
                return Number(num).toLocaleString('en-US');
            },

            togglePolling() {
                this.isPolling = !this.isPolling;
                if (this.isPolling) {
                    this.startPolling();
                } else {
                    this.stopPolling();
                }
            },

            startPolling() {
                // Initial immediate fetch
                this.fetchLatest();
                // Schedule polling every 6 seconds
                this.pollTimer = setInterval(() => {
                    this.fetchLatest();
                }, 6000);
            },

            stopPolling() {
                if (this.pollTimer) {
                    clearInterval(this.pollTimer);
                    this.pollTimer = null;
                }
            },

            async fetchLatest() {
                if (this.isFetching) return;
                this.isFetching = true;

                try {
                    const params = new URLSearchParams({
                        poll: '1',
                        status: this.statusFilter,
                        search: this.search
                    });

                    const url = '{{ route('admin.carts.index') }}?' + params.toString();
                    const res = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!res.ok) throw new Error('Poll failed');

                    const data = await res.json();
                    if (!data.success) return;

                    // 1. Update KPI stats reactively
                    this.stats = data.stats;

                    // 2. Merge incoming carts into list
                    const incomingCarts = data.carts || [];
                    let newlyAddedCount = 0;

                    for (const incoming of incomingCarts) {
                        const existingIdx = this.carts.findIndex(c => c.id === incoming.id);
                        if (existingIdx !== -1) {
                            // Update existing record preserving new flag if recently added
                            const wasNew = this.carts[existingIdx]._isNew;
                            this.carts[existingIdx] = { ...incoming, _isNew: wasNew };
                        } else {
                            // New cart arrived! Prepend to the top
                            incoming._isNew = true;
                            this.carts.unshift(incoming);
                            newlyAddedCount++;

                            // Clear highlight after 4 seconds
                            setTimeout(() => {
                                incoming._isNew = false;
                            }, 4000);
                        }
                    }

                    if (newlyAddedCount > 0) {
                        this.newItemsBannerText = `${newlyAddedCount} new cart activity detected!`;
                        this.newItemsBanner = true;
                        setTimeout(() => {
                            this.newItemsBanner = false;
                        }, 5000);
                    }

                } catch (e) {
                    console.warn('Auto poll error:', e);
                } finally {
                    this.isFetching = false;
                }
            },

            async deleteCart(cart) {
                if (!confirm('Are you sure you want to remove this cart record?')) return;

                try {
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    const res = await fetch(cart.delete_url, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    // Remove from list
                    this.carts = this.carts.filter(c => c.id !== cart.id);

                    // Re-fetch stats
                    this.fetchLatest();
                } catch (e) {
                    alert('Failed to remove cart.');
                }
            }
        };
    }
</script>
@endsection
