@extends('layouts.admin')

@section('title', 'Order Trash (Super Admin)')

@section('content')
<div x-data="orderTrashComponent()" x-init="init()" class="space-y-6">

    <!-- Top Breadcrumb & Navigation -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.orders') }}"
               class="w-10 h-10 rounded-xl bg-white border border-gray-200 text-gray-600 hover:text-brand-600 hover:border-brand-200 hover:bg-brand-50 flex items-center justify-center transition shadow-2xs group"
               title="Back to All Orders">
                <i class="ph ph-arrow-left text-lg group-hover:-translate-x-0.5 transition-transform"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Order Trash</h1>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-700 border border-rose-200">
                        <i class="ph-bold ph-shield-check text-xs"></i>
                        Super Admin Only
                    </span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-700"
                          x-text="pagination.total + ' Trashed'">
                        {{ $trashedCount ?? 0 }} Trashed
                    </span>
                </div>
                <p class="text-xs text-gray-500 mt-0.5">
                    Soft-deleted customer orders. Hidden from store tracking, customer dashboards, and standard admin search.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.orders') }}"
               class="px-4 py-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 rounded-xl text-xs font-semibold shadow-2xs transition inline-flex items-center gap-1.5">
                <i class="ph ph-shopping-cart text-sm text-gray-500"></i>
                <span>Active Orders</span>
            </a>
            <button type="button"
                    @click="fetchTrashOrders(pagination.current_page)"
                    :disabled="loading"
                    class="p-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-600 rounded-xl text-sm shadow-2xs transition disabled:opacity-50 cursor-pointer"
                    title="Refresh List">
                <i class="ph ph-arrows-clockwise text-base" :class="{ 'animate-spin': loading }"></i>
            </button>
        </div>
    </div>

    <!-- Security Warning Banner -->
    <div class="bg-amber-50/80 border border-amber-200 rounded-2xl p-4 flex items-start gap-3.5">
        <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 text-lg">
            <i class="ph-bold ph-shield-warning"></i>
        </div>
        <div class="flex-1 min-w-0 text-xs text-amber-900">
            <p class="font-bold text-amber-950">Super Admin Security Notice</p>
            <p class="mt-0.5 text-amber-800 leading-relaxed">
                Orders here are soft-deleted. Customers and staff cannot find or view these orders anywhere on the frontend or backend.
                You can <strong class="text-emerald-800">Restore</strong> single or multiple orders back to active status, or <strong class="text-rose-800">Permanently Delete</strong> them. Permanent deletion completely wipes the orders, items, and audit logs from the database and requires solving a <strong>Math CAPTCHA</strong>.
            </p>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-wrap gap-3 items-end">
        <!-- Search -->
        <div class="flex-1 min-w-[240px]">
            <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1">Search Trashed Orders</label>
            <div class="relative">
                <i class="ph ph-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text"
                       x-model="filters.search"
                       @input.debounce.400ms="fetchTrashOrders(1)"
                       placeholder="Search by Order ID, Customer Name, Phone..."
                       class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-9 pr-8 py-2 text-xs text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition">
                <button type="button"
                        x-show="filters.search"
                        @click="filters.search = ''; fetchTrashOrders(1)"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 p-0.5">
                    <i class="ph ph-x text-xs"></i>
                </button>
            </div>
        </div>

        <!-- Original Status Filter -->
        <div class="w-full sm:w-auto min-w-[140px]">
            <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1">Original Status</label>
            <select x-model="filters.status"
                    @change="fetchTrashOrders(1)"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-xs text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500">
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

        <!-- Date From -->
        <div class="w-full sm:w-auto min-w-[130px]">
            <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1">Trashed From</label>
            <input type="date"
                   x-model="filters.date_from"
                   @change="fetchTrashOrders(1)"
                   class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-xs text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>

        <!-- Date To -->
        <div class="w-full sm:w-auto min-w-[130px]">
            <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1">Trashed To</label>
            <input type="date"
                   x-model="filters.date_to"
                   @change="fetchTrashOrders(1)"
                   class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-xs text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>

        <!-- Per Page Filter -->
        <div class="w-full sm:w-auto min-w-[120px]">
            <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1">Per Page</label>
            <select x-model="filters.per_page"
                    @change="fetchTrashOrders(1)"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-xs text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-500 font-medium">
                <option value="10">10 per page</option>
                <option value="25">25 per page</option>
                <option value="50">50 per page</option>
                <option value="100">100 per page</option>
            </select>
        </div>

        <!-- Reset Button -->
        <div class="w-full sm:w-auto">
            <button type="button"
                    @click="resetFilters()"
                    class="w-full px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-semibold transition flex items-center justify-center gap-1.5 cursor-pointer">
                <i class="ph ph-arrow-counter-clockwise text-sm"></i>
                <span>Reset</span>
            </button>
        </div>
    </div>

    <!-- Bulk Action Bar (Visible when 1 or more orders are selected) -->
    <div x-show="selectedOrderIds.length > 0"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="bg-gradient-to-r from-gray-900 to-gray-800 text-white rounded-2xl p-4 shadow-xl flex flex-wrap items-center justify-between gap-4 border border-gray-700">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-rose-500/20 text-rose-400 border border-rose-500/30 flex items-center justify-center text-base font-bold">
                <span x-text="selectedOrderIds.length"></span>
            </div>
            <div>
                <h4 class="text-sm font-bold text-white flex items-center gap-2">
                    <span>Selected Orders</span>
                    <span class="text-xs text-gray-400 font-normal">
                        (<span x-text="selectedOrderIds.length"></span> of <span x-text="orders.length"></span> visible)
                    </span>
                </h4>
                <p class="text-[11px] text-gray-300">Choose a bulk action to apply to all selected orders.</p>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <!-- Deselect -->
            <button type="button"
                    @click="selectedOrderIds = []"
                    class="px-3 py-1.5 bg-gray-700 hover:bg-gray-600 text-gray-200 rounded-xl text-xs font-semibold transition cursor-pointer">
                Deselect All
            </button>

            <!-- Bulk Restore -->
            <button type="button"
                    @click="executeBulkRestore()"
                    :disabled="isBulkActionLoading"
                    class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition shadow-sm inline-flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                <i class="ph-bold" :class="isBulkActionLoading ? 'ph-spinner animate-spin' : 'ph-arrow-counter-clockwise'"></i>
                <span>Restore Selected (<span x-text="selectedOrderIds.length"></span>)</span>
            </button>

            <!-- Bulk Permanent Delete -->
            <button type="button"
                    @click="openBulkDeleteModal()"
                    :disabled="isBulkActionLoading"
                    class="px-4 py-1.5 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold transition shadow-sm inline-flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                <i class="ph-bold ph-trash"></i>
                <span>Permanent Delete (<span x-text="selectedOrderIds.length"></span>)</span>
            </button>
        </div>
    </div>

    <!-- Table Container -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 relative min-h-[360px] overflow-hidden">

        <!-- Loading Overlay -->
        <div x-show="loading"
             x-cloak
             class="absolute inset-0 bg-white/80 z-20 flex flex-col items-center justify-center backdrop-blur-2xs"
             style="display: none;">
            <i class="ph ph-spinner animate-spin text-3xl text-rose-600 mb-2"></i>
            <span class="text-xs font-semibold text-gray-600">Loading trashed orders...</span>
        </div>

        <!-- Orders Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="bg-gray-50/90 text-gray-600 font-bold border-b border-gray-100 uppercase tracking-wider text-[11px]">
                    <tr>
                        <!-- Bulk Selection Checkbox -->
                        <th class="px-4 py-3.5 w-10 text-center">
                            <input type="checkbox"
                                   @change="toggleSelectAll($event.target.checked)"
                                   :checked="orders.length > 0 && selectedOrderIds.length === orders.length"
                                   class="w-4 h-4 rounded border-gray-300 text-rose-600 focus:ring-rose-500 cursor-pointer">
                        </th>
                        <th class="px-5 py-3.5 cursor-pointer hover:text-rose-600 transition" @click="sortBy('order_id')">
                            <div class="flex items-center gap-1">
                                <span>Order ID</span>
                                <i class="ph" :class="getSortIcon('order_id')"></i>
                            </div>
                        </th>
                        <th class="px-6 py-3.5">Customer</th>
                        <th class="px-6 py-3.5">Original Status</th>
                        <th class="px-6 py-3.5">Items & Qty</th>
                        <th class="px-6 py-3.5 text-right cursor-pointer hover:text-rose-600 transition" @click="sortBy('total')">
                            <div class="flex items-center justify-end gap-1">
                                <i class="ph" :class="getSortIcon('total')"></i>
                                <span>Total Amount</span>
                            </div>
                        </th>
                        <th class="px-6 py-3.5 cursor-pointer hover:text-rose-600 transition" @click="sortBy('deleted_at')">
                            <div class="flex items-center gap-1">
                                <span>Moved to Trash</span>
                                <i class="ph" :class="getSortIcon('deleted_at')"></i>
                            </div>
                        </th>
                        <th class="px-6 py-3.5 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="order in orders" :key="order.id">
                        <tr class="hover:bg-rose-50/30 transition-colors"
                            :class="{ 'bg-rose-50/40': selectedOrderIds.includes(order.id) }">

                            <!-- Row Checkbox -->
                            <td class="px-4 py-4 text-center">
                                <input type="checkbox"
                                       :value="order.id"
                                       x-model="selectedOrderIds"
                                       class="w-4 h-4 rounded border-gray-300 text-rose-600 focus:ring-rose-500 cursor-pointer">
                            </td>

                            <!-- Order ID -->
                            <td class="px-5 py-4 font-medium">
                                <div class="flex flex-col items-start gap-0.5">
                                    <span class="font-mono font-bold text-gray-900 text-sm flex items-center gap-1.5">
                                        <i class="ph ph-receipt text-rose-500"></i>
                                        <span x-text="'#' + order.order_id"></span>
                                    </span>
                                    <span class="text-[10px] text-gray-400 font-mono" x-text="'Placed ' + order.created_at"></span>
                                </div>
                            </td>

                            <!-- Customer -->
                            <td class="px-6 py-4">
                                <div class="flex flex-col items-start gap-1">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full bg-rose-50 text-rose-700 font-bold text-xs flex items-center justify-center shrink-0 border border-rose-100"
                                             x-text="(order.customer_name || 'G').charAt(0).toUpperCase()">
                                        </div>
                                        <div class="min-w-0">
                                            <span class="font-semibold text-gray-900 text-xs block truncate" x-text="order.customer_name"></span>
                                            <span class="text-[11px] font-mono text-gray-500 block" x-text="order.customer_phone || 'No phone'"></span>
                                        </div>
                                    </div>
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-bold uppercase tracking-wider"
                                          :class="order.customer_type === 'Registered' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-gray-100 text-gray-600'"
                                          x-text="order.customer_type"></span>
                                </div>
                            </td>

                            <!-- Original Status -->
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider inline-block text-center border"
                                          :class="{
                                              'bg-amber-50 text-amber-700 border-amber-200': order.status === 'pending',
                                              'bg-blue-50 text-blue-700 border-blue-200': order.status === 'confirmed',
                                              'bg-indigo-50 text-indigo-700 border-indigo-200': order.status === 'processing',
                                              'bg-purple-50 text-purple-700 border-purple-200': order.status === 'shipped',
                                              'bg-emerald-50 text-emerald-700 border-emerald-200': order.status === 'delivered',
                                              'bg-rose-50 text-rose-700 border-rose-200': order.status === 'cancelled' || order.status === 'returned',
                                          }"
                                          x-text="order.status">
                                    </span>
                                    <span class="text-[10px] text-gray-400 capitalize" x-text="'Payment: ' + order.payment_status"></span>
                                </div>
                            </td>

                            <!-- Items -->
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="font-semibold text-gray-900" x-text="order.items_count + ' items'"></span>
                                    <span class="text-[10px] text-gray-400" x-text="'Total qty: ' + order.total_quantity"></span>
                                </div>
                            </td>

                            <!-- Total Amount -->
                            <td class="px-6 py-4 text-right">
                                <span class="font-mono font-bold text-gray-900 text-sm" x-text="'৳' + order.total"></span>
                            </td>

                            <!-- Deleted When & By -->
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="font-semibold text-gray-800" x-text="order.deleted_ago || order.deleted_at"></span>
                                    <span class="text-[10px] text-gray-400 font-mono" x-text="order.deleted_at"></span>
                                    <template x-if="order.deleted_by">
                                        <span class="text-[10px] text-rose-600 font-medium" x-text="'By: ' + order.deleted_by"></span>
                                    </template>
                                </div>
                            </td>

                            <!-- Action Buttons -->
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <!-- Restore Button -->
                                    <button type="button"
                                            @click="restoreOrder(order)"
                                            :disabled="restoringOrderId === order.id"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 hover:text-emerald-800 border border-emerald-200 rounded-xl font-bold text-xs transition shadow-2xs cursor-pointer disabled:opacity-50"
                                            title="Restore this order back to active orders">
                                        <i class="ph-bold" :class="restoringOrderId === order.id ? 'ph-spinner animate-spin' : 'ph-arrow-counter-clockwise'"></i>
                                        <span x-text="restoringOrderId === order.id ? 'Restoring...' : 'Restore'"></span>
                                    </button>

                                    <!-- Permanent Delete Button -->
                                    <button type="button"
                                            @click="openSingleDeleteModal(order)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white border border-rose-200 hover:border-rose-600 rounded-xl font-bold text-xs transition shadow-2xs cursor-pointer"
                                            title="Permanently force delete this order with Math CAPTCHA">
                                        <i class="ph-bold ph-trash"></i>
                                        <span>Permanent Delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- Empty State -->
                    <tr x-show="orders.length === 0 && !loading" style="display: none;">
                        <td colspan="8" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                <div class="w-16 h-16 rounded-3xl bg-gray-50 border border-gray-100 text-gray-400 flex items-center justify-center text-3xl mb-3 shadow-inner">
                                    <i class="ph ph-trash"></i>
                                </div>
                                <h3 class="text-base font-bold text-gray-900">Trash is Empty</h3>
                                <p class="text-xs text-gray-500 mt-1">
                                    No soft-deleted orders found. Any order you move to trash from the order list will appear here.
                                </p>
                                <a href="{{ route('admin.orders') }}"
                                   class="mt-4 px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-semibold shadow-xs transition inline-flex items-center gap-1.5">
                                    <i class="ph ph-shopping-cart text-sm"></i>
                                    <span>Go to All Orders</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
        <div x-show="pagination.total > 0"
             class="px-6 py-3.5 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3 bg-gray-50/50">
            <div class="text-xs text-gray-500">
                Showing <span class="font-bold text-gray-800" x-text="pagination.from || 0"></span>
                to <span class="font-bold text-gray-800" x-text="pagination.to || 0"></span>
                of <span class="font-bold text-gray-800" x-text="pagination.total"></span> trashed orders
            </div>

            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1.5 text-xs text-gray-500">
                    <span>Per page:</span>
                    <select x-model="filters.per_page"
                            @change="fetchTrashOrders(1)"
                            class="bg-white border border-gray-200 rounded-lg px-2 py-1 text-xs text-gray-700 font-semibold focus:outline-none focus:ring-1 focus:ring-brand-500">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>

                <div class="flex items-center gap-1">
                    <button type="button"
                            @click="fetchTrashOrders(pagination.current_page - 1)"
                            :disabled="pagination.current_page <= 1 || loading"
                            class="px-3 py-1.5 rounded-lg bg-white border border-gray-200 text-gray-600 text-xs font-semibold hover:bg-gray-100 disabled:opacity-40 disabled:hover:bg-white transition cursor-pointer">
                        Previous
                    </button>
                    <div class="px-3 py-1.5 text-xs font-bold text-gray-700 bg-white border border-gray-200 rounded-lg">
                        <span x-text="pagination.current_page"></span> / <span x-text="pagination.last_page || 1"></span>
                    </div>
                    <button type="button"
                            @click="fetchTrashOrders(pagination.current_page + 1)"
                            :disabled="pagination.current_page >= pagination.last_page || loading"
                            class="px-3 py-1.5 rounded-lg bg-white border border-gray-200 text-gray-600 text-xs font-semibold hover:bg-gray-100 disabled:opacity-40 disabled:hover:bg-white transition cursor-pointer">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Math CAPTCHA Permanent Delete Confirmation Modal (Teleported to Body) -->
    <template x-teleport="body">
        <div x-show="captchaModalOpen"
             x-cloak
             class="fixed inset-0 z-[9999] overflow-y-auto"
             role="dialog"
             aria-modal="true"
             @keydown.escape.window="closeDeleteModal()">

            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                 @click="closeDeleteModal()"></div>

            <!-- Modal Dialog -->
            <div class="flex min-h-screen items-center justify-center p-4 text-center">
                <div class="relative w-full max-w-md bg-white rounded-3xl shadow-2xl border border-gray-100 overflow-hidden text-left"
                     @click.stop>

                    <!-- Modal Header -->
                    <div class="bg-gradient-to-r from-rose-50 via-white to-white px-6 py-4 border-b border-rose-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-rose-100 text-rose-700 flex items-center justify-center text-xl shrink-0 border border-rose-200">
                                <i class="ph-bold ph-shield-warning"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900" x-text="deleteMode === 'bulk' ? 'Bulk Permanent Deletion' : 'Permanent Order Deletion'"></h3>
                                <p class="text-xs text-rose-600 font-medium">Math CAPTCHA Verification Required</p>
                            </div>
                        </div>
                        <button type="button"
                                @click="closeDeleteModal()"
                                :disabled="isSubmittingDelete"
                                class="p-1.5 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition cursor-pointer">
                            <i class="ph ph-x text-lg"></i>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <form @submit.prevent="executePermanentDelete()" class="p-6 space-y-4">
                        <!-- Warning Card -->
                        <div class="bg-rose-50/70 border border-rose-200 rounded-2xl p-4 text-xs text-rose-900 space-y-1">
                            <div class="font-bold flex items-center gap-1.5 text-rose-950">
                                <i class="ph-bold ph-warning-octagon text-sm"></i>
                                Irreversible Permanent Action
                            </div>
                            <template x-if="deleteMode === 'single'">
                                <p class="text-[11px] text-rose-800 leading-relaxed">
                                    Order <strong class="font-mono" x-text="'#' + (selectedOrder ? selectedOrder.order_id : '')"></strong> will be permanently wiped along with all its line items and history logs. This cannot be undone.
                                </p>
                            </template>
                            <template x-if="deleteMode === 'bulk'">
                                <p class="text-[11px] text-rose-800 leading-relaxed">
                                    You are about to permanently delete <strong class="font-bold text-rose-950" x-text="selectedOrderIds.length + ' orders'"></strong> and all their associated line items from the database.
                                </p>
                            </template>
                        </div>

                        <!-- Target Order Info (For Single Delete) -->
                        <template x-if="deleteMode === 'single' && selectedOrder">
                            <div class="bg-gray-50 border border-gray-200/80 rounded-2xl p-3 text-xs flex items-center justify-between">
                                <div>
                                    <span class="text-[10px] text-gray-400 font-semibold uppercase block">Customer</span>
                                    <span class="font-bold text-gray-900" x-text="selectedOrder.customer_name"></span>
                                    <span class="text-gray-400 text-[10px] block font-mono" x-text="selectedOrder.customer_phone"></span>
                                </div>
                                <div class="text-right">
                                    <span class="text-[10px] text-gray-400 font-semibold uppercase block">Total Amount</span>
                                    <span class="font-mono font-bold text-gray-900 text-sm" x-text="'৳' + selectedOrder.total"></span>
                                </div>
                            </div>
                        </template>

                        <!-- Target Summary (For Bulk Delete) -->
                        <template x-if="deleteMode === 'bulk'">
                            <div class="bg-gray-50 border border-gray-200/80 rounded-2xl p-3 text-xs flex items-center justify-between">
                                <span class="font-bold text-gray-800">Total Selected Orders:</span>
                                <span class="font-mono font-black text-rose-600 text-sm" x-text="selectedOrderIds.length + ' orders'"></span>
                            </div>
                        </template>

                        <!-- Math CAPTCHA Challenge Box -->
                        <div class="border border-indigo-100 bg-indigo-50/40 rounded-2xl p-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-indigo-950 flex items-center gap-1.5">
                                    <i class="ph-bold ph-calculator text-indigo-600"></i>
                                    Solve Math Challenge to Confirm:
                                </label>
                                <button type="button"
                                        @click="loadCaptcha()"
                                        :disabled="isLoadingCaptcha || isSubmittingDelete"
                                        class="text-[11px] text-indigo-600 hover:text-indigo-800 hover:underline flex items-center gap-1 cursor-pointer"
                                        title="Get new math question">
                                    <i class="ph ph-arrows-clockwise text-xs" :class="{ 'animate-spin': isLoadingCaptcha }"></i>
                                    <span>New Challenge</span>
                                </button>
                            </div>

                            <!-- CAPTCHA Display -->
                            <div class="flex items-center gap-3">
                                <div class="px-4 py-2.5 bg-white border-2 border-indigo-200 rounded-xl shadow-inner font-mono font-black text-xl text-indigo-900 tracking-wider shrink-0 select-none">
                                    <template x-if="isLoadingCaptcha">
                                        <i class="ph ph-spinner animate-spin text-lg text-indigo-500"></i>
                                    </template>
                                    <template x-if="!isLoadingCaptcha">
                                        <span x-text="captchaQuestion"></span>
                                    </template>
                                </div>

                                <input type="number"
                                       x-ref="captchaInput"
                                       x-model="captchaAnswer"
                                       required
                                       placeholder="Answer"
                                       :disabled="isLoadingCaptcha || isSubmittingDelete"
                                       class="flex-1 bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-base font-mono font-bold text-gray-900 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition shadow-2xs">
                            </div>

                            <template x-if="captchaError">
                                <p class="text-xs font-bold text-rose-600 flex items-center gap-1">
                                    <i class="ph-bold ph-x-circle text-sm"></i>
                                    <span x-text="captchaError"></span>
                                </p>
                            </template>
                        </div>

                        <!-- Modal Actions -->
                        <div class="pt-2 flex items-center justify-end gap-3">
                            <button type="button"
                                    @click="closeDeleteModal()"
                                    :disabled="isSubmittingDelete"
                                    class="px-4 py-2.5 bg-white border border-gray-200 hover:bg-gray-100 text-gray-700 text-xs font-bold rounded-xl transition cursor-pointer disabled:opacity-50">
                                Cancel
                            </button>
                            <button type="submit"
                                    :disabled="!captchaAnswer || isSubmittingDelete || isLoadingCaptcha"
                                    class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl shadow-xs transition inline-flex items-center gap-2 cursor-pointer disabled:opacity-50">
                                <i x-show="isSubmittingDelete" class="ph ph-spinner animate-spin text-base"></i>
                                <i x-show="!isSubmittingDelete" class="ph-bold ph-trash text-base"></i>
                                <span x-text="isSubmittingDelete ? 'Deleting...' : (deleteMode === 'bulk' ? 'Force Delete Selected' : 'Confirm & Force Delete')"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>

