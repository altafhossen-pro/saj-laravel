<div x-data="{ open: false }" 
     @open-category-drawer.window="open = true" 
     @keydown.escape.window="open = false">
    <!-- Backdrop -->
    <div x-show="open" 
         style="display: none;" 
         class="fixed inset-0 z-[90] bg-black/50 backdrop-blur-xs" 
         @click="open = false" 
         x-transition.opacity></div>

    <!-- Drawer (Left Slide-in like Cart) -->
    <div x-show="open" 
         style="display: none;"
         x-transition:enter="transition-transform duration-300 ease-out"
         x-transition:enter-start="-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition-transform duration-200 ease-in"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="-translate-x-full"
         class="fixed inset-y-0 left-0 z-[100] w-full max-w-xs sm:max-w-sm bg-white shadow-2xl flex flex-col h-full border-r border-gray-100">
         
         <!-- Header -->
         <div class="flex items-center justify-between p-4 border-b border-gray-100 shrink-0">
            <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-pink-50 text-pink-600 flex items-center justify-center">
                    <i class="ph ph-squares-four text-lg"></i>
                </div>
                <span>All Categories</span>
            </h2>
            <button @click="open = false" 
                    class="text-gray-400 hover:text-gray-700 p-2 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer"
                    aria-label="Close Categories Drawer">
                <i class="ph ph-x text-lg"></i>
            </button>
         </div>

         <!-- Body: Scrollable Categories -->
         <div class="flex-1 overflow-y-auto p-3 custom-scrollbar">
             @if(isset($sidebarCategories) && $sidebarCategories->count() > 0)
                 <x-frontend.sidebar-categories :categories="$sidebarCategories" />
             @else
                 <div class="py-8 text-center text-sm text-gray-400">
                     No categories available
                 </div>
             @endif
         </div>

         <!-- Footer (WhatsApp / Support) -->
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
         <div class="p-4 border-t border-gray-100 shrink-0 bg-gray-50/50">
             <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer"
                 class="flex items-center justify-center gap-2 w-full py-2.5 px-4 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 rounded-xl text-sm font-semibold transition-all duration-200 border border-emerald-200/60 shadow-xs">
                 <i class="ph-fill ph-whatsapp-logo text-emerald-600 text-lg"></i>
                 <span>Customer Support</span>
             </a>
         </div>
    </div>
</div>
