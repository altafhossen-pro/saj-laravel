<!-- Buying Guide Card Component -->
<article class="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-xs hover:shadow-md transition-all duration-300 flex flex-col group h-full">
    <!-- Card Image -->
    <a href="{{ route('guides.show', $guide->slug) }}" class="relative block aspect-16/10 bg-gray-100 overflow-hidden">
        @if($guide->featured_image)
            <img src="{{ $guide->featured_image_url }}"
                 alt="{{ $guide->title }}"
                 loading="lazy"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
        @else
            <div class="w-full h-full flex items-center justify-center bg-linear-to-br from-brand-50 to-gray-100 text-brand-600">
                <i class="ph ph-book-open-text text-4xl opacity-80"></i>
            </div>
        @endif

        @if($guide->topic)
            <span class="absolute top-3 left-3 px-3 py-1 rounded-full text-[11px] font-bold bg-white/95 text-gray-900 shadow-xs backdrop-blur-xs">
                {{ $guide->topic }}
            </span>
        @endif
    </a>

    <!-- Card Content -->
    <div class="p-5 flex flex-col flex-1">
        <!-- Meta Info (Read time & Date) -->
        <div class="flex items-center gap-3 text-[11px] text-gray-400 font-medium mb-2.5">
            <span class="flex items-center gap-1">
                <i class="ph ph-clock"></i>
                <span>{{ $guide->reading_time_minutes }} min read</span>
            </span>
            <span>•</span>
            <time datetime="{{ ($guide->published_at ?? $guide->created_at)->toIso8601String() }}">
                {{ ($guide->published_at ?? $guide->created_at)->format('M d, Y') }}
            </time>
        </div>

        <!-- Title -->
        <h3 class="text-base font-bold text-gray-900 group-hover:text-brand-600 transition-colors line-clamp-2 leading-snug mb-2">
            <a href="{{ route('guides.show', $guide->slug) }}">
                {{ $guide->title }}
            </a>
        </h3>

        <!-- Excerpt -->
        <p class="text-xs text-gray-500 line-clamp-3 leading-relaxed mb-4 flex-1">
            {{ $guide->excerpt }}
        </p>

        <!-- Read Action Link -->
        <div class="pt-3 border-t border-gray-100 flex items-center justify-between">
            <a href="{{ route('guides.show', $guide->slug) }}"
               class="inline-flex items-center gap-1.5 text-xs font-bold text-brand-600 group-hover:text-brand-700 transition-colors">
                <span>Read Full Guide</span>
                <i class="ph-bold ph-arrow-right text-xs group-hover:translate-x-1 transition-transform"></i>
            </a>

            @if($guide->views_count > 0)
            <span class="text-[11px] text-gray-400 flex items-center gap-1" title="Total Views">
                <i class="ph ph-eye"></i>
                <span>{{ number_format($guide->views_count) }}</span>
            </span>
            @endif
        </div>
    </div>
</article>
