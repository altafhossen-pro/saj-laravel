@extends('layouts.admin')

@section('title', 'Homepage Category Sections')
@section('header_title', 'Category Sections')

@section('content')
@php
    $categoriesJson = $categories->map(function($c) {
        return [
            'id' => $c->id,
            'name' => $c->name,
            'slug' => $c->slug,
            'parent_id' => $c->parent_id,
            'parent_name' => $c->parent ? $c->parent->name : null,
            'image' => $c->image ? (\Illuminate\Support\Str::startsWith($c->image, ['http://', 'https://']) ? $c->image : \Illuminate\Support\Facades\Storage::url($c->image)) : null,
            'is_featured' => (bool)$c->is_featured,
            'children_count' => (int)$c->children_count,
            'products_count' => (int)$c->products_count,
            'homepage_layout' => $c->homepage_layout ?? 'grid',
            'homepage_max_products' => (int)($c->homepage_max_products ?? 100),
            'homepage_per_page' => (int)($c->homepage_per_page ?? 20),
            'show_on_homepage' => (bool)$c->show_on_homepage,
            'sort_order' => (int)($c->sort_order ?? 0),
        ];
    });
@endphp

<div x-data="categorySectionsManager()" class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Homepage Category Sections</h1>
            <p class="text-sm text-gray-500 mt-1">Manage which categories (main & subcategories) appear as dedicated product sections on your homepage, their display order, and layouts.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.categories.index') }}" 
                class="px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-xl font-medium hover:bg-gray-50 text-sm transition-colors flex items-center gap-2 shadow-sm">
                <i class="ph ph-folder text-base text-gray-500"></i> Manage All Categories
            </a>
            <a href="{{ url('/') }}" target="_blank"
                class="px-4 py-2 bg-brand-600 text-white rounded-xl font-medium hover:bg-brand-700 text-sm transition-colors flex items-center gap-2 shadow-sm shadow-brand-200">
                <i class="ph ph-arrow-square-out text-base"></i> View Homepage
            </a>
        </div>
    </div>

    <!-- Info Helper Card -->
    <div class="p-4 bg-blue-50/70 border border-blue-200/80 rounded-2xl flex items-start gap-3.5 text-blue-900">
        <div class="w-8 h-8 rounded-xl bg-blue-100 flex items-center justify-center flex-shrink-0 text-blue-600 mt-0.5">
            <i class="ph ph-info text-xl"></i>
        </div>
        <div class="text-sm">
            <h4 class="font-semibold text-blue-950 mb-0.5">Main Categories & Subcategories Reordering</h4>
            <p class="text-blue-800 leading-relaxed">
                Both main categories and subcategories can be showcased as homepage sections! 
                Click the <span class="font-semibold">Up (▲)</span> or <span class="font-semibold">Down (▼)</span> arrows to reorder. 
                The new order will be <span class="font-bold underline decoration-blue-300">saved automatically</span>. 
                Sections with "Show on Homepage" turned ON appear in this exact order under Trending Now.
            </p>
        </div>
    </div>

    <!-- Filters & Table Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <!-- Controls bar -->
        <div class="p-5 border-b border-gray-100 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 bg-gray-50/40">
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative w-full sm:w-64">
                    <i class="ph ph-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" x-model="search" placeholder="Search categories..."
                        class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-white">
                </div>
                <select x-model="filterStatus"
                    class="py-2 px-3 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-white min-w-[160px]">
                    <option value="all">All Homepage Status</option>
                    <option value="enabled">Active on Homepage</option>
                    <option value="disabled">Hidden from Homepage</option>
                </select>
                <select x-model="filterType"
                    class="py-2 px-3 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-white min-w-[150px]">
                    <option value="all">All Levels</option>
                    <option value="root">Main Categories Only</option>
                    <option value="sub">Subcategories Only</option>
                </select>
            </div>

            <div class="flex items-center gap-2 text-xs text-gray-500 font-medium">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-brand-50 text-brand-700 font-semibold border border-brand-100">
                    <span class="w-2 h-2 rounded-full bg-brand-600 animate-pulse"></span>
                    <span x-text="enabledCount"></span> Active on Homepage
                </span>
                <span class="text-gray-400">|</span>
                <span>Total: <span x-text="items.length"></span> categories</span>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-gray-50 text-gray-500 font-medium border-b border-gray-100 uppercase text-[11px] tracking-wider">
                    <tr>
                        <th class="px-6 py-4 w-28 text-center">Reorder</th>
                        <th class="px-6 py-4">Category</th>
                        <th class="px-6 py-4">Hierarchy & Products</th>
                        <th class="px-6 py-4">Layout Style</th>
                        <th class="px-6 py-4 text-center">Show on Homepage</th>
                        <th class="px-6 py-4 text-right">Settings</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="(category, index) in filteredItems" :key="category.id">
                        <tr class="hover:bg-gray-50/70 transition-colors">
                            
                            <!-- Up & Down Arrows for Reordering -->
                            <td class="px-6 py-4 text-center">
                                <div class="inline-flex items-center justify-center gap-2 bg-gray-50 border border-gray-200/80 px-2.5 py-1 rounded-xl shadow-2xs">
                                    <span class="w-6 text-center font-mono font-bold text-xs text-gray-500" x-text="'#' + (index + 1)"></span>
                                    <div class="flex flex-col gap-0.5">
                                        <button type="button" 
                                            @click="moveUp(index)"
                                            :disabled="index === 0"
                                            class="w-6 h-5 rounded flex items-center justify-center text-gray-600 hover:text-brand-600 hover:bg-brand-50 disabled:opacity-20 disabled:hover:bg-transparent disabled:hover:text-gray-600 disabled:cursor-not-allowed transition-colors cursor-pointer"
                                            title="Move Up">
                                            <i class="ph ph-caret-up font-bold text-sm"></i>
                                        </button>
                                        <button type="button" 
                                            @click="moveDown(index)"
                                            :disabled="index === filteredItems.length - 1"
                                            class="w-6 h-5 rounded flex items-center justify-center text-gray-600 hover:text-brand-600 hover:bg-brand-50 disabled:opacity-20 disabled:hover:bg-transparent disabled:hover:text-gray-600 disabled:cursor-not-allowed transition-colors cursor-pointer"
                                            title="Move Down">
                                            <i class="ph ph-caret-down font-bold text-sm"></i>
                                        </button>
                                    </div>
                                </div>
                            </td>

                            <!-- Category Info -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3.5">
                                    <template x-if="category.image">
                                        <img :src="category.image" :alt="category.name"
                                            class="w-11 h-11 rounded-xl object-cover border border-gray-200/80 shadow-xs flex-shrink-0">
                                    </template>
                                    <template x-if="!category.image">
                                        <div class="w-11 h-11 rounded-xl bg-gray-100 border border-gray-200 flex items-center justify-center text-gray-400 flex-shrink-0">
                                            <i class="ph ph-image text-xl"></i>
                                        </div>
                                    </template>

                                    <div>
                                        <div class="font-semibold text-gray-900 flex items-center gap-2">
                                            <span x-text="category.name"></span>
                                            <template x-if="category.is_featured">
                                                <span class="px-2 py-0.5 text-[10px] uppercase font-bold bg-amber-100 text-amber-700 rounded-full">Featured</span>
                                            </template>
                                        </div>
                                        <div class="flex items-center gap-2 mt-1">
                                            <template x-if="!category.parent_id">
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-gray-100 text-gray-600 border border-gray-200">
                                                    Main Category
                                                </span>
                                            </template>
                                            <template x-if="category.parent_id">
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[11px] font-medium bg-amber-50 text-amber-800 border border-amber-200/80">
                                                    <i class="ph ph-arrow-bend-down-right text-amber-600"></i>
                                                    <span>Sub of: <strong x-text="category.parent_name"></strong></span>
                                                </span>
                                            </template>
                                            <span class="text-xs text-gray-400 font-mono" x-text="'/' + category.slug"></span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Hierarchy & Stats -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <template x-if="category.children_count > 0">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200/60" title="Subcategories count">
                                            <i class="ph ph-folders text-gray-500"></i>
                                            <span x-text="category.children_count + ' subcategories'"></span>
                                        </span>
                                    </template>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-100" title="Direct products in this category">
                                        <i class="ph ph-package text-indigo-500"></i>
                                        <span x-text="category.products_count + ' products'"></span>
                                    </span>
                                </div>
                            </td>

                            <!-- Layout Style badge -->
                            <td class="px-6 py-4">
                                <div>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold"
                                        :class="category.homepage_layout === 'slider' ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'">
                                        <i class="ph text-sm" :class="category.homepage_layout === 'slider' ? 'ph-arrows-horizontal' : 'ph-squares-four'"></i>
                                        <span class="capitalize" x-text="category.homepage_layout || 'Grid'"></span>
                                    </span>
                                    <div class="text-[11px] text-gray-400 mt-1">
                                        Max: <span x-text="category.homepage_max_products || 100"></span>
                                        <template x-if="(category.homepage_layout || 'grid') === 'grid'">
                                            <span> | Per load: <span x-text="category.homepage_per_page || 20"></span></span>
                                        </template>
                                    </div>
                                </div>
                            </td>

                            <!-- Toggle Switch -->
                            <td class="px-6 py-4 text-center">
                                <div class="inline-flex items-center justify-center">
                                    <button type="button"
                                        @click="toggleHomepage(category)"
                                        :class="category.show_on_homepage ? 'bg-brand-600' : 'bg-gray-200'"
                                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2"
                                        role="switch">
                                        <span aria-hidden="true"
                                            :class="category.show_on_homepage ? 'translate-x-5' : 'translate-x-0'"
                                            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"></span>
                                    </button>
                                </div>
                            </td>

                            <!-- Settings Button -->
                            <td class="px-6 py-4 text-right">
                                <button type="button"
                                    @click="openSettings(category)"
                                    class="p-2 text-gray-600 hover:text-brand-600 hover:bg-brand-50 rounded-xl transition-all cursor-pointer border border-transparent hover:border-brand-200"
                                    title="Configure Layout and Limits">
                                    <i class="ph ph-gear text-lg"></i>
                                </button>
                            </td>
                        </tr>
                    </template>

                    <!-- Empty state -->
                    <template x-if="filteredItems.length === 0">
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="ph ph-folder-notch-open text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-base font-semibold text-gray-900">No categories found</p>
                                    <p class="text-sm text-gray-500 mt-1">Try adjusting your search or filters.</p>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal for Homepage Settings -->
    <template x-teleport="body">
        <div x-show="isSettingsOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="isSettingsOpen" 
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0" 
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200" 
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0" 
                    class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
                    @click="closeSettings"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                <div x-show="isSettingsOpen" 
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-2xl shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">

                    <div class="absolute top-0 right-0 pt-5 pr-5">
                        <button type="button" @click="closeSettings" class="text-gray-400 hover:text-gray-600 rounded-lg p-1 hover:bg-gray-100 transition-colors">
                            <i class="ph ph-x text-xl"></i>
                        </button>
                    </div>

                    <div class="sm:flex sm:items-start">
                        <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto bg-brand-50 text-brand-600 rounded-2xl sm:mx-0 sm:h-11 sm:w-11 border border-brand-100">
                            <i class="ph ph-sliders text-2xl"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg font-bold leading-6 text-gray-900">
                                Section Settings: <span x-text="activeItem ? activeItem.name : ''" class="text-brand-600"></span>
                            </h3>
                            <p class="text-xs text-gray-500 mt-1">Configure layout style and product display limits for this section.</p>

                            <div class="mt-5 space-y-4">
                                <!-- Layout Selection -->
                                <div>
                                    <label class="block text-xs font-semibold uppercase text-gray-600 tracking-wider mb-1.5">Display Layout</label>
                                    <div class="grid grid-cols-2 gap-3">
                                        <label class="flex items-center gap-3 p-3 border rounded-xl cursor-pointer hover:bg-gray-50 transition-colors"
                                            :class="settingsForm.layout === 'grid' ? 'border-brand-500 bg-brand-50/40' : 'border-gray-200'">
                                            <input type="radio" name="modal_layout" value="grid" x-model="settingsForm.layout" class="text-brand-600 focus:ring-brand-500">
                                            <div>
                                                <div class="font-semibold text-xs text-gray-900">Grid Layout</div>
                                                <div class="text-[11px] text-gray-500">With Load More button</div>
                                            </div>
                                        </label>
                                        <label class="flex items-center gap-3 p-3 border rounded-xl cursor-pointer hover:bg-gray-50 transition-colors"
                                            :class="settingsForm.layout === 'slider' ? 'border-brand-500 bg-brand-50/40' : 'border-gray-200'">
                                            <input type="radio" name="modal_layout" value="slider" x-model="settingsForm.layout" class="text-brand-600 focus:ring-brand-500">
                                            <div>
                                                <div class="font-semibold text-xs text-gray-900">Slider Layout</div>
                                                <div class="text-[11px] text-gray-500">Horizontal swipe carousel</div>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Product Limits -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold uppercase text-gray-600 tracking-wider mb-1.5">Max Products</label>
                                        <input type="number" x-model="settingsForm.maxProducts" min="1"
                                            class="w-full py-2 px-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm">
                                        <p class="mt-1 text-[11px] text-gray-400">Total max products to show</p>
                                    </div>
                                    <div x-show="settingsForm.layout === 'grid'">
                                        <label class="block text-xs font-semibold uppercase text-gray-600 tracking-wider mb-1.5">Per Page (Load More)</label>
                                        <input type="number" x-model="settingsForm.perPage" min="1"
                                            class="w-full py-2 px-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm">
                                        <p class="mt-1 text-[11px] text-gray-400">Products loaded per click</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 sm:flex sm:flex-row-reverse gap-3">
                        <button type="button" @click="saveSettings()" :disabled="saving"
                            class="w-full sm:w-auto px-5 py-2.5 text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-xl shadow-sm transition-colors flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50">
                            <span x-show="!saving">Save Changes</span>
                            <span x-show="saving" class="flex items-center gap-2"><i class="ph ph-spinner animate-spin"></i> Saving...</span>
                        </button>
                        <button type="button" @click="closeSettings()"
                            class="mt-2 sm:mt-0 w-full sm:w-auto px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 rounded-xl transition-colors cursor-pointer">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- Toast Notification -->
    <div x-show="toast.show" 
        x-transition:enter="transform ease-out duration-300 transition"
        x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed bottom-6 right-6 z-50 max-w-sm w-full bg-gray-900 text-white px-4 py-3 rounded-xl shadow-xl flex items-center gap-3 border border-gray-800"
        style="display: none;">
        <i class="ph ph-check-circle text-emerald-400 text-xl flex-shrink-0"></i>
        <span class="text-sm font-medium" x-text="toast.message"></span>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('categorySectionsManager', () => ({
            search: '',
            filterStatus: 'all',
            filterType: 'all',
            items: @json($categoriesJson),

            // Modal state
            isSettingsOpen: false,
            saving: false,
            activeItem: null,
            settingsForm: {
                layout: 'grid',
                maxProducts: 100,
                perPage: 20
            },

            // Toast state
            toast: {
                show: false,
                message: ''
            },

            get enabledCount() {
                return this.items.filter(item => item.show_on_homepage).length;
            },

            get filteredItems() {
                let list = this.items;
                if (this.filterStatus === 'enabled') {
                    list = list.filter(item => item.show_on_homepage);
                } else if (this.filterStatus === 'disabled') {
                    list = list.filter(item => !item.show_on_homepage);
                }

                if (this.filterType === 'root') {
                    list = list.filter(item => !item.parent_id);
                } else if (this.filterType === 'sub') {
                    list = list.filter(item => !!item.parent_id);
                }

                if (this.search.trim()) {
                    const q = this.search.toLowerCase().trim();
                    list = list.filter(item => 
                        item.name.toLowerCase().includes(q) || 
                        item.slug.toLowerCase().includes(q) ||
                        (item.parent_name && item.parent_name.toLowerCase().includes(q))
                    );
                }
                return list;
            },

            showToast(message) {
                this.toast.message = message;
                this.toast.show = true;
                setTimeout(() => {
                    this.toast.show = false;
                }, 3000);
            },

            moveUp(index) {
                if (index <= 0) return;
                const currentItem = this.filteredItems[index];
                const prevItem = this.filteredItems[index - 1];

                const realIdxCurrent = this.items.findIndex(i => i.id === currentItem.id);
                const realIdxPrev = this.items.findIndex(i => i.id === prevItem.id);

                if (realIdxCurrent !== -1 && realIdxPrev !== -1) {
                    const temp = this.items[realIdxCurrent];
                    this.items[realIdxCurrent] = this.items[realIdxPrev];
                    this.items[realIdxPrev] = temp;
                    this.items = [...this.items];
                    this.saveOrder();
                }
            },

            moveDown(index) {
                if (index >= this.filteredItems.length - 1) return;
                const currentItem = this.filteredItems[index];
                const nextItem = this.filteredItems[index + 1];

                const realIdxCurrent = this.items.findIndex(i => i.id === currentItem.id);
                const realIdxNext = this.items.findIndex(i => i.id === nextItem.id);

                if (realIdxCurrent !== -1 && realIdxNext !== -1) {
                    const temp = this.items[realIdxCurrent];
                    this.items[realIdxCurrent] = this.items[realIdxNext];
                    this.items[realIdxNext] = temp;
                    this.items = [...this.items];
                    this.saveOrder();
                }
            },

            saveOrder() {
                const orderIds = this.items.map(i => i.id);
                fetch('{{ route('admin.homepage.category-sections.reorder') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        order: orderIds
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.showToast('Sorting saved automatically!');
                    }
                })
                .catch(err => {
                    console.error(err);
                });
            },

            toggleHomepage(category) {
                const newState = !category.show_on_homepage;
                category.show_on_homepage = newState;

                fetch(`/admin/dashboard/categories/${category.id}/homepage-settings`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        show_on_homepage: newState
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.showToast(newState ? `"${category.name}" added to Homepage!` : `"${category.name}" hidden from Homepage.`);
                    } else {
                        category.show_on_homepage = !newState;
                        alert('Error updating category.');
                    }
                })
                .catch(err => {
                    category.show_on_homepage = !newState;
                    console.error(err);
                    alert('Error connecting to server.');
                });
            },

            openSettings(category) {
                this.activeItem = category;
                this.settingsForm.layout = category.homepage_layout || 'grid';
                this.settingsForm.maxProducts = category.homepage_max_products || 100;
                this.settingsForm.perPage = category.homepage_per_page || 20;
                this.isSettingsOpen = true;
            },

            closeSettings() {
                this.isSettingsOpen = false;
            },

            saveSettings() {
                if (!this.activeItem) return;
                this.saving = true;

                fetch(`/admin/dashboard/categories/${this.activeItem.id}/homepage-settings`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        homepage_layout: this.settingsForm.layout,
                        homepage_max_products: parseInt(this.settingsForm.maxProducts) || 100,
                        homepage_per_page: parseInt(this.settingsForm.perPage) || 20
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.activeItem.homepage_layout = this.settingsForm.layout;
                        this.activeItem.homepage_max_products = parseInt(this.settingsForm.maxProducts) || 100;
                        this.activeItem.homepage_per_page = parseInt(this.settingsForm.perPage) || 20;
                        this.closeSettings();
                        this.showToast('Section settings saved successfully!');
                    }
                    this.saving = false;
                })
                .catch(err => {
                    console.error(err);
                    this.saving = false;
                });
            }
        }));
    });
</script>
@endpush
