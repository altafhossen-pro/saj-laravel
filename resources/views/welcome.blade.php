@extends('layouts.app')

@php
    $defaultSiteName = \App\Models\Setting::get('site_name', 'SAJ');
    $dynamicTitle = \App\Models\Setting::get('site_title') ?: "{$defaultSiteName} - Online Shopping in Bangladesh | Best Deals & Fast Delivery";
    $dynamicDesc = \App\Models\Setting::get('site_meta_description') ?: "Discover top quality trending products at best prices on {$defaultSiteName}. Fast delivery all across Bangladesh with Cash on Delivery.";
    $dynamicKeywords = \App\Models\Setting::get('site_meta_keywords') ?: "saj, saj bd, jewelry bd, cosmetics bangladesh, online shopping";
    $dynamicOgImage = \App\Models\Setting::get('site_og_image') ? url(\App\Models\Setting::get('site_og_image')) : (\App\Models\Setting::get('site_logo') ? url(\App\Models\Setting::get('site_logo')) : '');
@endphp

@section('title', $dynamicTitle)

@section('meta')
    <meta name="description" content="{{ $dynamicDesc }}">
    <meta name="keywords" content="{{ $dynamicKeywords }}">
    <link rel="canonical" href="{{ url('/') }}">
    
    <!-- Open Graph (Facebook, WhatsApp, Messenger) -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:title" content="{{ $dynamicTitle }}">
    <meta property="og:description" content="{{ $dynamicDesc }}">
    @if($dynamicOgImage)
        <meta property="og:image" content="{{ $dynamicOgImage }}">
    @endif

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $dynamicTitle }}">
    <meta name="twitter:description" content="{{ $dynamicDesc }}">
    @if($dynamicOgImage)
        <meta name="twitter:image" content="{{ $dynamicOgImage }}">
    @endif
@endsection

