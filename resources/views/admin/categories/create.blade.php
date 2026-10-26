@extends('layouts.admin')

@section('title', 'Create Category - Admin Panel')
@section('header_title', 'Categories')

@section('content')
<div class="mb-8 flex items-center gap-4">
    <a href="{{ route('admin.categories.index') }}" class="p-2 bg-white border border-gray-200 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition-colors">
        <i class="ph ph-arrow-left text-lg"></i>
    </a>
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Create Category</h1>
        <p class="text-sm text-gray-500 mt-1">Add a new category to your store.</p>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden w-full">
    <form action="{{ route('admin.categories.store') }}" method="POST" enctype="multipart/form-data" class="p-6 sm:p-8" x-data="{ isSubmitting: false }" @submit="isSubmitting = true">
        @csrf
        
        <!-- Grid Layout -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            
            <!-- Left Column: Basic Info -->
            <div class="space-y-6">
                <h3 class="text-lg font-bold text-gray-900 border-b border-gray-100 pb-2">Basic Information</h3>
                
                <!-- Category Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Category Name *</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-colors"
                           placeholder="e.g. Men's Fashion">
                    @error('name')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Parent Category -->
                <div>
                    <label for="parent_id" class="block text-sm font-medium text-gray-700 mb-1">Parent Category</label>
                    <select id="parent_id" name="parent_id" 
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-colors">
                        <option value="">None (Root Category)</option>
                        @foreach($parentCategories as $parent)
                            <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                                {{ $parent->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Leave empty to create a top-level category.</p>
                </div>

                <!-- Sort Order -->
                <div>
                    <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                    <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}"
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-colors">
                </div>
            </div>

            <!-- Right Column: Settings & Media -->
            <div class="space-y-6">
                <h3 class="text-lg font-bold text-gray-900 border-b border-gray-100 pb-2">Settings & Media</h3>
                
                <!-- Category Image -->
                <div class="bg-gray-50 p-5 rounded-xl border border-gray-100" x-data="{ imageType: 'upload', imagePreview: null, errorMessage: null }">
                    <div class="flex items-center justify-between mb-4">
                        <label class="block text-sm font-semibold text-gray-700">Category Image</label>
                        <div class="flex bg-gray-200/80 rounded-lg p-1">
                            <button type="button" @click="imageType = 'upload'" :class="imageType === 'upload' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-700'" class="px-3 py-1 text-xs font-medium rounded-md transition-all">Upload File</button>
                            <button type="button" @click="imageType = 'url'" :class="imageType === 'url' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-700'" class="px-3 py-1 text-xs font-medium rounded-md transition-all">Image URL</button>
                        </div>
                    </div>
                    
                    <!-- Upload Input -->
                    <div x-show="imageType === 'upload'" class="relative w-full h-36 border-2 border-gray-300 border-dashed rounded-xl hover:bg-gray-100 transition-colors flex flex-col items-center justify-center cursor-pointer overflow-hidden group">
                        <input id="image" name="image" type="file" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" accept="image/*" 
                               @change="
                                   const file = $event.target.files[0];
                                   if (file) {
                                       if (file.size > 2 * 1024 * 1024) {
                                           errorMessage = 'File size must be less than 2MB.';
                                           imagePreview = null;
                                           $event.target.value = '';
                                       } else {
                                           errorMessage = null;
                                           imagePreview = URL.createObjectURL(file);
                                       }
                                   } else {
                                       imagePreview = null;
                                   }
                               " />
                        
                        <!-- Preview State -->
                        <template x-if="imagePreview">
                            <div class="absolute inset-0 w-full h-full">
                                <img :src="imagePreview" class="w-full h-full object-contain p-2" />
                                <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                    <span class="text-white text-sm font-medium"><i class="ph ph-arrows-clockwise mr-1"></i> Change Image</span>
                                </div>
                            </div>
                        </template>

                        <!-- Empty State -->
                        <template x-if="!imagePreview">
                            <div class="text-center px-4">
                                <div class="w-10 h-10 bg-white shadow-sm rounded-full flex items-center justify-center mx-auto mb-2 text-brand-600 group-hover:scale-110 transition-transform">
                                    <i class="ph ph-upload-simple text-xl"></i>
                                </div>
                                <p class="text-sm text-gray-600 font-medium"><span class="text-brand-600">Click to upload</span> or drag and drop</p>
                                <p class="text-xs text-gray-400 mt-1">SVG, PNG, JPG or GIF (max. 2MB)</p>
                            </div>
                        </template>
                    </div>

                    <!-- Error Message -->
                    <p x-show="errorMessage" x-text="errorMessage" class="text-sm text-red-500 mt-2 font-medium" style="display: none;"></p>
                    @error('image')
                        <p class="mt-2 text-sm text-red-500 font-medium">{{ $message }}</p>
                    @enderror

                    <!-- URL Input -->
                    <div x-show="imageType === 'url'" style="display: none;">
                        <div class="relative">
                            <i class="ph ph-link absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                            <input type="url" name="image_url" placeholder="https://example.com/image.jpg" class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors">
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Enter a direct image link (e.g. from an external server).</p>
                    </div>

                    <!-- Auto Convert WebP (Moved here) -->
                    <div class="flex items-center justify-between border-t border-gray-200 pt-4 mt-4" x-show="imageType === 'upload'">
                        <div>
                            <p class="text-sm font-semibold text-gray-900 flex items-center gap-1">Auto Convert to WebP <span class="bg-brand-100 text-brand-700 text-[10px] uppercase font-bold px-1.5 py-0.5 rounded-sm">Recommended</span></p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="auto_convert_webp" class="sr-only peer" {{ $autoConvertWebp ? 'checked' : '' }}>
                            <div class="w-9 h-5 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand-600"></div>
                        </label>
                    </div>
                </div>

                <!-- Switches -->
                <div class="bg-gray-50 p-5 rounded-xl border border-gray-100 space-y-6">
                    
                    <!-- Status -->
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">Active Status</p>
                            <p class="text-xs text-gray-500">Make this category visible on the site.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-600"></div>
                        </label>
                    </div>

                    <!-- Featured -->
                    <div class="flex items-center justify-between border-t border-gray-200 pt-5">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">Featured Category</p>
                            <p class="text-xs text-gray-500">Highlight this category on homepage.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_featured" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-600"></div>
                        </label>
                    </div>

                    <!-- Show on Header -->
                    <div class="flex items-center justify-between border-t border-gray-200 pt-5">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">Show on Header</p>
                            <p class="text-xs text-gray-500">Display this category in top navigation.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="show_on_header" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-600"></div>
                        </label>
                    </div>

                </div>

            </div>
        </div>

        <div class="mt-8 pt-6 border-t border-gray-100 flex justify-end gap-3">
            <a href="{{ route('admin.categories.index') }}" class="px-6 py-2.5 border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer">
                Cancel
            </a>
            <button type="submit" :disabled="isSubmitting" :class="isSubmitting ? 'opacity-70 cursor-not-allowed' : 'hover:bg-brand-700'" class="px-6 py-2.5 bg-brand-600 rounded-lg text-sm font-medium text-white transition-colors shadow-sm cursor-pointer flex items-center justify-center min-w-[140px]">
                <span x-show="!isSubmitting">Save Category</span>
                <span x-show="isSubmitting" style="display: none;" class="flex items-center gap-2">
                    <i class="ph ph-spinner animate-spin text-lg"></i> Saving...
                </span>
            </button>
        </div>
    </form>
</div>

@endsection
