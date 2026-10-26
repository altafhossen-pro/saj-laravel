@extends('layouts.app')

@php
    $siteName = \App\Models\Setting::get('site_name', 'SAJ');
    $sitePhone = \App\Models\Setting::get('site_phone');
    $siteEmail = \App\Models\Setting::get('site_email');
    $siteWhatsapp = \App\Models\Setting::get('site_whatsapp');
    $lastUpdated = 'August 6, 2026';
    $metaDescription = "Return, Replacement and Refund Policy of {$siteName}. Read our product-specific replacement eligibility, warranty claims, and step-by-step resolution process.";
@endphp

@section('title', "Return & Refund Policy | {$siteName}")

@section('meta')
    <meta name="description" content="{{ $metaDescription }}">
    <link rel="canonical" href="{{ url('/return-and-refund-policy') }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/return-and-refund-policy') }}">
    <meta property="og:title" content="Return & Refund Policy - {{ $siteName }}">
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
                <span itemprop="name">Return & Refund Policy</span>
                <meta itemprop="position" content="2" />
            </li>
        </ol>
    </nav>

    <!-- Header Banner Card -->
    <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 md:p-8 mb-6">
        <div class="max-w-4xl">
            <span class="inline-block px-3.5 py-1 bg-purple-50 text-purple-600 rounded-full text-xs font-semibold uppercase tracking-wider mb-3">
                Returns & Replacement Terms
            </span>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">
                Return & Refund Policy
            </h1>
            <p class="text-gray-500 text-sm md:text-base font-normal">
                Please review product eligibility and warranty terms carefully &bull; Last updated: {{ $lastUpdated }}
            </p>
            <p class="text-gray-600 text-base md:text-lg leading-relaxed mt-4 font-normal">
                At <strong class="text-gray-900 font-semibold">{{ $siteName }}</strong>, customer satisfaction is our top priority. However, please note that not all products are eligible for return or replacement—policies vary across individual products and categories. Please review the criteria below before placing an order.
            </p>
        </div>
    </section>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

        <!-- Left Column: Detailed Policy -->
        <article class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-6 md:p-8 space-y-8">

            <!-- 1. Replacement Eligibility Notice -->
            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 font-bold text-sm flex items-center justify-center shrink-0">1</span>
                    <h2 class="text-lg md:text-xl font-bold text-gray-900">Replacement Eligibility & Scope</h2>
                </div>
                <div class="text-sm md:text-base text-gray-600 leading-relaxed pl-11 space-y-2">
                    <p class="font-medium text-gray-900">
                        Not all products are returnable or refundable. <strong>Only items explicitly designated with replacement or warranty availability</strong> on their respective product details page are eligible for replacement:
                    </p>
                    <ul class="list-disc pl-5 space-y-1.5 text-gray-600">
                        <li>Whether an item has replacement or warranty coverage is clearly highlighted under the ‘Warranty & Guarantee’ tab on that item's individual product details page.</li>
                        <li>Items showing <em>"No warranty and guarantee applied for this product"</em> are sold as final sale and cannot be returned or replaced.</li>
                        <li>Customers are strongly encouraged to inspect package integrity and merchandise with the courier representative upon delivery.</li>
                    </ul>
                </div>
            </section>

            <hr class="border-gray-100">

            <!-- 2. Conditions for Eligible Replacement -->
            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 font-bold text-sm flex items-center justify-center shrink-0">2</span>
                    <h2 class="text-lg md:text-xl font-bold text-gray-900">Conditions for Replacement</h2>
                </div>
                <div class="text-sm md:text-base text-gray-600 leading-relaxed pl-11 space-y-2">
                    <p>For items that qualify for replacement coverage, the following criteria must be fulfilled:</p>
                    <ul class="list-disc pl-5 space-y-1.5 text-gray-600">
                        <li>Notify our customer service team via WhatsApp or helpline within 24 hours of parcel receipt.</li>
                        <li>The merchandise must remain unused, unwashed, and in its authentic original packaging along with all manufacturer tags and accessories intact.</li>
                        <li>In case of manufacturing defects, missing pieces, or shipping damage, an unboxing video recorded while opening the parcel must be presented as proof.</li>
                    </ul>
                </div>
            </section>

            <hr class="border-gray-100">

            <!-- 3. Non-Returnable Items -->
            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 font-bold text-sm flex items-center justify-center shrink-0">3</span>
                    <h2 class="text-lg md:text-xl font-bold text-gray-900">Non-Returnable & Ineligible Scenarios</h2>
                </div>
                <div class="text-sm md:text-base text-gray-600 leading-relaxed pl-11 space-y-2">
                    <ul class="list-disc pl-5 space-y-1.5 text-gray-600">
                        <li>Requests based merely on a "change of mind" or personal aesthetic preference after accepting delivery.</li>
                        <li>Physical damages caused by customer mishandling, rough usage, or exposure to harsh chemicals/perfume.</li>
                        <li>Opened, tested, or seal-broken cosmetic, skincare, and beauty care products due to health and hygiene safety.</li>
                        <li>Personal piercing items (such as pierced earrings and nosepins) once worn, and customized engraved gifts.</li>
                        <li>Clearance, flash sale, or deeply discounted promotional items sold under "As-Is" final sale terms.</li>
                    </ul>
                </div>
            </section>

            <hr class="border-gray-100">

            <!-- 4. How to Request Replacement -->
            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 font-bold text-sm flex items-center justify-center shrink-0">4</span>
                    <h2 class="text-lg md:text-xl font-bold text-gray-900">How to Request a Replacement in 4 Steps</h2>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pl-11 pt-2">
                    <div class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 space-y-1">
                        <span class="text-xs font-bold text-purple-600 uppercase tracking-wider">Step 1</span>
                        <h4 class="font-bold text-gray-900 text-sm">Contact Support</h4>
                        <p class="text-xs text-gray-600">Reach out via WhatsApp or phone with your Order ID within 24 hours of parcel receipt.</p>
                    </div>

                    <div class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 space-y-1">
                        <span class="text-xs font-bold text-purple-600 uppercase tracking-wider">Step 2</span>
                        <h4 class="font-bold text-gray-900 text-sm">Submit Photo/Video Proof</h4>
                        <p class="text-xs text-gray-600">Send clear photos of the defect and the unboxing clip showing the package condition.</p>
                    </div>

                    <div class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 space-y-1">
                        <span class="text-xs font-bold text-purple-600 uppercase tracking-wider">Step 3</span>
                        <h4 class="font-bold text-gray-900 text-sm">Verification & Approval</h4>
                        <p class="text-xs text-gray-600">Our quality control team validates the issue and issues replacement approval.</p>
                    </div>

                    <div class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 space-y-1">
                        <span class="text-xs font-bold text-purple-600 uppercase tracking-wider">Step 4</span>
                        <h4 class="font-bold text-gray-900 text-sm">Replacement Dispatch</h4>
                        <p class="text-xs text-gray-600">A replacement unit is dispatched while collecting or retrieving the defective item.</p>
                    </div>
                </div>
            </section>

            <hr class="border-gray-100">

            <!-- 5. Refund Policy -->
            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 font-bold text-sm flex items-center justify-center shrink-0">5</span>
                    <h2 class="text-lg md:text-xl font-bold text-gray-900">Refund Policy</h2>
                </div>
                <p class="text-sm md:text-base text-gray-600 leading-relaxed pl-11">
                    If an eligible damaged item has no replacement stock available in our inventory, a full refund will be processed back to the customer's bKash, Nagad, or Bank Account within 3 to 7 business days following customer agreement.
                </p>
            </section>

        </article>

        <!-- Right Column: Navigation & Contact -->
        <aside class="space-y-6">

            <!-- Related Policies Card -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h3 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <i class="ph ph-shield-check text-purple-600 text-lg"></i>
                    <span>Store Policies</span>
                </h3>
                <ul class="space-y-2.5 text-sm">
                    <li>
                        <a href="{{ route('page.terms') }}" class="flex items-center justify-between p-3 rounded-xl bg-gray-50 hover:bg-purple-50 hover:text-purple-700 transition-colors font-medium text-gray-700 cursor-pointer">
                            <span>Terms & Conditions</span>
                            <i class="ph ph-caret-right text-gray-400"></i>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('page.privacy') }}" class="flex items-center justify-between p-3 rounded-xl bg-gray-50 hover:bg-purple-50 hover:text-purple-700 transition-colors font-medium text-gray-700 cursor-pointer">
                            <span>Privacy Policy</span>
                            <i class="ph ph-caret-right text-gray-400"></i>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('page.contact') }}" class="flex items-center justify-between p-3 rounded-xl bg-gray-50 hover:bg-purple-50 hover:text-purple-700 transition-colors font-medium text-gray-700 cursor-pointer">
                            <span>Contact Us & Help Desk</span>
                            <i class="ph ph-caret-right text-gray-400"></i>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Help Contact Box -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h3 class="text-base font-bold text-gray-900 mb-2">Replacement Helpline</h3>
                <p class="text-xs text-gray-500 mb-5 leading-relaxed">
                    Have an issue with your parcel? Reach out directly to our support team:
                </p>

                <div class="space-y-3">
                    @if($siteWhatsapp)
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $siteWhatsapp) }}" target="_blank" rel="noopener" class="flex items-center gap-3 p-3 rounded-xl bg-green-50 text-green-700 hover:bg-green-100 transition-colors cursor-pointer">
                        <i class="ph-fill ph-whatsapp-logo text-2xl text-green-600"></i>
                        <div>
                            <span class="block text-[11px] text-green-600 uppercase font-semibold">Send Photos on WhatsApp</span>
                            <span class="font-bold text-sm text-gray-900">{{ $siteWhatsapp }}</span>
                        </div>
                    </a>
                    @endif

                    @if($sitePhone)
                    <a href="tel:{{ $sitePhone }}" class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 hover:bg-brand-50 hover:text-brand-600 transition-colors cursor-pointer">
                        <i class="ph-fill ph-phone-call text-xl text-brand-600"></i>
                        <div>
                            <span class="block text-[11px] text-gray-400 uppercase font-semibold">Direct Call</span>
                            <span class="font-bold text-sm text-gray-800">{{ $sitePhone }}</span>
                        </div>
                    </a>
                    @endif
                </div>

                <div class="mt-6 pt-5 border-t border-gray-100">
                    <a href="{{ route('shop.search') }}" class="w-full inline-flex justify-center items-center gap-2 px-4 py-3 rounded-xl bg-gray-900 hover:bg-black text-white font-bold text-sm shadow-sm transition-colors cursor-pointer">
                        <i class="ph ph-shopping-bag"></i>
                        <span>Browse Products</span>
                    </a>
                </div>
            </div>

        </aside>

    </div>

</div>
@endsection
