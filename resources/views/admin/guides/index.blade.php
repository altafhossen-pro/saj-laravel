@extends('layouts.admin')

@section('header_title', 'Buying Guides')

@section('content')
<div class="space-y-6" x-data="{
    async togglePublish(url, event) {
        const checkbox = event.target;
        try {
            const res = await fetch(url, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (!data.success) {
                checkbox.checked = !checkbox.checked;
                alert('Failed to update status');
            }
        } catch (e) {
            checkbox.checked = !checkbox.checked;
            alert('Network error occurred.');
        }
    }
}">
    <!-- Top Action Banner -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-gray-100 shadow-xs">
        <div>
            <h1 class="text-xl font-bold text-gray-900 flex items-center gap-2.5">
                <i class="ph-bold ph-book-open-text text-brand-600 text-2xl"></i>
                <span>Buying Guides & Articles</span>
            </h1>
            <p class="text-xs text-gray-500 mt-1">
                Manage SEO-optimized buying guides, expert product recommendations, and editorial articles.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 w-full sm:w-auto">
            <!-- Copy Master Prompt Button -->
            <button type="button" onclick="copyBuyingGuideMasterPrompt(this)"
                class="px-4 py-2.5 bg-white border border-gray-200 text-gray-700 rounded-xl text-sm font-medium hover:bg-gray-50 transition-colors shadow-xs flex items-center gap-2 cursor-pointer"
                title="Copy Master Prompt for ChatGPT/Claude">
                <i class="ph-bold ph-copy text-gray-500 text-base"></i>
                <span>Copy Prompt</span>
            </button>

            <!-- Quick Paste / Import Button -->
            <button type="button" @click="$dispatch('open-quick-import-modal')"
               class="px-4 py-2.5 bg-indigo-50 border border-indigo-200 text-indigo-700 rounded-xl text-sm font-medium hover:bg-indigo-100 transition-colors shadow-xs flex items-center gap-2 cursor-pointer"
               title="Paste AI JSON/Markdown Output">
                <i class="ph-bold ph-clipboard-text text-indigo-600 text-base"></i>
                <span>Quick Import</span>
            </button>

            <!-- AI Generate Guide Button -->
            <button type="button" @click="$dispatch('open-ai-guide-modal')"
               class="px-4 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl text-sm font-medium transition-all shadow-xs flex items-center gap-2 cursor-pointer transform hover:-translate-y-0.5">
                <i class="ph-bold ph-sparkle text-base"></i>
                <span>AI Generate</span>
            </button>

            <!-- Public Guides Link -->
            <a href="{{ route('guides.index') }}" target="_blank"
               class="px-4 py-2.5 bg-white border border-gray-200 text-gray-700 rounded-xl text-sm font-medium hover:bg-gray-50 transition-colors shadow-xs flex items-center gap-2">
                <i class="ph ph-arrow-square-out text-base"></i>
                <span>Public Guides</span>
            </a>

            <!-- Create Guide Button -->
            <a href="{{ route('admin.guides.create') }}"
               class="px-5 py-2.5 bg-gradient-to-r from-brand-600 to-brand-500 text-white rounded-xl text-sm font-medium hover:from-brand-700 hover:to-brand-600 transition-all duration-300 shadow-xs flex items-center gap-2 transform hover:-translate-y-0.5">
                <i class="ph-bold ph-plus-circle text-base"></i>
                <span>Create Guide</span>
            </a>
        </div>
    </div>

    <!-- AI Buying Guide Modal -->
    @include('admin.guides.partials._ai_modal')

    <!-- Quick Import / Paste Modal -->
    @include('admin.guides.partials._quick_import_modal')

    <!-- Quick Stats Cards Component -->
    @include('admin.guides.partials._stats')

    @if(session('success'))
    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center gap-2">
        <i class="ph-bold ph-check-circle text-base text-emerald-600"></i>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    <!-- Filters & Search Toolbar Component -->
    @include('admin.guides.partials._filters')

    <!-- Guides Table Component -->
    @include('admin.guides.partials._table')
</div>
@endsection
