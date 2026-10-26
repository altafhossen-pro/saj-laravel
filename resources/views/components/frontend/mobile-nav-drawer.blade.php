@php
    $siteName = \App\Models\Setting::get('site_name', 'SAJ');
    $siteLogo = \App\Models\Setting::get('site_logo');
    $sitePhone = \App\Models\Setting::get('site_phone');
    $siteEmail = \App\Models\Setting::get('site_email');
    $siteWhatsapp = \App\Models\Setting::get('site_whatsapp') ?: $sitePhone;
    
    $cleanWaPhone = preg_replace('/[^0-9]/', '', $siteWhatsapp ?: '');
    if (strlen($cleanWaPhone) === 11 && str_starts_with($cleanWaPhone, '01')) {
        $cleanWaPhone = '88' . $cleanWaPhone;
    }
    $waText = urlencode("Hello {$siteName}, I need support regarding orders/products.");
    $whatsappUrl = $cleanWaPhone ? "https://wa.me/{$cleanWaPhone}?text={$waText}" : null;

    $socials = [
        ['name' => 'Facebook', 'url' => \App\Models\Setting::get('site_facebook'), 'icon' => 'ph-fill ph-facebook-logo', 'bg' => 'bg-[#1877F2] text-white hover:bg-[#166fe5]'],
        ['name' => 'YouTube', 'url' => \App\Models\Setting::get('site_youtube'), 'icon' => 'ph-fill ph-youtube-logo', 'bg' => 'bg-[#FF0000] text-white hover:bg-[#e60000]'],
        ['name' => 'TikTok', 'url' => \App\Models\Setting::get('site_tiktok'), 'icon' => 'ph-fill ph-tiktok-logo', 'bg' => 'bg-[#000000] text-white hover:bg-[#1f1f1f]'],
        ['name' => 'Instagram', 'url' => \App\Models\Setting::get('site_instagram'), 'icon' => 'ph-fill ph-instagram-logo', 'bg' => 'bg-gradient-to-tr from-[#f09433] via-[#dc2743] to-[#bc1888] text-white'],
    ];
@endphp

<!-- Mobile Nav Drawer Overlay -->
<div x-show="mobileNavOpen" style="display: none;" 
     class="fixed inset-0 z-[80] bg-black/50 backdrop-blur-xs lg:hidden"
     @click="mobileNavOpen = false" 
     x-transition:enter="transition-opacity ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"></div>

