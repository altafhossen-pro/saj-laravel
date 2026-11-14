@extends('layouts.admin')

@section('header_title', 'Create Buying Guide')

@section('content')
<div x-data="guideCreateForm()" @guide-autofill.window="applyGuideData($event.detail)" class="max-w-5xl mx-auto space-y-6">
    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.guides.index') }}" class="text-xs font-semibold text-gray-500 hover:text-brand-600 transition-colors">
                    ← Back to All Guides
                </a>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">Create New Buying Guide</h1>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <!-- Copy Master Prompt -->
            <button type="button" onclick="copyBuyingGuideMasterPrompt(this)"
                class="px-4 py-2.5 bg-white border border-gray-200 text-gray-700 rounded-xl text-sm font-medium hover:bg-gray-50 transition-colors shadow-xs flex items-center gap-2 cursor-pointer"
                title="Copy Master Prompt for ChatGPT/Claude">
                <i class="ph-bold ph-copy text-gray-500 text-base"></i>
                <span>Copy Prompt</span>
            </button>

            <!-- Quick Paste / Autofill -->
            <button type="button" @click="$dispatch('open-quick-import-modal')"
               class="px-4 py-2.5 bg-indigo-50 border border-indigo-200 text-indigo-700 rounded-xl text-sm font-medium hover:bg-indigo-100 transition-colors shadow-xs flex items-center gap-2 cursor-pointer">
                <i class="ph-bold ph-clipboard-text text-indigo-600 text-base"></i>
                <span>Quick Import</span>
            </button>

            <a href="{{ route('admin.guides.index') }}"
               class="px-4 py-2.5 bg-white border border-gray-200 text-gray-700 rounded-xl text-sm font-medium hover:bg-gray-50 transition-colors shadow-xs flex items-center gap-2">
                Cancel
            </a>
            <button type="submit"
                    form="guide-form"
                    class="px-5 py-2.5 bg-gradient-to-r from-brand-600 to-brand-500 text-white rounded-xl text-sm font-medium hover:from-brand-700 hover:to-brand-600 transition-all duration-300 shadow-xs flex items-center gap-2 transform hover:-translate-y-0.5 cursor-pointer">
                <i class="ph-bold ph-check text-base"></i>
                <span>Save & Publish</span>
            </button>
        </div>
    </div>

    <!-- AI Quick Assistant Bar -->
    <div class="p-4.5 rounded-2xl bg-gradient-to-r from-purple-50 via-indigo-50/50 to-white border border-purple-200/80 flex flex-wrap items-center justify-between gap-3 shadow-xs">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-purple-600 text-white flex items-center justify-center text-lg shadow-xs shrink-0">
                <i class="ph-bold ph-sparkle"></i>
            </div>
            <div>
                <h4 class="text-xs font-bold text-purple-950 uppercase tracking-wider">AI Buying Guide Assistant</h4>
                <p class="text-xs text-purple-800 mt-0.5">
                    ChatGPT বা Claude ব্যবহার করে সম্পূর্ণ গাইড তৈরি করতে চান? 
                    প্রম্পট কপি করে টপিক বলুন, তারপর রেসপন্স এনে কুইক পেস্ট করুন — সাথে সাথে সব ফিল্ড পূরণ হয়ে যাবে!
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" onclick="copyBuyingGuideMasterPrompt(this)"
                class="px-3.5 py-2 rounded-xl text-xs font-bold text-purple-700 bg-white hover:bg-purple-100/60 border border-purple-200 transition cursor-pointer flex items-center gap-1.5 shadow-2xs">
                <i class="ph-bold ph-copy text-sm"></i>
                <span>Copy Master Prompt</span>
            </button>
            <button type="button" @click="$dispatch('open-quick-import-modal')"
                class="px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-purple-600 hover:bg-purple-700 transition cursor-pointer flex items-center gap-1.5 shadow-xs">
                <i class="ph-bold ph-lightning text-sm"></i>
                <span>Quick Paste Output</span>
            </button>
        </div>
    </div>

    <!-- Main Form -->
    <form id="guide-form"
          method="POST"
          action="{{ route('admin.guides.store') }}"
          enctype="multipart/form-data"
          class="space-y-6">
        @csrf

        @include('admin.guides.partials._form_basic', ['guide' => null])
        @include('admin.guides.partials._form_content', ['guide' => null])
        @include('admin.guides.partials._form_products', ['selectedProducts' => []])
        @include('admin.guides.partials._form_seo', ['guide' => null])

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
            <a href="{{ route('admin.guides.index') }}"
               class="px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                Cancel
            </a>
            <button type="submit"
                    class="px-6 py-2.5 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-xl shadow-xs transition-all cursor-pointer">
                Save & Publish Guide
            </button>
        </div>
    </form>
</div>

<!-- Quick Import / Paste Modal -->
@include('admin.guides.partials._quick_import_modal')

<script>
function guideCreateForm() {
    return {
        title: @json(old('title', '')),
        slug: @json(old('slug', '')),
        isEditingSlug: false,
        imagePreview: null,
        generateSlug(text) {
            return (text || '').toString().toLowerCase().trim()
                .replace(/[\s\W-]+/g, '-')
                .replace(/^-+|-+$/g, '');
        },
        handleImagePreview(event) {
            const file = event.target.files[0];
            if (file) {
                this.imagePreview = URL.createObjectURL(file);
            }
        },
        applyGuideData(data) {
            if (!data) return;
            if (data.title) {
                this.title = data.title;
                this.slug = this.generateSlug(data.title);
            }

            this.$nextTick(() => {
                const topicInput = document.querySelector('input[name="topic"]');
                const excerptInput = document.querySelector('textarea[name="excerpt"]');
                const metaTitleInput = document.querySelector('input[name="meta_title"]');
                const metaDescInput = document.querySelector('textarea[name="meta_description"]');
                const metaKeywordsInput = document.querySelector('input[name="meta_keywords"]');

                if (topicInput && data.topic) {
                    topicInput.value = data.topic;
                    topicInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
                if (excerptInput && data.excerpt) {
                    excerptInput.value = data.excerpt;
                    excerptInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
                if (metaTitleInput && data.meta_title) {
                    metaTitleInput.value = data.meta_title;
                    metaTitleInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
                if (metaDescInput && data.meta_description) {
                    metaDescInput.value = data.meta_description;
                    metaDescInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
                if (metaKeywordsInput && data.meta_keywords) {
                    metaKeywordsInput.value = data.meta_keywords;
                    metaKeywordsInput.dispatchEvent(new Event('input', { bubbles: true }));
                }

                const setTinyMce = (attempts = 0) => {
                    if (window.tinymce) {
                        const editor = tinymce.get('guide-tinymce-editor') || tinymce.activeEditor;
                        if (editor && editor.initialized) {
                            editor.setContent(data.content || '');
                            return;
                        }
                    }
                    if (attempts < 30) {
                        setTimeout(() => setTinyMce(attempts + 1), 150);
                    }
                };
                setTimeout(() => setTinyMce(0), 200);
            });
        },
        init() {
            const stored = sessionStorage.getItem('saj_guide_autofill');
            if (stored) {
                try {
                    const data = JSON.parse(stored);
                    sessionStorage.removeItem('saj_guide_autofill');
                    this.applyGuideData(data);
                } catch (e) {
                    console.error('Failed to parse autofill data', e);
                }
            }
        }
    };
}
</script>

<x-tinymce-config />
@endsection
