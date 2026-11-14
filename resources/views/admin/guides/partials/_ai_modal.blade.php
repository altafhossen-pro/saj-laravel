<!-- ================= AI GENERATED BUYING GUIDE MODAL ================= -->
<div x-data="aiGuideGenerator()" @open-ai-guide-modal.window="openModal()" x-cloak>
    <!-- Modal Backdrop -->
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">

        <!-- Modal Dialog -->
        <div @click.away="if(!loading) closeModal()"
             x-show="isOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-4"
             class="bg-white rounded-3xl shadow-2xl border border-gray-150 w-full max-w-2xl overflow-hidden relative">

            <!-- Modal Header -->
            <div class="px-6 py-4.5 border-b border-gray-150 flex items-center justify-between bg-linear-to-r from-purple-50/70 via-indigo-50/40 to-white">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-linear-to-tr from-purple-600 to-indigo-600 text-white flex items-center justify-center text-lg shadow-xs">
                        <i class="ph-bold ph-sparkle"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                            <span>AI Buying Guide Generator</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-700">Auto SEO & HTML</span>
                        </h2>
                        <p class="text-xs text-gray-500">Generate a comprehensive, SEO-optimized buying guide saved directly into pending drafts.</p>
                    </div>
                </div>

                <button type="button" @click="closeModal()" :disabled="loading"
                    class="w-8 h-8 rounded-full text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition flex items-center justify-center cursor-pointer disabled:opacity-40">
                    <i class="ph-bold ph-x text-sm"></i>
                </button>
            </div>

            <!-- Modal Body Form -->
            <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
                <!-- Mode Selector Tabs -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-2">Generation Source Mode</label>
                    <div class="grid grid-cols-2 gap-3 p-1 bg-gray-100 rounded-2xl">
                        <button type="button" @click="form.mode = 'product'"
                            class="py-2.5 px-3 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 cursor-pointer"
                            :class="form.mode === 'product' ? 'bg-white text-purple-700 shadow-xs' : 'text-gray-600 hover:text-gray-900'">
                            <i class="ph-bold ph-package text-base"></i>
                            <span>Based on a Product</span>
                        </button>
                        <button type="button" @click="form.mode = 'custom_topic'"
                            class="py-2.5 px-3 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 cursor-pointer"
                            :class="form.mode === 'custom_topic' ? 'bg-white text-purple-700 shadow-xs' : 'text-gray-600 hover:text-gray-900'">
                            <i class="ph-bold ph-pencil-line text-base"></i>
                            <span>Custom Topic / Niche</span>
                        </button>
                    </div>
                </div>

                <!-- Product Selector (when mode === 'product') -->
                <div x-show="form.mode === 'product'" class="space-y-2">
                    <label class="block text-xs font-bold text-gray-700">
                        Select Product from Catalog <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="text" x-model="productSearch" @input.debounce.300ms="searchProducts()"
                            placeholder="Type product name or SKU to search..."
                            class="w-full px-4 py-2.5 text-xs border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:outline-none bg-gray-50/50">

                        <!-- Selected Product Badge -->
                        <div x-show="selectedProduct" class="mt-2.5 p-3 rounded-xl bg-purple-50/70 border border-purple-200 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <template x-if="selectedProduct && selectedProduct.image">
                                    <img :src="selectedProduct.image" class="w-10 h-10 object-cover rounded-lg border border-purple-200">
                                </template>
                                <div>
                                    <h4 class="text-xs font-bold text-gray-900" x-text="selectedProduct?.title"></h4>
                                    <p class="text-[11px] text-purple-700 font-medium" x-text="'Price: ৳' + selectedProduct?.price + ' • SKU: ' + selectedProduct?.sku"></p>
                                </div>
                            </div>
                            <button type="button" @click="clearSelectedProduct()" class="text-xs text-red-500 hover:text-red-700 font-semibold cursor-pointer">
                                Change
                            </button>
                        </div>

                        <!-- Product Search Dropdown Results -->
                        <div x-show="searchingProducts || searchResults.length > 0"
                             class="absolute left-0 right-0 top-full mt-1.5 bg-white border border-gray-200 rounded-xl shadow-xl z-20 max-h-52 overflow-y-auto divide-y divide-gray-100">
                            <template x-if="searchingProducts">
                                <div class="p-3 text-center text-xs text-gray-400 flex items-center justify-center gap-2">
                                    <i class="ph-bold ph-circle-notch animate-spin text-purple-600"></i> Searching catalog...
                                </div>
                            </template>
                            <template x-for="p in searchResults" :key="p.id">
                                <div @click="selectProduct(p)" class="p-2.5 hover:bg-purple-50/60 cursor-pointer flex items-center justify-between gap-3 transition">
                                    <div class="flex items-center gap-2.5">
                                        <img :src="p.image" class="w-8 h-8 object-cover rounded-md border border-gray-100 shrink-0">
                                        <div>
                                            <div class="text-xs font-semibold text-gray-800 line-clamp-1" x-text="p.title"></div>
                                            <div class="text-[10px] text-gray-400" x-text="'SKU: ' + p.sku + ' • ৳' + p.price"></div>
                                        </div>
                                    </div>
                                    <span class="text-[10px] font-bold text-purple-600 bg-purple-50 px-2 py-0.5 rounded-full shrink-0">Select</span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Custom Topic Input (when mode === 'custom_topic') -->
                <div x-show="form.mode === 'custom_topic'" class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-700">
                        Topic or Target Keyword <span class="text-red-500">*</span>
                    </label>
                    <input type="text" x-model="form.topic"
                        placeholder="e.g. সেরা ব্রাইডাল জুয়েলারি কালেকশন ২০২৬ / স্কিন কেয়ার সিরাম গাইড"
                        class="w-full px-4 py-2.5 text-xs border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:outline-none bg-gray-50/50">
                    <p class="text-[11px] text-gray-400">Enter the subject or product niche you want the AI to write a buying guide about.</p>
                </div>

                <!-- Web Research & Market Enrichment Toggle -->
                <div class="p-3.5 bg-linear-to-r from-purple-50/50 to-indigo-50/50 border border-purple-150 rounded-2xl">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" x-model="form.web_research" class="mt-0.5 rounded text-purple-600 focus:ring-purple-500">
                        <div>
                            <span class="text-xs font-bold text-gray-800 flex items-center gap-1.5">
                                <i class="ph-bold ph-globe text-purple-600"></i> Enrich with Real-time Market & Buying Factors
                            </span>
                            <p class="text-[11px] text-gray-500 mt-0.5">
                                AI will evaluate real-world buying criteria, authenticity checklists, price-to-value tips, and genuine buyer FAQs for Bangladesh shoppers.
                            </p>
                        </div>
                    </label>
                </div>

                <!-- Admin Custom Instructions -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-700">Custom Instructions & Focus (Optional)</label>
                    <textarea x-model="form.instructions" rows="3"
                        placeholder="e.g. সহজ প্রাঞ্জল বাংলায় লিখবে, ৩টি প্রধান ফিচার হাইলাইট করবে, সাথে ৫টি FAQ রাখবে..."
                        class="w-full px-4 py-2.5 text-xs border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:outline-none bg-gray-50/50 resize-none"></textarea>

                    <!-- Quick Suggestion Chips -->
                    <div class="flex flex-wrap items-center gap-1.5 pt-1">
                        <span class="text-[10px] text-gray-400 font-medium">Quick Chips:</span>
                        <button type="button" @click="appendInstruction('সহজ ও প্রাঞ্জল বাংলায় লিখবে')" class="px-2 py-0.5 rounded-full text-[10px] bg-gray-100 hover:bg-purple-100 text-gray-700 cursor-pointer">+ সহজ বাংলা</button>
                        <button type="button" @click="appendInstruction('বাজেট ফ্রেন্ডলি অপশন ও মূল্য বিবেচনা পয়েন্ট যোগ করবে')" class="px-2 py-0.5 rounded-full text-[10px] bg-gray-100 hover:bg-purple-100 text-gray-700 cursor-pointer">+ বাজেট বিবেচনা</button>
                        <button type="button" @click="appendInstruction('কেনার আগে আসল-নকল চেনার উপায় পয়েন্ট আকারে দেবে')" class="px-2 py-0.5 rounded-full text-[10px] bg-gray-100 hover:bg-purple-100 text-gray-700 cursor-pointer">+ আসল-নকল চেনার উপায়</button>
                        <button type="button" @click="appendInstruction('৫টি প্রায়শই জিজ্ঞাসিত প্রশ্ন (FAQ) যোগ করবে')" class="px-2 py-0.5 rounded-full text-[10px] bg-gray-100 hover:bg-purple-100 text-gray-700 cursor-pointer">+ ৫টি FAQ</button>
                    </div>
                </div>

                <!-- Generation Success State Banner -->
                <div x-show="generatedResult" class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 space-y-3">
                    <div class="flex items-center gap-2 text-emerald-800 text-xs font-bold">
                        <i class="ph-bold ph-check-circle text-base text-emerald-600"></i>
                        <span>Guide Successfully Generated & Saved into AI Pending Drafts!</span>
                    </div>
                    <div class="text-xs text-gray-700">
                        <div class="font-bold" x-text="generatedResult?.title"></div>
                        <div class="text-[11px] text-gray-500 mt-0.5">Status: <span class="text-amber-700 font-semibold">Unpublished (Pending Review)</span></div>
                    </div>
                    <div class="flex items-center gap-2 pt-1">
                        <a :href="generatedResult?.preview_url" class="px-3 py-1.5 bg-purple-600 text-white font-bold rounded-xl text-xs hover:bg-purple-700 transition flex items-center gap-1.5">
                            <i class="ph-bold ph-eye"></i> Safe Preview
                        </a>
                        <a :href="generatedResult?.edit_url" class="px-3 py-1.5 bg-white border border-gray-200 text-gray-700 font-bold rounded-xl text-xs hover:bg-gray-100 transition flex items-center gap-1.5">
                            <i class="ph-bold ph-pencil"></i> Edit in Editor
                        </a>
                        <a href="{{ route('admin.guides.ai-pending') }}" class="px-3 py-1.5 text-purple-700 font-bold text-xs hover:underline ml-auto">
                            View All Pending →
                        </a>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-150 flex items-center justify-between">
                <div class="text-[11px] text-gray-400">
                    <i class="ph-bold ph-shield-check text-emerald-600"></i> Saved as <strong class="text-gray-600">Pending Draft</strong> (Safe from public/Google)
                </div>

                <div class="flex items-center gap-2.5">
                    <button type="button" @click="closeModal()" :disabled="loading"
                        class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-600 hover:text-gray-800 hover:bg-gray-200/60 transition cursor-pointer disabled:opacity-40">
                        Close
                    </button>

                    <button type="button" @click="generateGuide()" :disabled="loading || (!form.topic && !form.product_id)"
                        class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-linear-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 shadow-md transition flex items-center gap-2 cursor-pointer disabled:opacity-50">
                        <template x-if="loading">
                            <div class="flex items-center gap-2">
                                <i class="ph-bold ph-circle-notch animate-spin text-sm"></i>
                                <span>Generating Guide...</span>
                            </div>
                        </template>
                        <template x-if="!loading">
                            <div class="flex items-center gap-2">
                                <i class="ph-bold ph-sparkle text-sm"></i>
                                <span>Generate & Save Draft</span>
                            </div>
                        </template>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function aiGuideGenerator() {
        return {
            isOpen: false,
            loading: false,
            productSearch: '',
            searchingProducts: false,
            searchResults: [],
            selectedProduct: null,
            generatedResult: null,

            form: {
                mode: 'product',
                product_id: null,
                topic: '',
                instructions: '',
                web_research: true
            },

            openModal() {
                this.isOpen = true;
                this.generatedResult = null;
            },

            closeModal() {
                this.isOpen = false;
            },

            appendInstruction(text) {
                if (!this.form.instructions) {
                    this.form.instructions = text;
                } else {
                    this.form.instructions += ', ' + text;
                }
            },

            async searchProducts() {
                const q = this.productSearch.trim();
                if (q.length < 2) {
                    this.searchResults = [];
                    return;
                }
                this.searchingProducts = true;
                try {
                    const res = await fetch(`{{ route('admin.guides.search-products') }}?q=${encodeURIComponent(q)}`);
                    const data = await res.json();
                    this.searchResults = data.products || [];
                } catch (e) {
                    console.error('Product search error:', e);
                } finally {
                    this.searchingProducts = false;
                }
            },

            selectProduct(p) {
                this.selectedProduct = p;
                this.form.product_id = p.id;
                this.form.topic = p.title;
                this.searchResults = [];
                this.productSearch = '';
            },

            clearSelectedProduct() {
                this.selectedProduct = null;
                this.form.product_id = null;
                this.form.topic = '';
            },

            async generateGuide() {
                if (this.loading) return;
                this.loading = true;
                this.generatedResult = null;

                try {
                    const res = await fetch('{{ route('admin.guides.ai-generate') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(this.form)
                    });

                    const data = await res.json();
                    if (!res.ok || !data.success) {
                        alert(data.message || 'Generation failed. Please try again.');
                        return;
                    }

                    this.generatedResult = data;
                } catch (err) {
                    alert('Network error occurred during generation: ' + err.message);
                } finally {
                    this.loading = false;
                }
            }
        };
    }
</script>
