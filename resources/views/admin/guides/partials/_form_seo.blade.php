<!-- SEO & Meta Configuration Component -->
<div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-xs space-y-5"
     x-data="{
        metaTitle: '{{ addslashes(old('meta_title', $guide->meta_title ?? '')) }}',
        metaDescription: '{{ addslashes(old('meta_description', $guide->meta_description ?? '')) }}',
        get effectiveTitle() {
            return this.metaTitle || title || 'Article Title Placeholder';
        },
        get effectiveDesc() {
            return this.metaDescription || 'Detailed buying guide and review explaining everything you need to know before buying in Bangladesh.';
        }
     }">
    <div class="flex items-center justify-between pb-3 border-b border-gray-100">
        <div class="flex items-center gap-2">
            <i class="ph-bold ph-magnifying-glass text-brand-600 text-lg"></i>
            <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wider">SEO & Metadata (Google Ranking)</h2>
        </div>
        <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
            100% SEO Ready
        </span>
    </div>

    <!-- Google Search Snippet Live Preview -->
    <div>
        <span class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Google Search Live Preview</span>
        <div class="p-4 rounded-xl border border-gray-200 bg-white shadow-2xs space-y-1 font-sans">
            <div class="flex items-center gap-1.5 text-xs text-gray-600">
                <span class="font-medium text-gray-800">{{ url('/') }}</span>
                <span class="text-gray-400">› guides › <span x-text="slug || 'article-slug'"></span></span>
            </div>
            <h3 class="text-base text-blue-700 hover:underline font-medium cursor-pointer line-clamp-1 leading-snug"
                x-text="effectiveTitle"></h3>
            <p class="text-xs text-gray-600 line-clamp-2 leading-relaxed"
               x-text="effectiveDesc"></p>
        </div>
    </div>

    <!-- Meta Title -->
    <div>
        <div class="flex items-center justify-between mb-1.5">
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                Meta Title
            </label>
            <span class="text-[11px] font-semibold"
                  :class="metaTitle.length > 60 ? 'text-rose-500' : 'text-gray-400'"
                  x-text="`${metaTitle.length}/60 chars (Recommended: 50-60)`"></span>
        </div>
        <input type="text"
               name="meta_title"
               x-model="metaTitle"
               placeholder="Leave empty to use main article title"
               class="w-full px-4 py-2.5 text-xs border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500 bg-white text-gray-800">
        @error('meta_title')
            <p class="mt-1 text-xs text-rose-500 font-semibold">{{ $message }}</p>
        @enderror
    </div>

    <!-- Meta Description -->
    <div>
        <div class="flex items-center justify-between mb-1.5">
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                Meta Description
            </label>
            <span class="text-[11px] font-semibold"
                  :class="metaDescription.length > 160 ? 'text-rose-500' : 'text-gray-400'"
                  x-text="`${metaDescription.length}/160 chars (Recommended: 120-160)`"></span>
        </div>
        <textarea name="meta_description"
                  x-model="metaDescription"
                  rows="3"
                  placeholder="Leave empty to automatically use excerpt or first paragraph"
                  class="w-full px-4 py-2.5 text-xs border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500 bg-white text-gray-800"></textarea>
        @error('meta_description')
            <p class="mt-1 text-xs text-rose-500 font-semibold">{{ $message }}</p>
        @enderror
    </div>

    <!-- Meta Keywords & Canonical URL -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                Meta Keywords
            </label>
            <input type="text"
                   name="meta_keywords"
                   value="{{ old('meta_keywords', $guide->meta_keywords ?? '') }}"
                   placeholder="skincare guide, best body oil, moisturizer review"
                   class="w-full px-4 py-2 text-xs border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500 bg-white text-gray-800">
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                Canonical URL (Optional)
            </label>
            <input type="url"
                   name="canonical_url"
                   value="{{ old('canonical_url', $guide->canonical_url ?? '') }}"
                   placeholder="https://yoursite.com/guides/slug"
                   class="w-full px-4 py-2 text-xs border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500 bg-white text-gray-800">
        </div>
    </div>
</div>
