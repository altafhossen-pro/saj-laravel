@extends('layouts.app')

@php
    $siteName = \App\Models\Setting::get('site_name', 'SAJ');
    $sitePhone = \App\Models\Setting::get('site_phone', '+880 1700-000000');
    $siteEmail = \App\Models\Setting::get('site_email', 'hello@saj.bd');
    $siteWhatsapp = \App\Models\Setting::get('site_whatsapp', '+880 1700-000000');
    $siteLogo = \App\Models\Setting::get('site_logo') ? url(\App\Models\Setting::get('site_logo')) : null;
    $sameAs = array_values(array_filter([
        \App\Models\Setting::get('site_facebook'),
        \App\Models\Setting::get('site_instagram'),
        \App\Models\Setting::get('site_youtube'),
    ]));
    $metaDescription = "Contact {$siteName} Customer Care. Helpline: {$sitePhone}, WhatsApp: {$siteWhatsapp}, Email: {$siteEmail}. Office & Warehouse: Notun Bazar, Vatara, Dhaka 1212. 24/7 shopping assistance across Bangladesh.";
@endphp

@section('title', "Contact Us | {$siteName} - Helpline & Support")

@section('meta')
    <meta name="description" content="{{ $metaDescription }}">
    <meta name="keywords"
        content="{{ $siteName }} contact, {{ $siteName }} helpline, customer support {{ $siteName }}, online shop phone number Dhaka, vatara dhaka ecommerce contact, complain and order tracking support">
    <link rel="canonical" href="{{ url('/contact-us') }}">

    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/contact-us') }}">
    <meta property="og:title" content="Contact Us - {{ $siteName }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    @if($siteLogo)
    <meta property="og:image" content="{{ $siteLogo }}">@endif

    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Contact Us - {{ $siteName }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">

    <!-- Schema.org ContactPage JSON-LD -->
    <script type="application/ld+json">
            {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'ContactPage',
        'name' => "Contact Us - {$siteName}",
        'url' => url('/contact-us'),
        'description' => $metaDescription,
        'mainEntity' => array_filter([
            '@type' => 'OnlineStore',
            'name' => $siteName,
            'url' => url('/'),
            'logo' => $siteLogo,
            'telephone' => $sitePhone,
            'email' => $siteEmail,
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'telephone' => $sitePhone,
                'contactType' => 'Customer Service',
                'areaServed' => 'BD',
                'availableLanguage' => ['Bengali', 'English']
            ],
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
                <li class="text-gray-800 font-medium" itemprop="itemListElement" itemscope
                    itemtype="https://schema.org/ListItem">
                    <span itemprop="name">Contact Us</span>
                    <meta itemprop="position" content="2" />
                </li>
            </ol>
        </nav>

        <!-- Top Banner Card -->
        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 md:p-8 mb-6">
            <div class="max-w-4xl">
                <span
                    class="inline-block px-3.5 py-1 bg-brand-50 text-brand-600 rounded-full text-xs font-semibold uppercase tracking-wider mb-3">
                    Customer Support & Help Desk
                </span>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-3">
                    যেকোনো প্রয়োজনে আমরা আপনার পাশে আছি
                </h1>
                <p class="text-gray-600 text-base md:text-lg leading-relaxed mb-2 font-normal">
                    অর্ডার সম্পর্কিত যেকোনো জিজ্ঞাসা, ডেলিভারি আপডেট বা সহায়তার জন্য আমাদের সাথে খুব সহজেই সরাসরি ফোন,
                    হোয়াটসঅ্যাপ অথবা মেসেজের মাধ্যমে যোগাযোগ করতে পারেন।
                </p>
                <p class="text-gray-500 text-sm md:text-base font-normal">
                    সাপোর্ট টিম প্রতিদিন সকাল ৯:০০ টা থেকে রাত ১০:০০ টা পর্যন্ত আপনার সেবায় নিয়োজিত।
                </p>
            </div>

            <!-- 4 Fast Contact Channels -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-6 pt-6 border-t border-gray-100">
                <!-- Helpline Phone -->
                <a href="tel:{{ $sitePhone }}"
                    onclick="if(typeof window.trackFbEvent==='function'){window.trackFbEvent('Contact', {content_name: 'Phone Call Click'});}"
                    class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 hover:border-brand-300 hover:bg-brand-50/30 transition-all group flex items-center gap-3.5 cursor-pointer">
                    <div
                        class="w-12 h-12 rounded-xl bg-brand-50 text-brand-600 group-hover:bg-brand-600 group-hover:text-white transition-colors flex items-center justify-center shrink-0 text-2xl">
                        <i class="ph-fill ph-phone-call"></i>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">সরাসরি কল
                            করুন</span>
                        <span
                            class="text-gray-900 font-bold text-sm md:text-base group-hover:text-brand-600 transition-colors block mt-0.5">{{ $sitePhone }}</span>
                    </div>
                </a>

                <!-- WhatsApp -->
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $siteWhatsapp) }}" target="_blank" rel="noopener"
                    onclick="if(typeof window.trackFbEvent==='function'){window.trackFbEvent('Contact', {content_name: 'WhatsApp Support Click'});}"
                    class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 hover:border-green-300 hover:bg-green-50/30 transition-all group flex items-center gap-3.5 cursor-pointer">
                    <div
                        class="w-12 h-12 rounded-xl bg-green-50 text-green-600 group-hover:bg-green-600 group-hover:text-white transition-colors flex items-center justify-center shrink-0 text-2xl">
                        <i class="ph-fill ph-whatsapp-logo"></i>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">হোয়াটসঅ্যাপ
                            চ্যাট</span>
                        <span
                            class="text-gray-900 font-bold text-sm md:text-base group-hover:text-green-600 transition-colors block mt-0.5">{{ $siteWhatsapp }}</span>
                    </div>
                </a>

                <!-- Email Support -->
                <a href="mailto:{{ $siteEmail }}"
                    class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 hover:border-indigo-300 hover:bg-indigo-50/30 transition-all group flex items-center gap-3.5 cursor-pointer">
                    <div
                        class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-colors flex items-center justify-center shrink-0 text-2xl">
                        <i class="ph-fill ph-envelope-simple"></i>
                    </div>
                    <div class="min-w-0">
                        <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">ইমেইল সহায়তা</span>
                        <span
                            class="text-gray-900 font-bold text-xs md:text-sm group-hover:text-indigo-600 transition-colors block mt-0.5 truncate">{{ $siteEmail }}</span>
                    </div>
                </a>

                <!-- Order Tracking -->
                <a href="{{ route('page.tracking') }}"
                    class="p-4 rounded-xl border border-gray-100 bg-gray-50/50 hover:border-amber-300 hover:bg-amber-50/30 transition-all group flex items-center gap-3.5 cursor-pointer">
                    <div
                        class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 group-hover:bg-amber-600 group-hover:text-white transition-colors flex items-center justify-center shrink-0 text-2xl">
                        <i class="ph-fill ph-truck"></i>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">অর্ডার
                            ট্র্যাকিং</span>
                        <span
                            class="text-gray-900 font-bold text-sm md:text-base group-hover:text-amber-600 transition-colors block mt-0.5">লাইভ
                            স্ট্যাটাস দেখুন</span>
                    </div>
                </a>
            </div>
        </section>

        <!-- Main Grid: Quick Contact Message Form & Office Details -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

            <!-- Column 1 & 2: Quick Message Form -->
            <section class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-6 md:p-8" x-data="{ 
                             sent: false,
                             loading: false,
                             errorMessage: '',
                             formData: {
                                 name: '',
                                 phone: '',
                                 email: '',
                                 subject: 'নতুন অর্ডার করতে চাই',
                                 message: ''
                             },
                             get phoneError() {
                                 const p = this.formData.phone ? this.formData.phone.trim() : '';
                                 if (!p) return '';
                                 if (!p.startsWith('01')) {
                                     return 'মোবাইল নম্বরটি অবশ্যই 01 দিয়ে শুরু হতে হবে।';
                                 }
                                 if (p.length > 11) {
                                     return 'মোবাইল নম্বর সর্বোচ্চ 11 ডিজিট হতে পারে (অতিরিক্ত ডিজিট দেওয়া যাবে না)।';
                                 }
                                 if (p.length > 2 && !/^01[0-9]+$/.test(p)) {
                                     return 'মোবাইল নম্বরে শুধুমাত্র সংখ্যা (0-9) ব্যবহার করুন।';
                                 }
                                 if (p.length < 11) {
                                     return 'সঠিক 11 ডিজিটের মোবাইল নম্বর লিখুন (বর্তমানে ' + p.length + ' ডিজিট)।';
                                 }
                                 return '';
                             },
                             get isFormValid() {
                                 const p = this.formData.phone ? this.formData.phone.trim() : '';
                                 const isPhoneValid = /^01[0-9]{9}$/.test(p);
                                 const isNameValid = this.formData.name && this.formData.name.trim().length > 0;
                                 const isMsgValid = this.formData.message && this.formData.message.trim().length > 0;
                                 return isPhoneValid && isNameValid && isMsgValid && !this.loading;
                             },
                             submitForm() {
                                 if (!this.isFormValid) {
                                     return;
                                 }

                                 this.loading = true;
                                 this.errorMessage = '';

                                 fetch('{{ route('page.contact.submit') }}', {
                                     method: 'POST',
                                     headers: {
                                         'Content-Type': 'application/json',
                                         'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                         'Accept': 'application/json'
                                     },
                                     body: JSON.stringify(this.formData)
                                 })
                                 .then(async (res) => {
                                     const data = await res.json();
                                     if (!res.ok) {
                                         throw new Error(data.message || 'মেসেজ পাঠাতে সমস্যা হয়েছে। দয়া করে আবার চেষ্টা করুন।');
                                     }
                                     return data;
                                 })
                                 .then(data => {
                                     this.loading = false;
                                     this.sent = true;
                                     if (typeof window.trackFbEvent === 'function') {
                                         window.trackFbEvent('Contact', {
                                             content_name: 'Contact Form Message'
                                         });
                                     }
                                 })
                                 .catch(err => {
                                     this.loading = false;
                                     this.errorMessage = err.message || 'একটি অপ্রত্যাশিত সমস্যা হয়েছে। অনুগ্রহ করে আবার চেষ্টা করুন।';
                                 });
                             }
                         }">
                <h2 class="text-xl font-bold text-gray-900 mb-1">আমাদের একটি মেসেজ পাঠান</h2>
                <p class="text-sm md:text-base text-gray-500 mb-6 font-normal">
                    আপনার কোনো মতামত, জিজ্ঞাসা বা অর্ডার সংক্রান্ত বিষয় থাকলে নিচের বক্সে লিখে পাঠান:
                </p>

                <template x-if="sent">
                    <div class="p-8 rounded-2xl bg-emerald-50 border border-emerald-200 text-center space-y-3">
                        <div
                            class="w-14 h-14 mx-auto rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-3xl">
                            <i class="ph ph-check-circle"></i>
                        </div>
                        <h3 class="text-lg font-bold text-emerald-900">মেসেজটি সফলভাবে পাঠানো হয়েছে!</h3>
                        <p class="text-sm text-emerald-700 max-w-md mx-auto">
                            ধন্যবাদ! আমাদের কাস্টমার রিলেশন প্রতিনিধি আপনার বার্তার প্রেক্ষিতে খুব দ্রুত আপনার সাথে যোগাযোগ
                            করবেন।
                        </p>
                        <div class="pt-2">
                            <button type="button"
                                @click="sent = false; formData.message = ''; formData.name = ''; formData.phone = '';"
                                class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition-colors cursor-pointer inline-flex items-center gap-1.5">
                                <i class="ph ph-arrow-counter-clockwise"></i>
                                <span>আরেকটি মেসেজ পাঠান</span>
                            </button>
                        </div>
                    </div>
                </template>

                <form x-show="!sent" @submit.prevent="submitForm()" class="space-y-4.5">
                    <!-- Error Alert -->
                    <div x-show="errorMessage"
                        class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm font-medium flex items-center gap-2">
                        <i class="ph ph-warning-circle text-lg shrink-0"></i>
                        <span x-text="errorMessage"></span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4.5">
                        <div>
                            <label class="block text-sm font-bold text-gray-800 mb-1.5">আপনার নাম <span
                                    class="text-rose-500">*</span></label>
                            <input type="text" x-model="formData.name" required placeholder="উদাঃ মোহাম্মদ করিম"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-800 mb-1.5">
                                মোবাইল নম্বর (১১ ডিজিট) <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="tel" x-model="formData.phone" maxlength="11" required placeholder="01XXXXXXXXX"
                                    :class="phoneError ? 'border-rose-400 focus:ring-rose-400 bg-rose-50/20' : (formData.phone && !phoneError ? 'border-emerald-400 focus:ring-emerald-400' : 'border-gray-200 focus:ring-brand-500')"
                                    class="w-full bg-gray-50 border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:bg-white transition-all font-mono">

                                <!-- Valid check icon -->
                                <template x-if="formData.phone && !phoneError">
                                    <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-emerald-600 text-base">
                                        <i class="ph-bold ph-check-circle"></i>
                                    </span>
                                </template>
                            </div>

                            <!-- Inline Phone Error Message -->
                            <div x-show="phoneError"
                                class="mt-1.5 text-xs text-rose-600 flex items-center gap-1 font-medium">
                                <i class="ph ph-warning-circle text-sm shrink-0"></i>
                                <span x-text="phoneError"></span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4.5">
                        <div>
                            <label class="block text-sm font-bold text-gray-800 mb-1.5">ইমেইল ঠিকানা (ঐচ্ছিক)</label>
                            <input type="email" x-model="formData.email" placeholder="name@example.com"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-800 mb-1.5">বিষয় / টপিক</label>
                            <select x-model="formData.subject"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all cursor-pointer">
                                <option value="নতুন অর্ডার করতে চাই">নতুন অর্ডার করতে চাই</option>
                                <option value="চলমান অর্ডারের তথ্য জানতে চাই">চলমান অর্ডারের তথ্য জানতে চাই</option>
                                <option value="পণ্য পরিবর্তন বা রিটার্ন সুবিধা">পণ্য পরিবর্তন বা রিটার্ন সুবিধা</option>
                                <option value="পণ্য সম্পর্কিত জিজ্ঞাসা">পণ্য সম্পর্কিত জিজ্ঞাসা</option>
                                <option value="অন্যান্য বিষয়">অন্যান্য বিষয়</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-800 mb-1.5">আপনার বার্তা বা মেসেজ <span
                                class="text-rose-500">*</span></label>
                        <textarea x-model="formData.message" rows="4" required
                            placeholder="আপনার জিজ্ঞাসা বিস্তারিত এখানে লিখুন..."
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl p-4 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all resize-y"></textarea>
                    </div>

                    <div class="pt-2 flex items-center gap-3">
                        <button type="submit" :disabled="!isFormValid"
                            :class="!isFormValid ? 'opacity-50 cursor-not-allowed bg-gray-400 hover:bg-gray-400' : 'bg-brand-600 hover:bg-brand-700 cursor-pointer shadow-sm'"
                            class="px-7 py-3 rounded-xl text-white font-bold text-sm transition-all inline-flex items-center gap-2">
                            <template x-if="!loading">
                                <div class="flex items-center gap-2">
                                    <span>মেসেজ পাঠান</span>
                                    <i class="ph ph-paper-plane-tilt text-base"></i>
                                </div>
                            </template>
                            <template x-if="loading">
                                <div class="flex items-center gap-2">
                                    <i class="ph ph-spinner animate-spin text-base"></i>
                                    <span>পাঠানো হচ্ছে...</span>
                                </div>
                            </template>
                        </button>

                        <template x-if="formData.phone && phoneError">
                            <span class="text-xs text-rose-500 font-medium">
                                মোবাইল নম্বরটি সঠিক করুন
                            </span>
                        </template>
                    </div>
                </form>
            </section>

            <!-- Column 3: Corporate Office Address Card -->
            <aside class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 md:p-8 flex flex-col justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 mb-5">অফিস ও ওয়্যারহাউজ</h2>

                    <address class="not-italic space-y-5 text-sm">
                        <!-- Location -->
                        <div class="flex gap-3.5">
                            <div
                                class="w-9 h-9 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center shrink-0 text-lg mt-0.5">
                                <i class="ph-fill ph-map-pin"></i>
                            </div>
                            <div>
                                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider">সরাসরি
                                    ঠিকানা</span>
                                <span class="text-gray-900 font-bold text-sm md:text-base leading-relaxed block mt-0.5">
                                    Notun Bazar, Vatara, Dhaka 1212, Bangladesh
                                </span>
                                <span class="text-xs text-gray-500 block mt-1">
                                    (গুলশান-বাড্ডা সংলগ্ন প্রধান পয়েন্ট)
                                </span>
                            </div>
                        </div>

                        <!-- Business Hours -->
                        <div class="flex gap-3.5">
                            <div
                                class="w-9 h-9 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 text-lg mt-0.5">
                                <i class="ph-fill ph-clock"></i>
                            </div>
                            <div>
                                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider">সার্ভিস
                                    আওয়ার্স</span>
                                <span class="text-gray-900 font-bold text-sm block mt-0.5">প্রতিদিন: সকাল ৯:০০ টা - রাত
                                    ১০:০০ টা</span>
                                <span class="text-xs text-emerald-600 font-semibold block mt-0.5">অনলাইন অর্ডার ২৪/৭ খোলা
                                    থাকে</span>
                            </div>
                        </div>

                        <!-- Delivery Promise -->
                        <div class="flex gap-3.5">
                            <div
                                class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 text-lg mt-0.5">
                                <i class="ph-fill ph-package"></i>
                            </div>
                            <div>
                                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider">ডেলিভারি
                                    কভারেজ</span>
                                <span class="text-gray-900 font-bold text-sm block mt-0.5">সারা বাংলাদেশের ৬৪টি
                                    জেলায়</span>
                                <span class="text-xs text-gray-500 block mt-0.5">প্রযোজ্য ক্ষেত্রে ক্যাশ অন ডেলিভারি</span>
                            </div>
                        </div>
                    </address>
                </div>

                <div class="mt-8 pt-5 border-t border-gray-100">
                    <a href="{{ route('shop.search') }}"
                        class="w-full inline-flex justify-center items-center gap-2 px-4 py-3.5 rounded-xl bg-gray-900 hover:bg-black text-white text-sm font-bold shadow-sm transition-colors cursor-pointer">
                        <i class="ph ph-shopping-bag text-base"></i>
                        <span>পণ্য ব্রাউজ করুন</span>
                    </a>
                </div>
            </aside>
        </div>

        <!-- FAQ Accordion: Frequently Asked Questions (Beginner Friendly) -->
        <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 md:p-8 mb-4" x-data="{ openFaq: 1 }">
            <h2 class="text-xl font-bold text-gray-900 mb-1">সাধারণ জিজ্ঞাসা (FAQ)</h2>
            <p class="text-sm md:text-base text-gray-500 mb-6 font-normal">কেনাকাটা, ক্যাশ অন ডেলিভারি ও রিপ্লেসমেন্ট পলিসি
                সম্পর্কিত তথ্য:</p>

            <div class="space-y-3">
                <!-- FAQ 1 -->
                <div class="rounded-xl border border-gray-100 overflow-hidden">
                    <button type="button" @click="openFaq = (openFaq === 1 ? null : 1)"
                        class="w-full p-4 text-left font-bold text-gray-900 text-sm md:text-base flex items-center justify-between hover:bg-gray-50 transition-colors cursor-pointer">
                        <span>১. সব পণ্যে কি ক্যাশ অন ডেলিভারি সুবিধা আছে?</span>
                        <i class="ph text-lg text-gray-400" :class="openFaq === 1 ? 'ph-caret-up' : 'ph-caret-down'"></i>
                    </button>
                    <div x-show="openFaq === 1"
                        class="px-4 pb-4 text-sm text-gray-600 leading-relaxed font-normal bg-gray-50/40">
                        আমাদের বেশিরভাগ পণ্যের ক্ষেত্রেই ক্যাশ অন ডেলিভারি সুবিধা রয়েছে। তবে সব ধরনের পণ্যে বা লোকেশনে ক্যাশ
                        অন ডেলিভারি নাও থাকতে পারে — এটি পণ্য এবং ক্যাটাগরির উপর নির্ভর করে ভিন্ন হতে পারে। সংশ্লিষ্ট পণ্যের
                        বিস্তারিত (Product Details) পেজে এবং চেকআউটে পেমেন্ট অপশন দেখে নিতে পারেন।
                    </div>
                </div>

                <!-- FAQ 2 -->
                <div class="rounded-xl border border-gray-100 overflow-hidden">
                    <button type="button" @click="openFaq = (openFaq === 2 ? null : 2)"
                        class="w-full p-4 text-left font-bold text-gray-900 text-sm md:text-base flex items-center justify-between hover:bg-gray-50 transition-colors cursor-pointer">
                        <span>২. ঢাকা ও ঢাকার বাইরে কত দিনে ডেলিভারি পাওয়া যায়?</span>
                        <i class="ph text-lg text-gray-400" :class="openFaq === 2 ? 'ph-caret-up' : 'ph-caret-down'"></i>
                    </button>
                    <div x-show="openFaq === 2"
                        class="px-4 pb-4 text-sm text-gray-600 leading-relaxed font-normal bg-gray-50/40">
                        ঢাকা সিটির ভেতরে সাধারণত ২৪ থেকে ৪৮ ঘণ্টার মধ্যে এবং ঢাকার বাইরে সারা বাংলাদেশের যেকোনো প্রান্তে ২
                        থেকে ৪ দিনের মধ্যে হোম ডেলিভারি পৌঁছে দেওয়া হয়।
                    </div>
                </div>

                <!-- FAQ 3 -->
                <div class="rounded-xl border border-gray-100 overflow-hidden">
                    <button type="button" @click="openFaq = (openFaq === 3 ? null : 3)"
                        class="w-full p-4 text-left font-bold text-gray-900 text-sm md:text-base flex items-center justify-between hover:bg-gray-50 transition-colors cursor-pointer">
                        <span>৩. পণ্যে কোনো সমস্যা থাকলে কি রিপ্লেসমেন্ট বা রিটার্ন করা যাবে?</span>
                        <i class="ph text-lg text-gray-400" :class="openFaq === 3 ? 'ph-caret-up' : 'ph-caret-down'"></i>
                    </button>
                    <div x-show="openFaq === 3"
                        class="px-4 pb-4 text-sm text-gray-600 leading-relaxed font-normal bg-gray-50/40">
                        সব পণ্য রিপ্লেসেবল বা রিটার্নেবল/রিফান্ডেবল নয়; এটি পণ্যভেদে পরিবর্তনশীল। <strong>শুধুমাত্র যেসকল
                            পণ্যে রিপ্লেসমেন্ট সুবিধা প্রযোজ্য</strong> (যা প্রোডাক্টের বিবরণ বা Product Details পেজের
                        'Warranty / Guarantee' সেকশনে স্পষ্টভাবে উল্লেখ থাকে), সেগুলোর ক্ষেত্রেই ত্রুটি বা সমস্যা থাকলে
                        আমাদের হেল্পলাইন বা হোয়াটসঅ্যাপে যোগাযোগ করে রিপ্লেসমেন্ট সুবিধা নিতে পারবেন।
                    </div>
                </div>
            </div>
        </section>

    </div>
@endsection