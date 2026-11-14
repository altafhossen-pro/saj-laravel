@extends('layouts.admin')

@section('title', 'Purge Cache - Admin Panel')
@section('header_title', 'Cache Manager')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <!-- Back Button & Page Title -->
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.settings.index') }}"
               class="w-10 h-10 rounded-full bg-white shadow-sm flex items-center justify-center text-gray-500 hover:text-brand-600 hover:shadow transition-all duration-300 border border-gray-100">
                <i class="ph ph-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Purge Cache & Sitemap Manager</h1>
                <p class="text-sm text-gray-500 mt-0.5">Control live XML sitemaps, compiled Blade views, and application memory caches.</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-white border border-gray-200 text-gray-700 shadow-xs">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Host: <strong class="text-gray-900">{{ $currentHost }}</strong></span>
            </span>
        </div>
    </div>

    <!-- Flash Notification Alerts -->
    @if(session('success'))
    <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center gap-3 text-emerald-800 text-sm shadow-xs">
        <i class="ph-fill ph-check-circle text-emerald-600 text-xl shrink-0"></i>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    <!-- Main Card: XML Sitemap Cache Control -->
    <div class="bg-white rounded-[2rem] p-6 sm:p-8 shadow-sm border border-gray-100 relative overflow-hidden space-y-6">
        <div class="absolute top-0 left-0 right-0 h-2 bg-gradient-to-r from-amber-400 via-brand-500 to-rose-500"></div>

        <div class="flex items-start justify-between flex-wrap gap-4 pt-1 pb-4 border-b border-gray-100">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-2xl shadow-xs">
                    <i class="ph-bold ph-tree-structure"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Google XML Sitemap Cache</h2>
                    <p class="text-xs text-gray-500">Includes master index, Buying Guides, Products, Categories, and static pages.</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                @if($isSitemapCached)
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <i class="ph-fill ph-check-circle"></i>
                    <span>Currently Cached</span>
                </span>
                @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                    <i class="ph-bold ph-hourglass-medium"></i>
                    <span>Purged / Generating Fresh</span>
                </span>
                @endif
            </div>
        </div>

        <!-- Metrics Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Last Cached At -->
            <div class="p-4 rounded-2xl bg-gray-50/80 border border-gray-100 space-y-1">
                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">
                    <i class="ph ph-clock mr-1"></i> Last Cached At
                </span>
                <p class="text-sm font-black text-gray-900">
                    {{ $lastSitemapCachedAt ? \Carbon\Carbon::parse($lastSitemapCachedAt)->format('d M Y, h:i A') : 'Not Cached / Cleared' }}
                </p>
                <p class="text-xs text-gray-500">
                    {{ $lastSitemapCachedAt ? \Carbon\Carbon::parse($lastSitemapCachedAt)->diffForHumans() : 'Will build automatically on request' }}
                </p>
            </div>

            <!-- Cached Domain / Target Host -->
            <div class="p-4 rounded-2xl bg-gray-50/80 border border-gray-100 space-y-1">
                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">
                    <i class="ph ph-globe mr-1"></i> Generated For Host
                </span>
                <p class="text-sm font-black text-brand-600 truncate">
                    {{ $lastSitemapCachedHost ?: $currentHost }}
                </p>
                <p class="text-xs text-gray-500">
                    Scoped per environment (No domain mix)
                </p>
            </div>

            <!-- Last Purged Info -->
            <div class="p-4 rounded-2xl bg-gray-50/80 border border-gray-100 space-y-1">
                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">
                    <i class="ph ph-broom mr-1"></i> Last Purged At
                </span>
                <p class="text-sm font-black text-gray-900">
                    {{ $lastSitemapPurgedAt ? \Carbon\Carbon::parse($lastSitemapPurgedAt)->format('d M Y, h:i A') : 'Never Manually Purged' }}
                </p>
                <p class="text-xs text-gray-500">
                    {{ $lastSitemapPurgedBy ? 'By ' . $lastSitemapPurgedBy : 'Cache lifetime: 1 hour' }}
                </p>
            </div>
        </div>

        <!-- Purge Button & Information -->
        <div class="flex items-center justify-between flex-wrap gap-4 pt-2">
            <p class="text-xs text-gray-500 max-w-xl">
                When you add new products or publish new buying guides, clicking <strong>Purge Sitemap Cache</strong> instantly flushes cached XML files across all sub-sitemaps. Googlebot and browsers will receive 100% fresh URLs on their next request.
            </p>

            <form action="{{ route('admin.settings.cache.purge-sitemap') }}" method="POST"
                  x-data="{ loading: false }"
                  @submit="loading = true">
                @csrf
                <button type="submit"
                        :disabled="loading"
                        class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 active:bg-amber-700 text-white font-bold text-xs shadow-sm hover:shadow transition-all duration-200 inline-flex items-center gap-2 cursor-pointer disabled:opacity-50">
                    <template x-if="!loading">
                        <i class="ph-bold ph-broom text-base"></i>
                    </template>
                    <template x-if="loading">
                        <i class="ph ph-spinner animate-spin text-base"></i>
                    </template>
                    <span x-text="loading ? 'Purging Sitemap...' : 'Purge Sitemap Cache Now'"></span>
                </button>
            </form>
        </div>

        <!-- Live Sub-Sitemaps Quick Inspection Links -->
        <div class="pt-4 border-t border-gray-100 space-y-3">
            <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider">Inspect Live Sitemaps in Browser</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5">
                @foreach($sitemapLinks as $link)
                <a href="{{ $link['path'] }}" target="_blank"
                   class="flex items-center justify-between p-3 rounded-xl bg-gray-50 hover:bg-brand-50/60 border border-gray-200/70 hover:border-brand-200 text-gray-700 hover:text-brand-700 transition-colors group">
                    <div class="min-w-0 pr-2">
                        <p class="text-xs font-bold truncate">{{ $link['name'] }}</p>
                        <p class="text-[11px] text-gray-400 group-hover:text-brand-500 font-mono truncate">{{ $link['path'] }}</p>
                    </div>
                    <i class="ph-bold ph-arrow-square-out text-sm text-gray-400 group-hover:text-brand-600 shrink-0"></i>
                </a>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Secondary Caches: Blade Views & App Memory -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Blade Views Card -->
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 space-y-4 flex flex-col justify-between">
            <div class="space-y-2">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl shrink-0">
                        <i class="ph-bold ph-paint-brush-broad"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900">Compiled Blade Views</h3>
                        <p class="text-xs text-gray-400">storage/framework/views</p>
                    </div>
                </div>
                <p class="text-xs text-gray-500 leading-relaxed">
                    Clears all compiled Blade templates. Use this if you edited frontend views, stylesheets, or components and the changes don't appear immediately.
                </p>
            </div>

            <form action="{{ route('admin.settings.cache.purge-views') }}" method="POST"
                  x-data="{ loading: false }"
                  @submit="loading = true">
                @csrf
                <button type="submit"
                        :disabled="loading"
                        class="w-full py-2.5 px-4 rounded-xl border border-gray-200 hover:border-purple-300 hover:bg-purple-50/50 text-gray-700 hover:text-purple-700 font-bold text-xs transition-colors inline-flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50">
                    <template x-if="!loading"><i class="ph-bold ph-trash text-sm"></i></template>
                    <template x-if="loading"><i class="ph ph-spinner animate-spin text-sm"></i></template>
                    <span x-text="loading ? 'Clearing Views...' : 'Purge Blade Views Cache'"></span>
                </button>
            </form>
        </div>

        <!-- Full Application Cache Card -->
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 space-y-4 flex flex-col justify-between">
            <div class="space-y-2">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl shrink-0">
                        <i class="ph-bold ph-fire"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900">Entire Application Cache</h3>
                        <p class="text-xs text-gray-400">Database & Redis Memory</p>
                    </div>
                </div>
                <p class="text-xs text-gray-500 leading-relaxed">
                    Flushes all application caches including homepage sections, category offers, settings cache, and sitemaps.
                </p>
            </div>

            <form action="{{ route('admin.settings.cache.purge-all') }}" method="POST"
                  x-data="{ loading: false }"
                  @submit="loading = true">
                @csrf
                <button type="submit"
                        :disabled="loading"
                        class="w-full py-2.5 px-4 rounded-xl border border-gray-200 hover:border-rose-300 hover:bg-rose-50/50 text-gray-700 hover:text-rose-700 font-bold text-xs transition-colors inline-flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50">
                    <template x-if="!loading"><i class="ph-bold ph-broom text-sm"></i></template>
                    <template x-if="loading"><i class="ph ph-spinner animate-spin text-sm"></i></template>
                    <span x-text="loading ? 'Purging Everything...' : 'Purge Entire Application Cache'"></span>
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
