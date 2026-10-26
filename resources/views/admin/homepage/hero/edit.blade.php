@extends('layouts.admin')

@section('title', 'Edit Hero Slide - Admin Panel')
@section('header_title', 'Hero Banner')

@push('styles')
<link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
<style>
    .cropper-view-box, .cropper-face {
        border-radius: 4px;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
@endpush

@section('content')
<div class="mb-8 flex items-center gap-4">
    <a href="{{ route('admin.homepage.hero.index') }}" class="p-2 bg-white border border-gray-200 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition-colors">
        <i class="ph ph-arrow-left text-lg"></i>
    </a>
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Edit Slide</h1>
        <p class="text-sm text-gray-500 mt-1">Update slide content and settings.</p>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden w-full">
    <form action="{{ route('admin.homepage.hero.update', $heroSlide->id) }}" method="POST" enctype="multipart/form-data" class="p-6 sm:p-8" x-data="{ 
            type: '{{ old('type', $heroSlide->type) }}', 
            previewUrl: '{{ $heroSlide->type === 'image' ? $heroSlide->content : '' }}', 
            mobilePreviewUrl: '{{ $heroSlide->type === 'image' ? $heroSlide->mobile_content : '' }}', 
            isSubmitting: false,
            cropModalOpen: false,
            cropType: 'desktop',
            cropImageSrc: '',
            cropperInstance: null,
            
            initCropper(event, type) {
                let file = event.target.files[0];
                if(!file) return;
                this.cropType = type;
                this.cropImageSrc = URL.createObjectURL(file);
                this.cropModalOpen = true;
                
                this.$nextTick(() => {
                    if(this.cropperInstance) this.cropperInstance.destroy();
                    let imageElement = document.getElementById('cropper-image');
                    this.cropperInstance = new Cropper(imageElement, {
                        aspectRatio: type === 'desktop' ? 4/1 : 16/9,
                        viewMode: 1,
                        autoCropArea: 1,
                    });
                });
                
                event.target.value = '';
            },
            
            saveCrop() {
                if(!this.cropperInstance) return;
                this.cropperInstance.getCroppedCanvas().toBlob((blob) => {
                    let file = new File([blob], 'cropped.jpg', { type: 'image/jpeg', lastModified: Date.now() });
                    let dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    
                    if(this.cropType === 'desktop') {
                        document.getElementById('slide_image').files = dataTransfer.files;
                        this.previewUrl = URL.createObjectURL(blob);
                    } else {
                        document.getElementById('mobile_slide_image').files = dataTransfer.files;
                        this.mobilePreviewUrl = URL.createObjectURL(blob);
                    }
                    this.cropModalOpen = false;
                }, 'image/jpeg', 0.9);
            }
        }" @submit="isSubmitting = true">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            
            <!-- Left Column: Basic Info -->
            <div class="space-y-6">
                <h3 class="text-lg font-bold text-gray-900 border-b border-gray-100 pb-2">Basic Information</h3>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Slide Type <span class="text-red-500">*</span></label>
                    <select name="type" x-model="type" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-colors">
                        <option value="image">Image Slide</option>
                        <option value="html">Custom HTML</option>
                    </select>
                    @error('type')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Target Link (Optional)</label>
                    <input type="url" name="link" value="{{ old('link', $heroSlide->link) }}" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-colors" placeholder="https://example.com/promo">
                    @error('link')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $heroSlide->sort_order) }}" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-colors">
                    @error('sort_order')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-center justify-between border-t border-gray-200 pt-5 mt-5">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">Active Status</p>
                        <p class="text-xs text-gray-500">Show this slide on the homepage.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" class="sr-only peer" {{ $heroSlide->is_active ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-600"></div>
                    </label>
                </div>
            </div>

            <!-- Right Column: Media / Content -->
            <div class="space-y-6">
                <h3 class="text-lg font-bold text-gray-900 border-b border-gray-100 pb-2">Content</h3>
                
                <!-- Image Upload Content -->
                <div x-show="type === 'image'" class="space-y-6">
                    <div class="bg-gray-50 p-5 rounded-xl border border-gray-100">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Desktop Image (Ratio 4:1) <span class="text-red-500">*</span></label>
                        <input type="file" name="image" accept="image/*" class="hidden" id="slide_image_upload" @change="initCropper($event, 'desktop')">
                        <input type="file" name="image" class="hidden" id="slide_image">
                        
                        <label for="slide_image_upload" class="relative w-full aspect-[4/1] border-2 border-gray-300 border-dashed rounded-xl hover:bg-gray-100 transition-colors flex flex-col items-center justify-center cursor-pointer overflow-hidden group bg-white">
                            <div x-show="previewUrl" class="absolute inset-0 w-full h-full p-2">
                                <img :src="previewUrl" class="w-full h-full object-cover rounded-lg">
                                <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity rounded-lg">
                                    <span class="text-white text-sm font-medium"><i class="ph ph-arrows-clockwise mr-1"></i> Change Image</span>
                                </div>
                            </div>
                            <div x-show="!previewUrl" class="text-center px-4">
                                <div class="w-10 h-10 bg-white shadow-sm rounded-full flex items-center justify-center mx-auto mb-2 text-brand-600 group-hover:scale-110 transition-transform">
                                    <i class="ph ph-desktop text-xl"></i>
                                </div>
                                <p class="text-sm text-gray-600 font-medium">Click to upload desktop image</p>
                            </div>
                        </label>
                        @error('image')<p class="mt-2 text-sm text-red-500 font-medium">{{ $message }}</p>@enderror
                    </div>

                    <div class="bg-gray-50 p-5 rounded-xl border border-gray-100">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Mobile Image (Ratio 16:9)</label>
                        <input type="file" name="mobile_image" accept="image/*" class="hidden" id="mobile_slide_image_upload" @change="initCropper($event, 'mobile')">
                        <input type="file" name="mobile_image" class="hidden" id="mobile_slide_image">
                        
                        <label for="mobile_slide_image_upload" class="relative w-full max-w-[250px] aspect-[16/9] border-2 border-gray-300 border-dashed rounded-xl hover:bg-gray-100 transition-colors flex flex-col items-center justify-center cursor-pointer overflow-hidden group bg-white">
                            <div x-show="mobilePreviewUrl" class="absolute inset-0 w-full h-full p-2">
                                <img :src="mobilePreviewUrl" class="w-full h-full object-cover rounded-lg">
                                <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity rounded-lg">
                                    <span class="text-white text-sm font-medium"><i class="ph ph-arrows-clockwise mr-1"></i> Change Image</span>
                                </div>
                            </div>
                            <div x-show="!mobilePreviewUrl" class="text-center px-4">
                                <div class="w-10 h-10 bg-white shadow-sm rounded-full flex items-center justify-center mx-auto mb-2 text-brand-600 group-hover:scale-110 transition-transform">
                                    <i class="ph ph-device-mobile text-xl"></i>
                                </div>
                                <p class="text-xs text-gray-600 font-medium">Upload mobile</p>
                            </div>
                        </label>
                        @error('mobile_image')<p class="mt-2 text-sm text-red-500 font-medium">{{ $message }}</p>@enderror
                    </div>
                </div>

                <!-- HTML Content -->
                <div x-show="type === 'html'" class="space-y-6" style="display: none;">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Desktop HTML <span class="text-red-500">*</span></label>
                        <textarea name="html_content" rows="4" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-colors">{{ old('html_content', $heroSlide->type === 'html' ? $heroSlide->content : '') }}</textarea>
                        @error('html_content')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mobile HTML (Optional)</label>
                        <textarea name="mobile_html_content" rows="4" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 focus:bg-white transition-colors">{{ old('mobile_html_content', $heroSlide->type === 'html' ? $heroSlide->mobile_content : '') }}</textarea>
                        @error('mobile_html_content')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-8 pt-6 border-t border-gray-100 flex justify-end gap-3">
            <a href="{{ route('admin.homepage.hero.index') }}" class="px-6 py-2.5 border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer">
                Cancel
            </a>
            <button type="submit" :disabled="isSubmitting" :class="isSubmitting ? 'opacity-70 cursor-not-allowed' : 'hover:bg-brand-700'" class="px-6 py-2.5 bg-brand-600 rounded-lg text-sm font-medium text-white transition-colors shadow-sm cursor-pointer flex items-center justify-center min-w-[140px]">
                <span x-show="!isSubmitting">Update Slide</span>
                <span x-show="isSubmitting" style="display: none;" class="flex items-center gap-2">
                    <i class="ph ph-spinner animate-spin text-lg"></i> Updating...
                </span>
            </button>
        </div>

        <!-- Cropper Modal -->
        <div x-show="cropModalOpen" style="display: none;" class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-900/80 backdrop-blur-sm p-4">
            <div class="bg-white rounded-2xl overflow-hidden shadow-2xl w-full max-w-4xl flex flex-col h-[80vh]">
                <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h3 class="text-lg font-bold text-gray-800">Crop <span x-text="cropType === 'desktop' ? 'Desktop' : 'Mobile'"></span> Image</h3>
                    <button @click="cropModalOpen = false" type="button" class="text-gray-400 hover:text-red-500"><i class="ph ph-x text-xl"></i></button>
                </div>
                <div class="flex-1 bg-black overflow-hidden relative p-4">
                    <div class="w-full h-full relative flex items-center justify-center">
                        <img :src="cropImageSrc" id="cropper-image" style="display: block; max-width: 100%;">
                    </div>
                </div>
                <div class="p-4 border-t border-gray-100 flex justify-end gap-3 bg-gray-50">
                    <button @click="cropModalOpen = false" type="button" class="px-5 py-2.5 border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors">Cancel</button>
                    <button @click="saveCrop()" type="button" class="px-5 py-2.5 bg-brand-600 rounded-lg text-sm font-medium text-white hover:bg-brand-700 transition-colors shadow-sm">Save Crop</button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