<!-- Mobile Navigation Drawer -->
<aside :class="mobileNavOpen ? 'translate-x-0' : '-translate-x-full'"
       class="fixed inset-y-0 left-0 z-[90] w-[86vw] max-w-xs bg-white shadow-2xl transition-transform duration-300 ease-in-out -translate-x-full lg:hidden flex flex-col h-full border-r border-gray-100"
       style="display: flex;">

    <!-- Top Header -->
    <div class="h-16 relative flex items-center justify-center px-5 border-b border-gray-100 shrink-0">
        <a href="{{ route('home') }}" @click="mobileNavOpen = false" class="flex items-center select-none">
            @if($siteLogo)
                <img src="{{ $siteLogo }}" alt="{{ $siteName }}" class="h-9 w-auto object-contain">
            @else
                <span class="text-2xl font-black uppercase tracking-[0.22em] text-pink-500 inline-flex items-baseline" style="font-family: 'Playfair Display', serif;">{{ $siteName }}<span class="text-pink-600 font-black">.</span></span>
            @endif
        </a>
        <button @click="mobileNavOpen = false" 
                class="absolute right-4 w-9 h-9 rounded-full flex items-center justify-center text-gray-500 hover:text-gray-800 hover:bg-gray-100 transition-colors focus:outline-none"
                aria-label="Close Menu">
            <i class="ph ph-x text-xl font-bold"></i>
        </button>
    </div>

    <!-- Scrollable Content -->
    <div class="flex-1 overflow-y-auto custom-scrollbar divide-y divide-gray-100">
        
        <!-- User Greeting Card -->
        <div class="p-4 bg-gradient-to-br from-brand-50/70 via-white to-gray-50">
            @auth
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center overflow-hidden font-bold text-lg shrink-0 border border-brand-200 shadow-xs">
                        @if(auth()->user()->avatar)
                            <img src="{{ auth()->user()->avatar }}" alt="{{ auth()->user()->name }}" class="w-full h-full object-cover">
                        @else
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-xs font-semibold text-brand-600 uppercase tracking-wide">Welcome Back</div>
                        <div class="font-bold text-gray-900 truncate text-sm">{{ auth()->user()->name }}</div>
                        <div class="text-xs text-gray-500 truncate">{{ auth()->user()->phone ?? auth()->user()->email }}</div>
                    </div>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-2">
                    <a href="{{ route('dashboard') }}" @click="mobileNavOpen = false"
                       class="flex items-center justify-center gap-1.5 py-1.5 px-3 bg-white hover:bg-brand-50 text-brand-700 border border-brand-200 rounded-lg text-xs font-medium transition-colors shadow-xs">
                        <i class="ph ph-user"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="{{ route('dashboard.orders') }}" @click="mobileNavOpen = false"
                       class="flex items-center justify-center gap-1.5 py-1.5 px-3 bg-white hover:bg-brand-50 text-brand-700 border border-brand-200 rounded-lg text-xs font-medium transition-colors shadow-xs">
                        <i class="ph ph-package"></i>
                        <span>My Orders</span>
                    </a>
                </div>
            @else
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center shrink-0 text-xl">
                        <i class="ph-fill ph-user-circle"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="font-bold text-gray-900 text-sm">Welcome Guest 👋</div>
                        <div class="text-xs text-gray-500">Log in to track orders & manage profile</div>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="{{ route('login') }}" @click="mobileNavOpen = false"
                       class="flex items-center justify-center gap-2 w-full py-2 px-4 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-semibold shadow-xs transition-colors">
                        <i class="ph-bold ph-sign-in"></i>
                        <span>Login / Sign Up</span>
                    </a>
                </div>
            @endauth
        </div>

        <!-- Order Tracking Special Highlight Card -->
        <div class="p-3">
            <a href="{{ route('page.tracking') }}" @click="mobileNavOpen = false"
               class="group flex items-center gap-3 p-3 bg-gradient-to-r from-amber-500/10 via-brand-500/10 to-indigo-500/10 hover:from-amber-500/20 hover:to-indigo-500/20 border border-brand-200/80 rounded-2xl transition-all duration-200">
                <div class="w-10 h-10 rounded-xl bg-brand-600 text-white flex items-center justify-center text-xl shrink-0 shadow-sm group-hover:scale-105 transition-transform">
                    <i class="ph-fill ph-truck"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-bold text-gray-900 group-hover:text-brand-600 transition-colors">Order Tracking</span>
                        <span class="px-1.5 py-0.5 text-[10px] font-semibold bg-brand-600 text-white rounded-full">Track</span>
                    </div>
                    <p class="text-xs text-gray-500 truncate">আপনার অর্ডারের বর্তমান অবস্থা দেখুন</p>
                </div>
                <i class="ph-bold ph-caret-right text-gray-400 group-hover:text-brand-600 transition-colors"></i>
            </a>
        </div>

        <!-- Quick Navigation Links -->
        <div class="py-2">
            <div class="px-4 py-1.5 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Quick Links</div>
            <nav class="space-y-0.5 px-2">
                <a href="{{ route('home') }}" @click="mobileNavOpen = false"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-brand-600 transition-colors">
                    <div class="w-8 h-8 rounded-lg bg-gray-100 text-gray-600 flex items-center justify-center shrink-0 text-base">
                        <i class="ph-fill ph-house"></i>
                    </div>
                    <span>Home</span>
                </a>

                <a href="{{ route('shop.search') }}" @click="mobileNavOpen = false"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-brand-600 transition-colors">
                    <div class="w-8 h-8 rounded-lg bg-gray-100 text-gray-600 flex items-center justify-center shrink-0 text-base">
                        <i class="ph-fill ph-shopping-bag-open"></i>
                    </div>
                    <span>Shop All Products</span>
                </a>

                <a href="{{ route('page.about') }}" @click="mobileNavOpen = false"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-brand-600 transition-colors">
                    <div class="w-8 h-8 rounded-lg bg-gray-100 text-gray-600 flex items-center justify-center shrink-0 text-base">
                        <i class="ph-fill ph-info"></i>
                    </div>
                    <span>About Us</span>
                </a>

                <a href="{{ route('page.contact') }}" @click="mobileNavOpen = false"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-brand-600 transition-colors">
                    <div class="w-8 h-8 rounded-lg bg-gray-100 text-gray-600 flex items-center justify-center shrink-0 text-base">
                        <i class="ph-fill ph-phone-call"></i>
                    </div>
                    <span>Contact Us</span>
                </a>
            </nav>
        </div>

        <!-- Help & Policies Links -->
        <div class="py-2">
            <div class="px-4 py-1.5 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Help & Policies</div>
            <nav class="space-y-0.5 px-2">
                <a href="{{ route('page.return-refund') }}" @click="mobileNavOpen = false"
                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-brand-600 transition-colors">
                    <i class="ph ph-arrow-counter-clockwise text-gray-400 text-base"></i>
                    <span>Return & Refund Policy</span>
                </a>

                <a href="{{ route('page.privacy') }}" @click="mobileNavOpen = false"
                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-brand-600 transition-colors">
                    <i class="ph ph-shield-check text-gray-400 text-base"></i>
                    <span>Privacy Policy</span>
                </a>

                <a href="{{ route('page.terms') }}" @click="mobileNavOpen = false"
                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-brand-600 transition-colors">
                    <i class="ph ph-file-text text-gray-400 text-base"></i>
                    <span>Terms & Conditions</span>
                </a>
            </nav>
        </div>

        <!-- Direct Customer Support Card -->
        <div class="p-4 space-y-2.5 bg-gray-50/70">
            <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Customer Support</div>

            @if($whatsappUrl)
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer"
                   class="flex items-center justify-center gap-2 w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-all duration-200 shadow-xs">
                    <i class="ph-fill ph-whatsapp-logo text-lg"></i>
                    <span>WhatsApp Support</span>
                </a>
            @endif

            @if($sitePhone)
                <a href="tel:{{ $sitePhone }}"
                   class="flex items-center justify-center gap-2 w-full py-2.5 px-4 bg-white hover:bg-gray-100 text-gray-800 border border-gray-200 rounded-xl text-xs font-semibold transition-all duration-200 shadow-xs">
                    <i class="ph-fill ph-phone-call text-brand-600 text-base"></i>
                    <span>Call: {{ $sitePhone }}</span>
                </a>
            @endif

            @if($siteEmail)
                <a href="mailto:{{ $siteEmail }}"
                   class="flex items-center justify-center gap-2 w-full py-2 px-3 text-gray-500 hover:text-gray-800 text-xs transition-colors">
                    <i class="ph-fill ph-envelope-simple text-sm"></i>
                    <span class="truncate">{{ $siteEmail }}</span>
                </a>
            @endif
        </div>

    </div>

    <!-- Drawer Footer -->
    <div class="p-4 border-t border-gray-100 shrink-0 bg-white">
        <!-- Social Icons -->
        <div class="flex items-center justify-center gap-2 mb-2">
            @foreach($socials as $social)
                @if(!empty($social['url']))
                    <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer" title="{{ $social['name'] }}"
                       class="w-7 h-7 rounded-full {{ $social['bg'] }} flex items-center justify-center transition-all duration-200 shadow-xs hover:scale-110">
                        <i class="{{ $social['icon'] }} text-xs"></i>
                    </a>
                @endif
            @endforeach
        </div>
        <p class="text-[11px] text-gray-400 text-center">
            &copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.
        </p>
    </div>

</aside>
