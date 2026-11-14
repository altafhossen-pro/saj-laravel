@extends('layouts.app')

@section('title', $seo['title'])
@section('canonical', $seo['canonical'])

@section('meta')
    <!-- Robots Directive for Google Search Rich Thumbnails (Scoped to Guide) -->
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="googlebot" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">

    <meta name="description" content="{{ $seo['description'] }}">
    @if(!empty($seo['keywords']))
    <meta name="keywords" content="{{ $seo['keywords'] }}">
    @endif
    <!-- OpenGraph / Facebook -->
    <meta property="og:title" content="{{ $seo['title'] }}">
    <meta property="og:description" content="{{ $seo['description'] }}">
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{ $seo['canonical'] }}">
    <meta property="og:image" content="{{ $seo['og_image'] }}">
    <meta property="og:image:secure_url" content="{{ $seo['og_image'] }}">
    <meta property="og:image:alt" content="{{ $guide->title }}">
    <meta property="article:published_time" content="{{ ($guide->published_at ?? $guide->created_at)->toIso8601String() }}">
    <meta property="article:modified_time" content="{{ $guide->updated_at->toIso8601String() }}">
    @if($guide->topic)
    <meta property="article:section" content="{{ $guide->topic }}">
    @endif

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seo['title'] }}">
    <meta name="twitter:description" content="{{ $seo['description'] }}">
    <meta name="twitter:image" content="{{ $seo['og_image'] }}">

    <!-- Google Thumbnail Meta Tag Fallback -->
    <meta name="thumbnail" content="{{ $seo['og_image'] }}">

    <!-- Schema.org JSON-LD Structured Data for Google Rich Snippets -->
    <script type="application/ld+json">
    {!! json_encode($articleSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>
    <script type="application/ld+json">
    {!! json_encode($breadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>
@endsection

@section('content')
<main class="min-h-screen bg-white px-4 lg:px-8 py-6 pb-20">
    <!-- Breadcrumb Top Bar -->
    <div class="mb-5 pb-3 border-b border-gray-100">
        @include('frontend.guides.partials._breadcrumbs', ['guide' => $guide])
    </div>

    <!-- Main Container: 12-Column Responsive Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 items-start">
        
        <!-- Left Main Article Column (8 Columns) -->
        <article class="lg:col-span-8 space-y-6">
            <!-- Topic & Title -->
            <div class="space-y-3">
                @if($guide->topic)
                <div>
                    <a href="{{ route('guides.index', ['topic' => $guide->topic]) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-brand-50 text-brand-700 hover:bg-brand-100 transition-colors">
                        <i class="ph-bold ph-tag"></i>
                        <span>{{ $guide->topic }}</span>
                    </a>
                </div>
                @endif

                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black text-gray-900 tracking-tight leading-tight">
                    {{ $guide->title }}
                </h1>

                <!-- Article Meta Bar -->
                <div class="flex items-center justify-between flex-wrap gap-4 py-3 border-y border-gray-100 text-xs text-gray-500">
                    <div class="flex items-center gap-3 sm:gap-4 flex-wrap">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-brand-50 text-brand-600 font-bold flex items-center justify-center text-xs border border-brand-100 shrink-0">
                                {{ substr(\App\Models\Setting::get('site_name', 'SAJ'), 0, 1) }}
                            </div>
                            <div>
                                <span class="font-bold text-gray-800">{{ \App\Models\Setting::get('site_name', 'SAJ') }} Editorial</span>
                                <span class="block text-[10px] text-gray-400">Verified Guide</span>
                            </div>
                        </div>

                        <span>•</span>

                        <time datetime="{{ ($guide->published_at ?? $guide->created_at)->toIso8601String() }}" class="flex items-center gap-1">
                            <i class="ph ph-calendar"></i>
                            <span>Published {{ ($guide->published_at ?? $guide->created_at)->format('M d, Y') }}</span>
                        </time>

                        <span>•</span>

                        <span class="flex items-center gap-1">
                            <i class="ph ph-clock"></i>
                            <span>{{ $guide->reading_time_minutes }} min read</span>
                        </span>
                    </div>

                    <!-- Share Component (Top) -->
                    @include('frontend.guides.partials._share_bar', ['guide' => $guide])
                </div>
            </div>

            <!-- Featured Banner Image -->
            @if($guide->featured_image)
            <div class="aspect-16/9 rounded-3xl overflow-hidden bg-gray-100 border border-gray-100 shadow-xs">
                <img src="{{ $guide->featured_image_url }}"
                     alt="{{ $guide->title }}"
                     fetchpriority="high"
                     itemprop="image"
                     class="w-full h-full object-cover">
            </div>
            @endif

            <!-- Guide Rich Content (Left inline TOC removed as requested; TOC is elegantly anchored in the right sidebar) -->
            <div class="guide-content prose prose-base sm:prose-lg max-w-none text-gray-800 leading-relaxed font-normal">
                {!! $guide->processed_content !!}
            </div>

            <!-- Share Component (Bottom) -->
            <div class="pt-6 border-t border-gray-100 flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center gap-2">
                    <i class="ph-bold ph-heart text-rose-500 text-lg"></i>
                    <span class="text-xs text-gray-600 font-medium">Found this guide helpful? Share it with your friends!</span>
                </div>
                @include('frontend.guides.partials._share_bar', ['guide' => $guide])
            </div>

            <!-- Recommended / Attached Store Products (Scoped data-nosnippet) -->
            <div data-nosnippet>
                @include('frontend.guides.partials._related_products', ['relatedProducts' => $relatedProducts])
            </div>
        </article>

        <!-- Right Sidebar (4 Columns - Sticky) -->
        <aside class="hidden lg:block lg:col-span-4 space-y-6">
            <div class="sticky top-24 space-y-6">
                <!-- In This Article / Table of Contents (Right Side) -->
                @if(!empty($guide->table_of_contents) && count($guide->table_of_contents) > 0)
                <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-5 space-y-3">
                    <div class="flex items-center gap-2 pb-3 border-b border-gray-100">
                        <div class="w-7 h-7 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center text-sm">
                            <i class="ph-bold ph-list-numbers"></i>
                        </div>
                        <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider">In This Article</h3>
                    </div>
                    <nav class="space-y-1.5 text-xs max-h-[50vh] overflow-y-auto pr-1" aria-label="Table of contents">
                        @php $sIndex = 1; @endphp
                        @foreach($guide->table_of_contents as $item)
                            @if($item['level'] === 1 || $item['level'] === 2)
                                <a href="#{{ $item['id'] }}"
                                   class="flex items-start gap-2 py-1.5 px-2 rounded-lg text-gray-700 hover:text-brand-600 hover:bg-brand-50/50 font-semibold transition-colors group">
                                    <span class="w-4 h-4 rounded-full bg-gray-100 text-gray-600 text-[10px] font-bold flex items-center justify-center shrink-0 mt-0.5 group-hover:bg-brand-600 group-hover:text-white transition-colors">
                                        {{ $sIndex++ }}
                                    </span>
                                    <span class="line-clamp-2 leading-snug">{{ $item['title'] }}</span>
                                </a>
                            @else
                                <a href="#{{ $item['id'] }}"
                                   class="flex items-start gap-1.5 py-1 pl-7 pr-2 rounded-lg text-gray-500 hover:text-brand-600 hover:bg-gray-50 transition-colors">
                                    <span class="text-brand-400">↳</span>
                                    <span class="line-clamp-2 leading-snug">{{ $item['title'] }}</span>
                                </a>
                            @endif
                        @endforeach
                    </nav>
                </div>
                @endif

                <!-- Recommended Products Sticky Mini Card -->
                @if(!empty($relatedProducts) && $relatedProducts->count() > 0)
                <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-5 space-y-3">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                                <i class="ph-bold ph-shopping-bag"></i>
                            </div>
                            <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider">Featured Products</h3>
                        </div>
                        <span class="text-[10px] font-bold text-brand-600 bg-brand-50 px-2 py-0.5 rounded-full">{{ $relatedProducts->count() }} items</span>
                    </div>
                    <div class="space-y-3">
                        @foreach($relatedProducts->take(3) as $rp)
                        <a href="{{ route('product.show', $rp->slug) }}" class="flex items-center gap-3 p-2 rounded-xl hover:bg-gray-50 transition-colors group border border-transparent hover:border-gray-200">
                            <div class="w-14 h-14 rounded-lg bg-gray-50 overflow-hidden shrink-0 border border-gray-100">
                                <img src="{{ $rp->primary_image_url ?? (Str::startsWith($rp->featured_image, 'http') ? $rp->featured_image : Storage::url($rp->featured_image)) }}" alt="{{ $rp->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                            </div>
                            <div class="min-w-0 flex-1">
                                <h4 class="text-xs font-bold text-gray-800 group-hover:text-brand-600 line-clamp-1 transition-colors">{{ $rp->title }}</h4>
                                <div class="flex items-baseline gap-1.5 mt-1">
                                    <span class="text-xs font-black text-brand-600">৳{{ (int) round($rp->final_price) }}</span>
                                    @if($rp->original_price && $rp->original_price > $rp->final_price)
                                    <span class="text-[10px] text-gray-400 line-through">৳{{ (int) round($rp->original_price) }}</span>
                                    @endif
                                </div>
                            </div>
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- More Guides Widget -->
                @if($moreGuides->count() > 0)
                <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-5 space-y-3">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-sm">
                                <i class="ph-bold ph-book-open"></i>
                            </div>
                            <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider">Related Guides</h3>
                        </div>
                        <a href="{{ route('guides.index') }}" class="text-[11px] font-bold text-brand-600 hover:underline">View All</a>
                    </div>
                    <div class="space-y-3" data-nosnippet>
                        @foreach($moreGuides as $mg)
                        <a href="{{ route('guides.show', $mg->slug) }}" class="flex items-center gap-3 p-2 rounded-xl hover:bg-gray-50 transition-colors group">
                            <div class="w-14 h-12 rounded-lg bg-gray-100 overflow-hidden shrink-0 border border-gray-100">
                                @if($mg->featured_image)
                                <img src="{{ $mg->featured_image_url }}" alt="{{ $mg->title }}" class="w-full h-full object-cover">
                                @else
                                <div class="w-full h-full flex items-center justify-center text-brand-600"><i class="ph ph-article"></i></div>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <h4 class="text-xs font-bold text-gray-800 group-hover:text-brand-600 line-clamp-1 transition-colors">{{ $mg->title }}</h4>
                                <span class="text-[10px] text-gray-400 mt-0.5 block">{{ $mg->reading_time_minutes }} min read</span>
                            </div>
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </aside>
    </div>

    <!-- More Guides Full Width Grid (Bottom) -->
    @if($moreGuides->count() > 0)
    <section class="mt-16 pt-10 border-t border-gray-100 space-y-6" data-nosnippet>
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-xl font-bold text-gray-900">More Buying Guides</h3>
                <p class="text-xs text-gray-500 mt-0.5">Explore expert recommendations across categories</p>
            </div>
            <a href="{{ route('guides.index') }}" class="text-xs font-bold text-brand-600 hover:text-brand-700 flex items-center gap-1">
                <span>View All Guides</span>
                <i class="ph-bold ph-arrow-right"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @foreach($moreGuides as $more)
                @include('frontend.guides.partials._card', ['guide' => $more])
            @endforeach
        </div>
    </section>
    @endif
</main>

<style>
/* Prose Styling for Guide Content */
.guide-content h1 {
    display: block !important;
    font-size: 1.875rem !important;
    font-weight: 800 !important;
    color: #111827 !important;
    margin-top: 2.25rem !important;
    margin-bottom: 1rem !important;
    line-height: 1.25 !important;
}
.guide-content h2 {
    display: block !important;
    font-size: 1.5rem !important;
    font-weight: 800 !important;
    color: #111827 !important;
    margin-top: 2.25rem !important;
    margin-bottom: 0.85rem !important;
    line-height: 1.35 !important;
    border-bottom: 2px solid #f3f4f6 !important;
    padding-bottom: 0.5rem !important;
}
.guide-content h3 {
    display: block !important;
    font-size: 1.25rem !important;
    font-weight: 700 !important;
    color: #1f2937 !important;
    margin-top: 1.5rem !important;
    margin-bottom: 0.5rem !important;
    line-height: 1.35 !important;
}
.guide-content h4 {
    display: block !important;
    font-size: 1.125rem !important;
    font-weight: 700 !important;
    color: #1f2937 !important;
    margin-top: 1.25rem !important;
    margin-bottom: 0.5rem !important;
    line-height: 1.4 !important;
}
.guide-content strong, .guide-content b {
    font-weight: 700;
    color: #111827;
}
.guide-content p {
    margin-top: 0.85rem;
    margin-bottom: 0.85rem;
    line-height: 1.8;
    color: #374151;
    font-size: 1rem;
}
.guide-content ul {
    list-style-type: disc;
    padding-left: 1.5rem;
    margin-top: 0.75rem;
    margin-bottom: 0.75rem;
}
.guide-content ol {
    list-style-type: decimal;
    padding-left: 1.5rem;
    margin-top: 0.75rem;
    margin-bottom: 0.75rem;
}
.guide-content li {
    margin-top: 0.35rem;
    margin-bottom: 0.35rem;
    line-height: 1.65;
    color: #374151;
}
.guide-content img {
    border-radius: 1rem;
    margin-top: 1.5rem;
    margin-bottom: 1.5rem;
    max-width: 100%;
    height: auto;
    border: 1px solid #f3f4f6;
}
.guide-content a {
    color: #e11d48;
    text-decoration: underline;
    font-weight: 500;
}
.guide-content a:hover {
    color: #be123c;
}
.guide-content blockquote {
    border-left: 4px solid #e11d48;
    padding-left: 1rem;
    font-style: italic;
    color: #4b5563;
    margin-top: 1.25rem;
    margin-bottom: 1.25rem;
    background-color: #fff1f2;
    padding-top: 0.5rem;
    padding-bottom: 0.5rem;
    border-radius: 0 0.5rem 0.5rem 0;
}
.guide-content table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1.5rem;
    margin-bottom: 1.5rem;
}
.guide-content table th, .guide-content table td {
    border: 1px solid #e5e7eb;
    padding: 0.75rem 1rem;
    text-align: left;
}
.guide-content table th {
    background-color: #f9fafb;
    font-weight: 700;
}
</style>
@endsection
