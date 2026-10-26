@props(['item'])

<a href="{{ route('product.show', $item->slug) }}" class="block bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-300 group overflow-hidden relative flex flex-col h-full">
    
    <div class="relative aspect-square bg-gray-50 overflow-hidden">
        @if($item->featured_image)
            <img src="{{ Str::startsWith($item->featured_image, 'http') ? $item->featured_image : Storage::url($item->featured_image) }}"
                alt="{{ $item->title }} - {{ \App\Models\Setting::get('site_name', 'SAJ') }}"
                width="300"
                height="300"
                loading="lazy"
                decoding="async"
                class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
        @else
            <div class="absolute inset-0 flex items-center justify-center text-gray-300">
                <i class="ph ph-image text-4xl"></i>
            </div>
        @endif
        
        <!-- Sale Badge -->
        @if($item->original_price && $item->base_price < $item->original_price)
            <div class="absolute top-2 left-2 bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full tracking-wide uppercase shadow-sm z-10">
                Save {{ round((($item->original_price - $item->base_price) / $item->original_price) * 100) }}%
            </div>
        @endif
    </div>
    
    <div class="p-3 flex-1 flex flex-col">
        <div class="text-xs text-gray-400 mb-1 flex items-center justify-between">
            <div class="flex items-center text-amber-400">
                <i class="ph-fill ph-star"></i>
                <span class="text-gray-700 font-medium ml-1">{{ $item->average_rating > 0 ? number_format($item->average_rating, 1) : '0.0' }}</span>
                <span class="text-gray-400 ml-1">({{ $item->total_reviews ?? 0 }})</span>
            </div>
            {{-- Sold count commented out for clean brand presentation and SEO exclusion --}}
            {{-- <span class="text-[10px]">{{ $item->total_sold ?? 0 }} Sold</span> --}}
        </div>
        
        <h3 class="font-medium text-sm text-gray-800 leading-5 mb-2 group-hover:text-brand-600 transition-colors h-[2.5rem] line-clamp-2 overflow-hidden">
            {{ $item->title }}
        </h3>
            
        <div class="mt-auto flex items-end justify-between gap-2">
            <div class="flex items-baseline gap-2 flex-wrap">
                <span class="text-lg font-bold text-brand-600 leading-none">৳{{ $item->base_price }}</span>
                @if($item->original_price && $item->base_price < $item->original_price)
                    <span class="text-xs text-gray-400 line-through leading-none">৳{{ $item->original_price }}</span>
                @endif
            </div>
        </div>
    </div>
</a>
