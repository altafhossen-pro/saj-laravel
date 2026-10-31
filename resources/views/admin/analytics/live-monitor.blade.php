@extends('layouts.admin')

@section('header_title', 'Live Shoppers Monitor')

@section('content')
<div class="space-y-6" x-data="liveShoppersManager()">

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-gray-900">Live Shoppers Monitor</h1>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                    <span x-text="liveVisitors.length + ' Active Now'"></span>
                </span>
            </div>
            <p class="text-sm text-gray-500 mt-1">
                Real-time active storefront shoppers, visited pages & instant behavior stream (Auto-refreshes every 4s).
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.visitors.footprint') }}"
               class="px-4 py-2 rounded-xl bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 text-xs font-semibold shadow-2xs transition inline-flex items-center gap-1.5">
                <i class="ph ph-footprints text-base text-indigo-600"></i>
                <span>Historical Footprints &rarr;</span>
            </a>

            <button type="button" @click="fetchLiveFeed()"
                class="p-2.5 rounded-xl bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 shadow-2xs transition"
                title="Refresh Live Stream">
                <i class="ph ph-arrows-clockwise text-lg" :class="isRefreshing ? 'animate-spin text-brand-600' : ''"></i>
            </button>
        </div>
    </div>

    <!-- Live Metrics Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl p-4 text-white shadow-sm">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs uppercase font-semibold text-emerald-100 tracking-wider">Active Now</span>
                <span class="w-2.5 h-2.5 rounded-full bg-white animate-pulse"></span>
            </div>
            <div class="text-3xl font-extrabold" x-text="liveVisitors.length"></div>
            <div class="text-[11px] text-emerald-100 mt-1">Live right this second</div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
            <div class="flex items-center justify-between text-gray-500 mb-1">
                <span class="text-xs uppercase font-semibold tracking-wider">Today's Visitors</span>
                <i class="ph ph-users text-indigo-600 text-lg"></i>
            </div>
            <div class="text-3xl font-bold text-gray-900" x-text="todayStats.unique || 0"></div>
            <div class="text-[11px] text-gray-400 mt-1">Total unique visits today</div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
            <div class="flex items-center justify-between text-gray-500 mb-1">
                <span class="text-xs uppercase font-semibold tracking-wider">In Cart/Checkout</span>
                <i class="ph ph-shopping-bag text-rose-500 text-lg"></i>
            </div>
            <div class="text-3xl font-bold text-rose-600"
                 x-text="liveVisitors.filter(v => v.url && (v.url.indexOf('/checkout') !== -1 || v.url.indexOf('/cart') !== -1)).length"></div>
            <div class="text-[11px] text-gray-400 mt-1">High purchase intent now</div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
            <div class="flex items-center justify-between text-gray-500 mb-1">
                <span class="text-xs uppercase font-semibold tracking-wider">Recognized</span>
                <i class="ph ph-identification-badge text-amber-500 text-lg"></i>
            </div>
            <div class="text-3xl font-bold text-amber-600"
                 x-text="liveVisitors.filter(v => v.is_logged_in || v.name || v.last_seen_as).length"></div>
            <div class="text-[11px] text-gray-400 mt-1">Known member or previous buyer</div>
        </div>
    </div>

    <!-- Live Shoppers Stream Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <!-- Filter Header -->
        <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3 bg-gradient-to-r from-white via-emerald-50/15 to-white">
            <div class="flex items-center gap-2">
                <div class="bg-gray-100 p-1 rounded-xl flex items-center text-xs font-medium">
                    <button type="button" @click="activeFilter = 'all'"
                        class="px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1.5"
                        :class="activeFilter === 'all' ? 'bg-white text-gray-900 shadow-sm font-semibold' : 'text-gray-600 hover:text-gray-900'">
                        <span>All Active</span>
                        <span class="text-[10px] px-1.5 py-0.2 rounded-full"
                            :class="activeFilter === 'all' ? 'bg-gray-100 text-gray-800' : 'bg-gray-200 text-gray-600'"
                            x-text="liveVisitors.length"></span>
                    </button>
                    <button type="button" @click="activeFilter = 'members'"
                        class="px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1.5"
                        :class="activeFilter === 'members' ? 'bg-white text-gray-900 shadow-sm font-semibold' : 'text-gray-600 hover:text-gray-900'">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>
                        <span>Recognized</span>
                        <span class="text-[10px] px-1.5 py-0.2 rounded-full"
                            :class="activeFilter === 'members' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-200 text-gray-600'"
                            x-text="liveVisitors.filter(v => v.is_logged_in || v.name || v.last_seen_as).length"></span>
                    </button>
                    <button type="button" @click="activeFilter = 'checkout'"
                        class="px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1.5"
                        :class="activeFilter === 'checkout' ? 'bg-white text-gray-900 shadow-sm font-semibold' : 'text-gray-600 hover:text-gray-900'">
                        <i class="ph ph-shopping-bag text-rose-500"></i>
                        <span>Checkout / Cart</span>
                        <span class="text-[10px] px-1.5 py-0.2 rounded-full"
                            :class="activeFilter === 'checkout' ? 'bg-rose-50 text-rose-700' : 'bg-gray-200 text-gray-600'"
                            x-text="liveVisitors.filter(v => v.url && (v.url.indexOf('/checkout') !== -1 || v.url.indexOf('/cart') !== -1)).length"></span>
                    </button>
                </div>
            </div>

            <div class="text-xs text-gray-400 font-mono flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                <span>Live Feed Active</span>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50/70 text-gray-500 font-medium border-b border-gray-100 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3.5 text-center w-12">#</th>
                        <th class="px-6 py-3.5">Shopper / Identity</th>
                        <th class="px-6 py-3.5">Current Page & Title</th>
                        <th class="px-6 py-3.5">Device & Activity</th>
                        <th class="px-6 py-3.5">Time on Site</th>
                        <th class="px-6 py-3.5 text-center">Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="v in filteredVisitors" :key="v.vid">
                        <tr class="hover:bg-gray-50/80 transition-colors">
                            <!-- Slot / Serial Number -->
                            <td class="px-5 py-4 text-center">
                                <span class="inline-flex items-center justify-center min-w-8 h-7 px-2 rounded-lg bg-indigo-50 font-mono font-bold text-xs text-indigo-700 border border-indigo-200"
                                      title="Today's Visitor ID"
                                      x-text="'#' + (v.serial || v.slot)">
                                </span>
                            </td>

                            <!-- Shopper / Identity -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="relative shrink-0">
                                        <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs shadow-2xs"
                                            :class="{
                                                'bg-emerald-100 text-emerald-700 border border-emerald-200': v.is_logged_in,
                                                'bg-amber-100 text-amber-800 border border-amber-200': (!v.is_logged_in && (v.name || v.last_seen_as)),
                                                'bg-indigo-100 text-indigo-700 border border-indigo-200': (!v.is_logged_in && !v.name && !v.last_seen_as)
                                            }">
                                            <template x-if="v.name || v.last_seen_as">
                                                <span x-text="(v.name || v.last_seen_as).substring(0, 2).toUpperCase()"></span>
                                            </template>
                                            <template x-if="!v.name && !v.last_seen_as">
                                                <span class="font-mono text-[11px]" x-text="'#' + (v.serial || v.slot)"></span>
                                            </template>
                                        </div>
                                        <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full border-2 border-white"
                                            :class="v.seconds_ago <= 15 ? 'bg-emerald-500 animate-pulse' : 'bg-gray-400'"></span>
                                    </div>

                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                <span x-text="'Visitor #' + (v.serial || v.slot)"></span>
                                            </span>

                                            <template x-if="v.is_logged_in">
                                                <span class="font-bold text-gray-900 truncate" x-text="v.name"></span>
                                            </template>
                                            <template x-if="!v.is_logged_in && (v.name || v.last_seen_as)">
                                                <span class="font-bold text-gray-900 truncate" x-text="v.last_seen_as || v.name"></span>
                                            </template>

                                            <template x-if="v.is_logged_in">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                    <i class="ph ph-check-circle"></i> Logged In
                                                </span>
                                            </template>
                                            <template x-if="!v.is_logged_in && (v.name || v.last_seen_as)">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                                    <i class="ph ph-clock-counter-clockwise"></i> Previous Customer
                                                </span>
                                            </template>
                                        </div>

                                        <div class="text-xs text-gray-500 mt-1 flex items-center gap-2 flex-wrap">
                                            <template x-if="v.first_seen_time">
                                                <span class="inline-flex items-center gap-1 text-[11px] text-gray-600 font-medium bg-gray-50 px-1.5 py-0.5 rounded border border-gray-100">
                                                    <i class="ph ph-clock text-xs text-gray-400"></i>
                                                    <span x-text="'Entry: ' + v.first_seen_time"></span>
                                                </span>
                                            </template>
                                            <template x-if="v.phone">
                                                <span class="font-mono text-gray-700 font-medium" x-text="v.phone"></span>
                                            </template>
                                            <template x-if="v.ip">
                                                <span class="font-mono text-[11px] text-gray-400" x-text="'IP: ' + v.ip"></span>
                                            </template>
                                        </div>

                                        <template x-if="v.address">
                                            <div class="text-[11px] text-gray-600 mt-1 flex items-center gap-1 truncate max-w-[280px]" :title="v.address">
                                                <i class="ph ph-map-pin text-rose-500 shrink-0"></i>
                                                <span class="truncate" x-text="v.address"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </td>

                            <!-- Current Page & Title -->
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1 max-w-md">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold border"
                                            :class="getPageBadge(v.url).bg">
                                            <i class="ph" :class="getPageBadge(v.url).icon"></i>
                                            <span x-text="getPageBadge(v.url).label"></span>
                                        </span>

                                        <a :href="v.url" target="_blank"
                                            class="text-xs font-mono font-medium text-brand-600 hover:text-brand-700 hover:underline inline-flex items-center gap-1 truncate max-w-[220px]">
                                            <span x-text="v.url"></span>
                                            <i class="ph ph-arrow-square-out text-xs shrink-0"></i>
                                        </a>
                                    </div>

                                    <p class="text-xs text-gray-500 truncate" x-text="v.title || 'Browsing Store'"></p>
                                </div>
                            </td>

                            <!-- Device & Activity Engagement -->
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1">
                                    <div class="flex items-center gap-2 text-gray-700 text-xs font-medium">
                                        <template x-if="v.device === 'Mobile'">
                                            <span class="inline-flex items-center gap-1 text-gray-700">
                                                <i class="ph ph-device-mobile text-base text-gray-500"></i> Mobile
                                            </span>
                                        </template>
                                        <template x-if="v.device !== 'Mobile'">
                                            <span class="inline-flex items-center gap-1 text-gray-700">
                                                <i class="ph ph-desktop text-base text-gray-500"></i> Desktop
                                            </span>
                                        </template>
                                    </div>

                                    <!-- Activity Pill (Scrolling/Reading vs Idle vs Other Tab) -->
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <template x-if="v.is_visible === false">
                                            <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-purple-700 bg-purple-50 px-2 py-0.5 rounded-full border border-purple-200" title="Visitor is in another browser tab or minimized">
                                                <i class="ph ph-browsers text-xs text-purple-600"></i>
                                                Switched to Other Tab
                                            </span>
                                        </template>

                                        <template x-if="v.is_visible !== false && v.is_engaged">
                                            <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                Active & Scrolling
                                                <span x-show="v.scroll_percent > 0" x-text="'(' + v.scroll_percent + '%)'"></span>
                                            </span>
                                        </template>

                                        <template x-if="v.is_visible !== false && !v.is_engaged">
                                            <span class="inline-flex items-center gap-1 text-[10px] font-medium text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                                Idle / Tab Open
                                            </span>
                                        </template>
                                    </div>
                                </div>
                            </td>

                            <!-- Time on Site -->
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-xs font-semibold text-gray-900" x-text="formatDuration(v.duration)"></span>
                                    <span class="text-[11px] text-gray-400">Total session</span>
                                </div>
                            </td>

                            <!-- Status / Pulse -->
                            <td class="px-6 py-4 text-center">
                                <div class="flex flex-col items-center">
                                    <template x-if="v.seconds_ago <= 10">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                                            Active Now
                                        </span>
                                    </template>
                                    <template x-if="v.seconds_ago > 10">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-gray-100 text-gray-600">
                                            <i class="ph ph-clock text-xs"></i>
                                            <span x-text="v.seconds_ago + 's ago'"></span>
                                        </span>
                                    </template>
                                </div>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Send Instant Offer Button -->
                                    <button type="button" @click="openOfferModal(v)"
                                        class="cursor-pointer inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-pink-50 hover:bg-pink-100 text-pink-700 font-bold text-xs border border-pink-200 shadow-2xs hover:shadow-xs transition"
                                        title="Send Instant Screen Popup Offer">
                                        <i class="ph ph-lightning text-sm text-pink-600"></i>
                                        <span>Offer</span>
                                    </button>

                                    <!-- Visitor Journey Action Button -->
                                    <button type="button" @click="openJourneyModal(v)"
                                        class="cursor-pointer inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-brand-50 hover:bg-brand-100 text-brand-700 font-semibold text-xs border border-brand-200 shadow-2xs hover:shadow-xs transition">
                                        <i class="ph ph-footprints text-sm text-brand-600"></i>
                                        <span>Timeline</span>
                                        <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-brand-200/80 text-brand-900 font-mono" x-text="v.history ? v.history.length : 1"></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- Empty State -->
                    <tr x-show="filteredVisitors.length === 0">
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl mb-3 relative">
                                    <i class="ph ph-broadcast"></i>
                                    <span class="animate-ping absolute inline-flex h-8 w-8 rounded-full bg-emerald-400 opacity-20"></span>
                                </div>
                                <h4 class="text-sm font-bold text-gray-900">No active shoppers in this view</h4>
                                <p class="text-xs text-gray-400 mt-1">Visitors browsing the store right now will show up instantly.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Live Shopper Journey Timeline Modal -->
    <div x-show="isJourneyModalOpen"
        x-cloak
        style="display: none;"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/50 backdrop-blur-xs p-4"
        @keydown.escape.window="isJourneyModalOpen = false">

        <div class="bg-white w-full max-w-xl rounded-2xl shadow-xl overflow-hidden border border-gray-100 max-h-[90vh] flex flex-col">

            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-gray-50 to-white">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center text-lg">
                        <i class="ph ph-footprints"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 text-sm">Live Shopper Navigation Journey</h3>
                        <p class="text-[11px] text-gray-400">Chronological history of pages viewed in this visit</p>
                    </div>
                </div>

                <button type="button" @click="isJourneyModalOpen = false" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition">
                    <i class="ph ph-x text-lg"></i>
                </button>
            </div>

            <div class="p-6 overflow-y-auto space-y-4">
                <template x-if="selectedVisitor">
                    <div class="space-y-4">
                        <div class="bg-gray-50 p-3 rounded-xl border border-gray-100 flex items-center justify-between text-xs">
                            <div>
                                <span class="font-bold text-gray-900" x-text="selectedVisitor.name || selectedVisitor.last_seen_as || 'Guest Visitor'"></span>
                                <span x-show="selectedVisitor.phone" class="block font-mono text-gray-600 mt-0.5" x-text="selectedVisitor.phone"></span>
                            </div>
                            <div class="text-right">
                                <span class="font-bold text-gray-900" x-text="formatDuration(selectedVisitor.duration)"></span>
                                <span class="block text-gray-400 text-[11px]" x-text="'IP: ' + (selectedVisitor.ip || '--')"></span>
                            </div>
                        </div>

                        <div class="relative pl-6 space-y-3 border-l-2 border-brand-100">
                            <template x-for="(step, idx) in (selectedVisitor.history || [])" :key="idx">
                                <div class="relative">
                                    <div class="absolute -left-[31px] top-1.5 w-3 h-3 rounded-full border-2 border-white shadow-2xs"
                                        :class="idx === (selectedVisitor.history.length - 1) ? 'bg-emerald-500 ring-2 ring-emerald-200' : 'bg-brand-500'"></div>

                                    <div class="bg-gray-50 hover:bg-brand-50/40 p-2.5 rounded-xl border border-gray-100 transition">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="font-semibold text-xs text-gray-900" x-text="step.title || 'Page Visit'"></span>
                                            <span class="text-[10px] font-mono text-gray-400" x-text="step.time || ''"></span>
                                        </div>
                                        <div class="text-[11px] text-brand-600 font-mono mt-0.5 truncate">
                                            <a :href="step.url" target="_blank" class="hover:underline" x-text="step.url"></a>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <div class="px-6 py-3 border-t border-gray-100 bg-gray-50 flex items-center justify-between">
                <template x-if="selectedVisitor && selectedVisitor.vid">
                    <a :href="'{{ route('admin.visitors.footprint') }}?search=' + encodeURIComponent(selectedVisitor.vid)"
                       class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 hover:underline inline-flex items-center gap-1">
                        <span>View All Historical Days for this Visitor &rarr;</span>
                    </a>
                </template>
                <button type="button" @click="isJourneyModalOpen = false" class="px-4 py-1.5 bg-white border border-gray-200 hover:bg-gray-100 text-gray-700 text-xs font-semibold rounded-xl transition ml-auto">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Send Custom Offer Modal -->
    <div x-show="offerModalOpen"
        x-cloak
        style="display: none;"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/60 backdrop-blur-xs p-4"
        @keydown.escape.window="offerModalOpen = false">

        <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden border border-gray-100">

            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-pink-50/50 to-white">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-pink-100 text-pink-600 flex items-center justify-center text-lg">
                        <i class="ph ph-lightning"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 text-base">Send Instant Screen Popup Offer</h3>
                        <p class="text-xs text-gray-500">Will immediately appear on this shopper's screen</p>
                    </div>
                </div>

                <button type="button" @click="offerModalOpen = false" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition">
                    <i class="ph ph-x text-lg"></i>
                </button>
            </div>

            <form @submit.prevent="submitOffer()" class="p-6 space-y-4">
                <div class="bg-pink-50/60 border border-pink-100 rounded-xl p-3 flex items-center justify-between text-xs">
                    <div>
                        <span class="text-gray-500 block text-[10px] uppercase font-semibold">Target Shopper:</span>
                        <span class="font-bold text-gray-900" x-text="offerTarget ? (offerTarget.name || offerTarget.last_seen_as || 'Guest Visitor') : ''"></span>
                        <span x-show="offerTarget && offerTarget.phone" class="font-mono text-gray-600 ml-1" x-text="'(' + offerTarget.phone + ')'"></span>
                    </div>
                    <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold">Live Active Now</span>
                </div>

                <!-- Template Selector -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">অফার টেমপ্লেট নির্বাচন করুন (Optional)</label>
                    <select x-model="selectedTemplate" @change="applyTemplate()"
                        class="w-full px-3.5 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                        <option value="">-- Choose a Pre-defined Bengali Template or Write Custom --</option>
                        <template x-for="t in templates" :key="t.id">
                            <option :value="t.id" x-text="t.name"></option>
                        </template>
                    </select>
                </div>

                <!-- Title -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Popup Headline *</label>
                    <input type="text" x-model="offerForm.title" required placeholder="পপআপের শিরোনাম লিখুন"
                        class="w-full px-3.5 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                </div>

                <!-- Message -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Offer Message / Pitch *</label>
                    <textarea x-model="offerForm.message" rows="3" required placeholder="অফার মেসেজ লিখুন..."
                        class="w-full px-3.5 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-pink-500 focus:border-pink-500"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <!-- Coupon Code -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Coupon Code (Optional)</label>
                        <input type="text" x-model="offerForm.coupon_code" placeholder="e.g. FLASH10"
                            class="w-full px-3.5 py-2 bg-white border border-gray-200 rounded-xl text-sm font-mono uppercase focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                    </div>

                    <!-- Delay Seconds -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Popup Delay</label>
                        <select x-model="offerForm.delay_seconds"
                            class="w-full px-3.5 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                            <option value="0">Instant (0 seconds)</option>
                            <option value="5">5 seconds</option>
                            <option value="10">10 seconds (Recommended)</option>
                            <option value="20">20 seconds</option>
                            <option value="30">30 seconds</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <!-- Button Text -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Button Text</label>
                        <input type="text" x-model="offerForm.button_text"
                            class="w-full px-3.5 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                    </div>

                    <!-- Button URL -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Button Redirect Link</label>
                        <input type="text" x-model="offerForm.button_url"
                            class="w-full px-3.5 py-2 bg-white border border-gray-200 rounded-xl text-sm font-mono focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                    </div>
                </div>

                <template x-if="offerNotice">
                    <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-semibold text-center" x-text="offerNotice"></div>
                </template>

                <div class="pt-2 flex items-center justify-end gap-2 border-t border-gray-100">
                    <button type="button" @click="offerModalOpen = false" class="px-4 py-2 border border-gray-200 rounded-xl text-xs font-semibold text-gray-600 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit" :disabled="offerSending"
                        class="px-5 py-2 bg-gradient-to-r from-pink-500 to-rose-500 hover:from-pink-600 hover:to-rose-600 text-white rounded-xl text-xs font-bold shadow-md shadow-pink-500/20 transition flex items-center gap-1.5">
                        <i class="ph ph-paper-plane-tilt font-bold"></i>
                        <span x-text="offerSending ? 'Firing...' : 'Fire Instant Offer'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function liveShoppersManager() {
        return {
            liveVisitors: [],
            todayStats: { unique: {{ $initialTodayVisitors ?? 0 }}, views: 0 },
            activeFilter: 'all',
            isRefreshing: false,
            selectedVisitor: null,
            isJourneyModalOpen: false,

            // Offer Modal State
            offerModalOpen: false,
            offerTarget: null,
            selectedTemplate: '',
            templates: @json($templates ?? []),
            offerForm: {
                title: '',
                message: '',
                coupon_code: '',
                button_text: 'অফারটি গ্রহণ করুন',
                button_url: '/checkout',
                delay_seconds: 0
            },
            offerSending: false,
            offerNotice: null,

            applyTemplate() {
                if (!this.selectedTemplate) return;
                const t = this.templates.find(item => item.id == this.selectedTemplate);
                if (t) {
                    this.offerForm.title = t.title;
                    this.offerForm.message = t.message;
                    this.offerForm.coupon_code = t.coupon_code || '';
                    this.offerForm.button_text = t.button_text || 'অফারটি গ্রহণ করুন';
                    this.offerForm.button_url = t.button_url || '/checkout';
                }
            },

            // Persistent slot assignment for rock-solid visual row order (#1, #2, #3...)
            slotMap: {},
            nextSlotNum: 1,

            init() {
                this.fetchLiveFeed();
                setInterval(() => {
                    this.fetchLiveFeed();
                }, 4000);
            },

            fetchLiveFeed() {
                this.isRefreshing = true;
                fetch('{{ route('admin.api.live-visitors') }}')
                    .then(res => res.json())
                    .then(data => {
                        const fresh = Array.isArray(data.visitors) ? data.visitors : [];
                        
                        // Assign persistent slot numbers so rows don't constantly jump around
                        fresh.forEach(v => {
                            if (!this.slotMap[v.vid]) {
                                this.slotMap[v.vid] = this.nextSlotNum++;
                            }
                            v.slot = this.slotMap[v.vid];
                        });

                        // Stable sorting: keep existing slots in ascending order
                        fresh.sort((a, b) => a.slot - b.slot);

                        this.liveVisitors = fresh;
                        if (data.today) {
                            this.todayStats = data.today;
                        }
                        if (this.selectedVisitor && this.isJourneyModalOpen) {
                            const found = this.liveVisitors.find(v => v.vid === this.selectedVisitor.vid);
                            if (found) {
                                this.selectedVisitor = found;
                            }
                        }
                    })
                    .catch(err => console.error(err))
                    .finally(() => {
                        this.isRefreshing = false;
                    });
            },

            get filteredVisitors() {
                if (this.activeFilter === 'members') {
                    return this.liveVisitors.filter(v => v.is_logged_in || v.name || v.last_seen_as);
                }
                if (this.activeFilter === 'checkout') {
                    return this.liveVisitors.filter(v => v.url && (v.url.indexOf('/checkout') !== -1 || v.url.indexOf('/cart') !== -1));
                }
                return this.liveVisitors;
            },

            openJourneyModal(v) {
                this.selectedVisitor = v;
                this.isJourneyModalOpen = true;
            },

            openOfferModal(v) {
                this.offerTarget = v;
                this.selectedTemplate = '';
                this.offerForm = {
                    title: '',
                    message: '',
                    coupon_code: '',
                    button_text: 'অফারটি গ্রহণ করুন',
                    button_url: '/checkout',
                    delay_seconds: 0
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
                        customer_phone: this.offerTarget.phone || null,
                        user_id: this.offerTarget.user_id || null
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.offerNotice = 'Offer sent! Will popup on shopper screen.';
                        setTimeout(() => {
                            this.offerModalOpen = false;
                            this.offerNotice = null;
                        }, 1400);
                    }
                })
                .catch(err => {
                    alert('Failed to send offer: ' + err);
                })
                .finally(() => {
                    this.offerSending = false;
                });
            },

            formatDuration(seconds) {
                if (!seconds || seconds <= 0) return '0s';
                const mins = Math.floor(seconds / 60);
                const rem = seconds % 60;
                return mins > 0 ? mins + 'm ' + rem + 's' : rem + 's';
            },

            getPageBadge(url) {
                if (!url || url === '/') return { label: 'Home Page', icon: 'ph-house', bg: 'bg-blue-50 text-blue-700 border-blue-200' };
                if (url.indexOf('/checkout') !== -1) return { label: 'Checkout', icon: 'ph-credit-card', bg: 'bg-rose-50 text-rose-700 border-rose-200' };
                if (url.indexOf('/cart') !== -1) return { label: 'Cart Drawer', icon: 'ph-shopping-bag', bg: 'bg-amber-50 text-amber-700 border-amber-200' };
                if (url.indexOf('/product/') !== -1) return { label: 'Product View', icon: 'ph-package', bg: 'bg-purple-50 text-purple-700 border-purple-200' };
                if (url.indexOf('/category/') !== -1) return { label: 'Category', icon: 'ph-tag', bg: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
                if (url.indexOf('/search') !== -1) return { label: 'Search Page', icon: 'ph-magnifying-glass', bg: 'bg-sky-50 text-sky-700 border-sky-200' };
                return { label: 'Store Page', icon: 'ph-browsers', bg: 'bg-gray-50 text-gray-700 border-gray-200' };
            }
        };
    }
</script>
@endpush
