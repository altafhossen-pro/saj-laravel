@extends('layouts.admin')

@section('header_title', 'Search Analytics')

@section('content')
<div class="space-y-6">
    <!-- Header with Period Switcher -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Search Analytics & Product Insights</h1>
            <p class="text-sm text-gray-500 mt-1">Deep analysis of customer searches, zero-result terms, top viewed products, active users & IPs.</p>
        </div>

        <!-- Period Filter Tabs (Today, This Week, This Month, This Year, All Time) -->
        <div class="inline-flex items-center p-1 bg-white border border-gray-200 rounded-xl shadow-xs self-start lg:self-auto">
            <a href="{{ route('admin.analytics.search', ['period' => 'today']) }}" 
               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $period === 'today' ? 'bg-brand-600 text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                Today
            </a>
            <a href="{{ route('admin.analytics.search', ['period' => 'week']) }}" 
               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $period === 'week' ? 'bg-brand-600 text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                This Week
            </a>
            <a href="{{ route('admin.analytics.search', ['period' => 'month']) }}" 
               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $period === 'month' ? 'bg-brand-600 text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                This Month
            </a>
            <a href="{{ route('admin.analytics.search', ['period' => 'year']) }}" 
               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $period === 'year' ? 'bg-brand-600 text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                This Year
            </a>
            <a href="{{ route('admin.analytics.search', ['period' => 'all']) }}" 
               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $period === 'all' ? 'bg-brand-600 text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                All Time
            </a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Total Searches in Period -->
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl shrink-0">
                    <i class="ph ph-magnifying-glass"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Searches ({{ ucfirst($period) }})</p>
                    <h3 class="text-xl font-bold text-gray-900">{{ number_format($totalSearches) }}</h3>
                </div>
            </div>
        </div>

        <!-- Suggestion Clicks -->
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl shrink-0">
                    <i class="ph ph-cursor-click"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Suggestion Clicks</p>
                    <h3 class="text-xl font-bold text-gray-900">{{ number_format($suggestionClicks) }}</h3>
                </div>
            </div>
        </div>

        <!-- Direct Search -->
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shrink-0">
                    <i class="ph ph-arrow-square-out"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Search Button/Page</p>
                    <h3 class="text-xl font-bold text-gray-900">{{ number_format($directSearches) }}</h3>
                </div>
            </div>
        </div>

        <!-- Zero Result (Demands) -->
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl shrink-0">
                    <i class="ph ph-warning-circle"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">No Result Matches</p>
                    <h3 class="text-xl font-bold text-gray-900 text-rose-600">{{ number_format($noResultCount) }}</h3>
                </div>
            </div>
        </div>

        <!-- Today Quick Metric -->
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0">
                    <i class="ph ph-clock-countdown"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Today's Traffic</p>
                    <h3 class="text-xl font-bold text-gray-900 text-emerald-600">{{ number_format($todaySearches) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 1: Top Keywords & Top Clicked Products via Suggestions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Searched Keywords -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-gray-900 flex items-center gap-2">
                    <i class="ph-fill ph-fire text-amber-500 text-lg"></i>
                    Top Searched Keywords ({{ ucfirst($period) }})
                </h3>
                <span class="text-xs text-gray-400 font-medium">Search Volume</span>
            </div>

            @if($topKeywords->isEmpty())
                <div class="py-12 text-center text-gray-400 text-sm">
                    <i class="ph ph-magnifying-glass text-3xl mb-2 opacity-40 block"></i>
                    No search keywords recorded for this period.
                </div>
            @else
                <div class="space-y-2.5">
                    @foreach($topKeywords as $index => $kw)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50/80 hover:bg-gray-100/90 transition-colors">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold shrink-0 {{ $index < 3 ? 'bg-amber-100 text-amber-800' : 'bg-gray-200 text-gray-700' }}">
                                    {{ $index + 1 }}
                                </span>
                                <span class="text-sm font-semibold text-gray-800 truncate">{{ $kw->query }}</span>
                            </div>
                            <div class="flex items-center gap-3 shrink-0">
                                <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($kw->last_searched)->diffForHumans() }}</span>
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-brand-50 text-brand-700 border border-brand-200/60">
                                    {{ $kw->count }} searches
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Top Products Clicked from Suggestions -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-gray-900 flex items-center gap-2">
                    <i class="ph-fill ph-trend-up text-emerald-500 text-lg"></i>
                    Top Suggestion Clicks (Dropdown)
                </h3>
                <span class="text-xs text-gray-400 font-medium">Conversion</span>
            </div>

            @if($topSuggestionProducts->isEmpty())
                <div class="py-12 text-center text-gray-400 text-sm">
                    <i class="ph ph-cursor-click text-3xl mb-2 opacity-40 block"></i>
                    No suggestion clicks logged yet.
                </div>
            @else
                <div class="space-y-2.5">
                    @foreach($topSuggestionProducts as $pLog)
                        @if($pLog->clickedProduct)
                            <div class="flex items-center justify-between p-2.5 rounded-xl bg-gray-50/80 hover:bg-gray-100/90 transition-colors">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-10 h-10 rounded-lg bg-gray-200 overflow-hidden shrink-0 border border-gray-200">
                                        @if($pLog->clickedProduct->featured_image)
                                            <img src="{{ str_starts_with($pLog->clickedProduct->featured_image, 'http') ? $pLog->clickedProduct->featured_image : '/storage/' . $pLog->clickedProduct->featured_image }}" 
                                                 class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-gray-400">
                                                <i class="ph ph-image"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <a href="{{ route('product.show', $pLog->clickedProduct->slug) }}" target="_blank" class="text-sm font-semibold text-gray-800 hover:text-brand-600 truncate block">
                                            {{ $pLog->clickedProduct->title }}
                                        </a>
                                        <div class="text-xs text-gray-400">
                                            Lifetime Views: <span class="font-medium text-gray-600">{{ $pLog->clickedProduct->views_count }}</span>
                                        </div>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200/60 shrink-0">
                                    {{ $pLog->clicks }} clicks
                                </span>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Row 2: Top Viewed Products & Customer / IP Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Top Viewed Products -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 lg:col-span-1">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-gray-900 flex items-center gap-2">
                    <i class="ph-fill ph-eye text-blue-500 text-lg"></i>
                    Top Viewed Products
                </h3>
                <span class="text-xs text-gray-400 font-medium">Traffic Leader</span>
            </div>

            @if($topViewedProducts->isEmpty())
                <div class="py-12 text-center text-gray-400 text-sm">
                    <i class="ph ph-package text-3xl mb-2 opacity-40 block"></i>
                    No product view counts logged yet.
                </div>
            @else
                <div class="space-y-2.5">
                    @foreach($topViewedProducts as $index => $prod)
                        <div class="flex items-center justify-between p-2.5 rounded-xl bg-gray-50/80 hover:bg-gray-100/90 transition-colors">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold shrink-0 bg-blue-100 text-blue-800">
                                    {{ $index + 1 }}
                                </span>
                                <div class="w-9 h-9 rounded-lg bg-gray-200 overflow-hidden shrink-0 border border-gray-200">
                                    @if($prod->featured_image)
                                        <img src="{{ str_starts_with($prod->featured_image, 'http') ? $prod->featured_image : '/storage/' . $prod->featured_image }}" 
                                             class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-gray-400">
                                            <i class="ph ph-image"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <a href="{{ route('product.show', $prod->slug) }}" target="_blank" class="text-xs font-semibold text-gray-800 hover:text-brand-600 truncate block">
                                        {{ $prod->title }}
                                    </a>
                                    <span class="text-[11px] text-gray-400">৳{{ number_format($prod->base_price) }}</span>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200/60 shrink-0">
                                {{ number_format($prod->current_period_views ?? $prod->views_count) }} views
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Top Active Searching Customers -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 lg:col-span-1">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-gray-900 flex items-center gap-2">
                    <i class="ph-fill ph-users text-purple-500 text-lg"></i>
                    Top Customers Searching
                </h3>
                <span class="text-xs text-gray-400 font-medium">Logged-in</span>
            </div>

            @if($topCustomers->isEmpty())
                <div class="py-12 text-center text-gray-400 text-sm">
                    <i class="ph ph-user-circle text-3xl mb-2 opacity-40 block"></i>
                    No registered user searches recorded.
                </div>
            @else
                <div class="space-y-2.5">
                    @foreach($topCustomers as $index => $c)
                        @if($c->user)
                            <div class="flex items-center justify-between p-2.5 rounded-xl bg-gray-50/80 hover:bg-gray-100/90 transition-colors">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="w-9 h-9 rounded-full bg-purple-100 text-purple-700 font-bold text-xs flex items-center justify-center shrink-0">
                                        {{ substr($c->user->name, 0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-xs font-semibold text-gray-800 truncate">{{ $c->user->name }}</div>
                                        <div class="text-[11px] text-gray-400 truncate">{{ $c->user->phone ?? $c->user->email }}</div>
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200/60 shrink-0">
                                    {{ $c->search_count }} searches
                                </span>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Top IP Addresses -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 lg:col-span-1">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-gray-900 flex items-center gap-2">
                    <i class="ph-fill ph-globe text-emerald-500 text-lg"></i>
                    Top Visitor IPs
                </h3>
                <span class="text-xs text-gray-400 font-medium">Activity Frequency</span>
            </div>

            @if($topIps->isEmpty())
                <div class="py-12 text-center text-gray-400 text-sm">
                    <i class="ph ph-globe text-3xl mb-2 opacity-40 block"></i>
                    No IP activity recorded.
                </div>
            @else
                <div class="space-y-2.5">
                    @foreach($topIps as $index => $ipItem)
                        <div class="flex items-center justify-between p-2.5 rounded-xl bg-gray-50/80 hover:bg-gray-100/90 transition-colors">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold shrink-0 bg-emerald-100 text-emerald-800">
                                    {{ $index + 1 }}
                                </span>
                                <div class="min-w-0">
                                    <a href="https://ipinfo.io/{{ $ipItem->ip_address }}" target="_blank" rel="noopener noreferrer"
                                       title="Lookup on ipinfo.io"
                                       class="text-xs font-mono font-bold text-gray-800 hover:text-brand-600 hover:underline flex items-center gap-1 truncate">
                                        <span>{{ $ipItem->ip_address }}</span>
                                        <i class="ph ph-arrow-square-out text-[11px] text-gray-400"></i>
                                    </a>
                                    <div class="text-[11px] text-gray-400">{{ \Carbon\Carbon::parse($ipItem->last_seen)->diffForHumans() }}</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                                    {{ $ipItem->ip_count }} times
                                </span>
                                <a href="{{ route('admin.blacklist.index', ['tab' => 'ip', 'search' => $ipItem->ip_address]) }}" 
                                   title="Review / Block IP in Blacklist"
                                   class="p-1 rounded-lg text-rose-500 hover:bg-rose-50 hover:text-rose-700 transition-colors">
                                    <i class="ph ph-shield-warning text-sm"></i>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Activity Logs Table with Filter & Pagination -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h3 class="font-bold text-gray-900">All Search & Click Activity Logs</h3>
                <p class="text-xs text-gray-500 mt-0.5">Historical activity log showing keyword, method, customer details, and timestamp.</p>
            </div>

            <!-- Filter Form -->
            <form method="GET" action="{{ route('admin.analytics.search') }}" class="flex items-center gap-2">
                <input type="hidden" name="period" value="{{ $period }}">
                
                <input type="text" 
                       name="q" 
                       value="{{ request('q') }}" 
                       placeholder="Filter by keyword..." 
                       class="text-xs bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none w-44">
                
                <select name="source" class="text-xs bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <option value="">All Sources</option>
                    <option value="suggestion" {{ request('source') === 'suggestion' ? 'selected' : '' }}>Suggestion Click</option>
                    <option value="direct" {{ request('source') === 'direct' ? 'selected' : '' }}>Direct Search</option>
                    <option value="no_result" {{ request('source') === 'no_result' ? 'selected' : '' }}>No Results Found</option>
                    <option value="live_search" {{ request('source') === 'live_search' ? 'selected' : '' }}>Live Typing</option>
                </select>

                <button type="submit" class="px-3 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-lg text-xs font-semibold transition-colors">
                    Filter
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs font-semibold uppercase tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-3.5">Keyword / Query</th>
                        <th class="px-6 py-3.5">Source Type</th>
                        <th class="px-6 py-3.5">Clicked Product</th>
                        <th class="px-6 py-3.5">Customer / Visitor</th>
                        <th class="px-6 py-3.5">IP Address</th>
                        <th class="px-6 py-3.5 text-right">Time & Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse($logs as $log)
                        <tr class="hover:bg-gray-50/70 transition-colors">
                            <td class="px-6 py-4 font-semibold text-gray-900">
                                <span class="bg-gray-100 px-2.5 py-1 rounded-md text-xs font-mono font-medium">
                                    {{ $log->query }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($log->source === 'suggestion')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200/60">
                                        <i class="ph ph-cursor-click"></i> Suggestion Click
                                    </span>
                                @elseif($log->source === 'no_result')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200/60">
                                        <i class="ph ph-warning-circle"></i> No Result Found
                                    </span>
                                @elseif($log->source === 'live_search')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-cyan-50 text-cyan-700 border border-cyan-200/60">
                                        <i class="ph ph-keyboard"></i> Live Typing
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200/60">
                                        <i class="ph ph-magnifying-glass"></i> Direct Search
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($log->clickedProduct)
                                    <a href="{{ route('product.show', $log->clickedProduct->slug) }}" target="_blank" class="text-xs font-medium text-brand-600 hover:text-brand-700 hover:underline flex items-center gap-1">
                                        <i class="ph ph-arrow-square-out"></i>
                                        <span class="truncate max-w-[180px]">{{ $log->clickedProduct->title }}</span>
                                    </a>
                                @else
                                    <span class="text-xs text-gray-400 italic">— None (Search Page) —</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($log->user)
                                    <div class="text-xs font-semibold text-gray-800">{{ $log->user->name }}</div>
                                    <div class="text-[11px] text-gray-400">{{ $log->user->phone ?? $log->user->email }}</div>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">
                                        <i class="ph ph-user"></i> Guest Visitor
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-xs font-mono">
                                @if($log->ip_address)
                                    <div class="flex items-center gap-1.5">
                                        <a href="https://ipinfo.io/{{ $log->ip_address }}" target="_blank" rel="noopener noreferrer"
                                           title="Lookup on ipinfo.io"
                                           class="text-gray-700 hover:text-brand-600 hover:underline inline-flex items-center gap-1">
                                            <span>{{ $log->ip_address }}</span>
                                            <i class="ph ph-arrow-square-out text-gray-400 text-[11px]"></i>
                                        </a>
                                        <a href="{{ route('admin.blacklist.index', ['tab' => 'ip', 'search' => $log->ip_address]) }}" 
                                           title="Blacklist this IP"
                                           class="text-gray-400 hover:text-rose-600 p-0.5 rounded transition-colors">
                                            <i class="ph ph-shield-warning text-xs"></i>
                                        </a>
                                    </div>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right text-xs">
                                <div class="font-semibold text-gray-800">{{ $log->created_at->format('M d, Y h:i A') }}</div>
                                <div class="text-[11px] text-gray-400">{{ $log->created_at->diffForHumans() }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                <i class="ph ph-folder-open text-3xl mb-2 opacity-50 block"></i>
                                No search logs recorded for this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
