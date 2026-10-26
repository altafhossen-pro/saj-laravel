@extends('layouts.admin')

@section('title', 'Branding Settings - Admin Panel')
@section('header_title', 'Branding Settings')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Back Button & Title -->
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('admin.settings.index') }}" class="w-10 h-10 rounded-full bg-white shadow-sm flex items-center justify-center text-gray-500 hover:text-brand-600 hover:shadow transition-all duration-300 border border-gray-100">
            <i class="ph ph-arrow-left text-xl"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Branding Settings</h1>
            <p class="text-sm text-gray-500 mt-1">Manage your website's logo, favicon, and visual identity.</p>
        </div>
    </div>

    <!-- Main Card -->
    <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-gray-100 relative overflow-hidden">
        <!-- Decorative Header Element -->
        <div class="absolute top-0 left-0 right-0 h-3 bg-gradient-to-r from-brand-400 to-brand-600"></div>

        <form action="{{ route('admin.settings.branding.update') }}" method="POST" enctype="multipart/form-data" class="space-y-8 mt-4" x-data="{ logoPreview: '{{ $logo ?? '' }}', faviconPreview: '{{ $favicon ?? '' }}', isSubmitting: false }" @submit="isSubmitting = true">
            @csrf

            <!-- Form Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                <!-- Site Logo -->
                <div class="flex flex-col gap-3">
                    <label class="text-sm font-bold text-gray-800 cursor-pointer" for="site_logo">Primary Site Logo</label>
                    <p class="text-xs text-gray-500 leading-relaxed max-w-sm">This logo will appear on the header of the website, admin panel, and emails. Recommended size: 250x60px.</p>
                    
                    <div class="mt-2 relative">
                        <input type="file" id="site_logo" name="site_logo" accept="image/*" class="hidden" @change="logoPreview = URL.createObjectURL($event.target.files[0])">
                        
                        <label for="site_logo" class="cursor-pointer block relative rounded-2xl border-2 border-dashed border-gray-200 hover:border-brand-400 transition-colors duration-300 overflow-hidden bg-gray-50 w-full h-48 group">
                            
                            <!-- Image Preview state -->
                            <div x-show="logoPreview" class="absolute inset-0 flex items-center justify-center p-4 bg-gray-100/50">
                                <img :src="logoPreview" class="max-w-full max-h-full object-contain drop-shadow-sm" alt="Logo preview">
                            </div>

                            <!-- Empty State -->
                            <div x-show="!logoPreview" class="absolute inset-0 flex flex-col items-center justify-center text-gray-400 group-hover:text-brand-500 transition-colors">
                                <i class="ph ph-upload-simple text-4xl mb-2"></i>
                                <span class="text-sm font-medium">Click to upload logo</span>
                            </div>

                            <!-- Overlay on hover if image exists -->
                            <div x-show="logoPreview" class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center text-white backdrop-blur-[2px]">
                                <div class="flex flex-col items-center gap-2">
                                    <i class="ph ph-swap text-2xl"></i>
                                    <span class="text-sm font-semibold">Change Logo</span>
                                </div>
                            </div>
                        </label>
                        @error('site_logo')
                            <p class="text-red-500 text-xs mt-2 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Favicon -->
                <div class="flex flex-col gap-3">
                    <label class="text-sm font-bold text-gray-800 cursor-pointer" for="site_favicon">Browser Favicon</label>
                    <p class="text-xs text-gray-500 leading-relaxed max-w-sm">The small icon that appears in the browser tabs and bookmarks. Recommended size: 32x32px or 64x64px.</p>
                    
                    <div class="mt-2 relative flex flex-col items-start">
                        <input type="file" id="site_favicon" name="site_favicon" accept="image/*" class="hidden" @change="faviconPreview = URL.createObjectURL($event.target.files[0])">
                        
                        <label for="site_favicon" class="cursor-pointer block relative rounded-2xl border-2 border-dashed border-gray-200 hover:border-brand-400 transition-colors duration-300 overflow-hidden bg-gray-50 w-32 h-32 group">
                            
                            <!-- Image Preview state -->
                            <div x-show="faviconPreview" class="absolute inset-0 flex items-center justify-center p-2 bg-gray-100/50">
                                <img :src="faviconPreview" class="w-16 h-16 object-contain drop-shadow-sm" alt="Favicon preview">
                            </div>

                            <!-- Empty State -->
                            <div x-show="!faviconPreview" class="absolute inset-0 flex flex-col items-center justify-center text-gray-400 group-hover:text-brand-500 transition-colors">
                                <i class="ph ph-image-square text-3xl mb-1"></i>
                                <span class="text-xs font-medium px-2 text-center">Upload icon</span>
                            </div>

                            <!-- Overlay on hover if image exists -->
                            <div x-show="faviconPreview" class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center text-white backdrop-blur-[2px]">
                                <i class="ph ph-swap text-xl"></i>
                            </div>
                        </label>
                        @error('site_favicon')
                            <p class="text-red-500 text-xs mt-2 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

            </div>

            <!-- SEO & Metadata Section -->
            <div class="pt-8 border-t border-gray-100">
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <i class="ph ph-globe text-brand-600 text-xl"></i> Homepage SEO & Metadata
                    </h3>
                    <p class="text-xs text-gray-500 mt-1">Configure search engine titles, snippets, keywords and social media sharing cards (Open Graph).</p>
                </div>

                <div class="space-y-6">
                    <!-- Homepage Title -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label for="site_title" class="text-sm font-bold text-gray-800">
                                Homepage Title <span class="text-brand-600 font-normal text-xs">(Shown in browser tab & Google search)</span>
                            </label>
                            <span class="text-[11px] text-gray-400 font-mono" x-data="{ len: '{{ strlen($siteTitle ?? '') }}' }" x-text="len + '/70 chars recommended'"></span>
                        </div>
                        <input type="text" 
                               name="site_title" 
                               id="site_title" 
                               value="{{ old('site_title', $siteTitle) }}" 
                               placeholder="e.g. SAJ - Online Shopping in Bangladesh | Best Deals & Fast Delivery"
                               class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors text-sm">
                        <p class="text-[11px] text-gray-400 mt-1">Leave empty to use default: <code>{Site Name} - Online Shopping in Bangladesh</code></p>
                        @error('site_title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Meta Description -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label for="site_meta_description" class="text-sm font-bold text-gray-800">
                                SEO Meta Description <span class="text-brand-600 font-normal text-xs">(Google search summary)</span>
                            </label>
                            <span class="text-[11px] text-gray-400 font-mono" x-data="{ len: '{{ strlen($metaDescription ?? '') }}' }" x-text="len + '/160 chars recommended'"></span>
                        </div>
                        <textarea name="site_meta_description" 
                                  id="site_meta_description" 
                                  rows="3" 
                                  placeholder="e.g. Discover top quality trending jewelry, cosmetics & fashion products at best prices on SAJ. Fast delivery all across Bangladesh with Cash on Delivery."
                                  class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors text-sm">{{ old('site_meta_description', $metaDescription) }}</textarea>
                        @error('site_meta_description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Meta Keywords -->
                    <div>
                        <label for="site_meta_keywords" class="text-sm font-bold text-gray-800 block mb-1">
                            SEO Meta Keywords <span class="text-gray-400 font-normal text-xs">(Comma separated)</span>
                        </label>
                        <input type="text" 
                               name="site_meta_keywords" 
                               id="site_meta_keywords" 
                               value="{{ old('site_meta_keywords', $metaKeywords) }}" 
                               placeholder="e.g. saj, saj bd, jewelry bangladesh, cosmetics bd, online shopping bangladesh"
                               class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors text-sm">
                        @error('site_meta_keywords') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Open Graph (OG) Image for Facebook / WhatsApp / Social Share -->
                    <div class="pt-2" x-data="{ ogPreview: '{{ $ogImage ?? '' }}' }">
                        <label class="text-sm font-bold text-gray-800 block mb-1">
                            Social Share Preview Image (OG Image)
                        </label>
                        <p class="text-xs text-gray-500 mb-3">This banner image is automatically previewed when someone shares your homepage link on Facebook, WhatsApp, Messenger, or Twitter. Recommended size: 1200x630px.</p>

                        <div class="relative max-w-lg">
                            <input type="file" id="site_og_image" name="site_og_image" accept="image/*" class="hidden" @change="ogPreview = URL.createObjectURL($event.target.files[0])">
                            
                            <label for="site_og_image" class="cursor-pointer block relative rounded-2xl border-2 border-dashed border-gray-200 hover:border-brand-400 transition-colors duration-300 overflow-hidden bg-gray-50 w-full h-44 group">
                                <!-- Image Preview -->
                                <div x-show="ogPreview" class="absolute inset-0 flex items-center justify-center p-2 bg-gray-100/50">
                                    <img :src="ogPreview" class="max-w-full max-h-full object-cover rounded-xl drop-shadow-sm" alt="OG Image preview">
                                </div>

                                <!-- Empty State -->
                                <div x-show="!ogPreview" class="absolute inset-0 flex flex-col items-center justify-center text-gray-400 group-hover:text-brand-500 transition-colors p-4 text-center">
                                    <i class="ph ph-share-network text-4xl mb-1"></i>
                                    <span class="text-sm font-semibold">Upload Social Banner (1200x630px)</span>
                                    <span class="text-xs text-gray-400 mt-1">PNG, JPG, or WEBP (Max 3MB)</span>
                                </div>

                                <!-- Hover Overlay -->
                                <div x-show="ogPreview" class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center text-white backdrop-blur-[2px]">
                                    <div class="flex flex-col items-center gap-1">
                                        <i class="ph ph-swap text-2xl"></i>
                                        <span class="text-sm font-semibold">Change Social Banner</span>
                                    </div>
                                </div>
                            </label>
                            @error('site_og_image') <p class="text-red-500 text-xs mt-2">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Action -->
            <div class="pt-6 mt-8 border-t border-gray-100 flex justify-end">
                <button type="submit" 
                        :disabled="isSubmitting"
                        :class="{'opacity-75 cursor-not-allowed': isSubmitting, 'cursor-pointer hover:-translate-y-0.5 hover:bg-brand-700 hover:shadow-lg hover:shadow-brand-500/30': !isSubmitting}"
                        class="inline-flex items-center gap-2 bg-brand-600 text-white px-8 py-3.5 rounded-xl font-bold transition-all duration-300 shadow-md transform">
                    <i class="ph ph-floppy-disk text-xl" x-show="!isSubmitting"></i>
                    <i class="ph ph-spinner-gap animate-spin text-xl" x-show="isSubmitting" style="display: none;"></i>
                    <span x-text="isSubmitting ? 'Saving...' : 'Save Branding & SEO Settings'"></span>
                </button>
            </div>

        </form>
    </div>
</div>
@endsection
