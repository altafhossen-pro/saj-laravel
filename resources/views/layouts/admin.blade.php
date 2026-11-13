<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $adminSiteName = \App\Models\Setting::get('site_name', 'SAJ');
        $adminFavicon = \App\Models\Setting::get('site_favicon') ?: asset('favicon.ico');
        $headerMerchantId = \App\Models\Setting::get('steadfast_merchant_id', '');
        $headerShowMerchantId = \App\Models\Setting::get('steadfast_show_merchant_id_in_header', '1');
    @endphp
    <title>@yield('title', 'Admin Panel - ' . $adminSiteName)</title>
    <link rel="icon" type="image/png" href="{{ $adminFavicon }}">
    <link rel="shortcut icon" href="{{ $adminFavicon }}">
    <link rel="apple-touch-icon" href="{{ $adminFavicon }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Vite/Tailwind -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <!-- Chart.js for High Performance Analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        [x-cloak] { display: none !important; }
    </style>

    @stack('styles')
</head>

<body class="antialiased bg-gray-50 text-gray-800 font-sans">
    <div class="flex h-screen overflow-hidden" x-data="{ sidebarOpen: false }">

        <!-- Mobile Sidebar Backdrop -->
        <div x-show="sidebarOpen" style="display: none;"
            @click="sidebarOpen = false"
            x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-gray-900/50 backdrop-blur-xs z-40 lg:hidden"></div>

        <!-- Admin Sidebar -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-100 flex flex-col shrink-0 overflow-y-auto transition-transform duration-300 ease-in-out lg:static lg:z-20">
            <div class="h-16 flex items-center justify-between px-6 border-b border-gray-100 shrink-0">
                <a href="{{ route('admin.dashboard') }}"
                    class="text-xl font-bold text-brand-600 flex items-center gap-2">
                    @if(\App\Models\Setting::get('site_logo'))
                        <img src="{{ \App\Models\Setting::get('site_logo') }}" alt="Site Logo"
                            class="max-h-14 object-contain">
                    @else
                        <i class="ph-fill ph-shopping-bag text-brand-500"></i>
                        Admin Panel
                    @endif
                </a>
                <button @click="sidebarOpen = false" type="button"
                    class="lg:hidden p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 cursor-pointer">
                    <i class="ph ph-x text-xl"></i>
                </button>
            </div>

            <div class="p-4 flex-1">
                <div class="px-2 text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Management</div>
                <nav class="space-y-1">
                    <x-nav-link href="{{ route('admin.dashboard') }}" icon="squares-four" theme="light"
                        :active="request()->routeIs('admin.dashboard')">
                        Overview
                    </x-nav-link>

                    <!-- Products -->
                    @if(auth()->user()->hasRole('super_admin') || auth()->user()->can('view_products') || auth()->user()->can('create_products') || auth()->user()->can('manage_products') || auth()->user()->can('manage_brands') || auth()->user()->can('manage_reviews'))
                    <x-nav-dropdown title="Products" icon="package" theme="light"
                        :active="request()->routeIs('admin.products.*') || request()->routeIs('admin.brands.*') || request()->routeIs('admin.questions.*') || request()->routeIs('admin.reviews.*')">
                        @if(auth()->user()->hasRole('super_admin') || auth()->user()->can('view_products') || auth()->user()->can('manage_products'))
                        <x-nav-link href="{{ route('admin.products.index') }}" theme="light" class="!py-2 !text-sm"
                            :active="request()->routeIs('admin.products.index')">All Products</x-nav-link>
                        @endif
                        @if(auth()->user()->hasRole('super_admin') || auth()->user()->can('create_products') || auth()->user()->can('manage_products'))
                        <x-nav-link href="{{ route('admin.products.create') }}" theme="light" class="!py-2 !text-sm"
                            :active="request()->routeIs('admin.products.create')">Create Product</x-nav-link>
                        @endif
                        @can('manage_brands')
                        <x-nav-link href="{{ route('admin.brands.index') }}" theme="light" class="!py-2 !text-sm"
                            :active="request()->routeIs('admin.brands.index')">Product Brands</x-nav-link>
                        @endcan
                        @can('manage_reviews')
                        <x-nav-link href="{{ route('admin.questions.index') }}" theme="light" class="!py-2 !text-sm"
                            :active="request()->routeIs('admin.questions.index')">Products Q&A</x-nav-link>
                        <x-nav-link href="{{ route('admin.reviews.index') }}" theme="light" class="!py-2 !text-sm"
                            :active="request()->routeIs('admin.reviews.*')">Product Reviews</x-nav-link>
                        @endcan
                    </x-nav-dropdown>
                    @endif

                    <!-- Orders -->
                    @if(auth()->user()->hasRole('super_admin') || auth()->user()->can('view_orders') || auth()->user()->can('manage_orders') || auth()->user()->can('create_manual_orders') || auth()->user()->can('create_orders') || auth()->user()->can('edit_orders') || auth()->user()->can('manage_order_status') || auth()->user()->can('cancel_orders') || auth()->user()->can('delete_orders') || auth()->user()->can('manage_courier'))
                    <x-nav-dropdown title="Orders" icon="shopping-cart" theme="light"
                        :active="request()->routeIs('admin.orders.*') || request()->routeIs('admin.orders')">
                        @if(auth()->user()->hasRole('super_admin') || auth()->user()->can('view_orders') || auth()->user()->can('manage_orders'))
                        <x-nav-link href="{{ route('admin.orders') }}" theme="light" class="!py-2 !text-sm"
                            :active="request()->routeIs('admin.orders')">All Orders</x-nav-link>
                        @endif
                        @if(auth()->user()->hasRole('super_admin') || auth()->user()->can('create_manual_orders') || auth()->user()->can('create_orders') || auth()->user()->can('manage_orders'))
                        <x-nav-link href="{{ route('admin.orders.manual') }}" theme="light" class="!py-2 !text-sm"
                            :active="request()->routeIs('admin.orders.manual')">Manual Order</x-nav-link>
                        @endif
                        @if(auth()->user()->hasRole('super_admin') || auth()->user()->can('view_orders') || auth()->user()->can('manage_orders'))
                        <x-nav-link href="{{ route('admin.orders.drafts') }}" theme="light" class="!py-2 !text-sm"
                            :active="request()->routeIs('admin.orders.drafts')">Draft Orders</x-nav-link>
                        @endif
                        @if(auth()->user()->hasRole('super_admin'))
                        <x-nav-link href="{{ route('admin.orders.trash') }}" theme="light" class="!py-2 !text-sm text-rose-600 hover:text-rose-700 font-medium"
                            :active="request()->routeIs('admin.orders.trash*')">
                            <span class="flex items-center justify-between w-full">
                                <span class="flex items-center gap-1.5">
                                    <i class="ph ph-trash text-sm"></i>
                                    <span>Trash</span>
                                </span>
                                @php
                                    $trashedOrdersCount = \App\Models\Order\Order::onlyTrashed()->count();
                                @endphp
                                @if($trashedOrdersCount > 0)
                                <span class="px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-rose-100 text-rose-700">
                                    {{ $trashedOrdersCount }}
                                </span>
                                @endif
                            </span>
                        </x-nav-link>
                        @endif
                    </x-nav-dropdown>
                    @endif

                    <!-- Categories -->
                    @if(auth()->user()->can('manage_categories'))
                    <x-nav-dropdown title="Categories" icon="list-dashes" theme="light"
                        :active="request()->routeIs('admin.categories.*')">
                        <x-nav-link href="{{ route('admin.categories.index') }}" theme="light" class="!py-2 !text-sm"
                            :active="request()->routeIs('admin.categories.index')">All Categories</x-nav-link>
                        <x-nav-link href="{{ route('admin.categories.create') }}" theme="light" class="!py-2 !text-sm"
                            :active="request()->routeIs('admin.categories.create')">Create Category</x-nav-link>
                    </x-nav-dropdown>
                    @endif

                    <!-- Customers -->
                    @if(auth()->user()->can('manage_customers') || auth()->user()->can('manage_staff'))
                    <x-nav-dropdown title="Customers" icon="users" theme="light"
                        :active="request()->routeIs('admin.customers.*') || request()->routeIs('admin.customers')">
                        <x-nav-link href="{{ route('admin.customers') }}" theme="light" class="!py-2 !text-sm"
                            :active="request()->routeIs('admin.customers')">All Customers</x-nav-link>
                        <x-nav-link href="{{ route('admin.customers.staff') }}" theme="light" class="!py-2 !text-sm"
                            :active="request()->routeIs('admin.customers.staff')">All Staff</x-nav-link>
                    </x-nav-dropdown>
                    @endif

                    <!-- Offers -->
                    @if(auth()->user()->can('manage_offers') || auth()->user()->can('manage_coupons'))
                    <x-nav-dropdown title="Offers" icon="tag" theme="light"
                        :active="request()->routeIs('admin.category-offers.*') || request()->routeIs('admin.coupons.*')">
                        <x-nav-link href="{{ route('admin.category-offers.index') }}" theme="light" class="!py-2 !text-sm"
                            :active="request()->routeIs('admin.category-offers.*')">Category Offers</x-nav-link>
                        <x-nav-link href="{{ route('admin.coupons.index') }}" theme="light" class="!py-2 !text-sm"
                            :active="request()->routeIs('admin.coupons.*')">Coupons</x-nav-link>
                    </x-nav-dropdown>
                    @endif

                    <!-- Live & Shoppers -->
                    @if(auth()->user()->can('view_visitors'))
                    <x-nav-dropdown title="Live & Shoppers" icon="footprints" theme="light"
                        :active="request()->routeIs('admin.visitors.*') || request()->routeIs('admin.carts.*') || request()->routeIs('admin.offer-templates.*')">
                        <x-nav-link href="{{ route('admin.visitors.live') }}" theme="light" class="!py-2 !text-sm"
                            :active="request()->routeIs('admin.visitors.live')">
                            <span class="flex items-center justify-between w-full">
                                <span>Live Monitor</span>
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            </span>
                        </x-nav-link>
                        <x-nav-link href="{{ route('admin.visitors.footprint') }}" theme="light" class="!py-2 !text-sm"
                            :active="request()->routeIs('admin.visitors.footprint')">
                            Visitor Footprint
                        </x-nav-link>
                        <x-nav-link href="{{ route('admin.carts.index') }}" theme="light" class="!py-2 !text-sm"
                            :active="request()->routeIs('admin.carts.*')">
                            Abandoned Carts
                        </x-nav-link>
                        <x-nav-link href="{{ route('admin.offer-templates.index') }}" theme="light" class="!py-2 !text-sm"
                            :active="request()->routeIs('admin.offer-templates.*')">
                            Offer Templates
                        </x-nav-link>
                    </x-nav-dropdown>
                    @endif

                    <!-- Homepage -->
                    @if(auth()->user()->can('manage_homepage'))
                    <x-nav-dropdown title="Homepage" icon="layout" theme="light"
                        :active="request()->routeIs('admin.homepage.*')">
                        <x-nav-link href="{{ route('admin.homepage.hero.index') }}" theme="light" class="!py-2 !text-sm"
                            :active="request()->routeIs('admin.homepage.hero.*')">Hero Banner</x-nav-link>
                        <x-nav-link href="{{ route('admin.homepage.trending') }}" theme="light" class="!py-2 !text-sm"
                            :active="request()->routeIs('admin.homepage.trending')">Trending
                            Now</x-nav-link>
                        <x-nav-link href="{{ route('admin.homepage.category-sections') }}" theme="light" class="!py-2 !text-sm"
                            :active="request()->routeIs('admin.homepage.category-sections*')">Category Sections</x-nav-link>
                        <x-nav-link href="{{ route('admin.homepage.just-for-you') }}" theme="light"
                            class="!py-2 !text-sm" :active="request()->routeIs('admin.homepage.just-for-you')">Just For
                            You</x-nav-link>
                    </x-nav-dropdown>
                    @endif

                    <!-- Analytics -->
                    @if(auth()->user()->can('view_analytics'))
                    <x-nav-dropdown title="Analytics" icon="chart-polar" theme="light"
                        :active="request()->routeIs('admin.analytics.*')">
                        <x-nav-link href="{{ route('admin.analytics.search') }}" theme="light" class="!py-2 !text-sm"
                            :active="request()->routeIs('admin.analytics.search')">
                            Search Analytics
                        </x-nav-link>
                        <x-nav-link href="{{ route('admin.analytics.sales') }}" theme="light" class="!py-2 !text-sm"
                            :active="request()->routeIs('admin.analytics.sales')">
                            Sales Analytics
                        </x-nav-link>
                        <x-nav-link href="{{ route('admin.analytics.visitors') }}" theme="light" class="!py-2 !text-sm"
                            :active="request()->routeIs('admin.analytics.visitors')">
                            Visitor Analytics
                        </x-nav-link>
                    </x-nav-dropdown>
                    @endif

                    <!-- Blacklist -->
                    @if(auth()->user()->can('manage_settings') || auth()->user()->can('manage_customers'))
                    <x-nav-link href="{{ route('admin.blacklist.index') }}" icon="shield-warning" theme="light"
                        :active="request()->routeIs('admin.blacklist.*')">
                        Blacklist / Ban
                    </x-nav-link>
                    @endif

                    <!-- Contact Messages -->
                    @if(auth()->user()->can('manage_settings') || auth()->user()->can('manage_customers'))
                    <x-nav-link href="{{ route('admin.contact-messages.index') }}" icon="chat-circle-dots" theme="light"
                        :active="request()->routeIs('admin.contact-messages.*')">
                        <div class="flex items-center justify-between w-full">
                            <span>Contact Messages</span>
                            @php
                                $newContactCount = \App\Models\ContactMessage::where('status', 'new')->count();
                            @endphp
                            @if($newContactCount > 0)
                                <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-brand-600 text-white">
                                    {{ $newContactCount }}
                                </span>
                            @endif
                        </div>
                    </x-nav-link>
                    @endif

                    <!-- Settings -->
                    @if(auth()->user()->can('manage_settings') || auth()->user()->can('manage_staff'))
                    <x-nav-link href="{{ route('admin.settings.index') }}" icon="gear" theme="light"
                        :active="request()->routeIs('admin.settings.*')">
                        Settings
                    </x-nav-link>
                    @endif
                </nav>
            </div>
            <div class="p-4 border-t border-gray-100 shrink-0">
                <a href="{{ route('logout') }}"
                    class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-colors text-sm font-medium text-red-500 hover:bg-red-50">
                    <i class="ph ph-sign-out text-lg"></i> Logout
                </a>
            </div>
        </aside>

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col min-w-0 bg-gray-50 h-full overflow-hidden">

            <!-- Admin Top Header -->
            <header class="h-16 bg-white shadow-sm flex items-center justify-between px-4 sm:px-6 lg:px-8 shrink-0 z-10">
                <div class="flex items-center flex-1 min-w-0 mr-2">
                    <button @click="sidebarOpen = true" type="button"
                        class="lg:hidden p-2 -ml-2 mr-2 rounded-lg text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none cursor-pointer"
                        title="Open Menu">
                        <i class="ph ph-list text-2xl"></i>
                    </button>
                    <h2 class="text-base sm:text-lg font-bold text-gray-800 truncate">@yield('header_title', 'Dashboard')</h2>
                </div>

                <!-- Right Actions -->
                <div class="flex items-center gap-2 sm:gap-3.5 ml-2 sm:ml-4">
                    <!-- Global Header Search -->
                    <div x-data="globalHeaderSearch()" class="relative" @click.away="showDropdown = false">
                        <div class="relative w-36 sm:w-56 md:w-64 lg:w-72">
                            <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                            <input type="text"
                                   x-ref="globalSearchInput"
                                   x-model="query"
                                   @input.debounce.300ms="search()"
                                   @focus="if(query.length >= 2 && (orders.length > 0 || products.length > 0)) showDropdown = true"
                                   @keydown.escape="showDropdown = false"
                                   placeholder="Search orders, phone, CID, products..."
                                   class="w-full bg-gray-50 hover:bg-gray-100 focus:bg-white border border-gray-200 focus:border-brand-500 rounded-xl pl-8.5 pr-8 py-1.5 text-xs text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500/20 transition-all shadow-2xs">
                            <i x-show="loading" class="ph ph-spinner animate-spin absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs" style="display: none;"></i>
                            <button type="button" 
                                    x-show="query && !loading" 
                                    @click="query = ''; orders = []; products = []; showDropdown = false;" 
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-xs"
                                    style="display: none;">
                                <i class="ph ph-x"></i>
                            </button>
                        </div>

                        <!-- Search Suggestions Dropdown -->
                        <div x-show="showDropdown && (orders.length > 0 || products.length > 0 || hasSearched)" 
                             x-transition.opacity.duration.200ms
                             style="display: none;"
                             class="absolute right-0 sm:left-0 sm:right-auto mt-2 w-[320px] sm:w-[380px] md:w-[420px] bg-white rounded-2xl shadow-2xl border border-gray-200 z-[120] max-h-[460px] overflow-y-auto divide-y divide-gray-100">
                            
                            <!-- Orders Section -->
                            <template x-if="orders.length > 0">
                                <div class="p-3">
                                    <div class="flex items-center justify-between px-2 mb-2">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 flex items-center gap-1">
                                            <i class="ph-bold ph-shopping-cart text-brand-600"></i> Orders
                                        </span>
                                        <span class="text-[10px] font-semibold text-gray-400" x-text="orders.length + ' found'"></span>
                                    </div>
                                    <div class="space-y-1">
                                        <template x-for="order in orders" :key="order.id">
                                            <a :href="order.url" 
                                               class="flex items-center justify-between p-2 rounded-xl hover:bg-brand-50/60 transition-colors group cursor-pointer">
                                                <div class="min-w-0 pr-2">
                                                    <div class="flex items-center gap-2">
                                                        <span class="font-bold text-xs text-brand-700 group-hover:underline" x-text="'#' + order.order_id"></span>
                                                        <template x-if="order.steadfast_consignment_id">
                                                            <span class="inline-flex items-center gap-0.5 text-[10px] font-mono text-pink-700 bg-pink-50 border border-pink-200 px-1.5 py-0.2 rounded font-semibold">
                                                                <span>CID:</span> <span x-text="order.steadfast_consignment_id"></span>
                                                            </span>
                                                        </template>
                                                    </div>
                                                    <div class="text-[11px] text-gray-700 font-medium truncate mt-0.5">
                                                        <span x-text="order.customer_name"></span>
                                                        <template x-if="order.customer_phone">
                                                            <span class="text-gray-400 font-mono" x-text="' · ' + order.customer_phone"></span>
                                                        </template>
                                                    </div>
                                                </div>
                                                <div class="text-right shrink-0">
                                                    <span class="text-xs font-bold text-gray-900 block" x-text="'৳' + order.total"></span>
                                                    <span class="text-[9px] uppercase font-bold tracking-wider px-1.5 py-0.5 rounded"
                                                          :class="{
                                                             'bg-emerald-50 text-emerald-700': order.status === 'delivered',
                                                             'bg-purple-50 text-purple-700': order.status === 'shipped',
                                                             'bg-indigo-50 text-indigo-700': order.status === 'processing',
                                                             'bg-amber-50 text-amber-700': order.status === 'pending' || order.status === 'confirmed',
                                                             'bg-red-50 text-red-700': order.status === 'cancelled' || order.status === 'returned',
                                                          }"
                                                          x-text="order.status"></span>
                                                </div>
                                            </a>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <!-- Products Section -->
                            <template x-if="products.length > 0">
                                <div class="p-3">
                                    <div class="flex items-center justify-between px-2 mb-2">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 flex items-center gap-1">
                                            <i class="ph-bold ph-package text-brand-600"></i> Products
                                        </span>
                                        <span class="text-[10px] font-semibold text-gray-400" x-text="products.length + ' found'"></span>
                                    </div>
                                    <div class="space-y-1">
                                        <template x-for="product in products" :key="product.id">
                                            <a :href="product.url" 
                                               class="flex items-center gap-2.5 p-2 rounded-xl hover:bg-brand-50/60 transition-colors group cursor-pointer">
                                                <div class="w-9 h-9 rounded-lg bg-gray-100 overflow-hidden flex items-center justify-center shrink-0 border border-gray-200">
                                                    <img x-show="product.image" :src="product.image" :alt="product.title" class="w-full h-full object-cover">
                                                    <i x-show="!product.image" class="ph ph-package text-gray-400 text-base"></i>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-xs font-medium text-gray-900 truncate group-hover:text-brand-700" x-text="product.title"></p>
                                                    <div class="flex items-center gap-2 text-[10px] text-gray-500 mt-0.5">
                                                        <span class="font-bold text-brand-600" x-text="'৳' + product.price"></span>
                                                        <template x-if="product.sku">
                                                            <span class="font-mono text-gray-600 bg-gray-100 px-1 rounded" x-text="'SKU: ' + product.sku"></span>
                                                        </template>
                                                        <span :class="product.stock > 0 ? 'text-emerald-600 font-medium' : 'text-red-500 font-medium'" x-text="product.stock > 0 ? 'Stock: ' + product.stock : 'Out of Stock'"></span>
                                                    </div>
                                                </div>
                                            </a>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <!-- Empty State -->
                            <template x-if="hasSearched && orders.length === 0 && products.length === 0 && !loading">
                                <div class="p-6 text-center text-xs text-gray-500">
                                    <i class="ph ph-magnifying-glass text-xl text-gray-400 mb-1 block"></i>
                                    <span>No orders or products matching "<strong x-text="query" class="text-gray-700"></strong>"</span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Steadfast Merchant ID Badge -->
                    @if(!empty($headerMerchantId) && $headerShowMerchantId == '1')
                        <div x-data="{ copiedMid: false }"
                             @click="navigator.clipboard.writeText('{{ $headerMerchantId }}'); copiedMid = true; setTimeout(() => copiedMid = false, 2000)"
                             title="Steadfast Merchant ID (Click to copy)"
                             class="hidden md:inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-pink-50 hover:bg-pink-100 border border-pink-200 text-pink-700 text-xs font-semibold cursor-pointer select-none transition-colors shadow-2xs group shrink-0">
                            <i class="ph-bold ph-truck text-pink-600"></i>
                            <span class="text-gray-500 text-[10px] font-bold uppercase tracking-wider">MID:</span>
                            <span class="font-mono font-black text-pink-800">{{ $headerMerchantId }}</span>
                            <i class="ph text-xs" :class="copiedMid ? 'ph-bold ph-check text-green-600' : 'ph-copy text-pink-400 group-hover:text-pink-600'"></i>
                        </div>
                    @endif

                    <!-- Realtime Visitor Badge with Authentic YouTube/Odometer style Rolling Reel -->
                    <div class="hidden sm:inline-flex items-center gap-2.5 px-3.5 py-1.5 rounded-full bg-emerald-50 border border-emerald-200/80 text-xs font-semibold text-emerald-900 shadow-xs select-none shrink-0"
                         x-data="liveVisitorPoller()"
                         title="Live active visitors browsing the store">
                        <span class="relative flex h-2.5 w-2.5">
                            <span
                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                        </span>

                        <!-- True YouTube Reel Odometer -->
                        <div
                            class="odometer-reel flex items-center font-mono font-black text-sm tracking-tighter text-emerald-950">
                            <template x-for="(d, idx) in digits" :key="idx">
                                <div class="odometer-digit-col relative overflow-hidden inline-block text-center"
                                    style="height: 18px; width: 10px; line-height: 18px;">
                                    <div class="odometer-ribbon flex flex-col"
                                        :class="animated ? 'transition-transform duration-700 ease-[cubic-bezier(0.25,1,0.5,1)]' : ''"
                                        :style="'transform: translateY(-' + (parseInt(d) * 18) + 'px)'">
                                        <span class="h-[18px] leading-[18px] block">0</span>
                                        <span class="h-[18px] leading-[18px] block">1</span>
                                        <span class="h-[18px] leading-[18px] block">2</span>
                                        <span class="h-[18px] leading-[18px] block">3</span>
                                        <span class="h-[18px] leading-[18px] block">4</span>
                                        <span class="h-[18px] leading-[18px] block">5</span>
                                        <span class="h-[18px] leading-[18px] block">6</span>
                                        <span class="h-[18px] leading-[18px] block">7</span>
                                        <span class="h-[18px] leading-[18px] block">8</span>
                                        <span class="h-[18px] leading-[18px] block">9</span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <span class="text-emerald-700 font-bold text-[10px] tracking-wider uppercase">Online</span>
                    </div>

                    <a href="/" target="_blank"
                        class="text-sm font-medium text-gray-500 hover:text-brand-600 flex items-center gap-1 transition-colors">
                        <i class="ph ph-arrow-square-out text-lg"></i>
                        <span class="hidden sm:inline">View Site</span>
                    </a>
                    <div class="h-6 w-px bg-gray-200 mx-1 sm:mx-2"></div>

                    <div class="flex items-center gap-2 sm:gap-3">
                        @if(Auth::user()->avatar)
                            <img src="{{ Auth::user()->avatar }}" alt="Admin"
                                class="w-8 h-8 rounded-full object-cover border border-gray-200 shrink-0">
                        @else
                            <div
                                class="w-8 h-8 rounded-full bg-brand-100 flex items-center justify-center text-brand-600 text-sm font-bold shrink-0">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                        @endif
                        <div class="hidden sm:block text-sm font-medium text-gray-700 truncate max-w-[120px]">
                            {{ Auth::user()->name }}
                        </div>
                    </div>
                </div>
            </header>

            <!-- Scrollable Content -->
            <main class="flex-1 overflow-y-auto overflow-x-hidden p-4 sm:p-6 lg:p-8">
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Toast Notification -->
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform translate-y-2"
            class="fixed bottom-6 right-6 z-50 flex items-center gap-3 bg-gray-900 text-white px-5 py-3.5 rounded-xl shadow-xl">
            <i class="ph-fill ph-check-circle text-green-400 text-xl"></i>
            <p class="text-sm font-medium">{{ session('success') }}</p>
            <button @click="show = false" class="ml-2 text-gray-400 hover:text-white transition-colors">
                <i class="ph ph-x"></i>
            </button>
        </div>
    @endif

    <!-- Global Delete Modal -->
    <div x-data="{ 
            deleteModalOpen: false, 
            deleteUrl: '', 
            deleteTitle: 'Confirm Deletion',
            deleteMessage: 'Are you sure you want to delete this item? This action cannot be undone.'
         }" @open-delete-modal.window="
            deleteModalOpen = true; 
            deleteUrl = $event.detail.url;
            if($event.detail.title) deleteTitle = $event.detail.title;
            if($event.detail.message) deleteMessage = $event.detail.message;
         " class="relative z-[100]" aria-labelledby="modal-title" role="dialog" aria-modal="true"
        x-show="deleteModalOpen" style="display: none;">

        <div x-show="deleteModalOpen" x-transition.opacity
            class="fixed inset-0 bg-gray-900/60 backdrop-blur-md transition-opacity"></div>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div x-show="deleteModalOpen" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
                    @click.away="deleteModalOpen = false"
                    class="relative transform overflow-hidden rounded-[2rem] bg-white text-left shadow-2xl shadow-red-900/10 transition-all sm:my-8 sm:w-full sm:max-w-md border border-red-50">

                    <!-- Decorative header background -->
                    <div class="absolute top-0 left-0 right-0 h-32 bg-gradient-to-b from-red-50 to-white"></div>

                    <div class="relative bg-white px-6 pb-6 pt-10 sm:p-8 sm:pb-8">
                        <div class="flex flex-col items-center">
                            <div
                                class="mx-auto flex h-20 w-20 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-red-100 to-red-50 shadow-inner mb-5">
                                <i class="ph-fill ph-trash text-4xl text-red-600 drop-shadow-sm"></i>
                            </div>
                            <div class="mt-2 text-center">
                                <h3 class="text-2xl font-bold text-gray-900 tracking-tight" id="modal-title"
                                    x-text="deleteTitle"></h3>
                                <div class="mt-3">
                                    <p class="text-base text-gray-500 leading-relaxed" x-text="deleteMessage"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        class="relative bg-gray-50/80 px-6 py-5 sm:flex sm:flex-row-reverse sm:px-8 gap-3 border-t border-gray-100">
                        <form :action="deleteUrl" method="POST" class="inline-block w-full sm:w-auto flex-1">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="inline-flex w-full justify-center items-center gap-2 rounded-xl bg-gradient-to-r from-red-600 to-red-500 px-5 py-3 text-sm font-semibold text-white shadow-md shadow-red-500/20 hover:from-red-700 hover:to-red-600 transition-all duration-300 sm:w-auto transform hover:-translate-y-0.5">
                                Yes, Delete It
                            </button>
                        </form>
                        <button type="button" @click="deleteModalOpen = false"
                            class="mt-3 inline-flex w-full justify-center items-center rounded-xl bg-white px-5 py-3 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-200 hover:bg-gray-50 transition-all sm:mt-0 sm:w-auto flex-1">
                            Cancel, Keep It
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Realtime Tracker & Poller for Admin -->
    <script>
        function globalHeaderSearch() {
            return {
                query: '',
                orders: [],
                products: [],
                loading: false,
                showDropdown: false,
                hasSearched: false,
                async search() {
                    const q = (this.query || '').trim();
                    if (q.length < 2) {
                        this.orders = [];
                        this.products = [];
                        this.showDropdown = false;
                        this.hasSearched = false;
                        return;
                    }
                    this.loading = true;
                    try {
                        const res = await fetch(`{{ route('admin.api.global-search') }}?q=${encodeURIComponent(q)}`);
                        const data = await res.json();
                        this.orders = data.orders || [];
                        this.products = data.products || [];
                        this.showDropdown = true;
                        this.hasSearched = true;
                    } catch (e) {
                        console.error(e);
                    } finally {
                        this.loading = false;
                    }
                }
            };
        }

        function liveVisitorPoller() {
            return {
                liveCount: 0,
                animated: false,
                get digits() {
                    return String(this.liveCount || 0).split('');
                },
                async fetchLive() {
                    try {
                        const res = await fetch("/visitor-heartbeat.php?stats=1");
                        if (res.ok) {
                            const data = await res.json();
                            const newLive = parseInt(data.live) || 0;
                            if (!this.animated) {
                                this.liveCount = newLive;
                                setTimeout(() => {
                                    this.animated = true;
                                }, 100);
                            } else {
                                this.liveCount = newLive;
                            }
                        }
                    } catch (e) { }
                },
                init() {
                    // Purge any accidental visitor tokens for admin/staff
                    try {
                        var oldVid = localStorage.getItem('fb_vid');
                        if (oldVid) {
                            localStorage.removeItem('fb_vid');
                            localStorage.removeItem('fb_v_profile');
                            localStorage.removeItem('fb_v_history');
                            sessionStorage.removeItem('fb_daily_serial');
                            var payload = JSON.stringify({
                                vid: oldVid,
                                act: 'remove_staff',
                                is_staff: true,
                                user_id: {{ Auth::id() }}
                            });
                            if (navigator.sendBeacon) {
                                navigator.sendBeacon('/visitor-heartbeat.php', payload);
                            } else {
                                fetch('/visitor-heartbeat.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: payload
                                }).catch(function () {});
                            }
                        }
                    } catch (e) {}

                    this.fetchLive();
                    // Instant responsiveness: 5-second poll (takes only ~0.2ms per call)
                    setInterval(() => {
                        this.fetchLive();
                    }, 5000);
                }
            }
        }
    </script>
    @stack('scripts')
</body>

</html>