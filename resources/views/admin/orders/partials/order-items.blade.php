<!-- Order Items -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h2 class="text-lg font-bold text-gray-900">Order Items ({{ $order->items->count() }})</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-gray-50 text-gray-500 font-medium">
                <tr>
                    <th class="px-6 py-4">Product</th>
                    <th class="px-6 py-4 text-center">Qty</th>
                    <th class="px-6 py-4 text-right">Unit Price</th>
                    <th class="px-6 py-4 text-right">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($order->items as $item)
                @php
                    $variant = ($item->product && $item->variant_sku)
                        ? $item->product->variants->firstWhere('sku', $item->variant_sku)
                        : null;

                    $variantImage = null;
                    if ($variant && is_array($variant->images) && count($variant->images)) {
                        $first = collect($variant->images)->first();
                        $variantImage = is_array($first) ? ($first['url'] ?? $first['path'] ?? $first['image'] ?? null) : $first;
                    }
                    $imagePath = $variantImage ?: ($item->product->featured_image ?? $item->image);
                    $imageUrl = $imagePath
                        ? (\Illuminate\Support\Str::startsWith($imagePath, ['http://', 'https://']) ? $imagePath : \Illuminate\Support\Facades\Storage::url($imagePath))
                        : null;

                    $sku = $item->variant_sku ?: ($item->product->sku ?? null);
                    $info = is_array($item->variant_info) ? $item->variant_info : [];
                    $variantLabel = $info['label'] ?? null;
                    $variantAttrs = (!$variantLabel && isset($info['attributes']) && is_array($info['attributes'])) ? $info['attributes'] : [];
                @endphp
                <tr>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="relative group w-12 h-12 rounded-lg bg-gray-100 flex-shrink-0 overflow-hidden border border-gray-200 cursor-pointer"
                                 @if($imageUrl) @click="openImagePreview('{{ $imageUrl }}', '{{ addslashes($item->name) }}')" title="Click to view large image" @endif>
                                @if($imageUrl)
                                    <img src="{{ $imageUrl }}" alt="{{ $item->name }}" class="w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-black/35 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white">
                                        <i class="ph-bold ph-magnifying-glass-plus text-base"></i>
                                    </div>
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                                        <i class="ph ph-image text-xl"></i>
                                    </div>
                                @endif
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 line-clamp-1">{{ $item->name }}</p>
                                <div class="flex flex-wrap items-center gap-1.5 mt-0.5">
                                    @if($sku)
                                        <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-700 px-1.5 py-0.5 rounded text-[11px] font-mono font-medium border border-gray-200">
                                            <span class="text-gray-400">SKU:</span> {{ $sku }}
                                        </span>
                                    @endif
                                    @if($variantLabel)
                                        <span class="inline-block bg-brand-50 text-brand-700 border border-brand-100 px-1.5 py-0.5 rounded text-xs font-medium">{{ $variantLabel }}</span>
                                    @elseif(count($variantAttrs))
                                        @foreach($variantAttrs as $key => $value)
                                            <span class="inline-block bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded text-xs">{{ is_string($key) ? ucfirst($key) . ': ' : '' }}{{ is_array($value) ? implode(', ', $value) : $value }}</span>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center font-medium">{{ $item->quantity }}</td>
                    <td class="px-6 py-4 text-right text-gray-600">৳{{ number_format($item->price, 2) }}</td>
                    <td class="px-6 py-4 text-right font-medium text-gray-900">৳{{ number_format($item->subtotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
