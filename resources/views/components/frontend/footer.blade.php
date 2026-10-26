<footer class="bg-white border-t border-gray-100 pt-16 pb-8 mt-auto shrink-0">
    <div class="max-w-screen-2xl px-4 lg:px-8 mx-auto w-full">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-12 mb-12">
            <!-- Brand Info -->
            <div class="space-y-4">
                <a href="/" class="inline-flex items-center gap-1.5 group select-none">
                    @php
                        $footerLogo = \App\Models\Setting::get('site_logo');
                        $footerSiteName = \App\Models\Setting::get('site_name', 'SAJ');
                    @endphp
                    @if($footerLogo)
                        <img src="{{ $footerLogo }}" alt="{{ $footerSiteName }}" class="h-10 lg:h-12 w-auto object-contain group-hover:opacity-90 transition-opacity">
                    @else
                        <span class="text-3xl font-black uppercase tracking-[0.22em] text-pink-500 group-hover:text-pink-600 transition-colors inline-flex items-baseline" style="font-family: 'Playfair Display', serif;">{{ $footerSiteName }}<span class="text-pink-600 font-black">.</span></span>
                    @endif
                </a>
                <p class="text-sm text-gray-500 leading-relaxed">
                    {{ \App\Models\Setting::get('site_description', 'Your ultimate destination for premium quality products at the best prices. Experience shopping like never before.') }}
                </p>
                <div class="flex items-center flex-wrap gap-2.5 pt-2">
                    @php
                        $socials = [
                            [
                                'key' => 'site_facebook',
                                'name' => 'Facebook',
                                'url' => \App\Models\Setting::get('site_facebook'),
                                'icon' => 'ph-fill ph-facebook-logo',
                                'bg' => 'bg-[#1877F2]',
                                'text' => 'text-white',
                                'hover' => 'hover:bg-[#166fe5]',
                                'border' => 'border-[#1877F2]'
                            ],
                            [
                                'key' => 'site_youtube',
                                'name' => 'YouTube',
                                'url' => \App\Models\Setting::get('site_youtube'),
                                'icon' => 'ph-fill ph-youtube-logo',
                                'bg' => 'bg-[#FF0000]',
                                'text' => 'text-white',
                                'hover' => 'hover:bg-[#e60000]',
                                'border' => 'border-[#FF0000]'
                            ],
                            [
                                'key' => 'site_tiktok',
                                'name' => 'TikTok',
                                'url' => \App\Models\Setting::get('site_tiktok'),
                                'icon' => 'ph-fill ph-tiktok-logo',
                                'bg' => 'bg-[#000000]',
                                'text' => 'text-white',
                                'hover' => 'hover:bg-[#1f1f1f]',
                                'border' => 'border-black'
                            ],
                            [
                                'key' => 'site_linkedin',
                                'name' => 'LinkedIn',
                                'url' => \App\Models\Setting::get('site_linkedin'),
                                'icon' => 'ph-fill ph-linkedin-logo',
                                'bg' => 'bg-[#0A66C2]',
                                'text' => 'text-white',
                                'hover' => 'hover:bg-[#084e96]',
                                'border' => 'border-[#0A66C2]'
                            ],
                            [
                                'key' => 'site_instagram',
                                'name' => 'Instagram',
                                'url' => \App\Models\Setting::get('site_instagram'),
                                'icon' => 'ph-fill ph-instagram-logo',
                                'bg' => 'bg-gradient-to-tr from-[#f09433] via-[#dc2743] to-[#bc1888]',
                                'text' => 'text-white',
                                'hover' => 'hover:opacity-90',
                                'border' => 'border-transparent'
                            ]
                        ];
                    @endphp

                    @foreach($socials as $social)
                        @if(!empty($social['url']))
                            <a href="{{ $social['url'] }}" 
                               target="_blank" 
                               rel="noopener noreferrer" 
                               title="{{ $social['name'] }}"
                               class="w-9 h-9 rounded-full {{ $social['bg'] }} {{ $social['text'] }} flex items-center justify-center {{ $social['hover'] }} transition-all duration-200 shadow-sm hover:shadow hover:-translate-y-0.5 cursor-pointer">
                                <i class="{{ $social['icon'] }} text-lg"></i>
                            </a>
                        @else
                            <span title="{{ $social['name'] }}"
                                  class="w-9 h-9 rounded-full {{ $social['bg'] }} {{ $social['text'] }} flex items-center justify-center shadow-sm cursor-default select-none opacity-90">
                                <i class="{{ $social['icon'] }} text-lg"></i>
                            </span>
                        @endif
                    @endforeach
                </div>
            </div>

            <!-- Quick Links -->
            <div>
                <h4 class="text-gray-900 font-bold mb-6 uppercase tracking-wider text-sm flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-brand-500"></span> Quick Links
                </h4>
                <ul class="space-y-3">
                    <li><a href="/" class="text-gray-500 hover:text-brand-600 hover:pl-1 transition-all duration-300 text-sm inline-block">Home</a></li>
                    <li><a href="{{ route('shop.search') }}" class="text-gray-500 hover:text-brand-600 hover:pl-1 transition-all duration-300 text-sm inline-block">Shop All Products</a></li>
                    <li><a href="{{ route('page.about') }}" class="text-gray-500 hover:text-brand-600 hover:pl-1 transition-all duration-300 text-sm inline-block">About Us</a></li>
                    <li><a href="{{ route('page.contact') }}" class="text-gray-500 hover:text-brand-600 hover:pl-1 transition-all duration-300 text-sm inline-block">Contact Us</a></li>
                </ul>
            </div>

            <!-- Customer Service -->
            <div>
                <h4 class="text-gray-900 font-bold mb-6 uppercase tracking-wider text-sm flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-brand-500"></span> Customer Service
                </h4>
                <ul class="space-y-3">
                    <li><a href="{{ route('page.terms') }}" class="text-gray-500 hover:text-brand-600 hover:pl-1 transition-all duration-300 text-sm inline-block">Terms & Conditions</a></li>
                    <li><a href="{{ route('page.privacy') }}" class="text-gray-500 hover:text-brand-600 hover:pl-1 transition-all duration-300 text-sm inline-block">Privacy Policy</a></li>
                    <li><a href="{{ route('page.return-refund') }}" class="text-gray-500 hover:text-brand-600 hover:pl-1 transition-all duration-300 text-sm inline-block">Return & Refund Policy</a></li>
                    <li><a href="{{ route('page.tracking') }}" class="text-gray-500 hover:text-brand-600 hover:pl-1 transition-all duration-300 text-sm inline-block">Track Your Order</a></li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div>
                <h4 class="text-gray-900 font-bold mb-6 uppercase tracking-wider text-sm flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-brand-500"></span> Get In Touch
                </h4>
                <ul class="space-y-4">
                    @if($phone = \App\Models\Setting::get('site_phone'))
                    <li class="flex items-start gap-3 group">
                        <div class="mt-1 w-8 h-8 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center shrink-0 group-hover:bg-brand-600 group-hover:text-white transition-colors">
                            <i class="ph-fill ph-phone-call"></i>
                        </div>
                        <div>
                            <span class="block text-[11px] text-gray-400 font-bold uppercase mb-0.5 tracking-wider">Call Us</span>
                            <a href="tel:{{ $phone }}" class="text-gray-700 group-hover:text-brand-600 font-medium transition-colors">{{ $phone }}</a>
                        </div>
                    </li>
                    @endif
                    @if($email = \App\Models\Setting::get('site_email'))
                    <li class="flex items-start gap-3 group">
                        <div class="mt-1 w-8 h-8 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center shrink-0 group-hover:bg-brand-600 group-hover:text-white transition-colors">
                            <i class="ph-fill ph-envelope-simple"></i>
                        </div>
                        <div>
                            <span class="block text-[11px] text-gray-400 font-bold uppercase mb-0.5 tracking-wider">Email Us</span>
                            <a href="mailto:{{ $email }}" class="text-gray-700 group-hover:text-brand-600 font-medium transition-colors">{{ $email }}</a>
                        </div>
                    </li>
                    @endif
                    @if($wa = \App\Models\Setting::get('site_whatsapp'))
                    <li class="flex items-start gap-3 group">
                        <div class="mt-1 w-8 h-8 rounded-lg bg-green-50 text-green-600 flex items-center justify-center shrink-0 group-hover:bg-green-600 group-hover:text-white transition-colors">
                            <i class="ph-fill ph-whatsapp-logo"></i>
                        </div>
                        <div>
                            <span class="block text-[11px] text-gray-400 font-bold uppercase mb-0.5 tracking-wider">WhatsApp Support</span>
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $wa) }}" target="_blank" class="text-gray-700 group-hover:text-green-600 font-medium transition-colors">{{ $wa }}</a>
                        </div>
                    </li>
                    @endif
                    <li class="flex items-start gap-3 group">
                        <div class="mt-1 w-8 h-8 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center shrink-0 group-hover:bg-brand-600 group-hover:text-white transition-colors">
                            <i class="ph-fill ph-map-pin"></i>
                        </div>
                        <div>
                            <span class="block text-[11px] text-gray-400 font-bold uppercase mb-0.5 tracking-wider">Our Address</span>
                            <span class="text-gray-700 font-medium text-xs leading-relaxed block">Notun Bazar, Vatara, Dhaka 1212</span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <div class="pt-8 border-t border-gray-100 flex flex-col md:flex-row items-center justify-between gap-4">
            <p class="text-sm text-gray-500 font-medium">
                &copy; {{ date('Y') }} <span class="text-brand-600">{{ \App\Models\Setting::get('site_name', 'SAJ') }}</span>. All rights reserved.
            </p>
            <div class="flex items-center gap-3">
                <div class="w-10 h-6 bg-gray-100 rounded flex items-center justify-center text-xl text-gray-400 hover:text-brand-600 transition-colors" title="Cash on Delivery"><i class="ph-fill ph-money"></i></div>
                <div class="w-10 h-6 bg-gray-100 rounded flex items-center justify-center text-xl text-gray-400 hover:text-brand-600 transition-colors" title="Mobile Banking"><i class="ph-fill ph-device-mobile"></i></div>
                <div class="w-10 h-6 bg-gray-100 rounded flex items-center justify-center text-xl text-gray-400 hover:text-brand-600 transition-colors" title="Cards"><i class="ph-fill ph-credit-card"></i></div>
            </div>
        </div>
    </div>
    
    <!-- Margin bottom for mobile nav spacing -->
    <div class="h-16 md:hidden"></div>
</footer>
