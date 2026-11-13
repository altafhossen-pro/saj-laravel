<!-- Related Products Picker Component -->
<div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-xs space-y-4"
     x-data="{
        searchQuery: '',
        searchResults: [],
        isSearching: false,
        selectedProducts: {{ json_encode($selectedProducts ?? []) }},

        async search() {
            const q = this.searchQuery.trim();
            if (q.length < 2) {
                this.searchResults = [];
                return;
            }
            this.isSearching = true;
            try {
                const res = await fetch(`{{ route('admin.guides.search-products') }}?q=${encodeURIComponent(q)}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                this.searchResults = data.products || [];
            } catch (err) {
                console.error(err);
            } finally {
                this.isSearching = false;
            }
        },

        addProduct(product) {
            if (!this.selectedProducts.some(p => p.id === product.id)) {
                this.selectedProducts.push(product);
            }
            this.searchQuery = '';
            this.searchResults = [];
        },

        removeProduct(productId) {
            this.selectedProducts = this.selectedProducts.filter(p => p.id !== productId);
        }
     }">
    <div class="flex items-center justify-between pb-3 border-b border-gray-100 flex-wrap gap-2">
        <div class="flex items-center gap-2">
            <i class="ph-bold ph-package text-brand-600 text-lg"></i>
            <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Related Store Products</h2>
        </div>
        <span class="text-xs font-semibold text-gray-400" x-text="`${selectedProducts.length} Products Attached`"></span>
    </div>

    <p class="text-xs text-gray-500">
        Attach relevant products from your store. They will be displayed at the end or inside the guide with live prices, offers, and direct "Add to Cart" buttons to drive conversions.
    </p>

    <!-- Search Input -->
    <div class="relative">
        <i class="ph ph-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-base"></i>
        <input type="text"
               x-model="searchQuery"
               @input.debounce.300ms="search()"
               placeholder="Search store products by title or SKU to attach..."
               class="w-full pl-10 pr-10 py-2.5 text-xs border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500 bg-gray-50 focus:bg-white transition-colors">
        <span x-show="isSearching" class="absolute right-3.5 top-1/2 -translate-y-1/2">
            <i class="ph ph-spinner animate-spin text-brand-600 text-sm"></i>
        </span>

        <!-- Search Results Dropdown -->
        <div x-show="searchResults.length > 0"
             @click.away="searchResults = []"
             class="absolute z-20 left-0 right-0 mt-1.5 bg-white rounded-xl border border-gray-200 shadow-lg max-h-60 overflow-y-auto divide-y divide-gray-100">
            <template x-for="p in searchResults" :key="p.id">
                <div class="p-2.5 hover:bg-gray-50 flex items-center justify-between gap-3 transition-colors">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-9 h-9 rounded-lg bg-gray-100 border border-gray-200 overflow-hidden shrink-0 flex items-center justify-center">
                            <img x-show="p.image" :src="p.image" class="w-full h-full object-cover">
                            <i x-show="!p.image" class="ph ph-package text-gray-400 text-base"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-gray-900 truncate" x-text="p.title"></p>
                            <p class="text-[11px] text-gray-500">SKU: <span x-text="p.sku || 'N/A'"></span> • ৳<span x-text="p.price"></span></p>
                        </div>
                    </div>
                    <button type="button"
                            @click="addProduct(p)"
                            class="px-3 py-1 text-xs font-bold text-brand-600 bg-brand-50 hover:bg-brand-600 hover:text-white rounded-lg transition-colors cursor-pointer shrink-0">
                        + Attach
                    </button>
                </div>
            </template>
        </div>
    </div>

    <!-- Currently Selected Products List -->
    <div class="space-y-2 pt-2">
        <template x-if="selectedProducts.length === 0">
            <div class="p-4 rounded-xl border border-dashed border-gray-200 text-center text-xs text-gray-400">
                No products attached yet. Search above to add products.
            </div>
        </template>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
            <template x-for="p in selectedProducts" :key="p.id">
                <div class="p-2.5 rounded-xl border border-gray-200 bg-white flex items-center justify-between gap-2.5 shadow-2xs">
                    <input type="hidden" name="related_product_ids[]" :value="p.id">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-9 h-9 rounded-lg bg-gray-100 border border-gray-200 overflow-hidden shrink-0 flex items-center justify-center">
                            <img x-show="p.image" :src="p.image" class="w-full h-full object-cover">
                            <i x-show="!p.image" class="ph ph-package text-gray-400 text-base"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-gray-900 truncate" x-text="p.title"></p>
                            <p class="text-[11px] text-gray-500">৳<span x-text="p.price"></span></p>
                        </div>
                    </div>
                    <button type="button"
                            @click="removeProduct(p.id)"
                            class="p-1 text-gray-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition-colors cursor-pointer shrink-0"
                            title="Remove">
                        <i class="ph-bold ph-x text-sm"></i>
                    </button>
                </div>
            </template>
        </div>
    </div>
</div>
