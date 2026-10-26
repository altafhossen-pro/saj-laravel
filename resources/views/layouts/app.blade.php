<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) == 'en' ? 'en-BD' : str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $globalSiteName = \App\Models\Setting::get('site_name', 'SAJ');
        $globalDefaultTitle = \App\Models\Setting::get('site_title') ?: "{$globalSiteName} - Online Shopping in Bangladesh";
        $siteFavicon = \App\Models\Setting::get('site_favicon') ?: asset('favicon.ico');
    @endphp
    <title>@yield('title', $globalDefaultTitle)</title>
    <link rel="icon" type="image/png" href="{{ $siteFavicon }}">
    <link rel="shortcut icon" href="{{ $siteFavicon }}">
    <link rel="apple-touch-icon" href="{{ $siteFavicon }}">
    @yield('meta')

    <!-- Google Fonts: Luxury Fonts (Cinzel, Playfair Display, Cormorant Garamond) & Hind Siliguri / Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800;900&family=Cormorant+Garamond:wght@600;700&family=Playfair+Display:ital,wght@0,600;0,700;0,800;0,900;1,700&family=Hind+Siliguri:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Vite/Tailwind -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Optional: Add Phosphor Icons or FontAwesome for icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    @stack('styles')

    <!-- JSON-LD Structured Data -->
    @php
        $schemaSiteName = \App\Models\Setting::get('site_name', 'SAJ');
        $schemaSiteLogo = \App\Models\Setting::get('site_logo') ? url(\App\Models\Setting::get('site_logo')) : '';
        $schemaSitePhone = \App\Models\Setting::get('site_phone');
        $schemaSiteEmail = \App\Models\Setting::get('site_email');

        $socialLinks = [];
        if ($fb = \App\Models\Setting::get('site_facebook'))
            $socialLinks[] = $fb;
        if ($yt = \App\Models\Setting::get('site_youtube'))
            $socialLinks[] = $yt;
        if ($tt = \App\Models\Setting::get('site_tiktok'))
            $socialLinks[] = $tt;
        if ($li = \App\Models\Setting::get('site_linkedin'))
            $socialLinks[] = $li;
        if ($ig = \App\Models\Setting::get('site_instagram'))
            $socialLinks[] = $ig;
    @endphp
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "OnlineStore",
        "name": "{!! addslashes($schemaSiteName) !!}",
        "url": "{{ url('/') }}",
        @if(!empty($socialLinks))
            "sameAs": {!! json_encode($socialLinks) !!},
        @endif
        @if($schemaSiteLogo)
            "logo": "{{ $schemaSiteLogo }}",
        @endif
        @if($schemaSitePhone || $schemaSiteEmail)
            "contactPoint": {
                "@@type": "ContactPoint",
                @if($schemaSiteEmail) "email": "{{ $schemaSiteEmail }}", @endif
                @if($schemaSitePhone) "telephone": "{{ $schemaSitePhone }}", @endif
                "contactType": "Customer Service"
            },
        @endif
        "hasShippingService": [
            {
                "@@type": "ShippingService",
                "name": "Standard Delivery",
                "shippingConditions": [
                    {
                        "@@type": "ShippingConditions",
                        "shippingDestination": {
                            "@@type": "DefinedRegion",
                            "addressCountry": "BD"
                        },
                        "shippingRate": {
                            "@@type": "MonetaryAmount",
                            "value": "{{ \App\Models\Setting::get('delivery_charge_inside_dhaka', 80) }}",
                            "currency": "BDT"
                        }
                    }
                ]
            }
        ]
    }
    </script>
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "WebSite",
        "url": "{{ url('/') }}",
        "potentialAction": {
            "@@type": "SearchAction",
            "target": "{{ url('/search') }}?q={search_term_string}",
            "query-input": "required name=search_term_string"
        }
    }
    </script>

    <!-- Dynamic Head Scripts -->
    {!! \App\Models\Setting::get('site_head_scripts') !!}

    @if((bool) \App\Models\Setting::get('tawkto_enabled', false) && \App\Models\Setting::get('tawkto_visibility_mode', 'hidden') === 'hidden')
        <style id="tawk-anti-fouc">
            /* 100% Zero-Flash Shield: Elements never render even for 1 millisecond */
            #tawk-default-container,
            #tawk-bubble-container,
            .tawk-min-container,
            .tawk-button,
            .tawk-customizer,
            iframe[src*="tawk"],
            iframe[title*="chat" i],
            iframe[title*="tawk" i],
            iframe[id*="tawk" i],
            iframe[class*="tawk" i],
            div[id^="tawk"],
            div[class^="tawk"] {
                display: none !important;
                visibility: hidden !important;
                opacity: 0 !important;
                width: 0 !important;
                height: 0 !important;
                max-width: 0 !important;
                max-height: 0 !important;
                pointer-events: none !important;
                position: fixed !important;
                left: -99999px !important;
                top: -99999px !important;
                z-index: -9999 !important;
                transform: scale(0) !important;
            }
        </style>
    @endif

    <x-frontend.meta-pixel />
