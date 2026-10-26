@extends('layouts.admin')

@section('header_title', 'Sales Analytics')

@section('content')
<div class="space-y-6" x-data="salesAnalyticsApp()">
    
    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Sales Analytics & Revenue Intelligence</h1>
            <p class="text-sm text-gray-500 mt-1">Real-time financial performance, product velocity, peak hour patterns, and customer purchase cohorts.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span>Live Sales Engine</span>
            </span>
        </div>
    </div>

    <!-- Quick Financial Summary KPI Grid (Single pass DB calculation) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Total Revenue -->
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 relative overflow-hidden">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center text-2xl shrink-0">
                    <i class="ph ph-currency-circle-dollar"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Total Net Revenue</p>
                    <h3 class="text-xl font-bold text-gray-900 mt-0.5">৳{{ number_format($kpi->total_revenue) }}</h3>
                    <p class="text-[11px] text-gray-400 mt-0.5">{{ number_format($kpi->total_orders) }} orders</p>
                </div>
            </div>
        </div>

        <!-- Today's Sales -->
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl shrink-0">
                    <i class="ph ph-trend-up"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Today's Sales</p>
                    <h3 class="text-xl font-bold text-emerald-700 mt-0.5">৳{{ number_format($kpi->today_revenue) }}</h3>
                    <p class="text-[11px] text-emerald-600 font-semibold mt-0.5">{{ number_format($kpi->today_orders) }} orders today</p>
                </div>
            </div>
        </div>

        <!-- This Month Sales -->
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-2xl shrink-0">
                    <i class="ph ph-calendar-check"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">This Month</p>
                    <h3 class="text-xl font-bold text-gray-900 mt-0.5">৳{{ number_format($kpi->month_revenue) }}</h3>
                    <p class="text-[11px] text-gray-400 mt-0.5">{{ number_format($kpi->month_orders) }} orders this month</p>
                </div>
            </div>
        </div>

        <!-- Average Order Value (AOV) -->
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-2xl shrink-0">
                    <i class="ph ph-receipt"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Average Order Value</p>
                    <h3 class="text-xl font-bold text-gray-900 mt-0.5">৳{{ number_format($kpi->avg_order_value) }}</h3>
                    <p class="text-[11px] text-purple-600 font-medium mt-0.5">Avg basket spend</p>
                </div>
            </div>
        </div>

        <!-- Delivery Success Rate -->
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-2xl shrink-0">
                    <i class="ph ph-truck"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Delivery Success Rate</p>
                    <h3 class="text-xl font-bold text-gray-900 mt-0.5">{{ $deliverySuccessRate }}%</h3>
                    <p class="text-[11px] text-gray-400 mt-0.5">{{ $kpi->delivered_orders }} delivered</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ROW 1: CHARTS (Annual 12-Month Sales & Monthly Date-by-Date Daily Sales) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- CARD 1: Annual 12-Month Sales Trend (Dual Axis: Revenue & Orders) -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100" x-data="annualTrendCard({{ $currentYear }})">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
                <div>
                    <h3 class="font-bold text-gray-900 text-base flex items-center gap-2">
                        <i class="ph ph-chart-bar text-brand-600 text-lg"></i>
                        <span>Annual Sales & Orders (12 Months)</span>
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">Monthly revenue volume (৳) and total order volume.</p>
                </div>
                <!-- Independent Card Filter: Year Picker -->
                <div class="flex items-center gap-2">
                    <label class="text-xs font-semibold text-gray-500">Year:</label>
                    <select x-model="selectedYear" @change="fetchData()" class="bg-gray-50 border border-gray-200 rounded-xl px-3 py-1.5 text-xs font-semibold text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500">
                        @for($y = (int)date('Y'); $y >= (int)date('Y') - 4; $y--)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>
            </div>

            <!-- Total Stats Banner -->
            <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 border border-gray-100 mb-4 text-xs">
                <div>
                    <span class="text-gray-500 font-medium">Selected Year Sales:</span>
                    <span class="font-bold text-brand-700 ml-1">৳<span x-text="formatNumber(totalRevenue)">0</span></span>
                </div>
                <div>
                    <span class="text-gray-500 font-medium">Total Orders:</span>
                    <span class="font-bold text-gray-900 ml-1" x-text="totalOrders">0</span>
                </div>
            </div>

            <!-- Canvas Container -->
            <div class="relative h-72">
                <div x-show="loading" class="absolute inset-0 bg-white/70 backdrop-blur-xs flex items-center justify-center z-10">
                    <i class="ph ph-spinner animate-spin text-2xl text-brand-600"></i>
                </div>
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>

        <!-- CARD 2: Monthly Date-by-Date Daily Sales Breakdown -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100" x-data="monthlyDailyCard('{{ $currentMonth }}')">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
                <div>
                    <h3 class="font-bold text-gray-900 text-base flex items-center gap-2">
                        <i class="ph ph-calendar-blank text-indigo-600 text-lg"></i>
                        <span>Daily Sales by Date (Monthly View)</span>
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">Which specific date produced how much sales in the chosen month.</p>
                </div>
                <!-- Independent Card Filter: Month Picker -->
                <div class="flex items-center gap-2">
                    <label class="text-xs font-semibold text-gray-500">Month:</label>
                    <input type="month" x-model="selectedMonth" @change="fetchData()" 
                           class="bg-gray-50 border border-gray-200 rounded-xl px-3 py-1.5 text-xs font-semibold text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
            </div>

            <!-- Month Total Summary -->
            <div class="flex items-center justify-between p-3 rounded-xl bg-indigo-50/50 border border-indigo-100/60 mb-4 text-xs">
                <div>
                    <span class="text-indigo-900 font-medium">Month Sales Total:</span>
                    <span class="font-bold text-indigo-700 ml-1">৳<span x-text="formatNumber(totalRevenue)">0</span></span>
                </div>
                <div>
                    <span class="text-indigo-900 font-medium">Orders in Month:</span>
                    <span class="font-bold text-indigo-950 ml-1" x-text="totalOrders">0</span>
                </div>
            </div>

            <!-- Canvas Container -->
            <div class="relative h-72">
                <div x-show="loading" class="absolute inset-0 bg-white/70 backdrop-blur-xs flex items-center justify-center z-10">
                    <i class="ph ph-spinner animate-spin text-2xl text-indigo-600"></i>
                </div>
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>
    </div>

    <!-- ROW 2: TIME PATTERNS (Daily Peak Hours & Weekday Heatmap) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- CARD 3: Daily Peak Hours (24 Hours Clock Pattern) -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100" x-data="peakHoursCard()">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
                <div>
                    <h3 class="font-bold text-gray-900 text-base flex items-center gap-2">
                        <i class="ph ph-clock-countdown text-amber-500 text-lg"></i>
                        <span>Daily Peak Sales Hours (00:00 - 23:00)</span>
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">What time of the day visitors buy the most products.</p>
                </div>
                <!-- Independent Card Filter -->
                <div class="inline-flex items-center p-1 bg-gray-50 border border-gray-200 rounded-xl text-xs">
                    <button @click="setFilter('today')" :class="filter === 'today' ? 'bg-white shadow-xs text-brand-600 font-bold' : 'text-gray-600 hover:text-gray-900'" class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer">Today</button>
                    <button @click="setFilter('week')" :class="filter === 'week' ? 'bg-white shadow-xs text-brand-600 font-bold' : 'text-gray-600 hover:text-gray-900'" class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer">Week</button>
                    <button @click="setFilter('month')" :class="filter === 'month' ? 'bg-white shadow-xs text-brand-600 font-bold' : 'text-gray-600 hover:text-gray-900'" class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer">Month</button>
                    <button @click="setFilter('all')" :class="filter === 'all' ? 'bg-white shadow-xs text-brand-600 font-bold' : 'text-gray-600 hover:text-gray-900'" class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer">All</button>
                </div>
            </div>

            <div class="relative h-64">
                <div x-show="loading" class="absolute inset-0 bg-white/70 backdrop-blur-xs flex items-center justify-center z-10">
                    <i class="ph ph-spinner animate-spin text-2xl text-amber-500"></i>
                </div>
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>

        <!-- CARD 4: Day of Week Sales Pattern (Sun - Sat) -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100" x-data="weekdaysCard()">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
                <div>
                    <h3 class="font-bold text-gray-900 text-base flex items-center gap-2">
                        <i class="ph ph-chart-pie text-emerald-600 text-lg"></i>
                        <span>Day of Week Sales Distribution</span>
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">Which days of the week generate the most revenue & orders.</p>
                </div>
                <!-- Independent Card Filter -->
                <div class="inline-flex items-center p-1 bg-gray-50 border border-gray-200 rounded-xl text-xs">
                    <button @click="setFilter('month')" :class="filter === 'month' ? 'bg-white shadow-xs text-brand-600 font-bold' : 'text-gray-600 hover:text-gray-900'" class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer">This Month</button>
                    <button @click="setFilter('year')" :class="filter === 'year' ? 'bg-white shadow-xs text-brand-600 font-bold' : 'text-gray-600 hover:text-gray-900'" class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer">This Year</button>
                    <button @click="setFilter('all')" :class="filter === 'all' ? 'bg-white shadow-xs text-brand-600 font-bold' : 'text-gray-600 hover:text-gray-900'" class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer">All Time</button>
                </div>
            </div>

            <div class="relative h-64">
                <div x-show="loading" class="absolute inset-0 bg-white/70 backdrop-blur-xs flex items-center justify-center z-10">
                    <i class="ph ph-spinner animate-spin text-2xl text-emerald-600"></i>
                </div>
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>
    </div>

    <!-- ROW 3: CATEGORIES & PAYMENT METHODS (Doughnut / Progress Bars) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- CARD 5: Top Sales Categories (2 Cols) -->
        <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm border border-gray-100" x-data="topCategoriesCard()">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
                <div>
                    <h3 class="font-bold text-gray-900 text-base flex items-center gap-2">
                        <i class="ph ph-stack text-purple-600 text-lg"></i>
                        <span>Top Revenue Categories</span>
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">Categories driving the highest sales share.</p>
                </div>
                <!-- Independent Card Filter -->
                <div class="inline-flex items-center p-1 bg-gray-50 border border-gray-200 rounded-xl text-xs">
                    <button @click="setFilter('week')" :class="filter === 'week' ? 'bg-white shadow-xs text-brand-600 font-bold' : 'text-gray-600 hover:text-gray-900'" class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer">Week</button>
                    <button @click="setFilter('month')" :class="filter === 'month' ? 'bg-white shadow-xs text-brand-600 font-bold' : 'text-gray-600 hover:text-gray-900'" class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer">Month</button>
                    <button @click="setFilter('year')" :class="filter === 'year' ? 'bg-white shadow-xs text-brand-600 font-bold' : 'text-gray-600 hover:text-gray-900'" class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer">Year</button>
                    <button @click="setFilter('all')" :class="filter === 'all' ? 'bg-white shadow-xs text-brand-600 font-bold' : 'text-gray-600 hover:text-gray-900'" class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer">All</button>
                </div>
            </div>

            <!-- Content Area -->
            <div class="space-y-4 min-h-[220px] relative">
                <div x-show="loading" class="absolute inset-0 bg-white/70 backdrop-blur-xs flex items-center justify-center z-10">
                    <i class="ph ph-spinner animate-spin text-2xl text-purple-600"></i>
                </div>
                
                <template x-for="cat in categories" :key="cat.id">
                    <div class="p-3 rounded-xl border border-gray-100 hover:border-purple-200 transition-colors bg-gray-50/50">
                        <div class="flex items-center justify-between mb-1.5 text-xs">
                            <span class="font-bold text-gray-900 flex items-center gap-2">
                                <i class="ph ph-folder text-purple-500"></i>
                                <span x-text="cat.name"></span>
                            </span>
                            <div class="flex items-center gap-3">
                                <span class="text-gray-500" x-text="cat.total_qty + ' units sold'"></span>
                                <span class="font-bold text-purple-700">৳<span x-text="cat.total_sales_formatted"></span></span>
                                <span class="font-mono font-bold text-gray-400" x-text="cat.percentage + '%'"></span>
                            </div>
                        </div>
                        <div class="w-full h-2 rounded-full bg-gray-200 overflow-hidden">
                            <div class="h-full bg-purple-600 rounded-full transition-all duration-500" :style="'width: ' + cat.percentage + '%'"></div>
                        </div>
                    </div>
                </template>

                <div x-show="!loading && categories.length === 0" class="py-12 text-center text-gray-400 text-xs">
                    No category sales data found for this period.
                </div>
            </div>
        </div>

        <!-- CARD 6: Payment Methods Doughnut Chart (1 Col) -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100" x-data="paymentMethodsCard()">
            <div class="flex items-center justify-between gap-3 mb-6">
                <div>
                    <h3 class="font-bold text-gray-900 text-base flex items-center gap-2">
                        <i class="ph ph-credit-card text-blue-600 text-lg"></i>
                        <span>Payment Channel</span>
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">COD vs Digital payment ratio.</p>
                </div>
                <!-- Independent Card Filter -->
                <select x-model="filter" @change="fetchData()" class="bg-gray-50 border border-gray-200 rounded-xl px-2.5 py-1 text-xs font-semibold text-gray-700">
                    <option value="month">Month</option>
                    <option value="year">Year</option>
                    <option value="all">All</option>
                </select>
            </div>

            <div class="relative h-60">
                <div x-show="loading" class="absolute inset-0 bg-white/70 backdrop-blur-xs flex items-center justify-center z-10">
                    <i class="ph ph-spinner animate-spin text-2xl text-blue-600"></i>
                </div>
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>
    </div>

    <!-- ROW 4: PRODUCT MOVEMENT VELOCITY (Top Selling vs Slow Moving Products) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- CARD 7: Top Selling & Fast Moving Products -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100" x-data="topProductsCard()">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
                <div>
                    <h3 class="font-bold text-gray-900 text-base flex items-center gap-2">
                        <i class="ph ph-fire text-rose-500 text-lg"></i>
                        <span>Fast-Moving & Top Selling Products</span>
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">Ranked by units sold and generated revenue.</p>
                </div>
                <!-- Independent Card Filter -->
                <div class="inline-flex items-center p-1 bg-gray-50 border border-gray-200 rounded-xl text-xs">
                    <button @click="setFilter('week')" :class="filter === 'week' ? 'bg-white shadow-xs text-brand-600 font-bold' : 'text-gray-600 hover:text-gray-900'" class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer">Week</button>
                    <button @click="setFilter('month')" :class="filter === 'month' ? 'bg-white shadow-xs text-brand-600 font-bold' : 'text-gray-600 hover:text-gray-900'" class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer">Month</button>
                    <button @click="setFilter('year')" :class="filter === 'year' ? 'bg-white shadow-xs text-brand-600 font-bold' : 'text-gray-600 hover:text-gray-900'" class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer">Year</button>
                    <button @click="setFilter('all')" :class="filter === 'all' ? 'bg-white shadow-xs text-brand-600 font-bold' : 'text-gray-600 hover:text-gray-900'" class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer">All</button>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto min-h-[220px] relative">
                <div x-show="loading" class="absolute inset-0 bg-white/70 backdrop-blur-xs flex items-center justify-center z-10">
                    <i class="ph ph-spinner animate-spin text-2xl text-rose-500"></i>
                </div>

                <table class="w-full text-left text-xs">
                    <thead class="bg-gray-50 text-gray-500 font-semibold border-b border-gray-100 uppercase text-[10px]">
                        <tr>
                            <th class="py-2.5 px-3">Product</th>
                            <th class="py-2.5 px-3 text-center">Units Sold</th>
                            <th class="py-2.5 px-3 text-right">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="p in products" :key="p.id">
                            <tr class="hover:bg-gray-50/60 transition-colors">
                                <td class="py-2.5 px-3">
                                    <div class="flex items-center gap-2.5">
                                        <img :src="getImageUrl(p.featured_image)" 
                                             class="w-8 h-8 rounded-lg object-cover border border-gray-100 shrink-0"
                                             onerror="this.src='/images/placeholder.svg'">
                                        <a :href="'/product/' + p.slug" target="_blank" 
                                           class="font-medium text-gray-900 hover:text-brand-600 transition-colors line-clamp-1 max-w-[200px]"
                                           x-text="p.title"></a>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700" 
                                          x-text="p.units_sold + ' pcs'"></span>
                                </td>
                                <td class="py-2.5 px-3 text-right font-bold text-gray-900">
                                    ৳<span x-text="Number(p.total_revenue).toLocaleString()"></span>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="!loading && products.length === 0">
                            <td colspan="3" class="py-8 text-center text-gray-400">No sold products in this range.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- CARD 8: Slow-Moving Products (Clearance Candidates) -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100" x-data="slowMovingCard()">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
                <div>
                    <h3 class="font-bold text-gray-900 text-base flex items-center gap-2">
                        <i class="ph ph-hourglass-medium text-amber-600 text-lg"></i>
                        <span>Slow-Moving Products (Stagnant Stock)</span>
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">Active catalog items with zero sales in the selected period.</p>
                </div>
                <!-- Independent Card Filter -->
                <div class="flex items-center gap-2">
                    <label class="text-xs font-semibold text-gray-500">Threshold:</label>
                    <select x-model="days" @change="fetchData()" class="bg-gray-50 border border-gray-200 rounded-xl px-2.5 py-1 text-xs font-semibold text-gray-800 focus:outline-none">
                        <option value="15">Last 15 Days</option>
                        <option value="30">Last 30 Days</option>
                        <option value="60">Last 60 Days</option>
                        <option value="90">Last 90 Days</option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto min-h-[220px] relative">
                <div x-show="loading" class="absolute inset-0 bg-white/70 backdrop-blur-xs flex items-center justify-center z-10">
                    <i class="ph ph-spinner animate-spin text-2xl text-amber-600"></i>
                </div>

                <table class="w-full text-left text-xs">
                    <thead class="bg-gray-50 text-gray-500 font-semibold border-b border-gray-100 uppercase text-[10px]">
                        <tr>
                            <th class="py-2.5 px-3">Product</th>
                            <th class="py-2.5 px-3">Category</th>
                            <th class="py-2.5 px-3 text-right">Price</th>
                            <th class="py-2.5 px-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="p in products" :key="p.id">
                            <tr class="hover:bg-gray-50/60 transition-colors">
                                <td class="py-2.5 px-3">
                                    <div class="flex items-center gap-2.5">
                                        <img :src="getImageUrl(p.featured_image)" 
                                             class="w-8 h-8 rounded-lg object-cover border border-gray-100 shrink-0"
                                             onerror="this.src='/images/placeholder.svg'">
                                        <a :href="'/product/' + p.slug" target="_blank" 
                                           class="font-medium text-gray-900 hover:text-brand-600 transition-colors line-clamp-1 max-w-[170px]"
                                           x-text="p.title"></a>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 text-gray-500" x-text="p.category_name || 'General'"></td>
                                <td class="py-2.5 px-3 text-right font-bold text-gray-900">৳<span x-text="Number(p.base_price).toLocaleString()"></span></td>
                                <td class="py-2.5 px-3 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                        Discount Suggestion
                                    </span>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="!loading && products.length === 0">
                            <td colspan="4" class="py-8 text-center text-gray-400">All products have recent sales activity!</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ROW 5: CUSTOMER SPENDING COHORTS (Top Purchasing VIP Customers) -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100" x-data="topCustomersCard()">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h3 class="font-bold text-gray-900 text-base flex items-center gap-2">
                    <i class="ph ph-crown text-amber-500 text-lg"></i>
                    <span>Top Purchasing Customers Leaderboard (VIP Spenders)</span>
                </h3>
                <p class="text-xs text-gray-500 mt-0.5">Highest spending clients by successful order value in the chosen window.</p>
            </div>
            <!-- Independent Card Filter (Week, Month, Year, All) -->
            <div class="inline-flex items-center p-1 bg-gray-50 border border-gray-200 rounded-xl text-xs">
                <button @click="setFilter('week')" :class="filter === 'week' ? 'bg-white shadow-xs text-brand-600 font-bold' : 'text-gray-600 hover:text-gray-900'" class="px-3 py-1.5 rounded-lg transition-colors cursor-pointer">This Week</button>
                <button @click="setFilter('month')" :class="filter === 'month' ? 'bg-white shadow-xs text-brand-600 font-bold' : 'text-gray-600 hover:text-gray-900'" class="px-3 py-1.5 rounded-lg transition-colors cursor-pointer">This Month</button>
                <button @click="setFilter('year')" :class="filter === 'year' ? 'bg-white shadow-xs text-brand-600 font-bold' : 'text-gray-600 hover:text-gray-900'" class="px-3 py-1.5 rounded-lg transition-colors cursor-pointer">This Year</button>
                <button @click="setFilter('all')" :class="filter === 'all' ? 'bg-white shadow-xs text-brand-600 font-bold' : 'text-gray-600 hover:text-gray-900'" class="px-3 py-1.5 rounded-lg transition-colors cursor-pointer">All Time</button>
            </div>
        </div>

        <!-- Leaderboard Table -->
        <div class="overflow-x-auto min-h-[220px] relative">
            <div x-show="loading" class="absolute inset-0 bg-white/70 backdrop-blur-xs flex items-center justify-center z-10">
                <i class="ph ph-spinner animate-spin text-2xl text-amber-500"></i>
            </div>

            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 text-gray-500 font-semibold border-b border-gray-100 uppercase text-[10px]">
                    <tr>
                        <th class="py-3 px-4">Rank & Customer</th>
                        <th class="py-3 px-4">Contact Phone</th>
                        <th class="py-3 px-4 text-center">Orders Count</th>
                        <th class="py-3 px-4 text-right">Total Spent</th>
                        <th class="py-3 px-4 text-right">Last Purchase</th>
                        <th class="py-3 px-4 text-center">VIP Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="(c, idx) in customers" :key="idx">
                        <tr class="hover:bg-gray-50/60 transition-colors">
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-3">
                                    <span class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-xs"
                                          :class="idx === 0 ? 'bg-amber-100 text-amber-800' : (idx === 1 ? 'bg-gray-200 text-gray-700' : (idx === 2 ? 'bg-amber-50 text-amber-700' : 'text-gray-400'))"
                                          x-text="'#' + (idx + 1)"></span>
                                    <div>
                                        <template x-if="c.user_id">
                                            <a :href="'/admin/dashboard/customers/' + c.user_id" 
                                               class="font-bold text-gray-900 hover:text-brand-600 transition-colors"
                                               x-text="c.customer_name"></a>
                                        </template>
                                        <template x-if="!c.user_id">
                                            <span class="font-bold text-gray-900" x-text="c.customer_name"></span>
                                        </template>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4 font-mono text-gray-600" x-text="c.customer_phone"></td>
                            <td class="py-3 px-4 text-center">
                                <span class="px-2.5 py-0.5 rounded-full font-bold bg-brand-50 text-brand-700" 
                                      x-text="c.order_count + ' orders'"></span>
                            </td>
                            <td class="py-3 px-4 text-right font-bold text-emerald-700 text-sm">
                                ৳<span x-text="Number(c.total_spent).toLocaleString()"></span>
                            </td>
                            <td class="py-3 px-4 text-right text-gray-500 font-mono text-[11px]" 
                                x-text="new Date(c.last_order_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })"></td>
                            <td class="py-3 px-4 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                      :class="c.total_spent > 15000 ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-gray-100 text-gray-600'">
                                    <span x-text="c.total_spent > 15000 ? 'Diamond VIP' : 'Gold Shopper'"></span>
                                </span>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="!loading && customers.length === 0">
                        <td colspan="6" class="py-8 text-center text-gray-400">No customer purchase data found in this period.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function salesAnalyticsApp() {
        return {
            init() {}
        };
    }

    // Helper: Number formatting
    function formatNumber(num) {
        return Number(num || 0).toLocaleString();
    }

    // Helper: Image URL Resolver
    function getImageUrl(image) {
        if (!image) return '/images/placeholder.svg';
        if (image.startsWith('http://') || image.startsWith('https://')) {
            return image;
        }
        if (image.startsWith('/storage/')) {
            return image;
        }
        return '/storage/' + image;
    }

    // 1. Annual Trend Card Component
    function annualTrendCard(initialYear) {
        return {
            selectedYear: initialYear,
            loading: false,
            chart: null,
            totalRevenue: 0,
            totalOrders: 0,
            async fetchData() {
                this.loading = true;
                try {
                    const res = await fetch(`/admin/dashboard/analytics/sales/data?card=annual_trend&year=${this.selectedYear}`);
                    const data = await res.json();
                    this.totalRevenue = data.total_year_revenue;
                    this.totalOrders = data.total_year_orders;
                    this.renderChart(data);
                } catch (e) {
                    console.error('Annual trend fetch error', e);
                } finally {
                    this.loading = false;
                }
            },
            renderChart(data) {
                if (this.chart) this.chart.destroy();
                const ctx = this.$refs.canvas.getContext('2d');
                this.chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                label: 'Revenue (৳)',
                                data: data.revenues,
                                backgroundColor: 'rgba(230, 81, 0, 0.85)',
                                borderRadius: 6,
                                yAxisID: 'yRevenue',
                                order: 2
                            },
                            {
                                label: 'Orders Count',
                                data: data.orders,
                                type: 'line',
                                borderColor: '#4F46E5',
                                backgroundColor: '#4F46E5',
                                borderWidth: 3,
                                tension: 0.35,
                                pointRadius: 4,
                                yAxisID: 'yOrders',
                                order: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } },
                            tooltip: {
                                callbacks: {
                                    label: function(c) {
                                        if (c.dataset.yAxisID === 'yRevenue') return ` Revenue: ৳${Number(c.raw).toLocaleString()}`;
                                        return ` Orders: ${c.raw}`;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: { grid: { display: false } },
                            yRevenue: {
                                type: 'linear',
                                position: 'left',
                                grid: { color: '#f3f4f6' },
                                ticks: {
                                    callback: v => '৳' + (v >= 1000 ? (v/1000) + 'k' : v)
                                }
                            },
                            yOrders: {
                                type: 'linear',
                                position: 'right',
                                grid: { display: false },
                                ticks: { precision: 0 }
                            }
                        }
                    }
                });
            },
            init() {
                this.$nextTick(() => this.fetchData());
            }
        };
    }

    // 2. Monthly Daily Breakdown Card
    function monthlyDailyCard(initialMonth) {
        return {
            selectedMonth: initialMonth,
            loading: false,
            chart: null,
            totalRevenue: 0,
            totalOrders: 0,
            async fetchData() {
                this.loading = true;
                try {
                    const res = await fetch(`/admin/dashboard/analytics/sales/data?card=monthly_daily&month=${this.selectedMonth}`);
                    const data = await res.json();
                    this.totalRevenue = data.month_total_revenue;
                    this.totalOrders = data.month_total_orders;
                    this.renderChart(data);
                } catch (e) {
                    console.error('Monthly daily fetch error', e);
                } finally {
                    this.loading = false;
                }
            },
            renderChart(data) {
                if (this.chart) this.chart.destroy();
                const ctx = this.$refs.canvas.getContext('2d');
                this.chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                label: 'Daily Revenue (৳)',
                                data: data.revenues,
                                borderColor: '#4F46E5',
                                backgroundColor: 'rgba(79, 70, 229, 0.12)',
                                fill: true,
                                tension: 0.3,
                                borderWidth: 2.5,
                                pointRadius: 3,
                                pointHoverRadius: 6
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: c => ` Daily Revenue: ৳${Number(c.raw).toLocaleString()}`
                                }
                            }
                        },
                        scales: {
                            x: { grid: { display: false }, ticks: { maxRotation: 45, minRotation: 45, font: { size: 9 } } },
                            y: {
                                grid: { color: '#f3f4f6' },
                                ticks: { callback: v => '৳' + (v >= 1000 ? (v/1000) + 'k' : v) }
                            }
                        }
                    }
                });
            },
            init() {
                this.$nextTick(() => this.fetchData());
            }
        };
    }

    // 3. Peak Hours Card
    function peakHoursCard() {
        return {
            filter: 'month',
            loading: false,
            chart: null,
            setFilter(f) {
                this.filter = f;
                this.fetchData();
            },
            async fetchData() {
                this.loading = true;
                try {
                    const res = await fetch(`/admin/dashboard/analytics/sales/data?card=peak_hours&filter=${this.filter}`);
                    const data = await res.json();
                    this.renderChart(data);
                } catch (e) {
                    console.error('Peak hours fetch error', e);
                } finally {
                    this.loading = false;
                }
            },
            renderChart(data) {
                if (this.chart) this.chart.destroy();
                const ctx = this.$refs.canvas.getContext('2d');
                this.chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Orders Count',
                            data: data.orders,
                            backgroundColor: '#F59E0B',
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    afterLabel: c => `Revenue: ৳${Number(data.revenues[c.dataIndex]).toLocaleString()}`
                                }
                            }
                        },
                        scales: {
                            x: { grid: { display: false }, ticks: { font: { size: 9 } } },
                            y: { grid: { color: '#f3f4f6' }, ticks: { precision: 0 } }
                        }
                    }
                });
            },
            init() {
                this.$nextTick(() => this.fetchData());
            }
        };
    }

    // 4. Weekdays Heatmap Card
    function weekdaysCard() {
        return {
            filter: 'month',
            loading: false,
            chart: null,
            setFilter(f) {
                this.filter = f;
                this.fetchData();
            },
            async fetchData() {
                this.loading = true;
                try {
                    const res = await fetch(`/admin/dashboard/analytics/sales/data?card=weekdays&filter=${this.filter}`);
                    const data = await res.json();
                    this.renderChart(data);
                } catch (e) {
                    console.error('Weekdays fetch error', e);
                } finally {
                    this.loading = false;
                }
            },
            renderChart(data) {
                if (this.chart) this.chart.destroy();
                const ctx = this.$refs.canvas.getContext('2d');
                this.chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Revenue (৳)',
                            data: data.revenues,
                            backgroundColor: '#10B981',
                            borderRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: c => ` Revenue: ৳${Number(c.raw).toLocaleString()}`,
                                    afterLabel: c => ` Orders: ${data.orders[c.dataIndex]}`
                                }
                            }
                        },
                        scales: {
                            x: { grid: { display: false } },
                            y: {
                                grid: { color: '#f3f4f6' },
                                ticks: { callback: v => '৳' + (v >= 1000 ? (v/1000) + 'k' : v) }
                            }
                        }
                    }
                });
            },
            init() {
                this.$nextTick(() => this.fetchData());
            }
        };
    }

    // 5. Top Categories Card
    function topCategoriesCard() {
        return {
            filter: 'month',
            loading: false,
            categories: [],
            setFilter(f) {
                this.filter = f;
                this.fetchData();
            },
            async fetchData() {
                this.loading = true;
                try {
                    const res = await fetch(`/admin/dashboard/analytics/sales/data?card=top_categories&filter=${this.filter}`);
                    const data = await res.json();
                    this.categories = data.categories || [];
                } catch (e) {
                    console.error('Top categories fetch error', e);
                } finally {
                    this.loading = false;
                }
            },
            init() {
                this.$nextTick(() => this.fetchData());
            }
        };
    }

    // 6. Payment Methods Doughnut Card
    function paymentMethodsCard() {
        return {
            filter: 'month',
            loading: false,
            chart: null,
            async fetchData() {
                this.loading = true;
                try {
                    const res = await fetch(`/admin/dashboard/analytics/sales/data?card=payment_methods&filter=${this.filter}`);
                    const data = await res.json();
                    this.renderChart(data);
                } catch (e) {
                    console.error('Payment methods fetch error', e);
                } finally {
                    this.loading = false;
                }
            },
            renderChart(data) {
                if (this.chart) this.chart.destroy();
                const ctx = this.$refs.canvas.getContext('2d');
                this.chart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: data.labels.length ? data.labels : ['No Data'],
                        datasets: [{
                            data: data.revenues.length ? data.revenues : [1],
                            backgroundColor: ['#2563EB', '#10B981', '#F59E0B', '#8B5CF6'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } }
                        },
                        cutout: '68%'
                    }
                });
            },
            init() {
                this.$nextTick(() => this.fetchData());
            }
        };
    }

    // 7. Top Selling Products Card
    function topProductsCard() {
        return {
            filter: 'month',
            loading: false,
            products: [],
            setFilter(f) {
                this.filter = f;
                this.fetchData();
            },
            async fetchData() {
                this.loading = true;
                try {
                    const res = await fetch(`/admin/dashboard/analytics/sales/data?card=top_products&filter=${this.filter}`);
                    const data = await res.json();
                    this.products = data.products || [];
                } catch (e) {
                    console.error('Top products fetch error', e);
                } finally {
                    this.loading = false;
                }
            },
            init() {
                this.$nextTick(() => this.fetchData());
            }
        };
    }

    // 8. Slow Moving Card
    function slowMovingCard() {
        return {
            days: 30,
            loading: false,
            products: [],
            async fetchData() {
                this.loading = true;
                try {
                    const res = await fetch(`/admin/dashboard/analytics/sales/data?card=slow_moving&days=${this.days}`);
                    const data = await res.json();
                    this.products = data.products || [];
                } catch (e) {
                    console.error('Slow moving fetch error', e);
                } finally {
                    this.loading = false;
                }
            },
            init() {
                this.$nextTick(() => this.fetchData());
            }
        };
    }

    // 9. Top Purchasing Customers Card
    function topCustomersCard() {
        return {
            filter: 'month',
            loading: false,
            customers: [],
            setFilter(f) {
                this.filter = f;
                this.fetchData();
            },
            async fetchData() {
                this.loading = true;
                try {
                    const res = await fetch(`/admin/dashboard/analytics/sales/data?card=top_customers&filter=${this.filter}`);
                    const data = await res.json();
                    this.customers = data.customers || [];
                } catch (e) {
                    console.error('Top customers fetch error', e);
                } finally {
                    this.loading = false;
                }
            },
            init() {
                this.$nextTick(() => this.fetchData());
            }
        };
    }
</script>
@endpush
@endsection
