<!-- Basic Information Component -->
<div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-xs space-y-5">
    <div class="flex items-center gap-2 pb-3 border-b border-gray-100">
        <i class="ph-bold ph-note-pencil text-brand-600 text-lg"></i>
        <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Basic Information</h2>
    </div>

    <!-- Title & Slug -->
    <div class="space-y-4">
        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                Article Title <span class="text-rose-500">*</span>
            </label>
            <input type="text"
                   name="title"
                   id="guide-title"
                   x-model="title"
                   @input="if(!isEditingSlug) { slug = generateSlug(title); }"
                   placeholder="e.g. The Ultimate Guide to Winter Skincare: Top Moisturizers & Oils"
                   required
                   class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-white font-medium text-gray-900 transition-all">
            @error('title')
                <p class="mt-1 text-xs text-rose-500 font-semibold">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                    URL Slug <span class="text-rose-500">*</span>
                </label>
                <button type="button"
                        @click="isEditingSlug = !isEditingSlug"
                        class="text-xs font-semibold text-brand-600 hover:text-brand-700 cursor-pointer">
                    <span x-text="isEditingSlug ? 'Lock Slug' : 'Edit Slug Manually'"></span>
                </button>
            </div>
            <div class="flex items-center rounded-xl border border-gray-200 overflow-hidden bg-gray-50 focus-within:ring-2 focus-within:ring-brand-500 focus-within:bg-white transition-all">
                <span class="px-3.5 text-xs text-gray-400 font-mono select-none">/guides/</span>
                <input type="text"
                       name="slug"
                       id="guide-slug"
                       x-model="slug"
                       :readonly="!isEditingSlug"
                       required
                       class="w-full py-2.5 pr-4 text-xs font-mono font-medium text-gray-800 bg-transparent focus:outline-none">
            </div>
            @error('slug')
                <p class="mt-1 text-xs text-rose-500 font-semibold">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <!-- Topic & Category -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                Topic / Category Tag
            </label>
            <input type="text"
                   name="topic"
                   list="topics-list"
                   value="{{ old('topic', $guide->topic ?? '') }}"
                   placeholder="e.g. Skincare, Hair Care, Gadgets"
                   class="w-full px-4 py-2 text-xs border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500 bg-white text-gray-800">
            <datalist id="topics-list">
                @foreach($existingTopics as $tp)
                    <option value="{{ $tp }}">
                @endforeach
            </datalist>
            <p class="mt-1 text-[11px] text-gray-400">Used for topic filters and related article recommendations.</p>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                Publication Status
            </label>
            <div class="flex items-center gap-4 pt-1.5">
                <label class="inline-flex items-center gap-2 text-xs font-semibold text-gray-800 cursor-pointer">
                    <input type="radio" name="is_published" value="1"
                           {{ old('is_published', $guide->is_published ?? true) ? 'checked' : '' }}
                           class="text-brand-600 focus:ring-brand-500">
                    <span>Published (Live)</span>
                </label>
                <label class="inline-flex items-center gap-2 text-xs font-semibold text-gray-500 cursor-pointer">
                    <input type="radio" name="is_published" value="0"
                           {{ !old('is_published', $guide->is_published ?? true) ? 'checked' : '' }}
                           class="text-brand-600 focus:ring-brand-500">
                    <span>Save as Draft</span>
                </label>
            </div>
        </div>
    </div>

    <!-- Excerpt / Short Summary -->
    <div>
        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
            Excerpt / Summary
        </label>
        <textarea name="excerpt"
                  rows="3"
                  placeholder="A concise summary of what readers will learn from this buying guide..."
                  class="w-full px-4 py-2 text-xs border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500 bg-white text-gray-800">{{ old('excerpt', $guide->excerpt ?? '') }}</textarea>
        <p class="mt-1 text-[11px] text-gray-400">Appears on guide cards, search result listings, and social share previews.</p>
    </div>

    <!-- Featured Banner Image -->
    <div>
        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
            Featured Image / Banner
        </label>
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 p-4 rounded-xl border border-gray-200 bg-gray-50/60">
            <div class="w-24 h-16 rounded-lg bg-gray-200 border border-gray-300 overflow-hidden flex items-center justify-center shrink-0">
                <template x-if="imagePreview">
                    <img :src="imagePreview" class="w-full h-full object-cover">
                </template>
                <template x-if="!imagePreview">
                    <div class="text-center text-gray-400">
                        <i class="ph ph-image text-2xl"></i>
                    </div>
                </template>
            </div>
            <div class="space-y-1.5 flex-1">
                <input type="file"
                       name="featured_image"
                       accept="image/*"
                       @change="handleImagePreview($event)"
                       class="text-xs text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 cursor-pointer">
                <p class="text-[11px] text-gray-400">
                    Recommended resolution: 1200x630 px (JPG, PNG, WebP up to 4MB).
                </p>
            </div>
        </div>
        @error('featured_image')
            <p class="mt-1 text-xs text-rose-500 font-semibold">{{ $message }}</p>
        @enderror
    </div>
</div>
