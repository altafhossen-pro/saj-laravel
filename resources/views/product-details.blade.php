@extends('layouts.app')

@php
    $metaTitle = isset($product->seo['meta_title']) && !empty($product->seo['meta_title']) ? $product->seo['meta_title'] : $product->title;

    $metaDesc = isset($product->seo['meta_description']) && !empty($product->seo['meta_description']) ? $product->seo['meta_description'] : null;
    if (!$metaDesc) {
        $metaDesc = trim(strip_tags($product->short_description ?? $product->description));
        if (empty($metaDesc)) {
            $metaDesc = "Buy " . $product->title . " at the best price in Bangladesh. Order online and get fast home delivery.";
        }
        $metaDesc = \Illuminate\Support\Str::limit($metaDesc, 160);
    }

    $ogImage = $product->featured_image ? (\Illuminate\Support\Str::startsWith($product->featured_image, ['http://', 'https://']) ? $product->featured_image : Storage::url($product->featured_image)) : \App\Models\Setting::get('site_logo');
@endphp

@section('title', $metaTitle . ' - ' . \App\Models\Setting::get('site_name', 'SAJ'))

@section('meta')
    <meta name="description" content="{{ $metaDesc }}">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDesc }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:url" content="{{ request()->url() }}">
    <meta property="og:type" content="product">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $metaDesc }}">
    <meta name="twitter:image" content="{{ $ogImage }}">

    <!-- JSON-LD Structured Data -->
    <script type="application/ld+json">
                {
                            "@@context": "https://schema.org/",
                            "@@type": "Product",
                            "name": "{!! addslashes($product->title) !!}",
                            @php
                                $schemaImages = [];
                                if($ogImage) $schemaImages[] = $ogImage;
                                if(is_array($product->gallery)) {
                                    foreach($product->gallery as $img) {
                                        $url = \Illuminate\Support\Str::startsWith($img, ['http://', 'https://']) ? $img : Storage::url($img);
                                        if(!in_array($url, $schemaImages)) {
                                            $schemaImages[] = $url;
                                        }
                                    }
                                }
                            @endphp
                            "image": {!! json_encode(count($schemaImages) > 0 ? $schemaImages : [$ogImage]) !!},
                            "description": "{!! addslashes($metaDesc) !!}",
                            "sku": "{{ $product->variants->first()?->sku ?? $product->id }}",
                            @if($product->product_type === 'variable' && $product->variants && $product->variants->count() > 0)
                                @php
                                    $minPrice = $product->variants->min('current_price') ?? $product->base_price;
                                    $maxPrice = $product->variants->max('current_price') ?? $product->base_price;
                                @endphp
                                "offers": {
                                    "@@type": "AggregateOffer",
                                    "url": "{{ request()->url() }}",
                                    "priceCurrency": "BDT",
                                    "lowPrice": "{{ $minPrice }}",
                                    "highPrice": "{{ $maxPrice }}",
                                    "offerCount": "{{ $product->variants->count() }}",
                                    "itemCondition": "https://schema.org/NewCondition",
                                    "availability": "{{ $product->total_stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock' }}",
                                    "shippingDetails": {
                                        "@@type": "OfferShippingDetails",
                                        "shippingRate": {
                                            "@@type": "MonetaryAmount",
                                            "value": "{{ \App\Models\Setting::get('delivery_charge_inside_dhaka', 80) }}",
                                            "currency": "BDT"
                                        },
                                        "shippingDestination": {
                                            "@@type": "DefinedRegion",
                                            "addressCountry": "BD"
                                        }
                                    }
                                }
                            @else
                                "offers": {
                                    "@@type": "Offer",
                                    "url": "{{ request()->url() }}",
                                    "itemCondition": "https://schema.org/NewCondition",
                                    "availability": "{{ $product->total_stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock' }}",
                                    @if($product->original_price && $product->base_price < $product->original_price)
                                    "priceSpecification": {
                                        "@@type": "UnitPriceSpecification",
                                        "priceType": "https://schema.org/StrikethroughPrice",
                                        "price": "{{ $product->original_price }}",
                                        "priceCurrency": "BDT"
                                    },
                                    @endif
                                    "price": "{{ $product->base_price }}",
                                    "priceCurrency": "BDT",
                                    "shippingDetails": {
                                        "@@type": "OfferShippingDetails",
                                        "shippingRate": {
                                            "@@type": "MonetaryAmount",
                                            "value": "{{ \App\Models\Setting::get('delivery_charge_inside_dhaka', 80) }}",
                                            "currency": "BDT"
                                        },
                                        "shippingDestination": {
                                            "@@type": "DefinedRegion",
                                            "addressCountry": "BD"
                                        }
                                    }
                                }
                            @endif
                            @if($product->brand)
                                ,"brand": {
                                    "@@type": "Brand",
                                    "name": "{!! addslashes($product->brand->name) !!}"
                                }
                            @endif
                            @if($product->average_rating > 0)
                                ,"aggregateRating": {
                                    "@@type": "AggregateRating",
                                    "ratingValue": "{{ $product->average_rating }}",
                                    "reviewCount": "{{ $product->total_reviews }}"
                                }
                            @endif
                            @if(isset($productReviews) && $productReviews->count() > 0)
                                ,"review": [
                                    @foreach($productReviews->take(10) as $index => $review)
                                    {
                                        "@@type": "Review",
                                        "reviewRating": {
                                            "@@type": "Rating",
                                            "ratingValue": "{{ $review->rating }}",
                                            "bestRating": "5"
                                        },
                                        "author": {
                                            "@@type": "Person",
                                            "name": "{!! addslashes($review->user->name ?? 'Customer') !!}"
                                        },
                                        "reviewBody": "{!! addslashes(str_replace(["\r", "\n", '"'], [' ', ' ', '\"'], $review->review ?? '')) !!}"
                                    }{{ !$loop->last ? ',' : '' }}
                                    @endforeach
                                ]
                            @endif
                        }
            </script>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <style>
        .product-thumbnails-slider .swiper-slide {
            width: auto;
        }

        /* Tailwind Typography Reset Fixes for TinyMCE */
        .prose h1 {
            font-size: 2.25rem;
            font-weight: 700;
            margin-top: 1.5rem;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .prose h2 {
            font-size: 1.875rem;
            font-weight: 600;
            margin-top: 1.5rem;
            margin-bottom: 1rem;
            line-height: 1.3;
        }

        .prose h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-top: 1.25rem;
            margin-bottom: 0.75rem;
            line-height: 1.4;
        }

        .prose h4 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
        }

        .prose p {
            margin-top: 0.75rem;
            margin-bottom: 0.75rem;
            line-height: 1.6;
        }

        .prose ul {
            list-style-type: disc;
            padding-left: 1.5rem;
            margin-top: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .prose ol {
            list-style-type: decimal;
            padding-left: 1.5rem;
            margin-top: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .prose li {
            margin-bottom: 0.25rem;
        }

        .prose a {
            color: #3b82f6;
            text-decoration: underline;
        }

        .prose strong {
            font-weight: 600;
        }

        .prose blockquote {
            border-left: 4px solid #e5e7eb;
            padding-left: 1rem;
            font-style: italic;
            color: #4b5563;
            margin-top: 1rem;
            margin-bottom: 1rem;
        }

        .prose table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            margin-bottom: 1rem;
        }

        .prose table th,
        .prose table td {
            border: 1px solid #e5e7eb;
            padding: 0.5rem;
        }

        .prose img {
            max-width: 100%;
            height: auto;
            border-radius: 0.375rem;
        }
    </style>
@endpush

@section('content')
    @php
        $featuredExists = !empty($product->featured_image) && (str_starts_with($product->featured_image, 'http') || \Illuminate\Support\Facades\Storage::disk('public')->exists($product->featured_image));
        $imageUrl = $featuredExists 
            ? (str_starts_with($product->featured_image, 'http') ? $product->featured_image : \Illuminate\Support\Facades\Storage::url($product->featured_image))
            : asset('frontend/images/placeholder.webp');

        $allImages = [$imageUrl];

        if ($product->gallery) {
            $gallery = is_array($product->gallery) ? $product->gallery : json_decode($product->gallery, true) ?? [];
            foreach ($gallery as $img) {
                if (empty($img)) continue;
                $exists = str_starts_with($img, 'http') || \Illuminate\Support\Facades\Storage::disk('public')->exists($img);
                if ($exists) {
                    $path = str_starts_with($img, 'http') ? $img : \Illuminate\Support\Facades\Storage::url($img);
                    if (!in_array($path, $allImages)) {
                        $allImages[] = $path;
                    }
                }
            }
        }

        $availableAttributes = [];
        $variantsJson = [];
        if ($product->product_type === 'variable' && $product->variants) {
            foreach ($product->variants as $variant) {
                // Ensure attributes is an array
                $attrs = is_array($variant->attributes) ? $variant->attributes : json_decode($variant->attributes, true) ?? [];

                foreach ($attrs as $key => $value) {
                    if (!isset($availableAttributes[$key])) {
                        $availableAttributes[$key] = [];
                    }
                    if (!in_array($value, $availableAttributes[$key])) {
                        $availableAttributes[$key][] = $value;
                    }
                }

                $variantImages = is_array($variant->images) ? $variant->images : json_decode($variant->images, true) ?? [];
                $vImage = null;
                if (!empty($variantImages[0])) {
                    $vExists = str_starts_with($variantImages[0], 'http') || \Illuminate\Support\Facades\Storage::disk('public')->exists($variantImages[0]);
                    if ($vExists) {
                        $vImage = str_starts_with($variantImages[0], 'http') ? $variantImages[0] : \Illuminate\Support\Facades\Storage::url($variantImages[0]);
                        if (!in_array($vImage, $allImages)) {
                            $allImages[] = $vImage;
                        }
                    }
                }

                $variantsJson[] = [
                    'id' => $variant->id,
                    'price' => $variant->current_price,
                    'old_price' => $variant->original_price,
                    'stock' => $variant->stock_quantity,
                    'sku' => $variant->sku,
                    'attributes' => $attrs,
                    'image' => $vImage
                ];
            }
        }

        $initialImageUrl = $imageUrl;
        if ($product->product_type === 'variable' && count($variantsJson) > 0) {
            if (!empty($variantsJson[0]['image'])) {
                $initialImageUrl = $variantsJson[0]['image'];
            }
        }
    @endphp

    <div class="w-full max-w-full mx-auto px-4 lg:px-8 py-4">

        <!-- SEO Friendly Breadcrumb -->
        <nav class="flex text-sm text-gray-500 mb-4 w-full" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-1 md:space-x-2 w-full" itemscope
                itemtype="https://schema.org/BreadcrumbList">
                <li class="shrink-0" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <a href="/" class="hover:text-brand-600" itemprop="item"><span itemprop="name">Home</span></a>
                    <meta itemprop="position" content="1" />
                </li>
                <li class="shrink-0"><span class="mx-1">/</span></li>

                @if($product->category)
                    <li class="shrink-0" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                        <a href="{{ route('shop.category', $product->category->slug) }}" class="hover:text-brand-600"
                            itemprop="item"><span itemprop="name">{{ $product->category->name }}</span></a>
                        <meta itemprop="position" content="2" />
                    </li>
                    <li class="shrink-0"><span class="mx-1">/</span></li>
                @endif

                <li class="min-w-0 flex-1 flex items-center" itemprop="itemListElement" itemscope
                    itemtype="https://schema.org/ListItem">
                    <span class="text-gray-800 font-medium truncate w-full" aria-current="page"
                        itemprop="name">{{ $product->title }}</span>
                    <meta itemprop="position" content="{{ $product->category ? 3 : 2 }}" />
                </li>
            </ol>
        </nav>

        <!-- Product Details Wrapper -->
        <div class="flex flex-col lg:flex-row gap-8 bg-white p-4 lg:p-6 rounded-2xl shadow-sm border border-gray-100"
            x-data="productDetails({ 
                                                            basePrice: {{ $product->base_price ?? 0 }}, 
                                                            oldPrice: {{ $product->original_price ?? 0 }},
                                                            variants: {{ json_encode($variantsJson) }},
                                                            isVariable: {{ $product->product_type === 'variable' ? 'true' : 'false' }},
                                                            availableAttributesKeys: {{ json_encode(array_keys($availableAttributes)) }},
                                                            featuredImage: '{{ $initialImageUrl }}',
                                                            sku: '{{ $product->product_type === 'simple' ? ($product->variants->first()?->sku ?? '') : '' }}',
                                                            productId: {{ $product->id }},
                                                            variantId: {{ $product->product_type === 'simple' ? ($product->variants->first()?->id ?? 'null') : 'null' }},
                                                            title: '{{ addslashes($product->title) }}',
                                                            stock: {{ $product->product_type === 'simple' ? ($product->variants->first()?->stock_quantity ?? 0) : 0 }}
                                                        })">

            <!-- Left: Image Gallery -->
            <div class="w-full lg:w-[46%] xl:w-[42%] 2xl:w-[40%] shrink-0 flex flex-col gap-4">
                <!-- Main Image Container with Smooth Hover Zoom & Click Lightbox -->
                <div class="bg-gray-50 rounded-2xl overflow-hidden relative aspect-square border border-gray-100 select-none group/zoom cursor-crosshair"
                     x-data="{
                         isZoomed: false,
                         zoomX: 50,
                         zoomY: 50,
                         handleMouseMove(e) {
                             if (window.innerWidth < 1024) return;
                             const rect = this.$el.getBoundingClientRect();
                             const x = Math.max(0, Math.min(100, ((e.clientX - rect.left) / rect.width) * 100));
                             const y = Math.max(0, Math.min(100, ((e.clientY - rect.top) / rect.height) * 100));
                             this.zoomX = x.toFixed(2);
                             this.zoomY = y.toFixed(2);
                             this.isZoomed = true;
                         },
                         handleMouseLeave() {
                             this.isZoomed = false;
                             this.zoomX = 50;
                             this.zoomY = 50;
                         }
                     }"
                     @mouseenter="handleMouseMove($event)"
                     @mousemove="handleMouseMove($event)"
                     @mouseleave="handleMouseLeave()"
                     @click="if (window.innerWidth < 1024) openLightbox(currentImage)">

                    <!-- Loading Overlay (Hidden initially, shows when switching images) -->
                    <div id="main-image-skeleton" style="display: none;"
                        class="absolute inset-0 bg-white/60 backdrop-blur-sm z-20 flex items-center justify-center pointer-events-none">
                        <i class="ph ph-spinner-gap animate-spin text-4xl text-brand-600"></i>
                    </div>

                    <!-- Product Image with 2.4x Smooth Zoom -->
                    <img :src="currentImage" src="{{ $initialImageUrl }}"
                        @load="document.getElementById('main-image-skeleton').style.display='none';" fetchpriority="high"
                        class="w-full h-full object-cover relative z-10 transition-transform duration-150 ease-out will-change-transform pointer-events-none"
                        :style="isZoomed ? 'transform: scale(2.4); transform-origin: ' + zoomX + '% ' + zoomY + '%;' : 'transform: scale(1); transform-origin: center center;'"
                        alt="{{ $product->title }}">

                    <!-- Discount Badge -->
                    <div x-show="currentOldPrice && Number(currentOldPrice) > Number(currentPrice)"
                        style="{{ ($product->original_price ?? 0) > ($product->base_price ?? 0) ? '' : 'display:none;' }}"
                        class="absolute top-4 left-4 bg-red-500 text-white text-xs font-bold px-3 py-1 rounded-full tracking-wide uppercase shadow-sm z-30 pointer-events-none">
                        Save <span x-text="Math.round(((Number(currentOldPrice) - Number(currentPrice)) / Number(currentOldPrice)) * 100) + '%'">{{ ($product->original_price ?? 0) > ($product->base_price ?? 0) ? round((($product->original_price - $product->base_price) / $product->original_price) * 100) . '%' : '' }}</span>
                    </div>

                    <!-- Expand / Fullscreen Button -->
                    <button type="button" 
                            @click.stop="openLightbox(currentImage)" 
                            class="absolute top-4 right-4 z-30 w-9 h-9 rounded-full bg-white/90 hover:bg-white text-gray-700 hover:text-pink-600 shadow-md backdrop-blur-sm flex items-center justify-center transition-all hover:scale-105 active:scale-95 cursor-pointer border border-gray-100"
                            title="Click to view full screen & zoom">
                        <i class="ph ph-arrows-out-simple text-base"></i>
                    </button>

                    <!-- Desktop Hover Hint Badge -->
                    <div x-show="!isZoomed" 
                         class="hidden lg:inline-flex items-center gap-1.5 absolute bottom-3 right-3 z-30 bg-black/60 backdrop-blur-md text-white text-[11px] font-medium px-2.5 py-1 rounded-full shadow pointer-events-none transition-opacity">
                        <i class="ph-bold ph-magnifying-glass-plus text-xs text-pink-400"></i>
                        <span>Hover to Zoom</span>
                    </div>

                    <!-- Mobile Tap Hint Badge -->
                    <div class="lg:hidden inline-flex items-center gap-1.5 absolute bottom-3 right-3 z-30 bg-black/60 backdrop-blur-md text-white text-[11px] font-medium px-2.5 py-1 rounded-full shadow pointer-events-none">
                        <i class="ph-bold ph-arrows-out-simple text-xs text-pink-400"></i>
                        <span>Tap to Expand</span>
                    </div>
                </div>
                <!-- Thumbnails Swiper Slider -->
                <div class="relative mt-2 w-full group">
                    <!-- Skeleton for Thumbnails -->
                    <div x-show="!swiperReady" class="flex gap-3 px-8 py-1 overflow-hidden">
                        @for($i = 0; $i < 4; $i++)
                            <div class="w-20 h-20 sm:w-24 sm:h-24 bg-gray-200 animate-pulse rounded-lg flex-shrink-0"></div>
                        @endfor
                    </div>

                    <div class="swiper product-thumbnails-slider w-full !px-8 py-1" x-show="swiperReady"
                        style="display: none;">
                        <div class="swiper-wrapper">
                            @foreach($allImages as $img)
                                <div class="swiper-slide !w-20 !h-20 sm:!w-24 sm:!h-24 bg-gray-50 rounded-lg overflow-hidden border-2 cursor-pointer transition-colors"
                                    :class="currentImage === '{{ $img }}' ? 'border-brand-500' : 'border-transparent hover:border-brand-300'"
                                    @click="
                                                                                                                if (currentImage !== '{{ $img }}') {
                                                                                                                    document.getElementById('main-image-skeleton').style.display='flex'; 
                                                                                                                    currentImage = '{{ $img }}';
                                                                                                                }
                                                                                                            ">
                                    <img src="{{ $img }}"
                                        class="w-full h-full object-cover transition-opacity duration-300"
                                        loading="lazy" decoding="async" width="96" height="96"
                                        alt="{{ $product->title }} - Thumbnail">
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <!-- Navigation buttons -->
                    <div x-show="swiperReady" style="display: none;"
                        class="swiper-button-prev !w-8 !h-8 !text-brand-600 after:!text-sm !left-0 !top-1/2 !-translate-y-1/2 !mt-0 bg-white rounded-full shadow border border-gray-100">
                    </div>
                    <div x-show="swiperReady" style="display: none;"
                        class="swiper-button-next !w-8 !h-8 !text-brand-600 after:!text-sm !right-0 !top-1/2 !-translate-y-1/2 !mt-0 bg-white rounded-full shadow border border-gray-100">
                    </div>
                </div>
            </div>

            <!-- Right: Product Info -->
            <div class="w-full lg:w-[54%] xl:w-[58%] 2xl:w-[60%] flex-1 min-w-0 flex flex-col">

                <!-- Stacked Details -->
                <div class="flex flex-col gap-2 mb-2 md:mb-4 pb-2 md:pb-4 border-b border-gray-100">
                    <h1 class="text-xl md:text-2xl lg:text-3xl font-bold text-gray-900">{{ $product->title }}</h1>

                    @if($product->category)
                        <div class="text-sm text-gray-600">
                            <span class="font-semibold text-gray-900">Category:</span>
                            <a href="{{ route('shop.category', $product->category->slug) }}"
                                class="hover:text-brand-600 hover:underline">{{ $product->category->name }}</a>
                        </div>
                    @endif

                    <div class="flex flex-wrap items-center gap-3 text-sm mt-1">
                        <!-- Reviews Link -->
                        <button @click="document.getElementById('reviews-section').scrollIntoView({ behavior: 'smooth' })"
                            class="flex items-center group cursor-pointer">
                            <div class="flex items-center">
                                @php $rating = $product->average_rating ?? 0; @endphp
                                @for($i = 1; $i <= 5; $i++)
                                    @if($rating >= $i)
                                        <i class="ph-fill ph-star text-amber-400"></i>
                                    @elseif($rating > ($i - 1) && $rating < $i)
                                        <i class="ph-fill ph-star-half text-amber-400"></i>
                                    @else
                                        <i class="ph-fill ph-star text-gray-300"></i>
                                    @endif
                                @endfor
                            </div>
                            <span
                                class="text-gray-600 ml-2 font-medium group-hover:text-brand-600 group-hover:underline transition-colors">({{ $product->average_rating }}
                                / 5) &bull; {{ $product->total_reviews }} Ratings</span>
                        </button>

                        <!-- Divider -->
                        <span class="text-gray-300 hidden sm:inline">|</span>

                        <!-- Q&A Link -->
                        <button @click="document.getElementById('questions-section').scrollIntoView({ behavior: 'smooth' })"
                            class="text-gray-600 font-medium hover:text-brand-600 hover:underline transition-colors flex items-center gap-1 cursor-pointer">
                            <i class="ph-fill ph-chats-circle"></i>
                            {{ $answeredQuestionsCount ?? 0 }} Questions
                        </button>
                    </div>

                    {{-- Sold count commented out for clean brand presentation and SEO/crawler exclusion --}}
                    {{--
                    <div class="flex gap-4">
                        <div class="text-sm">
                            <span class="font-semibold text-gray-900 mr-1">Sold:</span>
                            <span class="text-gray-600">{{ $product->total_sold }} units</span>
                        </div>
                    </div>
                    --}}
                </div>

                <!-- Short Description -->
                @if($product->short_description)
                    <div class="mb-4 text-gray-600 text-[15px] sm:text-base leading-relaxed max-w-2xl line-clamp-3"
                        title="{{ strip_tags($product->short_description) }}">
                        {!! strip_tags($product->short_description) !!}
                    </div>
                @endif

                <!-- Price -->
                <div
                    class="mb-3 md:mb-4 md:bg-gray-50 md:p-4 md:rounded-xl md:border md:border-gray-100 flex items-center gap-4 w-full md:max-w-sm">
                    <span class="text-3xl font-bold text-brand-600">৳<span
                            x-text="currentPrice">{{ $product->base_price ?? 0 }}</span></span>
                    <span x-show="currentOldPrice && Number(currentOldPrice) > Number(currentPrice)"
                        class="text-lg text-gray-400 line-through"
                        style="{{ ($product->original_price ?? 0) > ($product->base_price ?? 0) ? '' : 'display:none' }}">
                        ৳<span x-text="currentOldPrice">{{ $product->original_price ?? 0 }}</span>
                    </span>
                </div>

                <!-- Variants & Actions -->
                <div class="flex flex-col gap-4 w-full md:max-w-md">

                    <!-- Dynamic Attributes Loop -->
                    @if($product->product_type === 'variable' && count($availableAttributes) > 0)
                        @foreach($availableAttributes as $attributeName => $values)
                            <div>
                                <h3 class="text-sm font-semibold text-gray-800 mb-2">{{ $attributeName }}</h3>
                                <div class="flex items-center gap-3 flex-wrap">
                                    @foreach($values as $value)
                                        @php
                                            $hasImage = false;
                                            $swatchImage = null;
                                            if ($product->use_variant_images && strtolower($attributeName) === 'color') {
                                                foreach ($product->variants as $v) {
                                                    $vAttrs = is_array($v->attributes) ? $v->attributes : json_decode($v->attributes, true) ?? [];
                                                    if (isset($vAttrs[$attributeName]) && $vAttrs[$attributeName] === $value) {
                                                        $vImgs = is_array($v->images) ? $v->images : json_decode($v->images, true) ?? [];
                                                        if (!empty($vImgs[0])) {
                                                            $swatchImage = str_starts_with($vImgs[0], 'http') ? $vImgs[0] : Storage::url($vImgs[0]);
                                                            $hasImage = true;
                                                            break;
                                                        }
                                                    }
                                                }
                                            }
                                        @endphp

                                        @if($hasImage)
                                            <button x-show="isAvailableInHierarchy('{{ $attributeName }}', '{{ $value }}')"
                                                @click="selectAttribute('{{ $attributeName }}', '{{ $value }}')" title="{{ $value }}"
                                                :class="selectedAttributes['{{ $attributeName }}'] === '{{ $value }}' ? 'border-2 border-brand-500 scale-105' : 'border-2 border-gray-100 hover:border-brand-300'"
                                                class="w-16 h-16 sm:w-20 sm:h-20 rounded-lg overflow-hidden transition-all duration-200 focus:outline-none flex-shrink-0 bg-gray-50 shadow-sm cursor-pointer">
                                                <img src="{{ $swatchImage }}" onload="this.classList.remove('opacity-0');"
                                                    alt="{{ $value }}"
                                                    class="w-full h-full object-cover text-transparent opacity-0 transition-opacity duration-300"
                                                    fetchpriority="high">
                                            </button>
                                        @else
                                            <button x-show="isAvailableInHierarchy('{{ $attributeName }}', '{{ $value }}')"
                                                @click="selectAttribute('{{ $attributeName }}', '{{ $value }}')"
                                                :class="selectedAttributes['{{ $attributeName }}'] === '{{ $value }}' ? 'border-brand-500 text-brand-600 bg-brand-50 shadow-sm' : 'border-gray-200 text-gray-700 hover:border-brand-500 hover:text-brand-600'"
                                                class="px-4 h-10 rounded-lg border-2 font-medium flex items-center justify-center transition-all duration-200 cursor-pointer">
                                                {{ $value }}
                                            </button>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @endif

                    <!-- Actions: directly below size -->
                    <div class="flex flex-col gap-3 mt-1 border-t border-gray-100 pt-4">
                        <div class="flex items-center gap-3">
                            <!-- Quantity -->
                            <div class="flex items-center border border-gray-200 rounded-lg h-12 bg-white w-28 shrink-0">
                                <button @click="if(quantity > 1) quantity--"
                                    :disabled="quantity <= 1 || maxSelectable === 0"
                                    :class="{'opacity-50 cursor-not-allowed': quantity <= 1 || maxSelectable === 0}"
                                    class="w-8 h-full flex items-center justify-center text-gray-500 hover:text-brand-600 transition-colors cursor-pointer"><i
                                        class="ph ph-minus"></i></button>
                                <input type="text" x-model="quantity" value="1"
                                    @input="if(quantity > maxSelectable) quantity = maxSelectable; if(quantity < 1 && maxSelectable > 0) quantity = 1;"
                                    :disabled="maxSelectable === 0"
                                    class="flex-1 text-center font-medium text-gray-800 focus:outline-none w-full bg-transparent">
                                <button @click="if(quantity < maxSelectable) quantity++"
                                    :disabled="quantity >= maxSelectable || maxSelectable === 0"
                                    :class="{'opacity-50 cursor-not-allowed': quantity >= maxSelectable || maxSelectable === 0}"
                                    class="w-8 h-full flex items-center justify-center text-gray-500 hover:text-brand-600 transition-colors cursor-pointer"><i
                                        class="ph ph-plus"></i></button>
                            </div>

                            <button @click="addToCartAction()"
                                class="flex-1 bg-brand-600 hover:bg-brand-700 text-white font-semibold h-12 rounded-lg shadow-sm transition-colors flex items-center justify-center gap-2 cursor-pointer">
                                <i class="ph ph-shopping-cart text-lg"></i> Add to Cart
                            </button>
                        </div>

                        <button @click="addToCartAction('buy')"
                            class="w-full bg-gray-900 hover:bg-gray-800 text-white font-semibold h-12 rounded-lg shadow-sm transition-colors flex items-center justify-center gap-2 cursor-pointer">
                            <i class="ph ph-lightning text-lg"></i> Buy Now
                        </button>

                        <!-- SKU -->
                        <div class="mt-2 text-sm text-gray-500 flex items-center gap-1" x-show="currentSku"
                            style="display: none;">
                            <span class="font-medium text-gray-700">SKU:</span>
                            <span x-text="currentSku"></span>
                        </div>

                        <!-- Share -->
                        <div class="mt-3 border-t border-gray-100 pt-3">
                            <button @click="$dispatch('open-share-modal')"
                                class="text-gray-500 hover:text-brand-600 flex items-center gap-1.5 font-medium transition-colors text-sm cursor-pointer">
                                <i class="ph ph-share-network text-lg"></i> Share Product
                            </button>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Fullscreen Lightbox Modal (Inside productDetails scope for mobile & detailed examination) -->
            <div x-show="lightboxOpen" 
                 x-cloak
                 x-transition:enter="transition ease-out duration-250"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @keydown.escape.window="closeLightbox()"
                 class="fixed inset-0 z-[99999] bg-black/95 backdrop-blur-md flex flex-col justify-between select-none"
                 style="display: none;">
                 
                <!-- Top Bar: Title, Count, Close -->
                <div class="flex items-center justify-between px-4 py-3.5 text-white/90 border-b border-white/10 shrink-0">
                    <div class="text-xs sm:text-sm font-medium truncate max-w-[70%]">
                        <span>{{ $product->title }}</span>
                        <span class="text-white/50 text-xs ml-2" x-text="`(${lightboxIndex + 1}/${lightboxImages.length})`"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="closeLightbox()" class="w-9 h-9 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors cursor-pointer" aria-label="Close Lightbox">
                            <i class="ph ph-x text-xl"></i>
                        </button>
                    </div>
                </div>

                <!-- Center Stage: Double-click / Double-tap Zoom Image Stage -->
                <div class="flex-1 flex items-center justify-center relative overflow-hidden p-2 sm:p-6"
                     x-data="{
                         scale: 1,
                         toggleZoom() {
                             this.scale = this.scale > 1 ? 1 : 2.2;
                         }
                     }"
                     @click.self="toggleZoom()">

                    <!-- Previous Button -->
                    <button type="button" x-show="lightboxImages.length > 1" 
                            @click.stop="prevLightbox(); scale = 1;" 
                            class="absolute left-3 sm:left-6 z-20 w-11 h-11 rounded-full bg-black/60 hover:bg-black/90 text-white flex items-center justify-center border border-white/20 transition-all hover:scale-105 active:scale-95 cursor-pointer shadow-lg">
                        <i class="ph ph-caret-left text-2xl"></i>
                    </button>

                    <!-- Image with Double-Click / Double-Tap Zoom -->
                    <img :src="lightboxImages[lightboxIndex]" 
                         class="max-w-full max-h-[75vh] object-contain transition-transform duration-200 ease-out cursor-zoom-in rounded-lg shadow-2xl"
                         :class="scale > 1 ? '!cursor-zoom-out' : ''"
                         :style="'transform: scale(' + scale + ');'"
                         @dblclick="toggleZoom()"
                         alt="{{ $product->title }}">

                    <!-- Next Button -->
                    <button type="button" x-show="lightboxImages.length > 1" 
                            @click.stop="nextLightbox(); scale = 1;" 
                            class="absolute right-3 sm:right-6 z-20 w-11 h-11 rounded-full bg-black/60 hover:bg-black/90 text-white flex items-center justify-center border border-white/20 transition-all hover:scale-105 active:scale-95 cursor-pointer shadow-lg">
                        <i class="ph ph-caret-right text-2xl"></i>
                    </button>
                </div>

                <!-- Bottom: Gallery Thumbnails Stripe -->
                <div class="p-3 bg-black/50 border-t border-white/10 flex justify-center gap-2.5 overflow-x-auto shrink-0 custom-scrollbar">
                    <template x-for="(img, idx) in lightboxImages" :key="idx">
                        <button type="button" @click="lightboxIndex = idx; scale = 1;" 
                                class="w-12 h-12 rounded-lg overflow-hidden border-2 transition-all shrink-0 cursor-pointer"
                                :class="lightboxIndex === idx ? 'border-pink-500 scale-105 shadow-md' : 'border-transparent opacity-50 hover:opacity-100'">
                            <img :src="img" class="w-full h-full object-cover">
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <!-- Product Details Tabs -->
        <div class="mt-8 bg-white p-4 lg:px-6 lg:pt-2 lg:pb-6 rounded-2xl shadow-sm border border-gray-100"
            x-data="{ activeTab: 'description' }">

            <!-- Tab Navigation (Centered) -->
            <div class="flex flex-wrap justify-center gap-2 sm:gap-6 mb-8 border-b border-gray-100 pb-2">
                <button @click="activeTab = 'description'"
                    :class="activeTab === 'description' ? 'text-brand-600 border-b-2 border-brand-600 font-bold' : 'text-gray-500 hover:text-brand-600 font-medium'"
                    class="px-2 sm:px-4 py-2 transition-colors whitespace-nowrap cursor-pointer">Description</button>
                <button @click="activeTab = 'additional'"
                    :class="activeTab === 'additional' ? 'text-brand-600 border-b-2 border-brand-600 font-bold' : 'text-gray-500 hover:text-brand-600 font-medium'"
                    class="px-2 sm:px-4 py-2 transition-colors whitespace-nowrap cursor-pointer">Additional Info</button>
                <button @click="activeTab = 'shipping_warranty'"
                    :class="activeTab === 'shipping_warranty' ? 'text-brand-600 border-b-2 border-brand-600 font-bold' : 'text-gray-500 hover:text-brand-600 font-medium'"
                    class="px-2 sm:px-4 py-2 transition-colors whitespace-nowrap cursor-pointer">Shipping &
                    Warranty</button>
            </div>

            <!-- Tab Content -->
            <div class="relative min-h-[200px]">

                <!-- Description Tab with Show More -->
                <div x-show="activeTab === 'description'" x-data="{ 
                                                                expanded: false, 
                                                                showButton: false,
                                                                init() {
                                                                    this.$nextTick(() => {
                                                                        if (this.$refs.content.scrollHeight > 250) {
                                                                            this.showButton = true;
                                                                        } else {
                                                                            this.expanded = true;
                                                                            this.showButton = false;
                                                                        }
                                                                    });
                                                                }
                                                            }">
                    <div class="relative">
                        <div x-ref="content"
                            class="prose prose-brand max-w-none text-gray-600 text-sm md:text-base overflow-hidden transition-all duration-500"
                            :style="expanded ? 'max-height: ' + $refs.content.scrollHeight + 'px;' : 'max-height: 250px;'">
                            {!! $product->description ?? '<p>No description available.</p>' !!}
                        </div>
                        <div x-show="showButton && !expanded"
                            class="absolute bottom-0 left-0 right-0 h-24 bg-gradient-to-t from-white to-transparent pointer-events-none">
                        </div>
                    </div>
                    <div x-show="showButton" style="display: none;" class="mt-6 text-center">
                        <button @click="expanded = !expanded"
                            class="inline-flex items-center gap-2 px-6 py-2.5 border border-gray-200 rounded-full text-sm font-semibold text-gray-700 hover:bg-brand-50 hover:text-brand-600 hover:border-brand-200 transition-colors shadow-sm cursor-pointer">
                            <span x-text="expanded ? 'Show Less' : 'Show More'"></span>
                            <i class="ph" :class="expanded ? 'ph-caret-up' : 'ph-caret-down'"></i>
                        </button>
                    </div>
                </div>

                <!-- Additional Info Tab -->
                <div x-show="activeTab === 'additional'" style="display: none;"
                    class="prose prose-brand max-w-none text-gray-600 text-sm md:text-base">
                    @if($product->additional_info)
                        {!! $product->additional_info !!}
                    @else
                        <p class="text-center py-6 text-gray-500">No additional info provided.</p>
                    @endif
                </div>

                <!-- Shipping & Warranty Tab -->
                <div x-show="activeTab === 'shipping_warranty'" style="display: none;"
                    class="prose prose-brand max-w-none text-gray-600 text-sm md:text-base space-y-6">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Shipping Information</h3>
                        @if($product->delivery_info)
                            {!! $product->delivery_info !!}
                        @else
                            @php
                                $insideDhaka = \App\Models\Setting::where('key', 'delivery_charge_inside_dhaka')->value('value') ?? 80;
                                $outsideDhaka = \App\Models\Setting::where('key', 'delivery_charge_outside_dhaka')->value('value') ?? 130;
                            @endphp
                            <ul class="space-y-3 mt-4 not-prose list-none pl-0">
                                <li
                                    class="flex justify-between items-center bg-gray-50 p-3 sm:p-4 rounded-xl border border-gray-100 m-0">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-sm text-brand-600 border border-gray-100 shrink-0">
                                            <i class="ph-fill ph-scooter text-xl"></i>
                                        </div>
                                        <div>
                                            <span class="font-bold text-gray-900 block leading-tight">Inside Dhaka</span>
                                            <span class="text-gray-500 text-xs sm:text-sm">1-2 days delivery approx.</span>
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="font-bold text-brand-600 text-base sm:text-lg">৳{{ $insideDhaka }}</span>
                                    </div>
                                </li>
                                <li
                                    class="flex justify-between items-center bg-gray-50 p-3 sm:p-4 rounded-xl border border-gray-100 m-0">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-sm text-brand-600 border border-gray-100 shrink-0">
                                            <i class="ph-fill ph-truck text-xl"></i>
                                        </div>
                                        <div>
                                            <span class="font-bold text-gray-900 block leading-tight">Outside Dhaka</span>
                                            <span class="text-gray-500 text-xs sm:text-sm">3-5 days delivery approx.</span>
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="font-bold text-brand-600 text-base sm:text-lg">৳{{ $outsideDhaka }}</span>
                                    </div>
                                </li>
                            </ul>
                            <div
                                class="mt-4 p-3 sm:p-4 bg-brand-50 border border-brand-100 rounded-xl flex items-center gap-3 text-brand-700 not-prose">
                                <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center shadow-sm shrink-0">
                                    <i class="ph-fill ph-map-pin text-brand-600"></i>
                                </div>
                                <span class="font-medium text-sm">We provide home delivery all over Bangladesh!</span>
                            </div>
                        @endif
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Warranty & Guarantee</h3>
                        @if($product->warranty_info)
                            {!! $product->warranty_info !!}
                        @else
                            <p class="text-gray-500">No warranty and guarantee applied for this product.</p>
                        @endif
                    </div>
                </div>

            </div>
        </div>

        <!-- Reviews & Questions Section -->
        <div class="mt-8 flex flex-col gap-8 w-full lg:w-8/12">
            <!-- Reviews -->
            @php
                $ratingCounts = \App\Models\Product\ProductReview::where('product_id', $product->id)
                    ->where('status', 'approved')
                    ->selectRaw('rating, count(*) as count')
                    ->groupBy('rating')
                    ->pluck('count', 'rating')
                    ->toArray();
                $totalApprovedReviews = array_sum($ratingCounts);
            @endphp
            <div id="reviews-section" class="bg-white p-6 lg:p-8 rounded-2xl shadow-sm border border-gray-100" x-data="productReviews()">
                <h2
                    class="text-xl font-bold text-gray-900 mb-6 pb-4 border-b border-gray-100 flex justify-between items-center">
                    Customer Ratings & Reviews
                    <span class="text-sm font-normal text-gray-500">Based on {{ $product->total_reviews }} ratings</span>
                </h2>

                <div class="flex items-center gap-8 mb-8">
                    <div class="text-center">
                        <div class="text-5xl font-bold text-gray-900 mb-2">{{ number_format($product->average_rating, 1) }}</div>
                        <div class="flex text-amber-400 text-lg mb-1 justify-center">
                            @for ($i = 1; $i <= 5; $i++)
                                @if ($i <= round($product->average_rating))
                                    <i class="ph-fill ph-star"></i>
                                @else
                                    <i class="ph ph-star"></i>
                                @endif
                            @endfor
                        </div>
                        <div class="text-sm text-gray-500">{{ $product->total_reviews }} Ratings</div>
                    </div>

                    <div class="flex-1 flex flex-col gap-2">
                        @foreach([5, 4, 3, 2, 1] as $star)
                            @php
                                $count = $ratingCounts[$star] ?? 0;
                                $percentage = $totalApprovedReviews > 0 ? ($count / $totalApprovedReviews) * 100 : 0;
                            @endphp
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-medium text-gray-600 w-3">{{ $star }}</span>
                                <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-amber-400" style="width: {{ $percentage }}%"></div>
                                </div>
                                <span class="text-sm text-gray-500 w-8 text-right">({{ $count }})</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="py-4 relative" id="review-list-container">
                    <!-- Loader -->
                    <div x-show="isLoadingReviews" class="absolute inset-0 bg-white/70 backdrop-blur-[2px] z-10 flex items-start justify-center pt-20" style="display: none;">
                        <div class="bg-white p-3 rounded-xl shadow-lg border border-gray-100 flex items-center gap-3">
                            <i class="ph ph-spinner animate-spin text-2xl text-brand-600"></i>
                            <span class="text-sm font-bold text-gray-700">Loading reviews...</span>
                        </div>
                    </div>

                    @forelse($productReviews as $review)
                    <div class="mb-6 pb-6 border-b border-gray-100 last:border-0 last:mb-0 last:pb-0">
                        <div class="flex items-center gap-4 mb-3">
                            <div class="w-10 h-10 rounded-full bg-brand-100 flex items-center justify-center text-brand-600 font-bold shrink-0">
                                {{ strtoupper(substr($review->user->name, 0, 1)) }}
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900 text-sm">{{ $review->user->name }}</h4>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <div class="flex text-amber-400 text-xs">
                                        @for($i=1; $i<=5; $i++)
                                            <i class="ph-fill ph-star {{ $i <= $review->rating ? 'text-amber-400' : 'text-gray-200' }}"></i>
                                        @endfor
                                    </div>
                                    <span class="text-xs text-gray-500">{{ $review->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        </div>
                        @if($review->review)
                        <p class="text-gray-600 text-sm leading-relaxed mb-3">{{ $review->review }}</p>
                        @endif
                        @if(is_array($review->attachments) && count($review->attachments) > 0)
                        <div class="flex flex-wrap gap-2 mt-2">
                            @foreach($review->attachments as $attachment)
                            <button type="button" @click.prevent="lightboxImg = '{{ Storage::url($attachment) }}'; lightboxOpen = true" class="block focus:outline-none">
                                <img src="{{ Storage::url($attachment) }}" class="w-20 h-20 rounded-lg object-cover border border-gray-100 hover:opacity-90 transition-opacity cursor-zoom-in" alt="Review Photo">
                            </button>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @empty
                        @if($product->total_reviews > 0)
                        <p class="text-gray-500 mb-4">Written reviews are temporarily unavailable or not written by users yet.</p>
                        @else
                        <p class="text-gray-500 mb-4">No reviews found.</p>
                        @endif
                    @endforelse
                </div>

                @if($productReviews->hasPages())
                <div class="mt-4 pt-4 border-t border-gray-100 text-center" id="review-pagination">
                    {{ $productReviews->onEachSide(1)->links('vendor.pagination.tailwind-centered') }}
                </div>
                @else
                <div id="review-pagination"></div>
                @endif
                
                <!-- Lightbox Modal -->
                <div x-show="lightboxOpen" style="display: none;" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/90 p-4" x-transition.opacity>
                    <button @click="lightboxOpen = false" class="absolute top-6 right-6 text-white/70 hover:text-white transition-colors focus:outline-none cursor-pointer">
                        <i class="ph ph-x text-4xl"></i>
                    </button>
                    <img :src="lightboxImg" class="max-w-full max-h-full rounded-lg shadow-2xl object-contain cursor-zoom-out" @click.away="lightboxOpen = false" @click="lightboxOpen = false">
                </div>
            </div>

            <div id="questions-section" class="mt-8">
                @include('partials.product-questions')
            </div>
        </div>

        <!-- Similar Products Section -->
        @if(isset($similarProducts) && $similarProducts->count() > 0)
            <div class="mt-16">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Similar Products</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 py-4">
                    @foreach($similarProducts as $item)
                        <x-frontend.product-card :item="$item" />
                    @endforeach
                </div>
            </div>
        @endif

    </div>

        @push('scripts')
            <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
            <script>
                // Initialize Swiper
                document.addEventListener('DOMContentLoaded', () => {
                    const swiper = new Swiper('.product-thumbnails-slider', {
                        slidesPerView: 'auto',
                        spaceBetween: 12,
                        loop: true,
                        observer: true,
                        observeParents: true,
                        navigation: {
                            nextEl: '.swiper-button-next',
                            prevEl: '.swiper-button-prev',
                        },
                    });
                });

                document.addEventListener('alpine:init', () => {
                    Alpine.data('productReviews', () => ({
                        lightboxOpen: false,
                        lightboxImg: '',
                        isLoadingReviews: false,

                        init() {
                            this.$el.addEventListener('click', (e) => {
                                const link = e.target.closest('#review-pagination a');
                                if (link) {
                                    e.preventDefault();
                                    this.fetchReviews(link.href);
                                }
                            });
                        },

                        fetchReviews(url) {
                            this.isLoadingReviews = true;
                            // Add a visual cue immediately
                            document.getElementById('reviews-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
                            
                            fetch(url, {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                            .then(res => res.text())
                            .then(html => {
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(html, 'text/html');
                                
                                const newReviewList = doc.getElementById('review-list-container');
                                const newPagination = doc.getElementById('review-pagination');
                                
                                if (newReviewList) {
                                    document.getElementById('review-list-container').innerHTML = newReviewList.innerHTML;
                                }
                                
                                if (newPagination && document.getElementById('review-pagination')) {
                                    document.getElementById('review-pagination').innerHTML = newPagination.innerHTML;
                                } else if (!newPagination && document.getElementById('review-pagination')) {
                                    document.getElementById('review-pagination').innerHTML = '';
                                }
                                
                                this.isLoadingReviews = false;
                                // Re-evaluate Alpine on the newly injected content
                                // Alpine handles DOM changes automatically via mutation observer, but just in case
                            })
                            .catch(err => {
                                console.error('Error fetching reviews:', err);
                                this.isLoadingReviews = false;
                            });
                        }
                    }));

                    Alpine.data('productDetails', (config) => ({
                        quantity: 1,
                        selectedAttributes: {},
                        currentPrice: config.basePrice,
                        currentOldPrice: config.oldPrice,
                        currentImage: config.featuredImage,
                        currentSku: config.sku,
                        currentStock: config.stock,
                        currentVariantId: config.isVariable ? null : config.variantId,
                        isVariable: config.isVariable,
                        availableAttributesKeys: config.availableAttributesKeys,
                        showShareModal: false,
                        swiperReady: false,
                        lightboxOpen: false,
                        lightboxIndex: 0,
                        lightboxImages: @json($allImages),

                        openLightbox(img) {
                            let target = img || this.currentImage;
                            const idx = this.lightboxImages.indexOf(target);
                            this.lightboxIndex = idx !== -1 ? idx : 0;
                            this.lightboxOpen = true;
                            document.body.style.overflow = 'hidden';
                        },

                        closeLightbox() {
                            this.lightboxOpen = false;
                            document.body.style.overflow = '';
                        },

                        nextLightbox() {
                            this.lightboxIndex = (this.lightboxIndex + 1) % this.lightboxImages.length;
                        },

                        prevLightbox() {
                            this.lightboxIndex = (this.lightboxIndex - 1 + this.lightboxImages.length) % this.lightboxImages.length;
                        },

                        init() {
                            setTimeout(() => { this.swiperReady = true; }, 100);

                            if (this.isVariable && config.variants.length > 0) {
                                const firstVariant = config.variants[0];
                                this.selectedAttributes = { ...firstVariant.attributes };
                                this.updateDetails();
                            }

                            // Meta Pixel ViewContent Track (safe fallback)
                            if (typeof window.trackFbEvent === 'function') {
                                window.trackFbEvent('ViewContent', {
                                    content_name: config.title,
                                    content_ids: [String(config.productId)],
                                    content_type: 'product',
                                    value: Number(config.basePrice) || 0,
                                    currency: 'BDT'
                                });
                            }
                        },

                        selectAttribute(key, value) {
                            this.selectedAttributes[key] = value;

                            // Hierarchy logic: if a higher level attribute changes, validate/auto-select downstream attributes
                            const attrIndex = this.availableAttributesKeys.indexOf(key);

                            for (let i = attrIndex + 1; i < this.availableAttributesKeys.length; i++) {
                                const nextKey = this.availableAttributesKeys[i];
                                const currentValue = this.selectedAttributes[nextKey];

                                // If current downstream value is no longer valid with the new upstream selection
                                if (!this.isAvailableInHierarchy(nextKey, currentValue)) {
                                    // Find the first valid variant that matches the new upstream selections
                                    const validVariant = config.variants.find(v => {
                                        for (let j = 0; j <= i - 1; j++) {
                                            const prevKey = this.availableAttributesKeys[j];
                                            if (v.attributes[prevKey] !== this.selectedAttributes[prevKey]) return false;
                                        }
                                        return true;
                                    });

                                    if (validVariant) {
                                        this.selectedAttributes[nextKey] = validVariant.attributes[nextKey];
                                    }
                                }
                            }

                            this.updateDetails();
                        },

                        isAvailableInHierarchy(attributeName, value) {
                            if (!this.isVariable) return true;

                            const attrIndex = this.availableAttributesKeys.indexOf(attributeName);
                            if (attrIndex === 0) return true; // Top-level attribute always shows all options

                            // Check if ANY variant exists matching ALL preceding selected attributes + this test value
                            return config.variants.some(v => {
                                for (let i = 0; i < attrIndex; i++) {
                                    const prevKey = this.availableAttributesKeys[i];
                                    if (v.attributes[prevKey] !== this.selectedAttributes[prevKey]) {
                                        return false;
                                    }
                                }
                                if (v.attributes[attributeName] !== value) {
                                    return false;
                                }
                                return true;
                            });
                        },

                        updateDetails() {
                            if (!this.isVariable) return;

                            // Find a variant that matches ALL selected attributes exactly
                            const match = config.variants.find(v => {
                                for (const k of this.availableAttributesKeys) {
                                    if (v.attributes[k] !== this.selectedAttributes[k]) {
                                        return false;
                                    }
                                }
                                return true;
                            });

                            if (match) {
                                this.currentPrice = match.price;
                                this.currentOldPrice = match.old_price;
                                this.currentSku = match.sku;
                                this.currentStock = match.stock;
                                this.currentVariantId = match.id;
                                if (match.image) {
                                    this.currentImage = match.image;
                                }
                            }

                            this.$nextTick(() => {
                                if (this.quantity > this.maxSelectable) {
                                    this.quantity = this.maxSelectable > 0 ? this.maxSelectable : 1;
                                }
                            });
                        },

                        get quantityInCart() {
                            if (typeof Alpine === 'undefined' || !Alpine.store('cart')) return 0;
                            const existing = Alpine.store('cart').items.find(i => i.product_id === config.productId && i.variant_id === this.currentVariantId);
                            return existing ? existing.quantity : 0;
                        },

                        get maxSelectable() {
                            return Math.max(0, this.currentStock - this.quantityInCart);
                        },

                        canAddToCart() {
                            if (!this.isVariable) return true;
                            const allSelected = this.availableAttributesKeys.every(key => this.selectedAttributes[key]);
                            return allSelected;
                        },

                        addToCartAction(type = 'cart') {
                            if (!this.canAddToCart()) {
                                window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Please select ' + this.availableAttributesKeys.join(' and '), type: 'warning' } }));
                                return;
                            }

                            if (this.maxSelectable <= 0) {
                                window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Oops! You have already added all available items into your cart.', type: 'error' } }));
                                return;
                            }

                            const productToAdd = {
                                product_id: config.productId,
                                variant_id: this.currentVariantId,
                                title: config.title,
                                name: config.title,
                                variant_label: this.isVariable ? Object.entries(this.selectedAttributes).map(([k, v]) => `${k}: ${v}`).join(', ') : 'Single Piece',
                                price: this.currentPrice,
                                image: this.currentImage,
                                quantity: parseInt(this.quantity) || 1,
                                max_stock: this.currentStock
                            };

                            Alpine.store('cart').add(productToAdd);

                            if (type === 'buy') {
                                // Direct to checkout if required later, for now cart opens
                                window.location.href = '/checkout';
                            }
                        }
                    }));
                });
            </script>
        @endpush

        <!-- Share Modal Component -->
        <x-share-modal :product="$product" />
@endsection