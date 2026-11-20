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
                        <tr class="hover:bg-gray-50/80 transition-colors select-none group cursor-context-menu"
                            @contextmenu.prevent="openContextMenu($event, v)">
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
                                                <a :href="'https://ipinfo.io/' + encodeURIComponent(v.ip)" target="_blank" rel="noopener noreferrer"
                                                   class="font-mono text-[11px] text-gray-400 hover:text-indigo-600 hover:underline inline-flex items-center gap-1"
                                                   title="Lookup IP on ipinfo.io">
                                                    <span x-text="'IP: ' + v.ip"></span>
                                                    <i class="ph ph-arrow-square-out text-[10px]"></i>
                                                </a>
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
                                <div class="flex flex-col gap-1.5 max-w-md">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <!-- Checkout High-Priority Pulsing Badge -->
                                        <template x-if="v.url && v.url.indexOf('/checkout') !== -1">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-500 text-white shadow-xs animate-pulse">
                                                <i class="ph-bold ph-credit-card"></i>
                                                In Checkout
                                            </span>
                                        </template>

                                        <!-- Cart Page Badge -->
                                        <template x-if="v.url && v.url.indexOf('/cart') !== -1">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-500 text-white shadow-xs">
                                                <i class="ph-bold ph-shopping-cart"></i>
                                                In Cart
                                            </span>
                                        </template>

                                        <!-- Realtime Cart Items & Value Badge -->
                                        <template x-if="v.cart_items > 0">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200"
                                                  :title="'Customer has ' + v.cart_items + ' item(s) worth ৳' + (v.cart_total || 0)">
                                                <i class="ph-bold ph-shopping-bag text-emerald-600"></i>
                                                <span x-text="v.cart_items + ' item' + (v.cart_items > 1 ? 's' : '')"></span>
                                                <span class="text-emerald-400">&bull;</span>
                                                <span class="font-mono" x-text="'৳' + (v.cart_total || 0)"></span>
                                            </span>
                                        </template>

                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold border"
                                            :class="getPageBadge(v.url).bg">
                                            <i class="ph" :class="getPageBadge(v.url).icon"></i>
                                            <span x-text="getPageBadge(v.url).label"></span>
                                        </span>

                                        <a :href="v.url" target="_blank"
                                            class="text-xs font-mono font-medium text-brand-600 hover:text-brand-700 hover:underline inline-flex items-center gap-1 truncate max-w-[200px]">
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

                            <!-- Actions (Context Menu Trigger) -->
                            <td class="px-6 py-4 text-right">
                                <button type="button" @click="openContextMenu($event, v)"
                                    class="cursor-pointer p-1.5 rounded-xl text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition inline-flex items-center justify-center"
                                    title="Shopper Options (or Right-Click row)">
                                    <i class="ph-bold ph-dots-three-vertical text-base"></i>
                                </button>
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

                        <!-- Real-time Checkout Live Typed Info & History Versions -->
                        <template x-if="selectedVisitor.name || selectedVisitor.phone || selectedVisitor.address || (cartData?.info_history && cartData.info_history.length > 0) || (selectedVisitor.info_history && selectedVisitor.info_history.length > 0)">
                            <div class="p-3 bg-gradient-to-r from-blue-50/70 to-indigo-50/50 border border-blue-100 rounded-xl space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1.5 text-xs font-bold text-blue-900">
                                        <i class="ph-bold ph-keyboard text-brand-600"></i>
                                        <span>Live Checkout Input (Current)</span>
                                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                                            Live
                                        </span>
                                    </div>
                                    <template x-if="(cartData?.info_history && cartData.info_history.length > 1) || (selectedVisitor.info_history && selectedVisitor.info_history.length > 1)">
                                        <span class="text-[11px] font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md border border-indigo-200"
                                              x-text="(cartData?.info_history?.length || selectedVisitor.info_history?.length) + ' versions typed'">
                                        </span>
                                    </template>
                                </div>

                                <!-- Current Live Active Info -->
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-[11px]">
                                    <div class="bg-white/90 px-2.5 py-1.5 rounded-lg border border-blue-100/70 shadow-2xs">
                                        <span class="text-gray-400 block text-[10px] uppercase font-semibold">Name</span>
                                        <span class="font-bold text-gray-900 truncate block" x-text="selectedVisitor.name || cartData?.customer_name || '--'"></span>
                                    </div>
                                    <div class="bg-white/90 px-2.5 py-1.5 rounded-lg border border-blue-100/70 shadow-2xs">
                                        <span class="text-gray-400 block text-[10px] uppercase font-semibold">Phone</span>
                                        <span class="font-mono font-bold text-gray-900 truncate block" x-text="selectedVisitor.phone || cartData?.customer_phone || '--'"></span>
                                    </div>
                                    <div class="bg-white/90 px-2.5 py-1.5 rounded-lg border border-blue-100/70 shadow-2xs">
                                        <span class="text-gray-400 block text-[10px] uppercase font-semibold">Address</span>
                                        <span class="font-medium text-gray-900 truncate block" x-text="selectedVisitor.address || cartData?.customer_address || '--'"></span>
                                    </div>
                                </div>

                                <!-- All Previous Revisions (Collapsible / Full View) -->
                                <template x-if="(cartData?.info_history && cartData.info_history.length > 1) || (selectedVisitor.info_history && selectedVisitor.info_history.length > 1)">
                                    <div class="mt-2 pt-2 border-t border-blue-100/80">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500 block mb-1.5 flex items-center gap-1">
                                            <i class="ph ph-clock-counter-clockwise text-indigo-600"></i>
                                            All Previous Typed Versions (History)
                                        </span>
                                        <div class="space-y-1.5 max-h-36 overflow-y-auto pr-1">
                                            <template x-for="(rev, rIdx) in ((cartData?.info_history || selectedVisitor.info_history) ? (cartData?.info_history || selectedVisitor.info_history).slice().reverse() : [])" :key="rIdx">
                                                <div class="bg-white/90 p-2 rounded-lg border border-gray-100 text-[11px] flex items-center justify-between gap-2 shadow-2xs">
                                                    <div class="flex items-center gap-1.5 min-w-0">
                                                        <span class="px-1.5 py-0.2 rounded bg-indigo-50 text-indigo-700 font-bold text-[10px] shrink-0" x-text="'v' + rev.version"></span>
                                                        <span class="font-bold text-gray-800 truncate" x-text="rev.name || 'No Name'"></span>
                                                        <span class="text-gray-300">&bull;</span>
                                                        <span class="font-mono text-gray-700 truncate" x-text="rev.phone || 'No Phone'"></span>
                                                        <template x-if="rev.address">
                                                            <span class="text-gray-500 truncate" x-text="'&bull; ' + rev.address"></span>
                                                        </template>
                                                    </div>
                                                    <span class="text-[10px] text-gray-400 font-mono shrink-0" x-text="rev.time"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <div class="relative pl-6 space-y-3 border-l-2 border-brand-100">
                            <template x-for="(step, idx) in (selectedVisitor.history ? selectedVisitor.history.slice().reverse() : [])" :key="idx">
                                <div class="relative">
                                    <!-- Dynamic Marker Indicator based on step type -->
                                    <div class="absolute -left-[31px] top-2 w-3 h-3 rounded-full border-2 border-white shadow-2xs"
                                        :class="step.type === 'cart_add' 
                                            ? 'bg-emerald-500' 
                                            : (step.type === 'cart_remove' 
                                                ? 'bg-rose-500' 
                                                : (idx === 0 ? 'bg-emerald-500 ring-4 ring-emerald-200/80 animate-pulse' : 'bg-brand-400'))">
                                    </div>

                                    <div class="p-2.5 rounded-xl border transition"
                                         :class="step.type === 'cart_add'
                                            ? 'bg-emerald-50/70 border-emerald-200 shadow-2xs'
                                            : (step.type === 'cart_remove'
                                                ? 'bg-rose-50/70 border-rose-200 shadow-2xs'
                                                : (idx === 0 ? 'bg-emerald-50/60 border-emerald-200/80 shadow-2xs' : 'bg-gray-50 hover:bg-brand-50/40 border-gray-100'))">
                                        
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="flex items-center gap-1.5 min-w-0">
                                                <!-- Cart Add Badge -->
                                                <template x-if="step.type === 'cart_add'">
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-600 text-white shrink-0 flex items-center gap-1">
                                                        <i class="ph-bold ph-shopping-bag"></i> Cart Add
                                                    </span>
                                                </template>

                                                <!-- Cart Remove Badge -->
                                                <template x-if="step.type === 'cart_remove'">
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-600 text-white shrink-0 flex items-center gap-1">
                                                        <i class="ph-bold ph-trash"></i> Cart Remove
                                                    </span>
                                                </template>

                                                <span class="font-semibold text-xs text-gray-900 truncate" x-text="step.title || 'Page Visit'"></span>
                                                
                                                <template x-if="idx === 0 && (!step.type || step.type === 'page')">
                                                    <span class="px-1.5 py-0.2 rounded-full text-[9px] font-bold bg-emerald-100 text-emerald-800 uppercase tracking-wider shrink-0">Current</span>
                                                </template>
                                            </div>
                                            <span class="text-[10px] font-mono text-gray-400 shrink-0" x-text="step.time || ''"></span>
                                        </div>
                                        
                                        <template x-if="step.url">
                                            <div class="text-[11px] text-brand-600 font-mono mt-0.5 truncate">
                                                <a :href="step.url" target="_blank" class="hover:underline" x-text="step.url"></a>
                                            </div>
                                        </template>
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
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="font-bold text-gray-900 text-xs" x-text="'Shopper #' + (contextVisitor?.serial || contextVisitor?.slot || 1)"></span>
            </div>
            <span class="text-[10px] text-gray-500 font-medium px-1.5 py-0.5 rounded bg-white border border-gray-200" x-text="contextVisitor?.device || 'Live'"></span>
        </div>

        <div class="py-1">
            <!-- 1. Order as Customer -->
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

            <div class="my-1 border-t border-gray-100"></div>

            <!-- 4. Live Journey -->
            <button type="button" @click="triggerLiveJourney()"
                    class="w-full text-left px-3.5 py-2 hover:bg-blue-50 hover:text-blue-700 flex items-center gap-2.5 transition-colors cursor-pointer group">
                <i class="ph ph-footprints text-base text-gray-400 group-hover:text-blue-600"></i>
                <span class="font-medium text-gray-700 group-hover:text-blue-700">Live Journey</span>
            </button>

            <!-- 5. Footprint History -->
            <button type="button" @click="openFootprintHistory()"
                    class="w-full text-left px-3.5 py-2 hover:bg-gray-100 flex items-center gap-2.5 transition-colors cursor-pointer group">
                <i class="ph ph-clock-counter-clockwise text-base text-gray-400 group-hover:text-gray-700"></i>
                <span class="font-medium text-gray-700 group-hover:text-gray-900">Footprint History</span>
            </button>
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
                                <h3 class="text-base font-bold text-gray-900" x-text="'Shopper #' + (selectedVisitor?.serial || selectedVisitor?.slot || 1) + ' - Cart & Checkout Draft'"></h3>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">Live</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5" x-text="selectedVisitor?.name || selectedVisitor?.last_seen_as || 'Browsing Storefront'"></p>
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
                        <span class="text-xs font-medium">Fetching real-time cart data...</span>
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

                        <!-- Real-time Shopping Cart Items -->
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
                        <p class="text-[11px] text-gray-400">Cart & Checkout details autofilled from live session</p>
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
                                    Customer Details
                                </span>

                                <!-- Revision Version Switcher if user edited multiple times -->
                                <template x-if="(cartData?.info_history && cartData.info_history.length > 1) || (selectedVisitor?.info_history && selectedVisitor.info_history.length > 1)">
                                    <div class="flex items-center gap-1 text-[11px]">
                                        <label class="text-gray-500 text-[10px] font-semibold">Pre-fill Version:</label>
                                        <select x-model="selectedHistoryVersion" @change="onHistoryVersionChange()"
                                                class="bg-white border border-gray-200 text-gray-800 text-[11px] font-semibold rounded-lg px-2 py-0.5 focus:outline-none focus:ring-1 focus:ring-brand-500">
                                            <option value="latest">Latest</option>
                                            <template x-for="rev in (cartData?.info_history || selectedVisitor.info_history)" :key="rev.version">
                                                <option :value="rev.version" x-text="'v' + rev.version + ' (' + rev.time + ')'"></option>
                                            </template>
                                        </select>
                                    </div>
                                </template>
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">Customer Name <span class="text-red-500">*</span></label>
                                <input type="text" x-model="orderForm.customer_name" placeholder="Full Name"
                                       class="w-full bg-white border border-gray-200 rounded-lg px-3 py-1.5 text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">Customer Phone <span class="text-red-500">*</span></label>
                                <input type="tel" x-model="orderForm.customer_contact" placeholder="017xxxxxxxx"
                                       class="w-full bg-white border border-gray-200 rounded-lg px-3 py-1.5 text-xs font-mono text-gray-800 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">Delivery Address <span class="text-red-500">*</span></label>
                                <textarea x-model="orderForm.shipping_address" rows="2" placeholder="Full Delivery Address"
                                          class="w-full bg-white border border-gray-200 rounded-lg px-3 py-1.5 text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-brand-500"></textarea>
                            </div>

                            <!-- Quick Copy/Pick Individual Values from Prior Versions -->
                            <template x-if="(cartData?.info_history && cartData.info_history.length > 1) || (selectedVisitor?.info_history && selectedVisitor.info_history.length > 1)">
                                <div class="mt-2 pt-2 border-t border-gray-200">
                                    <div class="flex items-center justify-between mb-1.5">
                                        <span class="text-[10px] font-bold text-gray-600 uppercase tracking-wider flex items-center gap-1">
                                            <i class="ph ph-copy text-indigo-600"></i>
                                            Pick from Typed Revisions:
                                        </span>
                                        <span class="text-[9px] text-gray-400">Click any field to apply</span>
                                    </div>
                                    <div class="space-y-1.5 max-h-32 overflow-y-auto pr-1">
                                        <template x-for="h in (cartData?.info_history || selectedVisitor.info_history)" :key="h.version">
                                            <div class="p-1.5 bg-white rounded-lg border border-gray-200/80 text-[10px] flex items-center justify-between gap-1 shadow-2xs">
                                                <div class="flex items-center gap-1 flex-wrap min-w-0">
                                                    <span class="px-1 py-0.2 rounded bg-indigo-50 text-indigo-700 font-bold" x-text="'v' + h.version"></span>
                                                    <template x-if="h.name">
                                                        <button type="button" @click="orderForm.customer_name = h.name"
                                                                class="px-1.5 py-0.5 rounded bg-gray-100 hover:bg-brand-50 hover:text-brand-700 font-medium truncate max-w-[100px]"
                                                                title="Click to apply this name" x-text="h.name">
                                                        </button>
                                                    </template>
                                                    <template x-if="h.phone">
                                                        <button type="button" @click="orderForm.customer_contact = h.phone"
                                                                class="px-1.5 py-0.5 rounded bg-gray-100 hover:bg-brand-50 hover:text-brand-700 font-mono truncate max-w-[90px]"
                                                                title="Click to apply this phone" x-text="h.phone">
                                                        </button>
                                                    </template>
                                                    <template x-if="h.address">
                                                        <button type="button" @click="orderForm.shipping_address = h.address"
                                                                class="px-1.5 py-0.5 rounded bg-gray-100 hover:bg-brand-50 hover:text-brand-700 truncate max-w-[110px]"
                                                                title="Click to apply this address" x-text="h.address">
                                                        </button>
                                                    </template>
                                                </div>
                                                <span class="text-[9px] text-gray-400 shrink-0 font-mono" x-text="h.time"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Order Settings Card -->
                        <div class="bg-gray-50/80 p-4 rounded-xl border border-gray-200/70 space-y-3">
                            <span class="text-xs font-bold text-gray-800 uppercase tracking-wider flex items-center gap-1.5">
                                <i class="ph-bold ph-sliders text-brand-600"></i>
                                Order Settings
                            </span>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-600 mb-1">Order Source</label>
                                    <select x-model="orderForm.order_source"
                                            class="w-full bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-brand-500">
                                        <option value="website">Website Live</option>
                                        <option value="phone">Phone Call</option>
                                        <option value="whatsapp">WhatsApp</option>
                                        <option value="facebook">Facebook</option>
                                        <option value="manual">Manual POS</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-600 mb-1">Delivery (৳)</label>
                                    <input type="number" x-model.number="orderForm.delivery_charge" min="0"
                                           class="w-full bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-brand-500">
                                </div>
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">Special Discount (৳)</label>
                                <input type="number" x-model.number="orderForm.discount" min="0" placeholder="0"
                                       class="w-full bg-white border border-gray-200 rounded-lg px-3 py-1.5 text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">Order Notes (Optional)</label>
                                <input type="text" x-model="orderForm.notes" placeholder="Notes or instructions..."
                                       class="w-full bg-white border border-gray-200 rounded-lg px-3 py-1.5 text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            </div>
                        </div>

                    </div>

                    <!-- Right: Product Cart & POS Search (7 cols) -->
                    <div class="lg:col-span-7 flex flex-col gap-4">

                        <!-- Product Search / Barcode Bar -->
                        <div class="relative">
                            <i class="ph ph-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                            <input type="text" x-model="posSearchQuery" @input.debounce.250ms="searchPosProducts()"
                                   placeholder="Scan barcode or search by name / SKU to add more..."
                                   class="w-full pl-9 pr-8 py-2 bg-white border border-gray-200 rounded-xl text-xs text-gray-800 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            <i x-show="isSearchingPos" class="ph ph-spinner animate-spin absolute right-3 top-1/2 -translate-y-1/2 text-brand-600 text-sm"></i>

                            <!-- Search Auto-complete Suggestions -->
                            <div x-show="showPosSuggestions && posSuggestions.length > 0" @click.away="showPosSuggestions = false"
                                 class="absolute z-50 left-0 right-0 mt-1.5 bg-white rounded-xl shadow-xl border border-gray-100 max-h-60 overflow-y-auto">
                                <template x-for="prod in posSuggestions" :key="prod.id">
                                    <div class="p-2 border-b border-gray-50 last:border-0 hover:bg-gray-50 cursor-pointer text-xs">
                                        <template x-if="prod.product_type === 'variable' && prod.variants && prod.variants.length > 0">
                                            <div>
                                                <span class="font-bold text-gray-900 block" x-text="prod.title"></span>
                                                <div class="mt-1 flex flex-wrap gap-1">
                                                    <template x-for="v in prod.variants" :key="v.id">
                                                        <button type="button" @click="addPosProductToOrder(prod, v)"
                                                                class="px-2 py-0.5 bg-gray-100 hover:bg-brand-50 hover:text-brand-700 rounded text-[11px] font-mono border border-gray-200 transition">
                                                            <span x-text="(v.sku || 'SKU') + ' · ৳' + (v.current_price || v.original_price)"></span>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="prod.product_type !== 'variable' || !prod.variants || prod.variants.length === 0">
                                            <div @click="addPosProductToOrder(prod)" class="flex items-center justify-between">
                                                <div>
                                                    <span class="font-bold text-gray-900" x-text="prod.title"></span>
                                                    <span class="block text-[10px] text-gray-400 font-mono" x-text="'SKU: ' + (prod.sku || 'N/A')"></span>
                                                </div>
                                                <span class="font-bold text-brand-600 font-mono" x-text="'৳' + (prod.current_price || prod.regular_price)"></span>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Cart Products Table -->
                        <div class="border border-gray-200 rounded-xl overflow-hidden bg-white flex-1 flex flex-col">
                            <div class="px-3.5 py-2 bg-gray-50 border-b border-gray-200 flex items-center justify-between text-xs font-bold text-gray-700">
                                <span>Order Items (<span x-text="orderForm.items.length"></span>)</span>
                                <span>Subtotal: <span class="font-mono text-gray-900" x-text="'৳' + orderSubtotal.toFixed(2)"></span></span>
                            </div>

                            <div class="p-2 space-y-2 max-h-60 overflow-y-auto flex-1">
                                <template x-if="orderForm.items.length === 0">
                                    <div class="py-8 text-center text-gray-400 text-xs">
                                        <i class="ph ph-shopping-bag text-3xl block mx-auto mb-1 opacity-50"></i>
                                        Cart is empty. Search products above or items will autofill from live cart.
                                    </div>
                                </template>

                                <template x-for="(item, idx) in orderForm.items" :key="item.cartId || idx">
                                    <div class="p-2.5 bg-gray-50/60 rounded-xl border border-gray-100 flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-white border border-gray-200 overflow-hidden shrink-0 flex items-center justify-center">
                                            <img x-show="item.image" :src="item.image" class="w-full h-full object-cover">
                                            <i x-show="!item.image" class="ph ph-package text-gray-400 text-base"></i>
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-semibold text-xs text-gray-900 truncate" x-text="item.name"></h4>
                                            <div class="flex items-center gap-2 text-[10px] text-gray-500 font-mono mt-0.5">
                                                <span x-show="item.variant_sku || item.product_sku" class="bg-gray-100 px-1 rounded text-gray-700" x-text="'SKU: ' + (item.variant_sku || item.product_sku)"></span>
                                                <span x-show="item.variantLabel" x-text="item.variantLabel"></span>
                                                <span class="text-brand-600 font-bold" x-text="'৳' + item.price"></span>
                                            </div>
                                        </div>

                                        <!-- Quantity Controls -->
                                        <div class="flex items-center border border-gray-200 rounded-lg bg-white shrink-0">
                                            <button type="button" @click="updateOrderItemQty(idx, -1)" class="w-6 h-6 flex items-center justify-center text-gray-500 hover:bg-gray-100 transition">-</button>
                                            <span class="w-7 text-center text-xs font-bold font-mono text-gray-800" x-text="item.qty"></span>
                                            <button type="button" @click="updateOrderItemQty(idx, 1)" class="w-6 h-6 flex items-center justify-center text-gray-500 hover:bg-gray-100 transition">+</button>
                                        </div>

                                        <!-- Total for item -->
                                        <div class="text-right shrink-0 w-16">
                                            <span class="text-xs font-bold font-mono text-gray-900" x-text="'৳' + (item.price * item.qty).toFixed(0)"></span>
                                        </div>

                                        <!-- Remove Item Button -->
                                        <button type="button" @click="removeOrderItem(idx)" class="text-gray-400 hover:text-rose-600 p-1 transition shrink-0 cursor-pointer">
                                            <i class="ph ph-trash text-sm"></i>
                                        </button>
                                    </div>
                                </template>
                            </div>

                            <!-- Summary Strip -->
                            <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 space-y-1 text-xs">
                                <div class="flex justify-between text-gray-500">
                                    <span>Subtotal</span>
                                    <span class="font-mono text-gray-900" x-text="'৳' + orderSubtotal.toFixed(2)"></span>
                                </div>
                                <div class="flex justify-between text-gray-500">
                                    <span>Delivery Charge</span>
                                    <span class="font-mono text-gray-900" x-text="'+ ৳' + parseFloat(orderForm.delivery_charge || 0).toFixed(2)"></span>
                                </div>
                                <div class="flex justify-between text-gray-500" x-show="parseFloat(orderForm.discount || 0) > 0">
                                    <span>Discount</span>
                                    <span class="font-mono text-emerald-600" x-text="'- ৳' + parseFloat(orderForm.discount || 0).toFixed(2)"></span>
                                </div>
                                <div class="flex justify-between text-sm font-bold text-gray-900 pt-1 border-t border-gray-200">
                                    <span>Total Payable</span>
                                    <span class="font-mono text-brand-600 text-base" x-text="'৳' + orderTotal.toFixed(2)"></span>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-3.5 border-t border-gray-100 bg-gray-50 flex items-center justify-between">
                <div class="text-xs text-gray-500">
                    <span x-show="orderForm.user_id" class="font-semibold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded">Registered Customer</span>
                    <span x-show="!orderForm.user_id" class="text-gray-400">Guest Customer Session</span>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" @click="isOrderModalOpen = false" class="px-4 py-2 bg-white border border-gray-200 hover:bg-gray-100 text-gray-700 text-xs font-semibold rounded-xl transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="button" @click="submitCustomerOrder()" :disabled="isSubmittingOrder || orderForm.items.length === 0"
                            class="px-5 py-2 bg-brand-600 hover:bg-brand-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white rounded-xl text-xs font-bold shadow-md shadow-brand-500/20 transition flex items-center gap-1.5 cursor-pointer">
                        <i x-show="isSubmittingOrder" class="ph ph-spinner animate-spin text-sm"></i>
                        <i x-show="!isSubmittingOrder" class="ph-bold ph-check text-sm"></i>
                        <span x-text="isSubmittingOrder ? 'Placing Order...' : 'Confirm & Place Order'"></span>
                    </button>
                </div>
            </div>

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

            // Context Menu State
            contextMenuOpen: false,
            contextX: 0,
            contextY: 0,
            contextVisitor: null,

            // Realtime Cart Modal State
            isCartModalOpen: false,
            isLoadingCart: false,
            cartData: null,

            // Order as Customer POS Modal State
            isOrderModalOpen: false,
            isSubmittingOrder: false,
            orderSuccessNotice: null,
            orderErrorNotice: null,
            posSearchQuery: '',
            isSearchingPos: false,
            posSuggestions: [],
            showPosSuggestions: false,
            selectedHistoryVersion: '',
            orderForm: {
                user_id: null,
                visitor_token: '',
                customer_name: '',
                customer_contact: '',
                shipping_address: '',
                order_source: 'website',
                delivery_charge: 80,
                discount: 0,
                notes: '',
                items: []
            },

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
                }, 2000);
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

                        // Auto-refresh active cart & checkout draft modal if currently open
                        if (this.selectedVisitor && this.isCartModalOpen && !this.isLoadingCart) {
                            this.refreshCartData(this.selectedVisitor.vid);
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
                this.closeContextMenu();
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

            openContextMenu(e, v) {
                this.contextVisitor = v;
                this.selectedVisitor = v;
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
                        console.error('Failed to load realtime cart:', err);
                    })
                    .finally(() => {
                        this.isLoadingCart = false;
                    });
            },

            refreshCartData(vid) {
                if (!vid) return;
                const url = '{{ url('admin/dashboard/live-monitor/cart') }}/' + encodeURIComponent(vid);
                fetch(url)
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.success) {
                            this.cartData = data;
                        }
                    })
                    .catch(err => {});
            },

            triggerSendOffer() {
                const v = this.contextVisitor || this.selectedVisitor;
                this.closeContextMenu();
                if (v) this.openOfferModal(v);
            },

            triggerLiveJourney() {
                const v = this.contextVisitor || this.selectedVisitor;
                this.closeContextMenu();
                if (v) this.openJourneyModal(v);
            },

            openFootprintHistory() {
                const v = this.contextVisitor || this.selectedVisitor;
                this.closeContextMenu();
                if (v && v.vid) {
                    window.open('{{ route('admin.visitors.footprint') }}?search=' + encodeURIComponent(v.vid), '_blank');
                }
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

                // Pre-fill with latest known details
                const latestName = this.cartData?.customer_name || v.name || v.last_seen_as || '';
                const latestPhone = this.cartData?.customer_phone || v.phone || '';
                const latestAddress = this.cartData?.customer_address || v.address || '';
                const userId = this.cartData?.user_id || v.user_id || null;

                // Populate Cart Items if already loaded, else fetch
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
                    user_id: userId,
                    visitor_token: v.vid,
                    customer_name: latestName,
                    customer_contact: latestPhone,
                    shipping_address: latestAddress,
                    order_source: 'website',
                    delivery_charge: 80,
                    discount: 0,
                    notes: '',
                    items: initialItems
                };

                this.selectedHistoryVersion = 'latest';
                this.isOrderModalOpen = true;

                // If cartData wasn't loaded or might be fresh, fetch to ensure latest SKU & items
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
                    items: this.orderForm.items,
                    user_id: this.orderForm.user_id || null,
                    visitor_token: this.orderForm.visitor_token || null,
                    _token: '{{ csrf_token() }}'
                };

                try {
                    const res = await fetch('{{ route('admin.orders.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });
                    const data = await res.json();
                    if (data && data.success) {
                        this.orderSuccessNotice = 'Order created successfully! Redirecting...';
                        setTimeout(() => {
                            if (data.redirect) {
                                window.open(data.redirect, '_blank');
                            }
                            this.isOrderModalOpen = false;
                        }, 1200);
                    } else {
                        this.orderErrorNotice = (data && data.message) ? data.message : 'Failed to create order.';
                    }
                } catch (e) {
                    this.orderErrorNotice = 'An error occurred while creating order: ' + e.message;
                } finally {
                    this.isSubmittingOrder = false;
                }
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
