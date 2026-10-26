@php
    $rawWaPhone = \App\Models\Setting::get('site_whatsapp') ?: \App\Models\Setting::get('site_phone');
    $cleanWaPhone = preg_replace('/[^0-9]/', '', $rawWaPhone ?: '');
    if (strlen($cleanWaPhone) === 11 && str_starts_with($cleanWaPhone, '01')) {
        $cleanWaPhone = '88' . $cleanWaPhone;
    }
    $siteName = \App\Models\Setting::get('site_name', 'SAJ');
    $waText = urlencode("Hello {$siteName}, I need some assistance with an order/product.");
    $whatsappUrl = $cleanWaPhone ? "https://wa.me/{$cleanWaPhone}?text={$waText}" : null;
@endphp

@if($whatsappUrl)
<!-- Floating WhatsApp Support Button -->
<aside aria-label="Customer Support" 
       class="fixed right-4 bottom-[76px] sm:right-6 sm:bottom-6 z-40 select-none group print:hidden">
    <a href="{{ $whatsappUrl }}" 
       target="_blank" 
       rel="noopener noreferrer"
       class="relative flex items-center justify-center cursor-pointer transition-transform duration-300 active:scale-95 group-hover:scale-105"
       aria-label="Chat with {{ $siteName }} on WhatsApp">
        
        <!-- Ambient Animated Glow Pulse Ring -->
        <span class="absolute inset-0 rounded-full bg-[#25D366] opacity-40 animate-ping pointer-events-none"></span>

        <!-- Floating Desktop Hover Tooltip / Badge -->
        <div class="hidden sm:flex items-center gap-2 absolute right-full mr-3.5 px-3.5 py-1.5 bg-gray-900/90 backdrop-blur-sm text-white text-xs font-semibold rounded-full shadow-lg opacity-0 translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300 pointer-events-none whitespace-nowrap">
            <span class="w-2 h-2 rounded-full bg-[#25D366] animate-pulse"></span>
            <span>Need Help? Chat with us</span>
            <!-- Tooltip Arrow -->
            <div class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-1 border-y-4 border-y-transparent border-l-4 border-l-gray-900/90"></div>
        </div>

        <!-- Main Button Circle -->
        <div class="relative w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gradient-to-tr from-[#128C7E] via-[#25D366] to-[#25D366] text-white flex items-center justify-center shadow-[0_8px_25px_rgba(37,211,102,0.45)] border-2 border-white/90 hover:shadow-[0_10px_30px_rgba(37,211,102,0.6)] transition-all duration-300">
            <i class="ph-fill ph-whatsapp-logo text-2xl sm:text-3xl leading-none transition-transform duration-300 group-hover:rotate-12"></i>
            
            <!-- Online Indicator Dot -->
            <span class="absolute top-0 right-0 w-3 h-3 sm:w-3.5 sm:h-3.5 bg-emerald-300 border-2 border-white rounded-full"></span>
        </div>
    </a>
</aside>
@endif
