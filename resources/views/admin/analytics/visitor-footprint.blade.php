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
            <form method="GET" action="{{ route('admin.visitors.footprint') }}" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="filter" value="{{ $filter }}">

                <div class="relative">
                    <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()"
                        class="px-3.5 py-2 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-700 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer">
                </div>

                <div class="relative">
                    <select name="sort" onchange="this.form.submit()"
                        class="px-3.5 py-2 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-700 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer">
                        <option value="serial" {{ ($sort ?? 'serial') === 'serial' ? 'selected' : '' }}>Sort: Visitor ID (#1,
                            #2...)</option>
                        <option value="recent" {{ ($sort ?? '') === 'recent' ? 'selected' : '' }}>Sort: Most Recent Active
                        </option>
                    </select>
                </div>

                <div class="relative">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search ID, phone, name..."
                        class="pl-9 pr-3.5 py-2 bg-white border border-gray-200 rounded-xl text-sm text-gray-700 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 w-48 sm:w-64">
                    <i class="ph ph-magnifying-glass absolute left-3 top-2.5 text-gray-400 text-base"></i>
                </div>

                <button type="submit"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm rounded-xl transition-colors shadow-sm">
                    Filter
                </button>

                @if($search || $date !== date('Y-m-d') || $filter !== 'all' || ($sort ?? 'serial') !== 'serial')
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
                <div class="text-[11px] text-gray-400 mt-0.5">Unique sessions on
                    {{ \Carbon\Carbon::parse($date)->format('M d') }}</div>
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
            <a href="{{ route('admin.visitors.footprint', ['date' => $date, 'filter' => 'all', 'search' => $search, 'sort' => $sort]) }}"
                class="px-3.5 py-1.5 rounded-lg transition-colors flex items-center gap-1.5 {{ $filter === 'all' ? 'bg-indigo-600 text-white font-semibold' : 'bg-gray-100 text-gray-600 hover:text-gray-900' }}">
                <span>All Shoppers</span>
                <span
                    class="px-1.5 py-0.2 rounded-full text-[10px] {{ $filter === 'all' ? 'bg-indigo-700 text-white' : 'bg-gray-200 text-gray-700' }}">{{ $metrics['total_visitors'] }}</span>
            </a>

            <a href="{{ route('admin.visitors.footprint', ['date' => $date, 'filter' => 'returning', 'search' => $search, 'sort' => $sort]) }}"
                class="px-3.5 py-1.5 rounded-lg transition-colors flex items-center gap-1.5 {{ $filter === 'returning' ? 'bg-indigo-600 text-white font-semibold' : 'bg-gray-100 text-gray-600 hover:text-gray-900' }}">
                <i class="ph ph-arrow-counter-clockwise"></i>
                <span>Returning Visitors</span>
                <span
                    class="px-1.5 py-0.2 rounded-full text-[10px] {{ $filter === 'returning' ? 'bg-indigo-700 text-white' : 'bg-gray-200 text-gray-700' }}">{{ $metrics['returning_count'] }}</span>
            </a>

            <a href="{{ route('admin.visitors.footprint', ['date' => $date, 'filter' => 'with_cart', 'search' => $search, 'sort' => $sort]) }}"
                class="px-3.5 py-1.5 rounded-lg transition-colors flex items-center gap-1.5 {{ $filter === 'with_cart' ? 'bg-indigo-600 text-white font-semibold' : 'bg-gray-100 text-gray-600 hover:text-gray-900' }}">
                <i class="ph ph-shopping-cart"></i>
                <span>With Cart Items</span>
                <span
                    class="px-1.5 py-0.2 rounded-full text-[10px] {{ $filter === 'with_cart' ? 'bg-indigo-700 text-white' : 'bg-gray-200 text-gray-700' }}">{{ $metrics['cart_visitors'] }}</span>
            </a>

            <a href="{{ route('admin.visitors.footprint', ['date' => $date, 'filter' => 'checkout', 'search' => $search, 'sort' => $sort]) }}"
                class="px-3.5 py-1.5 rounded-lg transition-colors flex items-center gap-1.5 {{ $filter === 'checkout' ? 'bg-indigo-600 text-white font-semibold' : 'bg-gray-100 text-gray-600 hover:text-gray-900' }}">
                <i class="ph ph-credit-card"></i>
                <span>Visited Checkout</span>
                <span
                    class="px-1.5 py-0.2 rounded-full text-[10px] {{ $filter === 'checkout' ? 'bg-indigo-700 text-white' : 'bg-gray-200 text-gray-700' }}">{{ $metrics['checkout_visitors'] }}</span>
            </a>

            <a href="{{ route('admin.visitors.footprint', ['date' => $date, 'filter' => 'identified', 'search' => $search, 'sort' => $sort]) }}"
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
                            <tr class="hover:bg-gray-50/80 transition-colors">
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
                                                <div
                                                    class="text-xs text-gray-700 font-mono font-medium flex items-center gap-1 mt-0.5">
                                                    <i class="ph ph-phone text-brand-600"></i>
                                                    <span>{{ $s->customer_phone ?: $s->user?->phone }}</span>
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
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button" @click="openOfferModal({
                                            vid: '{{ $s->visitor_token }}',
                                            name: '{{ addslashes($s->customer_name ?: ($s->user?->name ?: 'Guest Visitor')) }}',
                                            phone: '{{ $s->customer_phone ?: ($s->user?->phone ?: '') }}',
                                            user_id: {{ $s->user_id ?: 'null' }}
                                        })" class="cursor-pointer inline-flex items-center gap-1 px-2.5 py-1.5 bg-pink-50 hover:bg-pink-100 text-pink-700 font-bold text-xs rounded-xl transition"
                                            title="Send Custom Offer for Next Visit">
                                            <i class="ph ph-lightning text-sm text-pink-600"></i>
                                            <span>Offer</span>
                                        </button>

                                        <button type="button" @click="openJourney({{ $s->id }})"
                                            class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold text-xs rounded-xl transition-colors"
                                            title="View Full Footprint & History Details">
                                            <i class="ph ph-user-list text-sm"></i>
                                            <span>Details</span>
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