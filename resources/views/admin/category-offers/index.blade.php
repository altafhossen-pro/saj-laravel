@extends('layouts.admin')

@section('header_title', 'Category Offers & Discount Matrix')

@section('content')
<div class="space-y-6" x-data="{
    selectedIds: [],
    selectAll: false,
    bulkValue: 10,
    bulkType: 'percentage',
    bulkIncludeSubs: true,
    toasts: [],
    toggleAll() {
        if (this.selectAll) {
            this.selectedIds = Array.from(document.querySelectorAll('.cat-checkbox')).map(el => el.value);
        } else {
            this.selectedIds = [];
        }
    },
    selectMainOnly() {
        this.selectedIds = Array.from(document.querySelectorAll('.main-cat-checkbox')).map(el => el.value);
        this.selectAll = false;
    },
    showToast(title, message, type = 'success') {
        const id = Date.now() + Math.random();
        this.toasts.push({ id, title, message, type, visible: true });
        setTimeout(() => {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }, 3500);
    },
    async toggleCategory(catId, toggleUrl, rowState) {
        if (rowState.loading) return;
        rowState.loading = true;
        const previous = rowState.active;
        // Optimistically update
        rowState.active = !previous;

        try {
            const res = await fetch(toggleUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    discount_value: parseInt(rowState.val) || 0,
                    discount_type: rowState.type
                })
            });
            const data = await res.json();
            if (res.ok && data.success) {
                rowState.active = data.is_active;
                this.showToast('Saved', data.detail || 'Status updated successfully', 'success');
            } else {
                rowState.active = previous;
                this.showToast('Error', data.message || 'Failed to update status', 'error');
            }
        } catch (err) {
            rowState.active = previous;
            this.showToast('Error', 'Connection error. Please try again.', 'error');
        } finally {
            rowState.loading = false;
        }
    }
}">
    <!-- Floating Toast Notifications for Instant Save -->
    <div class="fixed bottom-6 right-6 z-50 flex flex-col gap-2.5 pointer-events-none max-w-sm">
        <template x-for="t in toasts" :key="t.id">
            <div x-show="t.visible"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-3 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                 :class="t.type === 'error' ? 'bg-red-600 text-white shadow-red-500/20' : 'bg-gray-900 text-white shadow-black/20'"
                 class="pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-2xl shadow-xl border border-white/10">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0"
                     :class="t.type === 'error' ? 'bg-white/20 text-white' : 'bg-emerald-500/20 text-emerald-400'">
                    <i :class="t.type === 'error' ? 'ph-bold ph-warning-circle text-lg' : 'ph-bold ph-check text-lg'"></i>
                </div>
                <div class="flex-1 min-w-0 pr-2">
                    <p class="text-xs font-bold uppercase tracking-wider" :class="t.type === 'error' ? 'text-red-100' : 'text-emerald-400'" x-text="t.title"></p>
                    <p class="text-xs text-gray-200 truncate mt-0.5" x-text="t.message"></p>
                </div>
                <button type="button" @click="toasts = toasts.filter(item => item.id !== t.id)" class="text-gray-400 hover:text-white transition-colors cursor-pointer p-1">
                    <i class="ph ph-x text-sm"></i>
                </button>
            </div>
        </template>
    </div>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Category Offers & Discounts</h1>
            <p class="text-sm text-gray-500 mt-1">ক্যাটাগরি টেবিলে সরাসরি ডিসকাউন্ট (%) বা ফ্ল্যাট (৳) বসিয়ে সেভ করুন। মেইন ক্যাটাগরিতে অফার দিলে সাব-ক্যাটাগরির প্রোডাক্টেও প্রযোজ্য হবে, অথবা নির্দিষ্ট সাব-ক্যাটাগরিতে আলাদাভাবেও ছাড় দেওয়া যাবে।</p>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl text-sm flex items-center gap-3 shadow-sm">
            <i class="ph-bold ph-check-circle text-xl text-emerald-600 shrink-0"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-red-50 border border-red-200 text-red-800 rounded-2xl text-sm flex items-center gap-3 shadow-sm">
            <i class="ph-bold ph-warning-circle text-xl text-red-600 shrink-0"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Categories</p>
                <h3 class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['total_categories'] }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center text-xl">
                <i class="ph-bold ph-folders"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Active Offer Categories</p>
                <h3 class="text-2xl font-bold text-emerald-600 mt-1">{{ $stats['active_offers'] }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                <i class="ph-bold ph-tag"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Products Currently Discounted</p>
                <h3 class="text-2xl font-bold text-blue-600 mt-1">{{ $stats['total_discounted_products'] }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                <i class="ph-bold ph-package"></i>
            </div>
        </div>
    </div>

    <!-- Bulk Action Tool Bar -->
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex items-center gap-3 flex-wrap">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-400">বাল্ক অ্যাকশন (Bulk Options):</span>
                <button type="button" @click="selectAll = true; toggleAll()" class="text-xs font-semibold text-brand-600 hover:underline cursor-pointer">
                    Select All
                </button>
                <span class="text-gray-300">|</span>
                <button type="button" @click="selectMainOnly()" class="text-xs font-semibold text-gray-600 hover:text-brand-600 hover:underline cursor-pointer">
                    Select Main Only
                </button>
                <span class="text-gray-300">|</span>
                <button type="button" @click="selectAll = false; toggleAll()" class="text-xs font-semibold text-gray-500 hover:underline cursor-pointer">
                    Clear Selection
                </button>
                <span class="ml-2 px-2.5 py-1 bg-brand-50 text-brand-700 text-xs font-bold rounded-lg" x-text="`${selectedIds.length} Categories Selected`"></span>
            </div>

            <!-- Bulk Apply Form -->
            <form action="{{ route('admin.category-offers.bulk-apply') }}" method="POST" class="flex flex-wrap items-center gap-2">
                @csrf
                <template x-for="id in selectedIds" :key="id">
                    <input type="hidden" name="category_ids[]" :value="id">
                </template>

                <div class="flex items-center border border-gray-200 rounded-xl overflow-hidden bg-gray-50 focus-within:ring-2 focus-within:ring-brand-500">
                    <input type="number" step="1" min="0" x-model="bulkValue" name="bulk_discount_value" placeholder="10"
                           onkeydown="if(event.key === '.' || event.key === ',' || event.key === 'e' || event.key === 'E' || event.key === '+' || event.key === '-') event.preventDefault();"
                           oninput="this.value = this.value.replace(/[^0-9]/g, '');"
                           class="w-16 px-3 py-2 text-sm text-center font-bold text-gray-800 bg-transparent focus:outline-none">
                    <select x-model="bulkType" name="bulk_discount_type" class="bg-gray-100 text-xs font-bold text-gray-700 px-2 py-2 border-l border-gray-200 focus:outline-none">
                        <option value="percentage">% OFF</option>
                        <option value="fixed">৳ FLAT</option>
                    </select>
                </div>

                <label class="hidden sm:flex items-center gap-1.5 text-xs text-gray-600 font-medium px-2">
                    <input type="checkbox" name="include_subcategories" value="1" x-model="bulkIncludeSubs" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                    <span>Include Subs</span>
                </label>

                <button type="submit" name="bulk_action" value="apply" :disabled="selectedIds.length === 0"
                        class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-bold transition shadow-sm disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-1.5 cursor-pointer">
                    <i class="ph-bold ph-lightning"></i>
                    Apply to Selected
                </button>

                <button type="submit" name="bulk_action" value="revert" :disabled="selectedIds.length === 0"
                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-1.5 cursor-pointer">
                    <i class="ph ph-arrow-counter-clockwise"></i>
                    Turn Off / Revert
                </button>
            </form>
        </div>
    </div>

    <!-- Category Offer Matrix Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-gray-50/80 text-gray-500 font-semibold border-b border-gray-100 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3.5 w-10 text-center">
                            <input type="checkbox" x-model="selectAll" @change="toggleAll()" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                        </th>
                        <th class="px-6 py-3.5">Category Name</th>
                        <th class="px-4 py-3.5">Products</th>
                        <th class="px-6 py-3.5">Discount Amount</th>
                        <th class="px-4 py-3.5">Subcategories Scope</th>
                        <th class="px-4 py-3.5 text-center">Status</th>
                        <th class="px-6 py-3.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($categories as $mainCat)
                        @php
                            $mainOffer = $mainCat->offer;
                            $hasMainActiveOffer = $mainOffer && $mainOffer->is_active;
                        @endphp

                        <!-- MAIN CATEGORY ROW -->
                        <tr class="bg-white hover:bg-gray-50/80 transition-colors group"
                            x-data="{
                                active: {{ $hasMainActiveOffer ? 'true' : 'false' }},
                                loading: false,
                                val: {{ (int)($mainOffer?->discount_value ?? 0) }},
                                type: '{{ $mainOffer?->discount_type ?? 'percentage' }}'
                            }">
                            <td class="px-4 py-4 text-center">
                                <input type="checkbox" class="cat-checkbox main-cat-checkbox rounded border-gray-300 text-brand-600 focus:ring-brand-500"
                                       value="{{ $mainCat->id }}" x-model="selectedIds">
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center font-bold text-base shrink-0 border border-brand-100">
                                        <i class="ph-bold ph-folder"></i>
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-900 text-sm flex items-center gap-2">
                                            <span>{{ $mainCat->name }}</span>
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-gray-100 text-gray-600 uppercase tracking-wide">Main</span>
                                        </div>
                                        <div class="text-xs text-gray-400 mt-0.5">{{ $mainCat->children->count() }} subcategories</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-gray-100 text-gray-700">
                                    <i class="ph ph-package text-gray-400"></i>
                                    {{ $mainCat->products_count }} products
                                </span>
                            </td>

                            <!-- IN-ROW FORM FOR MAIN CATEGORY -->
                            <form action="{{ route('admin.category-offers.save', $mainCat) }}" method="POST">
                                @csrf
                                <input type="hidden" name="is_active" :value="active ? 1 : 0">

                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1.5">
                                        <div class="flex items-center border border-gray-200 rounded-xl overflow-hidden bg-gray-50 focus-within:ring-2 focus-within:ring-brand-500 focus-within:bg-white">
                                            <input type="number" step="1" min="0" name="discount_value"
                                                   x-model.number="val"
                                                   value="{{ old('discount_value', (int)($mainOffer?->discount_value ?? 0)) }}"
                                                   onkeydown="if(event.key === '.' || event.key === ',' || event.key === 'e' || event.key === 'E' || event.key === '+' || event.key === '-') event.preventDefault();"
                                                   oninput="this.value = this.value.replace(/[^0-9]/g, '');"
                                                   class="w-20 px-3 py-2 text-sm text-center font-bold text-gray-900 bg-transparent focus:outline-none"
                                                   placeholder="0">
                                            <select name="discount_type" x-model="type" class="bg-gray-100 text-xs font-bold text-gray-700 px-2 py-2 border-l border-gray-200 focus:outline-none">
                                                <option value="percentage" {{ ($mainOffer?->discount_type ?? 'percentage') === 'percentage' ? 'selected' : '' }}>%</option>
                                                <option value="fixed" {{ ($mainOffer?->discount_type ?? '') === 'fixed' ? 'selected' : '' }}>৳</option>
                                            </select>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <label class="flex items-center gap-2 cursor-pointer text-xs font-medium text-gray-600">
                                        <input type="checkbox" name="include_subcategories" value="1"
                                               {{ ($mainOffer?->include_subcategories ?? true) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                        <span>Apply to all subs</span>
                                    </label>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <div class="inline-flex items-center gap-2.5">
                                        <!-- Animated Toggle Switch -->
                                        <button type="button"
                                                @click="toggleCategory({{ $mainCat->id }}, '{{ route('admin.category-offers.toggle', $mainCat) }}', $data)"
                                                :disabled="loading"
                                                :class="active ? 'bg-emerald-500' : 'bg-gray-300'"
                                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 disabled:opacity-50"
                                                role="switch" :aria-checked="active">
                                            <span class="sr-only">Toggle offer status</span>
                                            <span :class="active ? 'translate-x-5' : 'translate-x-0'"
                                                  class="pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out">
                                                <span :class="active ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in'"
                                                      class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity text-[9px] text-gray-400 font-bold select-none">
                                                    ✕
                                                </span>
                                                <span :class="active ? 'opacity-100 duration-200 ease-in' : 'opacity-0 duration-100 ease-out'"
                                                      class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity text-[10px] text-emerald-600 font-bold select-none">
                                                    ✓
                                                </span>
                                            </span>
                                        </button>
                                        <span class="text-xs font-semibold w-10 text-left transition-colors"
                                              :class="active ? 'text-emerald-700' : 'text-gray-400'"
                                              x-text="active ? 'Active' : 'Off'">
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button type="submit"
                                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-gray-900 hover:bg-black text-white text-xs font-semibold transition shadow-sm cursor-pointer">
                                        <i class="ph-bold ph-floppy-disk text-sm"></i>
                                        Save
                                    </button>
                                </td>
                            </form>
                        </tr>

                        <!-- SUB-CATEGORIES ROWS -->
                        @if($mainCat->children && $mainCat->children->count() > 0)
                            @foreach($mainCat->children as $subCat)
                                @php
                                    $subOffer = $subCat->offer;
                                    $hasSubActiveOffer = $subOffer && $subOffer->is_active;
                                    // Inherited notice if main category has active offer applying to subcategories
                                    $isInheritedFromMain = $hasMainActiveOffer && ($mainOffer->include_subcategories);
                                @endphp
                                <tr class="bg-gray-50/40 hover:bg-gray-100/60 transition-colors"
                                    x-data="{
                                        active: {{ $hasSubActiveOffer ? 'true' : 'false' }},
                                        loading: false,
                                        val: {{ (int)($subOffer?->discount_value ?? 0) }},
                                        type: '{{ $subOffer?->discount_type ?? 'percentage' }}'
                                    }">
                                    <td class="px-4 py-3 text-center">
                                        <input type="checkbox" class="cat-checkbox rounded border-gray-300 text-brand-600 focus:ring-brand-500"
                                               value="{{ $subCat->id }}" x-model="selectedIds">
                                    </td>
                                    <td class="px-6 py-3 pl-12">
                                        <div class="flex items-center gap-2.5">
                                            <span class="text-gray-400 font-mono text-sm">↳</span>
                                            <div class="w-7 h-7 rounded-lg bg-gray-100 text-gray-500 flex items-center justify-center text-xs shrink-0">
                                                <i class="ph ph-folder-notch"></i>
                                            </div>
                                            <div>
                                                <span class="font-medium text-gray-800 text-sm">{{ $subCat->name }}</span>
                                                @if($isInheritedFromMain)
                                                    <span class="ml-1.5 px-2 py-0.5 text-[10px] font-bold rounded bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                        Inherited from {{ $mainCat->name }} ({{ (int)$mainOffer->discount_value }}{{ $mainOffer->discount_type === 'percentage' ? '%' : '৳' }})
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-xs text-gray-500 font-medium">
                                            {{ $subCat->products()->count() }} products
                                        </span>
                                    </td>

                                    <!-- IN-ROW FORM FOR SUB CATEGORY -->
                                    <form action="{{ route('admin.category-offers.save', $subCat) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="is_active" :value="active ? 1 : 0">

                                        <td class="px-6 py-3">
                                            <div class="flex items-center gap-1.5">
                                                <div class="flex items-center border border-gray-200 rounded-xl overflow-hidden bg-white focus-within:ring-2 focus-within:ring-brand-500">
                                                    <input type="number" step="1" min="0" name="discount_value"
                                                           x-model.number="val"
                                                           value="{{ old('discount_value', (int)($subOffer?->discount_value ?? 0)) }}"
                                                           onkeydown="if(event.key === '.' || event.key === ',' || event.key === 'e' || event.key === 'E' || event.key === '+' || event.key === '-') event.preventDefault();"
                                                           oninput="this.value = this.value.replace(/[^0-9]/g, '');"
                                                           class="w-18 px-2.5 py-1.5 text-xs text-center font-bold text-gray-900 bg-transparent focus:outline-none"
                                                           placeholder="0">
                                                    <select name="discount_type" x-model="type" class="bg-gray-50 text-[11px] font-bold text-gray-700 px-2 py-1.5 border-l border-gray-200 focus:outline-none">
                                                        <option value="percentage" {{ ($subOffer?->discount_type ?? 'percentage') === 'percentage' ? 'selected' : '' }}>%</option>
                                                        <option value="fixed" {{ ($subOffer?->discount_type ?? '') === 'fixed' ? 'selected' : '' }}>৳</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($subCat->children && $subCat->children->count() > 0)
                                                <label class="flex items-center gap-2 cursor-pointer text-xs font-medium text-gray-600">
                                                    <input type="checkbox" name="include_subcategories" value="1"
                                                           {{ ($subOffer?->include_subcategories ?? true) ? 'checked' : '' }}
                                                           class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                                    <span>Include children</span>
                                                </label>
                                            @else
                                                <span class="text-xs text-gray-400">Direct Only</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="inline-flex items-center gap-2.5">
                                                <!-- Animated Toggle Switch -->
                                                <button type="button"
                                                        @click="toggleCategory({{ $subCat->id }}, '{{ route('admin.category-offers.toggle', $subCat) }}', $data)"
                                                        :disabled="loading"
                                                        :class="active ? 'bg-emerald-500' : 'bg-gray-300'"
                                                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 disabled:opacity-50"
                                                        role="switch" :aria-checked="active">
                                                    <span class="sr-only">Toggle offer status</span>
                                                    <span :class="active ? 'translate-x-5' : 'translate-x-0'"
                                                          class="pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out">
                                                        <span :class="active ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in'"
                                                              class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity text-[9px] text-gray-400 font-bold select-none">
                                                            ✕
                                                        </span>
                                                        <span :class="active ? 'opacity-100 duration-200 ease-in' : 'opacity-0 duration-100 ease-out'"
                                                              class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity text-[10px] text-emerald-600 font-bold select-none">
                                                            ✓
                                                        </span>
                                                    </span>
                                                </button>
                                                <span class="text-xs font-semibold w-10 text-left transition-colors"
                                                      :class="active ? 'text-emerald-700' : 'text-gray-400'"
                                                      x-text="active ? 'Active' : 'Off'">
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-3 text-right">
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-brand-600 hover:text-white text-gray-700 text-xs font-semibold transition cursor-pointer">
                                                <i class="ph ph-floppy-disk text-xs"></i>
                                                Save
                                            </button>
                                        </td>
                                    </form>
                                </tr>
                            @endforeach
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                কোনো ক্যাটাগরি পাওয়া যায়নি।
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
