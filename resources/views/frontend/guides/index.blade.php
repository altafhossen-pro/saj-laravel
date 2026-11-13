@extends('layouts.app')

@section('title', $seo['title'])
@section('canonical', $seo['canonical'])

@section('meta')
    <meta name="description" content="{{ $seo['description'] }}">
    <meta name="keywords" content="{{ $seo['keywords'] }}">
    <meta property="og:title" content="{{ $seo['title'] }}">
    <meta property="og:description" content="{{ $seo['description'] }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $seo['canonical'] }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seo['title'] }}">
    <meta name="twitter:description" content="{{ $seo['description'] }}">
@endsection

@section('content')
<main class="min-h-screen bg-gray-50/60 px-4 lg:px-8 py-6 pb-20">
    <!-- Header: Title, Breadcrumbs & Search -->
    <div class="mb-6 pb-5 border-b border-gray-200/70 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">
                Buying Guides
            </h1>
            <div class="mt-1">
                @include('frontend.guides.partials._breadcrumbs')
            </div>
        </div>

        <!-- Search Option -->
        <div class="w-full md:w-80">
            <form method="GET" action="{{ route('guides.index') }}" class="relative">
                @if(request('topic'))
                    <input type="hidden" name="topic" value="{{ request('topic') }}">
                @endif
                <i class="ph ph-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-base"></i>
                <input type="text"
                       name="q"
                       value="{{ request('q') }}"
                       placeholder="Search guides..."
                       class="w-full pl-10 pr-20 py-2.5 text-xs bg-white border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all shadow-2xs">
                <button type="submit"
                        class="absolute right-1.5 top-1/2 -translate-y-1/2 px-3.5 py-1 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold rounded-lg transition-colors cursor-pointer">
                    Search
                </button>
            </form>
        </div>
    </div>

    <!-- Topic Filter Pills -->
    @if($topics->count() > 0)
    <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none mb-6">
        <a href="{{ route('guides.index', request()->only('q')) }}"
           class="px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0 cursor-pointer {{ !request('topic') ? 'bg-brand-600 text-white shadow-xs' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200' }}">
            All Guides
        </a>
        @foreach($topics as $t)
            <a href="{{ route('guides.index', array_merge(request()->only('q'), ['topic' => $t])) }}"
               class="px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0 cursor-pointer {{ request('topic') === $t ? 'bg-brand-600 text-white shadow-xs' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200' }}">
                {{ $t }}
            </a>
        @endforeach
    </div>
    @endif

    <!-- Guides 4-Column Grid with Top Margin -->
    <div class="mt-6">
        @if($guides->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @foreach($guides as $guide)
                @include('frontend.guides.partials._card', ['guide' => $guide])
            @endforeach
        </div>

        <!-- Pagination -->
        @if($guides->hasPages())
        <div class="mt-10 flex justify-center">
            {{ $guides->links() }}
        </div>
        @endif

        @else
        <!-- Empty State -->
        <div class="bg-white rounded-3xl border border-gray-200 p-12 text-center max-w-md mx-auto space-y-3">
            <div class="w-16 h-16 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center text-3xl mx-auto">
                <i class="ph ph-magnifying-glass"></i>
            </div>
            <h3 class="text-base font-bold text-gray-900">No guides found</h3>
            <p class="text-xs text-gray-500">
                We couldn't find any buying guides matching your criteria. Try adjusting your search query or topic filter.
            </p>
            <div class="pt-2">
                <a href="{{ route('guides.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-900 text-white text-xs font-bold rounded-xl hover:bg-brand-600 transition-colors">
                    <span>View All Guides</span>
                </a>
            </div>
        </div>
        @endif
    </div>
</main>
@endsection
