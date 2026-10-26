@extends('layouts.admin')

@section('title', 'Trending Now Settings')
@section('header_title', 'Trending Now')

@section('content')
<div x-data="trendingSearch()">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Trending Now</h1>
            <p class="text-sm text-gray-500 mt-1">Manage products displayed in the Trending Now section.</p>
        </div>
        <button type="button" @click="openModal()" class="px-5 py-2.5 bg-brand-600 text-white rounded-xl font-medium hover:bg-brand-700 shadow-sm transition-colors flex items-center gap-2 cursor-pointer">
            <i class="ph ph-plus text-lg"></i> Add Product
        </button>
    </div>

    @if($errors->any())
        <div class="mb-8 p-4 bg-red-50 text-red-700 border border-red-100 rounded-xl flex flex-col gap-1">
            @foreach($errors->all() as $error)
                <div class="flex items-center gap-2 text-sm">
                    <i class="ph-fill ph-warning-circle"></i>
                    <span>{{ $error }}</span>
                </div>
            @endforeach
        </div>
    @endif

    <form action="{{ route('admin.homepage.trending.settings.save') }}" method="POST" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8" x-data="{ layout: '{{ old('layout', $settings['layout']) }}', autoplay: '{{ old('slider_autoplay', $settings['slider_autoplay'] ?? 'true') }}' }">
        @csrf
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <div>
                <h3 class="text-lg font-bold text-gray-900">Layout Settings</h3>
                <p class="text-sm text-gray-500 mt-1">Configure how trending products appear on the homepage.</p>
            </div>
            <button type="submit" class="px-5 py-2.5 bg-gray-900 text-white rounded-xl font-medium hover:bg-black shadow-sm transition-colors cursor-pointer text-sm">
                Save Settings
            </button>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Layout Selection -->
                <div class="col-span-full md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Display Style</label>
                    <div class="flex flex-col gap-3">
                        <label class="flex items-center gap-3 p-3 border rounded-xl cursor-pointer hover:bg-gray-50 transition-colors"
                            :class="layout === 'grid' ? 'border-brand-500 bg-brand-50/30' : 'border-gray-200'">
                            <input type="radio" name="layout" value="grid" x-model="layout" class="text-brand-600 focus:ring-brand-500 border-gray-300">
                            <div>
                                <div class="font-medium text-gray-900">Grid Layout</div>
                                <div class="text-xs text-gray-500 mt-0.5">Show as a grid with "Load More"</div>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-3 border rounded-xl cursor-pointer hover:bg-gray-50 transition-colors"
                            :class="layout === 'slider' ? 'border-brand-500 bg-brand-50/30' : 'border-gray-200'">
                            <input type="radio" name="layout" value="slider" x-model="layout" class="text-brand-600 focus:ring-brand-500 border-gray-300">
                            <div>
                                <div class="font-medium text-gray-900">Slider Layout</div>
                                <div class="text-xs text-gray-500 mt-0.5">Show as a horizontal slider</div>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="col-span-full md:col-span-2 space-y-6">
                    <!-- Global Settings -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Sort Order</label>
                        <select name="sort_order" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-all">
                            <option value="latest" {{ old('sort_order', $settings['sort_order']) === 'latest' ? 'selected' : '' }}>Latest Added First</option>
                            <option value="random" {{ old('sort_order', $settings['sort_order']) === 'random' ? 'selected' : '' }}>Random Order</option>
                        </select>
                        <p class="text-xs text-gray-500">How to sort the products for both layouts.</p>
                    </div>

                    <!-- Grid Settings -->
                    <div x-show="layout === 'grid'" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Initial Count</label>
                            <input type="number" name="grid_initial" value="{{ old('grid_initial', $settings['grid_initial']) }}" min="1" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-all">
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Load More Count</label>
                            <input type="number" name="grid_load_more" value="{{ old('grid_load_more', $settings['grid_load_more']) }}" min="1" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-all">
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Max Products</label>
                            <input type="number" name="grid_max" value="{{ old('grid_max', $settings['grid_max']) }}" min="1" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-all">
                        </div>
                    </div>

                    <!-- Slider Settings -->
                    <div x-show="layout === 'slider'" style="display: none;" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Max Products</label>
                            <input type="number" name="slider_max" value="{{ old('slider_max', $settings['slider_max']) }}" min="1" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-all">
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Auto Play</label>
                            <select name="slider_autoplay" x-model="autoplay" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-all">
                                <option value="true" {{ old('slider_autoplay', $settings['slider_autoplay']) == 'true' ? 'selected' : '' }}>Yes</option>
                                <option value="false" {{ old('slider_autoplay', $settings['slider_autoplay']) == 'false' ? 'selected' : '' }}>No</option>
                            </select>
                        </div>
                        <div class="space-y-2" x-show="autoplay === 'true'">
                            <label class="block text-sm font-medium text-gray-700">Duration (ms)</label>
                            <input type="number" name="slider_autoplay_duration" value="{{ old('slider_autoplay_duration', $settings['slider_autoplay_duration']) }}" min="500" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-all">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if(session('success'))
            <div class="p-4 bg-green-50 text-green-700 border-b border-green-100 flex items-center gap-2">
                <i class="ph-fill ph-check-circle text-lg"></i>
                <span class="font-medium text-sm">{{ session('success') }}</span>
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 border-b border-gray-100">
                        <th class="py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider w-16">Image</th>
                        <th class="py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Product Title</th>
                        <th class="py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider w-32">Price</th>
                        <th class="py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider w-24 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($trendingProducts as $product)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="py-3 px-4">
                            @if($product->featured_image)
                                <img src="{{ Str::startsWith($product->featured_image, 'http') ? $product->featured_image : Storage::url($product->featured_image) }}" alt="{{ $product->title }}" class="w-12 h-12 rounded-lg object-cover border border-gray-200">
                            @else
                                <div class="w-12 h-12 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center text-gray-400">
                                    <i class="ph ph-image text-xl"></i>
                                </div>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <div class="text-sm font-medium text-gray-900 line-clamp-2">{{ $product->title }}</div>
                        </td>
                        <td class="py-3 px-4">
                            <div class="text-sm font-semibold text-gray-900">৳{{ $product->base_price }}</div>
                        </td>
                        <td class="py-3 px-4 text-right">
                            <form action="{{ route('admin.homepage.trending.remove', $product->id) }}" method="POST" class="inline-block">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors cursor-pointer" title="Remove from Trending">
                                    <i class="ph ph-trash text-lg"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="py-8 text-center text-gray-500 text-sm">
                            No trending products found. Click "Add Product" to add one.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Trending Product Modal -->
    <div x-show="isOpen" style="display: none;" class="relative z-[9999]" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Overlay -->
        <div x-show="isOpen" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0" 
             x-transition:enter-end="opacity-100" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100" 
             x-transition:leave-end="opacity-0" 
             class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" 
             @click="closeModal()"></div>

        <div class="fixed inset-0 z-[10000] overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <!-- Modal -->
                <div x-show="isOpen" 
                     x-transition:enter="ease-out duration-300" 
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                     x-transition:leave="ease-in duration-200" 
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                     class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-xl border border-gray-100"
                     @click.away="closeModal()">
                    
                    <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                        <h3 class="text-lg font-bold text-gray-900">Add to Trending</h3>
                        <button @click="closeModal()" class="text-gray-400 hover:text-gray-600 cursor-pointer p-1 hover:bg-gray-200 rounded-full transition-colors">
                            <i class="ph ph-x text-lg"></i>
                        </button>
                    </div>
                    
                    <div class="p-6 h-[400px] flex flex-col">
                        <div class="relative">
                            <i class="ph ph-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                            <input type="text" x-model="searchQuery" @input.debounce.300ms="searchProducts()" placeholder="Search products by name..." class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-all" autofocus>
                        </div>

                        <div class="mt-4 flex-1 overflow-y-auto pr-2 custom-scrollbar">
                            <div x-show="isLoading" class="flex justify-center py-8 text-brand-600">
                                <i class="ph ph-spinner animate-spin text-3xl"></i>
                            </div>

                            <div x-show="!isLoading && searchQuery.length > 0 && results.length === 0" class="text-center py-8 text-gray-500 text-sm">
                                No products found matching "<span x-text="searchQuery"></span>"
                            </div>

                            <div x-show="!isLoading && results.length > 0" class="space-y-2">
                                <template x-for="product in results" :key="product.id">
                                    <div class="flex items-center gap-3 p-3 border border-gray-100 rounded-xl hover:border-brand-200 hover:bg-brand-50/30 transition-colors">
                                        <img :src="product.image_url" class="w-12 h-12 rounded-lg object-cover border border-gray-200 shrink-0">
                                        <div class="flex-1 min-w-0 flex flex-col justify-center">
                                            <div class="text-sm font-medium text-gray-900 truncate" x-text="product.title"></div>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span class="text-xs font-semibold text-brand-600" x-text="'৳' + product.base_price"></span>
                                                <span class="text-[10px] text-gray-400">•</span>
                                                <span class="text-[11px] text-gray-500" x-text="product.variants_count > 0 ? product.variants_count + ' variants' : 'No variants'"></span>
                                            </div>
                                        </div>
                                        
                                        <div class="shrink-0">
                                            <template x-if="product.is_trending">
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
                                                    <i class="ph-fill ph-check-circle"></i> Added
                                                </span>
                                            </template>
                                            <template x-if="!product.is_trending">
                                                <button type="button" @click="addToTrending(product)" :disabled="product.isAdding" class="cursor-pointer px-4 py-1.5 bg-brand-600 text-white text-xs font-medium rounded-lg hover:bg-brand-700 transition-colors shadow-sm disabled:opacity-50 flex items-center justify-center min-w-[60px]">
                                                    <span x-show="!product.isAdding">Add</span>
                                                    <i x-show="product.isAdding" class="ph ph-spinner animate-spin text-sm" style="display: none;"></i>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<style>
    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #E5E7EB; border-radius: 20px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #D1D5DB; }