@section('content')
    <div class="px-4 lg:px-8 py-6 space-y-10 pb-20">
        {{-- SEO Accessible H1: Hidden from UI layout, fully crawlable by Google & Screen Readers --}}
        <h1 class="sr-only">{{ \App\Models\Setting::get('site_name', 'SAJ') }} - Online Shopping in Bangladesh | Best Deals & Fast Delivery</h1>

        @stack('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
        <style>
            /* Prevent Swiper FOUC (Flash of Unstyled Content) */
            .swiper:not(.swiper-initialized) .swiper-wrapper {
                opacity: 0 !important;
                visibility: hidden;
                position: absolute !important;
                top: 0;
                left: 0;
                width: 100%;
            }
            .swiper-initialized .swiper-skeleton {
                display: none !important;
                opacity: 0;
            }
        </style>

        @if(isset($heroSlides) && $heroSlides->count() > 0)
            <section class="relative rounded-2xl overflow-hidden bg-gray-100 border border-gray-100/80 w-full aspect-[16/9] md:aspect-[21/6] lg:aspect-[4/1] shadow-xs group mb-8">
                <div class="swiper hero-swiper w-full h-full relative">
                    <!-- Hero Skeleton Placeholder before Swiper / Image loads -->
                    <div class="swiper-skeleton absolute inset-0 z-10 bg-gradient-to-r from-gray-100 via-gray-200/60 to-gray-100 animate-pulse flex items-center px-8 md:px-16 pointer-events-none rounded-2xl">
                        <div class="w-full flex items-center justify-between">
                            <div class="space-y-3 max-w-sm md:max-w-md">
                                <div class="h-3.5 w-24 bg-gray-300/70 rounded-full"></div>
                                <div class="h-6 md:h-10 w-3/4 bg-gray-300/70 rounded-xl"></div>
                                <div class="h-3.5 w-1/2 bg-gray-300/70 rounded-md"></div>
                            </div>
                            <div class="hidden sm:block w-28 h-28 md:w-40 md:h-40 rounded-full bg-gray-300/50 shrink-0"></div>
                        </div>
                    </div>

                    <div class="swiper-wrapper">
                        @foreach($heroSlides as $slide)
                            <div class="swiper-slide h-full w-full relative bg-gray-100 flex items-center justify-center overflow-hidden">
                                @if($slide->link)
                                    <a href="{{ $slide->link }}" class="block w-full h-full absolute inset-0 z-20"></a>
                                @endif
                                
                                @if($slide->type === 'image')
                                    @if($slide->mobile_content)
                                        <img src="{{ Str::startsWith($slide->mobile_content, ['http://', 'https://']) ? $slide->mobile_content : asset($slide->mobile_content) }}"
                                            class="w-full h-full object-cover md:hidden block"
                                            alt="Hero Banner Mobile - {{ \App\Models\Setting::get('site_name', 'SAJ') }}"
                                            fetchpriority="{{ $loop->first ? 'high' : 'auto' }}"
                                            loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                                            decoding="async">
                                        <img src="{{ Str::startsWith($slide->content, ['http://', 'https://']) ? $slide->content : asset($slide->content) }}"
                                            class="w-full h-full object-cover hidden md:block"
                                            alt="Hero Banner Desktop - {{ \App\Models\Setting::get('site_name', 'SAJ') }}"
                                            fetchpriority="{{ $loop->first ? 'high' : 'auto' }}"
                                            loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                                            decoding="async">
                                    @else
                                        <img src="{{ Str::startsWith($slide->content, ['http://', 'https://']) ? $slide->content : asset($slide->content) }}"
                                            class="w-full h-full object-cover"
                                            alt="Hero Banner - {{ \App\Models\Setting::get('site_name', 'SAJ') }}"
                                            fetchpriority="{{ $loop->first ? 'high' : 'auto' }}"
                                            loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                                            decoding="async">
                                    @endif
                                @else
                                    <div class="w-full h-full absolute inset-0 z-10 hidden md:block">
                                        {!! $slide->content !!}
                                    </div>
                                    <div class="w-full h-full absolute inset-0 z-10 block md:hidden">
                                        {!! $slide->mobile_content ?? $slide->content !!}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <!-- Slider Dots -->
                    <div class="swiper-pagination !bottom-4 !z-30 hero-pagination"></div>
                    <!-- Navigation arrows -->
                    <div class="swiper-button-next !text-white !right-4 !z-30 opacity-0 group-hover:opacity-100 transition-all duration-300 after:!text-xl bg-black/20 hover:bg-black/40 backdrop-blur-sm !w-10 !h-10 rounded-full flex items-center justify-center shadow-lg"></div>
                    <div class="swiper-button-prev !text-white !left-4 !z-30 opacity-0 group-hover:opacity-100 transition-all duration-300 after:!text-xl bg-black/20 hover:bg-black/40 backdrop-blur-sm !w-10 !h-10 rounded-full flex items-center justify-center shadow-lg"></div>
                </div>
            </section>
        @endif


        <!-- Categories Section -->
        <section class="relative">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                    <i class="ph-fill ph-squares-four text-brand-500"></i> Explore Categories
                </h2>

                <!-- Swiper Navigation -->
                <div class="flex gap-2">
                    <button
                        class="swiper-button-prev-cat w-8 h-8 rounded-full bg-white shadow-sm border border-gray-100 flex items-center justify-center text-gray-600 hover:text-brand-600 hover:border-brand-200 transition-colors cursor-pointer">
                        <i class="ph ph-caret-left"></i>
                    </button>
                    <button
                        class="swiper-button-next-cat w-8 h-8 rounded-full bg-white shadow-sm border border-gray-100 flex items-center justify-center text-gray-600 hover:text-brand-600 hover:border-brand-200 transition-colors cursor-pointer">
                        <i class="ph ph-caret-right"></i>
                    </button>
                </div>
            </div>

            <div class="swiper category-swiper -mx-2 px-2 py-4 relative">
                <!-- Skeleton Placeholder before JS loads -->
                <div class="swiper-skeleton flex gap-4 overflow-hidden px-2 z-10 bg-gray-50 pointer-events-none">
                    @for($i=0; $i<8; $i++)
                        <div class="flex flex-col items-center p-3 bg-white rounded-xl border border-gray-100 shadow-xs shrink-0 w-[calc(50%-8px)] sm:w-[calc(33.333%-11px)] md:w-[calc(20%-13px)] lg:w-[calc(16.666%-14px)] xl:w-[calc(12.5%-14px)]">
                            <div class="w-16 h-16 sm:w-20 sm:h-20 bg-gray-200 animate-pulse rounded-full mb-3 shrink-0"></div>
                            <div class="w-16 h-3.5 bg-gray-200 animate-pulse rounded shrink-0"></div>
                        </div>
                    @endfor
                </div>
                <div class="swiper-wrapper">
                    @if(isset($sidebarCategories) && $sidebarCategories->count() > 0)
                        @foreach($sidebarCategories as $category)
                            <div class="swiper-slide mb-2">
                                <a href="{{ route('shop.category', ['category_slug' => $category->slug]) }}"
                                    class="flex flex-col items-center p-3 bg-white rounded-xl border border-gray-100 shadow-xs hover:border-pink-300 hover:shadow-md transition-all duration-300 ease-in-out group h-full">
                                    <div
                                        class="w-16 h-16 sm:w-20 sm:h-20 flex items-center justify-center bg-gray-50 rounded-full mb-3 group-hover:scale-105 transition-transform duration-300 ease-in-out overflow-hidden shrink-0">
                                        @if($category->image)
                                            <img src="{{ Str::startsWith($category->image, ['http://', 'https://']) ? $category->image : Storage::url($category->image) }}"
                                                alt="{{ $category->name }} Category - {{ \App\Models\Setting::get('site_name', 'SAJ') }}"
                                                class="w-full h-full object-cover">
                                        @else
                                            <i class="ph ph-squares-four text-2xl sm:text-3xl text-gray-400 group-hover:text-pink-500 transition-colors"></i>
                                        @endif
                                    </div>
                                    <span
                                        class="text-xs sm:text-sm font-semibold text-gray-700 group-hover:text-pink-600 text-center line-clamp-1 w-full transition-colors">{{ $category->name }}</span>
                                </a>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </section>

        <!-- Trending Now -->
        <!-- Trending Now -->
        @if(isset($trendingProducts) && $trendingProducts->count() > 0)
            <section class="relative" x-data="{ 
                products: {{ $trendingProducts->toJson() }},
                layout: '{{ $trendingSettings['layout'] }}',
                loading: false,
                page: 1,
                maxProducts: {{ $trendingSettings['layout'] === 'grid' ? $trendingSettings['grid_max'] : $trendingSettings['slider_max'] }},
                perPage: {{ $trendingSettings['layout'] === 'grid' ? $trendingSettings['grid_load_more'] : 100 }},
                autoLoadCount: 0,
                maxAutoLoads: 0, // Disabled infinite scroll
                loadMore(isAuto = false) {
                    if (this.layout !== 'grid') return;
                    if (this.products.length >= this.maxProducts || !window.allTrendingProducts) return;
                    if (this.products.length >= window.allTrendingProducts.length) return;
                    if (isAuto && this.autoLoadCount >= this.maxAutoLoads) return;

                    this.loading = true;
                    setTimeout(() => {
                        let nextItems = window.allTrendingProducts.slice(this.page * this.perPage, (this.page + 1) * this.perPage);
                        this.products = [...this.products, ...nextItems];
                        this.page++;
                        if (isAuto) this.autoLoadCount++;
                        this.loading = false;
                    }, 400); // Small fake delay for UX
                }
            }" x-init="
                if(layout === 'grid') {
                    window.allTrendingProducts = products;
                    products = window.allTrendingProducts.slice(0, {{ $trendingSettings['grid_initial'] }});
                } else {
                    setTimeout(() => {
                        let swiperConfig = {
                            slidesPerView: 2,
                            spaceBetween: 16,
                            loop: true,
                            navigation: {
                                nextEl: '.swiper-button-next-trending',
                                prevEl: '.swiper-button-prev-trending',
                            },
                            breakpoints: {
                                640: { slidesPerView: 2 },
                                768: { slidesPerView: 3 },
                                1024: { slidesPerView: 4 },
                                1280: { slidesPerView: 5 },
                                1536: { slidesPerView: 5 },
                            }
                        };

                        if ('{{ $trendingSettings["slider_autoplay"] ?? "true" }}' === 'true') {
                            swiperConfig.autoplay = {
                                delay: {{ $trendingSettings["slider_autoplay_duration"] ?? 3000 }},
                                disableOnInteraction: false,
                            };
                        }

                        new Swiper('.hp-product-swiper-trending', swiperConfig);
                    }, 100);
                }
            ">
                <div class="flex items-center justify-between mb-4 border-b border-gray-200 pb-2">
                    <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                        <i class="ph-fill ph-trend-up text-brand-500"></i> Trending Now
                    </h2>
                </div>

                @if($trendingSettings['layout'] === 'slider')
                    <div class="relative">
                        <!-- Swiper Navigation -->
                        <button
                            class="swiper-button-prev-trending absolute left-0 top-1/2 -translate-y-1/2 -ml-4 z-10 w-10 h-10 rounded-full bg-white shadow-md border border-gray-100 flex items-center justify-center text-gray-600 hover:text-brand-600 hover:border-brand-200 transition-colors cursor-pointer hidden md:flex">
                            <i class="ph ph-caret-left text-lg"></i>
                        </button>
                        <button
                            class="swiper-button-next-trending absolute right-0 top-1/2 -translate-y-1/2 -mr-4 z-10 w-10 h-10 rounded-full bg-white shadow-md border border-gray-100 flex items-center justify-center text-gray-600 hover:text-brand-600 hover:border-brand-200 transition-colors cursor-pointer hidden md:flex">
                            <i class="ph ph-caret-right text-lg"></i>
                        </button>

                        <div class="swiper hp-product-swiper-trending -mx-2 px-2 py-4 relative">
                            <!-- Skeleton Placeholder before JS loads -->
                            <div class="swiper-skeleton flex gap-4 overflow-hidden px-2 z-10 bg-white pointer-events-none">
                                @for($i=0; $i<5; $i++)
                                    <div class="w-[calc(50%-8px)] sm:w-[calc(33.333%-11px)] md:w-[calc(25%-12px)] lg:w-[calc(20%-13px)] shrink-0">
                                        <x-frontend.skeleton-product-card />
                                    </div>
                                @endfor
                            </div>
                            
                            <div class="swiper-wrapper">
                                <template x-for="(item, index) in products" :key="index">
                                    <div class="swiper-slide mb-2">
                                        <x-frontend.product-card-alpine />
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                @endif

                @if($trendingSettings['layout'] === 'grid')
                    <div class="relative">
                        <!-- Skeleton Placeholder before JS loads -->
                        <div x-show="false" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 py-4">
                            @for($i=0; $i<5; $i++)
                                <x-frontend.skeleton-product-card />
                            @endfor
                        </div>
                        
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 py-4">
                            <template x-for="(item, index) in products" :key="index">
                                <x-frontend.product-card-alpine />
                            </template>
                        </div>

                        <div x-show="products.length < maxProducts && products.length < window.allTrendingProducts?.length && loading"
                            class="flex justify-center mt-6 mb-4" style="display: none;">
                            <div class="flex items-center gap-2 text-brand-600 font-medium bg-brand-50 px-4 py-2 rounded-full">
                                <i class="ph ph-spinner animate-spin text-xl"></i> Loading more...
                            </div>
                        </div>

                        <!-- Load More Button -->
                        <div x-show="products.length < maxProducts && products.length < window.allTrendingProducts?.length && !loading"
                            class="flex justify-center mt-6 mb-4" style="display: none;">
                            <button @click="loadMore(false)"
                                class="cursor-pointer bg-brand-50 text-brand-700 font-medium px-6 py-2 rounded-full shadow-sm hover:bg-brand-100 hover:shadow transition-all flex items-center gap-2 border border-brand-200">
                                Show More <i class="ph ph-caret-down text-lg"></i>
                            </button>
                        </div>
                    </div>
                @endif
            </section>
        @endif

        <!-- Dynamic Category Sections -->
        @if(isset($homepageCategories) && $homepageCategories->count() > 0)
            @foreach($homepageCategories as $hpCategory)
                @if(count($hpCategory->homepage_products) > 0)
                    <section class="relative" x-data="{ 
                                products: {{ $hpCategory->homepage_products->toJson() }},
                                loading: false,
                                page: 1,
                                maxProducts: {{ $hpCategory->homepage_max_products }},
                                perPage: {{ $hpCategory->homepage_per_page }},
                                autoLoadCount: 0,
                                maxAutoLoads: 2,
                                loadMore(isAuto = false) {
                                    if (this.products.length >= this.maxProducts || !window.allProducts_{{ $hpCategory->id }}) return;
                                    if (isAuto && this.autoLoadCount >= this.maxAutoLoads) return;

                                    this.loading = true;
                                    setTimeout(() => {
                                        let nextItems = window.allProducts_{{ $hpCategory->id }}.slice(this.page * this.perPage, (this.page + 1) * this.perPage);
                                        this.products = [...this.products, ...nextItems];
                                        this.page++;
                                        if (isAuto) this.autoLoadCount++;
                                        this.loading = false;
                                    }, 400); // Small fake delay for UX
                                }
                            }" x-init="
                                if('{{ $hpCategory->homepage_layout }}' === 'grid') {
                                    // Hide products beyond perPage initially
                                    window.allProducts_{{ $hpCategory->id }} = products;
                                    products = window.allProducts_{{ $hpCategory->id }}.slice(0, perPage);
                                }
                            ">
                        <div class="flex items-center justify-between mb-4 border-b border-gray-200 pb-2">
                            <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                                @if($hpCategory->image)
                                    <img src="{{ Str::startsWith($hpCategory->image, ['http://', 'https://']) ? $hpCategory->image : Storage::url($hpCategory->image) }}"
                                        class="w-6 h-6 object-cover rounded-full">
                                @else
                                    <i class="ph-fill ph-storefront text-brand-500"></i>
                                @endif
                                {{ $hpCategory->name }}
                            </h2>
                            <a href="{{ route('shop.category', ['category_slug' => $hpCategory->slug]) }}"
                                class="text-sm font-semibold text-brand-600 hover:text-brand-700 hover:underline">View All</a>
                        </div>

                        @if($hpCategory->homepage_layout == 'slider')
                            <div class="relative">
                                <!-- Swiper Navigation -->
                                <button
                                    class="swiper-button-prev-{{ $hpCategory->id }} absolute left-0 top-1/2 -translate-y-1/2 -ml-4 z-10 w-10 h-10 rounded-full bg-white shadow-md border border-gray-100 flex items-center justify-center text-gray-600 hover:text-brand-600 hover:border-brand-200 transition-colors cursor-pointer hidden md:flex">
                                    <i class="ph ph-caret-left text-lg"></i>
                                </button>
                                <button
                                    class="swiper-button-next-{{ $hpCategory->id }} absolute right-0 top-1/2 -translate-y-1/2 -mr-4 z-10 w-10 h-10 rounded-full bg-white shadow-md border border-gray-100 flex items-center justify-center text-gray-600 hover:text-brand-600 hover:border-brand-200 transition-colors cursor-pointer hidden md:flex">
                                    <i class="ph ph-caret-right text-lg"></i>
                                </button>

                                <div class="swiper hp-product-swiper-{{ $hpCategory->id }} -mx-2 px-2 py-4 relative">
                                    <!-- Skeleton Placeholder before JS loads -->
                                    <div class="swiper-skeleton flex gap-4 overflow-hidden px-2 z-10 bg-white pointer-events-none">
                                        @for($i=0; $i<5; $i++)
                                            <div class="w-[calc(50%-8px)] sm:w-[calc(33.333%-11px)] md:w-[calc(25%-12px)] lg:w-[calc(20%-13px)] shrink-0">
                                                <x-frontend.skeleton-product-card />
                                            </div>
                                        @endfor
                                    </div>

                                    <div class="swiper-wrapper">
                                        <template x-for="(item, index) in products" :key="index">
                                            <div class="swiper-slide mb-2">
                                                <x-frontend.product-card-alpine />
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="relative">
                                <!-- Skeleton Placeholder before JS loads -->
                                <div x-show="false" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 py-4">
                                    @for($i=0; $i<5; $i++)
                                        <x-frontend.skeleton-product-card />
                                    @endfor
                                </div>
                                
                                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 py-4">
                                    <template x-for="(item, index) in products" :key="index">
                                        <x-frontend.product-card-alpine />
                                    </template>
                                </div>
                            </div>

                            <div x-show="products.length < maxProducts && window['allProducts_{{ $hpCategory->id }}'] && products.length < window['allProducts_{{ $hpCategory->id }}'].length && loading"
                                class="flex justify-center mt-6 mb-4" style="display: none;">
                                <div class="flex items-center gap-2 text-brand-600 font-medium bg-brand-50 px-4 py-2 rounded-full">
                                    <i class="ph ph-spinner animate-spin text-xl"></i> Loading more...
                                </div>
                            </div>

                            <!-- Load More Button -->
                            <div x-show="products.length < maxProducts && window['allProducts_{{ $hpCategory->id }}'] && products.length < window['allProducts_{{ $hpCategory->id }}'].length && !loading"
                                class="flex justify-center mt-6 mb-4" style="display: none;">
                                <button @click="loadMore(false)"
                                    class="cursor-pointer bg-brand-50 text-brand-700 font-medium px-6 py-2 rounded-full shadow-sm hover:bg-brand-100 hover:shadow transition-all flex items-center gap-2 border border-brand-200">
                                    Show More <i class="ph ph-caret-down text-lg"></i>
                                </button>
                            </div>
                        @endif
                    </section>
                @endif
            @endforeach
        @endif

        <!-- Just For You -->
        @if(isset($justForYouProducts) && $justForYouProducts->count() > 0)
            <section x-data="{
                products: {{ json_encode($justForYouProducts->items()) }},
                page: {{ $justForYouProducts->currentPage() }},
                lastPage: {{ $justForYouProducts->lastPage() }},
                loading: false,
                async loadMore() {
                    if (this.loading || this.page >= this.lastPage) return;

                    this.loading = true;
                    try {
                        this.page++;
                        let response = await fetch('/api/homepage/just-for-you?page=' + this.page);
                        let data = await response.json();

                        if (data.data && data.data.length > 0) {
                            this.products = [...this.products, ...data.data];
                            this.lastPage = data.last_page;
                        } else {
                            this.lastPage = this.page; // No more data
                        }
                    } catch (e) {
                        console.error('Error fetching more products:', e);
                        this.page--; // Revert page so we can try again
                    } finally {
                        this.loading = false;
                    }
                },
            }">
                <div class="flex items-center justify-between mb-4 border-b border-gray-200 pb-2">
                    <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                        <i class="ph-fill ph-sparkle text-amber-500"></i> Just For You
                    </h2>
                </div>

                <div class="relative">
                    <!-- Skeleton Placeholder before JS loads -->
                    <div x-show="false" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                        @for($i=0; $i<5; $i++)
                            <x-frontend.skeleton-product-card />
                        @endfor
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                        <template x-for="(item, index) in products" :key="index">
                            <!-- Product Card -->
                            <x-frontend.product-card-alpine />
                        </template>
                    </div>

                    <!-- Infinite Scroll Sentinel -->
                    <div x-show="page < lastPage" x-intersect="loadMore()"
                        class="absolute bottom-[300px] h-1 w-full pointer-events-none"></div>
                </div>

                <!-- Infinite Scroll Loader -->
                <div x-show="loading"
                    class="flex justify-center mt-6 mb-4" style="display: none;">
                    <div class="flex items-center gap-2 text-pink-600 font-medium bg-pink-50 px-5 py-2.5 rounded-full shadow-xs border border-pink-100">
                        <i class="ph ph-spinner animate-spin text-xl"></i> Loading more products...
                    </div>
                </div>

                <!-- All Products Seen Indicator -->
                <div x-show="page >= lastPage && products.length > 0"
                    class="flex justify-center mt-8 mb-4 text-gray-400 text-sm font-medium" style="display: none;">
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 rounded-full text-gray-500 text-xs">
                        <i class="ph-fill ph-check-circle text-emerald-500 text-base"></i>
                        <span>You've seen all products!</span>
                    </div>
                </div>
            </section>
        @endif

    </div>
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Hero Swiper
                @if(isset($heroSlides) && $heroSlides->count() > 0)
                const heroSwiper = new Swiper('.hero-swiper', {
                    loop: {{ $heroSlides->count() > 1 ? 'true' : 'false' }},
                    grabCursor: true,
                    autoplay: {{ $heroSlides->count() > 1 ? '{ delay: 4000, disableOnInteraction: false }' : 'false' }},
                    pagination: {
                        el: '.hero-pagination',
                        clickable: true,
                    },
                    navigation: {
                        nextEl: '.hero-swiper .swiper-button-next',
                        prevEl: '.hero-swiper .swiper-button-prev',
                    },
                });
                @endif

                const categorySwiper = new Swiper('.category-swiper', {
                    loop: true,
                    slidesPerView: 2.3,
                    spaceBetween: 14,
                    navigation: {
                        nextEl: '.swiper-button-next-cat',
                        prevEl: '.swiper-button-prev-cat',
                    },
                    breakpoints: {
                        480: { slidesPerView: 3, spaceBetween: 14 },
                        640: { slidesPerView: 4, spaceBetween: 16 },
                        768: { slidesPerView: 5, spaceBetween: 16 },
                        1024: { slidesPerView: 6, spaceBetween: 16 },
                        1280: { slidesPerView: 8, spaceBetween: 16 },
                        1536: { slidesPerView: 8, spaceBetween: 16 },
                    }
                });

                // Initialize Dynamic Category Product Swipers
                @if(isset($homepageCategories) && $homepageCategories->count() > 0)
                    @foreach($homepageCategories as $hpCategory)
                        @if($hpCategory->homepage_layout == 'slider')
                            new Swiper('.hp-product-swiper-{{ $hpCategory->id }}', {
                                loop: true,
                                slidesPerView: 2,
                                spaceBetween: 16,
                                navigation: {
                                    nextEl: '.swiper-button-next-{{ $hpCategory->id }}',
                                    prevEl: '.swiper-button-prev-{{ $hpCategory->id }}',
                                },
                                breakpoints: {
                                    640: { slidesPerView: 2 },
                                    768: { slidesPerView: 3 },
                                    1024: { slidesPerView: 4 },
                                    1280: { slidesPerView: 5 },
                                    1536: { slidesPerView: 5 },
                                }
                            });
                        @endif
                    @endforeach
                @endif
            });
        </script>
    @endpush
@endsection
