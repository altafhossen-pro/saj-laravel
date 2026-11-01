<div x-data="visualSearchModal()"
     x-cloak
     x-show="isOpen"
     @open-visual-search.window="openModal()"
     @keydown.escape.window="closeModal()"
     style="display: none;"
     class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs">

    <!-- Modal Card -->
    <div @click.away="closeModal()"
         class="bg-white rounded-2xl shadow-2xl border border-gray-100 w-full max-w-xl max-h-[90vh] flex flex-col overflow-hidden transition-all transform animate-in fade-in zoom-in-95 duration-200">

        <!-- Modal Header -->
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-gray-50 via-white to-gray-50">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center text-xl shadow-xs">
                    <i class="ph-bold ph-camera"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-900 text-base leading-tight">Visual Product Search</h3>
                    <p class="text-xs text-gray-500">Find products instantly by uploading or taking a photo</p>
                </div>
            </div>
            <button type="button" @click="closeModal()" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
                <i class="ph ph-x text-lg"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="p-5 overflow-y-auto flex-1 space-y-4">

            <!-- Upload Area (when no image selected) -->
            <template x-if="!previewUrl">
                <div class="space-y-4">
                    <div @dragover.prevent="isDragging = true"
                         @dragleave.prevent="isDragging = false"
                         @drop.prevent="handleDrop($event)"
                         @click="$refs.imageFileInput.click()"
                         :class="isDragging ? 'border-brand-500 bg-brand-50/50 scale-[0.99]' : 'border-gray-200 hover:border-brand-400 hover:bg-gray-50/70'"
                         class="border-2 border-dashed rounded-2xl p-8 text-center cursor-pointer transition-all duration-200 group">
                        
                        <div class="w-14 h-14 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform shadow-xs">
                            <i class="ph-bold ph-image text-2xl"></i>
                        </div>
                        
                        <h4 class="font-bold text-gray-800 text-sm mb-1">Upload or Drag & Drop Product Image</h4>
                        <p class="text-xs text-gray-500 max-w-xs mx-auto">Upload a photo, screenshot, or snap with your camera to find matching products</p>
                        
                        <div class="mt-4 flex items-center justify-center gap-2 flex-wrap">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-gray-200 text-xs font-semibold text-gray-700 shadow-xs">
                                <i class="ph ph-upload-simple"></i> Choose File
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-gray-200 text-xs font-semibold text-gray-700 shadow-xs">
                                <i class="ph ph-camera"></i> Take Photo
                            </span>
                        </div>
                        
                        <input type="file"
                               x-ref="imageFileInput"
                               accept="image/jpeg,image/png,image/webp,image/jpg"
                               @change="handleFileSelect($event)"
                               class="hidden">
                    </div>

                    <!-- Quick hints -->
                    <div class="bg-gray-50 rounded-xl p-3 text-[11px] text-gray-500 flex items-start gap-2 border border-gray-100">
                        <i class="ph-bold ph-lightbulb text-amber-500 text-base shrink-0 mt-0.5"></i>
                        <span><strong>Tip:</strong> For best accuracy, use a clear photo centered on the product or a clean screenshot from Facebook / Instagram.</span>
                    </div>
                </div>
            </template>

            <!-- Scanning & Analyzing State -->
            <template x-if="previewUrl && isSearching">
                <div class="py-6 text-center space-y-4">
                    <div class="relative w-36 h-36 mx-auto rounded-2xl overflow-hidden shadow-md border-2 border-brand-500">
                        <img :src="previewUrl" class="w-full h-full object-cover">
                        <!-- Animated Scanner Line -->
                        <div class="absolute inset-x-0 h-1 bg-gradient-to-r from-transparent via-brand-400 to-transparent shadow-lg shadow-brand-500/50 animate-bounce"></div>
                        <div class="absolute inset-0 bg-brand-600/10 backdrop-blur-[1px]"></div>
                    </div>
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand-50 text-brand-700 text-xs font-bold">
                            <i class="ph ph-spinner animate-spin"></i>
                            <span>Scanning image features & matching catalog...</span>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1">Comparing visual gradients, shapes, and color signatures</p>
                    </div>
                </div>
            </template>

            <!-- Results View -->
            <template x-if="previewUrl && !isSearching">
                <div class="space-y-4">
                    <!-- Query Info Bar -->
                    <div class="flex items-center justify-between bg-gray-50 p-2.5 rounded-xl border border-gray-100">
                        <div class="flex items-center gap-2.5">
                            <img :src="previewUrl" class="w-10 h-10 rounded-lg object-cover border border-gray-200">
                            <div>
                                <span class="text-xs font-bold text-gray-900 block leading-tight">Searched Image</span>
                                <span class="text-[11px] text-gray-500" x-text="results.length + ' matching product(s) found'"></span>
                            </div>
                        </div>
                        <button type="button" @click="resetSearch()" class="text-xs font-semibold text-brand-600 hover:text-brand-800 px-2.5 py-1 rounded-lg hover:bg-brand-50 transition cursor-pointer">
                            <i class="ph ph-arrow-counter-clockwise mr-1"></i> Try Another Image
                        </button>
                    </div>

                    <!-- No Results Found -->
                    <template x-if="results.length === 0">
                        <div class="py-8 text-center space-y-3">
                            <div class="w-12 h-12 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center mx-auto text-xl">
                                <i class="ph ph-magnifying-glass"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-800 text-sm">No close visual match found</h4>
                                <p class="text-xs text-gray-500 max-w-xs mx-auto mt-1">We couldn't find a matching product with this photo. Try taking a photo with more light or a closer crop of the item.</p>
                            </div>
                            <button type="button" @click="resetSearch()" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                                Upload Another Photo
                            </button>
                        </div>
                    </template>

                    <!-- Matching Products Grid -->
                    <template x-if="results.length > 0">
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 max-h-[55vh] overflow-y-auto p-1">
                            <template x-for="item in results" :key="item.id">
                                <a :href="item.url"
                                   class="group block bg-white border border-gray-100 hover:border-brand-500 rounded-xl overflow-hidden shadow-xs hover:shadow-md transition-all duration-200">
                                    <!-- Product Image & Match Badge -->
                                    <div class="aspect-square bg-gray-50 relative overflow-hidden">
                                        <template x-if="item.image_url">
                                            <img :src="item.image_url" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                        </template>
                                        <template x-if="!item.image_url">
                                            <div class="w-full h-full flex items-center justify-center text-gray-300">
                                                <i class="ph ph-image text-2xl"></i>
                                            </div>
                                        </template>

                                        <!-- Match Score Badge -->
                                        <div class="absolute top-1.5 left-1.5">
                                            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md text-[10px] font-bold shadow-xs"
                                                  :class="item.score >= 80 ? 'bg-emerald-600 text-white' : (item.score >= 60 ? 'bg-indigo-600 text-white' : 'bg-gray-800/80 text-white backdrop-blur-xs')">
                                                <i class="ph-bold ph-sparkle text-[9px]"></i>
                                                <span x-text="item.score_label"></span>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Product Details -->
                                    <div class="p-2.5">
                                        <h5 class="text-xs font-bold text-gray-800 line-clamp-2 group-hover:text-brand-600 transition-colors leading-snug" x-text="item.title"></h5>
                                        <div class="mt-1.5 flex items-center justify-between">
                                            <span class="text-xs font-bold text-brand-600 font-mono" x-text="'৳' + item.base_price"></span>
                                            <span class="text-[10px] text-gray-400 group-hover:text-brand-600 group-hover:translate-x-0.5 transition-all">
                                                View &rarr;
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            </template>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>
</div>

