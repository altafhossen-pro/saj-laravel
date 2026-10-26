@props(['product'])

<!-- Share Modal -->
<div x-data="{ showShareModal: false }" @open-share-modal.window="showShareModal = true" x-show="showShareModal" style="display: none;" class="relative z-[100]" aria-labelledby="modal-title" role="dialog"
    aria-modal="true">
    <!-- Background backdrop -->
    <div x-show="showShareModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>

    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <!-- Modal panel -->
            <div x-show="showShareModal" @click.away="showShareModal = false" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 w-full max-w-sm sm:max-w-md">

                <!-- Header -->
                <div class="flex items-center justify-between p-6 pb-4 border-b border-gray-100">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-brand-50 flex items-center justify-center text-brand-600">
                            <i class="ph ph-share-network text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900" id="modal-title">Share Product</h3>
                            <p class="text-sm text-gray-500">Spread the word about this product</p>
                        </div>
                    </div>
                    <button type="button" @click="showShareModal = false"
                        class="text-gray-400 hover:text-gray-500 bg-gray-50 hover:bg-gray-100 p-2 rounded-lg transition-colors cursor-pointer">
                        <i class="ph ph-x text-lg"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-6 space-y-6">
                    <!-- Product Link -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-800 mb-2">Product Link</label>
                        <div class="flex items-center gap-2">
                            <input type="text" readonly value="{{ url('/product/' . $product->slug) }}"
                                id="share-link-input"
                                class="flex-1 w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-600 focus:outline-none focus:ring-2 focus:ring-brand-500">
                            
                            <button type="button" 
                                onclick="navigator.clipboard.writeText(document.getElementById('share-link-input').value); const btn = this; const originalText = btn.innerHTML; btn.innerHTML = '<i class=\'ph ph-check text-green-500\'></i> Copied'; setTimeout(() => btn.innerHTML = originalText, 2000);"
                                class="shrink-0 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg text-sm transition-colors flex items-center gap-1.5 cursor-pointer">
                                <i class="ph ph-copy"></i> Copy
                            </button>
                        </div>
                    </div>

                    <!-- Social Media Links -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-800 mb-3">Share on Social Media</label>
                        
                        @php
                            $shareUrl = urlencode(url('/product/' . $product->slug));
                            $shareTitle = urlencode($product->title);
                        @endphp

                        <div class="flex items-center gap-3 flex-wrap justify-start">
                            <!-- WhatsApp -->
                            <a href="https://wa.me/?text={{ $shareTitle }}%20{{ $shareUrl }}" target="_blank"
                                class="w-12 h-12 rounded-full bg-[#25D366] text-white flex items-center justify-center hover:scale-110 transition-transform shadow-sm">
                                <i class="ph-fill ph-whatsapp-logo text-2xl"></i>
                            </a>
                            <!-- Messenger -->
                            <a href="fb-messenger://share?link={{ $shareUrl }}" target="_blank"
                                class="w-12 h-12 rounded-full bg-[#0084FF] text-white flex items-center justify-center hover:scale-110 transition-transform shadow-sm">
                                <i class="ph-fill ph-messenger-logo text-2xl"></i>
                            </a>
                            <!-- Facebook -->
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank"
                                class="w-12 h-12 rounded-full bg-[#1877F2] text-white flex items-center justify-center hover:scale-110 transition-transform shadow-sm">
                                <i class="ph-fill ph-facebook-logo text-2xl"></i>
                            </a>
                            <!-- LinkedIn -->
                            <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ $shareUrl }}&title={{ $shareTitle }}" target="_blank"
                                class="w-12 h-12 rounded-full bg-[#0A66C2] text-white flex items-center justify-center hover:scale-110 transition-transform shadow-sm">
                                <i class="ph-fill ph-linkedin-logo text-2xl"></i>
                            </a>
                            <!-- Telegram -->
                            <a href="https://t.me/share/url?url={{ $shareUrl }}&text={{ $shareTitle }}" target="_blank"
                                class="w-12 h-12 rounded-full bg-[#0088CC] text-white flex items-center justify-center hover:scale-110 transition-transform shadow-sm">
                                <i class="ph-fill ph-telegram-logo text-2xl"></i>
                            </a>
                            <!-- Twitter / X -->
                            <a href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $shareTitle }}" target="_blank"
                                class="w-12 h-12 rounded-full bg-[#1DA1F2] text-white flex items-center justify-center hover:scale-110 transition-transform shadow-sm">
                                <i class="ph-fill ph-twitter-logo text-2xl"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Native Share API Button -->
                    <div class="pt-2 flex justify-center">
                        <button type="button" 
                            onclick="if(navigator.share) { navigator.share({title: '{{ addslashes($product->title) }}', url: '{{ url('/product/' . $product->slug) }}'}) } else { window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Native sharing is not supported on this browser.', type: 'warning' } })) }"
                            class="px-6 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-full transition-colors flex items-center justify-center gap-2 w-full sm:w-auto mx-auto shadow-md hover:shadow-lg cursor-pointer">
                            <i class="ph ph-dots-three text-lg"></i> More Options
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
