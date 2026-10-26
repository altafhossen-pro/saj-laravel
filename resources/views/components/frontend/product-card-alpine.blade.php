<a :href="'/product/' + item.slug"
    class="block bg-white rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-all duration-300 group overflow-hidden relative flex flex-col h-full">

    <div class="relative aspect-square bg-gray-50 overflow-hidden">
        <template x-if="item.featured_image">
            <img :src="item.featured_image.startsWith('http') ? item.featured_image : '/storage/' + item.featured_image"
                :alt="item.title + ' - {{ \App\Models\Setting::get('site_name', 'SAJ') }}'"
                width="300"
                height="300"
                loading="lazy"
                decoding="async"
                class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
        </template>
        <template x-if="!item.featured_image">
            <div class="absolute inset-0 flex items-center justify-center text-gray-300">
                <i class="ph ph-image text-4xl"></i>
            </div>
        </template>

        <!-- Sale Badge -->
        <template x-if="item.original_price && item.base_price < item.original_price">
            <div class="absolute top-2 left-2 bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full tracking-wide uppercase shadow-sm z-10"
                x-text="'Save ' + Math.round(((item.original_price - item.base_price) / item.original_price) * 100) + '%'">
            </div>
        </template>
    </div>

    <div class="p-3 flex-1 flex flex-col">
        <div class="text-xs text-gray-400 mb-1 flex items-center justify-between">
            <div class="flex items-center text-amber-400">
                <i class="ph-fill ph-star"></i>
                <span class="text-gray-700 font-medium ml-1" x-text="Number(item.average_rating || 0) > 0 ? Number(item.average_rating).toFixed(1) : '0.0'"></span>
                <span class="text-gray-400 ml-1" x-text="'(' + (item.total_reviews || 0) + ')'"></span>
            </div>
            {{-- Sold count commented out for clean brand presentation and SEO exclusion --}}
            {{-- <span class="text-[10px]" x-text="(item.total_sold || 0) + ' Sold'"></span> --}}
        </div>

        <h3 class="font-medium text-sm text-gray-800 leading-5 mb-2 group-hover:text-brand-600 transition-colors h-[2.5rem] line-clamp-2 overflow-hidden"
            x-text="item.title"></h3>

        <div class="mt-auto flex items-end justify-between gap-2">
            <div class="flex items-baseline gap-2 flex-wrap">
                <span class="text-lg font-bold text-brand-600 leading-none" x-text="'৳' + item.base_price"></span>
                <template x-if="item.original_price && item.base_price < item.original_price">
                    <span class="text-xs text-gray-400 line-through leading-none"
                        x-text="'৳' + item.original_price"></span>
                </template>
            </div>
        </div>
    </div>
</a>