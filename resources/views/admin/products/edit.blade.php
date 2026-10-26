@extends('layouts.admin')

@section('title', 'Edit Product - Admin Panel')
@section('header_title', 'Products')

@section('content')
<div class="mb-6 flex items-center gap-4">
    <a href="{{ route('admin.products.index') }}" class="p-2 hover:bg-gray-200 rounded-lg transition-colors text-gray-500">
        <i class="ph ph-arrow-left text-lg"></i>
    </a>
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Edit Product: {{ $product->title }}</h1>
        <p class="text-sm text-gray-500 mt-1">Update product details and media.</p>
    </div>
</div>

<form action="{{ route('admin.products.update', $product->id) }}" method="POST" enctype="multipart/form-data" x-data="{ 
    activeTab: 'basic', 
    isSubmitting: false,
    productType: '{{ old('product_type', $product->product_type === 'variable' ? 'variable' : 'single') }}',
    init() {
        // Validation could go here
    }
}" @submit="isSubmitting = true">
    @csrf
    @method('PUT')
    <input type="hidden" name="product_type" x-model="productType">

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        
        <!-- Top Navigation: Horizontal Tabs -->
        <div class="flex flex-nowrap overflow-x-auto border-b border-gray-200 bg-white px-6 sm:px-10 pt-2 gap-8 scrollbar-hide">
    <button type="button" @click="activeTab = 'basic'" :class="activeTab === 'basic' ? 'border-brand-600 text-brand-600 font-bold' : 'border-transparent text-gray-500 hover:text-gray-800 hover:border-gray-300'" class="whitespace-nowrap py-4 border-b-2 transition-all flex items-center gap-2 text-sm font-medium -mb-px cursor-pointer">
                <i class="ph ph-info text-lg" :class="activeTab === 'basic' ? 'text-brand-600' : 'text-gray-400'"></i> Basic Info
            </button>
            <button type="button" @click="activeTab = 'media'" :class="activeTab === 'media' ? 'border-brand-600 text-brand-600 font-bold' : 'border-transparent text-gray-500 hover:text-gray-800 hover:border-gray-300'" class="whitespace-nowrap py-4 border-b-2 transition-all flex items-center gap-2 text-sm font-medium -mb-px cursor-pointer">
                <i class="ph ph-image text-lg" :class="activeTab === 'media' ? 'text-brand-600' : 'text-gray-400'"></i> Media & Image
            </button>
            <button type="button" @click="activeTab = 'variant'" :class="activeTab === 'variant' ? 'border-brand-600 text-brand-600 font-bold' : 'border-transparent text-gray-500 hover:text-gray-800 hover:border-gray-300'" class="whitespace-nowrap py-4 border-b-2 transition-all flex items-center gap-2 text-sm font-medium -mb-px cursor-pointer">
                <i class="ph ph-swatches text-lg" :class="activeTab === 'variant' ? 'text-brand-600' : 'text-gray-400'"></i> Variants
            </button>
            <button type="button" @click="activeTab = 'visibility'" :class="activeTab === 'visibility' ? 'border-brand-600 text-brand-600 font-bold' : 'border-transparent text-gray-500 hover:text-gray-800 hover:border-gray-300'" class="whitespace-nowrap py-4 border-b-2 transition-all flex items-center gap-2 text-sm font-medium -mb-px cursor-pointer">
                <i class="ph ph-eye text-lg" :class="activeTab === 'visibility' ? 'text-brand-600' : 'text-gray-400'"></i> Visibility
            </button>
            <button type="button" @click="activeTab = 'metrics'" :class="activeTab === 'metrics' ? 'border-brand-600 text-brand-600 font-bold' : 'border-transparent text-gray-500 hover:text-gray-800 hover:border-gray-300'" class="whitespace-nowrap py-4 border-b-2 transition-all flex items-center gap-2 text-sm font-medium -mb-px cursor-pointer">
                <i class="ph ph-chart-line-up text-lg" :class="activeTab === 'metrics' ? 'text-brand-600' : 'text-gray-400'"></i> Metrics
            </button>
            <button type="button" @click="activeTab = 'seo'" :class="activeTab === 'seo' ? 'border-brand-600 text-brand-600 font-bold' : 'border-transparent text-gray-500 hover:text-gray-800 hover:border-gray-300'" class="whitespace-nowrap py-4 border-b-2 transition-all flex items-center gap-2 text-sm font-medium -mb-px cursor-pointer">
                <i class="ph ph-magnifying-glass text-lg" :class="activeTab === 'seo' ? 'text-brand-600' : 'text-gray-400'"></i> SEO
            </button>
        </div>

        <!-- Tab Content Area -->
        <div class="p-6 sm:p-10 min-h-[500px]">
            
            <!-- Basic Info Tab -->
            <div x-show="activeTab === 'basic'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
                <h2 class="text-xl font-bold text-gray-900 mb-6 border-b border-gray-100 pb-4">Basic Information</h2>
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Product Name *</label>
                        <input type="text" name="name" x-ref="productName" value="{{ old('name', $product->title) }}" class="block w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-colors" placeholder="e.g. Elegant Cotton Shirt">
                    </div>

                    <!-- Slug Input -->
                    <div x-data="slugGenerator('{{ old('slug', $product->slug) }}', '{{ $product->id }}')">
                        <div class="flex justify-between items-center mb-1.5">
                            <label class="block text-sm font-medium text-gray-700">Product Slug *</label>
                            <button type="button" @click="generateSlug($refs.productName.value)" class="text-xs text-brand-600 hover:text-brand-700 font-medium flex items-center gap-1">
                                <i class="ph ph-magic-wand"></i> Generate from Name
                            </button>
                        </div>
                        <div class="relative">
                            <input type="text" name="slug" x-model="slug" @input.debounce.500ms="checkSlug()" class="block w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-colors" placeholder="e.g. elegant-cotton-shirt" required>
                            <!-- Status Icons -->
                            <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center">
                                <i x-show="isChecking" class="ph ph-spinner animate-spin text-gray-400" style="display:none;"></i>
                                <i x-show="!isChecking && isAvailable === true" class="ph ph-check-circle text-green-500 text-lg" style="display:none;"></i>
                                <i x-show="!isChecking && isAvailable === false" class="ph ph-x-circle text-red-500 text-lg" style="display:none;"></i>
                            </div>
                        </div>
                        <p x-show="!isChecking && isAvailable === false" class="text-xs text-red-500 mt-1" style="display:none;">This slug is already taken.</p>
                        <p x-show="!isChecking && isAvailable === true" class="text-xs text-green-500 mt-1" style="display:none;">Slug is available!</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex flex-col" x-data="categorySearch()">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Category *</label>
                            <div class="relative">
                                <input type="hidden" name="category_id" x-model="selectedId">
                                <input type="text" 
                                       x-model="searchQuery" 
                                       @focus="open = true; if(categories.length === 0) fetchCategories()"
                                       @input.debounce.300ms="fetchCategories()"
                                       @click.away="open = false"
                                       placeholder="Search and select category..."
                                       class="block w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-colors cursor-text"
                                       autocomplete="off">
                                
                                <!-- Clear button -->
                                <button type="button" x-show="selectedId" @click="clearSelection()" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500" style="display: none;">
                                    <i class="ph ph-x text-sm"></i>
                                </button>
                                <!-- Arrow -->
                                <i x-show="!selectedId" class="ph ph-caret-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>

                                <!-- Dropdown -->
                                <div x-show="open" 
                                     x-transition
                                     class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] max-h-60 overflow-y-auto"
                                     style="display: none;">
                                    
                                    <div x-show="isLoading" class="p-3 text-center text-sm text-gray-500 flex items-center justify-center gap-2">
                                        <i class="ph ph-spinner animate-spin"></i> Loading...
                                    </div>
                                    
                                    <div x-show="!isLoading && categories.length === 0" class="p-3 text-center text-sm text-gray-500" style="display: none;">
                                        No categories found.
                                    </div>

                                    <template x-for="cat in categories" :key="cat.id">
                                        <div @click="selectCategory(cat)"
                                             class="px-4 py-2.5 hover:bg-brand-50 cursor-pointer text-sm text-gray-700 border-b border-gray-50 last:border-0 transition-colors"
                                             :class="{'bg-brand-50 text-brand-700 font-medium': selectedId == cat.id}"
                                             x-text="cat.name">
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col" x-data="brandSearch()">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Brand</label>
                            <div class="relative">
                                <input type="hidden" name="brand_id" x-model="selectedBrandId">
                                <input type="text" 
                                       x-model="searchBrandQuery" 
                                       @focus="openBrand = true; if(brands.length === 0) fetchBrands()"
                                       @input.debounce.300ms="fetchBrands()"
                                       @click.away="openBrand = false"
                                       placeholder="Search and select brand..."
                                       class="block w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-colors cursor-text"
                                       autocomplete="off">
                                
                                <button type="button" x-show="selectedBrandId" @click="clearBrandSelection()" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500" style="display: none;">
                                    <i class="ph ph-x text-sm"></i>
                                </button>
                                <i x-show="!selectedBrandId" class="ph ph-caret-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>

                                <div x-show="openBrand" 
                                     x-transition
                                     class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] max-h-60 overflow-y-auto"
                                     style="display: none;">
                                    
                                    <div x-show="isBrandLoading" class="p-3 text-center text-sm text-gray-500 flex items-center justify-center gap-2">
                                        <i class="ph ph-spinner animate-spin"></i> Loading...
                                    </div>
                                    
                                    <div x-show="!isBrandLoading && brands.length === 0" class="p-3 text-center text-sm text-gray-500" style="display: none;">
                                        No brands found.
                                    </div>

                                    <template x-for="b in brands" :key="b.id">
                                        <div @click="selectBrand(b)"
                                             class="px-4 py-2.5 hover:bg-brand-50 cursor-pointer text-sm text-gray-700 border-b border-gray-50 last:border-0 transition-colors"
                                             :class="{'bg-brand-50 text-brand-700 font-medium': selectedBrandId == b.id}"
                                             x-text="b.name">
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col" x-data="{
                        tags: {{ $product->tags ? json_encode($product->tags) : '[]' }},
                        newTag: '',
                        addTag() {
                            if (this.newTag.trim() !== '' && !this.tags.includes(this.newTag.trim())) {
                                this.tags.push(this.newTag.trim());
                            }
                            this.newTag = '';
                        },
                        removeTag(index) {
                            this.tags.splice(index, 1);
                        }
                    }">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Search Tags</label>
                        <div class="flex flex-wrap items-center gap-2 p-2 bg-gray-50 border border-gray-200 rounded-lg focus-within:ring-2 focus-within:ring-brand-500 focus-within:border-brand-500 focus-within:bg-white transition-colors min-h-[46px]">
                            <template x-for="(tag, index) in tags" :key="index">
                                <span class="flex items-center gap-1.5 px-3 py-1 bg-white border border-gray-200 rounded-md text-sm font-medium text-gray-700 shadow-sm">
                                    <span x-text="tag"></span>
                                    <button type="button" @click="removeTag(index)" class="text-gray-400 hover:text-red-500 cursor-pointer">
                                        <i class="ph ph-x text-xs"></i>
                                    </button>
                                </span>
                            </template>
                            <input type="text" x-model="newTag" @keydown.enter.prevent="addTag()" @keydown.comma.prevent="addTag()" class="flex-1 bg-transparent min-w-[120px] text-sm focus:outline-none px-2" placeholder="Add tags (press Enter or Comma)">
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Used for optimizing search results. E.g. "summer, casual, blue"</p>
                        <!-- Hidden inputs for form submission -->
                        <template x-for="tag in tags">
                            <input type="hidden" name="tags[]" :value="tag">
                        </template>
                    </div>
                    
                    <div class="flex flex-col">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Short Description</label>
                        <textarea name="short_description" rows="2" class="tinymce-editor block w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-colors" placeholder="Brief summary of the product">{{ old('short_description', $product->short_description ?? '') }}</textarea>
                    </div>

                    <div class="flex flex-col">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Full Description</label>
                        <!-- In real scenario, use a rich text editor here -->
                        <textarea name="description" rows="5" class="tinymce-editor block w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-colors" placeholder="Detailed product description...">{{ old('description', $product->description ?? '') }}</textarea>
                    </div>
                    
                    <div class="flex flex-col">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Additional Info</label>
                        <textarea name="additional_info" rows="3" class="tinymce-editor block w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-colors" placeholder="Any additional information...">{{ old('additional_info', $product->additional_info ?? '') }}</textarea>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-2">
                        <div class="flex flex-col">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Warranty & Guarantee</label>
                            <textarea name="warranty_info" rows="3" class="tinymce-editor block w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-colors" placeholder="Warranty and guarantee information...">{{ old('warranty_info', $product->warranty_info ?? '') }}</textarea>
                        </div>
                        <div class="flex flex-col">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Shipping Info</label>
                            <textarea name="shipping_info" rows="3" class="tinymce-editor block w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-colors" placeholder="Shipping and delivery information...">{{ old('shipping_info', $product->delivery_info ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Bottom Action inside tab -->
                <div class="mt-8 pt-6 border-t border-gray-100 flex justify-end items-center">
                    <!-- Final Submit Button -->
                    <button type="submit" :disabled="isSubmitting" :class="isSubmitting ? 'opacity-70 cursor-not-allowed' : 'hover:bg-brand-700'" class="px-8 py-2.5 bg-brand-600 rounded-lg text-sm font-medium text-white transition-colors shadow-sm flex items-center justify-center min-w-[160px]">
                        <span x-show="!isSubmitting"><i class="ph ph-check-circle mr-2"></i> Update Product</span>
                        <span x-show="isSubmitting" style="display:none;"><i class="ph ph-spinner animate-spin mr-2"></i> Saving...</span>
                    </button>
                </div>
            </div>

            <!-- Media & Image Tab -->
            <div x-show="activeTab === 'media'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
                <h2 class="text-xl font-bold text-gray-900 mb-6 border-b border-gray-100 pb-4">Media & Images</h2>
                
                <!-- Define productMediaData function before it's used -->
                <script>
                function productMediaData() {
                    return {
                        thumbPreview: "{{ $product->featured_image ? Storage::url($product->featured_image) : '' }}" || null,
                        thumbError: "",
                        galleryFiles: [], // new files
                        galleryPreviews: {!! json_encode(array_map(fn($path) => Storage::url($path), $product->gallery ?? [])) !!},
                        galleryError: "",
                        handleThumb(e) {
                            this.thumbError = '';
                            const file = e.target.files[0];
                            if (file) {
                                if (file.size > 2 * 1024 * 1024) {
                                    this.thumbError = 'Thumbnail image must be less than 2MB.';
                                    e.target.value = '';
                                    this.thumbPreview = null;
                                    return;
                                }
                                const reader = new FileReader();
                                reader.onload = (event) => this.thumbPreview = event.target.result;
                                reader.readAsDataURL(file);
                            } else {
                                this.thumbPreview = null;
                            }
                        },
                        handleGallery(e) {
                            this.galleryError = '';
                            const newFiles = e.target.files;
                            let hasError = false;
                            
                            for(let i=0; i<newFiles.length; i++) {
                                if (newFiles[i].size > 2 * 1024 * 1024) {
                                    hasError = true;
                                    continue;
                                }
                                this.galleryFiles.push(newFiles[i]);
                            }
                            
                            if (hasError) {
                                this.galleryError = 'One or more images exceeded the 2MB limit and were skipped.';
                            }
                            
                            e.target.value = ''; // Reset input so same files can be re-selected if needed
                            this.updateGalleryPreviews();
                        },
                        removeGalleryImage(index) {
                            this.galleryFiles.splice(index, 1);
                            this.updateGalleryPreviews();
                        },
                        replaceGalleryImage(index, file) {
                            if (!file) return;
                            if (file.size > 2 * 1024 * 1024) {
                                this.galleryError = 'Replacement image must be less than 2MB.';
                                return;
                            }
                            this.galleryError = '';
                            this.galleryFiles[index] = file;
                            this.updateGalleryPreviews();
                        },
                        updateGalleryPreviews() {
                            const dt = new DataTransfer();
                            const newPreviews = new Array(this.galleryFiles.length);
                            let loadedCount = 0;
                            
                            if (this.galleryFiles.length === 0) {
                                this.galleryPreviews = [];
                                document.getElementById('galleryInput').files = dt.files;
                                return;
                            }

                            this.galleryFiles.forEach((file, index) => {
                                dt.items.add(file);
                                const reader = new FileReader();
                                reader.onload = (event) => {
                                    newPreviews[index] = event.target.result;
                                    loadedCount++;
                                    if (loadedCount === this.galleryFiles.length) {
                                        this.galleryPreviews = newPreviews;
                                    }
                                };
                                reader.readAsDataURL(file);
                            });
                            
                            document.getElementById('galleryInput').files = dt.files;
                        }
                    }
                }
                </script>

                <div class="space-y-6" x-data="productMediaData()">
                    <!-- Thumbnail -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700">Primary Thumbnail *</label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <span class="text-xs text-gray-600 font-medium">Convert & Optimize (WebP)</span>
                                <div class="relative inline-flex items-center">
                                    <input type="checkbox" name="auto_convert_thumbnail" class="sr-only peer">
                                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand-600"></div>
                                </div>
                            </label>
                        </div>
                        <label class="relative border-2 border-dashed rounded-xl p-8 flex flex-col items-center justify-center bg-gray-50 hover:bg-gray-100 transition-colors cursor-pointer group min-h-[200px] overflow-hidden"
                               :class="thumbError ? 'border-red-300 bg-red-50 hover:bg-red-50' : 'border-gray-300 hover:border-brand-400'">
                            <input type="file" name="thumbnail" accept="image/*" class="hidden" @change="handleThumb">
                            
                            <!-- Upload State -->
                            <div x-show="!thumbPreview" class="flex flex-col items-center text-center">
                                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-sm mb-3 group-hover:scale-105 transition-transform">
                                    <i class="ph text-2xl" :class="thumbError ? 'ph-warning-circle text-red-500' : 'ph-upload-simple text-brand-600'"></i>
                                </div>
                                <p class="text-sm font-medium text-gray-900">Click to upload or drag and drop</p>
                                <p class="text-xs text-gray-500 mt-1">SVG, PNG, JPG or GIF (MAX. 2MB)</p>
                            </div>

                            <!-- Preview State -->
                            <div x-show="thumbPreview" style="display: none;" class="absolute inset-0 w-full h-full p-2 bg-white">
                                <img :src="thumbPreview" class="w-full h-full object-contain rounded-lg">
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <span class="text-white font-medium text-sm flex items-center gap-2 px-4 py-2 bg-black/50 rounded-lg backdrop-blur-sm">
                                        <i class="ph ph-swap"></i> Change Image
                                    </span>
                                </div>
                            </div>
                        </label>
                        <p class="text-sm font-medium text-red-600 mt-2 flex items-center gap-1.5" x-show="thumbError" style="display: none;">
                            <i class="ph-fill ph-warning-circle text-lg"></i> <span x-text="thumbError"></span>
                        </p>
                    </div>

                    <!-- Gallery -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700">Product Gallery (Optional)</label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <span class="text-xs text-gray-600 font-medium">Convert & Optimize (WebP)</span>
                                <div class="relative inline-flex items-center">
                                    <input type="checkbox" name="auto_convert_gallery" class="sr-only peer">
                                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand-600"></div>
                                </div>
                            </label>
                        </div>
                        
                        <label class="border-2 border-dashed rounded-xl p-8 flex flex-col items-center justify-center bg-gray-50 hover:bg-gray-100 transition-colors cursor-pointer group" 
                               :class="[galleryError ? 'border-red-300 bg-red-50 hover:bg-red-50' : 'border-gray-300 hover:border-brand-400', galleryPreviews.length && !galleryError ? 'border-brand-300 bg-brand-50' : '']">
                            <input type="file" id="galleryInput" name="gallery[]" multiple accept="image/*" class="hidden" @change="handleGallery">
                            <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-sm mb-3 group-hover:scale-105 transition-transform">
                                <i class="ph text-2xl" :class="[galleryError ? 'ph-warning-circle text-red-500' : '', galleryPreviews.length && !galleryError ? 'ph-images text-brand-600' : 'ph-images text-brand-600']"></i>
                            </div>
                            <p class="text-sm font-medium" :class="galleryError ? 'text-red-700' : (galleryPreviews.length ? 'text-brand-700' : 'text-gray-900')" x-text="galleryPreviews.length ? galleryPreviews.length + ' image(s) selected (Click to add more)' : 'Add multiple images for gallery'"></p>
                        </label>
                        <p class="text-sm font-medium text-red-600 mt-2 flex items-center gap-1.5" x-show="galleryError" style="display: none;">
                            <i class="ph-fill ph-warning-circle text-lg"></i> <span x-text="galleryError"></span>
                        </p>

                        <!-- Gallery Previews Grid -->
                        <div x-show="galleryPreviews.length > 0" class="mt-4 grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-3" style="display: none;">
                            <template x-for="(preview, index) in galleryPreviews" :key="index">
                                <div class="aspect-square rounded-lg border border-gray-200 overflow-hidden bg-white shadow-sm relative group">
                                    <img :src="preview" class="w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center gap-2">
                                        <!-- Replace -->
                                        <label class="cursor-pointer text-white hover:text-brand-400 p-1 flex items-center justify-center w-8 h-8 rounded-full hover:bg-white/20 transition-colors" title="Replace Image">
                                            <input type="file" class="hidden" accept="image/*" @change="replaceGalleryImage(index, $event.target.files[0])">
                                            <i class="ph ph-swap text-xl"></i>
                                        </label>
                                        <!-- Remove -->
                                        <button type="button" @click.prevent="removeGalleryImage(index)" class="text-white hover:text-red-400 p-1 cursor-pointer flex items-center justify-center w-8 h-8 rounded-full hover:bg-white/20 transition-colors" title="Remove Image">
                                            <i class="ph ph-trash text-xl"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-gray-100 flex justify-end items-center">
                    <!-- Final Submit Button -->
                    <button type="submit" :disabled="isSubmitting" :class="isSubmitting ? 'opacity-70 cursor-not-allowed' : 'hover:bg-brand-700'" class="px-8 py-2.5 bg-brand-600 rounded-lg text-sm font-medium text-white transition-colors shadow-sm flex items-center justify-center min-w-[160px]">
                        <span x-show="!isSubmitting"><i class="ph ph-check-circle mr-2"></i> Update Product</span>
                        <span x-show="isSubmitting" style="display:none;"><i class="ph ph-spinner animate-spin mr-2"></i> Saving...</span>
                    </button>
                </div>
            </div>

            <!-- Variant Tab -->
            <div x-show="activeTab === 'variant'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
                <div class="flex items-center justify-between mb-6 border-b border-gray-100 pb-4">
                    <h2 class="text-xl font-bold text-gray-900">Product Variants</h2>
                    
                    <!-- Toggle Single / Variable -->
                    <div class="bg-gray-100 p-1 rounded-lg flex items-center">
                        <button type="button" @click="productType = 'single'" :class="productType === 'single' ? 'bg-white shadow-sm text-gray-900 font-medium' : 'text-gray-500 hover:text-gray-700'" class="px-4 py-1.5 text-sm rounded-md transition-all">
                            Single Product
                        </button>
                        <button type="button" @click="productType = 'variable'" :class="productType === 'variable' ? 'bg-white shadow-sm text-gray-900 font-medium' : 'text-gray-500 hover:text-gray-700'" class="px-4 py-1.5 text-sm rounded-md transition-all">
                            Variable Product
                        </button>
                    </div>
                </div>

                <!-- Single Product View -->
                <div x-show="productType === 'single'" class="space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex flex-col">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Regular Price (Current) *</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">৳</span>
                                <input type="number" name="regular_price" value="{{ old('regular_price', $product->base_price) }}" step="0.01" class="block w-full pl-8 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-colors" placeholder="0.00">
                            </div>
                        </div>
                        <div class="flex flex-col">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Old Price</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">৳</span>
                                <input type="number" name="old_price" value="{{ old('old_price', $product->variants->first()?->original_price) }}" step="0.01" class="block w-full pl-8 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-colors" placeholder="0.00 (Optional)">
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex flex-col">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">SKU (Stock Keeping Unit) <span class="text-xs text-gray-400 font-normal">(Auto-generated if blank)</span></label>
                            <input type="text" name="sku" value="{{ old('sku', $product->variants->first()?->sku) }}" @keydown.enter.prevent class="block w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-colors" placeholder="e.g. 261010191234">
                        </div>
                        <div class="flex flex-col">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Stock Quantity</label>
                            <input type="number" name="stock_quantity" value="{{ old('stock_quantity', $product->variants->first()?->stock_quantity ?? $product->total_stock) }}" class="block w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-colors" placeholder="100">
                        </div>
                    </div>
                </div>

                <!-- Variable Product View -->
                <div x-show="productType === 'variable'" style="display: none;">
                    <div class="bg-blue-50 border border-blue-100 rounded-xl p-5 mb-5 flex items-start gap-3">
                        <i class="ph-fill ph-info text-blue-500 text-xl mt-0.5"></i>
                        <div>
                            <h4 class="text-sm font-semibold text-blue-900">Variable Product Mode</h4>
                            <p class="text-xs text-blue-700 mt-1">Add variations like size, color, or material. Each variation can have its own SKU, price, and stock.</p>
                        </div>
                    </div>

                    <div class="space-y-4" x-data="productVariantData()">
                        <div class="flex flex-wrap items-center gap-4 mb-2">
                            <div class="flex items-center gap-3 bg-white border border-gray-200 p-4 rounded-xl shadow-sm">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="use_variant_images" value="1" {{ old('use_variant_images', $product->use_variant_images) ? 'checked' : '' }} class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-600"></div>
                                </label>
                                <div class="text-sm">
                                    <span class="font-medium text-gray-900 block">Show Image Instead of Color</span>
                                    <span class="text-gray-500 text-xs">Uses the variant image as selection button on frontend.</span>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 bg-white border border-gray-200 p-4 rounded-xl shadow-sm">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="auto_convert_variants" value="1" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-600"></div>
                                </label>
                                <div class="text-sm">
                                    <span class="font-medium text-gray-900 block">Convert & Optimize (WebP)</span>
                                    <span class="text-gray-500 text-xs">Auto-scale & convert variant images to lightweight WebP.</span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h4 class="text-sm font-semibold text-gray-900">Manually Add Variants</h4>
                            <p class="text-xs text-gray-500 mt-1">Select an option type (like Color or Size) and define each variant directly.</p>
                        </div>

                        <template x-for="(variant, vIndex) in variants" :key="vIndex">
                            <div class="border border-gray-200 rounded-xl p-5 bg-white shadow-sm mb-4 relative group">
                                <button type="button" @click="removeVariant(vIndex)" class="absolute top-4 right-4 text-gray-400 hover:text-red-500 transition-colors p-1" title="Remove Variant">
                                    <i class="ph ph-trash text-lg"></i>
                                </button>
                                
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
                                    <!-- Multiple Attributes per Variant -->
                                    <div class="col-span-1 md:col-span-4 border-b border-gray-100 pb-5">
                                        <div class="flex justify-between items-center mb-3 pr-10">
                                            <h5 class="text-sm font-semibold text-gray-800">Variant Attributes (e.g., Color + Size)</h5>
                                            <button type="button" @click="addAttribute(vIndex)" class="text-xs font-medium text-brand-600 hover:text-brand-800 flex items-center gap-1">
                                                <i class="ph ph-plus"></i> Add Attribute
                                            </button>
                                        </div>
                                        
                                        <div class="space-y-3">
                                            <template x-for="(attr, aIndex) in variant.attributes" :key="aIndex">
                                                <div class="flex items-center gap-3">
                                                    <div class="flex-1">
                                                        <input type="text" x-model="attr.type" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 outline-none" placeholder="Option Name (e.g. Color, Size, Weight)">
                                                    </div>
                                                    <div class="flex-1">
                                                        <input type="text" x-model="attr.value" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 outline-none" placeholder="Option Value (e.g. Red, XL, 1 KG)">
                                                    </div>
                                                    <button type="button" @click="removeAttribute(vIndex, aIndex)" class="w-8 h-8 flex items-center justify-center rounded bg-gray-100 text-gray-500 hover:text-red-500 hover:bg-red-50 transition-colors shrink-0" title="Remove Attribute">
                                                        <i class="ph ph-x"></i>
                                                    </button>
                                                    
                                                    <!-- Hidden Inputs for Submission -->
                                                    <input type="hidden" :name="'variants['+vIndex+'][attributes]['+aIndex+'][type]'" :value="attr.type">
                                                    <input type="hidden" :name="'variants['+vIndex+'][attributes]['+aIndex+'][value]'" :value="attr.value">
                                                </div>
                                            </template>
                                            <div x-show="variant.attributes.length === 0" class="text-xs text-gray-500 italic py-2">
                                                No attributes added. Click "Add Attribute" to define variation (e.g., Color: Red).
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Pricing and Inventory -->
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Price *</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-xs">৳</span>
                                            <input type="number" step="0.01" x-model="variant.price" class="w-full pl-7 pr-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 outline-none" placeholder="0.00">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Old Price</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-xs">৳</span>
                                            <input type="number" step="0.01" x-model="variant.old_price" class="w-full pl-7 pr-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 outline-none" placeholder="0.00">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1.5">SKU <span class="text-[10px] text-gray-400 font-normal">(Auto if blank)</span></label>
                                        <input type="text" x-model="variant.sku" @keydown.enter.prevent class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 outline-none" placeholder="e.g. 261010191234">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Stock Quantity *</label>
                                        <input type="number" x-model="variant.stock" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 outline-none" placeholder="0">
                                    </div>

                                    <!-- Variant Image -->
                                    <div class="col-span-1 md:col-span-4 pt-4 mt-2 border-t border-gray-100">
                                        <label class="block text-xs font-medium text-gray-600 mb-2">Variant Image (Used as selector on frontend)</label>
                                        <div class="flex items-center gap-4">
                                            <div class="relative w-16 h-16 rounded-lg bg-gray-50 border border-gray-200 overflow-hidden group cursor-pointer shadow-sm hover:border-brand-400 transition-colors">
                                                <input type="file" :name="'variants['+vIndex+'][image]'" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer z-10" @change="handleVariantImage($event, vIndex)">
                                                <img x-show="variant.imagePreview" :src="variant.imagePreview" class="w-full h-full object-cover">
                                                <div x-show="!variant.imagePreview" class="flex items-center justify-center w-full h-full text-gray-400 group-hover:text-brand-500 transition-colors">
                                                    <i class="ph ph-image text-2xl"></i>
                                                </div>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                <p>Upload a swatch or product image.</p>
                                                <p>Max size: 2MB.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Hidden form inputs -->
                                <input type="hidden" :name="'variants['+vIndex+'][id]'" :value="variant.id">
                                <input type="hidden" :name="'variants['+vIndex+'][price]'" :value="variant.price">
                                <input type="hidden" :name="'variants['+vIndex+'][old_price]'" :value="variant.old_price">
                                <input type="hidden" :name="'variants['+vIndex+'][sku]'" :value="variant.sku">
                                <input type="hidden" :name="'variants['+vIndex+'][stock]'" :value="variant.stock">
                            </div>
                        </template>

                        <button type="button" @click="addVariant" class="w-full py-4 border-2 border-dashed border-gray-300 rounded-xl text-brand-600 font-medium text-sm hover:border-brand-500 hover:bg-brand-50 transition-colors flex items-center justify-center gap-2">
                            <i class="ph ph-plus-circle text-lg"></i> Add New Variant
                        </button>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-gray-100 flex justify-end items-center">
                    <!-- Final Submit Button -->
                    <button type="submit" :disabled="isSubmitting" :class="isSubmitting ? 'opacity-70 cursor-not-allowed' : 'hover:bg-brand-700'" class="px-8 py-2.5 bg-brand-600 rounded-lg text-sm font-medium text-white transition-colors shadow-sm flex items-center justify-center min-w-[160px]">
                        <span x-show="!isSubmitting"><i class="ph ph-check-circle mr-2"></i> Update Product</span>
                        <span x-show="isSubmitting" style="display:none;"><i class="ph ph-spinner animate-spin mr-2"></i> Saving...</span>
                    </button>
                </div>
            </div>

            <!-- Visibility Tab -->
            <div x-show="activeTab === 'visibility'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
                <h2 class="text-xl font-bold text-gray-900 mb-6 border-b border-gray-100 pb-4">Product Visibility & Display</h2>
                
                <div class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Publish Product -->
                        <div class="flex items-center justify-between border border-gray-100 rounded-xl p-4 bg-gray-50">
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Publish Product</p>
                                <p class="text-xs text-gray-500">Make product visible on website storefront</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_active" class="sr-only peer" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                                <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-600"></div>
                            </label>
                        </div>
                        
                        <!-- Trending Now -->
                        <div class="flex items-center justify-between border border-gray-100 rounded-xl p-4 bg-gray-50">
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Trending Now</p>
                                <p class="text-xs text-gray-500">Show in Trending Now section</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_trending" class="sr-only peer" {{ old('is_trending', $product->is_trending) ? 'checked' : '' }}>
                                <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-600"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-gray-100 flex justify-between items-center">
                    <button type="button" @click="activeTab = 'variant'" class="px-6 py-2.5 border border-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors flex items-center gap-2">
                        <i class="ph ph-arrow-left"></i> Back
                    </button>
                    <div class="flex items-center gap-3">
                        <button type="button" @click="activeTab = 'metrics'" class="px-6 py-2.5 bg-gray-100 text-gray-800 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors flex items-center gap-2">
                            Next: Metrics <i class="ph ph-arrow-right"></i>
                        </button>
                        <button type="submit" :disabled="isSubmitting" :class="isSubmitting ? 'opacity-70 cursor-not-allowed' : 'hover:bg-brand-700'" class="px-6 py-2.5 bg-brand-600 rounded-lg text-sm font-medium text-white transition-colors shadow-sm flex items-center justify-center">
                            <span x-show="!isSubmitting"><i class="ph ph-check-circle mr-1.5"></i> Update Product</span>
                            <span x-show="isSubmitting" style="display:none;"><i class="ph ph-spinner animate-spin mr-1.5"></i> Saving...</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Metrics Tab (Sales Trend & Ratings) -->
            <div x-show="activeTab === 'metrics'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
                <h2 class="text-xl font-bold text-gray-900 mb-6 border-b border-gray-100 pb-4">Sales & Ratings Metrics Strategy</h2>
                
                <div class="space-y-6">
                    <!-- Sales Metrics & Trends Configuration -->
                    <div class="border border-brand-100 rounded-xl overflow-hidden" x-data="{ metricsMode: '{{ old('metrics_mode', $product->metrics_mode ?? 'organic') }}' }">
                        <div class="bg-brand-50 px-5 py-3 border-b border-brand-100 flex items-center gap-2">
                            <i class="ph ph-trend-up text-brand-600"></i>
                            <h3 class="font-medium text-sm text-brand-900">Sales & Sold Count Strategy</h3>
                        </div>
                        <div class="p-5 space-y-4 bg-white">
                            <div class="flex flex-col">
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Metrics Mode</label>
                                <select name="metrics_mode" x-model="metricsMode" class="block w-full px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-brand-500">
                                    <option value="organic">Organic (Show real sales data only)</option>
                                    <option value="optimized">Optimized (Show calculated growth trend over time)</option>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Organic shows actual order volume. Optimized simulates steady sold count growth based on parameters below.</p>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6" x-show="metricsMode === 'optimized'" style="display: none;">
                                <div class="flex flex-col">
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Trend Start Date</label>
                                    <input type="date" name="metrics_start_date" value="{{ old('metrics_start_date', $product->metrics_start_date ? \Carbon\Carbon::parse($product->metrics_start_date)->format('Y-m-d') : '') }}" class="block w-full px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-brand-500">
                                </div>
                                <div class="flex flex-col">
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Estimated Daily Sales Volume</label>
                                    <input type="number" name="metrics_daily_avg" value="{{ old('metrics_daily_avg', $product->metrics_daily_avg) }}" class="block w-full px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-brand-500" placeholder="e.g. 4">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Smart Ratings Strategy -->
                    <div class="border border-brand-100 rounded-xl overflow-hidden mt-6" x-data="{ ratingMode: '{{ old('rating_metrics_mode', $product->rating_metrics_mode ?? 'manual') }}' }">
                        <div class="bg-brand-50 px-5 py-3 border-b border-brand-100 flex items-center gap-2">
                            <i class="ph ph-star-half text-brand-600"></i>
                            <h3 class="font-medium text-sm text-brand-900">Ratings & Review Strategy</h3>
                        </div>
                        <div class="p-5 space-y-4 bg-white">
                            <div class="flex flex-col">
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Rating Mode</label>
                                <select name="rating_metrics_mode" x-model="ratingMode" class="block w-full px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-brand-500">
                                    <option value="optimized">Optimized (Auto generate ratings over time)</option>
                                    <option value="manual">Manual (Show only real ratings)</option>
                                </select>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6" x-show="ratingMode === 'optimized'" style="display: none;">
                                <div class="flex flex-col">
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Start Date</label>
                                    <input type="date" name="rating_metrics_start_date" value="{{ old('rating_metrics_start_date', $product->rating_metrics_start_date ? \Carbon\Carbon::parse($product->rating_metrics_start_date)->format('Y-m-d') : '') }}" class="block w-full px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-brand-500">
                                </div>
                                <div class="flex flex-col">
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Interval (Days)</label>
                                    <input type="number" name="rating_metrics_interval_days" value="{{ old('rating_metrics_interval_days', $product->rating_metrics_interval_days ?? 7) }}" class="block w-full px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-brand-500" min="1">
                                </div>
                                <div class="flex flex-col">
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Ratings per Interval</label>
                                    <input type="number" name="rating_metrics_count" value="{{ old('rating_metrics_count', $product->rating_metrics_count ?? 1) }}" class="block w-full px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-brand-500" min="1">
                                </div>
                                <div class="flex gap-4">
                                    <div class="flex flex-col w-1/2">
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Min Rating</label>
                                        <input type="number" step="0.1" name="rating_metrics_min" value="{{ old('rating_metrics_min', $product->rating_metrics_min ?? 4.5) }}" class="block w-full px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-brand-500" min="1" max="5">
                                    </div>
                                    <div class="flex flex-col w-1/2">
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Max Rating</label>
                                        <input type="number" step="0.1" name="rating_metrics_max" value="{{ old('rating_metrics_max', $product->rating_metrics_max ?? 5.0) }}" class="block w-full px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-brand-500" min="1" max="5">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-gray-100 flex justify-between items-center">
                    <button type="button" @click="activeTab = 'visibility'" class="px-6 py-2.5 border border-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors flex items-center gap-2">
                        <i class="ph ph-arrow-left"></i> Back
                    </button>
                    <div class="flex items-center gap-3">
                        <button type="button" @click="activeTab = 'seo'" class="px-6 py-2.5 bg-gray-100 text-gray-800 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors flex items-center gap-2">
                            Next: SEO <i class="ph ph-arrow-right"></i>
                        </button>
                        <button type="submit" :disabled="isSubmitting" :class="isSubmitting ? 'opacity-70 cursor-not-allowed' : 'hover:bg-brand-700'" class="px-6 py-2.5 bg-brand-600 rounded-lg text-sm font-medium text-white transition-colors shadow-sm flex items-center justify-center">
                            <span x-show="!isSubmitting"><i class="ph ph-check-circle mr-1.5"></i> Update Product</span>
                            <span x-show="isSubmitting" style="display:none;"><i class="ph ph-spinner animate-spin mr-1.5"></i> Saving...</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- SEO Tab -->
            <div x-show="activeTab === 'seo'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
                <h2 class="text-xl font-bold text-gray-900 mb-6 border-b border-gray-100 pb-4">Search Engine Optimization (SEO)</h2>
                
                <div class="space-y-6">
                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <div class="bg-gray-50 px-5 py-3 border-b border-gray-200 flex items-center gap-2">
                            <i class="ph ph-magnifying-glass text-gray-500"></i>
                            <h3 class="font-medium text-sm text-gray-900">Google Search Preview & Meta Tags</h3>
                        </div>
                        <div class="p-5 space-y-4 bg-white">
                            <div class="flex flex-col">
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Meta Title</label>
                                <input type="text" name="meta_title" value="{{ old('meta_title', $product->seo['meta_title'] ?? '') }}" class="block w-full px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-brand-500" placeholder="Custom page title for search engines (Optional)">
                            </div>
                            <div class="flex flex-col">
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Meta Description</label>
                                <textarea name="meta_description" rows="3" class="block w-full px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-brand-500" placeholder="Brief summary of product shown in Google search results...">{{ old('meta_description', $product->seo['meta_description'] ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-gray-100 flex justify-between items-center">
                    <button type="button" @click="activeTab = 'metrics'" class="px-6 py-2.5 border border-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors flex items-center gap-2">
                        <i class="ph ph-arrow-left"></i> Back
                    </button>
                    <!-- Final Submit Button -->
                    <button type="submit" :disabled="isSubmitting" :class="isSubmitting ? 'opacity-70 cursor-not-allowed' : 'hover:bg-brand-700'" class="px-8 py-2.5 bg-brand-600 rounded-lg text-sm font-medium text-white transition-colors shadow-sm flex items-center justify-center min-w-[160px]">
                        <span x-show="!isSubmitting"><i class="ph ph-check-circle mr-2"></i> Update Product</span>
                        <span x-show="isSubmitting" style="display:none;"><i class="ph ph-spinner animate-spin mr-2"></i> Saving...</span>
                    </button>
                </div>
            </div>

        </div>
    </div>
</form>

@push('scripts')
<script>
function categorySearch() {
    return {
        open: false,
        isLoading: false,
        searchQuery: '{{ $product->category ? addslashes($product->category->name) : '' }}',
        selectedId: '{{ $product->category_id ?? '' }}',
        categories: [],
        
        init() {
            // Optional: you can fetch immediately or wait for focus
        },
        
        fetchCategories() {
            this.isLoading = true;
            fetch(`/admin/dashboard/categories/search?q=${encodeURIComponent(this.searchQuery)}`)
                .then(res => res.json())
                .then(data => {
                    this.categories = data;
                    this.isLoading = false;
                });
        },
        
        selectCategory(cat) {
            this.selectedId = cat.id;
            this.searchQuery = cat.name;
            this.open = false;
        },
        
        clearSelection() {
            this.selectedId = '';
            this.searchQuery = '';
            this.fetchCategories(); // Reset list
            this.$el.querySelector('input[type="text"]').focus();
        }
    }
}

function productVariantData() {
    const existingVariants = {!! json_encode($product->variants ?? []) !!};
    
    // Map existing variants to the new manual structure
    const mappedVariants = existingVariants.map(v => {
        let attributesArray = [];
        if (v.attributes) {
            Object.keys(v.attributes).forEach(key => {
                attributesArray.push({ type: key, value: v.attributes[key] });
            });
        }
        
        if (attributesArray.length === 0) {
            attributesArray.push({ type: 'Color', value: '' });
        }

        return {
            id: v.id,
            attributes: attributesArray,
            sku: v.sku || '',
            price: v.current_price || '',
            old_price: v.original_price || '',
            stock: v.stock_quantity || '',
            imagePreview: v.images && v.images.length ? '{{ Storage::url('') }}' + v.images[0] : null
        };
    });

    return {
        variants: mappedVariants.length > 0 ? mappedVariants : [],

        addVariant() {
            this.variants.push({
                id: null,
                attributes: [{ type: 'Color', value: '' }],
                sku: '',
                price: '',
                old_price: '',
                stock: '',
                imagePreview: null
            });
        },
        addAttribute(vIndex) {
            this.variants[vIndex].attributes.push({ type: '', value: '' });
        },
        removeAttribute(vIndex, aIndex) {
            this.variants[vIndex].attributes.splice(aIndex, 1);
        },
        removeVariant(index) {
            this.variants.splice(index, 1);
        },
        handleVariantImage(e, index) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (event) => {
                    this.variants[index].imagePreview = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        }
    }
}

function slugGenerator(initialSlug, productId) {
    return {
        slug: initialSlug,
        productId: productId,
        isChecking: false,
        isAvailable: null,

        generateSlug(name) {
            if (!name) return;
            this.slug = name.toLowerCase().trim()
                .replace(/[^\w\s-]/g, '')
                .replace(/[\s_-]+/g, '-')
                .replace(/^-+|-+$/g, '');
            
            this.checkSlug();
        },

        checkSlug() {
            if (!this.slug) {
                this.isAvailable = null;
                return;
            }
            
            this.isChecking = true;
            fetch(`/admin/dashboard/products/check-slug?slug=${encodeURIComponent(this.slug)}&product_id=${this.productId}`)
                .then(res => res.json())
                .then(data => {
                    this.isAvailable = data.is_available;
                    this.slug = data.slug;
                    this.isChecking = false;
                })
                .catch(() => {
                    this.isChecking = false;
                    this.isAvailable = null;
                });
        }
    }
}

function brandSearch() {
    return {
        openBrand: false,
        isBrandLoading: false,
        searchBrandQuery: '{{ $product->brand_id ? addslashes(\App\Models\Product\Brand::find($product->brand_id)?->name ?? '') : '' }}',
        selectedBrandId: '{{ $product->brand_id ?? '' }}',
        brands: [],
        
        init() {},
        
        fetchBrands() {
            this.isBrandLoading = true;
            fetch(`/admin/dashboard/api/brands?search=${encodeURIComponent(this.searchBrandQuery)}&is_active=1`)
                .then(res => res.json())
                .then(data => {
                    this.brands = data.brands;
                    this.isBrandLoading = false;
                });
        },
        
        selectBrand(b) {
            this.selectedBrandId = b.id;
            this.searchBrandQuery = b.name;
            this.openBrand = false;
        },
        
        clearBrandSelection() {
            this.selectedBrandId = '';
            this.searchBrandQuery = '';
            this.fetchBrands();
            this.$el.querySelector('input[type="text"]').focus();
        }
    }
}
</script>

<x-tinymce-config />
@endpush
@endsection
