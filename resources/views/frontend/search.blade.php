@extends('layouts.app')

@php
    $siteName = \App\Models\Setting::get('site_name', 'SAJ');
    $searchTitle = !empty($searchQuery) ? "Search: {$searchQuery} | {$siteName}" : "Search Products | {$siteName}";
@endphp

@section('title', $searchTitle)

@section('meta')
<meta name="robots" content="noindex, follow">
@endsection

@section('content')
<div class="px-4 lg:px-8 py-4 pb-20" x-data="{ filterOpen: false }">
    
    <!-- Category Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="javascript:history.back()" class="w-10 h-10 rounded-full border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-gray-50 hover:text-brand-600 transition-colors cursor-pointer shrink-0" title="Go Back">
                <i class="ph ph-arrow-left text-lg"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-800 mb-1">Search Results: "{{ $searchQuery }}"</h1>
                <div class="flex items-center text-sm text-gray-500 gap-2">
                    <a href="/" class="hover:text-brand-600 transition-colors">Home</a>
                    <i class="ph ph-caret-right text-xs"></i>
                    <span>Search</span>
                </div>
            </div>
        </div>
        
        <button @click="filterOpen = true" class="cursor-pointer flex items-center justify-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-lg shadow-sm text-sm font-medium text-gray-700 hover:border-brand-500 hover:text-brand-600 transition-colors w-full sm:w-auto">
            <i class="ph ph-faders text-lg"></i>
            Filters
        </button>
    </div>

    <!-- Product Grid -->
    @if($products->count() > 0)
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
        @foreach($products as $item)
        <!-- Product Card -->
        <x-frontend.product-card :item="$item" />
        @endforeach
    </div>
    
    <!-- Pagination -->
    <div class="mt-8 flex justify-center w-full">
        {{ $products->onEachSide(2)->links('vendor.pagination.tailwind-centered') }}
    </div>
    @else
    <div class="bg-white rounded-xl border border-gray-100 p-12 text-center">
        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-400">
            <i class="ph ph-package text-2xl"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-900 mb-1">No products found</h3>
        <p class="text-gray-500">There are currently no products available in this category.</p>
    </div>
    @endif
    <!-- Filter Slide-over Modal -->
    <div x-show="filterOpen" class="fixed inset-0 z-[60] overflow-hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true" style="display: none;">
        <div class="absolute inset-0 overflow-hidden">
            <!-- Background overlay -->
            <div x-show="filterOpen" x-transition.opacity.duration.300ms class="absolute inset-0 bg-black/50 transition-opacity" @click="filterOpen = false" aria-hidden="true"></div>

            <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                <!-- Slide-over panel -->
                <div x-show="filterOpen" 
                     x-transition:enter="transform transition ease-in-out duration-300 sm:duration-500" 
                     x-transition:enter-start="translate-x-full" 
                     x-transition:enter-end="translate-x-0" 
                     x-transition:leave="transform transition ease-in-out duration-300 sm:duration-500" 
                     x-transition:leave-start="translate-x-0" 
                     x-transition:leave-end="translate-x-full" 
                     class="pointer-events-auto w-screen max-w-xs">
                    
                    <div class="flex h-full flex-col overflow-y-scroll bg-white shadow-xl">
                        <!-- Header -->
                        <div class="flex items-center justify-between px-4 py-4 sm:px-6 border-b border-gray-100">
                            <h2 class="text-lg font-bold text-gray-900" id="slide-over-title">Filters</h2>
                            <div class="ml-3 flex h-7 items-center">
                                <button type="button" @click="filterOpen = false" class="relative rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 cursor-pointer">
                                    <span class="absolute -inset-2.5"></span>
                                    <span class="sr-only">Close panel</span>
                                    <i class="ph ph-x text-2xl"></i>
                                </button>
                            </div>
                        </div>
                        <!-- Filter Content Form -->
                        <form action="{{ request()->url() }}" method="GET" class="flex h-full flex-col" onsubmit="event.target.querySelectorAll('input').forEach(i => { if(!i.value || (i.type==='radio' && !i.checked) || i.name==='q') { if (i.name!=='q') i.removeAttribute('name'); } })">
                            <input type="hidden" name="q" value="{{ $searchQuery }}">
                            <div class="relative flex-1 px-4 py-6 sm:px-6 overflow-y-auto">
                                
                                <!-- Sort By -->
                                <div class="mb-8 border-b border-gray-100 pb-6">
                                    <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">Sort By</h3>
                                    <div class="space-y-3">
                                        @php $currentSort = request('sort', 'latest'); @endphp
                                        <div class="flex items-center">
                                            <input id="sort-latest" name="sort" value="latest" type="radio" {{ $currentSort == 'latest' ? 'checked' : '' }} class="h-4 w-4 border-gray-300 text-brand-600 focus:ring-brand-600 cursor-pointer">
                                            <label for="sort-latest" class="ml-3 text-sm text-gray-600 cursor-pointer">Latest Products</label>
                                        </div>
                                        <div class="flex items-center">
                                            <input id="sort-low-high" name="sort" value="price_low_high" type="radio" {{ $currentSort == 'price_low_high' ? 'checked' : '' }} class="h-4 w-4 border-gray-300 text-brand-600 focus:ring-brand-600 cursor-pointer">
                                            <label for="sort-low-high" class="ml-3 text-sm text-gray-600 cursor-pointer">Price: Low to High</label>
                                        </div>
                                        <div class="flex items-center">
                                            <input id="sort-high-low" name="sort" value="price_high_low" type="radio" {{ $currentSort == 'price_high_low' ? 'checked' : '' }} class="h-4 w-4 border-gray-300 text-brand-600 focus:ring-brand-600 cursor-pointer">
                                            <label for="sort-high-low" class="ml-3 text-sm text-gray-600 cursor-pointer">Price: High to Low</label>
                                        </div>
                                        <div class="flex items-center">
                                            <input id="sort-popular" name="sort" value="popular" type="radio" {{ $currentSort == 'popular' ? 'checked' : '' }} class="h-4 w-4 border-gray-300 text-brand-600 focus:ring-brand-600 cursor-pointer">
                                            <label for="sort-popular" class="ml-3 text-sm text-gray-600 cursor-pointer">Most Popular</label>
                                        </div>
                                        <div class="flex items-center">
                                            <input id="sort-rating" name="sort" value="rating" type="radio" {{ $currentSort == 'rating' ? 'checked' : '' }} class="h-4 w-4 border-gray-300 text-brand-600 focus:ring-brand-600 cursor-pointer">
                                            <label for="sort-rating" class="ml-3 text-sm text-gray-600 cursor-pointer">Top Rated</label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Price Range -->
                                <div class="mb-8 border-b border-gray-100 pb-6">
                                    <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">Price Range</h3>
                                    <div class="flex items-center justify-between gap-4">
                                        <div class="flex-1">
                                            <label for="min-price" class="sr-only">Min Price</label>
                                            <div class="relative rounded-md shadow-sm">
                                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                    <span class="text-gray-500 sm:text-sm">৳</span>
                                                </div>
                                                <input type="number" autocomplete="off" name="min_price" id="min_price" value="{{ request('min_price') }}" class="block w-full rounded-md border-0 py-2 pl-7 pr-3 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-brand-600 focus:outline-none sm:text-sm sm:leading-6" placeholder="0">
                                            </div>
                                        </div>
                                        <span class="text-gray-400">-</span>
                                        <div class="flex-1">
                                            <label for="max-price" class="sr-only">Max Price</label>
                                            <div class="relative rounded-md shadow-sm">
                                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                    <span class="text-gray-500 sm:text-sm">৳</span>
                                                </div>
                                                <input type="number" autocomplete="off" name="max_price" id="max_price" value="{{ request('max_price') }}" class="block w-full rounded-md border-0 py-2 pl-7 pr-3 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-brand-600 focus:outline-none sm:text-sm sm:leading-6" placeholder="Max">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Rating -->
                                <div class="mb-8 pb-6">
                                    <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">Rating</h3>
                                    <div class="space-y-3">
                                        @php $currentRating = request('rating'); @endphp
                                        <div class="flex items-center">
                                            <input id="rating-4" name="rating" value="4" type="radio" {{ $currentRating == '4' ? 'checked' : '' }} class="h-4 w-4 border-gray-300 text-brand-600 focus:ring-brand-600 cursor-pointer">
                                            <label for="rating-4" class="ml-3 text-sm text-gray-600 flex items-center gap-1 cursor-pointer">
                                                <div class="flex text-amber-400 text-sm"><i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i><i class="ph ph-star text-gray-300"></i></div>
                                                <span>& Up</span>
                                            </label>
                                        </div>
                                        <div class="flex items-center">
                                            <input id="rating-3" name="rating" value="3" type="radio" {{ $currentRating == '3' ? 'checked' : '' }} class="h-4 w-4 border-gray-300 text-brand-600 focus:ring-brand-600 cursor-pointer">
                                            <label for="rating-3" class="ml-3 text-sm text-gray-600 flex items-center gap-1 cursor-pointer">
                                                <div class="flex text-amber-400 text-sm"><i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i><i class="ph ph-star text-gray-300"></i><i class="ph ph-star text-gray-300"></i></div>
                                                <span>& Up</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer / Apply Button -->
                            <div class="border-t border-gray-200 px-4 py-4 sm:px-6 bg-gray-50 flex gap-3 mt-auto">
                                <a href="{{ request()->url() }}?q={{ urlencode($searchQuery) }}" class="flex-1 text-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 cursor-pointer">Clear All</a>
                                <button type="submit" class="flex-1 rounded-md bg-brand-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 cursor-pointer">Apply Filters</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.trackFbEvent === 'function' && '{{ addslashes($searchQuery) }}') {
            window.trackFbEvent('Search', {
                search_string: '{{ addslashes($searchQuery) }}',
                content_type: 'product',
                content_ids: {!! json_encode($products->pluck('id')->take(10)->map(fn($id) => (string)$id)->values()) !!}
            });
        }
    });
</script>
@endpush
