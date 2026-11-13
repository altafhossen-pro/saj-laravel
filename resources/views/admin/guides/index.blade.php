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

        <div class="flex items-center gap-3 w-full sm:w-auto">
            <a href="{{ route('guides.index') }}" target="_blank"
               class="inline-flex items-center justify-center gap-2 px-3.5 py-2 text-xs font-semibold text-gray-700 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-xl transition-colors">
                <i class="ph ph-arrow-square-out text-sm"></i>
                <span>View Public Guides</span>
            </a>
            <a href="{{ route('admin.guides.create') }}"
               class="inline-flex items-center justify-center gap-2 px-4 py-2 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-xl shadow-xs hover:shadow transition-all">
                <i class="ph-bold ph-plus text-sm"></i>
                <span>Create Guide</span>
            </a>
        </div>
    </div>

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
