@extends('layouts.admin')

@php
    $activeTab = $activeTab ?? 'dashboard';
    $initialTodayVisitors = 0;
    if ($activeTab === 'dashboard') {
        $todayStr = date('Y-m-d');
        $possibleDirs = [
            '/dev/shm/ariomart_visitors',
            storage_path('framework/cache/visitors')
        ];
        foreach ($possibleDirs as $pDir) {
            $f = $pDir . '/daily_' . $todayStr . '.json';
            if (file_exists($f)) {
                $c = @file_get_contents($f);
                $d = json_decode($c, true);
                if (is_array($d) && isset($d['unique'])) {
                    $initialTodayVisitors = (int) $d['unique'];
                    break;
                }
            }
        }
    }
@endphp

@section('header_title')
    @if($activeTab === 'dashboard') Overview
    @elseif($activeTab === 'all-orders') All Orders
    @elseif($activeTab === 'products') Products
    @elseif($activeTab === 'customers') Customers
    @endif
@endsection

@section('content')
    @if($activeTab === 'dashboard')
        <div x-data="dashboardAnalytics()">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">Dashboard Overview</h1>
                <p class="text-sm text-gray-500 mt-1">Welcome back, Super Admin. Here's what's happening today.</p>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5 mb-8">
                <!-- Live Active Online -->
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 relative overflow-hidden">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl relative">
                            <i class="ph ph-broadcast"></i>
                            <span class="absolute top-2 right-2 flex h-2.5 w-2.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                            </span>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Live Active</p>
                            <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2 mt-0.5 select-none">
                                <!-- YouTube / Clock Style Rolling Number Reel -->
                                <div class="odometer-reel flex items-center font-mono font-black text-xl tracking-tight text-gray-900">
                                    <template x-for="(d, idx) in digits" :key="idx">
                                        <div class="relative overflow-hidden inline-block text-center" style="height: 24px; width: 14px; line-height: 24px;">
                                            <div class="flex flex-col"
                                                 :class="animated ? 'transition-transform duration-700 ease-[cubic-bezier(0.25,1,0.5,1)]' : ''"
                                                 :style="'transform: translateY(-' + (parseInt(d) * 24) + 'px)'">
                                                <span class="h-[24px] leading-[24px] block">0</span>
                                                <span class="h-[24px] leading-[24px] block">1</span>
                                                <span class="h-[24px] leading-[24px] block">2</span>
                                                <span class="h-[24px] leading-[24px] block">3</span>
                                                <span class="h-[24px] leading-[24px] block">4</span>
                                                <span class="h-[24px] leading-[24px] block">5</span>
                                                <span class="h-[24px] leading-[24px] block">6</span>
                                                <span class="h-[24px] leading-[24px] block">7</span>
                                                <span class="h-[24px] leading-[24px] block">8</span>
                                                <span class="h-[24px] leading-[24px] block">9</span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">Online</span>
                            </h3>
                        </div>
                    </div>
                </div>

                <!-- Total Revenue -->
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-2xl">
                            <i class="ph ph-currency-circle-dollar"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Total Revenue</p>
                            <h3 class="text-xl font-bold text-gray-900">৳24,500</h3>
                        </div>
                    </div>
                </div>

                <!-- Total Orders -->
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center text-2xl">
                            <i class="ph ph-shopping-bag"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Total Orders</p>
                            <h3 class="text-xl font-bold text-gray-900">142</h3>
                        </div>
                    </div>
                </div>

                <!-- Total Customers -->
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-2xl">
                            <i class="ph ph-users"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Total Customers</p>
                            <h3 class="text-xl font-bold text-gray-900">89</h3>
                        </div>
                    </div>
                </div>

                <!-- Today's Visitors -->
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-2xl">
                            <i class="ph ph-chart-line-up"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Today's Visitors</p>
                            <h3 class="text-xl font-bold text-gray-900 tabular-nums" x-text="todayUnique">{{ $initialTodayVisitors }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Real-Time Live Shoppers & Visitors Monitor -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-8 overflow-hidden">
                <!-- Header Bar -->
                <div class="px-6 py-5 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gradient-to-r from-white via-emerald-50/20 to-white">
                    <div class="flex items-center gap-3.5">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl relative shrink-0">
                            <i class="ph ph-broadcast"></i>
                            <span class="absolute top-1.5 right-1.5 flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="font-bold text-gray-900 text-base">Live Shoppers Monitor</h2>
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span x-text="liveVisitors.length + ' Active Now'"></span>
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">Real-time shopper activity, visited pages & recognized profiles (Auto-refreshes every 4s)</p>
                        </div>
                    </div>

                    <!-- Filters & Quick Actions -->
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="bg-gray-100 p-1 rounded-xl flex items-center text-xs font-medium">
                            <button type="button" @click="activeFilter = 'all'"
                                class="px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1.5"
                                :class="activeFilter === 'all' ? 'bg-white text-gray-900 shadow-sm font-semibold' : 'text-gray-600 hover:text-gray-900'">
                                <span>All</span>
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

                        <button type="button" @click="updateStats()" title="Refresh live feed"
                            class="p-2 text-gray-500 hover:text-brand-600 hover:bg-gray-100 rounded-xl transition-colors">
                            <i class="ph ph-arrows-clockwise text-lg" :class="isRefreshing ? 'animate-spin text-brand-600' : ''"></i>
                        </button>
                    </div>
                </div>

                <!-- Shoppers Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-gray-500 font-medium border-b border-gray-100 text-xs uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-3.5">Shopper / Identity</th>
                                <th class="px-6 py-3.5">Current Page & Title</th>
                                <th class="px-6 py-3.5">Device</th>
                                <th class="px-6 py-3.5">Time on Site</th>
                                <th class="px-6 py-3.5 text-center">Status</th>
                                <th class="px-6 py-3.5 text-right">Visitor Journey</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="v in filteredVisitors" :key="v.vid">
                                <tr class="hover:bg-gray-50/80 transition-colors">
                                    <!-- Shopper / Identity -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <!-- Avatar -->
                                            <div class="relative shrink-0">
                                                <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs shadow-sm"
                                                    :class="{
                                                        'bg-emerald-100 text-emerald-700 border border-emerald-200': v.is_logged_in,
                                                        'bg-amber-100 text-amber-800 border border-amber-200': (!v.is_logged_in && (v.name || v.last_seen_as)),
                                                        'bg-gray-100 text-gray-600 border border-gray-200': (!v.is_logged_in && !v.name && !v.last_seen_as)
                                                    }">
                                                    <template x-if="v.name || v.last_seen_as">
                                                        <span x-text="(v.name || v.last_seen_as).substring(0, 2).toUpperCase()"></span>
                                                    </template>
                                                    <template x-if="!v.name && !v.last_seen_as">
                                                        <i class="ph ph-user text-base"></i>
                                                    </template>
                                                </div>
                                                <!-- Green Live Dot -->
                                                <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full border-2 border-white"
                                                    :class="v.seconds_ago <= 15 ? 'bg-emerald-500 animate-pulse' : 'bg-gray-400'"></span>
                                            </div>

                                            <!-- Details -->
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <template x-if="v.is_logged_in">
                                                        <span class="font-bold text-gray-900 truncate" x-text="v.name"></span>
                                                    </template>
                                                    <template x-if="!v.is_logged_in && (v.name || v.last_seen_as)">
                                                        <span class="font-bold text-gray-900 truncate" x-text="v.last_seen_as || v.name"></span>
                                                    </template>
                                                    <template x-if="!v.is_logged_in && !v.name && !v.last_seen_as">
                                                        <span class="font-semibold text-gray-700">Guest Visitor</span>
                                                    </template>

                                                    <!-- Identity Badge -->
                                                    <template x-if="v.is_logged_in">
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                            <i class="ph ph-check-circle"></i> Logged In
                                                        </span>
                                                    </template>
                                                    <template x-if="!v.is_logged_in && (v.name || v.last_seen_as)">
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200" title="This customer previously logged in or placed orders on this browser">
                                                            <i class="ph ph-clock-counter-clockwise"></i> Logged Out (Previous Customer)
                                                        </span>
                                                    </template>
                                                    <template x-if="!v.is_logged_in && !v.name && !v.last_seen_as">
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-600">
                                                            Guest
                                                        </span>
                                                    </template>
                                                </div>

                                                <div class="text-xs text-gray-500 mt-0.5 flex items-center gap-2 truncate">
                                                    <template x-if="v.phone">
                                                        <span class="font-mono text-gray-700 font-medium" x-text="v.phone"></span>
                                                    </template>
                                                    <template x-if="v.phone && v.ip">
                                                        <span>•</span>
                                                    </template>
                                                    <template x-if="v.ip">
                                                        <span class="font-mono text-[11px] text-gray-400" x-text="'IP: ' + v.ip"></span>
                                                    </template>
                                                    <template x-if="!v.phone && !v.ip">
                                                        <span class="font-mono text-[11px] text-gray-400" x-text="'ID: ' + v.vid.substring(0, 10)"></span>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Current Page & Title -->
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col gap-1 max-w-md">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <!-- Page Type Tag -->
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold border"
                                                    :class="getPageBadge(v.url).bg">
                                                    <i class="ph" :class="getPageBadge(v.url).icon"></i>
                                                    <span x-text="getPageBadge(v.url).label"></span>
                                                </span>

                                                <!-- Clickable Page Link -->
                                                <a :href="v.url" target="_blank"
                                                    class="text-xs font-mono font-medium text-brand-600 hover:text-brand-700 hover:underline inline-flex items-center gap-1 truncate max-w-[220px]">
                                                    <span x-text="v.url"></span>
                                                    <i class="ph ph-arrow-square-out text-xs shrink-0"></i>
                                                </a>
                                            </div>

                                            <!-- Document Title -->
                                            <p class="text-xs text-gray-500 truncate" x-text="v.title || 'Browsing Store'"></p>
                                        </div>
                                    </td>

                                    <!-- Device -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2 text-gray-700 text-xs font-medium">
                                            <template x-if="v.device === 'Mobile'">
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700">
                                                    <i class="ph ph-device-mobile text-base text-gray-500"></i>
                                                    Mobile
                                                </span>
                                            </template>
                                            <template x-if="v.device !== 'Mobile'">
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700">
                                                    <i class="ph ph-desktop text-base text-gray-500"></i>
                                                    Desktop
                                                </span>
                                            </template>
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

                                    <!-- Visitor Journey Action Button -->
                                    <td class="px-6 py-4 text-right">
                                        <button type="button" @click="openJourneyModal(v)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-brand-50 hover:bg-brand-100 text-brand-700 font-semibold text-xs border border-brand-200 shadow-2xs hover:shadow-xs transition-all focus:outline-none">
                                            <i class="ph ph-footprints text-sm text-brand-600"></i>
                                            <span>Timeline</span>
                                            <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-brand-200/80 text-brand-900 font-mono" x-text="v.history ? v.history.length : 1"></span>
                                        </button>
                                    </td>
                                </tr>
                            </template>

                            <!-- Empty State -->
                            <tr x-show="filteredVisitors.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl mb-3 relative">
                                            <i class="ph ph-broadcast"></i>
                                            <span class="animate-ping absolute inline-flex h-8 w-8 rounded-full bg-emerald-400 opacity-20"></span>
                                        </div>
                                        <h4 class="text-sm font-bold text-gray-900">No active shoppers in this view</h4>
                                        <p class="text-xs text-gray-500 mt-1">Watching live store traffic. When a customer or guest visits any page, they appear here instantly.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Visitor Journey Timeline Modal -->
            <div x-show="journeyModalOpen"
                 x-transition:enter="transition ease-out duration-250"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-50 overflow-y-auto"
                 style="display: none;"
                 @keydown.escape.window="closeJourneyModal()">
                 
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-xs transition-opacity" @click="closeJourneyModal()"></div>

                <!-- Modal Dialog -->
                <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                    <div x-show="journeyModalOpen"
                         x-transition:enter="transition ease-out duration-250"
                         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-2xl border border-gray-100">
                         
                        <template x-if="selectedVisitor">
                            <div>
                                <!-- Header -->
                                <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-gray-50 via-white to-gray-50 flex items-center justify-between">
                                    <div class="flex items-center gap-3.5">
                                        <div class="w-11 h-11 rounded-2xl flex items-center justify-center font-bold text-sm shadow-sm relative shrink-0"
                                            :class="{
                                                'bg-emerald-100 text-emerald-700 border border-emerald-200': selectedVisitor.is_logged_in,
                                                'bg-amber-100 text-amber-800 border border-amber-200': (!selectedVisitor.is_logged_in && (selectedVisitor.name || selectedVisitor.last_seen_as)),
                                                'bg-gray-100 text-gray-600 border border-gray-200': (!selectedVisitor.is_logged_in && !selectedVisitor.name && !selectedVisitor.last_seen_as)
                                            }">
                                            <template x-if="selectedVisitor.name || selectedVisitor.last_seen_as">
                                                <span x-text="(selectedVisitor.name || selectedVisitor.last_seen_as).substring(0, 2).toUpperCase()"></span>
                                            </template>
                                            <template x-if="!selectedVisitor.name && !selectedVisitor.last_seen_as">
                                                <i class="ph ph-user text-lg"></i>
                                            </template>
                                            <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full border-2 border-white"
                                                :class="selectedVisitor.seconds_ago <= 15 ? 'bg-emerald-500 animate-pulse' : 'bg-gray-400'"></span>
                                        </div>

                                        <div>
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <h3 class="font-bold text-gray-900 text-base" x-text="selectedVisitor.name || selectedVisitor.last_seen_as || 'Guest Shopper'"></h3>
                                                
                                                <template x-if="selectedVisitor.is_logged_in">
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                        <i class="ph ph-check-circle"></i> Logged In Member
                                                    </span>
                                                </template>
                                                <template x-if="!selectedVisitor.is_logged_in && (selectedVisitor.name || selectedVisitor.last_seen_as)">
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                                        <i class="ph ph-clock-counter-clockwise"></i> Logged Out (Previous Customer)
                                                    </span>
                                                </template>
                                                <template x-if="!selectedVisitor.is_logged_in && !selectedVisitor.name && !selectedVisitor.last_seen_as">
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-600">
                                                        Guest Shopper
                                                    </span>
                                                </template>
                                            </div>
                                            
                                            <p class="text-xs text-gray-500 mt-0.5 flex items-center gap-2">
                                                <template x-if="selectedVisitor.phone">
                                                    <span class="font-mono text-gray-700 font-medium" x-text="'📞 ' + selectedVisitor.phone"></span>
                                                </template>
                                                <template x-if="selectedVisitor.phone">
                                                    <span>•</span>
                                                </template>
                                                <span class="inline-flex items-center gap-1">
                                                    <i class="ph" :class="selectedVisitor.device === 'Mobile' ? 'ph-device-mobile' : 'ph-desktop'"></i>
                                                    <span x-text="selectedVisitor.device"></span>
                                                </span>
                                                <span>•</span>
                                                <span class="font-mono text-[11px]" x-text="'IP: ' + (selectedVisitor.ip || 'Local')"></span>
                                            </p>
                                        </div>
                                    </div>

                                    <button type="button" @click="closeJourneyModal()"
                                        class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 hover:text-gray-700 flex items-center justify-center transition-colors">
                                        <i class="ph ph-x text-base"></i>
                                    </button>
                                </div>

                                <!-- Summary Stats Bar -->
                                <div class="px-6 py-3 bg-gray-50/70 border-b border-gray-100 grid grid-cols-3 gap-3 text-center">
                                    <div class="bg-white p-2.5 rounded-xl border border-gray-100 shadow-2xs">
                                        <p class="text-[11px] text-gray-400 font-medium">Session Duration</p>
                                        <p class="text-xs font-bold text-gray-900 mt-0.5" x-text="formatDuration(selectedVisitor.duration)"></p>
                                    </div>
                                    <div class="bg-white p-2.5 rounded-xl border border-gray-100 shadow-2xs">
                                        <p class="text-[11px] text-gray-400 font-medium">Pages Visited</p>
                                        <p class="text-xs font-bold text-brand-600 mt-0.5" x-text="(selectedVisitor.history ? selectedVisitor.history.length : 1) + ' steps'"></p>
                                    </div>
                                    <div class="bg-white p-2.5 rounded-xl border border-gray-100 shadow-2xs">
                                        <p class="text-[11px] text-gray-400 font-medium">Current Status</p>
                                        <p class="text-xs font-bold mt-0.5 flex items-center justify-center gap-1"
                                            :class="selectedVisitor.seconds_ago <= 15 ? 'text-emerald-600' : 'text-gray-500'">
                                            <span class="w-1.5 h-1.5 rounded-full inline-block"
                                                :class="selectedVisitor.seconds_ago <= 15 ? 'bg-emerald-500 animate-ping' : 'bg-gray-400'"></span>
                                            <span x-text="selectedVisitor.seconds_ago <= 15 ? 'Active Now' : selectedVisitor.seconds_ago + 's ago'"></span>
                                        </p>
                                    </div>
                                </div>

                                <!-- Body / Vertical Journey Timeline -->
                                <div class="p-6 max-h-[58vh] overflow-y-auto">
                                    <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                                        <i class="ph ph-clock-counter-clockwise text-base text-brand-600"></i>
                                        Browsing Path & Navigation Timeline
                                    </h4>

                                    <div class="relative pl-6 space-y-5 before:content-[''] before:absolute before:left-2.5 before:top-3 before:bottom-3 before:w-0.5 before:bg-gray-200">
                                        <template x-for="(step, idx) in (selectedVisitor.history || [])" :key="idx">
                                            <div class="relative group">
                                                <!-- Timeline Node Circle -->
                                                <div class="absolute -left-6 top-1 w-5 h-5 rounded-full border-2 bg-white flex items-center justify-center text-[10px] font-bold"
                                                    :class="idx === (selectedVisitor.history.length - 1)
                                                        ? 'border-emerald-500 bg-emerald-50 text-emerald-700 shadow-sm ring-4 ring-emerald-100'
                                                        : 'border-brand-500 text-brand-700 bg-white'">
                                                    <template x-if="idx === (selectedVisitor.history.length - 1)">
                                                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                                                    </template>
                                                    <template x-if="idx !== (selectedVisitor.history.length - 1)">
                                                        <span x-text="idx + 1"></span>
                                                    </template>
                                                </div>

                                                <!-- Step Content Card -->
                                                <div class="rounded-xl border p-3.5 transition-all"
                                                    :class="idx === (selectedVisitor.history.length - 1)
                                                        ? 'bg-emerald-50/40 border-emerald-200 shadow-2xs'
                                                        : 'bg-white border-gray-100 hover:border-gray-200 hover:shadow-2xs'">
                                                    <div class="flex items-center justify-between gap-2 flex-wrap mb-1.5">
                                                        <div class="flex items-center gap-2">
                                                            <!-- Step Number Pill -->
                                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                                                :class="idx === (selectedVisitor.history.length - 1)
                                                                    ? 'bg-emerald-600 text-white'
                                                                    : 'bg-gray-100 text-gray-600'"
                                                                x-text="idx === (selectedVisitor.history.length - 1) ? 'Current Page (Active)' : 'Step ' + (idx + 1)">
                                                            </span>

                                                            <!-- Page Type Badge -->
                                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold border"
                                                                :class="getPageBadge(step.url).bg">
                                                                <i class="ph" :class="getPageBadge(step.url).icon"></i>
                                                                <span x-text="getPageBadge(step.url).label"></span>
                                                            </span>
                                                        </div>

                                                        <!-- Timestamp -->
                                                        <span class="text-[11px] font-mono text-gray-400 font-medium flex items-center gap-1">
                                                            <i class="ph ph-clock text-xs"></i>
                                                            <span x-text="step.time"></span>
                                                        </span>
                                                    </div>

                                                    <!-- Title -->
                                                    <p class="text-xs font-semibold text-gray-900 line-clamp-1" x-text="step.title || 'Browsing Storefront'"></p>

                                                    <!-- URL Link -->
                                                    <div class="mt-1">
                                                        <a :href="step.url" target="_blank"
                                                            class="text-xs font-mono font-medium text-brand-600 hover:text-brand-700 hover:underline inline-flex items-center gap-1">
                                                            <span x-text="step.url"></span>
                                                            <i class="ph ph-arrow-square-out text-xs"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- Footer -->
                                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                                    <span class="text-xs text-gray-500">
                                        Live visitor tracking timeline
                                    </span>
                                    <div class="flex items-center gap-2">
                                        <a :href="selectedVisitor.url" target="_blank"
                                            class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold flex items-center gap-1.5 transition-colors shadow-sm">
                                            <i class="ph ph-arrow-square-out text-sm"></i>
                                            <span>Open Shopper's Current Page</span>
                                        </a>
                                        <button type="button" @click="closeJourneyModal()"
                                            class="px-4 py-2 rounded-xl bg-white hover:bg-gray-100 text-gray-700 text-xs font-semibold border border-gray-200 transition-colors">
                                            Close
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Recent Orders List -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center">
                    <h2 class="font-bold text-gray-900">Recent Orders</h2>
                    <a href="{{ route('admin.orders') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">View All</a>
                </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-gray-500 font-medium border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4">Order ID</th>
                            <th class="px-6 py-4">Customer</th>
                            <th class="px-6 py-4">Date</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @for($i = 1; $i <= 5; $i++)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-900">#ORD-2026-0{{ $i }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 font-bold text-xs">U{{ $i }}</div>
                                    <span class="font-medium text-gray-700">User {{ $i }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-500">Oct 01, 2026</td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $i % 2 == 0 ? 'bg-amber-100 text-amber-700' : 'bg-brand-100 text-brand-700' }}">
                                    {{ $i % 2 == 0 ? 'Pending' : 'Delivered' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right font-medium text-gray-900">৳{{ $i * 500 }}</td>
                        </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>
        </div>
    @endif

    @if($activeTab === 'all-orders')
        <div x-data="adminOrdersComponent()" x-init="fetchOrders()" class="mb-8">
            <div class="flex flex-col md:flex-row md:justify-between md:items-end gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">All Orders</h1>
                    <p class="text-sm text-gray-500 mt-1">Manage and track all customer orders dynamically.</p>
                </div>
                <div class="flex gap-2">
                    <button class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50"><i class="ph ph-download-simple mr-1"></i> Export</button>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6 flex flex-wrap gap-4 items-end">
                <div class="w-full md:w-auto flex-1 min-w-[200px]">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Search (Order ID, Name, Phone)</label>
                    <div class="relative">
                        <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" x-model="filters.search" @input.debounce.500ms="fetchOrders(1)" class="w-full bg-gray-50 border border-gray-200 rounded-lg pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500" placeholder="Search orders...">
                    </div>
                </div>
                <div class="w-full md:w-auto min-w-[150px]">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                    <select x-model="filters.status" @change="fetchOrders(1)" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <option value="all">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="processing">Processing</option>
                        <option value="shipped">Shipped</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="returned">Returned</option>
                    </select>
                </div>
                <div class="w-full sm:w-auto min-w-[130px]">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Date From</label>
                    <input type="date" x-model="filters.date_from" @change="fetchOrders(1)" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <div class="w-full sm:w-auto min-w-[130px]">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Date To</label>
                    <input type="date" x-model="filters.date_to" @change="fetchOrders(1)" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <div class="w-full sm:w-auto">
                    <button @click="resetFilters()" class="w-full px-4 py-2 bg-gray-100 text-gray-600 border border-gray-200 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">Clear</button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 relative min-h-[300px]">
                <!-- Loader -->
                <div x-show="loading" class="absolute inset-0 bg-white/70 z-10 flex items-center justify-center rounded-2xl backdrop-blur-sm" style="display: none;">
                    <div class="flex flex-col items-center gap-2">
                        <i class="ph ph-spinner animate-spin text-3xl text-brand-600"></i>
                        <span class="text-sm font-medium text-gray-600">Loading orders...</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-gray-50 text-gray-500 font-medium border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-4 cursor-pointer hover:text-brand-600 transition-colors" @click="sortBy('order_id')">
                                    <div class="flex items-center gap-1">Order ID <i class="ph" :class="getSortIcon('order_id')"></i></div>
                                </th>
                                <th class="px-6 py-4">Customer</th>
                                <th class="px-6 py-4 cursor-pointer hover:text-brand-600 transition-colors" @click="sortBy('created_at')">
                                    <div class="flex items-center gap-1">Date <i class="ph" :class="getSortIcon('created_at')"></i></div>
                                </th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">Payment</th>
                                <th class="px-6 py-4">Source</th>
                                <th class="px-6 py-4">Items</th>
                                <th class="px-6 py-4 text-right cursor-pointer hover:text-brand-600 transition-colors" @click="sortBy('total')">
                                    <div class="flex items-center justify-end gap-1"><i class="ph" :class="getSortIcon('total')"></i> Total</div>
                                </th>
                                <th class="px-6 py-4 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="order in orders" :key="order.id">
                                <tr class="hover:bg-gray-50 transition-colors" :class="ctx.open && ctx.order && ctx.order.id === order.id ? 'bg-gray-50' : ''" @contextmenu.prevent="openContextMenu($event, order)">
                                    <td class="px-6 py-4 font-medium text-brand-600">
                                        <a :href="'/admin/dashboard/all-orders/' + order.order_id" x-text="order.order_id" class="hover:underline flex items-center gap-1"></a>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col items-start gap-1">
                                            <div class="flex items-center gap-1.5">
                                                <template x-if="order.user_id">
                                                    <a :href="'/admin/dashboard/customers/' + order.user_id" 
                                                       class="font-medium text-gray-900 hover:text-brand-600 hover:underline transition-colors cursor-pointer"
                                                       title="View Customer Details"
                                                       x-text="order.customer_name"></a>
                                                </template>
                                                <template x-if="!order.user_id">
                                                    <span class="font-medium text-gray-900" x-text="order.customer_name"></span>
                                                </template>

                                                <template x-if="order.user_id">
                                                    <a :href="'/admin/dashboard/customers/' + order.user_id" 
                                                       class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors"
                                                       title="View Customer Details"
                                                       x-text="order.customer_type"></a>
                                                </template>
                                                <template x-if="!order.user_id">
                                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-gray-100 text-gray-600"
                                                          x-text="order.customer_type"></span>
                                                </template>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-xs text-gray-500" x-text="order.customer_phone"></span>
                                                <span x-show="order.order_count > 1" style="display:none;" class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-50 text-amber-600 border border-amber-200" title="Previous orders">
                                                    Returning (<span x-text="order.order_count"></span>)
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-gray-500" x-text="order.date"></td>
                                    <td class="px-6 py-4">
                                        <span class="px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider" 
                                              :class="{
                                                  'bg-amber-50 text-amber-600': order.status === 'pending',
                                                  'bg-blue-50 text-blue-600': order.status === 'confirmed',
                                                  'bg-indigo-50 text-indigo-600': order.status === 'processing',
                                                  'bg-purple-50 text-purple-600': order.status === 'shipped',
                                                  'bg-emerald-50 text-emerald-600': order.status === 'delivered',
                                                  'bg-red-50 text-red-600': order.status === 'cancelled' || order.status === 'returned',
                                              }"
                                              x-text="order.status">
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 rounded text-[11px] font-semibold uppercase tracking-wider border"
                                              :class="{
                                                  'bg-green-50 text-green-600 border-green-200': order.payment_status === 'paid',
                                                  'bg-gray-50 text-gray-500 border-gray-200': order.payment_status === 'pending',
                                                  'bg-red-50 text-red-600 border-red-200': order.payment_status === 'failed',
                                                  'bg-orange-50 text-orange-600 border-orange-200': order.payment_status === 'refunded',
                                              }"
                                              x-text="order.payment_status">
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 rounded text-[11px] font-semibold uppercase tracking-wider bg-gray-100 text-gray-700" x-text="order.source"></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="text-gray-900 font-medium"><span x-text="order.items_count"></span> <span class="text-gray-500 font-normal">items</span></span>
                                            <span class="text-xs text-gray-400 mt-0.5">Qty: <span x-text="order.total_quantity"></span></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex flex-col items-end">
                                            <span class="font-bold text-gray-900">৳<span x-text="order.total"></span></span>
                                            <span class="text-[11px] text-green-600 font-medium" x-show="parseFloat(String(order.discount).replace(/,/g, '')) > 0">
                                                Discount: ৳<span x-text="order.discount"></span>
                                                <template x-if="order.coupon">
                                                    <span class="text-[10px] text-emerald-700 bg-emerald-50 border border-emerald-200 px-1 py-0.5 rounded font-mono font-bold ml-0.5" x-text="order.coupon"></span>
                                                </template>
                                            </span>
                                            <span class="text-[11px] text-gray-400" x-show="parseFloat(String(order.discount).replace(/,/g, '')) == 0">Discount: ৳0</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center relative" x-data="{ openMenu: false }">
                                        <button @click.outside="openMenu = false" @click="openMenu = !openMenu" class="p-2 hover:bg-gray-200 rounded-full text-gray-600 transition-colors focus:outline-none">
                                            <i class="ph ph-dots-three-outline-vertical text-xl"></i>
                                        </button>
                                        
                                        <!-- Dropdown -->
                                        <div x-show="openMenu" 
                                             x-transition.opacity.duration.200ms
                                             class="absolute right-10 top-10 w-40 bg-white border border-gray-100 rounded-xl shadow-lg z-20 py-1"
                                             style="display: none;">
                                            <a :href="'/admin/dashboard/all-orders/' + order.order_id" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-brand-600">
                                                <i class="ph ph-eye text-lg"></i> View Details
                                            </a>
                                            <button @click="openMenu = false; /* editStatus function here */" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-amber-600">
                                                <i class="ph ph-pencil-simple text-lg"></i> Edit Status
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="orders.length === 0 && !loading" style="display: none;">
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <i class="ph ph-package text-4xl text-gray-300 mb-2"></i>
                                        <p>No orders found matching your criteria.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Row Context Menu -->
                <div x-show="ctx.open"
                     x-transition.opacity.duration.100ms
                     @click.outside="closeContextMenu()"
                     @keydown.escape.window="closeContextMenu()"
                     @scroll.window="closeContextMenu()"
                     @resize.window="closeContextMenu()"
                     x-ref="ctxMenu"
                     :style="`top: ${ctx.y}px; left: ${ctx.x}px;`"
                     class="fixed w-48 bg-white border border-gray-200 ring-1 ring-black/5 rounded-xl shadow-xl z-[90] py-1"
                     role="menu">
                    <a :href="ctx.order ? '/admin/dashboard/all-orders/' + ctx.order.order_id : '#'" role="menuitem" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-brand-600 cursor-pointer">
                        <i class="ph ph-eye text-lg"></i> View Details
                    </a>
                    <a :href="ctx.order ? '/admin/dashboard/all-orders/' + ctx.order.order_id : '#'" role="menuitem" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-amber-600 cursor-pointer">
                        <i class="ph ph-pencil-simple text-lg"></i> Edit Status
                    </a>
                </div>
                
                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4" x-show="pagination.total > 0" style="display: none;">
                    <p class="text-sm text-gray-500">
                        Showing <span class="font-medium" x-text="pagination.from"></span> to <span class="font-medium" x-text="pagination.to"></span> of <span class="font-medium" x-text="pagination.total"></span> results
                    </p>
                    <div class="flex gap-1">
                        <button @click="if(pagination.current_page > 1) fetchOrders(pagination.current_page - 1)" 
                                :disabled="pagination.current_page <= 1"
                                class="w-8 h-8 flex items-center justify-center rounded-lg border text-sm transition-colors"
                                :class="pagination.current_page <= 1 ? 'border-gray-100 text-gray-400 bg-gray-50 cursor-not-allowed' : 'border-gray-200 text-gray-700 hover:bg-gray-50'">
                            <i class="ph ph-caret-left"></i>
                        </button>
                        
                        <template x-for="page in getPageNumbers()" :key="page">
                            <button @click="if(page !== '...') fetchOrders(page)" 
                                    :disabled="page === '...'"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg border text-sm font-medium transition-colors"
                                    :class="page === pagination.current_page ? 'border-brand-600 bg-brand-600 text-white' : (page === '...' ? 'border-transparent text-gray-400' : 'border-gray-200 text-gray-700 hover:bg-gray-50')"
                                    x-text="page">
                            </button>
                        </template>

                        <button @click="if(pagination.current_page < pagination.last_page) fetchOrders(pagination.current_page + 1)" 
                                :disabled="pagination.current_page >= pagination.last_page"
                                class="w-8 h-8 flex items-center justify-center rounded-lg border text-sm transition-colors"
                                :class="pagination.current_page >= pagination.last_page ? 'border-gray-100 text-gray-400 bg-gray-50 cursor-not-allowed' : 'border-gray-200 text-gray-700 hover:bg-gray-50'">
                            <i class="ph ph-caret-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function adminOrdersComponent() {
                return {
                    orders: [],
                    loading: true,
                    filters: {
                        search: '',
                        status: 'all',
                        date_from: '',
                        date_to: '',
                        sort_field: 'created_at',
                        sort_direction: 'desc',
                    },
                    pagination: {
                        current_page: 1,
                        last_page: 1,
                        total: 0,
                        from: 0,
                        to: 0
                    },
                    ctx: { open: false, x: 0, y: 0, order: null },

                    openContextMenu(event, order) {
                        const menuWidth = 192;
                        const menuHeight = 130;
                        this.ctx.order = order;
                        this.ctx.x = Math.min(event.clientX, window.innerWidth - menuWidth - 8);
                        this.ctx.y = Math.min(event.clientY, window.innerHeight - menuHeight - 8);
                        this.ctx.open = true;
                    },

                    closeContextMenu() {
                        this.ctx.open = false;
                    },

                    
                    async fetchOrders(page = this.pagination.current_page) {
                        this.loading = true;
                        
                        try {
                            const params = new URLSearchParams({
                                page: page,
                                search: this.filters.search,
                                status: this.filters.status,
                                date_from: this.filters.date_from,
                                date_to: this.filters.date_to,
                                sort_field: this.filters.sort_field,
                                sort_direction: this.filters.sort_direction
                            });
                            
                            const response = await fetch('/admin/dashboard/api/orders?' + params.toString());
                            const json = await response.json();
                            
                            this.orders = json.data;
                            this.pagination = json.pagination;
                        } catch (error) {
                            console.error('Error fetching orders:', error);
                            window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Failed to load orders.', type: 'error' } }));
                        } finally {
                            this.loading = false;
                        }
                    },
                    
                    resetFilters() {
                        this.filters.search = '';
                        this.filters.status = 'all';
                        this.filters.date_from = '';
                        this.filters.date_to = '';
                        this.filters.sort_field = 'created_at';
                        this.filters.sort_direction = 'desc';
                        this.fetchOrders(1);
                    },
                    
                    sortBy(field) {
                        if (this.filters.sort_field === field) {
                            this.filters.sort_direction = this.filters.sort_direction === 'asc' ? 'desc' : 'asc';
                        } else {
                            this.filters.sort_field = field;
                            this.filters.sort_direction = 'desc';
                        }
                        this.fetchOrders(1);
                    },
                    
                    getSortIcon(field) {
                        if (this.filters.sort_field !== field) return 'ph-arrows-down-up text-gray-300';
                        return this.filters.sort_direction === 'asc' ? 'ph-arrow-up text-brand-600' : 'ph-arrow-down text-brand-600';
                    },
                    
                    getPageNumbers() {
                        const current = this.pagination.current_page;
                        const last = this.pagination.last_page;
                        
                        if (last <= 7) {
                            return Array.from({length: last}, (_, i) => i + 1);
                        }
                        
                        if (current <= 3) {
                            return [1, 2, 3, 4, '...', last];
                        }
                        
                        if (current >= last - 2) {
                            return [1, '...', last - 3, last - 2, last - 1, last];
                        }
                        
                        return [1, '...', current - 1, current, current + 1, '...', last];
                    }
                }
            }
        </script>
    @endif

    @if($activeTab === 'products')
        <div class="mb-8 flex justify-between items-end">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Products</h1>
                <p class="text-sm text-gray-500 mt-1">Manage your store products.</p>
            </div>
            <button class="px-4 py-2 bg-brand-600 rounded-lg text-sm font-medium text-white hover:bg-brand-700 flex items-center gap-2">
                <i class="ph ph-plus"></i> Add Product
            </button>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center text-gray-500">
            <i class="ph ph-package text-4xl mb-4 text-gray-300"></i>
            <p>Products module coming soon...</p>
        </div>
    @endif

    @if($activeTab === 'customers')
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Customers</h1>
            <p class="text-sm text-gray-500 mt-1">Manage your customers and their roles.</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center text-gray-500">
            <i class="ph ph-users text-4xl mb-4 text-gray-300"></i>
            <p>Customers module coming soon...</p>
        </div>
    @endif