</head>

<body class="antialiased bg-gray-50 text-gray-800 font-sans" x-data="{ sidebarOpen: false, mobileNavOpen: false }">

    <div class="flex h-screen overflow-hidden">

        {{-- Sidebar commented out as requested --}}
        {{--
        <!-- Mobile Sidebar Overlay -->
        <div x-show="sidebarOpen" style="display: none;" class="fixed inset-0 z-[60] bg-black/50 lg:hidden"
            @click="sidebarOpen = false" x-transition.opacity></div>

        <!-- Sidebar (Left) -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-[70] w-64 bg-white border-r border-gray-100 shadow-sm transition-transform duration-300 -translate-x-full lg:translate-x-0 lg:static lg:flex-shrink-0 flex flex-col h-full">

            <!-- Logo (Centered Capitalized Luxury Wordmark) -->
            <div class="h-16 relative flex items-center justify-center px-4 border-b border-gray-100 shrink-0">
                <a href="/" class="flex items-center justify-center group select-none">
                    <span class="text-2xl lg:text-[28px] font-black uppercase tracking-[0.22em] text-pink-500 group-hover:text-pink-600 transition-colors inline-flex items-baseline" style="font-family: 'Playfair Display', serif;">SAJ<span class="text-pink-600 font-black">.</span></span>
                </a>
                <button @click="sidebarOpen = false" class="lg:hidden absolute right-3 text-gray-400 hover:text-gray-700 p-1.5 rounded-lg hover:bg-gray-100 transition-colors" aria-label="Close Categories">
                    <i class="ph ph-x text-xl"></i>
                </button>
            </div>

            <!-- Categories Scrollable -->
            <div class="flex-1 overflow-y-auto py-4 ps-1 pe-3 custom-scrollbar">
                <h3 class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Categories</h3>
                <nav class="space-y-1">
                    <x-frontend.sidebar-categories :categories="$sidebarCategories" />
                </nav>
            </div>

            <!-- Help/Support -->
            @php
                $rawWaPhone = \App\Models\Setting::get('site_whatsapp') ?: \App\Models\Setting::get('site_phone', '8801840209060');
                $cleanWaPhone = preg_replace('/[^0-9]/', '', $rawWaPhone);
                if (strlen($cleanWaPhone) === 11 && str_starts_with($cleanWaPhone, '01')) {
                    $cleanWaPhone = '88' . $cleanWaPhone;
                }
                $siteBrandName = \App\Models\Setting::get('site_name', 'SAJ');
                $waText = urlencode("Hello {$siteBrandName}, I need support regarding products/orders.");
                $whatsappUrl = "https://wa.me/{$cleanWaPhone}?text={$waText}";
            @endphp
            <div class="p-4 border-t border-gray-100 shrink-0">
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer"
                    class="flex items-center justify-center gap-2 w-full py-2.5 px-4 bg-emerald-50 hover:bg-emerald-100/80 text-emerald-800 rounded-xl text-sm font-semibold transition-all duration-200 border border-emerald-200/60 shadow-xs hover:shadow-sm">
                    <i class="ph-fill ph-whatsapp-logo text-emerald-600 text-lg"></i>
                    <span>Support</span>
                </a>
            </div>
        </aside>
        --}}

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col min-w-0 bg-gray-50 h-full overflow-hidden">

            @if(session()->has('impersonator_admin_id'))
                <div
                    class="bg-amber-600 text-white text-xs font-semibold px-4 py-2 flex items-center justify-between shrink-0 shadow-sm z-50">
                    <div class="flex items-center gap-2">
                        <i class="ph-bold ph-shield-warning text-base"></i>
                        <span>Impersonation Mode: You are viewing as customer
                            <strong>{{ Auth::user()->name }}</strong></span>
                    </div>
                    <a href="{{ route('impersonate.stop') }}"
                        class="inline-flex items-center gap-1 bg-white text-amber-900 px-3 py-1 rounded-lg text-xs font-bold hover:bg-amber-50 transition-colors shadow-sm">
                        <i class="ph-bold ph-arrow-u-up-left"></i>
                        <span>Return to Admin</span>
                    </a>
                </div>
            @endif

            <!-- Top Header -->
            <!-- Top Header -->
            <header x-data="{ mobileSearchOpen: false }" class="bg-white shadow-xs shrink-0 relative z-[70] overflow-visible border-b border-gray-100">
                <div class="h-16 lg:h-[72px] px-4 lg:px-8">
                    <div class="max-w-screen-2xl mx-auto h-full flex items-center justify-between gap-4 lg:gap-8">

                        <!-- Left: Branding & Mobile Hamburger -->
                        <div class="flex items-center gap-3 shrink-0">
                            <!-- Hamburger Menu Mobile (Opens Main Nav Drawer) -->
                            <button @click="mobileNavOpen = true"
                                class="lg:hidden text-gray-600 hover:text-pink-600 focus:outline-none p-2 -ml-2 rounded-lg hover:bg-gray-100 transition-colors shrink-0"
                                aria-label="Open Navigation Menu">
                                <i class="ph ph-list text-2xl"></i>
                            </button>

                            <!-- Branding (Desktop & Tablet) -->
                            <a href="/" class="flex items-center shrink-0 group select-none pr-2 lg:pr-4">
                                @php
                                    $headerLogo = \App\Models\Setting::get('site_logo');
                                    $headerSiteName = \App\Models\Setting::get('site_name', 'SAJ');
                                @endphp
                                @if($headerLogo)
                                    <img src="{{ $headerLogo }}" alt="{{ $headerSiteName }}" class="h-9 lg:h-11 w-auto object-contain group-hover:opacity-90 transition-opacity">
                                @else
                                    <span class="text-2xl lg:text-[30px] font-black uppercase tracking-[0.22em] text-pink-500 group-hover:text-pink-600 transition-colors inline-flex items-baseline" style="font-family: 'Playfair Display', serif;">{{ $headerSiteName }}<span class="text-pink-600 font-black">.</span></span>
                                @endif
                            </a>
                        </div>

                        <!-- Center: Global Search (Desktop & Tablet) -->
                        <div class="hidden sm:block flex-1 max-w-2xl mx-auto">
                            <x-frontend.header-search class="w-full relative block" />
                        </div>

                        <!-- Right: Actions -->
                        <div class="flex items-center gap-2 sm:gap-4 shrink-0">
                            <!-- Track Order Link (Desktop) -->
                            <a href="{{ route('page.tracking') }}"
                                class="hidden lg:inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-gray-700 hover:text-pink-600 transition-colors px-2.5 py-1.5 rounded-lg hover:bg-gray-50 shrink-0">
                                <i class="ph-bold ph-truck text-base text-pink-500"></i>
                                <span>Track Order</span>
                            </a>

                            <div class="h-5 w-px bg-gray-200 hidden lg:block"></div>

                            <!-- Mobile Search Toggle -->
                            <button @click="mobileSearchOpen = !mobileSearchOpen"
                                class="sm:hidden text-gray-500 hover:text-brand-600 focus:outline-none p-2 rounded-lg hover:bg-gray-100 transition-colors">
                                <i class="ph ph-magnifying-glass text-2xl"></i>
                            </button>

                            <!-- Cart -->
                            <button @click="$dispatch('open-cart-drawer')"
                                class="relative flex items-center justify-center p-2 text-gray-700 hover:text-pink-600 transition-colors cursor-pointer rounded-lg hover:bg-gray-50">
                                <i class="ph ph-shopping-cart text-2xl"></i>
                                <span x-show="$store.cart.totalItems > 0" x-text="$store.cart.totalItems"
                                    class="absolute top-1 right-1 translate-x-1/4 -translate-y-1/4 min-w-[20px] h-[20px] bg-pink-500 text-white rounded-full text-[11px] font-bold flex items-center justify-center px-1 border-2 border-white shadow-sm leading-none"
                                    style="display: none;"></span>
                            </button>

                            <div class="h-6 w-px bg-gray-200 mx-1 hidden sm:block"></div>

                            <!-- User Menu -->
                            <div class="hidden sm:block">
                                <x-frontend.header-user-menu />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Category Navigation Bar (Desktop & Tablet) -->
                @if(isset($sidebarCategories) && $sidebarCategories->count() > 0)
                    <nav class="hidden md:block bg-gray-50/80 border-t border-gray-100 relative z-[70] overflow-visible"
                         x-data="{
                            hiddenIndices: [],
                            moreOpen: false,
                            itemWidths: [],
                            activeIndices: [
                                @foreach($sidebarCategories as $cat)
                                    @php
                                        $hasChildren = $cat->children && $cat->children->count() > 0;
                                        $childSlugs = $hasChildren ? $cat->children->pluck('slug')->map(fn($s) => 'shop/' . $s)->toArray() : [];
                                        $isCatActive = request()->is('shop/' . $cat->slug) || ($hasChildren && request()->is($childSlugs));
                                    @endphp
                                    @if($isCatActive) {{ $loop->index }}, @endif
                                @endforeach
                            ],
                            isMoreActive() {
                                return this.activeIndices.some(idx => this.hiddenIndices.includes(idx));
                            },
                            checkOverflow() {
                                const wrapper = this.$refs.navWrapper;
                                if (!wrapper) return;
                                const availableWidth = wrapper.clientWidth;
                                const items = this.$refs.catList ? Array.from(this.$refs.catList.children) : [];
                                if (items.length === 0) return;

                                if (this.itemWidths.length !== items.length) {
                                    this.itemWidths = items.map(el => {
                                        const clone = el.cloneNode(true);
                                        clone.style.visibility = 'hidden';
                                        clone.style.position = 'absolute';
                                        clone.style.display = 'inline-flex';
                                        clone.classList.remove('!hidden', 'hidden');
                                        document.body.appendChild(clone);
                                        const w = clone.getBoundingClientRect().width;
                                        document.body.removeChild(clone);
                                        return w;
                                    });
                                }

                                const moreBtnWidth = 90;
                                const gap = 8;
                                let totalW = 0;
                                let newHidden = [];

                                for (let i = 0; i < this.itemWidths.length; i++) {
                                    const w = this.itemWidths[i] + gap;
                                    const needed = (i < this.itemWidths.length - 1) ? (totalW + w + moreBtnWidth) : (totalW + w);
                                    if (needed > availableWidth) {
                                        for (let j = i; j < this.itemWidths.length; j++) {
                                            newHidden.push(j);
                                        }
                                        break;
                                    } else {
                                        totalW += w;
                                    }
                                }
                                this.hiddenIndices = newHidden;
                            }
                         }"
                         x-init="
                            $nextTick(() => {
                                checkOverflow();
                                if (window.ResizeObserver) {
                                    new ResizeObserver(() => checkOverflow()).observe($refs.navWrapper);
                                }
                            });
                         "
                         @resize.window.debounce.100ms="checkOverflow()">
                        <div class="max-w-screen-2xl mx-auto px-4 lg:px-8 flex items-center justify-between overflow-visible py-2.5 relative gap-4">
                            <!-- Left-aligned: Main Category List with Dynamic Overflow -->
                            <div x-ref="navWrapper" class="flex-1 min-w-0 flex items-center space-x-1 lg:space-x-2 overflow-visible flex-nowrap whitespace-nowrap">
                                <div x-ref="catList" class="flex items-center space-x-1 lg:space-x-2 overflow-visible flex-nowrap whitespace-nowrap">
                                    @foreach($sidebarCategories as $cat)
                                        @php
                                            $hasChildren = $cat->children && $cat->children->count() > 0;
                                            $childSlugs = $hasChildren ? $cat->children->pluck('slug')->map(fn($s) => 'shop/' . $s)->toArray() : [];
                                            $isCatActive = request()->is('shop/' . $cat->slug) || ($hasChildren && request()->is($childSlugs));
                                        @endphp
                                        @if($hasChildren)
                                            <div class="relative group shrink-0"
                                                 :class="hiddenIndices.includes({{ $loop->index }}) ? '!hidden' : ''">
                                                <a href="{{ route('shop.category', ['category_slug' => $cat->slug]) }}"
                                                   class="inline-flex items-center gap-1 px-2.5 lg:px-3 py-2 text-xs lg:text-[13px] font-semibold uppercase tracking-wider transition-colors border-b-2 {{ $isCatActive ? 'border-pink-500 text-pink-600 font-bold' : 'border-transparent text-gray-700 hover:text-pink-600 hover:border-pink-300' }}">
                                                    <span>{{ $cat->name }}</span>
                                                    <i class="ph ph-caret-down text-[10px] text-gray-400 group-hover:text-pink-500 transition-transform duration-200 group-hover:rotate-180"></i>
                                                </a>

                                                <!-- Luxury Single Dropdown (Smooth Tailwind Transition) -->
                                                <div class="absolute top-full left-0 pt-1.5 z-[9999] min-w-[220px] pointer-events-none opacity-0 invisible translate-y-2 group-hover:opacity-100 group-hover:visible group-hover:translate-y-0 group-hover:pointer-events-auto transition-all duration-200 ease-out">
                                                    <div class="bg-white rounded-xl shadow-2xl border border-gray-100 py-2 divide-y divide-gray-50 overflow-hidden ring-1 ring-black/10">
                                                        @foreach($cat->children as $sub)
                                                            @php
                                                                $isSubActive = request()->is('shop/' . $sub->slug);
                                                            @endphp
                                                            <a href="{{ route('shop.category', ['category_slug' => $sub->slug]) }}"
                                                               class="flex items-center justify-between px-4 py-2.5 text-xs font-medium transition-colors {{ $isSubActive ? 'bg-pink-50 text-pink-600 font-semibold' : 'text-gray-700 hover:bg-pink-50/70 hover:text-pink-600' }}">
                                                                <span>{{ $sub->name }}</span>
                                                                <i class="ph ph-caret-right text-[10px] text-gray-300"></i>
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <a href="{{ route('shop.category', ['category_slug' => $cat->slug]) }}"
                                               :class="hiddenIndices.includes({{ $loop->index }}) ? '!hidden' : ''"
                                               class="inline-flex items-center px-2.5 lg:px-3 py-2 text-xs lg:text-[13px] font-semibold uppercase tracking-wider transition-colors border-b-2 shrink-0 {{ $isCatActive ? 'border-pink-500 text-pink-600 font-bold' : 'border-transparent text-gray-700 hover:text-pink-600 hover:border-pink-300' }}">
                                                {{ $cat->name }}
                                            </a>
                                        @endif
                                    @endforeach
                                </div>

                                <!-- Dynamic 'More' Dropdown (ONLY visible when hiddenIndices.length > 0) -->
                                <div x-show="hiddenIndices.length > 0"
                                     class="relative group shrink-0"
                                     style="display: none;">
                                    <button type="button"
                                            class="inline-flex items-center gap-1.5 px-3 py-2 text-xs lg:text-[13px] font-bold uppercase tracking-wider transition-colors border-b-2 cursor-pointer"
                                            :class="isMoreActive() ? 'border-pink-500 text-pink-600' : 'border-transparent text-gray-700 hover:text-pink-600 hover:border-pink-300'">
                                        <span>More</span>
                                        <i class="ph ph-caret-down text-[10px] text-gray-400 group-hover:text-pink-500 transition-transform duration-200 group-hover:rotate-180"></i>
                                    </button>

                                    <!-- Multi-Column Mega Dropdown (Smooth Tailwind Transition) -->
                                    <div class="absolute top-full left-auto right-0 pt-1.5 z-[9999] pointer-events-none opacity-0 invisible translate-y-2 group-hover:opacity-100 group-hover:visible group-hover:translate-y-0 group-hover:pointer-events-auto transition-all duration-200 ease-out">
                                        <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 p-5 ring-1 ring-black/10 min-w-[320px] max-w-[90vw] max-h-[80vh] overflow-y-auto custom-scrollbar"
                                             :class="hiddenIndices.length > 2 ? 'sm:min-w-[560px] lg:min-w-[640px]' : (hiddenIndices.length === 2 ? 'sm:min-w-[420px]' : '')">
                                            <div class="grid gap-6"
                                                 :class="hiddenIndices.length > 2 ? 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3' : (hiddenIndices.length === 2 ? 'grid-cols-1 sm:grid-cols-2' : 'grid-cols-1')">
                                                @foreach($sidebarCategories as $cat)
                                                    @php
                                                        $hasChildren = $cat->children && $cat->children->count() > 0;
                                                        $isCatActive = request()->is('shop/' . $cat->slug);
                                                    @endphp
                                                    <div x-show="hiddenIndices.includes({{ $loop->index }})" class="space-y-2.5">
                                                        <!-- Category Header Link -->
                                                        <a href="{{ route('shop.category', ['category_slug' => $cat->slug]) }}"
                                                           class="flex items-center justify-between pb-1.5 border-b border-gray-100 group/cat">
                                                            <span class="text-xs font-bold uppercase tracking-wider transition-colors {{ $isCatActive ? 'text-pink-600' : 'text-gray-900 group-hover/cat:text-pink-600' }}">
                                                                {{ $cat->name }}
                                                            </span>
                                                            <i class="ph ph-arrow-up-right text-[11px] text-gray-400 group-hover/cat:text-pink-500 transition-transform group-hover/cat:translate-x-0.5"></i>
                                                        </a>

                                                        @if($hasChildren)
                                                            <ul class="space-y-1">
                                                                @foreach($cat->children as $sub)
                                                                    @php
                                                                        $subActive = request()->is('shop/' . $sub->slug);
                                                                    @endphp
                                                                    <li>
                                                                        <a href="{{ route('shop.category', ['category_slug' => $sub->slug]) }}"
                                                                           class="flex items-center gap-1.5 text-[12px] py-1 px-1.5 rounded-md transition-colors {{ $subActive ? 'text-pink-600 font-semibold bg-pink-50' : 'text-gray-600 hover:text-pink-600 hover:bg-gray-50' }}">
                                                                            <i class="ph ph-caret-right text-[9px] text-gray-300"></i>
                                                                            <span>{{ $sub->name }}</span>
                                                                        </a>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Contact Us Quick Link -->
                            <div class="hidden xl:flex items-center gap-4 text-xs font-semibold uppercase tracking-wider text-gray-600 shrink-0 pl-4">
                                <a href="{{ route('page.contact') }}" class="hover:text-pink-600 flex items-center gap-1.5 transition-colors">
                                    <i class="ph-bold ph-chat-circle-dots text-base text-pink-500"></i>
                                    <span>Contact Us</span>
                                </a>
                            </div>
                        </div>
                    </nav>
                @endif

                <!-- Mobile Search Expanded -->
                <div x-show="mobileSearchOpen" x-transition.opacity
                    class="sm:hidden border-t border-gray-100 p-3 bg-white" style="display: none;">
                    <x-frontend.header-search class="w-full relative block" />
                </div>
            </header>

            <!-- Scrollable Content -->
            <main class="flex-1 flex flex-col overflow-y-auto overflow-x-hidden relative pb-16 sm:pb-0">
                <div class="flex-1 shrink-0 max-w-screen-2xl mx-auto w-full">
                    @yield('content')
                </div>

                @if(!isset($hideFooter) || !$hideFooter)
                    <x-frontend.footer />
                @endif
            </main>

        </div>
    </div>

    <!-- Toast Notification System -->
    <div x-data="{ 
            toasts: [],
            add(event) {
                const id = Date.now() + Math.random();
                this.toasts.push({ id, message: event.detail.message, type: event.detail.type || 'success' });
                setTimeout(() => {
                    this.toasts = this.toasts.filter(t => t.id !== id);
                }, 3000);
            }
        }" @notify.window="add($event)"
        class="fixed top-4 left-1/2 -translate-x-1/2 z-[9999] flex flex-col gap-2 pointer-events-none items-center">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 -translate-y-10" x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-300"
                x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-10"
                class="px-4 py-3 rounded-lg shadow-lg border flex items-center gap-3 min-w-[280px] pointer-events-auto"
                :class="{
                      'bg-red-50 border-red-200 text-red-800': toast.type === 'error',
                      'bg-green-50 border-green-200 text-green-800': toast.type === 'success',
                      'bg-blue-50 border-blue-200 text-blue-800': toast.type === 'info',
                      'bg-yellow-50 border-yellow-200 text-yellow-800': toast.type === 'warning',
                  }">
                <i class="text-xl shrink-0" :class="{
                      'ph-fill ph-warning-circle': toast.type === 'error',
                      'ph-fill ph-check-circle': toast.type === 'success',
                      'ph-fill ph-info': toast.type === 'info',
                      'ph-fill ph-warning': toast.type === 'warning',
                 }"></i>
                <p class="text-sm font-semibold" x-text="toast.message"></p>
            </div>
        </template>
    </div>

    <!-- Custom Scrollbar Styles -->
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
            height: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #e5e7eb;
            border-radius: 4px;
        }

        .custom-scrollbar:hover::-webkit-scrollbar-thumb {
            background: #d1d5db;
        }
    </style>

    <x-frontend.mobile-nav-drawer />

    <x-frontend.mobile-bottom-nav />

    <x-cart-drawer />

    <x-frontend.category-drawer />

    <x-frontend.floating-whatsapp />

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('cart', {
                items: JSON.parse(localStorage.getItem('cart') || '[]'),
                syncTimer: null,

                syncServer(immediate = false) {
                    if (this.syncTimer) {
                        clearTimeout(this.syncTimer);
                        this.syncTimer = null;
                    }

                    const doSync = () => {
                        try {
                            const vid = localStorage.getItem('fb_vid') || '';
                            if (!vid) return;

                            const payload = JSON.stringify({
                                visitor_token: vid,
                                items: this.items
                            });

                            const url = '{{ route('api.cart.sync') }}';
                            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

                            if (navigator.sendBeacon && immediate) {
                                const blob = new Blob([payload], { type: 'application/json' });
                                navigator.sendBeacon(url, blob);
                            } else {
                                fetch(url, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': csrfToken,
                                        'Accept': 'application/json'
                                    },
                                    body: payload
                                }).catch(() => {});
                            }
                        } catch (e) {}
                    };

                    if (immediate) {
                        doSync();
                    } else {
                        this.syncTimer = setTimeout(doSync, 1200); // 1.2s debounce
                    }
                },

                save() {
                    localStorage.setItem('cart', JSON.stringify(this.items));
                    this.syncServer();
                },

                add(product) {
                    const existingIndex = this.items.findIndex(i => i.product_id === product.product_id && i.variant_id === product.variant_id);
                    if (existingIndex !== -1) {
                        const existing = this.items[existingIndex];
                        if (existing.quantity + product.quantity > product.max_stock) {
                            window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Cannot add more than available stock.', type: 'error' } }));
                            return;
                        }
                        this.items[existingIndex].quantity += product.quantity;
                    } else {
                        if (product.quantity > product.max_stock) {
                            window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Cannot add more than available stock.', type: 'error' } }));
                            return;
                        }
                        this.items.push(product);
                    }
                    this.save();

                    // Global Meta Pixel AddToCart Event
                    if (typeof window.trackFbEvent === 'function') {
                        window.trackFbEvent('AddToCart', {
                            content_name: product.name || product.title || 'Product',
                            content_ids: [String(product.product_id || product.id || 0)],
                            content_type: 'product',
                            value: Number(product.price || 0) * (parseInt(product.quantity) || 1),
                            currency: 'BDT'
                        });
                    }

                    window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Added to cart successfully!', type: 'success' } }));
                    window.dispatchEvent(new CustomEvent('open-cart-drawer'));
                },

                remove(index) {
                    this.items.splice(index, 1);
                    this.save();
                },

                updateQuantity(index, newQty) {
                    if (newQty < 1) newQty = 1;
                    if (newQty > this.items[index].max_stock) {
                        window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Cannot exceed available stock.', type: 'error' } }));
                        newQty = this.items[index].max_stock;
                    }
                    this.items[index].quantity = newQty;
                    this.save();
                },

                get totalAmount() {
                    return this.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                },

                get totalItems() {
                    return this.items.reduce((sum, item) => sum + item.quantity, 0);
                }
            });

            // Initial background sync if browser already has items in localStorage
            setTimeout(() => {
                if (Alpine.store('cart')?.items?.length > 0) {
                    Alpine.store('cart').syncServer(false);
                }
            }, 2000);

            // Flush changes immediately when user closes/leaves window
            window.addEventListener('pagehide', () => {
                if (Alpine.store('cart')?.syncTimer) {
                    Alpine.store('cart').syncServer(true);
                }
            });
        });
    </script>

    @if(session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                setTimeout(() => {
                    window.dispatchEvent(new CustomEvent('notify', { detail: { message: '{{ session('success') }}', type: 'success' } }));
                }, 100);
            });
        </script>
    @endif

    @if(session('error'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                setTimeout(() => {
                    window.dispatchEvent(new CustomEvent('notify', { detail: { message: '{{ session('error') }}', type: 'error' } }));
                }, 100);
            });
        </script>
    @endif

    @stack('scripts')

    <!-- Ultra-Light Realtime Visitor Heartbeat Tracker (Zero Server Load & Persistent Shopper Identity) -->
    <script>
        (function () {
            try {
                // Persistent Visitor ID across page switches in localStorage
                var VID_KEY = 'fb_vid';
                var PROFILE_KEY = 'fb_v_profile';
                var HISTORY_KEY = 'fb_v_history';
                var _d = new Date();
                var TODAY_KEY = 'fb_v_day_' + _d.getFullYear() + '-' + String(_d.getMonth() + 1).padStart(2, '0') + '-' + String(_d.getDate()).padStart(2, '0');
                
                var vid = localStorage.getItem(VID_KEY);
                if (!vid) {
                    vid = 'v_' + Math.random().toString(36).substring(2, 12) + Date.now().toString(36);
                    localStorage.setItem(VID_KEY, vid);
                }

                // Sync current Auth state with persistent profile in localStorage
                @auth
                    try {
                        var authProfile = {
                            user_id: {{ Auth::id() }},
                            name: {!! json_encode(Auth::user()->name) !!},
                            phone: {!! json_encode(Auth::user()->phone ?? '') !!},
                            email: {!! json_encode(Auth::user()->email ?? '') !!},
                            role: {!! json_encode(Auth::user()->role ?? 'customer') !!},
                            is_logged_in: true,
                            last_seen_as: {!! json_encode(Auth::user()->name) !!}
                        };
                        localStorage.setItem(PROFILE_KEY, JSON.stringify(authProfile));
                    } catch (e) {}
                @else
                    try {
                        var existingProfile = localStorage.getItem(PROFILE_KEY);
                        if (existingProfile) {
                            var p = JSON.parse(existingProfile);
                            if (p && typeof p === 'object') {
                                p.is_logged_in = false; // Mark currently logged out, but preserve previous name & phone
                                localStorage.setItem(PROFILE_KEY, JSON.stringify(p));
                            }
                        }
                    } catch (e) {}
                @endauth

                // Maintain persistent multi-page journey timeline in localStorage
                var visitorHistory = [];
                try {
                    visitorHistory = JSON.parse(localStorage.getItem(HISTORY_KEY) || '[]');
                    if (!Array.isArray(visitorHistory)) visitorHistory = [];
                } catch (e) { visitorHistory = []; }

                var currentUrl = window.location.pathname + window.location.search;
                var currentTitle = document.title || 'Storefront';
                var nowSec = Math.floor(Date.now() / 1000);
                var formattedTime = (new Date()).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });

                var lastStep = visitorHistory.length > 0 ? visitorHistory[visitorHistory.length - 1] : null;
                if (!lastStep || lastStep.url !== currentUrl) {
                    visitorHistory.push({
                        url: currentUrl,
                        title: currentTitle,
                        time: formattedTime,
                        timestamp: nowSec
                    });
                    // Keep max 25 recent steps to keep memory usage super light (< 2KB)
                    if (visitorHistory.length > 25) {
                        visitorHistory = visitorHistory.slice(-25);
                    }
                    try { localStorage.setItem(HISTORY_KEY, JSON.stringify(visitorHistory)); } catch (e) {}
                } else if (lastStep && (!lastStep.title || lastStep.title === 'Storefront') && currentTitle !== 'Storefront') {
                    lastStep.title = currentTitle;
                    try { localStorage.setItem(HISTORY_KEY, JSON.stringify(visitorHistory)); } catch (e) {}
                }

                // First visit of the day check
                var isNewDaily = !localStorage.getItem(TODAY_KEY);
                if (isNewDaily) {
                    localStorage.setItem(TODAY_KEY, '1');
                }

                // Helper to get current profile
                function getProfile() {
                    try {
                        var raw = localStorage.getItem(PROFILE_KEY);
                        return raw ? JSON.parse(raw) : null;
                    } catch (e) {
                        return null;
                    }
                }

                // Detect device type
                var deviceType = /Mobi|Android|iPhone|iPad/i.test(navigator.userAgent) ? 'Mobile' : 'Desktop';

                // Send heartbeat function via navigator.sendBeacon (or XHR fallback)
                function sendPulse(action) {
                    var endpoint = '/visitor-heartbeat.php';
                    var payload = JSON.stringify({
                        vid: vid,
                        act: action || 'ping',
                        url: currentUrl,
                        title: document.title || currentTitle,
                        device: deviceType,
                        profile: getProfile(),
                        history: visitorHistory,
                        is_new: isNewDaily ? 1 : 0
                    });

                    if (navigator.sendBeacon) {
                        navigator.sendBeacon(endpoint, payload);
                    } else {
                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', endpoint, true);
                        xhr.setRequestHeader('Content-Type', 'application/json');
                        xhr.send(payload);
                    }
                    if (isNewDaily) isNewDaily = false;
                }

                // Immediate initial pulse on page load
                sendPulse('enter');

                // Routine background heartbeats every 20 seconds
                setInterval(function () {
                    sendPulse('ping');
                }, 20000);

                // Page visibility change: when returning to tab, send instant refresh pulse
                document.addEventListener('visibilitychange', function () {
                    if (document.visibilityState === 'visible') {
                        sendPulse('ping');
                    }
                });
            } catch (e) { }
        })();
    </script>

    <!-- Dynamic Body Scripts -->
    {!! \App\Models\Setting::get('site_body_scripts') !!}

    <!-- Dynamic Tawk.to Live Chat / Invisible Visitor Tracker -->
    @php
        $tawkEnabled = (bool) \App\Models\Setting::get('tawkto_enabled', false);
        $tawkPropId = \App\Models\Setting::get('tawkto_property_id', '');
        $tawkWidgetId = \App\Models\Setting::get('tawkto_widget_id', 'default');
        $tawkMode = \App\Models\Setting::get('tawkto_visibility_mode', 'hidden');
        $tawkAuthOnly = (bool) \App\Models\Setting::get('tawkto_track_logged_in_only', false);
        $canRunTawk = $tawkEnabled && !empty($tawkPropId) && (!$tawkAuthOnly || auth()->check());
    @endphp

    @if($canRunTawk)
        <script type="text/javascript">
            var Tawk_API = Tawk_API || {}, Tawk_LoadStart = new Date();

            @if($tawkMode === 'hidden')
                // OFFICIAL TAWK.TO METHOD: Hide the widget BEFORE it loads/renders to prevent flashing
                window.Tawk_API.onBeforeLoad = function () {
                    try {
                        if (typeof window.Tawk_API.hideWidget === 'function') {
                            window.Tawk_API.hideWidget();
                        }
                    } catch (e) { }
                };

                // Fallback on load
                window.Tawk_API.onLoad = function () {
                    try {
                        if (typeof window.Tawk_API.hideWidget === 'function') {
                            window.Tawk_API.hideWidget();
                        }
                    } catch (e) { }
                };
            @endif

            @auth
                // Identify logged-in customer for rich live tracking in Tawk.to
                Tawk_API.visitor = {
                    name: '{{ addslashes(auth()->user()->name) }}',
                        email: '{{ addslashes(auth()->user()->email ?? '') }}'
                };
            @endauth

            (function () {
                var s1 = document.createElement("script"), s0 = document.getElementsByTagName("script")[0];
                s1.async = true;
                s1.src = 'https://embed.tawk.to/{{ trim($tawkPropId) }}/{{ trim($tawkWidgetId) }}';
                s1.charset = 'UTF-8';
                s1.setAttribute('crossorigin', '*');
                s0.parentNode.insertBefore(s1, s0);
            })();
        </script>
        @if($tawkMode === 'hidden')
            <style>
                /* Additional hard-hide guarantee so no chat bubble flashes during initial async script load */
                #tawk-default-container,
                iframe[title*="chat" i],
                div[class*="tawk-" i] {
                    display: none !important;
                    visibility: hidden !important;
                    opacity: 0 !important;
                    pointer-events: none !important;
                }
            </style>
        @endif
    @endif
</body>

</html>