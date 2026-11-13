<!-- Featured / Related Products Mentioned In Article Component -->
@if(!empty($relatedProducts) && $relatedProducts->count() > 0)
<section class="my-10 p-6 rounded-2xl bg-gray-50 border border-gray-200/80 space-y-5">
    <div class="flex items-center justify-between flex-wrap gap-2 pb-3 border-b border-gray-200">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-brand-600 text-white flex items-center justify-center text-base shadow-xs shrink-0">
                <i class="ph-bold ph-shopping-bag-open"></i>
            </div>
            <div>
                <h3 class="text-sm font-bold text-gray-900 tracking-wide">
                    Products Recommended In This Guide
                </h3>
                <p class="text-[11px] text-gray-500">Available now with authentic guarantee & fast delivery</p>
            </div>
        </div>
        <span class="text-xs font-bold text-brand-600 bg-white px-3 py-1 rounded-full border border-gray-200">
            {{ $relatedProducts->count() }} Featured Items
        </span>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
        @foreach($relatedProducts as $product)
            <div class="bg-white rounded-xl border border-gray-200 p-3 flex flex-col justify-between hover:shadow-md transition-all duration-300 group">
                <a href="{{ route('product.show', $product->slug) }}" class="block aspect-square rounded-lg bg-gray-50 overflow-hidden mb-2.5 relative">
                    <img src="{{ $product->primary_image_url ?? (Str::startsWith($product->featured_image, 'http') ? $product->featured_image : Storage::url($product->featured_image)) }}"
                         alt="{{ $product->title }}"
                         loading="lazy"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    @php
                        $discountPct = $product->active_discount_percent;
                        if (!$discountPct && $product->original_price && $product->original_price > $product->final_price) {
                            $discountPct = (int) round((($product->original_price - $product->final_price) / $product->original_price) * 100);
                        }
                    @endphp
                    @if($discountPct && $discountPct > 0)
                        <span class="absolute top-2 left-2 px-2 py-0.5 rounded-md text-[10px] font-black bg-rose-600 text-white shadow-xs">
                            -{{ $discountPct }}%
                        </span>
                    @endif
                </a>

                <div class="space-y-1.5 flex-1 flex flex-col justify-between">
                    <div>
                        <a href="{{ route('product.show', $product->slug) }}"
                           class="text-xs font-bold text-gray-900 hover:text-brand-600 line-clamp-2 leading-snug transition-colors">
                            {{ $product->title }}
                        </a>
                    </div>

                    <div class="pt-2 flex items-center justify-between gap-1 flex-wrap">
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-sm font-black text-brand-600">
                                ৳{{ (int) round($product->final_price) }}
                            </span>
                            @if($product->original_price && $product->original_price > $product->final_price)
                            <span class="text-[11px] text-gray-400 line-through">
                                ৳{{ (int) round($product->original_price) }}
                            </span>
                            @endif
                        </div>

                        <a href="{{ route('product.show', $product->slug) }}"
                           class="px-2.5 py-1 text-[11px] font-bold text-white bg-gray-900 hover:bg-brand-600 rounded-lg transition-colors inline-flex items-center gap-1 cursor-pointer">
                            <span>View</span>
                            <i class="ph-bold ph-arrow-right text-[10px]"></i>
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</section>
@endif
