<!-- Rich Text Article Content Component -->
<div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-xs space-y-4">
    <div class="flex items-center justify-between pb-3 border-b border-gray-100 flex-wrap gap-2">
        <div class="flex items-center gap-2">
            <i class="ph-bold ph-article text-brand-600 text-lg"></i>
            <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Article Content</h2>
        </div>
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
            <i class="ph-bold ph-lightbulb text-amber-600"></i>
            <span>Format with H1 / H2 / H3 for Auto Table of Contents</span>
        </span>
    </div>

    <!-- SEO & TOC Explanatory Alert -->
    <div class="p-3.5 rounded-xl bg-blue-50/70 border border-blue-100 text-xs text-blue-900 flex items-start gap-2.5">
        <i class="ph-bold ph-info text-blue-600 text-base shrink-0 mt-0.5"></i>
        <div class="space-y-0.5">
            <p class="font-bold">Automatic Table of Contents & Google Jump Links</p>
            <p class="text-blue-800 text-[11px] leading-relaxed">
                When you use <strong>Heading 1 (H1)</strong>, <strong>Heading 2 (H2)</strong>, and <strong>Heading 3 (H3)</strong> in this editor, our system will automatically build an interactive <strong>"In This Article" (সূচিপত্র)</strong> jump menu on the public article page. Readers can click any heading to smoothly scroll down, and Google Search will display site-links directly to those headings.
            </p>
        </div>
    </div>

    <!-- TinyMCE Rich Text Editor -->
    <div>
        <textarea name="content"
                  id="guide-tinymce-editor"
                  class="tinymce-editor"
                  rows="20">{{ old('content', $guide->content ?? '') }}</textarea>
        @error('content')
            <p class="mt-1 text-xs text-rose-500 font-semibold">{{ $message }}</p>
        @enderror
    </div>
</div>
