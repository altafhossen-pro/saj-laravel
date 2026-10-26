@extends('layouts.app')

@php
    $siteName = \App\Models\Setting::get('site_name', 'SAJ');
    $sitePhone = \App\Models\Setting::get('site_phone');
    $siteEmail = \App\Models\Setting::get('site_email');
    $siteWhatsapp = \App\Models\Setting::get('site_whatsapp');
    $siteLogo = \App\Models\Setting::get('site_logo') ? url(\App\Models\Setting::get('site_logo')) : null;
    $sameAs = array_values(array_filter([
        \App\Models\Setting::get('site_facebook'),
        \App\Models\Setting::get('site_instagram'),
        \App\Models\Setting::get('site_youtube'),
    ]));
    $metaDescription = "About {$siteName} - Bangladesh's premier online boutique for trending jewelry, authentic cosmetics, skincare, and elegant ladies fashion. Enjoy Cash on Delivery across all 64 districts with premium quality guarantee.";
@endphp

@section('title', "About Us | {$siteName} - Premium Jewelry, Cosmetics & Fashion in Bangladesh")

@section('meta')
    <meta name="description" content="{{ $metaDescription }}">
    <meta name="keywords" content="{{ $siteName }}, about saj, jewelry online shopping bangladesh, earrings bd, necklaces, cosmetics bangladesh, ladies fashion, gold plated jewelry, beauty products, cash on delivery bangladesh">
    <link rel="canonical" href="{{ url('/about-us') }}">

    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/about-us') }}">
    <meta property="og:title" content="About {{ $siteName }} - Premium Jewelry & Cosmetics in Bangladesh">
    <meta property="og:description" content="{{ $metaDescription }}">
    @if($siteLogo)<meta property="og:image" content="{{ $siteLogo }}">@endif

    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="About {{ $siteName }} - Premium Jewelry & Cosmetics in Bangladesh">
    <meta name="twitter:description" content="{{ $metaDescription }}">

    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'AboutPage',
        'name' => "About {$siteName}",
        'url' => url('/about-us'),
        'description' => $metaDescription,
        'mainEntity' => array_filter([
            '@type' => 'OnlineStore',
            'name' => $siteName,
            'url' => url('/'),
            'logo' => $siteLogo,
            'telephone' => $sitePhone,
            'email' => $siteEmail,
            'areaServed' => 'BD',
            'currenciesAccepted' => 'BDT',
            'paymentAccepted' => 'Cash on Delivery',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => 'Notun Bazar, Vatara',
                'addressLocality' => 'Dhaka',
                'postalCode' => '1212',
                'addressCountry' => 'BD',
            ],
            'sameAs' => $sameAs ?: null,
        ]),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endsection

