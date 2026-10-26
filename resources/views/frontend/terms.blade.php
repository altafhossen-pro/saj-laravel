@extends('layouts.app')

@php
    $siteName = \App\Models\Setting::get('site_name', 'SAJ');
    $sitePhone = \App\Models\Setting::get('site_phone');
    $siteEmail = \App\Models\Setting::get('site_email');
    $siteWhatsapp = \App\Models\Setting::get('site_whatsapp');
    $lastUpdated = 'August 6, 2026';
    $metaDescription = "Terms & Conditions of {$siteName}. Read our store policies regarding order placement, product information, pricing, delivery, and customer obligations.";
@endphp

@section('title', "Terms & Conditions | {$siteName}")

@section('meta')
    <meta name="description" content="{{ $metaDescription }}">
    <link rel="canonical" href="{{ url('/terms-and-conditions') }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/terms-and-conditions') }}">
    <meta property="og:title" content="Terms & Conditions - {{ $siteName }}">
    <meta property="og:description" content="{{ $metaDescription }}">
@endsection

@section('content')
<div class="w-full max-w-full mx-auto px-4 lg:px-8 py-4">

    <!-- Breadcrumb -->
    <nav class="flex text-sm text-gray-500 mb-4" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-1 md:space-x-2" itemscope itemtype="https://schema.org/BreadcrumbList">
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <a href="/" class="hover:text-brand-600 cursor-pointer" itemprop="item"><span itemprop="name">Home</span></a>
                <meta itemprop="position" content="1" />
            </li>
            <li><span class="mx-1">/</span></li>
            <li class="text-gray-800 font-medium" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <span itemprop="name">Terms & Conditions</span>
                <meta itemprop="position" content="2" />
            </li>
        </ol>
    </nav>

    <!-- Header Banner Card -->
    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 md:p-8 mb-6">
        <div class="max-w-4xl">
            <span class="inline-block px-3.5 py-1 bg-brand-50 text-brand-600 rounded-full text-xs font-semibold uppercase tracking-wider mb-3">
                Store Policy & Legal Agreement
            </span>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">
                Terms & Conditions
            </h1>
            <p class="text-gray-500 text-sm md:text-base font-normal">
                Please read these terms carefully before using our service &bull; Last updated: {{ $lastUpdated }}
            </p>
            <p class="text-gray-600 text-base md:text-lg leading-relaxed mt-4 font-normal">
                Welcome to <strong class="text-gray-900 font-semibold">{{ $siteName }}</strong>. By accessing our website, browsing our catalog, or placing an order, you agree to be bound by the following Terms & Conditions.
            </p>
        </div>
    </section>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

        <!-- Left Column: Detailed Terms -->
        <article class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-6 md:p-8 space-y-8">

            <!-- 1. General -->
            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 font-bold text-sm flex items-center justify-center shrink-0">1</span>
                    <h2 class="text-lg md:text-xl font-bold text-gray-900">General</h2>
                </div>
                <p class="text-sm md:text-base text-gray-600 leading-relaxed pl-11">
                    {{ $siteName }} reserves the right to update products, prices, promotions, and these Terms & Conditions at any time without prior notice. Any modifications become effective immediately upon being published on this website.
                </p>
            </section>

            <hr class="border-gray-100">

            <!-- 2. Product Information -->
            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 font-bold text-sm flex items-center justify-center shrink-0">2</span>
                    <h2 class="text-lg md:text-xl font-bold text-gray-900">Product Information & Descriptions</h2>
                </div>
                <div class="text-sm md:text-base text-gray-600 leading-relaxed pl-11 space-y-2">
                    <p>We strive to ensure that all product descriptions, specifications, and images are accurate. However:</p>
                    <ul class="list-disc pl-5 space-y-1.5 text-gray-600">
                        <li>Slight color variations may occur due to device display settings and photography lighting.</li>
                        <li>Product dimensions or weight may slightly vary due to manufacturer production tolerances.</li>
                        <li>Customers are advised to review product attributes and instructions on the product packaging prior to usage.</li>
                    </ul>
                </div>
            </section>

            <hr class="border-gray-100">

            <!-- 3. Pricing -->
            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 font-bold text-sm flex items-center justify-center shrink-0">3</span>
                    <h2 class="text-lg md:text-xl font-bold text-gray-900">Pricing & Payment</h2>
                </div>
                <p class="text-sm md:text-base text-gray-600 leading-relaxed pl-11">
                    All prices are listed in Bangladeshi Taka (BDT ৳). Prices may change without prior notice due to market fluctuations or special promotions. Cash on Delivery (COD) is available on eligible products and locations as specified during checkout.
                </p>
            </section>

            <hr class="border-gray-100">

            <!-- 4. Orders -->
            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 font-bold text-sm flex items-center justify-center shrink-0">4</span>
                    <h2 class="text-lg md:text-xl font-bold text-gray-900">Orders & Verification</h2>
                </div>
                <div class="text-sm md:text-base text-gray-600 leading-relaxed pl-11 space-y-2">
                    <p>{{ $siteName }} reserves the right to:</p>
                    <ul class="list-disc pl-5 space-y-1.5 text-gray-600">
                        <li>Accept or decline any order at its sole discretion.</li>
                        <li>Verify customer details, phone number, and shipping address via call or message prior to processing the dispatch.</li>
                        <li>Cancel orders resulting from stock unavailability, incorrect pricing, or suspected unauthorized activity.</li>
                    </ul>
                </div>
            </section>

            <hr class="border-gray-100">

            <!-- 5. Delivery -->
            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 font-bold text-sm flex items-center justify-center shrink-0">5</span>
                    <h2 class="text-lg md:text-xl font-bold text-gray-900">Delivery & Logistics</h2>
                </div>
                <p class="text-sm md:text-base text-gray-600 leading-relaxed pl-11">
                    Estimated delivery time depends on destination and third-party courier partner operations (typically 24–48 hours inside Dhaka and 2–4 business days across other districts). Unavoidable delays caused by severe weather, public holidays, political unrest, or courier logistics disruptions are beyond our direct control.
                </p>
            </section>

            <hr class="border-gray-100">

            <!-- 6. Customer Responsibilities -->
            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 font-bold text-sm flex items-center justify-center shrink-0">6</span>
                    <h2 class="text-lg md:text-xl font-bold text-gray-900">Customer Responsibilities</h2>
                </div>
                <div class="text-sm md:text-base text-gray-600 leading-relaxed pl-11 space-y-2">
                    <p>To guarantee successful and prompt delivery, customers must provide:</p>
                    <ul class="list-disc pl-5 space-y-1.5 text-gray-600">
                        <li>Their full and correct legal name.</li>
                        <li>A valid and reachable 11-digit Bangladeshi mobile number (starting with 01).</li>
                        <li>An accurate and complete shipping address (including district, area, and street details).</li>
                        <li>Failed delivery attempts caused by inaccurate contact details may incur redelivery charges.</li>
                    </ul>
                </div>
            </section>

            <hr class="border-gray-100">

            <!-- 7. Intellectual Property -->
            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 font-bold text-sm flex items-center justify-center shrink-0">7</span>
                    <h2 class="text-lg md:text-xl font-bold text-gray-900">Intellectual Property</h2>
                </div>
                <p class="text-sm md:text-base text-gray-600 leading-relaxed pl-11">
                    All brand logos, trademarks, text, product descriptions, photography, graphics, and software assets on this platform belong exclusively to {{ $siteName }} and may not be reproduced, copied, or distributed without prior written consent.
                </p>
            </section>

            <hr class="border-gray-100">

            <!-- 8. Limitation of Liability -->
            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 font-bold text-sm flex items-center justify-center shrink-0">8</span>
                    <h2 class="text-lg md:text-xl font-bold text-gray-900">Limitation of Liability & Governing Law</h2>
                </div>
                <p class="text-sm md:text-base text-gray-600 leading-relaxed pl-11">
                    {{ $siteName }} shall not be held liable for any indirect, incidental, or consequential damages resulting from platform usage or merchandise purchase. These Terms & Conditions are governed by and construed in accordance with the applicable laws of Bangladesh.
                </p>
            </section>

        </article>

        <!-- Right Column: Navigation & Quick Contact -->
        <aside class="space-y-6">

            <!-- Related Policies Card -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h3 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <i class="ph ph-shield-check text-brand-600 text-lg"></i>
                    <span>Store Policies</span>
                </h3>
                <ul class="space-y-2.5 text-sm">
                    <li>
                        <a href="{{ route('page.privacy') }}" class="flex items-center justify-between p-3 rounded-xl bg-gray-50 hover:bg-brand-50 hover:text-brand-600 transition-colors font-medium text-gray-700 cursor-pointer">
                            <span>Privacy Policy</span>
                            <i class="ph ph-caret-right text-gray-400"></i>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('page.return-refund') }}" class="flex items-center justify-between p-3 rounded-xl bg-gray-50 hover:bg-brand-50 hover:text-brand-600 transition-colors font-medium text-gray-700 cursor-pointer">
                            <span>Return & Refund Policy</span>
                            <i class="ph ph-caret-right text-gray-400"></i>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('page.contact') }}" class="flex items-center justify-between p-3 rounded-xl bg-gray-50 hover:bg-brand-50 hover:text-brand-600 transition-colors font-medium text-gray-700 cursor-pointer">
                            <span>Contact Us & Help Desk</span>
                            <i class="ph ph-caret-right text-gray-400"></i>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Help Contact Box -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h3 class="text-base font-bold text-gray-900 mb-2">Have Any Questions?</h3>
                <p class="text-xs text-gray-500 mb-5 leading-relaxed">
                    Our customer care team is available daily to assist with inquiries:
                </p>

                <div class="space-y-3">
                    @if($sitePhone)
                    <a href="tel:{{ $sitePhone }}" class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 hover:bg-brand-50 hover:text-brand-600 transition-colors cursor-pointer">
                        <i class="ph-fill ph-phone-call text-xl text-brand-600"></i>
                        <div>
                            <span class="block text-[11px] text-gray-400 uppercase font-semibold">Direct Call</span>
                            <span class="font-bold text-sm text-gray-800">{{ $sitePhone }}</span>
                        </div>
                    </a>
                    @endif

                    @if($siteWhatsapp)
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $siteWhatsapp) }}" target="_blank" rel="noopener" class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 hover:bg-green-50 hover:text-green-600 transition-colors cursor-pointer">
                        <i class="ph-fill ph-whatsapp-logo text-xl text-green-600"></i>
                        <div>
                            <span class="block text-[11px] text-gray-400 uppercase font-semibold">WhatsApp</span>
                            <span class="font-bold text-sm text-gray-800">{{ $siteWhatsapp }}</span>
                        </div>
                    </a>
                    @endif

                    @if($siteEmail)
                    <a href="mailto:{{ $siteEmail }}" class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 hover:bg-indigo-50 hover:text-indigo-600 transition-colors cursor-pointer">
                        <i class="ph-fill ph-envelope-simple text-xl text-indigo-600"></i>
                        <div class="min-w-0">
                            <span class="block text-[11px] text-gray-400 uppercase font-semibold">Email Us</span>
                            <span class="font-bold text-xs text-gray-800 truncate block">{{ $siteEmail }}</span>
                        </div>
                    </a>
                    @endif
                </div>

                <div class="mt-6 pt-5 border-t border-gray-100">
                    <a href="{{ route('shop.search') }}" class="w-full inline-flex justify-center items-center gap-2 px-4 py-3 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm shadow-sm transition-colors cursor-pointer">
                        <i class="ph ph-shopping-bag"></i>
                        <span>Start Shopping</span>
                    </a>
                </div>
            </div>

        </aside>

    </div>

</div>
@endsection
