@extends('layouts.app')

@php
    $siteName = \App\Models\Setting::get('site_name', 'SAJ');
    $sitePhone = \App\Models\Setting::get('site_phone');
    $siteEmail = \App\Models\Setting::get('site_email');
    $siteWhatsapp = \App\Models\Setting::get('site_whatsapp');
    $lastUpdated = 'August 6, 2026';
    $metaDescription = "Privacy Policy for {$siteName}. Understand how we collect, handle, store, and safeguard your personal information when ordering online across Bangladesh.";
@endphp

@section('title', "Privacy Policy | {$siteName}")

@section('meta')
    <meta name="description" content="{{ $metaDescription }}">
    <link rel="canonical" href="{{ url('/privacy-policy') }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/privacy-policy') }}">
    <meta property="og:title" content="Privacy Policy - {{ $siteName }}">
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
                <span itemprop="name">Privacy Policy</span>
                <meta itemprop="position" content="2" />
            </li>
        </ol>
    </nav>

    <!-- Header Banner Card -->
    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 md:p-8 mb-6">
        <div class="max-w-4xl">
            <span class="inline-block px-3.5 py-1 bg-emerald-50 text-emerald-600 rounded-full text-xs font-semibold uppercase tracking-wider mb-3">
                Security & Data Protection
            </span>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">
                Privacy Policy
            </h1>
            <p class="text-gray-500 text-sm md:text-base font-normal">
                Your data protection is our highest commitment &bull; Last updated: {{ $lastUpdated }}
            </p>
            <p class="text-gray-600 text-base md:text-lg leading-relaxed mt-4 font-normal">
                At <strong class="text-gray-900 font-semibold">{{ $siteName }}</strong>, we are deeply committed to safeguarding the privacy and security of our customers' personal information. This policy explains how your information is collected, utilized, and safeguarded.
            </p>
        </div>
    </section>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

        <!-- Left Column: Detailed Privacy Policy -->
        <article class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-6 md:p-8 space-y-8">

            <!-- 1. What Information We Collect -->
            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 font-bold text-sm flex items-center justify-center shrink-0">1</span>
                    <h2 class="text-lg md:text-xl font-bold text-gray-900">Information We Collect</h2>
                </div>
                <div class="text-sm md:text-base text-gray-600 leading-relaxed pl-11 space-y-2">
                    <p>To process orders smoothly and offer personalized shopping experiences, we may collect the following details:</p>
                    <ul class="list-disc pl-5 space-y-1.5 text-gray-600">
                        <li><strong>Personal Information:</strong> Full name, active 11-digit mobile number, and accurate delivery destination.</li>
                        <li><strong>Contact Details:</strong> Optional email address for digital order confirmations and receipts.</li>
                        <li><strong>Technical & Security Data:</strong> IP address, device specifications, browser type, and interaction logs to prevent fraudulent transactions and cyber attacks.</li>
                        <li><strong>Order History:</strong> Purchased items, order frequency, delivery logs, and verified reviews.</li>
                    </ul>
                </div>
            </section>

            <hr class="border-gray-100">

            <!-- 2. How We Use Your Information -->
            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 font-bold text-sm flex items-center justify-center shrink-0">2</span>
                    <h2 class="text-lg md:text-xl font-bold text-gray-900">How We Use Your Information</h2>
                </div>
                <div class="text-sm md:text-base text-gray-600 leading-relaxed pl-11 space-y-2">
                    <p>We use customer data strictly for lawful and essential e-commerce purposes, including:</p>
                    <ul class="list-disc pl-5 space-y-1.5 text-gray-600">
                        <li>Fulfilling, shipping, and delivering purchases to your exact delivery address.</li>
                        <li>Sending live SMS and phone call order status notifications and courier dispatch tracking.</li>
                        <li>Responding quickly to customer inquiries, complaints, and replacement requests.</li>
                        <li>Maintaining platform safety, authenticating customer orders, and filtering fake requests.</li>
                    </ul>
                </div>
            </section>

            <hr class="border-gray-100">

            <!-- 3. Third-Party Sharing -->
            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 font-bold text-sm flex items-center justify-center shrink-0">3</span>
                    <h2 class="text-lg md:text-xl font-bold text-gray-900">Third-Party Data Sharing</h2>
                </div>
                <p class="text-sm md:text-base text-gray-600 leading-relaxed pl-11">
                    We strictly maintain a zero-tolerance policy against selling, leasing, or trading your personal data to external marketers or advertisers. Essential shipping details (recipient name, phone number, destination) are shared solely with certified courier logistics partners (e.g., Steadfast Courier) to accomplish doorstep parcel delivery.
                </p>
            </section>

            <hr class="border-gray-100">

            <!-- 4. Data Security -->
            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 font-bold text-sm flex items-center justify-center shrink-0">4</span>
                    <h2 class="text-lg md:text-xl font-bold text-gray-900">Data Security & Retention</h2>
                </div>
                <p class="text-sm md:text-base text-gray-600 leading-relaxed pl-11">
                    Our platform incorporates SSL encryption, secured databases, and multi-layer firewall infrastructure. Authorized personnel only have controlled access to fulfill customer orders and prevent unauthorized information exposure.
                </p>
            </section>

            <hr class="border-gray-100">

            <!-- 5. Cookies -->
            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 font-bold text-sm flex items-center justify-center shrink-0">5</span>
                    <h2 class="text-lg md:text-xl font-bold text-gray-900">Cookie & Session Policy</h2>
                </div>
                <p class="text-sm md:text-base text-gray-600 leading-relaxed pl-11">
                    We employ browser cookies and session tokens solely to preserve active shopping cart items, facilitate checkout, and analyze general site traffic performance. You can disable or wipe cookies through your browser preferences at any moment.
                </p>
            </section>

        </article>

        <!-- Right Column: Navigation & Contact -->
        <aside class="space-y-6">

            <!-- Related Policies Card -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h3 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <i class="ph ph-shield-check text-emerald-600 text-lg"></i>
                    <span>Store Policies</span>
                </h3>
                <ul class="space-y-2.5 text-sm">
                    <li>
                        <a href="{{ route('page.terms') }}" class="flex items-center justify-between p-3 rounded-xl bg-gray-50 hover:bg-emerald-50 hover:text-emerald-700 transition-colors font-medium text-gray-700 cursor-pointer">
                            <span>Terms & Conditions</span>
                            <i class="ph ph-caret-right text-gray-400"></i>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('page.return-refund') }}" class="flex items-center justify-between p-3 rounded-xl bg-gray-50 hover:bg-emerald-50 hover:text-emerald-700 transition-colors font-medium text-gray-700 cursor-pointer">
                            <span>Return & Refund Policy</span>
                            <i class="ph ph-caret-right text-gray-400"></i>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('page.contact') }}" class="flex items-center justify-between p-3 rounded-xl bg-gray-50 hover:bg-emerald-50 hover:text-emerald-700 transition-colors font-medium text-gray-700 cursor-pointer">
                            <span>Contact Us & Help Desk</span>
                            <i class="ph ph-caret-right text-gray-400"></i>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Help Contact Box -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h3 class="text-base font-bold text-gray-900 mb-2">Privacy Questions?</h3>
                <p class="text-xs text-gray-500 mb-5 leading-relaxed">
                    Have questions regarding your data privacy or account preferences? Feel free to reach out:
                </p>

                <div class="space-y-3">
                    @if($sitePhone)
                    <a href="tel:{{ $sitePhone }}" class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 hover:bg-brand-50 hover:text-brand-600 transition-colors cursor-pointer">
                        <i class="ph-fill ph-phone-call text-xl text-brand-600"></i>
                        <div>
                            <span class="block text-[11px] text-gray-400 uppercase font-semibold">Call Us</span>
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
                            <span class="block text-[11px] text-gray-400 uppercase font-semibold">Email</span>
                            <span class="font-bold text-xs text-gray-800 truncate block">{{ $siteEmail }}</span>
                        </div>
                    </a>
                    @endif
                </div>
            </div>

        </aside>

    </div>

</div>
@endsection
