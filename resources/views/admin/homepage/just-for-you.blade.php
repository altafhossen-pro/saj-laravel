@extends('layouts.admin')

@section('title', 'Just For You Settings')
@section('header_title', 'Just For You')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-900">Just For You Settings</h1>
    <p class="text-sm text-gray-500 mt-1">Configure how products are displayed in the Just For You section.</p>
</div>

<div class="max-w-3xl bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    @if(session('success'))
        <div class="p-4 bg-green-50 text-green-700 border-b border-green-100 flex items-center gap-2">
            <i class="ph-fill ph-check-circle text-lg"></i>
            <span class="font-medium text-sm">{{ session('success') }}</span>
        </div>
    @endif

    <form action="{{ route('admin.homepage.just-for-you.save') }}" method="POST" class="p-6">
        @csrf
        
        <div class="space-y-6">
            <!-- Product Display Type -->
            <div>
                <label class="block text-sm font-medium text-gray-900 mb-2">Display Type</label>
                <div class="space-y-3">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="radio" name="type" value="latest" class="mt-0.5 text-brand-600 focus:ring-brand-500" {{ $type === 'latest' ? 'checked' : '' }}>
                        <div>
                            <span class="block text-sm font-medium text-gray-900">Latest Products</span>
                            <span class="block text-sm text-gray-500">Show newly created products first (Ordered by latest).</span>
                        </div>
                    </label>
                    
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="radio" name="type" value="random" class="mt-0.5 text-brand-600 focus:ring-brand-500" {{ $type === 'random' ? 'checked' : '' }}>
                        <div>
                            <span class="block text-sm font-medium text-gray-900">Random Products</span>
                            <span class="block text-sm text-gray-500">Show products completely randomly each time.</span>
                        </div>
                    </label>
                </div>
            </div>

            <hr class="border-gray-100">

            <!-- Category Priorities -->
            <div>
                <label class="block text-sm font-medium text-gray-900 mb-1">Priority Categories (Optional)</label>
                <p class="text-sm text-gray-500 mb-3">Products from these categories will have a higher chance of showing up first in the Just For You section.</p>
                
                <div class="max-h-60 overflow-y-auto border border-gray-200 rounded-lg p-3 bg-gray-50/50 space-y-2">
                    @forelse($categories as $category)
                        <label class="flex items-center gap-2 cursor-pointer p-2 hover:bg-white rounded border border-transparent hover:border-gray-200 hover:shadow-sm transition-all">
                            <input type="checkbox" name="priority_categories[]" value="{{ $category->id }}" class="text-brand-600 rounded focus:ring-brand-500" {{ in_array($category->id, $priorityCategories) ? 'checked' : '' }}>
                            <span class="text-sm text-gray-800">{{ $category->name }}</span>
                        </label>
                    @empty
                        <div class="text-sm text-gray-500 p-2 text-center">No categories found.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="mt-8 pt-5 border-t border-gray-100 flex justify-end">
            <button type="submit" class="px-6 py-2.5 bg-brand-600 text-white rounded-lg font-medium hover:bg-brand-700 focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 transition-colors cursor-pointer flex items-center gap-2">
                <i class="ph ph-floppy-disk text-lg"></i> Save Settings
            </button>
        </div>
    </form>
</div>
@endsection
