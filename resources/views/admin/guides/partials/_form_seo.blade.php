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
        <div class="p-4 rounded-xl border border-gray-200 bg-white shadow-2xs flex items-center justify-between gap-4 font-sans">
            <div class="space-y-1 flex-1">
                <div class="flex items-center gap-1.5 text-xs text-gray-600">
                    <span class="font-medium text-gray-800">{{ url('/') }}</span>
                    <span class="text-gray-400">› guides › <span x-text="slug || 'article-slug'"></span></span>
                </div>
                <h3 class="text-base text-blue-700 hover:underline font-medium cursor-pointer line-clamp-1 leading-snug"
                    x-text="effectiveTitle"></h3>
                <p class="text-xs text-gray-600 line-clamp-2 leading-relaxed"
                   x-text="effectiveDesc"></p>
            </div>
            <div class="w-20 h-20 rounded-xl bg-gray-100 border border-gray-200 overflow-hidden shrink-0 flex items-center justify-center">
                <template x-if="imagePreview">
                    <img :src="imagePreview" class="w-full h-full object-cover">
                </template>
                <template x-if="!imagePreview">
                    <i class="ph ph-image text-gray-300 text-2xl"></i>
                </template>
            </div>
        </div>
    </div>

    <!-- Dedicated Google Search & Social Media Thumbnail (OG Image) -->
    <div x-data="{
        ogPreview: '{{ !empty($guide->og_image) ? $guide->og_image_url : '' }}',
        handleOgPreview(event) {
            const file = event.target.files[0];
            if (file) {
                this.ogPreview = URL.createObjectURL(file);
            }
        }
    }">
        <div class="flex items-center justify-between mb-1.5">
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                Dedicated Google Search & Social Thumbnail (1200×630)
            </label>
            <span class="text-[11px] font-semibold text-gray-400">Recommended: 1200×630 px (16:9)</span>
        </div>
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 p-4 rounded-xl border border-gray-200 bg-gray-50/60">
            <div class="w-28 h-16 rounded-lg bg-gray-200 border border-gray-300 overflow-hidden flex items-center justify-center shrink-0">
                <template x-if="ogPreview">
                    <img :src="ogPreview" class="w-full h-full object-cover">
                </template>
                <template x-if="!ogPreview">
                    <div class="text-center text-gray-400">
                        <i class="ph ph-image text-2xl"></i>
                    </div>
                </template>
            </div>
            <div class="space-y-1.5 flex-1">
                <input type="file"
                       name="og_image"
                       accept="image/*"
                       @change="handleOgPreview($event)"
                       class="text-xs text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 cursor-pointer">
                <p class="text-[11px] text-gray-500 leading-relaxed">
                    আর্টিকেলের মূল ছবির বাইরে <strong>গুগল সার্চ স্নsnippet ও সোশ্যাল শেয়ারের জন্য নির্দিষ্ট থাম্বনেইল ছবি</strong> দিতে চাইলে এখানে আপলোড করুন। খালি রাখলে স্বয়ংক্রিয়ভাবে আর্টিকেলের ফিচারড ইমেজটি গুগল থাম্বনেইল হিসেবে ব্যবহার হবে।
                </p>
                @if(!empty($guide?->og_image))
                <label class="inline-flex items-center gap-1.5 text-xs text-rose-600 font-semibold cursor-pointer pt-1">
                    <input type="checkbox" name="remove_og_image" value="1" class="rounded text-rose-600">
                    <span>Remove dedicated SEO image (revert to featured image)</span>
                </label>
                @endif
            </div>
        </div>
        @error('og_image')
            <p class="mt-1 text-xs text-rose-500 font-semibold">{{ $message }}</p>
        @enderror
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