</style>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('trendingSearch', () => ({
            isOpen: false,
            searchQuery: '',
            isLoading: false,
            results: [],
            openModal() {
                this.isOpen = true;
                this.searchQuery = '';
                this.results = [];
                setTimeout(() => {
                    this.$root.querySelector('input').focus();
                }, 100);
            },
            closeModal() {
                this.isOpen = false;
            },
            async searchProducts() {
                if (this.searchQuery.length < 1) {
                    this.results = [];
                    return;
                }
                
                this.isLoading = true;
                try {
                    const response = await fetch(`/admin/dashboard/api/products/search?q=${encodeURIComponent(this.searchQuery)}`);
                    if (response.ok) {
                        const data = await response.json();
                        this.results = data.map(p => ({ ...p, isAdding: false }));
                    }
                } catch (e) {
                    console.error('Search failed', e);
                } finally {
                    this.isLoading = false;
                }
            },
            async addToTrending(product) {
                product.isAdding = true;
                try {
                    const response = await fetch('{{ route('admin.homepage.trending.add') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ product_id: product.id })
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        product.is_trending = true;
                    }
                } catch (e) {
                    console.error('Add failed', e);
                } finally {
                    product.isAdding = false;
                }
            }
        }));
    });
</script>
@endpush
@endsection
