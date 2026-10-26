<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-gray-50/80 border-b border-gray-100 text-[11px] uppercase text-gray-500 font-bold tracking-wider">
                <th class="p-4 pl-6 w-10">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500 transition-all">
                    </label>
                </th>
                <th class="p-4">Product Details</th>
                <th class="p-4">Category</th>
                <th class="p-4">Pricing</th>
                <th class="p-4">Inventory</th>
                <th class="p-4">Status</th>
                <th class="p-4 pr-6 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 text-sm">
            @forelse($products as $product)
                <tr class="hover:bg-gray-50/50 transition-colors group">
                    <td class="p-4 pl-6">
                        <input type="checkbox" value="{{ $product->id }}" class="w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500">
                    </td>
                    <td class="p-4">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-lg bg-gray-100 border border-gray-200 overflow-hidden shrink-0">
                                @if($product->featured_image)
                                    <img src="{{ str_starts_with($product->featured_image, 'http') ? $product->featured_image : Storage::url($product->featured_image) }}" alt="{{ $product->title }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                                        <i class="ph ph-image text-xl"></i>
                                    </div>
                                @endif
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 group-hover:text-brand-600 transition-colors line-clamp-1">{{ $product->title }}</h3>
                                <p class="text-xs text-gray-500 mt-0.5">Type: <span class="capitalize">{{ $product->product_type }}</span></p>
                            </div>
                        </div>
                    </td>
                    <td class="p-4">
                        @if($product->category)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-800">
                                {{ $product->category->name }}
                            </span>
                        @else
                            <span class="text-gray-400 text-xs">Uncategorized</span>
                        @endif
                    </td>
                    <td class="p-4">
                        <div class="font-medium text-gray-900">৳{{ number_format($product->base_price, 2) }}</div>
                    </td>
                    <td class="p-4">
                        @if($product->total_stock > 10)
                            <span class="inline-flex items-center gap-1.5 text-green-700 text-xs font-medium">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> {{ $product->total_stock }} in stock
                            </span>
                        @elseif($product->total_stock > 0)
                            <span class="inline-flex items-center gap-1.5 text-orange-700 text-xs font-medium">
                                <span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span> Low stock ({{ $product->total_stock }})
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 text-red-700 text-xs font-medium">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Out of stock
                            </span>
                        @endif
                    </td>
                    <td class="p-4">
                        @if($product->is_active)
                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-green-50 text-green-700 border border-green-200">
                                Active
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
                                Draft
                            </span>
                        @endif
                    </td>
                    <td class="p-4 pr-6 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.products.edit', $product->id) }}" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-brand-600 hover:bg-brand-50 transition-colors" title="Edit">
                                <i class="ph ph-pencil-simple text-lg"></i>
                            </a>
                            <button @click="$dispatch('open-delete-modal', { url: '{{ route('admin.products.destroy', $product->id) }}', title: 'Delete Product', message: 'Are you sure you want to delete {{ addslashes($product->title) }}? This action cannot be undone.' })" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors" title="Delete">
                                <i class="ph ph-trash text-lg"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="p-8 text-center">
                        <div class="flex flex-col items-center justify-center text-gray-500">
                            <i class="ph ph-package text-4xl mb-3 text-gray-300"></i>
                            <p class="text-sm font-medium text-gray-900">No products found</p>
                            <p class="text-xs mt-1">Try adjusting your filters or search query.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="px-6 py-4 border-t border-gray-100 bg-gray-50/30">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-2">
            <span class="text-sm text-gray-500 font-medium">Per Page:</span>
            <select x-model="per_page" @change="fetchData()"
                class="py-1.5 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-white">
                <option value="1">1</option>
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </div>
        <div class="flex-1 w-full sm:w-auto">
            {{ $products->links('components.pagination') }}
        </div>
    </div>
</div>