</div>

<script>
window.orderTrashComponent = function() {
    return {
        orders: [],
        loading: true,
        filters: {
            search: '',
            status: 'all',
            date_from: '',
            date_to: '',
            sort_field: 'deleted_at',
            sort_direction: 'desc',
            per_page: 10,
        },
        pagination: {
            current_page: 1,
            last_page: 1,
            total: 0,
            from: 0,
            to: 0,
        },

        // Selection & Bulk state
        selectedOrderIds: [],
        isBulkActionLoading: false,
        restoringOrderId: null,

        // CAPTCHA Modal State
        captchaModalOpen: false,
        deleteMode: 'single', // 'single' or 'bulk'
        selectedOrder: null,
        captchaQuestion: 'Loading...',
        captchaToken: '',
        captchaAnswer: '',
        captchaError: '',
        isLoadingCaptcha: false,
        isSubmittingDelete: false,

        init() {
            this.fetchTrashOrders(1);
        },

        async fetchTrashOrders(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: page,
                    search: this.filters.search || '',
                    status: this.filters.status || 'all',
                    date_from: this.filters.date_from || '',
                    date_to: this.filters.date_to || '',
                    sort_field: this.filters.sort_field,
                    sort_direction: this.filters.sort_direction,
                    per_page: this.filters.per_page || 10,
                });

                const res = await fetch(`/admin/dashboard/api/orders/trash?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' }
                });

                if (!res.ok) {
                    throw new Error('Failed to load trashed orders.');
                }

                const data = await res.json();
                this.orders = data.data || [];
                this.pagination = data.pagination || this.pagination;

                // Remove ids from selection that are no longer present
                const visibleIds = this.orders.map(o => o.id);
                this.selectedOrderIds = this.selectedOrderIds.filter(id => visibleIds.includes(id));
            } catch (err) {
                console.error(err);
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: { message: err.message || 'Error loading trashed orders.', type: 'error' }
                }));
            } finally {
                this.loading = false;
            }
        },

        toggleSelectAll(checked) {
            if (checked) {
                this.selectedOrderIds = this.orders.map(o => o.id);
            } else {
                this.selectedOrderIds = [];
            }
        },

        resetFilters() {
            this.filters.search = '';
            this.filters.status = 'all';
            this.filters.date_from = '';
            this.filters.date_to = '';
            this.filters.sort_field = 'deleted_at';
            this.filters.sort_direction = 'desc';
            this.filters.per_page = 10;
            this.fetchTrashOrders(1);
        },

        sortBy(field) {
            if (this.filters.sort_field === field) {
                this.filters.sort_direction = this.filters.sort_direction === 'asc' ? 'desc' : 'asc';
            } else {
                this.filters.sort_field = field;
                this.filters.sort_direction = 'desc';
            }
            this.fetchTrashOrders(1);
        },

        getSortIcon(field) {
            if (this.filters.sort_field !== field) return 'ph-caret-up-down text-gray-300';
            return this.filters.sort_direction === 'asc' ? 'ph-caret-up text-rose-600' : 'ph-caret-down text-rose-600';
        },

        async restoreOrder(order) {
            this.restoringOrderId = order.id;
            try {
                const res = await fetch(`/admin/dashboard/api/orders/${order.id}/restore`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
                    }
                });

                const data = await res.json().catch(() => ({}));

                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Failed to restore order.');
                }

                window.dispatchEvent(new CustomEvent('notify', {
                    detail: { message: data.message || `Order #${order.order_id} restored successfully.`, type: 'success' }
                }));

                // Re-fetch list
                this.fetchTrashOrders(this.pagination.current_page);
            } catch (err) {
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: { message: err.message || 'Error restoring order.', type: 'error' }
                }));
            } finally {
                this.restoringOrderId = null;
            }
        },

        async executeBulkRestore() {
            if (this.selectedOrderIds.length === 0 || this.isBulkActionLoading) return;

            this.isBulkActionLoading = true;
            try {
                const res = await fetch('/admin/dashboard/api/orders/trash/bulk-restore', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        order_ids: this.selectedOrderIds
                    })
                });

                const data = await res.json().catch(() => ({}));

                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Failed to bulk restore orders.');
                }

                window.dispatchEvent(new CustomEvent('notify', {
                    detail: { message: data.message || 'Selected orders restored successfully.', type: 'success' }
                }));

                this.selectedOrderIds = [];
                this.fetchTrashOrders(this.pagination.current_page);
            } catch (err) {
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: { message: err.message || 'Error restoring orders.', type: 'error' }
                }));
            } finally {
                this.isBulkActionLoading = false;
            }
        },

        async openSingleDeleteModal(order) {
            this.deleteMode = 'single';
            this.selectedOrder = order;
            this.captchaModalOpen = true;
            this.captchaAnswer = '';
            this.captchaError = '';
            await this.loadCaptcha();
            this.$nextTick(() => {
                if (this.$refs.captchaInput) {
                    this.$refs.captchaInput.focus();
                }
            });
        },

        async openBulkDeleteModal() {
            if (this.selectedOrderIds.length === 0) return;
            this.deleteMode = 'bulk';
            this.selectedOrder = null;
            this.captchaModalOpen = true;
            this.captchaAnswer = '';
            this.captchaError = '';
            await this.loadCaptcha();
            this.$nextTick(() => {
                if (this.$refs.captchaInput) {
                    this.$refs.captchaInput.focus();
                }
            });
        },

        closeDeleteModal(force = false) {
            if (this.isSubmittingDelete && !force) return;
            this.isSubmittingDelete = false;
            this.captchaModalOpen = false;
            this.selectedOrder = null;
            this.captchaAnswer = '';
            this.captchaError = '';
        },

        async loadCaptcha() {
            this.isLoadingCaptcha = true;
            this.captchaError = '';
            try {
                const res = await fetch('/admin/dashboard/api/orders/trash/captcha', {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.captchaQuestion = data.question;
                    this.captchaToken = data.token;
                } else {
                    this.captchaQuestion = 'Failed to load';
                }
            } catch (err) {
                this.captchaQuestion = 'Error loading';
            } finally {
                this.isLoadingCaptcha = false;
            }
        },

        async executePermanentDelete() {
            if (!this.captchaAnswer || this.isSubmittingDelete) return;

            this.isSubmittingDelete = true;
            this.captchaError = '';

            try {
                let url, body;
                if (this.deleteMode === 'bulk') {
                    url = '/admin/dashboard/api/orders/trash/bulk-force-delete';
                    body = {
                        order_ids: this.selectedOrderIds,
                        captcha_answer: this.captchaAnswer,
                        captcha_token: this.captchaToken
                    };
                } else {
                    if (!this.selectedOrder) return;
                    url = `/admin/dashboard/api/orders/${this.selectedOrder.id}/force-delete`;
                    body = {
                        captcha_answer: this.captchaAnswer,
                        captcha_token: this.captchaToken
                    };
                }

                const res = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(body)
                });

                const data = await res.json().catch(() => ({}));

                if (!res.ok || !data.success) {
                    if (res.status === 422) {
                        this.captchaError = data.message || 'Incorrect CAPTCHA answer.';
                        await this.loadCaptcha();
                        return;
                    }
                    throw new Error(data.message || 'Failed to permanently delete order.');
                }

                window.dispatchEvent(new CustomEvent('notify', {
                    detail: { message: data.message || 'Orders permanently deleted.', type: 'success' }
                }));

                this.closeDeleteModal(true);
                if (this.deleteMode === 'bulk') {
                    this.selectedOrderIds = [];
                }
                this.fetchTrashOrders(this.pagination.current_page);
            } catch (err) {
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: { message: err.message || 'Error deleting order.', type: 'error' }
                }));
            } finally {
                this.isSubmittingDelete = false;
            }
        }
    };
};

document.addEventListener('alpine:init', () => {
    Alpine.data('orderTrashComponent', window.orderTrashComponent);
});
</script>
@endsection