@endsection

@push('scripts')
<script>
    function dashboardAnalytics() {
        return {
            liveCount: 0,
            todayUnique: {{ $initialTodayVisitors ?? 0 }},
            animated: false,
            liveVisitors: [],
            activeFilter: 'all',
            isRefreshing: false,

            get digits() {
                return String(this.liveCount || 0).split('');
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

            getPageBadge(url) {
                if (!url) return { label: 'Storefront', bg: 'bg-gray-100 text-gray-700 border-gray-200', icon: 'ph-house' };
                if (url.indexOf('/checkout') !== -1) return { label: 'In Checkout', bg: 'bg-rose-50 text-rose-700 border-rose-200', icon: 'ph-shopping-bag' };
                if (url.indexOf('/cart') !== -1) return { label: 'Viewing Cart', bg: 'bg-purple-50 text-purple-700 border-purple-200', icon: 'ph-shopping-cart' };
                if (url.indexOf('/product') !== -1) return { label: 'Viewing Product', bg: 'bg-blue-50 text-blue-700 border-blue-200', icon: 'ph-package' };
                if (url.indexOf('/shop') !== -1 || url.indexOf('/category') !== -1) return { label: 'Browsing Catalog', bg: 'bg-indigo-50 text-indigo-700 border-indigo-200', icon: 'ph-magnifying-glass' };
                return { label: 'Browsing Store', bg: 'bg-emerald-50 text-emerald-700 border-emerald-200', icon: 'ph-compass' };
            },

            formatDuration(sec) {
                sec = parseInt(sec) || 0;
                if (sec < 60) return sec + 's';
                const mins = Math.floor(sec / 60);
                const remSec = sec % 60;
                return mins + 'm ' + remSec + 's';
            },

            journeyModalOpen: false,
            selectedVisitor: null,

            openJourneyModal(visitor) {
                this.selectedVisitor = visitor;
                this.journeyModalOpen = true;
            },

            closeJourneyModal() {
                this.journeyModalOpen = false;
            },

            async updateStats() {
                try {
                    this.isRefreshing = true;
                    const res = await fetch("/visitor-heartbeat.php?stats=1");
                    if (res.ok) {
                        const data = await res.json();
                        const newLive = parseInt(data.live) || 0;
                        const newToday = (data.today && data.today.unique !== undefined) ? parseInt(data.today.unique) : 0;
                        
                        this.liveCount = newLive;
                        this.todayUnique = newToday;
                        this.liveVisitors = Array.isArray(data.visitors) ? data.visitors : [];

                        // Real-time update of modal if currently inspecting a visitor
                        if (this.journeyModalOpen && this.selectedVisitor) {
                            const updated = this.liveVisitors.find(v => v.vid === this.selectedVisitor.vid);
                            if (updated) {
                                this.selectedVisitor = updated;
                            }
                        }
                        
                        if (!this.animated) {
                            setTimeout(() => {
                                this.animated = true;
                            }, 100);
                        }
                    }
                } catch (e) {
                } finally {
                    setTimeout(() => {
                        this.isRefreshing = false;
                    }, 300);
                }
            },

            init() {
                this.updateStats();
                setInterval(() => {
                    this.updateStats();
                }, 4000);
            }
        }
    }
</script>
@endpush