<script>
    function visualSearchModal() {
        return {
            isOpen: false,
            isDragging: false,
            isSearching: false,
            previewUrl: null,
            results: [],

            openModal() {
                this.isOpen = true;
            },

            closeModal() {
                this.isOpen = false;
            },

            resetSearch() {
                this.previewUrl = null;
                this.results = [];
                this.isSearching = false;
                if (this.$refs.imageFileInput) {
                    this.$refs.imageFileInput.value = '';
                }
            },

            handleFileSelect(event) {
                const file = event.target.files && event.target.files[0];
                if (file) {
                    this.processImage(file);
                }
            },

            handleDrop(event) {
                this.isDragging = false;
                const file = event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files[0];
                if (file) {
                    this.processImage(file);
                }
            },

            processImage(file) {
                if (!file.type.match(/^image\//)) {
                    alert('Please select a valid image file.');
                    return;
                }

                this.previewUrl = URL.createObjectURL(file);
                this.isSearching = true;
                this.results = [];

                const formData = new FormData();
                formData.append('image', file);

                fetch('{{ route('api.search.image') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.results = data.results || [];
                    } else {
                        alert(data.message || 'Visual search failed.');
                    }
                })
                .catch(err => {
                    console.error('Visual search error:', err);
                    alert('Error searching by image. Please try again.');
                })
                .finally(() => {
                    this.isSearching = false;
                });
            }
        };
    }
</script>
