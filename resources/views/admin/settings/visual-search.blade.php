@extends('layouts.admin')

@section('title', 'AI Visual Search Settings - Admin Panel')
@section('header_title', 'AI Visual Search Settings')

@section('content')
<div class="w-full space-y-6" x-data="visualSearchSettings()">
    <!-- Toast Notification -->
    <div x-show="toast.show" 
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 translate-y-4" 
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0" 
         x-transition:leave-end="opacity-0 translate-y-4"
         class="fixed bottom-6 right-6 z-50 flex items-center gap-3 px-5 py-3.5 rounded-xl shadow-xl border text-sm font-medium"
         :class="toast.type === 'success' ? 'bg-gray-900 border-gray-800 text-white' : 'bg-red-900 border-red-800 text-white'"
         style="display: none;">
        <i class="text-xl" :class="toast.type === 'success' ? 'ph-fill ph-check-circle text-green-400' : 'ph-fill ph-warning-circle text-red-400'"></i>
        <span x-text="toast.message"></span>
        <button type="button" @click="toast.show = false" class="ml-2 text-gray-400 hover:text-white transition-colors">
            <i class="ph ph-x"></i>
        </button>
    </div>

    <!-- Header Breadcrumbs / Back button & Top Actions -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.settings.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-brand-600 transition-colors">
            <i class="ph ph-arrow-left text-lg"></i>
            <span>Back to Settings</span>
        </a>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 text-amber-700 font-medium border border-amber-100 text-xs">
                <i class="ph-bold ph-lightning"></i> Cloudflare Workers AI
            </span>
            <button type="button" 
                    @click="document.getElementById('saveAiFormBtn').click()" 
                    :disabled="saving"
                    class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs rounded-xl shadow-sm shadow-brand-500/20 transition flex items-center gap-1.5 cursor-pointer disabled:opacity-60">
                <i x-show="!saving" class="ph-bold ph-floppy-disk text-sm text-white"></i>
                <i x-show="saving" class="ph-bold ph-spinner animate-spin text-sm text-white" style="display: none;"></i>
                <span class="text-white" x-text="saving ? 'Saving...' : 'Save Settings'"></span>
            </button>
        </div>
    </div>

    <!-- Quick Status Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- AI Engine Status -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl"
                     :class="form.enabled ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-400'">
                    <i :class="form.enabled ? 'ph-bold ph-check-circle' : 'ph-bold ph-power'"></i>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">AI Search Engine</p>
                    <h4 class="text-sm font-bold text-gray-800" x-text="form.enabled ? 'Active (Enabled)' : 'Disabled (Local Engine)'"></h4>
                    <p class="text-[11px] text-gray-500" x-text="testStatus.latency !== null ? 'Ping: ' + testStatus.latency + 'ms' : 'Status: Ready'"></p>
                </div>
            </div>
            <span class="w-3 h-3 rounded-full" :class="form.enabled ? 'bg-emerald-500 ring-4 ring-emerald-50' : 'bg-gray-300'"></span>
        </div>

        <!-- Vision Model -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl">
                    <i class="ph-bold ph-brain"></i>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Vision Model</p>
                    <h4 class="text-sm font-bold text-gray-800">Llama 3.2 11B Vision</h4>
                    <p class="text-[11px] text-gray-500">ResNet + BGE Edge GPU</p>
                </div>
            </div>
            <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-md bg-purple-100 text-purple-700">GPU Edge</span>
        </div>

        <!-- AI Vector Indexing Coverage -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                    <i class="ph-bold ph-database"></i>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Catalog Embedded</p>
                    <h4 class="text-sm font-bold text-gray-800">
                        <span x-text="stats.aiIndexed"></span> / <span x-text="stats.totalImages"></span> Images
                    </h4>
                    <p class="text-[11px] text-gray-500" x-text="coveragePercent + '% embedded'"></p>
                </div>
            </div>
            <div class="w-10 h-10 rounded-full border-4 border-blue-500 flex items-center justify-center text-[10px] font-bold text-blue-600"
                 x-text="coveragePercent + '%'"></div>
        </div>

        <!-- Daily AI Quota Usage -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                    <i class="ph-bold ph-gauge"></i>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Today's AI Quota</p>
                    <h4 class="text-sm font-bold text-gray-800">
                        {{ number_format($todayRequests) }} / {{ number_format($dailyQuotaLimit) }}
                    </h4>
                    <p class="text-[11px] text-gray-500">
                        <span class="text-emerald-600 font-semibold">{{ number_format($remainingQuota) }}</span> left today
                    </p>
                </div>
            </div>
            <a href="https://dash.cloudflare.com/{{ $accountId }}/workers-ai" target="_blank" title="View Cloudflare Official Graph" class="px-2 py-1 text-[10px] font-bold uppercase rounded-md bg-amber-100 text-amber-800 hover:bg-amber-200 transition flex items-center gap-1">
                <span>CF Dash</span>
                <i class="ph ph-arrow-square-out text-xs"></i>
            </a>
        </div>
    </div>

    <!-- Main Settings Form & Info Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Configuration Card (2 cols) -->
        <div class="lg:col-span-2 space-y-6">
            <form @submit.prevent="saveSettings()" class="bg-white p-6 sm:p-7 rounded-2xl shadow-sm border border-gray-100 space-y-6">
                @csrf
                <div class="border-b border-gray-100 pb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-gray-900">Cloudflare Workers AI Credentials</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Powers sub-second AI vector embeddings without overloading your server</p>
                    </div>
                    <!-- Master Toggle -->
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="form.enabled" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-600"></div>
                    </label>
                </div>

                <!-- Account ID Input -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Cloudflare Account ID <span class="text-red-500">*</span></label>
                        <a href="https://dash.cloudflare.com" target="_blank" class="text-xs text-brand-600 hover:underline flex items-center gap-1">
                            <span>Open Cloudflare Dash</span>
                            <i class="ph ph-arrow-square-out text-xs"></i>
                        </a>
                    </div>
                    <div class="relative rounded-xl shadow-xs">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                            <i class="ph ph-identification-badge text-lg"></i>
                        </div>
                        <input type="text" 
                               x-model="form.accountId" 
                               placeholder="e.g. 8f6b4d3c2a1e0f9b8a7c6d5e4f3a2b1c"
                               class="block w-full pl-10 pr-4 py-2.5 bg-gray-50/70 border border-gray-200 rounded-xl text-xs font-mono text-gray-900 focus:bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition">
                    </div>
                    <p class="text-[11px] text-gray-400">Found on the right-hand sidebar of any domain in your Cloudflare dashboard.</p>
                </div>

                <!-- API Token Input -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Workers AI API Token <span class="text-red-500">*</span></label>
                        <a href="https://dash.cloudflare.com/profile/api-tokens" target="_blank" class="text-xs text-brand-600 hover:underline flex items-center gap-1">
                            <span>Create Token</span>
                            <i class="ph ph-arrow-square-out text-xs"></i>
                        </a>
                    </div>
                    <div class="relative rounded-xl shadow-xs">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                            <i class="ph ph-key text-lg"></i>
                        </div>
                        <input :type="showToken ? 'text' : 'password'" 
                               x-model="form.apiToken" 
                               placeholder="e.g. abcdefghijklmnopqrstuvwxyz1234567890"
                               class="block w-full pl-10 pr-10 py-2.5 bg-gray-50/70 border border-gray-200 rounded-xl text-xs font-mono text-gray-900 focus:bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition">
                        <button type="button" @click="showToken = !showToken" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 cursor-pointer">
                            <i class="ph" :class="showToken ? 'ph-eye-slash' : 'ph-eye'"></i>
                        </button>
                    </div>
                    <p class="text-[11px] text-gray-400">Create an API Token with <strong>Workers AI: Read/Edit</strong> permission under User Profile &rarr; API Tokens.</p>
                </div>

                <!-- Action Toolbar inside form -->
                <div class="pt-3 border-t border-gray-100 flex items-center justify-between flex-wrap gap-3">
                    <button type="button" 
                            @click="testConnection()" 
                            :disabled="testing || !form.accountId || !form.apiToken"
                            class="px-4 py-2.5 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 text-xs font-semibold shadow-xs transition flex items-center gap-2 cursor-pointer disabled:opacity-50">
                        <i x-show="!testing" class="ph-bold ph-plugs-connected text-brand-600"></i>
                        <i x-show="testing" class="ph-bold ph-spinner animate-spin text-brand-600" style="display: none;"></i>
                        <span x-text="testing ? 'Testing Connection...' : 'Test Connection'"></span>
                    </button>

                    <button type="submit" 
                            id="saveAiFormBtn" 
                            :disabled="saving"
                            class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-md shadow-brand-600/20 transition flex items-center gap-2 cursor-pointer disabled:opacity-50">
                        <i x-show="!saving" class="ph-bold ph-check"></i>
                        <i x-show="saving" class="ph-bold ph-spinner animate-spin" style="display: none;"></i>
                        <span x-text="saving ? 'Saving...' : 'Save Configuration'"></span>
                    </button>
                </div>

                <!-- Test Connection Feedback Result -->
                <template x-if="testStatus.message">
                    <div class="p-4 rounded-xl text-xs font-medium flex items-start gap-2.5 transition animate-in fade-in"
                         :class="testStatus.success ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-red-50 text-red-800 border border-red-200'">
                        <i class="text-base mt-0.5 shrink-0" :class="testStatus.success ? 'ph-fill ph-check-circle text-emerald-600' : 'ph-fill ph-warning-circle text-red-600'"></i>
                        <div class="flex-1">
                            <span class="font-bold" x-text="testStatus.success ? 'Success: ' : 'Failed: '"></span>
                            <span x-text="testStatus.message"></span>
                            <span x-show="testStatus.latency" class="block text-[11px] opacity-80 mt-0.5" x-text="'Response time: ' + testStatus.latency + ' ms'"></span>
                        </div>
                    </div>
                </template>
            </form>

            <!-- Catalog AI Embeddings Sync Card -->
            <div class="bg-white p-6 sm:p-7 rounded-2xl shadow-sm border border-gray-100 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-gray-900">Precompute Catalog AI Embeddings</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Generates and saves 512-dim AI vectors for all products into MySQL for instant sub-second lookup</p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-bold border border-blue-100">
                        <span x-text="stats.aiIndexed"></span> / <span x-text="stats.totalImages"></span> Ready
                    </span>
                </div>

                <!-- Progress Bar -->
                <div class="space-y-1.5">
                    <div class="flex justify-between text-xs text-gray-500 font-medium">
                        <span>Embedding Progress</span>
                        <span x-text="coveragePercent + '%'"></span>
                    </div>
                    <div class="w-full h-2.5 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-blue-500 to-indigo-600 transition-all duration-300"
                             :style="'width: ' + coveragePercent + '%'"></div>
                    </div>
                </div>

                <!-- Sync Action Trigger -->
                <div class="pt-2 flex items-center justify-between flex-wrap gap-3">
                    <p class="text-xs text-gray-500 max-w-sm">
                        Runs in lightweight batches of 10 images with zero server CPU load via Cloudflare's GPU.
                    </p>
                    <button type="button" 
                            @click="startSync()" 
                            :disabled="syncing || !form.accountId || !form.apiToken"
                            class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-md shadow-blue-600/20 transition flex items-center gap-2 cursor-pointer disabled:opacity-50">
                        <i x-show="!syncing" class="ph-bold ph-arrows-clockwise"></i>
                        <i x-show="syncing" class="ph-bold ph-spinner animate-spin" style="display: none;"></i>
                        <span x-text="syncing ? 'Processing AI Embeddings...' : 'Generate AI Embeddings'"></span>
                    </button>
                </div>

                <!-- Sync Log / Message -->
                <template x-if="syncMessage">
                    <div class="p-3 bg-blue-50 border border-blue-100 rounded-xl text-xs text-blue-800 flex items-center gap-2">
                        <i class="ph-bold ph-info text-blue-600"></i>
                        <span x-text="syncMessage"></span>
                    </div>
                </template>
            </div>
        </div>

        <!-- Right Side: How It Works & Tips Guide -->
        <div class="space-y-5">
            <div class="bg-gradient-to-br from-amber-500/10 via-brand-50/20 to-transparent p-6 rounded-2xl border border-amber-200/60 space-y-4">
                <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center text-xl shadow-sm shadow-amber-500/30">
                    <i class="ph-bold ph-lightbulb"></i>
                </div>
                <h4 class="font-bold text-gray-900 text-sm">How AI Vector Search Works</h4>
                <div class="space-y-3 text-xs text-gray-600 leading-relaxed">
                    <p>
                        <strong>1. Semantic Understanding:</strong> Unlike basic color matching, OpenAI CLIP recognizes real-world objects. A Rubik's cube or desk clutter gets 0% match against skincare products.
                    </p>
                    <p>
                        <strong>2. 100% Free GPU Tier:</strong> Cloudflare provides 10,000 daily AI inference requests for $0. No credit card or paid GPU server required.
                    </p>
                    <p>
                        <strong>3. Graceful Fallback:</strong> If Cloudflare API is temporarily unreachable or rate-limited, the system seamlessly falls back to our local barcode and perceptual hash engine without interrupting shoppers.
                    </p>
                </div>
            </div>

            <!-- 2-Minute Setup Instructions -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-3 text-xs text-gray-600">
                <h4 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                    <i class="ph-bold ph-list-numbers text-brand-600 text-base"></i>
                    <span>Quick Setup Guide</span>
                </h4>
                <ol class="space-y-2.5 list-decimal list-inside pl-1 text-gray-600">
                    <li class="leading-relaxed">
                        Log in to <a href="https://dash.cloudflare.com" target="_blank" class="text-brand-600 font-semibold underline">Cloudflare</a>.
                    </li>
                    <li class="leading-relaxed">
                        Click on your domain or dashboard; copy the <strong>Account ID</strong> from the right sidebar.
                    </li>
                    <li class="leading-relaxed">
                        Go to <strong>My Profile &rarr; API Tokens</strong> &rarr; click <strong>Create Token</strong>.
                    </li>
                    <li class="leading-relaxed">
                        Select <strong>Workers AI (Read/Edit)</strong> template and create.
                    </li>
                    <li class="leading-relaxed">
                        Paste both values here and click <strong>Save Settings</strong>!
                    </li>
                </ol>
            </div>
        </div>
    </div>

    <!-- Customer Visual Search Audit & Demand Insights Log -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center text-lg">
                        <i class="ph-bold ph-clock-counter-clockwise"></i>
                    </span>
                    <h3 class="text-base font-bold text-gray-900">Customer Visual Searches (Live History)</h3>
                </div>
                <p class="text-xs text-gray-500 mt-1">Real-time audit log of customer-uploaded photos, detected brands/types, and matched products to forecast product demand</p>
            </div>
            <div class="flex items-center gap-2 text-xs text-gray-500 flex-wrap">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 font-semibold border border-emerald-100">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Live Tracking Active</span>
                </span>
                <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-700 font-medium">
                    Total: {{ number_format($totalAllTimeSearches) }} searches
                </span>
            </div>
        </div>

        @if($searchLogs->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50/75 text-gray-500 uppercase tracking-wider text-[11px] font-semibold border-b border-gray-100">
                    <tr>
                        <th class="py-3.5 px-6">Customer Photo</th>
                        <th class="py-3.5 px-6">AI Detection (Brand / Type)</th>
                        <th class="py-3.5 px-6">Top Matched Product</th>
                        <th class="py-3.5 px-6">Confidence</th>
                        <th class="py-3.5 px-6">Search Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @foreach($searchLogs as $log)
                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="py-4 px-6">
                            @if($log->thumbnail_url)
                            <a href="{{ $log->thumbnail_url }}" target="_blank" class="block group relative w-14 h-14 rounded-xl overflow-hidden border border-gray-200 bg-gray-50 shadow-xs">
                                <img src="{{ $log->thumbnail_url }}" alt="Uploaded photo" class="w-full h-full object-cover group-hover:scale-105 transition">
                                <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white text-xs">
                                    <i class="ph-bold ph-magnifying-glass-plus"></i>
                                </div>
                            </a>
                            @else
                            <div class="w-14 h-14 rounded-xl bg-gray-100 text-gray-400 flex items-center justify-center text-xl">
                                <i class="ph ph-image"></i>
                            </div>
                            @endif
                        </td>
                        <td class="py-4 px-6 space-y-1">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                @if(!empty($log->detected_brand) && strtolower($log->detected_brand) !== 'none')
                                <span class="px-2 py-0.5 rounded-md bg-purple-50 text-purple-700 font-bold border border-purple-100 text-[11px]">
                                    {{ $log->detected_brand }}
                                </span>
                                @endif
                                @if(!empty($log->detected_type) && strtolower($log->detected_type) !== 'none')
                                <span class="px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 font-semibold border border-blue-100 text-[11px]">
                                    {{ $log->detected_type }}
                                </span>
                                @endif
                                @if($log->is_rejected)
                                <span class="px-2 py-0.5 rounded-md bg-red-50 text-red-700 font-bold border border-red-100 text-[11px]">
                                    <i class="ph-bold ph-prohibit"></i> Filtered (Unrelated)
                                </span>
                                @endif
                            </div>
                            @if(!empty($log->detected_keywords))
                            <p class="text-[11px] text-gray-500 max-w-xs truncate" title="{{ $log->detected_keywords }}">
                                <span class="font-medium text-gray-400">Keywords:</span> {{ $log->detected_keywords }}
                            </p>
                            @endif
                        </td>
                        <td class="py-4 px-6">
                            @if($log->topProduct)
                            <div class="flex items-center gap-3 max-w-sm">
                                @if($log->topProduct->featured_image)
                                <img src="{{ asset('storage/' . ltrim($log->topProduct->featured_image, '/')) }}" alt="" class="w-10 h-10 rounded-lg object-cover border border-gray-100 shrink-0">
                                @endif
                                <div class="min-w-0">
                                    <a href="{{ route('product.show', $log->topProduct->slug) }}" target="_blank" class="font-semibold text-gray-900 hover:text-brand-600 truncate block transition" title="{{ $log->topProduct->title }}">
                                        {{ $log->topProduct->title }}
                                    </a>
                                    <span class="text-[11px] text-gray-400">
                                        {{ $log->topProduct->category?->name ?? 'Uncategorized' }}
                                    </span>
                                </div>
                            </div>
                            @elseif($log->is_rejected)
                            <span class="text-xs text-red-600 font-medium">None (Rejected by Vision AI)</span>
                            @else
                            <span class="text-xs text-gray-400 italic">No matching catalog items found</span>
                            @endif
                        </td>
                        <td class="py-4 px-6">
                            @if($log->top_score)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold {{ $log->top_score >= 80 ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : ($log->top_score >= 50 ? 'bg-amber-50 text-amber-700 border border-amber-100' : 'bg-gray-100 text-gray-600') }}">
                                <i class="ph-bold ph-sparkle text-xs"></i>
                                <span>{{ round($log->top_score, 0) }}% Match</span>
                            </span>
                            @else
                            <span class="text-xs text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="py-4 px-6 text-gray-500 whitespace-nowrap">
                            <span class="font-medium text-gray-700 block">{{ $log->created_at->diffForHumans() }}</span>
                            <span class="text-[11px] text-gray-400">{{ $log->created_at->format('d M, Y h:i A') }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-gray-100">
            {{ $searchLogs->links() }}
        </div>
        @else
        <div class="p-12 text-center text-gray-400 space-y-3">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-gray-50 flex items-center justify-center text-2xl text-gray-400">
                <i class="ph ph-camera-slash"></i>
            </div>
            <div>
                <h4 class="font-bold text-gray-700 text-sm">No Customer Visual Searches Yet</h4>
                <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">When shoppers use the camera or photo upload button on your storefront, their uploaded search images and AI detection records will appear here.</p>
            </div>
        </div>
        @endif
    </div>
</div>

<script>
function visualSearchSettings() {
    return {
        form: {
            enabled: {{ $enabled ? 'true' : 'false' }},
            accountId: '{{ $accountId }}',
            apiToken: '{{ $apiToken }}',
        },
        stats: {
            totalImages: {{ $totalImages }},
            aiIndexed: {{ $aiIndexed }},
        },
        showToken: false,
        saving: false,
        testing: false,
        syncing: false,
        syncMessage: null,
        testStatus: {
            message: null,
            success: false,
            latency: null,
        },
        toast: {
            show: false,
            message: '',
            type: 'success',
        },

        get coveragePercent() {
            if (!this.stats.totalImages || this.stats.totalImages === 0) return 0;
            return Math.min(100, Math.round((this.stats.aiIndexed / this.stats.totalImages) * 100));
        },

        showToast(msg, type = 'success') {
            this.toast.message = msg;
            this.toast.type = type;
            this.toast.show = true;
            setTimeout(() => { this.toast.show = false; }, 3500);
        },

        async saveSettings() {
            this.saving = true;
            try {
                const res = await fetch('{{ route('admin.settings.visual-search.update') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        visual_search_ai_enabled: this.form.enabled,
                        cloudflare_account_id: this.form.accountId,
                        cloudflare_ai_token: this.form.apiToken,
                    }),
                });
                const data = await res.json();
                this.saving = false;
                if (data.status === 'success') {
                    this.showToast('Settings saved successfully!');
                } else {
                    this.showToast(data.message || 'Save failed', 'error');
                }
            } catch (e) {
                this.saving = false;
                this.showToast('Failed to save settings: ' + e.message, 'error');
            }
        },

        async testConnection() {
            this.testing = true;
            this.testStatus.message = null;
            try {
                const res = await fetch('{{ route('admin.settings.visual-search.test') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        account_id: this.form.accountId,
                        api_token: this.form.apiToken,
                    }),
                });
                const data = await res.json();
                this.testing = false;
                this.testStatus.success = !!data.success;
                this.testStatus.message = data.message;
                this.testStatus.latency = data.latency_ms || null;

                if (data.success) {
                    this.showToast('Connected to Cloudflare AI (' + data.latency_ms + 'ms)!');
                } else {
                    this.showToast(data.message, 'error');
                }
            } catch (e) {
                this.testing = false;
                this.testStatus.success = false;
                this.testStatus.message = 'Test failed: ' + e.message;
                this.showToast('Test failed', 'error');
            }
        },

        async startSync() {
            this.syncing = true;
            this.syncMessage = 'Generating AI embeddings in batches...';
            
            const runBatch = async () => {
                try {
                    const res = await fetch('{{ route('admin.settings.visual-search.sync') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                    });
                    const data = await res.json();
                    if (!data.success) {
                        this.syncing = false;
                        this.syncMessage = data.message || 'Sync error';
                        this.showToast(data.message, 'error');
                        return;
                    }

                    this.stats.aiIndexed += data.processed;
                    if (data.remaining > 0) {
                        this.syncMessage = 'Processed ' + data.processed + ' images. Remaining: ' + data.remaining + '...';
                        setTimeout(runBatch, 500); // graceful pause between batches
                    } else {
                        this.syncing = false;
                        this.syncMessage = 'All catalog images have been successfully embedded with AI!';
                        this.showToast('AI indexing complete!');
                    }
                } catch (e) {
                    this.syncing = false;
                    this.syncMessage = 'Sync interrupted: ' + e.message;
                    this.showToast('Sync error: ' + e.message, 'error');
                }
            };

            await runBatch();
        }
    };
}
</script>
@endsection

