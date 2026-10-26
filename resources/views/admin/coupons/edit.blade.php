@extends('layouts.admin')

@section('header_title', 'Edit Coupon')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="couponEditForm({
    discountType: '{{ old('discount_type', $coupon->discount_type) }}',
    targetType: '{{ old('target_type', $coupon->target_type) }}',
    initialSelectedProducts: {{ json_encode($selectedProducts) }}
})">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Coupon: <span class="font-mono text-brand-600">{{ $coupon->code }}</span></h1>
            <p class="text-sm text-gray-500 mt-1">Update discount values, restrictions, and validity period.</p>
        </div>
        <a href="{{ route('admin.coupons.index') }}"
           class="px-4 py-2 rounded-xl bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 text-sm font-semibold transition">
            <i class="ph ph-arrow-left mr-1"></i> Back to List
        </a>
    </div>

    @if($errors->any())
        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
            <p class="font-bold mb-1">Please fix the following errors:</p>
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.coupons.update', $coupon) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Card 1: Basic Information -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 space-y-5">
            <h2 class="text-base font-bold text-gray-900 pb-3 border-b border-gray-100 flex items-center gap-2">
                <i class="ph-bold ph-ticket text-brand-600"></i> Coupon Details
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider">
                        Coupon Code <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="code" value="{{ old('code', $coupon->code) }}" required
                           class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm font-mono uppercase focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider">Description (Optional)</label>
                    <input type="text" name="description" value="{{ old('description', $coupon->description) }}"
                           placeholder="e.g. 50 Taka off for Eid Special"
                           class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition">
                </div>
            </div>
        </div>

        <!-- Card 2: Discount & Pricing Rules -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 space-y-5">
            <h2 class="text-base font-bold text-gray-900 pb-3 border-b border-gray-100 flex items-center gap-2">
                <i class="ph-bold ph-percent text-brand-600"></i> Discount & Pricing Rules
            </h2>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-2 uppercase tracking-wider">Discount Type <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <label class="flex items-center gap-3 p-3.5 border rounded-xl cursor-pointer transition"
                           :class="discountType === 'fixed' ? 'border-brand-500 bg-brand-50/20 text-brand-900 font-bold' : 'border-gray-200 bg-gray-50/50 text-gray-700'">
                        <input type="radio" name="discount_type" value="fixed" x-model="discountType" class="text-brand-600 focus:ring-brand-500">
                        <div>
                            <span class="block text-sm">Fixed Amount (৳)</span>
                            <span class="block text-xs font-normal text-gray-500">Direct deduction in Taka</span>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3.5 border rounded-xl cursor-pointer transition"
                           :class="discountType === 'percentage' ? 'border-brand-500 bg-brand-50/20 text-brand-900 font-bold' : 'border-gray-200 bg-gray-50/50 text-gray-700'">
                        <input type="radio" name="discount_type" value="percentage" x-model="discountType" class="text-brand-600 focus:ring-brand-500">
                        <div>
                            <span class="block text-sm">Percentage (%)</span>
                            <span class="block text-xs font-normal text-gray-500">Percent off order value</span>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3.5 border rounded-xl cursor-pointer transition"
                           :class="discountType === 'free_shipping' ? 'border-brand-500 bg-brand-50/20 text-brand-900 font-bold' : 'border-gray-200 bg-gray-50/50 text-gray-700'">
                        <input type="radio" name="discount_type" value="free_shipping" x-model="discountType" class="text-brand-600 focus:ring-brand-500">
                        <div>
                            <span class="block text-sm">Free Shipping</span>
                            <span class="block text-xs font-normal text-gray-500">Waives delivery charge</span>
                        </div>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div x-show="discountType !== 'free_shipping'">
                    <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider">
                        <span x-text="discountType === 'percentage' ? 'Discount Percentage (%)' : 'Discount Amount (৳)'"></span>
                        <span class="text-red-500">*</span>
                    </label>
                    <input type="number" step="0.01" min="0" name="discount_amount" value="{{ old('discount_amount', $coupon->discount_amount) }}"
                           class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition">
                </div>

                <div x-show="discountType === 'percentage'">
                    <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider">
                        Max Discount Cap (৳) <span class="text-gray-400 font-normal lowercase">(optional)</span>
                    </label>
                    <input type="number" step="0.01" min="0" name="max_discount_amount" value="{{ old('max_discount_amount', $coupon->max_discount_amount) }}"
                           placeholder="e.g. 500"
                           class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider">
                        Min Order Amount (৳) <span class="text-gray-400 font-normal lowercase">(optional)</span>
                    </label>
                    <input type="number" step="0.01" min="0" name="min_order_amount" value="{{ old('min_order_amount', $coupon->min_order_amount) }}"
                           placeholder="e.g. 1000"
                           class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition">
                </div>
            </div>
        </div>

        <!-- Card 3: Scope & Applicability -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 space-y-5">
            <h2 class="text-base font-bold text-gray-900 pb-3 border-b border-gray-100 flex items-center gap-2">
                <i class="ph-bold ph-package text-brand-600"></i> Product & Category Scope
            </h2>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-2 uppercase tracking-wider">Where does this apply?</label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <label class="flex items-center gap-3 p-3.5 border rounded-xl cursor-pointer transition"
                           :class="targetType === 'all' ? 'border-brand-500 bg-brand-50/20 text-brand-900 font-bold' : 'border-gray-200 bg-gray-50/50 text-gray-700'">
                        <input type="radio" name="target_type" value="all" x-model="targetType" class="text-brand-600 focus:ring-brand-500">
                        <span class="text-sm">Entire Store (All Products)</span>
                    </label>

                    <label class="flex items-center gap-3 p-3.5 border rounded-xl cursor-pointer transition"
                           :class="targetType === 'category' ? 'border-brand-500 bg-brand-50/20 text-brand-900 font-bold' : 'border-gray-200 bg-gray-50/50 text-gray-700'">
                        <input type="radio" name="target_type" value="category" x-model="targetType" class="text-brand-600 focus:ring-brand-500">
                        <div>
                            <span class="block text-sm">Specific Categories</span>
                            <span class="block text-xs font-normal text-emerald-600">Auto-includes child sub-categories</span>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3.5 border rounded-xl cursor-pointer transition"
                           :class="targetType === 'product' ? 'border-brand-500 bg-brand-50/20 text-brand-900 font-bold' : 'border-gray-200 bg-gray-50/50 text-gray-700'">
                        <input type="radio" name="target_type" value="product" x-model="targetType" class="text-brand-600 focus:ring-brand-500">
                        <span class="text-sm">Specific Products</span>
                    </label>
                </div>
            </div>

            @php
                $targetIds = old('target_ids', $coupon->target_ids ?? []);
            @endphp

            <!-- Category Selector -->
            <div x-show="targetType === 'category'" style="display: none;" class="pt-2 border-t border-gray-100">
                <div class="flex items-center justify-between mb-3">
                    <label class="text-xs font-bold text-gray-700 uppercase tracking-wider">Select Categories:</label>
                    <input type="text" x-model="categorySearch" placeholder="Filter categories..."
                           class="bg-gray-50 border border-gray-200 rounded-lg px-3 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-brand-500">
                </div>
                <div class="max-h-56 overflow-y-auto custom-scrollbar border border-gray-100 rounded-xl p-3 bg-gray-50/50 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                    @foreach($categories as $category)
                        <label class="flex items-center gap-2.5 p-2 bg-white rounded-lg border border-gray-200 hover:border-brand-500 cursor-pointer text-xs transition"
                               x-show="'{{ strtolower(addslashes($category->name)) }}'.includes(categorySearch.toLowerCase())">
                            <input type="checkbox" name="target_ids[]" value="{{ $category->id }}"
                                   {{ in_array($category->id, $targetIds) ? 'checked' : '' }}
                                   :disabled="targetType !== 'category'"
                                   class="rounded text-brand-600 focus:ring-brand-500">
                            <span class="font-medium text-gray-800 truncate">{{ $category->name }}</span>
                            @if($category->parent_id)
                                <span class="text-[10px] text-gray-400 bg-gray-100 px-1.5 py-0.5 rounded">sub</span>
                            @endif
                        </label>
                    @endforeach
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    <i class="ph ph-info text-brand-600"></i> Any product belonging to selected categories or their descendant child categories will automatically receive the discount.
                </p>
            </div>

            <!-- Product Selector -->
            <div x-show="targetType === 'product'" style="display: none;" class="pt-4 border-t border-gray-100 space-y-3">
                <!-- Hidden inputs for form submission -->
                <template x-for="p in selectedProducts" :key="p.id">
                    <input type="hidden" name="target_ids[]" :value="p.id" :disabled="targetType !== 'product'">
                </template>

                <!-- Header & Search -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                    <div>
                        <label class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-2">
                            <span>Select Products</span>
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold"
                                  :class="selectedProducts.length > 0 ? 'bg-brand-100 text-brand-700' : 'bg-gray-100 text-gray-500'"
                                  x-text="selectedProducts.length + ' selected'"></span>
                        </label>
                        <p class="text-[11px] text-gray-400 mt-0.5">Filter and select the products eligible for this coupon.</p>
                    </div>

                    <div class="relative w-full sm:w-72">
                        <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input type="text" x-model="productSearch" @input="debounceFetchProducts()"
                               placeholder="Search products by title..."
                               class="w-full bg-gray-50 border border-gray-200 rounded-lg pl-8 pr-8 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-brand-500 focus:bg-white transition">
                        <button type="button" x-show="productSearch" @click="productSearch = ''; fetchProducts(1)"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-xs">
                            <i class="ph ph-x"></i>
                        </button>
                    </div>
                </div>

                <!-- Selected Products Tray -->
                <div x-show="selectedProducts.length > 0" class="p-3 bg-brand-50/40 rounded-xl border border-brand-100 space-y-2">
                    <div class="flex items-center justify-between text-xs text-brand-900 font-semibold">
                        <span>Selected for this coupon (<span x-text="selectedProducts.length"></span>)</span>
                        <button type="button" @click="clearSelectedProducts()" class="text-xs text-red-500 hover:text-red-700 font-medium hover:underline cursor-pointer">
                            Clear all
                        </button>
                    </div>
                    <div class="flex flex-wrap gap-2 max-h-28 overflow-y-auto custom-scrollbar">
                        <template x-for="p in selectedProducts" :key="'chip-' + p.id">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-white text-gray-800 border border-brand-200 shadow-2xs">
                                <span class="max-w-[200px] truncate" x-text="p.title"></span>
                                <span class="text-brand-600 font-bold" x-text="'৳' + p.base_price"></span>
                                <button type="button" @click="removeProduct(p.id)" class="text-gray-400 hover:text-red-500 transition ml-0.5 cursor-pointer">
                                    <i class="ph ph-x-circle-fill"></i>
                                </button>
                            </span>
                        </template>
                    </div>
                </div>

                <!-- Paginated List with Loading Overlay -->
                <div class="relative border border-gray-200 rounded-xl bg-gray-50/50 overflow-hidden">
                    <div x-show="loadingProducts" style="display: none;"
                         class="absolute inset-0 bg-white/75 backdrop-blur-2xs z-10 flex items-center justify-center">
                        <div class="flex items-center gap-2 text-xs font-semibold text-brand-600 bg-white px-3.5 py-2 rounded-lg shadow-sm border border-gray-100">
                            <i class="ph ph-spinner animate-spin text-base"></i>
                            <span>Loading products...</span>
                        </div>
                    </div>

                    <div class="max-h-72 overflow-y-auto custom-scrollbar p-2.5 space-y-1.5">
                        <template x-for="product in productsList" :key="product.id">
                            <label class="flex items-center justify-between p-2.5 rounded-lg border cursor-pointer text-xs transition select-none"
                                   :class="isSelected(product.id) ? 'bg-brand-50/60 border-brand-300 ring-1 ring-brand-300' : 'bg-white border-gray-200 hover:border-gray-300'">
                                <div class="flex items-center gap-3 min-w-0">
                                    <input type="checkbox" :checked="isSelected(product.id)" @change="toggleProduct(product)"
                                           class="rounded text-brand-600 focus:ring-brand-500">
                                    <template x-if="product.image_url">
                                        <img :src="product.image_url" alt="" class="w-8 h-8 rounded-md object-cover border border-gray-100 shrink-0">
                                    </template>
                                    <template x-if="!product.image_url">
                                        <div class="w-8 h-8 rounded-md bg-gray-100 flex items-center justify-center text-gray-400 shrink-0">
                                            <i class="ph ph-image text-sm"></i>
                                        </div>
                                    </template>
                                    <span class="font-medium text-gray-800 truncate" x-text="product.title"></span>
                                </div>
                                <span class="font-bold text-brand-600 shrink-0 ml-2" x-text="'৳' + product.base_price"></span>
                            </label>
                        </template>

                        <div x-show="!loadingProducts && productsList.length === 0" class="text-center py-8 text-gray-400 text-xs">
                            <i class="ph ph-package text-2xl mb-1 block"></i>
                            No products found.
                        </div>
                    </div>

                    <!-- Pagination Footer -->
                    <div class="px-3 py-2.5 bg-white border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs">
                        <div class="text-gray-500 text-[11px]">
                            <template x-if="pagination.total > 0">
                                <span>Showing <strong class="text-gray-800" x-text="pagination.from"></strong> - <strong class="text-gray-800" x-text="pagination.to"></strong> of <strong class="text-gray-800" x-text="pagination.total"></strong> products</span>
                            </template>
                            <template x-if="pagination.total === 0">
                                <span>0 products</span>
                            </template>
                        </div>
                        <div class="flex items-center gap-1.5" x-show="pagination.last_page > 1">
                            <button type="button" @click="fetchProducts(pagination.current_page - 1)"
                                    :disabled="pagination.current_page <= 1 || loadingProducts"
                                    class="px-2.5 py-1 rounded-md border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed text-xs font-medium flex items-center gap-1 transition cursor-pointer">
                                <i class="ph ph-caret-left"></i>
                                <span>Prev</span>
                            </button>
                            <span class="px-2 text-gray-500 text-xs font-medium">
                                <span class="font-bold text-gray-800" x-text="pagination.current_page"></span> / <span x-text="pagination.last_page"></span>
                            </span>
                            <button type="button" @click="fetchProducts(pagination.current_page + 1)"
                                    :disabled="pagination.current_page >= pagination.last_page || loadingProducts"
                                    class="px-2.5 py-1 rounded-md border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed text-xs font-medium flex items-center gap-1 transition cursor-pointer">
                                <span>Next</span>
                                <i class="ph ph-caret-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: Validity & Usage Limits -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 space-y-5">
            <h2 class="text-base font-bold text-gray-900 pb-3 border-b border-gray-100 flex items-center gap-2">
                <i class="ph-bold ph-calendar-check text-brand-600"></i> Validity Period & Limits
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider">Start Date & Time (Optional)</label>
                    <input type="datetime-local" name="starts_at"
                           value="{{ old('starts_at', $coupon->starts_at ? $coupon->starts_at->format('Y-m-d\TH:i') : '') }}"
                           class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider">Expiration Date & Time (Optional)</label>
                    <input type="datetime-local" name="expires_at"
                           value="{{ old('expires_at', $coupon->expires_at ? $coupon->expires_at->format('Y-m-d\TH:i') : '') }}"
                           class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-2">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider">
                        Total Usage Limit <span class="text-gray-400 font-normal lowercase">(leave empty for unlimited)</span>
                    </label>
                    <input type="number" min="1" name="usage_limit_total" value="{{ old('usage_limit_total', $coupon->usage_limit_total) }}"
                           placeholder="e.g. 100"
                           class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition">
                    <p class="text-[11px] text-gray-400 mt-1">Currently used: {{ $coupon->used_count }} times</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider">Usage Limit Per Customer</label>
                    <input type="number" min="1" name="usage_limit_per_user" value="{{ old('usage_limit_per_user', $coupon->usage_limit_per_user) }}"
                           class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition">
                </div>
            </div>

            <!-- Toggles -->
            <div class="pt-4 border-t border-gray-100 flex flex-col sm:flex-row gap-6">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $coupon->is_active) ? 'checked' : '' }}
                           class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500">
                    <div>
                        <span class="block text-sm font-bold text-gray-900">Active Status</span>
                        <span class="block text-xs text-gray-500">Customers can apply this coupon</span>
                    </div>
                </label>

                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="first_order_only" value="1" {{ old('first_order_only', $coupon->first_order_only) ? 'checked' : '' }}
                           class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500">
                    <div>
                        <span class="block text-sm font-bold text-gray-900">First-Time Customers Only</span>
                        <span class="block text-xs text-gray-500">Only customers with 0 previous orders can apply this</span>
                    </div>
                </label>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.coupons.index') }}"
               class="px-6 py-2.5 rounded-xl border border-gray-200 text-gray-600 font-semibold text-sm hover:bg-gray-50 transition">
                Cancel
            </a>
            <button type="submit"
                    class="px-8 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm shadow transition cursor-pointer">
                Save & Update Coupon
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    function couponEditForm(config) {
        return {
            discountType: config.discountType || 'fixed',
            targetType: config.targetType || 'all',
            categorySearch: '',
            productSearch: '',
            selectedProducts: config.initialSelectedProducts || [],
            productsList: [],
            loadingProducts: false,
            pagination: {
                current_page: 1,
                last_page: 1,
                total: 0,
                from: 0,
                to: 0
            },
            debounceTimer: null,

            init() {
                this.fetchProducts(1);
            },

            debounceFetchProducts() {
                clearTimeout(this.debounceTimer);
                this.debounceTimer = setTimeout(() => {
                    this.fetchProducts(1);
                }, 350);
            },

            async fetchProducts(page = 1) {
                if (page < 1) return;
                this.loadingProducts = true;
                try {
                    const url = new URL('{{ route('admin.coupons.api.products') }}', window.location.origin);
                    url.searchParams.set('page', page);
                    if (this.productSearch.trim()) {
                        url.searchParams.set('q', this.productSearch.trim());
                    }
                    const res = await fetch(url.toString(), {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const data = await res.json();
                    this.productsList = data.data || [];
                    this.pagination = {
                        current_page: data.current_page,
                        last_page: data.last_page,
                        total: data.total,
                        from: data.from,
                        to: data.to
                    };
                } catch (err) {
                    console.error('Failed to load products', err);
                } finally {
                    this.loadingProducts = false;
                }
            },

            isSelected(id) {
                return this.selectedProducts.some(p => p.id === id);
            },

            toggleProduct(product) {
                const idx = this.selectedProducts.findIndex(p => p.id === product.id);
                if (idx > -1) {
                    this.selectedProducts.splice(idx, 1);
                } else {
                    this.selectedProducts.push({
                        id: product.id,
                        title: product.title,
                        base_price: product.base_price,
                        image_url: product.image_url
                    });
                }
            },

            removeProduct(id) {
                this.selectedProducts = this.selectedProducts.filter(p => p.id !== id);
            },

            clearSelectedProducts() {
                this.selectedProducts = [];
            }
        };
    }
</script>
@endpush
