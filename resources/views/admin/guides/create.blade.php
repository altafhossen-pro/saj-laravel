@extends('layouts.admin')

@section('header_title', 'Create Buying Guide')

@section('content')
<div x-data="{
    title: '{{ addslashes(old('title', '')) }}',
    slug: '{{ addslashes(old('slug', '')) }}',
    isEditingSlug: false,
    imagePreview: null,
    generateSlug(text) {
        return text.toString().toLowerCase().trim()
            .replace(/[\s\W-]+/g, '-')
            .replace(/^-+|-+$/g, '');
    },
    handleImagePreview(event) {
        const file = event.target.files[0];
        if (file) {
            this.imagePreview = URL.createObjectURL(file);
        }
    }
}" class="max-w-5xl mx-auto space-y-6">
    <!-- Top Action Bar -->
    <div class="flex items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-gray-100 shadow-xs">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.guides.index') }}" class="text-xs font-semibold text-gray-500 hover:text-brand-600 transition-colors">
                    ← Back to All Guides
                </a>
            </div>
            <h1 class="text-xl font-bold text-gray-900 mt-1">Create New Buying Guide</h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.guides.index') }}"
               class="px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                Cancel
            </a>
            <button type="submit"
                    form="guide-form"
                    class="px-5 py-2 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-xl shadow-xs transition-all cursor-pointer">
                Save & Publish
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

<x-tinymce-config />
@endsection
