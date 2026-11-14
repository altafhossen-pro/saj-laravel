@extends('layouts.admin')

@section('header_title', 'Edit Buying Guide')

@section('content')
<div x-data="guideEditForm()" class="max-w-5xl mx-auto space-y-6">
    <!-- Top Action Bar -->
    <div class="flex items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-gray-100 shadow-xs">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.guides.index') }}" class="text-xs font-semibold text-gray-500 hover:text-brand-600 transition-colors">
                    ← Back to All Guides
                </a>
            </div>
            <h1 class="text-xl font-bold text-gray-900 mt-1">Edit: {{ $guide->title }}</h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.guides.preview', $guide->id) }}"
               class="px-3 py-2 text-xs font-bold text-purple-700 bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-xl transition-colors inline-flex items-center gap-1.5"
               title="Preview without publishing">
                <i class="ph-bold ph-eye text-sm"></i>
                <span>Safe Preview</span>
            </a>
            @if($guide->is_published)
            <a href="{{ route('guides.show', $guide->slug) }}" target="_blank"
               class="px-3 py-2 text-xs font-semibold text-gray-700 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-xl transition-colors inline-flex items-center gap-1.5">
                <i class="ph ph-arrow-square-out text-sm"></i>
                <span>View Live</span>
            </a>
            @endif
            <a href="{{ route('admin.guides.index') }}"
               class="px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                Cancel
            </a>
            <button type="submit"
                    form="guide-form"
                    class="px-5 py-2 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-xl shadow-xs transition-all cursor-pointer">
                Save Changes
            </button>
        </div>
    </div>

    <!-- Main Form -->
    <form id="guide-form"
          method="POST"
          action="{{ route('admin.guides.update', $guide) }}"
          enctype="multipart/form-data"
          class="space-y-6">
        @csrf
        @method('PUT')

        @include('admin.guides.partials._form_basic', ['guide' => $guide])
        @include('admin.guides.partials._form_content', ['guide' => $guide])
        @include('admin.guides.partials._form_products', ['selectedProducts' => $selectedProducts])
        @include('admin.guides.partials._form_seo', ['guide' => $guide])

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
            <a href="{{ route('admin.guides.index') }}"
               class="px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                Cancel
            </a>
            <button type="submit"
                    class="px-6 py-2.5 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-xl shadow-xs transition-all cursor-pointer">
                Save Changes
            </button>
        </div>
    </form>
</div>

<script>
function guideEditForm() {
    return {
        title: @json(old('title', $guide->title)),
        slug: @json(old('slug', $guide->slug)),
        isEditingSlug: false,
        imagePreview: @json($guide->featured_image ? $guide->featured_image_url : ''),
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
        }
    };
}
</script>

<x-tinymce-config />
@endsection
