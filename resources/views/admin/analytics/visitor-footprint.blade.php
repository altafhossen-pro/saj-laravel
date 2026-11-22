@extends('layouts.admin')

@section('header_title', 'Visitor Footprint & Activity Logs')

@section('content')
    <div class="space-y-6" x-data="visitorFootprintManager()">

        <!-- Page Header & Filters -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold text-gray-900">Visitor Footprint & Audit</h1>
                    <span
                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                        <i class="ph ph-footprints"></i>
                        <span>Historical Log</span>
                    </span>
                </div>
                <p class="text-sm text-gray-500 mt-1">Review historical shopper footprints, visited pages, devices &
                    behaviors by date.</p>
            </div>

            <!-- Date Picker & Filter Form -->
            <form method="GET" action="{{ route('admin.visitors.footprint') }}" x-data="{ selectedRange: '{{ $range }}' }" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="filter" value="{{ $filter }}">

                <!-- Date Range Preset Dropdown -->
                <div class="relative">
                    <select name="range" x-model="selectedRange" @change="if (selectedRange !== 'custom') $el.form.submit()"
                        class="px-3.5 py-2 bg-white border border-gray-200 rounded-xl text-sm font-semibold text-gray-800 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer">
                        <option value="today">Today (Default)</option>
                        <option value="yesterday">Yesterday</option>
                        <option value="last_7_days">Last 7 Days</option>
                        <option value="last_1_month">Last 1 Month</option>
                        <option value="last_1_year">Last 1 Year</option>
                        <option value="custom">Custom Date Range...</option>
                    </select>
                </div>

                <!-- Custom Date Inputs (Visible only when 'custom' is selected) -->
                <div x-show="selectedRange === 'custom'" class="flex items-center gap-1.5" style="display: none;">
                    <input type="date" name="start_date" value="{{ $startDate }}"
                        class="px-2.5 py-2 bg-white border border-gray-200 rounded-xl text-xs font-medium text-gray-700 shadow-sm focus:ring-2 focus:ring-indigo-500 cursor-pointer"
                        title="Start Date">
                    <span class="text-xs text-gray-400 font-bold">to</span>
                    <input type="date" name="end_date" value="{{ $endDate }}"
                        class="px-2.5 py-2 bg-white border border-gray-200 rounded-xl text-xs font-medium text-gray-700 shadow-sm focus:ring-2 focus:ring-indigo-500 cursor-pointer"
                        title="End Date">
                </div>

                <div class="relative">
                    <select name="sort" onchange="this.form.submit()"
                        class="px-3.5 py-2 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-700 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer">
                        <option value="recent" {{ ($sort ?? 'recent') === 'recent' ? 'selected' : '' }}>Sort: Most Recent Active (Latest)</option>
                        <option value="serial" {{ ($sort ?? '') === 'serial' ? 'selected' : '' }}>Sort: Visitor ID (#1, #2...)</option>
                    </select>
                </div>

                <div class="relative">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search ID, phone, name..."
                        class="pl-9 pr-3.5 py-2 bg-white border border-gray-200 rounded-xl text-sm text-gray-700 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 w-48 sm:w-64">
                    <i class="ph ph-magnifying-glass absolute left-3 top-2.5 text-gray-400 text-base"></i>
                </div>

                <button type="submit"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm rounded-xl transition-colors shadow-sm cursor-pointer">
                    Filter
                </button>

                @if($search || $range !== 'today' || $filter !== 'all' || ($sort ?? 'recent') !== 'recent')
                    <a href="{{ route('admin.visitors.footprint') }}"
                        class="px-3 py-2 text-gray-500 hover:text-gray-800 text-sm font-medium">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        <!-- Summary Metrics Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
            <!-- Total Visitors -->
            <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between text-gray-500 mb-1">
                    <span class="text-xs font-semibold uppercase tracking-wider">Visitors</span>
                    <i class="ph ph-users text-indigo-600 text-lg"></i>
                </div>
                <div class="text-2xl font-bold text-gray-900">{{ number_format($metrics['total_visitors']) }}</div>
                <div class="text-[11px] text-gray-400 mt-0.5">
                    @if($range === 'today')
                        Today's unique shoppers
                    @elseif($range === 'yesterday')
                        Yesterday's unique shoppers
                    @elseif($startDate === $endDate)
                        {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }}
                    @else
                        {{ \Carbon\Carbon::parse($startDate)->format('M d') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
                    @endif
                </div>
            </div>

            <!-- Total Page Views -->
            <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between text-gray-500 mb-1">
                    <span class="text-xs font-semibold uppercase tracking-wider">Page Views</span>
                    <i class="ph ph-browsers text-blue-600 text-lg"></i>
                </div>
                <div class="text-2xl font-bold text-gray-900">{{ number_format($metrics['total_page_views']) }}</div>
                <div class="text-[11px] text-gray-400 mt-0.5">Clicks across pages</div>
            </div>

            <!-- Returning Visitors -->
            <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between text-gray-500 mb-1">
                    <span class="text-xs font-semibold uppercase tracking-wider">Returning</span>
                    <i class="ph ph-arrow-counter-clockwise text-emerald-600 text-lg"></i>
                </div>
                <div class="text-2xl font-bold text-emerald-600">{{ number_format($metrics['returning_count']) }}</div>
                <div class="text-[11px] text-gray-400 mt-0.5">Re-visited our store</div>
            </div>

            <!-- With Cart -->
            <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between text-gray-500 mb-1">
                    <span class="text-xs font-semibold uppercase tracking-wider">Added to Cart</span>
                    <i class="ph ph-shopping-cart text-amber-500 text-lg"></i>
                </div>
                <div class="text-2xl font-bold text-amber-600">{{ number_format($metrics['cart_visitors']) }}</div>
                <div class="text-[11px] text-gray-400 mt-0.5">Active intent shoppers</div>
            </div>

            <!-- Visited Checkout -->
            <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between text-gray-500 mb-1">
                    <span class="text-xs font-semibold uppercase tracking-wider">Checkout</span>
                    <i class="ph ph-check-circle text-rose-500 text-lg"></i>
                </div>
                <div class="text-2xl font-bold text-rose-600">{{ number_format($metrics['checkout_visitors']) }}</div>
                <div class="text-[11px] text-gray-400 mt-0.5">Reached checkout step</div>
            </div>

            <!-- Avg Time on Site -->
            <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between text-gray-500 mb-1">
                    <span class="text-xs font-semibold uppercase tracking-wider">Avg Time</span>
                    <i class="ph ph-clock text-purple-600 text-lg"></i>
                </div>
                <div class="text-2xl font-bold text-purple-600">
                    @php
                        $avgSec = $metrics['avg_duration_seconds'];
                        $avgMin = floor($avgSec / 60);
                        $remSec = $avgSec % 60;
                    @endphp
                    {{ $avgMin }}m {{ $remSec }}s
                </div>
                <div class="text-[11px] text-gray-400 mt-0.5">Stayed on website</div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="flex items-center gap-2 border-b border-gray-200 pb-3 overflow-x-auto text-xs font-medium">
            <a href="{{ route('admin.visitors.footprint', ['range' => $range, 'start_date' => $startDate, 'end_date' => $endDate, 'filter' => 'all', 'search' => $search, 'sort' => $sort]) }}"
                class="px-3.5 py-1.5 rounded-lg transition-colors flex items-center gap-1.5 {{ $filter === 'all' ? 'bg-indigo-600 text-white font-semibold' : 'bg-gray-100 text-gray-600 hover:text-gray-900' }}">
                <span>All Shoppers</span>
                <span
                    class="px-1.5 py-0.2 rounded-full text-[10px] {{ $filter === 'all' ? 'bg-indigo-700 text-white' : 'bg-gray-200 text-gray-700' }}">{{ $metrics['total_visitors'] }}</span>
            </a>

            <a href="{{ route('admin.visitors.footprint', ['range' => $range, 'start_date' => $startDate, 'end_date' => $endDate, 'filter' => 'returning', 'search' => $search, 'sort' => $sort]) }}"
                class="px-3.5 py-1.5 rounded-lg transition-colors flex items-center gap-1.5 {{ $filter === 'returning' ? 'bg-indigo-600 text-white font-semibold' : 'bg-gray-100 text-gray-600 hover:text-gray-900' }}">
                <i class="ph ph-arrow-counter-clockwise"></i>
                <span>Returning Visitors</span>
                <span
                    class="px-1.5 py-0.2 rounded-full text-[10px] {{ $filter === 'returning' ? 'bg-indigo-700 text-white' : 'bg-gray-200 text-gray-700' }}">{{ $metrics['returning_count'] }}</span>
            </a>

            <a href="{{ route('admin.visitors.footprint', ['range' => $range, 'start_date' => $startDate, 'end_date' => $endDate, 'filter' => 'with_cart', 'search' => $search, 'sort' => $sort]) }}"
                class="px-3.5 py-1.5 rounded-lg transition-colors flex items-center gap-1.5 {{ $filter === 'with_cart' ? 'bg-indigo-600 text-white font-semibold' : 'bg-gray-100 text-gray-600 hover:text-gray-900' }}">
                <i class="ph ph-shopping-cart"></i>
                <span>With Cart Items</span>
                <span
                    class="px-1.5 py-0.2 rounded-full text-[10px] {{ $filter === 'with_cart' ? 'bg-indigo-700 text-white' : 'bg-gray-200 text-gray-700' }}">{{ $metrics['cart_visitors'] }}</span>
            </a>

            <a href="{{ route('admin.visitors.footprint', ['range' => $range, 'start_date' => $startDate, 'end_date' => $endDate, 'filter' => 'checkout', 'search' => $search, 'sort' => $sort]) }}"
                class="px-3.5 py-1.5 rounded-lg transition-colors flex items-center gap-1.5 {{ $filter === 'checkout' ? 'bg-indigo-600 text-white font-semibold' : 'bg-gray-100 text-gray-600 hover:text-gray-900' }}">
                <i class="ph ph-credit-card"></i>
                <span>Visited Checkout</span>
                <span
                    class="px-1.5 py-0.2 rounded-full text-[10px] {{ $filter === 'checkout' ? 'bg-indigo-700 text-white' : 'bg-gray-200 text-gray-700' }}">{{ $metrics['checkout_visitors'] }}</span>
            </a>

            <a href="{{ route('admin.visitors.footprint', ['range' => $range, 'start_date' => $startDate, 'end_date' => $endDate, 'filter' => 'identified', 'search' => $search, 'sort' => $sort]) }}"
                class="px-3.5 py-1.5 rounded-lg transition-colors flex items-center gap-1.5 {{ $filter === 'identified' ? 'bg-indigo-600 text-white font-semibold' : 'bg-gray-100 text-gray-600 hover:text-gray-900' }}">
                <i class="ph ph-identification-badge"></i>
                <span>Recognized Profiles</span>
                <span
                    class="px-1.5 py-0.2 rounded-full text-[10px] {{ $filter === 'identified' ? 'bg-indigo-700 text-white' : 'bg-gray-200 text-gray-700' }}">{{ $metrics['identified_visitors'] }}</span>
            </a>
        </div>

        <!-- Visitors Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead
                        class="bg-gray-50/80 text-gray-500 font-medium border-b border-gray-100 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-5 py-4 text-center">ID</th>
                            <th class="px-6 py-4">Visitor Identity</th>
                            <th class="px-6 py-4">Entry & Last Seen Page</th>
                            <th class="px-6 py-4">Device / System</th>
                            <th class="px-6 py-4">IP & Location</th>
                            <th class="px-6 py-4 text-center">Time & Duration</th>
                            <th class="px-6 py-4 text-center">Intent Flags</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($sessions as $s)
                            @php
                                $visitorObj = [
                                    'id' => (int) $s->id,
                                    'vid' => (string) $s->visitor_token,
                                    'serial' => (int) ($s->daily_serial ?: $s->id),
                                    'name' => (string) ($s->customer_name ?: ($s->user?->name ?: 'Guest Visitor')),
                                    'phone' => (string) ($s->customer_phone ?: ($s->user?->phone ?: '')),
                                    'address' => (string) ($s->customer_address ?: ''),
                                    'user_id' => $s->user_id ? (int) $s->user_id : null,
                                    'device' => (string) ($s->device ?: 'Desktop'),
                                    'has_cart' => (bool) $s->has_cart,
                                ];
                            @endphp
                            <tr class="hover:bg-gray-50/80 transition-colors select-none group cursor-context-menu"
                                @contextmenu.prevent='openContextMenu($event, @json($visitorObj))'>
                                <!-- Visitor ID -->
                                <td class="px-5 py-4 text-center">
                                    <span
                                        class="inline-flex items-center justify-center min-w-8 h-7 px-2 rounded-lg bg-indigo-50 font-mono font-bold text-xs text-indigo-700 border border-indigo-200"
                                        title="Daily Visitor ID">
                                        #{{ $s->daily_serial ?: $s->id }}
                                    </span>
                                </td>

                                <!-- Visitor Identity -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs shrink-0 {{ $s->customer_name || $s->user_id ? 'bg-indigo-50 text-indigo-600 border border-indigo-200' : 'bg-gray-100 text-gray-600' }}">
                                            @if($s->customer_name)
                                                {{ strtoupper(substr($s->customer_name, 0, 2)) }}
                                            @elseif($s->user?->name)
                                                {{ strtoupper(substr($s->user->name, 0, 2)) }}
                                            @else
                                                <span class="font-mono text-[10px]">#{{ $s->daily_serial ?: $s->id }}</span>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="font-semibold text-gray-900 flex items-center gap-1.5 flex-wrap">
                                                <span
                                                    class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                    Visitor #{{ $s->daily_serial ?: $s->id }}
                                                </span>
                                                @if($s->customer_name)
                                                    <span>{{ $s->customer_name }}</span>
                                                @elseif($s->user?->name)
                                                    <span>{{ $s->user->name }}</span>
                                                    <span
                                                        class="text-[10px] bg-blue-50 text-blue-600 px-1.5 py-0.2 rounded font-medium">Member</span>
                                                @else
                                                    <span class="text-gray-700">Guest Visitor</span>
                                                @endif

                                                @if($s->is_returning)
                                                    <span
                                                        class="text-[10px] bg-emerald-50 text-emerald-700 border border-emerald-200 px-1.5 py-0.2 rounded font-medium"
                                                        title="Visited in previous days">
                                                        Returning
                                                    </span>
                                                @endif
                                            </div>

                                            @if($s->customer_phone || $s->user?->phone)
                                                @php $ph = $s->customer_phone ?: $s->user?->phone; @endphp
                                                <div class="inline-flex items-center gap-1.5 mt-0.5 group/phone">
                                                    <button type="button"
                                                        @click.stop="copyText('{{ addslashes($ph) }}', 'Phone copied: {{ addslashes($ph) }}')"
                                                        class="inline-flex items-center gap-1 px-1.5 py-0.5 -ml-1 rounded-md text-xs text-gray-800 font-mono font-semibold hover:bg-brand-50 hover:text-brand-700 transition cursor-pointer"
                                                        title="Click to copy phone number">
                                                        <i class="ph-bold ph-phone text-brand-600"></i>
                                                        <span>{{ $ph }}</span>
                                                        <i class="ph ph-copy text-[11px] text-gray-400 group-hover/phone:text-brand-600 transition"></i>
                                                    </button>
                                                </div>
                                            @endif

                                            @php $addr = $s->customer_address ?: ($s->cartSession?->customer_address ?? null); @endphp
                                            @if($addr)
                                                <div class="text-[11px] text-gray-600 flex items-center gap-1 mt-0.5 max-w-xs truncate"
                                                    title="{{ $addr }}">
                                                    <i class="ph ph-map-pin text-rose-500 shrink-0"></i>
                                                    <span class="truncate">{{ $addr }}</span>
                                                </div>
                                            @endif

                                            <div class="text-[10px] text-gray-400 font-mono mt-0.5"
                                                title="Visitor Token: {{ $s->visitor_token }}">
                                                Token: {{ substr($s->visitor_token, 0, 14) }}...
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Entry & Last Seen Page -->
                                <td class="px-6 py-4 max-w-xs">
                                    <div class="text-xs font-semibold text-gray-900 truncate"
                                        title="{{ $s->last_title ?: $s->last_url }}">
                                        {{ $s->last_title ?: 'Storefront' }}
                                    </div>
                                    <div class="text-[11px] text-indigo-600 font-mono truncate mt-0.5">
                                        <a href="{{ $s->last_url }}" target="_blank" class="hover:underline">
                                            {{ $s->last_url }}
                                        </a>
                                    </div>
                                    <div class="text-[11px] text-gray-400 mt-1 flex items-center gap-1">
                                        <span class="bg-gray-100 text-gray-600 px-1.5 py-0.2 rounded font-medium">
                                            {{ $s->page_views_count }} {{ Str::plural('page', $s->page_views_count) }}
                                        </span>
                                        @if($s->referrer)
                                            <span class="truncate max-w-[120px]" title="Came from: {{ $s->referrer }}">
                                                • from {{ parse_url($s->referrer, PHP_URL_HOST) ?: $s->referrer }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Device / System -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="text-lg text-gray-500">
                                            @if($s->device === 'Mobile')
                                                <i class="ph ph-device-mobile"></i>
                                            @else
                                                <i class="ph ph-desktop"></i>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="text-xs font-semibold text-gray-800">{{ $s->device }}</div>
                                            <div class="text-[11px] text-gray-400">{{ $s->browser }} • {{ $s->os }}</div>
                                        </div>
                                    </div>
                                </td>

                                <!-- IP & Location -->
                                <td class="px-6 py-4">
                                    <div class="text-xs font-mono font-medium text-gray-700 flex items-center gap-1.5">
                                        <i class="ph ph-globe text-gray-400"></i>
                                        <a href="https://ipinfo.io/{{ $s->ip_address }}" target="_blank"
                                            class="hover:text-indigo-600 hover:underline">
                                            {{ $s->ip_address }}
                                        </a>
                                    </div>
                                </td>

                                <!-- Time & Duration -->
                                <td class="px-6 py-4 text-center">
                                    <div class="text-xs font-semibold text-gray-900">
                                        {{ $s->first_seen_at ? $s->first_seen_at->format('h:i:s A') : '--' }}
                                    </div>
                                    <div class="text-[11px] text-gray-500 mt-0.5">
                                        Stayed: <span class="font-semibold text-gray-700">{{ $s->formatted_duration }}</span>
                                    </div>
                                    <div class="text-[10px] text-gray-400 mt-0.5">
                                        Last: {{ $s->last_seen_at ? $s->last_seen_at->format('h:i:s A') : '--' }}
                                    </div>
                                </td>

                                <!-- Intent Flags -->
                                <td class="px-6 py-4 text-center">
                                    <div class="flex flex-col items-center gap-1">
                                        @if($s->has_cart)
                                            <span
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                                <i class="ph ph-shopping-cart"></i> In Cart
                                            </span>
                                        @endif

                                        @if($s->visited_checkout)
                                            <span
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                                <i class="ph ph-credit-card"></i> Checkout
                                            </span>
                                        @endif

                                        @if(!$s->has_cart && !$s->visited_checkout)
                                            <span class="text-[11px] text-gray-400">Browsing</span>
                                        @endif

                                        @if($s->customOffers && $s->customOffers->count() > 0)
                                            @php $latestOffer = $s->customOffers->first(); @endphp
                                            <span
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border cursor-pointer {{ $latestOffer->status === 'clicked' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : ($latestOffer->status === 'delivered' ? 'bg-blue-50 text-blue-800 border-blue-200' : 'bg-pink-50 text-pink-700 border-pink-200') }}"
                                                @click="openJourney({{ $s->id }})"
                                                title="Offer sent: {{ $latestOffer->title }} ({{ $latestOffer->status }})">
                                                <i class="ph ph-gift"></i>
                                                <span>Offer {{ ucfirst($latestOffer->status) }}</span>
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end">
                                        <!-- Context Menu Dropdown Trigger Button -->
                                        <button type="button" @click='openContextMenu($event, @json($visitorObj))'
                                            class="cursor-pointer p-1.5 rounded-xl text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition inline-flex items-center justify-center"
                                            title="Actions (or Right-Click row)">
                                            <i class="ph-bold ph-dots-three-vertical text-base"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center">
                                    <div
                                        class="w-12 h-12 bg-gray-50 text-gray-400 rounded-full flex items-center justify-center mx-auto mb-3 text-xl">
                                        <i class="ph ph-footprints"></i>
                                    </div>
                                    <div class="font-bold text-gray-900 text-base">No visitor activity found</div>
                                    <div class="text-sm text-gray-500 mt-1">No sessions recorded on
                                        {{ \Carbon\Carbon::parse($date)->format('d M, Y') }} matching criteria.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($sessions->hasPages())
                <div class="p-4 border-t border-gray-100">
                    {{ $sessions->links() }}
                </div>
            @endif
        </div>

        <!-- Custom Modern Context Menu on Row Right Click -->
        <div x-show="contextMenuOpen"
             @click.outside="closeContextMenu()"
             @scroll.window="closeContextMenu()"
             @keydown.escape.window="closeContextMenu()"
             x-cloak
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             :style="`top: ${contextY}px; left: ${contextX}px;`"
             class="fixed z-[120] w-60 bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl border border-gray-100 py-1.5 text-xs text-gray-700 select-none overflow-hidden"
             style="display: none;">

            <!-- Header -->
            <div class="px-3.5 py-2.5 border-b border-gray-100 bg-gray-50/80 flex items-center justify-between">
                <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                    <span class="font-bold text-gray-900 text-xs" x-text="'Shopper #' + (contextVisitor?.serial || 1)"></span>
                </div>
                <span class="text-[10px] text-gray-500 font-medium px-1.5 py-0.5 rounded bg-white border border-gray-200" x-text="contextVisitor?.device || 'Visitor'"></span>
            </div>

            <div class="py-1">
                <!-- 1. Order as Customer (POS) -->
                <button type="button" @click="orderAsCustomer()"
                        class="w-full text-left px-3.5 py-2 hover:bg-brand-50 hover:text-brand-700 flex items-center gap-2.5 transition-colors cursor-pointer group">
                    <i class="ph ph-shopping-cart-simple text-base text-gray-400 group-hover:text-brand-600"></i>
                    <span class="font-semibold text-gray-800 group-hover:text-brand-700">Order as Customer</span>
                </button>

                <!-- 2. View Cart -->
                <button type="button" @click="openCartModal(contextVisitor)"
                        class="w-full text-left px-3.5 py-2 hover:bg-emerald-50 hover:text-emerald-700 flex items-center gap-2.5 transition-colors cursor-pointer group">
                    <i class="ph ph-bag text-base text-gray-400 group-hover:text-emerald-600"></i>
                    <span class="font-semibold text-gray-800 group-hover:text-emerald-700">View Cart</span>
                </button>

                <!-- 3. Send Offer -->
                <button type="button" @click="triggerSendOffer()"
                        class="w-full text-left px-3.5 py-2 hover:bg-pink-50 hover:text-pink-700 flex items-center gap-2.5 transition-colors cursor-pointer group">
                    <i class="ph ph-lightning text-base text-gray-400 group-hover:text-pink-600"></i>
                    <span class="font-semibold text-gray-800 group-hover:text-pink-700">Send Offer</span>
                </button>

                <!-- 4. Visitor Info & History Profile -->
                <button type="button" @click="openInfoModal(contextVisitor)"
                        class="w-full text-left px-3.5 py-2 hover:bg-sky-50 hover:text-sky-700 flex items-center gap-2.5 transition-colors cursor-pointer group">
                    <i class="ph ph-identification-card text-base text-gray-400 group-hover:text-sky-600"></i>
                    <span class="font-semibold text-gray-800 group-hover:text-sky-700">Visitor Info & Notes</span>
                </button>

                <div class="my-1 border-t border-gray-100"></div>

                <!-- 5. Shopper Journey Details -->
                <button type="button" @click="triggerJourney()"
                        class="w-full text-left px-3.5 py-2 hover:bg-indigo-50 hover:text-indigo-700 flex items-center gap-2.5 transition-colors cursor-pointer group">
                    <i class="ph ph-footprints text-base text-gray-400 group-hover:text-indigo-600"></i>
                    <span class="font-medium text-gray-700 group-hover:text-indigo-700">Footprint Journey</span>
                </button>

                <!-- 6. Real-time Live Monitor Shortcut -->
                <button type="button" @click="openLiveMonitor()"
                        class="w-full text-left px-3.5 py-2 hover:bg-gray-100 flex items-center gap-2.5 transition-colors cursor-pointer group">
                    <i class="ph ph-broadcast text-base text-gray-400 group-hover:text-gray-700"></i>
                    <span class="font-medium text-gray-700 group-hover:text-gray-900">Open Live Monitor</span>
                </button>
            </div>
        </div>

        <!-- Real-time Shopper Cart & Checkout Draft Modal -->
        <!-- Visitor Info, Historical Revisions & Timeline Notes Modal -->
        <div x-show="isInfoModalOpen"
            x-cloak
            style="display: none;"
            class="fixed inset-0 z-[110] overflow-y-auto"
            role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity" @click="isInfoModalOpen = false"></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl bg-white rounded-3xl shadow-2xl border border-gray-100 overflow-hidden"
                    @click.stop>

                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/80 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl shrink-0 border border-sky-100">
                                <i class="ph-bold ph-identification-card"></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-base font-bold text-gray-900" x-text="'Shopper #' + (infoModalVisitor?.serial || 1) + ' - Identity & Notes'"></h3>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-100 text-sky-700">Visitor Profile</span>
                                </div>
                                <p class="text-xs text-gray-500 font-mono mt-0.5" x-text="'Token: ' + (infoModalVisitor?.vid || '')"></p>
                            </div>
                        </div>
                        <button type="button" @click="isInfoModalOpen = false" class="p-2 text-gray-400 hover:text-gray-700 rounded-xl hover:bg-gray-100 transition cursor-pointer">
                            <i class="ph ph-x text-lg"></i>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 max-h-[75vh] overflow-y-auto space-y-6">
                        <!-- Loading spinner -->
                        <div x-show="isLoadingInfo" class="py-12 flex flex-col items-center justify-center text-gray-400">
                            <i class="ph ph-spinner animate-spin text-3xl text-sky-600 mb-2"></i>
                            <span class="text-xs font-medium">Fetching visitor info & history...</span>
                        </div>

                        <div x-show="!isLoadingInfo" class="space-y-6">
                            <!-- 1. Current Active Profile Card -->
                            <div class="bg-gradient-to-br from-sky-50/60 via-indigo-50/30 to-white rounded-2xl p-4 border border-sky-100">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-sky-900 flex items-center gap-1.5">
                                        <i class="ph-bold ph-user text-sky-600 text-sm"></i>
                                        Latest Saved Profile
                                    </h4>
                                    <span class="text-[11px] text-gray-400" x-text="infoData?.profile?.last_seen ? 'Last seen ' + infoData.profile.last_seen : ''"></span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <!-- Name -->
                                    <div class="bg-white/90 p-3 rounded-xl border border-gray-100 shadow-2xs">
                                        <div class="text-[10px] text-gray-400 font-semibold uppercase">Customer Name</div>
                                        <div class="flex items-center justify-between gap-1 mt-1">
                                            <span class="font-bold text-gray-900 text-xs truncate" x-text="infoData?.profile?.name || 'Not provided'"></span>
                                            <template x-if="infoData?.profile?.name">
                                                <button type="button" @click="copyText(infoData.profile.name, 'Name copied!')"
                                                        class="p-1 text-gray-400 hover:text-sky-600 rounded hover:bg-sky-50 transition cursor-pointer"
                                                        title="Copy Name">
                                                    <i class="ph ph-copy text-xs"></i>
                                                </button>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Phone -->
                                    <div class="bg-white/90 p-3 rounded-xl border border-gray-100 shadow-2xs">
                                        <div class="text-[10px] text-gray-400 font-semibold uppercase">Phone Number</div>
                                        <div class="flex items-center justify-between gap-1 mt-1">
                                            <span class="font-bold font-mono text-gray-900 text-xs" x-text="infoData?.profile?.phone || 'Not provided'"></span>
                                            <template x-if="infoData?.profile?.phone">
                                                <button type="button" @click="copyText(infoData.profile.phone, 'Phone copied!')"
                                                        class="p-1 text-gray-400 hover:text-sky-600 rounded hover:bg-sky-50 transition cursor-pointer"
                                                        title="Copy Phone">
                                                    <i class="ph ph-copy text-xs"></i>
                                                </button>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Address -->
                                    <div class="bg-white/90 p-3 rounded-xl border border-gray-100 shadow-2xs">
                                        <div class="text-[10px] text-gray-400 font-semibold uppercase">Address</div>
                                        <div class="flex items-center justify-between gap-1 mt-1">
                                            <span class="font-medium text-gray-800 text-xs truncate" :title="infoData?.profile?.address || ''" x-text="infoData?.profile?.address || 'Not provided'"></span>
                                            <template x-if="infoData?.profile?.address">
                                                <button type="button" @click="copyText(infoData.profile.address, 'Address copied!')"
                                                        class="p-1 text-gray-400 hover:text-sky-600 rounded hover:bg-sky-50 transition cursor-pointer"
                                                        title="Copy Address">
                                                    <i class="ph ph-copy text-xs"></i>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Historical Typed Info & Revisions -->
                            <div>
                                <div class="flex items-center justify-between mb-2.5">
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700 flex items-center gap-1.5">
                                        <i class="ph-bold ph-clock-counter-clockwise text-indigo-600 text-sm"></i>
                                        Previous Typed Info & Revisions
                                        <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-indigo-50 text-indigo-700 font-semibold"
                                              x-text="(infoData?.revisions?.length || 0) + ' saved'"></span>
                                    </h4>
                                    <span class="text-[10px] text-gray-400">Click copy on any field to use</span>
                                </div>

                                <div class="space-y-2">
                                    <template x-for="(rev, idx) in (infoData?.revisions || [])" :key="idx">
                                        <div class="bg-gray-50/80 hover:bg-indigo-50/30 p-3 rounded-2xl border border-gray-200/80 transition-colors">
                                            <div class="flex items-center justify-between gap-2 mb-2">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="px-2 py-0.5 rounded-md bg-indigo-100 text-indigo-800 text-[10px] font-bold"
                                                          x-text="'Rev #' + (rev.version || (infoData.revisions.length - idx))"></span>
                                                    <span class="text-[10px] text-gray-400 font-mono" x-text="rev.time"></span>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
                                                <!-- Rev Name -->
                                                <div class="bg-white p-2 rounded-xl border border-gray-100 flex items-center justify-between gap-1">
                                                    <div class="min-w-0">
                                                        <div class="text-[9px] text-gray-400 font-bold uppercase">Name</div>
                                                        <div class="font-medium text-gray-800 truncate" x-text="rev.name || '-'"></div>
                                                    </div>
                                                    <template x-if="rev.name">
                                                        <button type="button" @click="copyText(rev.name, 'Name copied: ' + rev.name)"
                                                                class="p-1 text-gray-400 hover:text-indigo-600 rounded transition cursor-pointer"
                                                                title="Copy Name">
                                                            <i class="ph ph-copy text-xs"></i>
                                                        </button>
                                                    </template>
                                                </div>

                                                <!-- Rev Phone -->
                                                <div class="bg-white p-2 rounded-xl border border-gray-100 flex items-center justify-between gap-1">
                                                    <div class="min-w-0">
                                                        <div class="text-[9px] text-gray-400 font-bold uppercase">Phone</div>
                                                        <div class="font-mono font-medium text-gray-800 truncate" x-text="rev.phone || '-'"></div>
                                                    </div>
                                                    <template x-if="rev.phone">
                                                        <button type="button" @click="copyText(rev.phone, 'Phone copied: ' + rev.phone)"
                                                                class="p-1 text-gray-400 hover:text-indigo-600 rounded transition cursor-pointer"
                                                                title="Copy Phone">
                                                            <i class="ph ph-copy text-xs"></i>
                                                        </button>
                                                    </template>
                                                </div>

                                                <!-- Rev Address -->
                                                <div class="bg-white p-2 rounded-xl border border-gray-100 flex items-center justify-between gap-1">
                                                    <div class="min-w-0">
                                                        <div class="text-[9px] text-gray-400 font-bold uppercase">Address</div>
                                                        <div class="text-gray-800 truncate text-[11px]" :title="rev.address || ''" x-text="rev.address || '-'"></div>
                                                    </div>
                                                    <template x-if="rev.address">
                                                        <button type="button" @click="copyText(rev.address, 'Address copied: ' + rev.address)"
                                                                class="p-1 text-gray-400 hover:text-indigo-600 rounded transition cursor-pointer"
                                                                title="Copy Address">
                                                            <i class="ph ph-copy text-xs"></i>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </template>

                                    <div x-show="!infoData?.revisions || infoData.revisions.length === 0" class="text-center py-4 bg-gray-50 rounded-2xl border border-dashed border-gray-200 text-xs text-gray-400">
                                        No prior typed variations saved for this visitor yet.
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Visitor Notes & Timeline -->
                            <div class="pt-2 border-t border-gray-100">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700 flex items-center gap-1.5">
                                        <i class="ph-bold ph-note-pencil text-amber-600 text-sm"></i>
                                        Admin Notes & Timeline
                                        <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-amber-50 text-amber-700 font-semibold"
                                              x-text="(infoData?.notes?.length || 0) + ' notes'"></span>
                                    </h4>
                                </div>

                                <!-- Add New Note Form -->
                                <div class="bg-amber-50/40 rounded-2xl p-3.5 border border-amber-100 mb-4">
                                    <label class="block text-[11px] font-bold text-amber-900 mb-1.5">Add New Note for this Visitor</label>
                                    <div class="space-y-2">
                                        <textarea x-model="newVisitorNote"
                                                  rows="2"
                                                  placeholder="Type notes (e.g. Called customer, requested follow-up, promised discount, fake order suspect...)"
                                                  class="w-full text-xs p-2.5 rounded-xl border border-amber-200 bg-white focus:outline-none focus:ring-2 focus:ring-amber-400 resize-none"></textarea>
                                        <div class="flex items-center justify-between">
                                            <span class="text-[10px] text-gray-400">Notes are saved permanently with timestamps.</span>
                                            <button type="button"
                                                    @click="saveNote()"
                                                    :disabled="isSavingNote || !newVisitorNote.trim()"
                                                    class="px-3.5 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 disabled:opacity-50 text-white font-bold text-xs shadow-xs transition flex items-center gap-1 cursor-pointer">
                                                <i class="ph ph-plus-circle font-bold" x-show="!isSavingNote"></i>
                                                <i class="ph ph-spinner animate-spin" x-show="isSavingNote"></i>
                                                <span x-text="isSavingNote ? 'Saving...' : 'Add Note'"></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Notes Timeline List -->
                                <div class="space-y-2.5">
                                    <template x-for="n in (infoData?.notes || [])" :key="n.id">
                                        <div class="p-3 bg-white rounded-2xl border border-gray-200/90 shadow-2xs hover:border-amber-200 transition">
                                            <div class="flex items-center justify-between gap-2 mb-1.5">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                    <span class="font-bold text-gray-900 text-xs" x-text="n.admin_name"></span>
                                                </div>
                                                <div class="flex items-center gap-1 text-[10px] text-gray-400 font-mono">
                                                    <span x-text="n.created_at"></span>
                                                    <span x-show="n.time_ago" x-text="'(' + n.time_ago + ')'"></span>
                                                </div>
                                            </div>
                                            <div class="text-xs text-gray-800 whitespace-pre-line pl-3 border-l-2 border-amber-200/80" x-text="n.note"></div>
                                        </div>
                                    </template>

                                    <div x-show="!infoData?.notes || infoData.notes.length === 0" class="text-center py-4 bg-gray-50 rounded-2xl border border-dashed border-gray-200 text-xs text-gray-400">
                                        No notes added for this visitor yet. Add the first note above!
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-3 border-t border-gray-100 bg-gray-50/70 flex items-center justify-end">
                        <button type="button" @click="isInfoModalOpen = false" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold text-xs rounded-xl transition cursor-pointer">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Real-time Shopper Cart & Checkout Draft Modal -->
        <div x-show="isCartModalOpen"
            x-cloak
            style="display: none;"
            class="fixed inset-0 z-[110] overflow-y-auto"
            role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity" @click="isCartModalOpen = false"></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl bg-white rounded-3xl shadow-2xl border border-gray-100 overflow-hidden"
                    @click.stop>

                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/70 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0 border border-emerald-100">
                                <i class="ph-bold ph-bag"></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-base font-bold text-gray-900" x-text="'Shopper #' + (selectedVisitor?.serial || 1) + ' - Cart & Checkout Draft'"></h3>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">Cart Log</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-0.5" x-text="selectedVisitor?.name || 'Customer'"></p>
                            </div>
                        </div>
                        <button type="button" @click="isCartModalOpen = false" class="p-2 text-gray-400 hover:text-gray-700 rounded-xl hover:bg-gray-100 transition cursor-pointer">
                            <i class="ph ph-x text-lg"></i>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 max-h-[75vh] overflow-y-auto space-y-6">
                        <!-- Loading state -->
                        <div x-show="isLoadingCart" class="py-12 flex flex-col items-center justify-center text-gray-400">
                            <i class="ph ph-spinner animate-spin text-3xl text-emerald-600 mb-2"></i>
                            <span class="text-xs font-medium">Fetching cart data...</span>
                        </div>

                        <div x-show="!isLoadingCart" class="space-y-6">
                            <!-- Customer Checkout Draft Profile -->
                            <div class="bg-gray-50/90 rounded-2xl p-4 border border-gray-200/80">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700 flex items-center gap-1.5">
                                        <i class="ph-bold ph-identification-card text-brand-600 text-sm"></i>
                                        Customer Checkout Draft Info
                                    </h4>
                                    <span class="text-[11px] text-gray-400" x-text="cartData?.last_active ? 'Updated ' + cartData.last_active : ''"></span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                                    <div class="bg-white p-3 rounded-xl border border-gray-100 shadow-2xs">
                                        <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider block mb-0.5">Name</span>
                                        <span class="font-bold text-gray-800" x-text="cartData?.customer_name || selectedVisitor?.name || 'Not typed yet'"></span>
                                    </div>
                                    <div class="bg-white p-3 rounded-xl border border-gray-100 shadow-2xs">
                                        <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider block mb-0.5">Mobile Number</span>
                                        <span class="font-bold font-mono text-gray-800" x-text="cartData?.customer_phone || selectedVisitor?.phone || 'Not typed yet'"></span>
                                    </div>
                                    <div class="bg-white p-3 rounded-xl border border-gray-100 shadow-2xs">
                                        <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider block mb-0.5">Delivery Address</span>
                                        <span class="font-medium text-gray-800 line-clamp-2" :title="cartData?.customer_address || selectedVisitor?.address" x-text="cartData?.customer_address || selectedVisitor?.address || 'Not typed yet'"></span>
                                    </div>
                                </div>

                                <!-- Customer Info Revision History (Versioning) -->
                                <template x-if="cartData?.info_history && cartData.info_history.length > 1">
                                    <div class="mt-4 pt-3 border-t border-gray-200/60">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-[11px] font-bold text-gray-700 flex items-center gap-1">
                                                <i class="ph ph-clock-counter-clockwise text-indigo-600 font-bold"></i>
                                                Information Revisions History (<span x-text="cartData.info_history.length"></span> versions)
                                            </span>
                                            <span class="text-[10px] text-gray-400">Customer modified info during session</span>
                                        </div>
                                        <div class="space-y-1.5 max-h-36 overflow-y-auto pr-1">
                                            <template x-for="(rev, idx) in cartData.info_history.slice().reverse()" :key="idx">
                                                <div class="bg-white p-2.5 rounded-xl border border-gray-100 text-[11px] flex items-center justify-between gap-3 shadow-2xs">
                                                    <div class="flex items-center gap-2 min-w-0">
                                                        <span class="px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 font-bold text-[10px]" x-text="'v' + (rev.version || (cartData.info_history.length - idx))"></span>
                                                        <span class="font-bold text-gray-900 truncate" x-text="rev.name || 'No Name'"></span>
                                                        <span class="text-gray-300">&bull;</span>
                                                        <span class="font-mono text-gray-600 truncate" x-text="rev.phone || 'No Phone'"></span>
                                                        <template x-if="rev.address">
                                                            <span class="text-gray-500 truncate" x-text="'&bull; ' + rev.address"></span>
                                                        </template>
                                                    </div>
                                                    <span class="text-[10px] text-gray-400 shrink-0 font-mono" x-text="rev.time"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Shopping Cart Items -->
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700 flex items-center gap-1.5">
                                        <i class="ph-bold ph-shopping-bag text-emerald-600 text-sm"></i>
                                        Shopping Cart Products (<span x-text="cartData?.items ? cartData.items.length : 0"></span> items)
                                    </h4>
                                    <span class="text-xs font-bold text-brand-600" x-text="'Subtotal: ৳' + Math.round(cartData?.total_amount || 0)"></span>
                                </div>

                                <!-- Items List -->
                                <template x-if="cartData?.items && cartData.items.length > 0">
                                    <div class="divide-y divide-gray-100 border border-gray-100 rounded-2xl overflow-hidden bg-white shadow-2xs">
                                        <template x-for="(item, idx) in cartData.items" :key="idx">
                                            <div class="p-3.5 flex items-center justify-between gap-4 hover:bg-gray-50/50 transition">
                                                <div class="flex items-center gap-3 min-w-0">
                                                    <div class="w-12 h-12 rounded-xl bg-gray-100 overflow-hidden shrink-0 border border-gray-100">
                                                        <template x-if="item.image">
                                                            <img :src="item.image.startsWith('http') ? item.image : '/storage/' + item.image" class="w-full h-full object-cover">
                                                        </template>
                                                        <template x-if="!item.image">
                                                            <div class="w-full h-full flex items-center justify-center text-gray-300">
                                                                <i class="ph ph-image text-xl"></i>
                                                            </div>
                                                        </template>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <h5 class="font-bold text-xs text-gray-900 truncate" x-text="item.name"></h5>
                                                        <template x-if="item.variant_name">
                                                            <p class="text-[11px] text-gray-500 mt-0.5" x-text="'Variant: ' + item.variant_name"></p>
                                                        </template>
                                                        <p class="text-[11px] text-gray-400 mt-0.5" x-text="'৳' + Math.round(item.price) + ' × ' + item.quantity"></p>
                                                    </div>
                                                </div>
                                                <div class="text-right shrink-0">
                                                    <span class="text-xs font-bold text-gray-900" x-text="'৳' + Math.round(item.price * item.quantity)"></span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <!-- Empty cart state -->
                                <template x-if="!cartData?.items || cartData.items.length === 0">
                                    <div class="py-8 text-center bg-gray-50/60 rounded-2xl border border-dashed border-gray-200">
                                        <i class="ph ph-shopping-bag-open text-3xl text-gray-300 mb-1"></i>
                                        <p class="text-xs font-medium text-gray-500">Shopper has not added any products to cart yet</p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 bg-gray-50/80 border-t border-gray-100 flex items-center justify-between gap-3">
                        <button type="button" @click="isCartModalOpen = false" class="px-4 py-2 bg-white border border-gray-200 text-gray-600 rounded-xl text-xs font-semibold hover:bg-gray-100 transition cursor-pointer">
                            Close
                        </button>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="openOfferModal(selectedVisitor); isCartModalOpen = false;"
                                    class="px-4 py-2 bg-pink-50 hover:bg-pink-100 text-pink-700 border border-pink-200 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                                <i class="ph ph-lightning text-sm text-pink-600"></i>
                                Send Offer
                            </button>
                            <button type="button" @click="orderAsCustomer()"
                                    class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-bold shadow-md shadow-brand-500/20 transition flex items-center gap-1.5 cursor-pointer">
                                <i class="ph ph-shopping-cart-simple text-sm"></i>
                                Order as Customer (POS)
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Dedicated In-Modal "Order as Customer" POS Modal -->
        <div x-show="isOrderModalOpen"
            x-cloak
            style="display: none;"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/60 backdrop-blur-xs p-3 sm:p-5"
            @keydown.escape.window="isOrderModalOpen = false">

            <div class="bg-white w-full max-w-4xl rounded-2xl shadow-2xl overflow-hidden border border-gray-100 max-h-[92vh] flex flex-col">

                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-brand-50/70 via-gray-50 to-white">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-brand-600 text-white flex items-center justify-center text-lg shadow-sm">
                            <i class="ph ph-shopping-bag"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                                <span>Create Order as Customer</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-brand-100 text-brand-800 uppercase tracking-wider">POS Mode</span>
                            </h3>
                            <p class="text-[11px] text-gray-400">Cart & Checkout details autofilled from session</p>
                        </div>
                    </div>

                    <button type="button" @click="isOrderModalOpen = false" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition cursor-pointer">
                        <i class="ph ph-x text-lg"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-5 sm:p-6 overflow-y-auto space-y-5 flex-1">

                    <!-- Alert Messages -->
                    <template x-if="orderSuccessNotice">
                        <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-semibold flex items-center gap-2">
                            <i class="ph-bold ph-check-circle text-base text-emerald-600"></i>
                            <span x-text="orderSuccessNotice"></span>
                        </div>
                    </template>

                    <template x-if="orderErrorNotice">
                        <div class="p-3 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs font-semibold flex items-center gap-2">
                            <i class="ph-bold ph-warning-circle text-base text-rose-600"></i>
                            <span x-text="orderErrorNotice"></span>
                        </div>
                    </template>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

                        <!-- Left: Customer Info & Order Settings (5 cols) -->
                        <div class="lg:col-span-5 space-y-4">

                            <!-- Customer Info Card -->
                            <div class="bg-gray-50/80 p-4 rounded-xl border border-gray-200/70 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-gray-800 uppercase tracking-wider flex items-center gap-1.5">
                                        <i class="ph-bold ph-user text-brand-600"></i>
                                        Customer Information
                                    </span>

                                    <!-- Version selector if revisions exist -->
                                    <template x-if="cartData?.info_history && cartData.info_history.length > 1">
                                        <select x-model="selectedHistoryVersion" @change="onHistoryVersionChange()"
                                            class="text-[11px] font-semibold bg-white border border-indigo-200 text-indigo-700 rounded-lg px-2 py-0.5 cursor-pointer">
                                            <option value="latest">Latest Typings</option>
                                            <template x-for="rev in cartData.info_history" :key="rev.version">
                                                <option :value="rev.version" x-text="'Version ' + rev.version + ' (' + rev.time + ')'"></option>
                                            </template>
                                        </select>
                                    </template>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-600 mb-1">Customer Name *</label>
                                    <input type="text" x-model="orderForm.customer_name" required placeholder="Full Name"
                                        class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                                </div>

                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-600 mb-1">Phone Number *</label>
                                    <input type="text" x-model="orderForm.customer_contact" required placeholder="01XXXXXXXXX"
                                        class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs font-mono focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                                </div>

                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-600 mb-1">Delivery Address *</label>
                                    <textarea x-model="orderForm.shipping_address" required rows="2" placeholder="Full delivery address..."
                                        class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-brand-500 focus:border-brand-500"></textarea>
                                </div>
                            </div>

                            <!-- Delivery & Order Settings -->
                            <div class="bg-gray-50/80 p-4 rounded-xl border border-gray-200/70 space-y-3">
                                <span class="text-xs font-bold text-gray-800 uppercase tracking-wider flex items-center gap-1.5">
                                    <i class="ph-bold ph-truck text-brand-600"></i>
                                    Shipping & Charges
                                </span>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">Delivery Charge (৳)</label>
                                        <input type="number" x-model.number="orderForm.delivery_charge" min="0"
                                            class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs font-mono font-bold focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">Discount (৳)</label>
                                        <input type="number" x-model.number="orderForm.discount" min="0"
                                            class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs font-mono font-bold focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-600 mb-1">Order Notes / Instructions</label>
                                    <input type="text" x-model="orderForm.notes" placeholder="e.g. Call before delivery, fragile item"
                                        class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                                </div>
                            </div>

                        </div>

                        <!-- Right: Products List & Live Search (7 cols) -->
                        <div class="lg:col-span-7 space-y-4 flex flex-col justify-between">

                            <div class="space-y-4">
                                <!-- Product Search Bar -->
                                <div class="relative">
                                    <label class="block text-xs font-bold text-gray-800 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                                        <i class="ph-bold ph-magnifying-glass text-brand-600"></i>
                                        Add More Products to Order
                                    </label>
                                    <div class="relative">
                                        <input type="text" x-model="posSearchQuery" @input.debounce.300ms="searchPosProducts()"
                                            placeholder="Type product name or SKU..."
                                            class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                                        <i class="ph ph-magnifying-glass absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                                        <i x-show="isSearchingPos" class="ph ph-spinner animate-spin absolute right-3 top-2.5 text-brand-600 text-sm"></i>
                                    </div>

                                    <!-- Suggestions Dropdown -->
                                    <div x-show="showPosSuggestions" @click.outside="showPosSuggestions = false"
                                        class="absolute z-20 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl max-h-56 overflow-y-auto divide-y divide-gray-100">
                                        <template x-for="p in posSuggestions" :key="p.id">
                                            <div class="p-2.5 hover:bg-gray-50 transition cursor-pointer flex items-center justify-between gap-3"
                                                @click="addPosProductToOrder(p)">
                                                <div class="flex items-center gap-2.5 min-w-0">
                                                    <img :src="p.featured_image ? (p.featured_image.startsWith('http') ? p.featured_image : '/storage/' + p.featured_image) : '/images/placeholder.png'"
                                                        class="w-8 h-8 rounded-lg object-cover shrink-0 border border-gray-100">
                                                    <div class="min-w-0">
                                                        <h5 class="font-bold text-xs text-gray-900 truncate" x-text="p.title"></h5>
                                                        <span class="text-[10px] text-gray-400 font-mono" x-text="'SKU: ' + (p.sku || 'N/A') + ' • Stock: ' + (p.stock_quantity ?? '—')"></span>
                                                    </div>
                                                </div>
                                                <div class="text-right shrink-0">
                                                    <span class="text-xs font-bold text-brand-600" x-text="'৳' + Math.round(p.current_price || p.regular_price || 0)"></span>
                                                    <span class="block text-[10px] text-emerald-600 font-bold">+ Add</span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- Current Order Items Table -->
                                <div class="border border-gray-200 rounded-xl overflow-hidden bg-white">
                                    <div class="px-4 py-2.5 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                                        <span class="text-xs font-bold text-gray-700 uppercase tracking-wider">
                                            Order Items (<span x-text="orderForm.items.length"></span>)
                                        </span>
                                        <span class="text-xs font-bold text-gray-500 font-mono" x-text="'Subtotal: ৳' + Math.round(orderSubtotal)"></span>
                                    </div>

                                    <div class="divide-y divide-gray-100 max-h-64 overflow-y-auto">
                                        <template x-for="(item, idx) in orderForm.items" :key="item.cartId || idx">
                                            <div class="p-3 flex items-center justify-between gap-3 text-xs hover:bg-gray-50/50">
                                                <div class="flex items-center gap-2.5 min-w-0">
                                                    <img :src="item.image ? (item.image.startsWith('http') ? item.image : '/storage/' + item.image) : '/images/placeholder.png'"
                                                        class="w-10 h-10 rounded-lg object-cover shrink-0 border border-gray-100">
                                                    <div class="min-w-0">
                                                        <h5 class="font-bold text-gray-900 truncate" x-text="item.name"></h5>
                                                        <p class="text-[10px] text-gray-400 font-mono" x-text="(item.variantLabel ? item.variantLabel + ' • ' : '') + '৳' + Math.round(item.price)"></p>
                                                    </div>
                                                </div>

                                                <div class="flex items-center gap-3 shrink-0">
                                                    <!-- Quantity Selector -->
                                                    <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden bg-gray-50">
                                                        <button type="button" @click="updateOrderItemQty(idx, -1)"
                                                            class="px-2 py-1 text-gray-500 hover:bg-gray-200 transition font-bold cursor-pointer">-</button>
                                                        <span class="px-2 text-xs font-bold font-mono text-gray-800" x-text="item.qty"></span>
                                                        <button type="button" @click="updateOrderItemQty(idx, 1)"
                                                            class="px-2 py-1 text-gray-500 hover:bg-gray-200 transition font-bold cursor-pointer">+</button>
                                                    </div>

                                                    <span class="font-bold text-gray-900 font-mono w-14 text-right" x-text="'৳' + Math.round(item.price * item.qty)"></span>

                                                    <button type="button" @click="removeOrderItem(idx)"
                                                        class="text-gray-300 hover:text-rose-600 p-1 rounded transition cursor-pointer" title="Remove Item">
                                                        <i class="ph ph-trash text-sm"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </template>

                                        <div x-show="orderForm.items.length === 0" class="py-8 text-center text-gray-400">
                                            <i class="ph ph-shopping-bag text-2xl text-gray-300 mb-1"></i>
                                            <p class="text-xs">No products in order. Search and add products above.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Financial Summary & Confirm Button -->
                            <div class="pt-3 border-t border-gray-100 space-y-3">
                                <div class="bg-brand-50/50 p-3.5 rounded-xl border border-brand-100/70 flex items-center justify-between">
                                    <div class="space-y-0.5 text-xs">
                                        <div class="text-gray-500">Subtotal: <span class="font-mono font-bold text-gray-700" x-text="'৳' + Math.round(orderSubtotal)"></span></div>
                                        <div class="text-gray-500">Shipping: <span class="font-mono text-gray-700" x-text="'৳' + (orderForm.delivery_charge || 0)"></span> • Discount: <span class="font-mono text-brand-600" x-text="'−৳' + (orderForm.discount || 0)"></span></div>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-[10px] text-gray-400 uppercase tracking-wider block font-bold">Total Payable</span>
                                        <span class="text-lg font-black text-brand-700 font-mono" x-text="'৳' + Math.round(orderTotal)"></span>
                                    </div>
                                </div>

                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" @click="isOrderModalOpen = false"
                                        class="px-4 py-2 border border-gray-200 rounded-xl text-xs font-semibold text-gray-600 hover:bg-gray-50 transition cursor-pointer">
                                        Cancel
                                    </button>
                                    <button type="button" @click="submitCustomerOrder()" :disabled="isSubmittingOrder || orderForm.items.length === 0"
                                        class="px-6 py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-bold shadow-lg shadow-brand-500/25 transition flex items-center gap-2 cursor-pointer disabled:opacity-50">
                                        <i x-show="!isSubmittingOrder" class="ph-bold ph-check text-sm"></i>
                                        <i x-show="isSubmittingOrder" class="ph ph-spinner animate-spin text-sm"></i>
                                        <span x-text="isSubmittingOrder ? 'Placing Order...' : 'Confirm & Place Order'"></span>
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Footprint Journey Timeline Modal -->
        <div x-show="detailOpen" x-cloak style="display: none;"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/50 backdrop-blur-xs p-4"
            @keydown.escape.window="detailOpen = false">

            <div
                class="bg-white w-full max-w-2xl rounded-2xl shadow-xl overflow-hidden border border-gray-100 max-h-[90vh] flex flex-col">

                <!-- Modal Header -->
                <div
                    class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-gray-50 to-white">
                    <div class="flex items-center gap-2.5">
                        <div
                            class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg">
                            <i class="ph ph-footprints"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 text-base">Shopper Footprint Journey</h3>
                            <p class="text-xs text-gray-500">Step-by-step navigation timeline of this visitor</p>
                        </div>
                    </div>

                    <button type="button" @click="detailOpen = false"
                        class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition-colors">
                        <i class="ph ph-x text-lg"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 overflow-y-auto space-y-6">
                    <!-- Loading State -->
                    <template x-if="loadingDetail">
                        <div class="py-12 text-center text-gray-400 space-y-2">
                            <i class="ph ph-spinner animate-spin text-3xl text-indigo-600 inline-block"></i>
                            <p class="text-xs font-medium">Loading visitor timeline...</p>
                        </div>
                    </template>

                    <!-- Data Content -->
                    <template x-if="!loadingDetail && selectedSession">
                        <div class="space-y-6">
                            <!-- Visitor Metadata Overview -->
                            <div
                                class="bg-gray-50 p-4 rounded-xl border border-gray-200/70 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                                <div>
                                    <span
                                        class="text-gray-400 uppercase tracking-wider block text-[10px] font-semibold">Name
                                        / Identity</span>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span
                                            class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-700 font-mono"
                                            x-text="'Visitor #' + (selectedSession.daily_serial || selectedSession.id)"></span>
                                        <span class="font-bold text-gray-900" x-text="selectedSession.name"></span>
                                    </div>
                                    <span x-show="selectedSession.phone"
                                        class="block font-mono text-gray-700 font-medium mt-0.5"
                                        x-text="selectedSession.phone"></span>
                                    <span x-show="selectedSession.address" class="block text-gray-600 text-[11px] mt-1"
                                        x-text="'Address: ' + selectedSession.address"></span>
                                </div>

                                <div>
                                    <span
                                        class="text-gray-400 uppercase tracking-wider block text-[10px] font-semibold">Device
                                        & IP</span>
                                    <span class="font-bold text-gray-900"
                                        x-text="selectedSession.device + ' • ' + selectedSession.browser"></span>
                                    <span class="block font-mono text-gray-600 mt-0.5" x-text="selectedSession.ip"></span>
                                </div>

                                <div>
                                    <span
                                        class="text-gray-400 uppercase tracking-wider block text-[10px] font-semibold">Session
                                        Time</span>
                                    <span class="font-bold text-gray-900" x-text="selectedSession.duration"></span>
                                    <span class="block text-gray-500 mt-0.5"
                                        x-text="selectedSession.page_views + ' Total Pages'"></span>
                                </div>

                                <div>
                                    <span
                                        class="text-gray-400 uppercase tracking-wider block text-[10px] font-semibold">Status
                                        / History</span>
                                    <span class="inline-flex items-center gap-1 font-semibold text-emerald-700"
                                        x-show="selectedSession.is_returning">
                                        <i class="ph ph-arrow-counter-clockwise"></i> Returning User
                                    </span>
                                    <span class="font-medium text-gray-600" x-show="!selectedSession.is_returning">New
                                        First-time</span>
                                    <span class="block text-gray-400 text-[10px] mt-0.5 truncate"
                                        x-text="'Ref: ' + selectedSession.referrer"></span>
                                </div>
                            </div>

                            <!-- Abandoned Cart Banner with Products if present -->
                            <template x-if="selectedSession.cart">
                                <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-xs space-y-2">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <i class="ph ph-shopping-cart text-amber-600 text-lg"></i>
                                            <div>
                                                <span class="font-bold text-amber-900">Cart Attached:</span>
                                                <span class="text-amber-800"
                                                    x-text="selectedSession.cart.total_items + ' items (৳' + selectedSession.cart.total_amount + ')'"></span>
                                                <span class="text-[10px] px-1.5 py-0.2 rounded font-semibold uppercase ml-1"
                                                    :class="selectedSession.cart.status === 'abandoned' ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700'"
                                                    x-text="selectedSession.cart.status"></span>
                                            </div>
                                        </div>
                                        <a href="{{ route('admin.carts.index') }}"
                                            class="font-bold text-amber-700 hover:text-amber-900 hover:underline">
                                            Open Carts &rarr;
                                        </a>
                                    </div>

                                    <template x-if="selectedSession.cart.items && selectedSession.cart.items.length > 0">
                                        <div class="pt-2 border-t border-amber-200/60 space-y-1.5">
                                            <template x-for="ci in selectedSession.cart.items" :key="ci.product_id">
                                                <div
                                                    class="flex items-center justify-between text-xs text-amber-950 bg-white/80 px-2.5 py-1.5 rounded-lg border border-amber-100">
                                                    <div class="flex items-center gap-2 truncate">
                                                        <img x-show="ci.image"
                                                            :src="ci.image && ci.image.startsWith('http') ? ci.image : '/storage/' + ci.image"
                                                            class="w-6 h-6 rounded object-cover">
                                                        <span class="truncate font-medium" x-text="ci.name"></span>
                                                        <span x-show="ci.variant_name"
                                                            class="text-[10px] text-gray-500 font-normal"
                                                            x-text="'(' + ci.variant_name + ')'"></span>
                                                    </div>
                                                    <div class="text-right shrink-0 font-mono text-[11px] font-bold">
                                                        <span x-text="ci.quantity + 'x'"></span>
                                                        <span x-text="'৳' + (ci.price * ci.quantity)"></span>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <!-- Sent Offers History Section for this Visitor -->
                            <template x-if="selectedSession.offers && selectedSession.offers.length > 0">
                                <div>
                                    <h4
                                        class="text-xs font-bold uppercase tracking-wider text-pink-600 mb-2 flex items-center gap-1.5">
                                        <i class="ph ph-gift"></i>
                                        <span>Offers Dispatched to this Visitor (<span
                                                x-text="selectedSession.offers.length"></span>)</span>
                                    </h4>
                                    <div class="space-y-2">
                                        <template x-for="o in selectedSession.offers" :key="o.id">
                                            <div class="bg-pink-50/70 border border-pink-200 rounded-xl p-3 text-xs">
                                                <div class="flex items-center justify-between gap-2">
                                                    <span class="font-bold text-gray-900" x-text="o.title"></span>
                                                    <span class="px-2 py-0.5 rounded-full font-bold uppercase text-[10px]"
                                                        :class="{
                                                            'bg-emerald-100 text-emerald-800 border border-emerald-300': o.status === 'clicked',
                                                            'bg-blue-100 text-blue-800 border border-blue-300': o.status === 'delivered',
                                                            'bg-amber-100 text-amber-800 border border-amber-300': o.status === 'pending',
                                                            'bg-gray-100 text-gray-600': o.status === 'dismissed'
                                                        }" x-text="o.status">
                                                    </span>
                                                </div>
                                                <p class="text-gray-600 mt-1 whitespace-pre-line leading-relaxed"
                                                    x-text="o.message"></p>
                                                <div
                                                    class="mt-2 pt-2 border-t border-pink-200/60 flex items-center justify-between text-[11px] text-gray-500 font-mono">
                                                    <div>
                                                        <span x-show="o.coupon_code"
                                                            class="font-bold text-pink-700 bg-white px-2 py-0.5 rounded border border-pink-200 mr-2"
                                                            x-text="'Coupon: ' + o.coupon_code"></span>
                                                        <span x-text="'Sent: ' + o.sent_at"></span>
                                                    </div>
                                                    <span x-show="o.action_at" class="text-emerald-700 font-semibold"
                                                        x-text="'Clicked: ' + o.action_at"></span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <!-- Timeline -->
                            <div>
                                <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-3">Navigation
                                    Footprint Timeline</h4>

                                <div class="relative pl-6 space-y-4 border-l-2 border-indigo-100">
                                    <template x-for="(ev, index) in events" :key="ev.id">
                                        <div class="relative group">
                                            <!-- Dot -->
                                            <div class="absolute -left-[31px] top-1 w-3.5 h-3.5 rounded-full border-2 border-white shadow-xs"
                                                :class="ev.type === 'checkout_visit' ? 'bg-rose-500 ring-2 ring-rose-200' : (ev.type === 'add_to_cart' ? 'bg-amber-500' : 'bg-indigo-500')">
                                            </div>

                                            <div
                                                class="bg-gray-50 hover:bg-indigo-50/40 p-3 rounded-xl border border-gray-100 transition-colors">
                                                <div class="flex items-center justify-between gap-2">
                                                    <span class="font-semibold text-xs text-gray-900"
                                                        x-text="ev.title || 'Page Visit'"></span>
                                                    <div class="flex items-center gap-1.5 shrink-0">
                                                        <span class="text-[10px] font-mono text-gray-400"
                                                            x-text="ev.time"></span>
                                                        <template x-if="ev.duration">
                                                            <span
                                                                class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100"
                                                                x-text="ev.duration"></span>
                                                        </template>
                                                    </div>
                                                </div>
                                                <div class="text-[11px] text-indigo-600 font-mono mt-0.5 truncate">
                                                    <a :href="ev.url" target="_blank" class="hover:underline"
                                                        x-text="ev.url"></a>
                                                </div>
                                            </div>
                                        </div>
                                    </template>

                                    <div x-show="events.length === 0" class="text-xs text-gray-400 py-3">
                                        No detailed sub-events recorded for this session.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-3 border-t border-gray-100 bg-gray-50 text-right">
                    <button type="button" @click="detailOpen = false"
                        class="px-4 py-2 bg-white border border-gray-200 hover:bg-gray-100 text-gray-700 text-xs font-semibold rounded-xl transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>

        <!-- Send Custom Offer Modal for Historical Users -->
        <div x-show="offerModalOpen" x-cloak style="display: none;"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/60 backdrop-blur-xs p-4"
            @keydown.escape.window="offerModalOpen = false">

            <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden border border-gray-100">

                <div
                    class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-pink-50/50 to-white">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-pink-100 text-pink-600 flex items-center justify-center text-lg">
                            <i class="ph ph-lightning"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 text-base">Send Custom Offer for Next Visit</h3>
                            <p class="text-xs text-gray-500">Will pop up when this shopper visits your store again</p>
                        </div>
                    </div>

                    <button type="button" @click="offerModalOpen = false"
                        class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition">
                        <i class="ph ph-x text-lg"></i>
                    </button>
                </div>

                <form @submit.prevent="submitOffer()" class="p-6 space-y-4">
                    <div
                        class="bg-indigo-50/60 border border-indigo-100 rounded-xl p-3 flex items-center justify-between text-xs">
                        <div>
                            <span class="text-gray-500 block text-[10px] uppercase font-semibold">Target Shopper:</span>
                            <span class="font-bold text-gray-900"
                                x-text="offerTarget ? (offerTarget.name || 'Guest Visitor') : ''"></span>
                            <span x-show="offerTarget && offerTarget.phone" class="font-mono text-gray-600 ml-1"
                                x-text="'(' + offerTarget.phone + ')'"></span>
                        </div>
                        <span class="px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-800 text-[10px] font-bold">Targeted
                            User</span>
                    </div>

                    <!-- Template Selector -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Select Offer Template
                            (Optional)</label>
                        <select x-model="selectedTemplate" @change="applyTemplate()"
                            class="w-full px-3.5 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                            <option value="">-- Choose a Pre-defined Template or Write Custom --</option>
                            <template x-for="t in templates" :key="t.id">
                                <option :value="t.id" x-text="t.name"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Popup Headline *</label>
                        <input type="text" x-model="offerForm.title" required placeholder="e.g. Special Discount for You"
                            class="w-full px-3.5 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Offer Message / Pitch *</label>
                        <textarea x-model="offerForm.message" rows="3" required placeholder="Write custom offer message..."
                            class="w-full px-3.5 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-pink-500 focus:border-pink-500"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Coupon Code (Optional)</label>
                            <input type="text" x-model="offerForm.coupon_code" placeholder="e.g. WELCOME10"
                                class="w-full px-3.5 py-2 bg-white border border-gray-200 rounded-xl text-sm font-mono uppercase focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Popup Delay (on Next
                                Visit)</label>
                            <select x-model="offerForm.delay_seconds"
                                class="w-full px-3.5 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                                <option value="0">Instant (0 seconds)</option>
                                <option value="5">5 seconds</option>
                                <option value="10">10 seconds (Default)</option>
                                <option value="20">20 seconds</option>
                                <option value="30">30 seconds</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">প্রদর্শনের নিয়ম (Show
                            Frequency)</label>
                        <select x-model="offerForm.show_mode"
                            class="w-full px-3.5 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                            <option value="once">শুধুমাত্র একবার দেখাবে (Once Only)</option>
                            <option value="every_session">পরবর্তী সেশনেও বারবার দেখাবে (Repeat)</option>
                        </select>
                    </div>

                    <div x-show="offerForm.show_mode === 'every_session'" x-cloak
                        class="p-3 bg-pink-50/60 border border-pink-100 rounded-xl text-xs space-y-2">
                        <label class="block font-semibold text-pink-900">কত মিনিট পর পর পুনরায় দেখাবে?</label>
                        <div class="flex items-center gap-2">
                            <select x-model="offerForm.repeat_interval_minutes"
                                class="px-3 py-1.5 bg-white border border-pink-200 rounded-lg text-xs font-semibold text-pink-700">
                                <option value="30">৩০ মিনিট পর</option>
                                <option value="60">১ ঘণ্টা পর (Default)</option>
                                <option value="120">২ ঘণ্টা পর</option>
                                <option value="360">৬ ঘণ্টা পর</option>
                                <option value="1440">১ দিন (২৪ ঘণ্টা) পর</option>
                            </select>
                            <span class="text-gray-500 text-[11px]">ইউজার যতবারই সাইটে নতুন করে আসবে, নির্দিষ্ট সময় পর পর
                                অফারটি দেখতে পাবে।</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Button Text (Optional - ফাঁকা
                                রাখলে শুধু মেসেজ যাবে)</label>
                            <input type="text" x-model="offerForm.button_text" placeholder="যেমন: অফারটি নিন (ঐচ্ছিক)"
                                class="w-full px-3.5 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Button Redirect Link
                                (Optional)</label>
                            <input type="text" x-model="offerForm.button_url" placeholder="/checkout (ঐচ্ছিক)"
                                class="w-full px-3.5 py-2 bg-white border border-gray-200 rounded-xl text-sm font-mono focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                        </div>
                    </div>

                    <template x-if="offerNotice">
                        <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-semibold text-center"
                            x-text="offerNotice"></div>
                    </template>

                    <div class="pt-2 flex items-center justify-end gap-2 border-t border-gray-100">
                        <button type="button" @click="offerModalOpen = false"
                            class="px-4 py-2 border border-gray-200 rounded-xl text-xs font-semibold text-gray-600 hover:bg-gray-50 transition">
                            Cancel
                        </button>
                        <button type="submit" :disabled="offerSending"
                            class="px-5 py-2 bg-gradient-to-r from-pink-500 to-rose-500 hover:from-pink-600 hover:to-rose-600 text-white rounded-xl text-xs font-bold shadow-md shadow-pink-500/20 transition flex items-center gap-1.5">
                            <i class="ph ph-paper-plane-tilt font-bold"></i>
                            <span x-text="offerSending ? 'Scheduling...' : 'Save & Schedule Offer'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Global Floating Toast Notification -->
        <div x-show="toast.show"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-3"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-3"
             style="display: none;"
             class="fixed bottom-6 right-6 z-[150] flex items-center gap-2.5 px-4 py-3 bg-gray-900/95 text-white text-xs font-semibold rounded-2xl shadow-2xl backdrop-blur-md border border-gray-800">
            <i class="ph-bold text-base" :class="toast.type === 'error' ? 'ph-warning-circle text-rose-400' : 'ph-check-circle text-emerald-400'"></i>
            <span x-text="toast.message"></span>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        function visitorFootprintManager() {
            return {
                detailOpen: false,
                loadingDetail: false,
                selectedSession: null,
                events: [],

                // Context Menu State
                contextMenuOpen: false,
                contextX: 0,
                contextY: 0,
                contextVisitor: null,

                // Cart & Order Modal State
                isCartModalOpen: false,
                isLoadingCart: false,
                cartData: null,
                selectedVisitor: null,
                selectedHistoryVersion: 'latest',

                // POS "Order as Customer" State
                isOrderModalOpen: false,
                isSubmittingOrder: false,
                orderSuccessNotice: null,
                orderErrorNotice: null,
                posSearchQuery: '',
                isSearchingPos: false,
                posSuggestions: [],
                showPosSuggestions: false,

                // Visitor Info, Revisions & Notes Modal State
                isInfoModalOpen: false,
                isLoadingInfo: false,
                infoModalVisitor: null,
                infoData: null,
                newVisitorNote: '',
                isSavingNote: false,

                // Toast Notification State
                toast: { show: false, message: '', type: 'success' },
                toastTimer: null,

                showToast(message, type = 'success') {
                    clearTimeout(this.toastTimer);
                    this.toast = { show: true, message, type };
                    this.toastTimer = setTimeout(() => this.toast.show = false, 3500);
                },

                async copyText(text, successMsg = 'Copied to clipboard!') {
                    if (!text) return;
                    try {
                        if (navigator.clipboard && window.isSecureContext) {
                            await navigator.clipboard.writeText(text);
                        } else {
                            const input = document.createElement('textarea');
                            input.value = text;
                            input.style.position = 'fixed';
                            input.style.opacity = '0';
                            document.body.appendChild(input);
                            input.select();
                            document.execCommand('copy');
                            document.body.removeChild(input);
                        }
                        this.showToast(successMsg, 'success');
                    } catch (e) {
                        this.showToast('Could not copy text', 'error');
                    }
                },
                orderForm: {
                    customer_name: '',
                    customer_contact: '',
                    shipping_address: '',
                    delivery_charge: 70,
                    discount: 0,
                    notes: '',
                    order_source: 'website',
                    user_id: null,
                    items: []
                },

                // Offer modal state for historical users
                offerModalOpen: false,
                offerTarget: null,
                selectedTemplate: '',
                offerForm: {
                    title: '',
                    message: '',
                    coupon_code: '',
                    button_text: '',
                    button_url: '',
                    delay_seconds: 10,
                    show_mode: 'once',
                    repeat_interval_minutes: 60
                },
                offerSending: false,
                offerNotice: null,

                templates: @json($templates ?? []),

                openContextMenu(e, v) {
                    this.contextVisitor = v;
                    const menuWidth = 240;
                    const menuHeight = 240;
                    let x = e.clientX;
                    let y = e.clientY;
                    if (x + menuWidth > window.innerWidth) x = window.innerWidth - menuWidth - 12;
                    if (y + menuHeight > window.innerHeight) y = window.innerHeight - menuHeight - 12;
                    this.contextX = Math.max(10, x);
                    this.contextY = Math.max(10, y);
                    this.contextMenuOpen = true;
                },

                closeContextMenu() {
                    this.contextMenuOpen = false;
                },

                openCartModal(v) {
                    const target = v || this.contextVisitor || this.selectedVisitor;
                    if (!target || !target.vid) return;
                    this.closeContextMenu();
                    this.selectedVisitor = target;
                    this.isCartModalOpen = true;
                    this.isLoadingCart = true;
                    this.cartData = null;

                    const url = '{{ url('admin/dashboard/live-monitor/cart') }}/' + encodeURIComponent(target.vid);
                    fetch(url)
                        .then(res => res.json())
                        .then(data => {
                            if (data && data.success) {
                                this.cartData = data;
                            }
                        })
                        .catch(err => {
                            console.error('Failed to load cart:', err);
                        })
                        .finally(() => {
                            this.isLoadingCart = false;
                        });
                },

                triggerSendOffer() {
                    const v = this.contextVisitor || this.selectedVisitor;
                    this.closeContextMenu();
                    if (v) this.openOfferModal(v);
                },

                triggerJourney() {
                    const v = this.contextVisitor || this.selectedVisitor;
                    this.closeContextMenu();
                    if (v && v.id) this.openJourney(v.id);
                },

                openLiveMonitor() {
                    const v = this.contextVisitor || this.selectedVisitor;
                    this.closeContextMenu();
                    window.open('{{ route('admin.visitors.live') }}', '_blank');
                },

                openInfoModal(v) {
                    const target = v || this.contextVisitor || this.selectedVisitor;
                    if (!target || !target.vid) return;
                    this.closeContextMenu();
                    this.infoModalVisitor = target;
                    this.isInfoModalOpen = true;
                    this.isLoadingInfo = true;
                    this.infoData = null;
                    this.newVisitorNote = '';

                    const url = '{{ url('admin/dashboard/visitor/info-profile') }}/' + encodeURIComponent(target.vid);
                    fetch(url)
                        .then(res => res.json())
                        .then(data => {
                            if (data && data.success) {
                                this.infoData = data;
                            }
                        })
                        .catch(err => {
                            console.error('Failed to load visitor info profile:', err);
                            this.showToast('Failed to load profile', 'error');
                        })
                        .finally(() => {
                            this.isLoadingInfo = false;
                        });
                },

                saveNote() {
                    if (!this.infoModalVisitor?.vid || !this.newVisitorNote.trim()) return;
                    this.isSavingNote = true;

                    fetch('{{ route('admin.visitors.save-note') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            visitor_token: this.infoModalVisitor.vid,
                            note: this.newVisitorNote.trim()
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.success) {
                            if (!this.infoData) this.infoData = {};
                            if (!Array.isArray(this.infoData.notes)) this.infoData.notes = [];
                            this.infoData.notes.unshift(data.note);
                            this.newVisitorNote = '';
                            this.showToast('Note added to visitor history!', 'success');
                        } else {
                            this.showToast(data.message || 'Could not save note', 'error');
                        }
                    })
                    .catch(err => {
                        console.error('Error saving note:', err);
                        this.showToast('Error saving note', 'error');
                    })
                    .finally(() => {
                        this.isSavingNote = false;
                    });
                },

                orderAsCustomer() {
                    const v = this.contextVisitor || this.selectedVisitor;
                    if (!v || !v.vid) return;

                    this.closeContextMenu();
                    this.isCartModalOpen = false;
                    this.selectedVisitor = v;
                    this.orderSuccessNotice = null;
                    this.orderErrorNotice = null;
                    this.posSearchQuery = '';
                    this.posSuggestions = [];
                    this.showPosSuggestions = false;

                    const latestName = this.cartData?.customer_name || v.name || '';
                    const latestPhone = this.cartData?.customer_phone || v.phone || '';
                    const latestAddress = this.cartData?.customer_address || v.address || '';
                    const userId = this.cartData?.user_id || v.user_id || null;

                    let initialItems = [];
                    if (this.cartData && Array.isArray(this.cartData.items)) {
                        initialItems = this.cartData.items.map((ci, idx) => ({
                            cartId: 'c_' + (ci.product_id || idx) + '_' + (ci.variant_id || 0) + '_' + Date.now(),
                            product_id: ci.product_id,
                            variant_id: ci.variant_id || null,
                            name: ci.name || 'Product',
                            qty: parseInt(ci.quantity) || 1,
                            price: parseFloat(ci.price) || 0,
                            original_price: parseFloat(ci.price) || 0,
                            image: ci.image || null,
                            product_sku: ci.sku || null,
                            variant_sku: ci.variant_sku || null,
                            variantLabel: ci.variant_name || null,
                            max_stock: ci.max_stock || 999
                        }));
                    }

                    this.orderForm = {
                        customer_name: latestName,
                        customer_contact: latestPhone,
                        shipping_address: latestAddress,
                        delivery_charge: 70,
                        discount: 0,
                        notes: 'Order created via Visitor Footprint (POS Mode)',
                        order_source: 'admin_pos',
                        user_id: userId,
                        items: initialItems
                    };

                    this.isOrderModalOpen = true;

                    fetch('{{ url('admin/dashboard/live-monitor/cart') }}/' + encodeURIComponent(v.vid))
                        .then(res => res.json())
                        .then(data => {
                            if (data && data.success) {
                                this.cartData = data;
                                if (this.orderForm.items.length === 0 && Array.isArray(data.items) && data.items.length > 0) {
                                    this.orderForm.items = data.items.map((ci, idx) => ({
                                        cartId: 'c_' + (ci.product_id || idx) + '_' + (ci.variant_id || 0) + '_' + Date.now(),
                                        product_id: ci.product_id,
                                        variant_id: ci.variant_id || null,
                                        name: ci.name || 'Product',
                                        qty: parseInt(ci.quantity) || 1,
                                        price: parseFloat(ci.price) || 0,
                                        original_price: parseFloat(ci.price) || 0,
                                        image: ci.image || null,
                                        product_sku: ci.sku || null,
                                        variant_sku: ci.variant_sku || null,
                                        variantLabel: ci.variant_name || null,
                                        max_stock: ci.max_stock || 999
                                    }));
                                }
                                if (!this.orderForm.customer_name && data.customer_name) this.orderForm.customer_name = data.customer_name;
                                if (!this.orderForm.customer_contact && data.customer_phone) this.orderForm.customer_contact = data.customer_phone;
                                if (!this.orderForm.shipping_address && data.customer_address) this.orderForm.shipping_address = data.customer_address;
                                if (!this.orderForm.user_id && data.user_id) this.orderForm.user_id = data.user_id;
                            }
                        })
                        .catch(() => {});
                },

                onHistoryVersionChange() {
                    if (this.selectedHistoryVersion === 'latest') {
                        this.orderForm.customer_name = this.cartData?.customer_name || this.selectedVisitor?.name || '';
                        this.orderForm.customer_contact = this.cartData?.customer_phone || this.selectedVisitor?.phone || '';
                        this.orderForm.shipping_address = this.cartData?.customer_address || this.selectedVisitor?.address || '';
                    } else if (this.cartData?.info_history) {
                        const rev = this.cartData.info_history.find(h => String(h.version) === String(this.selectedHistoryVersion));
                        if (rev) {
                            if (rev.name) this.orderForm.customer_name = rev.name;
                            if (rev.phone) this.orderForm.customer_contact = rev.phone;
                            if (rev.address) this.orderForm.shipping_address = rev.address;
                        }
                    }
                },

                get orderSubtotal() {
                    return this.orderForm.items.reduce((sum, item) => sum + (parseFloat(item.price || 0) * (parseInt(item.qty) || 1)), 0);
                },

                get orderTotal() {
                    const sub = this.orderSubtotal;
                    const shipping = parseFloat(this.orderForm.delivery_charge || 0);
                    const discount = parseFloat(this.orderForm.discount || 0);
                    return Math.max(0, sub + shipping - discount);
                },

                updateOrderItemQty(index, change) {
                    const item = this.orderForm.items[index];
                    if (!item) return;
                    let newQty = (parseInt(item.qty) || 1) + change;
                    if (newQty < 1) newQty = 1;
                    if (item.max_stock && newQty > item.max_stock) {
                        alert('Cannot exceed stock quantity (' + item.max_stock + ')');
                        return;
                    }
                    item.qty = newQty;
                },

                removeOrderItem(index) {
                    this.orderForm.items.splice(index, 1);
                },

                async searchPosProducts() {
                    if (!this.posSearchQuery || this.posSearchQuery.length < 2) {
                        this.posSuggestions = [];
                        this.showPosSuggestions = false;
                        return;
                    }
                    this.isSearchingPos = true;
                    try {
                        const res = await fetch(`/admin/dashboard/products/search?q=${encodeURIComponent(this.posSearchQuery)}`);
                        const data = await res.json();
                        this.posSuggestions = Array.isArray(data) ? data : [];
                        this.showPosSuggestions = this.posSuggestions.length > 0;
                    } catch (e) {
                        this.posSuggestions = [];
                    } finally {
                        this.isSearchingPos = false;
                    }
                },

                addPosProductToOrder(product, variant = null) {
                    const cartId = 'item_' + product.id + (variant ? '_' + variant.id : '') + '_' + Date.now();
                    const existingIndex = this.orderForm.items.findIndex(i => i.product_id === product.id && i.variant_id === (variant ? variant.id : null));

                    if (existingIndex !== -1) {
                        this.updateOrderItemQty(existingIndex, 1);
                    } else {
                        let attrParts = [];
                        if (variant && variant.attributes) {
                            for (const [key, val] of Object.entries(variant.attributes)) {
                                attrParts.push(val);
                            }
                        }

                        this.orderForm.items.push({
                            cartId: cartId,
                            product_id: product.id,
                            variant_id: variant ? variant.id : null,
                            name: product.title,
                            qty: 1,
                            price: parseFloat(variant ? (variant.current_price || variant.original_price) : (product.current_price || product.regular_price)) || 0,
                            original_price: parseFloat(variant ? (variant.original_price || variant.current_price) : (product.regular_price || product.current_price)) || 0,
                            image: variant?.images?.[0] || product.featured_image || null,
                            product_sku: product.sku || null,
                            variant_sku: variant ? variant.sku : null,
                            variantLabel: attrParts.length > 0 ? attrParts.join(', ') : null,
                            max_stock: variant ? (variant.stock_quantity || 999) : (product.stock_quantity || 999)
                        });
                    }

                    this.posSearchQuery = '';
                    this.showPosSuggestions = false;
                },

                async submitCustomerOrder() {
                    if (!this.orderForm.customer_name || !this.orderForm.customer_contact || !this.orderForm.shipping_address) {
                        this.orderErrorNotice = 'Please fill customer name, contact and delivery address.';
                        return;
                    }
                    if (this.orderForm.items.length === 0) {
                        this.orderErrorNotice = 'Please add at least one product to the order.';
                        return;
                    }

                    this.isSubmittingOrder = true;
                    this.orderErrorNotice = null;
                    this.orderSuccessNotice = null;

                    const payload = {
                        customer_name: this.orderForm.customer_name,
                        customer_contact: this.orderForm.customer_contact,
                        shipping_address: this.orderForm.shipping_address,
                        source: this.orderForm.order_source || 'website',
                        notes: this.orderForm.notes || null,
                        delivery_charge: parseFloat(this.orderForm.delivery_charge || 0),
                        discount: parseFloat(this.orderForm.discount || 0),
                        subtotal: this.orderSubtotal,
                        total: this.orderTotal,
                        user_id: this.orderForm.user_id,
                        visitor_token: this.selectedVisitor?.vid || null,
                        items: this.orderForm.items.map(it => ({
                            product_id: it.product_id,
                            variant_id: it.variant_id,
                            product_sku: it.product_sku,
                            variant_sku: it.variant_sku,
                            variant_name: it.variantLabel,
                            name: it.name,
                            quantity: it.qty,
                            price: it.price
                        }))
                    };

                    try {
                        const res = await fetch('{{ route('admin.orders.store') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(payload)
                        });
                        const data = await res.json();
                        if (data && data.success) {
                            this.orderSuccessNotice = data.message || 'Order placed successfully!';
                            setTimeout(() => {
                                this.isOrderModalOpen = false;
                                if (data.order && data.order.order_id) {
                                    window.open('/admin/dashboard/all-orders/' + data.order.order_id, '_blank');
                                }
                            }, 1200);
                        } else {
                            this.orderErrorNotice = data.message || 'Failed to place order.';
                        }
                    } catch (e) {
                        this.orderErrorNotice = 'Error connecting to order server: ' + e.message;
                    } finally {
                        this.isSubmittingOrder = false;
                    }
                },

                applyTemplate() {
                    if (!this.selectedTemplate) return;
                    const t = this.templates.find(item => item.id == this.selectedTemplate);
                    if (t) {
                        this.offerForm.title = t.title;
                        this.offerForm.message = t.message;
                        this.offerForm.coupon_code = t.coupon_code || '';
                        this.offerForm.button_text = t.button_text || '';
                        this.offerForm.button_url = t.button_url || '';
                        this.offerForm.delay_seconds = t.delay_seconds || 10;
                        this.offerForm.show_mode = t.show_mode || 'once';
                        this.offerForm.repeat_interval_minutes = t.repeat_interval_minutes || 60;
                    }
                },

                openOfferModal(target) {
                    this.offerTarget = target;
                    this.selectedTemplate = '';
                    this.offerForm = {
                        title: '',
                        message: '',
                        coupon_code: '',
                        button_text: '',
                        button_url: '',
                        delay_seconds: 10,
                        show_mode: 'once',
                        repeat_interval_minutes: 60
                    };
                    this.offerModalOpen = true;
                    this.offerNotice = null;
                },

                submitOffer() {
                    if (!this.offerTarget) return;
                    this.offerSending = true;
                    this.offerNotice = null;

                    fetch('{{ route('admin.visitors.send-offer') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            visitor_token: this.offerTarget.vid,
                            title: this.offerForm.title,
                            message: this.offerForm.message,
                            coupon_code: this.offerForm.coupon_code,
                            button_text: this.offerForm.button_text,
                            button_url: this.offerForm.button_url,
                            delay_seconds: this.offerForm.delay_seconds,
                            show_mode: this.offerForm.show_mode,
                            repeat_interval_minutes: this.offerForm.repeat_interval_minutes,
                            customer_phone: this.offerTarget.phone || null,
                            user_id: this.offerTarget.user_id || null
                        })
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                this.offerNotice = 'Offer queued successfully! Shopper will see this modal on their next visit.';
                                setTimeout(() => {
                                    this.offerModalOpen = false;
                                    this.offerNotice = null;
                                }, 1600);
                            }
                        })
                        .catch(err => {
                            alert('Error: ' + err);
                        })
                        .finally(() => {
                            this.offerSending = false;
                        });
                },

                openJourney(sessionId) {
                    this.detailOpen = true;
                    this.loadingDetail = true;
                    this.selectedSession = null;
                    this.events = [];

                    fetch('{{ url('/admin/dashboard/visitor-footprint') }}/' + sessionId + '/journey')
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                this.selectedSession = data.session;
                                this.events = data.events;
                            }
                        })
                        .catch(err => {
                            console.error(err);
                        })
                        .finally(() => {
                            this.loadingDetail = false;
                        });
                }
            };
        }
    </script>
@endpush