@section('content')
<div class="w-full max-w-full mx-auto px-4 lg:px-8 py-4 font-['Hind_Siliguri',sans-serif]">

    <!-- Breadcrumb -->
    <nav class="flex text-sm text-gray-500 mb-4" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-1 md:space-x-2" itemscope itemtype="https://schema.org/BreadcrumbList">
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <a href="/" class="hover:text-brand-600" itemprop="item"><span itemprop="name">Home</span></a>
                <meta itemprop="position" content="1" />
            </li>
            <li><span class="mx-1">/</span></li>
            <li class="text-gray-800 font-medium" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <span itemprop="name">About Us</span>
                <meta itemprop="position" content="2" />
            </li>
        </ol>
    </nav>

    <!-- Main About Intro Banner Card -->
    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 md:p-8 mb-6">
        <div class="max-w-4xl">
            <span class="inline-block px-3 py-1 bg-pink-50 text-pink-600 rounded-full text-xs font-semibold uppercase tracking-wider mb-3">
                Welcome to {{ $siteName }}
            </span>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-3.5">
                প্রিমিয়াম জুয়েলারি, খাঁটি কসমেটিক্স ও লেডিস ফ্যাশনের বিশ্বস্ত ঠিকানা
            </h1>
            <p class="text-gray-600 text-base md:text-lg leading-relaxed mb-3.5 font-normal">
                {{ $siteName }}-এর মূল লক্ষ্য হলো প্রতিটি নারীর প্রতিদিনের সাজসজ্জা, বিশেষ উৎসব এবং রূপচর্চাকে আরও প্রিমিয়াম ও আকর্ষণীয় করে তোলা। আমরা সরবরাহ করি লেটেস্ট ট্রেন্ডের এক্সক্লুসিভ গোল্ড-প্লেটেড জুয়েলারি, ১০০% আসল স্কিনকেয়ার ও কসমেটিক্স এবং মার্জিত লেডিস ফ্যাশন এক্সেসরিজ — সবকিছু সাধ্যের মধ্যে সর্বোচ্চ কোয়ালিটি নিশ্চিত করে।
            </p>
            <p class="text-gray-600 text-base md:text-lg leading-relaxed font-normal">
                ঘরে বসেই যেকোনো পছন্দের জুয়েলারি বা বিউটি প্রোডাক্ট খুব সহজে অর্ডার করুন। সারা বাংলাদেশের ৬৪টি জেলায় দ্রুত ডেলিভারি সুবিধা এবং প্রযোজ্য পণ্যে রয়েছে <strong class="text-gray-900 font-semibold">ক্যাশ অন ডেলিভারি (Cash on Delivery)</strong>। প্রতিটি পার্সেল গিফট-রেডি সুরক্ষিত প্যাকেজিংয়ে আপনার হাতে পৌঁছে দেওয়া হয়।
            </p>
        </div>

        <!-- 4 Key Highlights -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 pt-6 border-t border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-pink-50 text-pink-600 flex items-center justify-center shrink-0 text-2xl">
                    <i class="ph-fill ph-sparkle"></i>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-gray-900">১০০% প্রিমিয়াম ফিনিশ</h2>
                    <p class="text-xs text-gray-500 mt-0.5">নিখুঁত ও দীর্ঘস্থায়ী জুয়েলারি</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 text-2xl">
                    <i class="ph ph-shield-check"></i>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-gray-900">অথেনটিক বিউটি কেয়ার</h2>
                    <p class="text-xs text-gray-500 mt-0.5">১০০% আসল ও কোয়ালিটি টেস্টেড</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 text-2xl">
                    <i class="ph ph-truck"></i>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-gray-900">সারা দেশে ডেলিভারি</h2>
                    <p class="text-xs text-gray-500 mt-0.5">৬৪ জেলায় ক্যাশ অন ডেলিভারি</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0 text-2xl">
                    <i class="ph ph-gift"></i>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-gray-900">সুরক্ষিত গিফট প্যাকেজিং</h2>
                    <p class="text-xs text-gray-500 mt-0.5">নিরাপদ ও আকর্ষণীয় বক্স</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Two Columns: What You Can Buy & Contact/Address Info -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        
        <!-- Column 1 & 2: Popular Categories & Products We Offer -->
        <section class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-6 md:p-8">
            <h2 class="text-xl font-bold text-gray-900 mb-1">এখানে কী কী ধরনের পণ্য পাবেন?</h2>
            <p class="text-sm md:text-base text-gray-500 mb-6 font-normal">প্রতিদিনের সাজসজ্জা এবং পছন্দের সব কালেকশন সাজানো রয়েছে সহজ ক্যাটাগরিতে:</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4.5">
                <!-- Jewelry -->
                <div class="flex gap-4 p-4.5 rounded-xl border border-gray-100 hover:border-pink-200 transition-colors bg-gray-50/50">
                    <div class="w-11 h-11 rounded-lg bg-pink-50 text-pink-600 flex items-center justify-center shrink-0">
                        <i class="ph-fill ph-sketch-logo text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 text-base">ট্রেন্ডি জুয়েলারি কালেকশন (Jewelry)</h3>
                        <p class="text-sm text-gray-600 mt-1 leading-relaxed">
                            ঝুমকা, ড্রপ ও পার্টি ইয়াররিংস, ট্রেন্ডি নেকলেস ও চোকার, অ্যাডজাস্টেবল আংটি, চার্ম ব্রেসলেট, নোজপিন ও পায়েল।
                        </p>
                    </div>
                </div>

                <!-- Beauty & Cosmetics -->
                <div class="flex gap-4 p-4.5 rounded-xl border border-gray-100 hover:border-pink-200 transition-colors bg-gray-50/50">
                    <div class="w-11 h-11 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                        <i class="ph-fill ph-heart text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 text-base">স্কিনকেয়ার ও কসমেটিক্স (Beauty & Skincare)</h3>
                        <p class="text-sm text-gray-600 mt-1 leading-relaxed">
                            প্রিমিয়াম ব্র্যান্ডের ময়েশ্চারাইজার, লোশন, হেয়ার কেয়ার শ্যাম্পু, মেকআপ, লিপ কেয়ার এবং রিফ্রেশিং বডি মিস্ট।
                        </p>
                    </div>
                </div>

                <!-- Ladies Fashion -->
                <div class="flex gap-4 p-4.5 rounded-xl border border-gray-100 hover:border-pink-200 transition-colors bg-gray-50/50">
                    <div class="w-11 h-11 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                        <i class="ph-fill ph-t-shirt text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 text-base">লেডিস ফ্যাশন ও এক্সেসরিজ (Fashion)</h3>
                        <p class="text-sm text-gray-600 mt-1 leading-relaxed">
                            মার্জিত বোরকা, প্রিমিয়াম হিজাব ও খিমার, হিজাব পিন এক্সেসরিজ, স্টাইলিশ ব্যাগ ও এক্সক্লুসিভ লেডিস ওয়াচ।
                        </p>
                    </div>
                </div>

                <!-- Gifts & Combos -->
                <div class="flex gap-4 p-4.5 rounded-xl border border-gray-100 hover:border-pink-200 transition-colors bg-gray-50/50">
                    <div class="w-11 h-11 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <i class="ph-fill ph-gift text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 text-base">গিফট বক্স ও স্পেশাল কম্বো (Gift Sets)</h3>
                        <p class="text-sm text-gray-600 mt-1 leading-relaxed">
                            প্রিয়জনকে উপহার দেওয়ার জন্য আকর্ষণীয় জুয়েলারি গিফট বক্স, স্পেশাল কম্বো সেট ও পারফেক্ট সারপ্রাইজ প্যাকেজ।
                        </p>
                    </div>
                </div>
            </div>

            @if($categories->count())
                <div class="mt-7 pt-6 border-t border-gray-100">
                    <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider mb-3.5">জনপ্রিয় ক্যাটাগরিগুলো দেখুন:</h3>
                    <div class="flex flex-wrap gap-2.5">
                        @foreach($categories as $cat)
                            <a href="{{ route('shop.category', $cat->slug) }}"
                               class="px-4 py-2 rounded-full bg-gray-50 border border-gray-200 text-sm font-medium text-gray-700 hover:border-brand-500 hover:text-brand-600 transition-colors cursor-pointer">
                                {{ $cat->name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>

        <!-- Column 3: Contact & Address Box -->
        <aside class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 md:p-8" id="contact">
            <h2 class="text-xl font-bold text-gray-900 mb-5">আমাদের সাথে যোগাযোগ</h2>
            
            <address class="not-italic space-y-4.5 text-sm">
                <!-- Address -->
                <div class="flex gap-3.5">
                    <div class="w-9 h-9 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center shrink-0 text-lg mt-0.5">
                        <i class="ph-fill ph-map-pin"></i>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider">অফিস ও ওয়্যারহাউজ ঠিকানা</span>
                        <span class="text-gray-900 font-medium text-sm md:text-base leading-relaxed block mt-0.5">
                            Notun Bazar, Vatara, Dhaka 1212, Bangladesh
                        </span>
                    </div>
                </div>

                @if($sitePhone)
                    <!-- Phone -->
                    <div class="flex gap-3.5">
                        <div class="w-9 h-9 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center shrink-0 text-lg mt-0.5">
                            <i class="ph-fill ph-phone-call"></i>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider">হেল্পলাইন নম্বর</span>
                            <a href="tel:{{ $sitePhone }}" class="text-gray-900 font-bold text-base hover:text-brand-600 transition-colors block mt-0.5">{{ $sitePhone }}</a>
                        </div>
                    </div>
                @endif

                @if($siteWhatsapp)
                    <!-- WhatsApp -->
                    <div class="flex gap-3.5">
                        <div class="w-9 h-9 rounded-lg bg-green-50 text-green-600 flex items-center justify-center shrink-0 text-lg mt-0.5">
                            <i class="ph-fill ph-whatsapp-logo"></i>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider">হোয়াটসঅ্যাপ সাপোর্ট</span>
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $siteWhatsapp) }}" target="_blank" rel="noopener" class="text-gray-900 font-bold text-base hover:text-green-600 transition-colors block mt-0.5">{{ $siteWhatsapp }}</a>
                        </div>
                    </div>
                @endif

                @if($siteEmail)
                    <!-- Email -->
                    <div class="flex gap-3.5">
                        <div class="w-9 h-9 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center shrink-0 text-lg mt-0.5">
                            <i class="ph-fill ph-envelope-simple"></i>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider">ইমেইল ঠিকানা</span>
                            <a href="mailto:{{ $siteEmail }}" class="text-gray-900 font-medium text-sm hover:text-brand-600 transition-colors block mt-0.5 break-all">{{ $siteEmail }}</a>
                        </div>
                    </div>
                @endif
            </address>

            <div class="mt-7 pt-5 border-t border-gray-100">
                <a href="{{ route('shop.search') }}"
                   class="w-full inline-flex justify-center items-center gap-2 px-4 py-3.5 rounded-xl bg-pink-600 hover:bg-pink-700 text-white text-sm font-bold shadow-sm transition-colors cursor-pointer">
                    <span>কেনাকাটা শুরু করুন</span>
                    <i class="ph ph-arrow-right text-base"></i>
                </a>
            </div>
        </aside>
    </div>

    <!-- How Easy Is Shopping With Us? (Beginner Friendly Order Guide) -->
    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 md:p-8 mb-4">
        <h2 class="text-xl font-bold text-gray-900 mb-1">সহজে অর্ডার করার ৩টি নিয়ম</h2>
        <p class="text-sm md:text-base text-gray-500 mb-6 font-normal">কোনো ঝামেলা ছাড়াই মাত্র ১ মিনিটেই যেকোনো পছন্দের জিনিস অর্ডার করুন:</p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="flex items-start gap-3.5">
                <div class="w-9 h-9 rounded-full bg-pink-50 text-pink-600 font-bold text-base flex items-center justify-center shrink-0">
                    ১
                </div>
                <div>
                    <h3 class="font-bold text-gray-900 text-base">পছন্দের জুয়েলারি বা প্রসাধন বাছুন</h3>
                    <p class="text-sm text-gray-600 mt-1 leading-relaxed">
                        প্রোডাক্টের ছবি ও বিবরণ দেখে সরাসরি ‘Buy Now’ অথবা কার্ট বাটনে ক্লিক করুন।
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-3.5">
                <div class="w-9 h-9 rounded-full bg-pink-50 text-pink-600 font-bold text-base flex items-center justify-center shrink-0">
                    ২
                </div>
                <div>
                    <h3 class="font-bold text-gray-900 text-base">ঠিকানা ও ফোন নম্বর দিন</h3>
                    <p class="text-sm text-gray-600 mt-1 leading-relaxed">
                        ডেলিভারির জন্য আপনার মোবাইল নম্বর এবং সঠিক হোম ডেলিভারি ঠিকানা লিখুন।
                    </p>
                </div>
            </div>

            <div class="flex items-start gap-3.5">
                <div class="w-9 h-9 rounded-full bg-pink-50 text-pink-600 font-bold text-base flex items-center justify-center shrink-0">
                    ৩
                </div>
                <div>
                    <h3 class="font-bold text-gray-900 text-base">পার্সেল বুঝে নিয়ে মূল্য পরিশোধ করুন</h3>
                    <p class="text-sm text-gray-600 mt-1 leading-relaxed">
                        ক্যাশ অন ডেলিভারি সুবিধায় ডেলিভারি ম্যানের কাছ থেকে পার্সেল বুঝে পেয়ে মূল্য পরিশোধ করুন।
                    </p>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
