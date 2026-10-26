@extends('layouts.admin')

@section('title', 'Site Information - Admin Panel')
@section('header_title', 'Site Information')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('admin.settings.index') }}" class="text-sm text-gray-500 hover:text-brand-600 flex items-center gap-1 mb-2 transition-colors">
                <i class="ph ph-arrow-left"></i> Back to Settings
            </a>
            <h2 class="text-2xl font-bold text-gray-800">Site Information</h2>
            <p class="text-sm text-gray-500 mt-1">Manage your business contact details and social media links for SEO and display.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 text-green-700 p-4 rounded-xl mb-6 flex items-center gap-3 border border-green-200" x-data="{ show: true }" x-show="show" x-transition>
            <i class="ph-fill ph-check-circle text-xl"></i>
            <span class="font-medium">{{ session('success') }}</span>
            <button @click="show = false" class="ml-auto text-green-500 hover:text-green-700">
                <i class="ph ph-x"></i>
            </button>
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <form action="{{ route('admin.settings.site-info.update') }}" method="POST">
            @csrf

            <!-- Primary Contact Section -->
            <div class="p-6 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="ph ph-address-book text-brand-600"></i> Primary Contact Details
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="site_name" class="block text-sm font-medium text-gray-700 mb-1">Site Name <span class="text-red-500">*</span></label>
                        <input type="text" name="site_name" id="site_name" value="{{ old('site_name', $siteName) }}" required
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors">
                        @error('site_name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="site_email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <input type="email" name="site_email" id="site_email" value="{{ old('site_email', $siteEmail) }}" placeholder="e.g. support@sajbd.com"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors">
                        @error('site_email') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="site_phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                        <input type="text" name="site_phone" id="site_phone" value="{{ old('site_phone', $sitePhone) }}" placeholder="e.g. +8801XXXXXXXXX"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors">
                        @error('site_phone') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="site_whatsapp" class="block text-sm font-medium text-gray-700 mb-1">WhatsApp Number</label>
                        <input type="text" name="site_whatsapp" id="site_whatsapp" value="{{ old('site_whatsapp', $siteWhatsapp) }}" placeholder="e.g. 8801XXXXXXXXX (Country code without +)"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors">
                        @error('site_whatsapp') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <!-- Social Media Section -->
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="ph ph-share-network text-brand-600"></i> Social Media Links
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="site_facebook" class="block text-sm font-medium text-gray-700 mb-1">Facebook Page URL</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="ph-fill ph-facebook-logo text-blue-600 text-lg"></i>
                            </div>
                            <input type="url" name="site_facebook" id="site_facebook" value="{{ old('site_facebook', $siteFacebook) }}" placeholder="https://facebook.com/yourpage"
                                class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors">
                        </div>
                        @error('site_facebook') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="site_instagram" class="block text-sm font-medium text-gray-700 mb-1">Instagram URL</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="ph-fill ph-instagram-logo text-pink-600 text-lg"></i>
                            </div>
                            <input type="url" name="site_instagram" id="site_instagram" value="{{ old('site_instagram', $siteInstagram) }}" placeholder="https://instagram.com/yourprofile"
                                class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors">
                        </div>
                        @error('site_instagram') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="site_youtube" class="block text-sm font-medium text-gray-700 mb-1">YouTube Channel URL</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="ph-fill ph-youtube-logo text-red-600 text-lg"></i>
                            </div>
                            <input type="url" name="site_youtube" id="site_youtube" value="{{ old('site_youtube', $siteYoutube) }}" placeholder="https://youtube.com/@yourchannel"
                                class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors">
                        </div>
                        @error('site_youtube') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="site_tiktok" class="block text-sm font-medium text-gray-700 mb-1">TikTok Profile URL</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="ph-fill ph-tiktok-logo text-gray-900 text-lg"></i>
                            </div>
                            <input type="url" name="site_tiktok" id="site_tiktok" value="{{ old('site_tiktok', $siteTiktok) }}" placeholder="https://tiktok.com/@yourprofile"
                                class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors">
                        </div>
                        @error('site_tiktok') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="site_linkedin" class="block text-sm font-medium text-gray-700 mb-1">LinkedIn Profile URL</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="ph-fill ph-linkedin-logo text-[#0A66C2] text-lg"></i>
                            </div>
                            <input type="url" name="site_linkedin" id="site_linkedin" value="{{ old('site_linkedin', $siteLinkedin) }}" placeholder="https://linkedin.com/company/yourprofile"
                                class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors">
                        </div>
                        @error('site_linkedin') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="p-6 bg-gray-50 border-t border-gray-100 flex justify-end">
                <button type="submit" class="px-6 py-2.5 bg-brand-600 text-white font-medium rounded-xl hover:bg-brand-700 focus:ring-4 focus:ring-brand-500/20 transition-all flex items-center gap-2">
                    <i class="ph ph-device-floppy"></i>
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
