<!-- Social Share Component -->
<div class="flex items-center gap-2 flex-wrap" x-data="{
    copied: false,
    copyLink() {
        navigator.clipboard.writeText(window.location.href);
        this.copied = true;
        setTimeout(() => this.copied = false, 2500);
    }
}">
    <span class="text-xs font-bold uppercase tracking-wider text-gray-400 mr-1 select-none">Share Guide:</span>

    <!-- Facebook -->
    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}"
       target="_blank" rel="noopener noreferrer"
       class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white flex items-center justify-center transition-colors shadow-2xs"
       title="Share on Facebook">
        <i class="ph-bold ph-facebook-logo text-sm"></i>
    </a>

    <!-- WhatsApp -->
    <a href="https://api.whatsapp.com/send?text={{ urlencode($guide->title . ' ' . url()->current()) }}"
       target="_blank" rel="noopener noreferrer"
       class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white flex items-center justify-center transition-colors shadow-2xs"
       title="Share on WhatsApp">
        <i class="ph-bold ph-whatsapp-logo text-sm"></i>
    </a>

    <!-- Twitter / X -->
    <a href="https://twitter.com/intent/tweet?text={{ urlencode($guide->title) }}&url={{ urlencode(url()->current()) }}"
       target="_blank" rel="noopener noreferrer"
       class="w-8 h-8 rounded-full bg-gray-100 text-gray-800 hover:bg-black hover:text-white flex items-center justify-center transition-colors shadow-2xs"
       title="Share on X">
        <i class="ph-bold ph-x-logo text-sm"></i>
    </a>

    <!-- Copy Link -->
    <button type="button"
            @click="copyLink()"
            class="px-3 py-1.5 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold flex items-center gap-1.5 transition-colors cursor-pointer select-none"
            title="Copy Article Link">
        <i class="ph-bold" :class="copied ? 'ph-check text-emerald-600' : 'ph-link'"></i>
        <span x-text="copied ? 'Copied!' : 'Copy Link'"></span>
    </button>
</div>
