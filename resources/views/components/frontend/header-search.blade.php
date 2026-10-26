@props(['class' => 'max-w-md w-full relative hidden sm:block'])

@php
    try {
        $placeholderCategories = \App\Models\Category::whereNull('parent_id')->inRandomOrder()->limit(5)->pluck('name')->toArray();
    } catch (\Exception $e) {
        $placeholderCategories = [];
    }
    
    if(empty($placeholderCategories)) {
        $placeholderCategories = ['Gadgets', 'Shoes', 'Electronics', 'Clothing', 'Accessories'];
    }
@endphp

<div class="{{ $class }}"
     x-data="{ 
        query: @js(request()->query('q', '')), 
        results: [], 
        hasMore: false,
        loading: false, 
        show: false,
        isFocused: false,
        
        words: @js($placeholderCategories),
        placeholderText: '',
        wordIndex: 0,
        charIndex: 0,
        isDeleting: false,
        
        init() {
            this.typeWriter();
        },
        
        typeWriter() {
            if (this.words.length === 0) return;
            const currentWord = this.words[this.wordIndex];
            
            if (this.isDeleting) {
                this.placeholderText = currentWord.substring(0, this.charIndex - 1);
                this.charIndex--;
            } else {
                this.placeholderText = currentWord.substring(0, this.charIndex + 1);
                this.charIndex++;
            }

            let typeSpeed = this.isDeleting ? 50 : 100;

            if (!this.isDeleting && this.charIndex === currentWord.length) {
                typeSpeed = 2000;
                this.isDeleting = true;
            } else if (this.isDeleting && this.charIndex === 0) {
                this.isDeleting = false;
                this.wordIndex = (this.wordIndex + 1) % this.words.length;
                typeSpeed = 500;
            }

            setTimeout(() => {
                // Ensure Alpine re-evaluates
                this.placeholderText = this.placeholderText; 
                this.typeWriter();
            }, typeSpeed);
        },

        async performSearch() {
            if (this.query.trim().length < 1) {
                this.results = [];
                this.hasMore = false;
                this.show = false;
                return;
            }
            this.loading = true;
            this.show = true;
            try {
                let response = await fetch('/api/search?q=' + encodeURIComponent(this.query.trim()));
                let data = await response.json();
                this.results = data.results || [];
                this.hasMore = data.has_more || false;
            } catch (e) {
                console.error(e);
            } finally {
                this.loading = false;
            }
        },
        goToSearch() {
            if(this.query.trim().length > 0) {
                window.location.href = '/search?q=' + encodeURIComponent(this.query.trim());
            }
        },
        onSuggestionClick(item) {
            // Meta Pixel Search Track on suggestion selection
            if (typeof window.trackFbEvent === 'function') {
                window.trackFbEvent('Search', {
                    search_string: this.query.trim(),
                    content_ids: [String(item.id)],
                    content_type: 'product'
                });
            }

            try {
                var payload = JSON.stringify({
                    q: this.query.trim(),
                    product_id: item.id,
                    source: 'suggestion',
                    _token: '{{ csrf_token() }}'
                });
                if (navigator.sendBeacon) {
                    var blob = new Blob([payload], { type: 'application/json' });
                    navigator.sendBeacon('/api/search/log', blob);
                } else {
                    fetch('/api/search/log', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: payload,
                        keepalive: true
                    });
                }
            } catch(e) {}
        }
     }"
     @click.away="show = false; isFocused = false"
>
    <div class="relative">
        <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
        <input type="text" 
               x-model="query" 
               @input.debounce.300ms="performSearch()"
               @focus="isFocused = true; if(query.length > 0) show = true"
               @blur="isFocused = false"
               @keydown.enter="goToSearch()"
               :placeholder="isFocused ? 'Search for products...' : 'Search ' + placeholderText" 
               class="w-full bg-gray-50 border border-gray-200 rounded-lg pl-10 pr-14 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-shadow">
        
        <button @click="goToSearch()" class="absolute right-1.5 top-1/2 -translate-y-1/2 h-7 px-3 flex items-center justify-center bg-brand-600 hover:bg-brand-700 text-white rounded transition-colors cursor-pointer" title="Search">
            <i class="ph ph-magnifying-glass font-bold text-sm"></i>
        </button>
    </div>
    <!-- Search Results Dropdown -->
    <div x-show="show" style="display: none;"
         class="absolute top-full left-0 right-0 mt-2 bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden z-[100]"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0">
         
         <div x-show="loading" class="p-4 text-center text-sm text-gray-500 flex items-center justify-center gap-2">
             <i class="ph ph-spinner animate-spin text-lg"></i> Searching...
         </div>
         
         <div x-show="!loading && results.length === 0 && query.length > 0" class="p-4 text-center text-sm text-gray-500">
             No products found matching "<span x-text="query" class="font-semibold text-gray-800"></span>"
         </div>
         
         <ul x-show="!loading && results.length > 0" class="divide-y divide-gray-100 max-h-[400px] overflow-y-auto">
             <template x-for="item in results" :key="item.id">
                 <li>
                     <a :href="'/product/' + item.slug" @click="onSuggestionClick(item)" class="flex items-center gap-3 p-3 hover:bg-gray-50 transition-colors group">
                         <!-- Image -->
                         <div class="w-12 h-12 rounded-lg bg-gray-100 overflow-hidden shrink-0 relative">
                            <template x-if="item.featured_image">
                                <img :src="item.featured_image.startsWith('http') ? item.featured_image : '/storage/' + item.featured_image" class="absolute inset-0 w-full h-full object-cover">
                            </template>
                            <template x-if="!item.featured_image">
                                <div class="absolute inset-0 flex items-center justify-center text-gray-300">
                                    <i class="ph ph-image text-xl"></i>
                                </div>
                            </template>
                         </div>
                         <!-- Info -->
                         <div class="flex-1 min-w-0">
                             <h4 class="text-sm font-medium text-gray-800 truncate group-hover:text-brand-600 transition-colors" x-text="item.title"></h4>
                             <!-- Price -->
                             <div class="text-xs mt-0.5">
                                 <template x-if="!item.price_range || item.price_range.length === 0">
                                     <span class="font-bold text-brand-600" x-text="'৳' + item.base_price"></span>
                                 </template>
                                 <template x-if="item.price_range && item.price_range.length > 1">
                                     <span class="font-bold text-brand-600" x-text="'৳' + item.price_range[0] + ' - ৳' + item.price_range[1]"></span>
                                 </template>
                             </div>
                         </div>
                     </a>
                 </li>
             </template>
         </ul>
         
         <!-- See More -->
         <div x-show="hasMore" style="display: none;" class="p-3 border-t border-gray-100 text-center bg-gray-50">
             <button @click="goToSearch()" class="text-brand-600 font-semibold text-sm hover:text-brand-700 transition-colors inline-flex items-center gap-1 cursor-pointer">
                 See all results <i class="ph ph-arrow-right"></i>
             </button>
         </div>
    </div>
</div>
