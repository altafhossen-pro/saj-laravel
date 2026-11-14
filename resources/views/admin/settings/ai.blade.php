@extends('layouts.admin')

@section('title', 'AI Providers & Configuration - Admin Panel')
@section('header_title', 'AI Configuration')

@section('content')
<div class="w-full space-y-5" x-data="aiSettingsManager()" x-cloak>
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
        <i class="text-xl" :class="toast.type === 'success' ? 'ph-fill ph-check-circle text-emerald-400' : 'ph-fill ph-warning-circle text-red-400'"></i>
        <span x-text="toast.message"></span>
        <button type="button" @click="toast.show = false" class="ml-2 text-gray-400 hover:text-white transition-colors cursor-pointer">
            <i class="ph ph-x"></i>
        </button>
    </div>

    <!-- Header Breadcrumbs / Top Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <a href="{{ route('admin.settings.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-brand-600 transition-colors">
            <i class="ph ph-arrow-left text-lg"></i>
            <span>Back to Settings</span>
        </a>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-purple-50 text-purple-700 font-semibold border border-purple-200/80 text-xs">
                <i class="ph-bold ph-sparkle text-purple-600"></i> Multi-AI Engine
            </span>
            <button type="button" @click="saveSettings()" :disabled="saving"
                class="px-5 py-2 bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs rounded-xl shadow-sm shadow-brand-500/20 transition flex items-center gap-1.5 cursor-pointer disabled:opacity-60">
                <i x-show="!saving" class="ph-bold ph-floppy-disk text-sm text-white"></i>
                <i x-show="saving" class="ph-bold ph-spinner animate-spin text-sm text-white" style="display: none;"></i>
                <span class="text-white" x-text="saving ? 'Saving...' : 'Save Settings'"></span>
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl flex items-center gap-2.5 text-xs font-medium">
            <i class="ph-fill ph-check-circle text-emerald-600 text-lg flex-shrink-0"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Status Overview Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- Active Engine Card -->
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg">
                    <i class="ph-bold ph-cpu"></i>
                </div>
                <div>
                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Default Engine</p>
                    <h4 class="text-sm font-bold text-gray-800 capitalize" x-text="getProviderDisplayName(defaultProvider)"></h4>
                </div>
            </div>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-100">
                Active
            </span>
        </div>

        <!-- Connection Test Status Card -->
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg transition-colors"
                     :class="lastTest.success ? 'bg-emerald-50 text-emerald-600' : (lastTest.tested ? 'bg-red-50 text-red-600' : 'bg-gray-50 text-gray-400')">
                    <i :class="testing ? 'ph-bold ph-spinner animate-spin text-brand-600' : (lastTest.success ? 'ph-fill ph-check-circle' : (lastTest.tested ? 'ph-fill ph-x-circle' : 'ph ph-broadcast'))"></i>
                </div>
                <div>
                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">API Test Status</p>
                    <h4 class="text-sm font-bold text-gray-800" x-text="testing ? 'Testing...' : (lastTest.tested ? (lastTest.success ? 'Connected (' + lastTest.latency + 'ms)' : 'Test Failed') : 'Ready to Test')"></h4>
                </div>
            </div>
            <button type="button" @click="testActiveProvider()" :disabled="testing"
                class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition flex items-center gap-1 cursor-pointer disabled:opacity-50">
                <i class="ph ph-arrows-clockwise text-xs" :class="{'animate-spin': testing}"></i>
                <span>Test</span>
            </button>
        </div>

        <!-- Gemini Free Quota Card -->
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                    <i class="ph-bold ph-gift"></i>
                </div>
                <div>
                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Gemini Free Quota</p>
                    <h4 class="text-sm font-bold text-gray-800">1,500 req/day</h4>
                </div>
            </div>
            <a href="https://aistudio.google.com/app/apikey" target="_blank"
                class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition inline-flex items-center gap-1">
                <span>Free Key</span> <i class="ph ph-arrow-square-out text-xs"></i>
            </a>
        </div>
    </div>

    <!-- Main Config Card with Tabs -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <!-- Tab Navigation Bar -->
        <div class="px-5 pt-3 border-b border-gray-100 bg-gray-50/50 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-1 overflow-x-auto">
                <!-- Gemini Tab -->
                <button type="button" @click="activeTab = 'gemini'"
                    class="px-4 py-2.5 text-xs font-bold border-b-2 flex items-center gap-2 transition-all cursor-pointer"
                    :class="activeTab === 'gemini' ? 'border-brand-600 text-brand-600 bg-white rounded-t-xl shadow-2xs' : 'border-transparent text-gray-500 hover:text-gray-800 hover:bg-white/60 rounded-t-xl'">
                    <i class="ph-bold ph-google-logo text-sm"></i>
                    <span>Google Gemini</span>
                    <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700">Free</span>
                    <template x-if="defaultProvider === 'gemini'">
                        <span class="w-2 h-2 rounded-full bg-brand-600"></span>
                    </template>
                </button>

                <!-- OpenAI Tab -->
                <button type="button" @click="activeTab = 'openai'"
                    class="px-4 py-2.5 text-xs font-bold border-b-2 flex items-center gap-2 transition-all cursor-pointer"
                    :class="activeTab === 'openai' ? 'border-brand-600 text-brand-600 bg-white rounded-t-xl shadow-2xs' : 'border-transparent text-gray-500 hover:text-gray-800 hover:bg-white/60 rounded-t-xl'">
                    <i class="ph-bold ph-open-ai-logo text-sm"></i>
                    <span>OpenAI</span>
                    <template x-if="defaultProvider === 'openai'">
                        <span class="w-2 h-2 rounded-full bg-brand-600"></span>
                    </template>
                </button>

                <!-- Groq Tab -->
                <button type="button" @click="activeTab = 'groq'"
                    class="px-4 py-2.5 text-xs font-bold border-b-2 flex items-center gap-2 transition-all cursor-pointer"
                    :class="activeTab === 'groq' ? 'border-brand-600 text-brand-600 bg-white rounded-t-xl shadow-2xs' : 'border-transparent text-gray-500 hover:text-gray-800 hover:bg-white/60 rounded-t-xl'">
                    <i class="ph-bold ph-lightning text-sm"></i>
                    <span>Groq Cloud</span>
                    <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-orange-100 text-orange-700">Fast</span>
                    <template x-if="defaultProvider === 'groq'">
                        <span class="w-2 h-2 rounded-full bg-brand-600"></span>
                    </template>
                </button>

                <!-- Claude Tab -->
                <button type="button" @click="activeTab = 'claude'"
                    class="px-4 py-2.5 text-xs font-bold border-b-2 flex items-center gap-2 transition-all cursor-pointer"
                    :class="activeTab === 'claude' ? 'border-brand-600 text-brand-600 bg-white rounded-t-xl shadow-2xs' : 'border-transparent text-gray-500 hover:text-gray-800 hover:bg-white/60 rounded-t-xl'">
                    <i class="ph-bold ph-brain text-sm"></i>
                    <span>Claude</span>
                    <template x-if="defaultProvider === 'claude'">
                        <span class="w-2 h-2 rounded-full bg-brand-600"></span>
                    </template>
                </button>
            </div>

            <div class="text-[11px] text-gray-500 pb-2 sm:pb-0 font-medium">
                Active Provider: <strong class="text-brand-600 capitalize" x-text="getProviderDisplayName(defaultProvider)"></strong>
            </div>
        </div>

        <form id="aiConfigForm" action="{{ route('admin.settings.ai.update') }}" method="POST" class="p-5 sm:p-6 space-y-5">
            @csrf
            <input type="hidden" name="default_provider" :value="defaultProvider">

            <!-- Hidden inputs so other tab values are preserved when submitting -->
            <input type="hidden" name="gemini_api_key" :value="form.gemini.key">
            <input type="hidden" name="gemini_model" :value="form.gemini.model">

            <input type="hidden" name="openai_api_key" :value="form.openai.key">
            <input type="hidden" name="openai_model" :value="form.openai.model">

            <input type="hidden" name="groq_api_key" :value="form.groq.key">
            <input type="hidden" name="groq_model" :value="form.groq.model">

            <input type="hidden" name="claude_api_key" :value="form.claude.key">
            <input type="hidden" name="claude_model" :value="form.claude.model">

            <!-- ================= TAB 1: GOOGLE GEMINI ================= -->
            <div x-show="activeTab === 'gemini'" class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-gray-100">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                            <span>Google Gemini (Recommended)</span>
                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">100% Free Tier</span>
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">Free 1,500 requests/day. Best for Bangladeshi SEO guides, high-intent product articles & FAQs.</p>
                    </div>

                    <button type="button" @click="defaultProvider = 'gemini'"
                        class="px-3 py-1.5 rounded-xl border text-xs font-semibold flex items-center gap-1.5 transition cursor-pointer self-start sm:self-auto"
                        :class="defaultProvider === 'gemini' ? 'bg-purple-50 border-purple-200 text-purple-700 font-bold' : 'bg-gray-50 border-gray-200 text-gray-600 hover:bg-gray-100'">
                        <i class="ph text-sm" :class="defaultProvider === 'gemini' ? 'ph-check-circle-fill text-purple-600' : 'ph-circle'"></i>
                        <span x-text="defaultProvider === 'gemini' ? 'Active Default Engine' : 'Use as Default'"></span>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- API Key -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-semibold text-gray-700">Gemini API Key</label>
                            <a href="https://aistudio.google.com/app/apikey" target="_blank" class="text-[11px] text-purple-600 hover:text-purple-700 font-semibold inline-flex items-center gap-0.5">
                                <span>Get Free Key</span> <i class="ph ph-arrow-square-out text-xs"></i>
                            </a>
                        </div>
                        <div class="relative">
                            <input :type="showKey.gemini ? 'text' : 'password'" x-model="form.gemini.key"
                                placeholder="AIzaSy..."
                                class="w-full pr-10 pl-3.5 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 font-mono">
                            <button type="button" @click="showKey.gemini = !showKey.gemini"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 cursor-pointer">
                                <i :class="showKey.gemini ? 'ph ph-eye-slash' : 'ph ph-eye'" class="text-base"></i>
                            </button>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1">Get your free key from Google AI Studio (no credit card required).</p>
                    </div>

                    <!-- Model Selection -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Model</label>
                        <select x-model="form.gemini.model"
                            class="w-full py-2 px-3 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 bg-white font-mono">
                            <option value="gemini-3.5-flash-lite">gemini-3.5-flash-lite (Ultra Fast ~0.8s, 100% Free - Recommended)</option>
                            <option value="gemini-flash-lite-latest">gemini-flash-lite-latest (Fast Free Tier)</option>
                            <option value="gemini-3.5-flash">gemini-3.5-flash (Standard 1M Context)</option>
                            <option value="gemini-3.8-flash">gemini-3.8-flash (High Reasoning Flash)</option>
                            <option value="gemini-flash-latest">gemini-flash-latest (Latest Flash)</option>
                            <option value="gemini-pro-latest">gemini-pro-latest (Pro Model - Requires Google Cloud Billing)</option>
                            <template x-if="!['gemini-3.5-flash-lite', 'gemini-flash-lite-latest', 'gemini-3.5-flash', 'gemini-3.8-flash', 'gemini-flash-latest', 'gemini-pro-latest'].includes(form.gemini.model)">
                                <option :value="form.gemini.model" x-text="form.gemini.model + ' (Custom Selected)'"></option>
                            </template>
                        </select>
                        <p class="text-[11px] text-gray-400 mt-1">Recommended: <strong>gemini-3.5-flash-lite</strong> for blazing-fast responses under 1s & 1,500 free daily requests.</p>
                    </div>
                </div>

                <!-- Live Official API Model & Limit Check Buttons -->
                <div class="flex flex-wrap items-center gap-2 pt-1">
                    <button type="button" @click="checkGeminiModels()" :disabled="loadingModels"
                        class="px-3.5 py-1.5 rounded-xl border border-purple-200 bg-purple-50 hover:bg-purple-100 text-purple-700 text-xs font-semibold flex items-center gap-1.5 transition cursor-pointer disabled:opacity-60">
                        <i x-show="!loadingModels" class="ph-bold ph-list-dashes text-sm text-purple-600"></i>
                        <i x-show="loadingModels" class="ph-bold ph-spinner animate-spin text-sm text-purple-600" style="display: none;"></i>
                        <span x-text="loadingModels ? 'Fetching Models...' : 'Check Available Models'"></span>
                    </button>

                    <button type="button" @click="checkGeminiLimits()" :disabled="loadingLimits"
                        class="px-3.5 py-1.5 rounded-xl border border-emerald-200 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-semibold flex items-center gap-1.5 transition cursor-pointer disabled:opacity-60">
                        <i x-show="!loadingLimits" class="ph-bold ph-gauge text-sm text-emerald-600"></i>
                        <i x-show="loadingLimits" class="ph-bold ph-spinner animate-spin text-sm text-emerald-600" style="display: none;"></i>
                        <span x-text="loadingLimits ? 'Fetching Quotas...' : 'Check Limits & Quota'"></span>
                    </button>
                </div>

                <!-- Available Models Slide-down Panel -->
                <div x-show="showModelsPanel" x-transition class="p-4 rounded-xl border border-purple-200 bg-purple-50/50 space-y-3" style="display: none;">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold text-purple-950 flex items-center gap-1.5">
                            <i class="ph-bold ph-sparkle text-purple-600"></i>
                            <span>Official Models Available on Your Key (<span x-text="geminiModels.length"></span>)</span>
                        </h4>
                        <button type="button" @click="showModelsPanel = false" class="text-gray-400 hover:text-gray-600 text-sm">
                            <i class="ph ph-x"></i>
                        </button>
                    </div>
                    <div class="max-h-60 overflow-y-auto space-y-2 pr-1">
                        <template x-for="m in geminiModels" :key="m.id">
                            <div class="p-2.5 bg-white rounded-lg border border-purple-100 flex items-center justify-between gap-3 text-xs hover:border-purple-300 transition-colors">
                                <div class="space-y-0.5">
                                    <div class="font-bold text-gray-900 flex items-center gap-2">
                                        <span x-text="m.displayName || m.id"></span>
                                        <span class="font-mono text-[10px] text-gray-400" x-text="'(' + m.id + ')'"></span>
                                        <template x-if="form.gemini.model === m.id">
                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-brand-100 text-brand-700">Selected</span>
                                        </template>
                                    </div>
                                    <div class="text-[11px] text-gray-500 line-clamp-1" x-text="m.description"></div>
                                    <div class="text-[10px] text-gray-400 flex items-center gap-2">
                                        <span x-show="m.inputTokenLimit">Input: <strong x-text="Number(m.inputTokenLimit).toLocaleString()"></strong> tokens</span>
                                        <span x-show="m.outputTokenLimit">Output: <strong x-text="Number(m.outputTokenLimit).toLocaleString()"></strong> tokens</span>
                                    </div>
                                </div>
                                <button type="button" @click="selectGeminiModel(m.id)"
                                    class="px-2.5 py-1 rounded-lg text-xs font-semibold cursor-pointer transition shrink-0"
                                    :class="form.gemini.model === m.id ? 'bg-brand-50 text-brand-700 border border-brand-200' : 'bg-gray-100 hover:bg-purple-600 hover:text-white text-gray-700'">
                                    <span x-text="form.gemini.model === m.id ? 'In Use' : 'Use Model'"></span>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Limits Slide-down Panel -->
                <div x-show="showLimitsPanel && geminiLimits" x-transition class="p-4 rounded-xl border border-emerald-200 bg-emerald-50/50 space-y-3" style="display: none;">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold text-emerald-950 flex items-center gap-1.5">
                            <i class="ph-bold ph-gauge text-emerald-600"></i>
                            <span>Official Limits for <strong class="font-mono text-emerald-700" x-text="geminiLimits ? geminiLimits.model : ''"></strong></span>
                        </h4>
                        <button type="button" @click="showLimitsPanel = false" class="text-gray-400 hover:text-gray-600 text-sm">
                            <i class="ph ph-x"></i>
                        </button>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="bg-white p-2.5 rounded-lg border border-emerald-100">
                            <div class="text-[10px] uppercase font-bold text-gray-400">RPM (Req/Min)</div>
                            <div class="text-sm font-extrabold text-emerald-700" x-text="geminiLimits && geminiLimits.freeTier ? geminiLimits.freeTier.rpm : '15 RPM'"></div>
                        </div>
                        <div class="bg-white p-2.5 rounded-lg border border-emerald-100">
                            <div class="text-[10px] uppercase font-bold text-gray-400">RPD (Req/Day)</div>
                            <div class="text-sm font-extrabold text-emerald-700" x-text="geminiLimits && geminiLimits.freeTier ? geminiLimits.freeTier.rpd : '1,500 RPD'"></div>
                        </div>
                        <div class="bg-white p-2.5 rounded-lg border border-emerald-100">
                            <div class="text-[10px] uppercase font-bold text-gray-400">TPM (Tokens/Min)</div>
                            <div class="text-sm font-extrabold text-emerald-700" x-text="geminiLimits && geminiLimits.freeTier ? geminiLimits.freeTier.tpm : '1,000,000 TPM'"></div>
                        </div>
                        <div class="bg-white p-2.5 rounded-lg border border-emerald-100">
                            <div class="text-[10px] uppercase font-bold text-gray-400">Max Context Window</div>
                            <div class="text-sm font-extrabold text-emerald-700" x-text="geminiLimits ? geminiLimits.inputTokenLimit : '1M tokens'"></div>
                        </div>
                    </div>

                    <div class="text-[11px] text-emerald-900 bg-white/90 p-3 rounded-lg border border-emerald-200/80 leading-relaxed space-y-1">
                        <template x-if="geminiLimits && geminiLimits.model.includes('pro')">
                            <div>
                                <strong class="text-emerald-950">Official Pro Model Limits:</strong> On Google AI Studio <strong>Free Tier</strong>, Pro models are officially limited to <strong>50 requests per day (RPD)</strong> because they use high-power reasoning GPUs. If your account has <strong>Google AI Pro / Billing</strong> active, this limit is elevated to <strong>Pay-As-You-Go (1,000+ RPM & Unlimited Daily Requests)</strong>!
                            </div>
                        </template>
                        <template x-if="!geminiLimits || !geminiLimits.model.includes('pro')">
                            <div>
                                <strong class="text-emerald-950">Official Flash Model Limits:</strong> Flash models give you <strong>1,500 requests per day (RPD)</strong> completely free on Google AI Studio without credit card charges!
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- ================= TAB 2: OPENAI ================= -->
            <div x-show="activeTab === 'openai'" class="space-y-4" style="display: none;">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-gray-100">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                            <span>OpenAI (ChatGPT)</span>
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">Uses your OpenAI platform credits for generation.</p>
                    </div>

                    <button type="button" @click="defaultProvider = 'openai'"
                        class="px-3 py-1.5 rounded-xl border text-xs font-semibold flex items-center gap-1.5 transition cursor-pointer self-start sm:self-auto"
                        :class="defaultProvider === 'openai' ? 'bg-emerald-50 border-emerald-200 text-emerald-700 font-bold' : 'bg-gray-50 border-gray-200 text-gray-600 hover:bg-gray-100'">
                        <i class="ph text-sm" :class="defaultProvider === 'openai' ? 'ph-check-circle-fill text-emerald-600' : 'ph-circle'"></i>
                        <span x-text="defaultProvider === 'openai' ? 'Active Default Engine' : 'Use as Default'"></span>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-semibold text-gray-700">OpenAI API Key</label>
                            <a href="https://platform.openai.com/api-keys" target="_blank" class="text-[11px] text-emerald-600 hover:text-emerald-700 font-semibold inline-flex items-center gap-0.5">
                                <span>Get Key</span> <i class="ph ph-arrow-square-out text-xs"></i>
                            </a>
                        </div>
                        <div class="relative">
                            <input :type="showKey.openai ? 'text' : 'password'" x-model="form.openai.key"
                                placeholder="sk-proj-..."
                                class="w-full pr-10 pl-3.5 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 font-mono">
                            <button type="button" @click="showKey.openai = !showKey.openai"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 cursor-pointer">
                                <i :class="showKey.openai ? 'ph ph-eye-slash' : 'ph ph-eye'" class="text-base"></i>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Model</label>
                        <select x-model="form.openai.model"
                            class="w-full py-2 px-3 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 bg-white">
                            <option value="gpt-4o-mini">gpt-4o-mini (Cost-effective)</option>
                            <option value="gpt-4o">gpt-4o (High capability)</option>
                            <option value="gpt-3.5-turbo">gpt-3.5-turbo</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- ================= TAB 3: GROQ CLOUD ================= -->
            <div x-show="activeTab === 'groq'" class="space-y-4" style="display: none;">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-gray-100">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                            <span>Groq Cloud (Llama 3.3)</span>
                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-orange-50 text-orange-700 border border-orange-200">Free Tier</span>
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">Ultra-fast generation using Meta Llama models with free tier access.</p>
                    </div>

                    <button type="button" @click="defaultProvider = 'groq'"
                        class="px-3 py-1.5 rounded-xl border text-xs font-semibold flex items-center gap-1.5 transition cursor-pointer self-start sm:self-auto"
                        :class="defaultProvider === 'groq' ? 'bg-orange-50 border-orange-200 text-orange-700 font-bold' : 'bg-gray-50 border-gray-200 text-gray-600 hover:bg-gray-100'">
                        <i class="ph text-sm" :class="defaultProvider === 'groq' ? 'ph-check-circle-fill text-orange-600' : 'ph-circle'"></i>
                        <span x-text="defaultProvider === 'groq' ? 'Active Default Engine' : 'Use as Default'"></span>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-semibold text-gray-700">Groq API Key</label>
                            <a href="https://console.groq.com/keys" target="_blank" class="text-[11px] text-orange-600 hover:text-orange-700 font-semibold inline-flex items-center gap-0.5">
                                <span>Get Free Key</span> <i class="ph ph-arrow-square-out text-xs"></i>
                            </a>
                        </div>
                        <div class="relative">
                            <input :type="showKey.groq ? 'text' : 'password'" x-model="form.groq.key"
                                placeholder="gsk_..."
                                class="w-full pr-10 pl-3.5 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 font-mono">
                            <button type="button" @click="showKey.groq = !showKey.groq"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 cursor-pointer">
                                <i :class="showKey.groq ? 'ph ph-eye-slash' : 'ph ph-eye'" class="text-base"></i>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Model</label>
                        <select x-model="form.groq.model"
                            class="w-full py-2 px-3 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 bg-white">
                            <option value="llama-3.3-70b-versatile">llama-3.3-70b-versatile (Recommended)</option>
                            <option value="llama-3.1-8b-instant">llama-3.1-8b-instant (Fastest)</option>
                            <option value="mixtral-8x7b-32768">mixtral-8x7b-32768</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- ================= TAB 4: CLAUDE ================= -->
            <div x-show="activeTab === 'claude'" class="space-y-4" style="display: none;">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-gray-100">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                            <span>Anthropic Claude</span>
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">Top-tier writing and nuance using Claude 3.5 Sonnet.</p>
                    </div>

                    <button type="button" @click="defaultProvider = 'claude'"
                        class="px-3 py-1.5 rounded-xl border text-xs font-semibold flex items-center gap-1.5 transition cursor-pointer self-start sm:self-auto"
                        :class="defaultProvider === 'claude' ? 'bg-amber-50 border-amber-200 text-amber-700 font-bold' : 'bg-gray-50 border-gray-200 text-gray-600 hover:bg-gray-100'">
                        <i class="ph text-sm" :class="defaultProvider === 'claude' ? 'ph-check-circle-fill text-amber-600' : 'ph-circle'"></i>
                        <span x-text="defaultProvider === 'claude' ? 'Active Default Engine' : 'Use as Default'"></span>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-semibold text-gray-700">Claude API Key</label>
                            <a href="https://console.anthropic.com/settings/keys" target="_blank" class="text-[11px] text-amber-600 hover:text-amber-700 font-semibold inline-flex items-center gap-0.5">
                                <span>Get Key</span> <i class="ph ph-arrow-square-out text-xs"></i>
                            </a>
                        </div>
                        <div class="relative">
                            <input :type="showKey.claude ? 'text' : 'password'" x-model="form.claude.key"
                                placeholder="sk-ant-..."
                                class="w-full pr-10 pl-3.5 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 font-mono">
                            <button type="button" @click="showKey.claude = !showKey.claude"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 cursor-pointer">
                                <i :class="showKey.claude ? 'ph ph-eye-slash' : 'ph ph-eye'" class="text-base"></i>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Model</label>
                        <select x-model="form.claude.model"
                            class="w-full py-2 px-3 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 bg-white">
                            <option value="claude-3-5-sonnet-20241022">claude-3-5-sonnet-20241022</option>
                            <option value="claude-3-5-haiku-20241022">claude-3-5-haiku-20241022</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Connection Test Result Box (Compact) -->
            <div x-show="testResult" x-transition class="p-3.5 rounded-xl border text-xs"
                 :class="testResult && testResult.success ? 'bg-emerald-50/70 border-emerald-200 text-emerald-900' : 'bg-red-50/70 border-red-200 text-red-900'"
                 style="display: none;">
                <div class="flex items-start gap-2.5">
                    <i class="text-base mt-0.5 flex-shrink-0"
                       :class="testResult && testResult.success ? 'ph-fill ph-check-circle text-emerald-600' : 'ph-fill ph-x-circle text-red-600'"></i>
                    <div class="flex-1 space-y-1">
                        <div class="font-bold" x-text="testResult ? testResult.message : ''"></div>
                        <template x-if="testResult && testResult.sample_output">
                            <div class="text-[11px] text-gray-600 font-mono bg-white/80 p-2 rounded-lg border border-gray-200/60 mt-1">
                                <span class="text-gray-400">Response:</span> <span x-text="'\"' + testResult.sample_output + '\"'"></span>
                            </div>
                        </template>
                    </div>
                    <button type="button" @click="testResult = null" class="text-gray-400 hover:text-gray-600">
                        <i class="ph ph-x"></i>
                    </button>
                </div>
            </div>

            <!-- Action Buttons Footer -->
            <div class="pt-4 border-t border-gray-100 flex items-center justify-between gap-4">
                <button type="button" @click="testActiveProvider()" :disabled="testing"
                    class="px-4 py-2 bg-gray-900 hover:bg-black text-white rounded-xl text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-60">
                    <i x-show="!testing" class="ph-bold ph-plug text-sm text-white"></i>
                    <i x-show="testing" class="ph-bold ph-spinner animate-spin text-sm text-white" style="display: none;"></i>
                    <span class="text-white" x-text="testing ? 'Testing Connection...' : 'Test ' + getProviderDisplayName(activeTab) + ' Connection'"></span>
                </button>

                <button type="button" @click="saveSettings()" :disabled="saving"
                    class="px-5 py-2 bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs rounded-xl shadow-sm shadow-brand-500/20 transition flex items-center gap-1.5 cursor-pointer disabled:opacity-60">
                    <i x-show="!saving" class="ph-bold ph-floppy-disk text-sm text-white"></i>
                    <i x-show="saving" class="ph-bold ph-spinner animate-spin text-sm text-white" style="display: none;"></i>
                    <span class="text-white" x-text="saving ? 'Saving...' : 'Save Settings'"></span>
                </button>
            </div>
        </form>
    </div>

    <!-- ================= LIVE AI CHAT PLAYGROUND ================= -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center text-base">
                    <i class="ph-bold ph-chat-teardrop-dots"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                        <span>Live AI Test Chat & Playground</span>
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-purple-50 text-purple-700 border border-purple-200"
                              x-text="'Testing: ' + getActiveModelName()"></span>
                    </h3>
                    <p class="text-xs text-gray-500">Test prompt generation directly with your selected model to evaluate content quality in real-time.</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" @click="clearChat()" class="px-3 py-1 rounded-lg text-xs font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition cursor-pointer">
                    Clear Chat
                </button>
            </div>
        </div>

        <!-- Chat Messages Body -->
        <div class="p-5 space-y-4 max-h-[420px] min-h-[180px] overflow-y-auto bg-gray-50/30" id="chatMessagesContainer">
            <!-- Initial Prompt Suggestions if empty -->
            <template x-if="chatMessages.length === 0">
                <div class="text-center py-6 space-y-3">
                    <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 mx-auto flex items-center justify-center text-xl">
                        <i class="ph-bold ph-sparkle"></i>
                    </div>
                    <div class="space-y-1">
                        <h4 class="text-xs font-bold text-gray-800">Test Your Model with a Live Prompt</h4>
                        <p class="text-[11px] text-gray-400">Click a sample prompt below or type your own question to test how the model writes.</p>
                    </div>

                    <div class="flex flex-wrap items-center justify-center gap-2 pt-2">
                        <button type="button" @click="sendQuickPrompt('একটি ভালো মানের স্কিন কেয়ার ক্রিম ব্যবহারের সঠিক নিয়ম ও ৩টি উপকারিতা বাংলায় লিখো।')"
                            class="px-3 py-1.5 rounded-full bg-white border border-gray-200 hover:border-purple-300 text-[11px] text-gray-700 shadow-2xs hover:bg-purple-50 transition cursor-pointer">
                            ✨ স্কিন কেয়ার ক্রিমের নিয়ম ও উপকারিতা
                        </button>
                        <button type="button" @click="sendQuickPrompt('একটি কানের দুল প্রোডাক্টের জন্য ফেসবুকে দেওয়ার মতো আকর্ষণীয় ২টি ছোট ক্যাপশন লিখে দাও।')"
                            class="px-3 py-1.5 rounded-full bg-white border border-gray-200 hover:border-purple-300 text-[11px] text-gray-700 shadow-2xs hover:bg-purple-50 transition cursor-pointer">
                            🛍️ কানের দুল ফেসবুক ক্যাপশন
                        </button>
                        <button type="button" @click="sendQuickPrompt('একটি এসইও ফ্রেন্ডলি বায়িং গাইডের জন্য সেরা ৫টি হেডিং (H2, H3) আউটলাইন দাও।')"
                            class="px-3 py-1.5 rounded-full bg-white border border-gray-200 hover:border-purple-300 text-[11px] text-gray-700 shadow-2xs hover:bg-purple-50 transition cursor-pointer">
                            🔍 এসইও বায়িং গাইড আউটলাইন
                        </button>
                    </div>
                </div>
            </template>

            <!-- Message Thread -->
            <template x-for="(msg, idx) in chatMessages" :key="idx">
                <div class="flex" :class="msg.role === 'user' ? 'justify-end' : 'justify-start'">
                    <!-- User Message Bubble -->
                    <template x-if="msg.role === 'user'">
                        <div class="max-w-[80%] bg-brand-600 text-white rounded-2xl rounded-tr-xs px-4 py-2.5 text-xs shadow-xs">
                            <p class="whitespace-pre-wrap leading-relaxed" x-text="msg.text"></p>
                        </div>
                    </template>

                    <!-- AI Message Bubble -->
                    <template x-if="msg.role === 'assistant'">
                        <div class="max-w-[85%] bg-white border border-gray-200/80 rounded-2xl rounded-tl-xs p-4 text-xs shadow-xs space-y-2">
                            <div class="flex items-center justify-between gap-3 pb-1 border-b border-gray-100 text-[10px] text-gray-400">
                                <span class="font-bold text-purple-700 flex items-center gap-1">
                                    <i class="ph-bold ph-robot"></i> <span x-text="msg.model"></span>
                                </span>
                                <div class="flex items-center gap-1.5">
                                    <span x-show="msg.streaming" class="flex items-center gap-1 text-brand-600 font-semibold">
                                        <i class="ph-bold ph-broadcast animate-pulse"></i> Streaming...
                                    </span>
                                    <span x-show="msg.latency && !msg.streaming" x-text="msg.latency + 'ms'"></span>
                                </div>
                            </div>
                            <div class="text-gray-800 whitespace-pre-wrap leading-relaxed font-sans">
                                <template x-if="msg.streaming && !msg.text">
                                    <div class="flex items-center gap-2 text-gray-400 py-1">
                                        <i class="ph-bold ph-circle-notch animate-spin text-purple-600"></i>
                                        <span class="italic text-[11px]">Thinking & generating response...</span>
                                    </div>
                                </template>
                                <span x-text="msg.text"></span><span x-show="msg.streaming" class="inline-block w-1.5 h-3.5 bg-brand-600 animate-pulse ml-0.5 rounded-xs align-middle"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        <!-- Chat Input Bar with Auto-expanding Textarea -->
        <div class="p-3 bg-white border-t border-gray-100 flex items-end gap-2.5">
            <div class="flex-1 relative">
                <textarea x-ref="chatTextarea" x-model="chatInput"
                    @input="autoResizeTextarea($event.target)"
                    @keydown.enter="handleChatEnter($event)"
                    rows="1"
                    placeholder="Type your message or prompt here... (Press Enter to send, Shift+Enter for new line)"
                    class="w-full resize-none px-4 py-2.5 text-xs border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-gray-50 focus:bg-white transition-all max-h-36 min-h-[42px] leading-relaxed"
                    :disabled="chatStreaming"></textarea>
            </div>

            <!-- Stop Generation Button (shown while streaming) -->
            <button type="button" @click="stopStreaming()" x-show="chatStreaming" style="display: none;"
                class="px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-semibold shadow-sm transition flex items-center gap-1.5 cursor-pointer shrink-0">
                <i class="ph-bold ph-stop text-sm"></i>
                <span>Stop</span>
            </button>

            <!-- Send Button -->
            <button type="button" @click="sendChatMessage()" x-show="!chatStreaming" :disabled="!chatInput.trim()"
                class="px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-semibold shadow-sm transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50 shrink-0">
                <i class="ph-bold ph-paper-plane-right text-sm"></i>
                <span>Send</span>
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('aiSettingsManager', () => ({
            activeTab: 'gemini',
            defaultProvider: '{{ old('default_provider', $settings['default_provider'] ?? 'gemini') }}',

            form: {
                gemini: {
                    key: '{{ old('gemini_api_key', $settings['gemini_api_key'] ?? '') }}',
                    model: '{{ old('gemini_model', $settings['gemini_model'] ?? 'gemini-3.8-flash') }}'
                },
                openai: {
                    key: '{{ old('openai_api_key', $settings['openai_api_key'] ?? '') }}',
                    model: '{{ old('openai_model', $settings['openai_model'] ?? 'gpt-4o-mini') }}'
                },
                groq: {
                    key: '{{ old('groq_api_key', $settings['groq_api_key'] ?? '') }}',
                    model: '{{ old('groq_model', $settings['groq_model'] ?? 'llama-3.3-70b-versatile') }}'
                },
                claude: {
                    key: '{{ old('claude_api_key', $settings['claude_api_key'] ?? '') }}',
                    model: '{{ old('claude_model', $settings['claude_model'] ?? 'claude-3-5-sonnet-20241022') }}'
                }
            },

            loadingModels: false,
            loadingLimits: false,
            showModelsPanel: false,
            showLimitsPanel: false,
            geminiModels: [],
            geminiLimits: null,

            showKey: {
                gemini: false,
                openai: false,
                groq: false,
                claude: false
            },

            saving: false,
            testing: false,
            testResult: null,

            lastTest: {
                tested: false,
                success: false,
                latency: null
            },

            // Chat Playground State
            chatInput: '',
            chatStreaming: false,
            chatMessages: [],
            abortController: null,

            getActiveModelName() {
                const provider = this.activeTab;
                return this.form[provider] ? this.form[provider].model : 'Selected Model';
            },

            autoResizeTextarea(el) {
                if (!el) return;
                el.style.height = 'auto';
                el.style.height = Math.min(el.scrollHeight, 140) + 'px';
            },

            handleChatEnter(e) {
                if (e.shiftKey) {
                    // Shift + Enter: let newline be added normally
                    return;
                }
                e.preventDefault();
                this.sendChatMessage();
            },

            clearChat() {
                if (this.chatStreaming) {
                    this.stopStreaming();
                }
                this.chatMessages = [];
            },

            sendQuickPrompt(text) {
                this.chatInput = text;
                this.$nextTick(() => {
                    const el = this.$refs.chatTextarea;
                    if (el) this.autoResizeTextarea(el);
                    this.sendChatMessage();
                });
            },

            stopStreaming() {
                if (this.abortController) {
                    this.abortController.abort();
                    this.abortController = null;
                }
                this.chatStreaming = false;
                if (this.chatMessages.length > 0) {
                    const last = this.chatMessages[this.chatMessages.length - 1];
                    if (last && last.role === 'assistant') {
                        last.streaming = false;
                    }
                }
            },

            async sendChatMessage() {
                const message = this.chatInput.trim();
                if (!message || this.chatStreaming) return;

                const provider = this.activeTab;
                const config = this.form[provider];

                if (!config || !config.key || !config.key.trim()) {
                    this.showToast(`Please enter an API key for ${this.getProviderDisplayName(provider)} first.`, 'error');
                    return;
                }

                this.chatMessages.push({
                    role: 'user',
                    text: message
                });

                this.chatInput = '';
                this.$nextTick(() => {
                    const el = this.$refs.chatTextarea;
                    if (el) el.style.height = 'auto';
                    const container = document.getElementById('chatMessagesContainer');
                    if (container) container.scrollTop = container.scrollHeight;
                });

                // Add placeholder assistant message
                const assistantMsg = {
                    role: 'assistant',
                    text: '',
                    model: config.model,
                    latency: null,
                    streaming: true
                };
                this.chatMessages.push(assistantMsg);
                const assistantIndex = this.chatMessages.length - 1;

                this.chatStreaming = true;
                this.abortController = new AbortController();
                const startTime = performance.now();

                try {
                    const response = await fetch('{{ route('admin.settings.ai.chat-stream') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'text/event-stream'
                        },
                        body: JSON.stringify({
                            provider: provider,
                            api_key: config.key.trim(),
                            model: config.model,
                            message: message
                        }),
                        signal: this.abortController.signal
                    });

                    if (!response.ok) {
                        const errorText = await response.text();
                        this.chatMessages[assistantIndex].text = '⚠️ Server error (' + response.status + '): ' + errorText;
                        this.chatMessages[assistantIndex].streaming = false;
                        this.chatStreaming = false;
                        return;
                    }

                    const reader = response.body.getReader();
                    const decoder = new TextDecoder();
                    let buffer = '';

                    while (true) {
                        const { done, value } = await reader.read();
                        if (done) break;

                        buffer += decoder.decode(value, { stream: true });
                        const lines = buffer.split('\n');
                        buffer = lines.pop(); // keep last incomplete line

                        for (const line of lines) {
                            const trimmed = line.trim();
                            if (trimmed === 'data: [DONE]') {
                                this.chatMessages[assistantIndex].streaming = false;
                                break;
                            }
                            if (trimmed.startsWith('data: ')) {
                                try {
                                    const parsed = JSON.parse(trimmed.slice(6));
                                    if (parsed.chunk) {
                                        this.chatMessages[assistantIndex].text += parsed.chunk;
                                        const container = document.getElementById('chatMessagesContainer');
                                        if (container) container.scrollTop = container.scrollHeight;
                                    }
                                } catch (e) {
                                    // Non-json chunk or partial line
                                }
                            }
                        }
                    }

                    this.chatMessages[assistantIndex].streaming = false;
                    this.chatMessages[assistantIndex].latency = Math.round(performance.now() - startTime);
                    this.chatStreaming = false;

                    this.$nextTick(() => {
                        const container = document.getElementById('chatMessagesContainer');
                        if (container) container.scrollTop = container.scrollHeight;
                    });
                } catch (err) {
                    if (err.name === 'AbortError') {
                        // Stopped by user
                        this.chatMessages[assistantIndex].streaming = false;
                        this.chatStreaming = false;
                        return;
                    }
                    console.error('Streaming error, falling back to sync chat:', err);
                    // Fallback to standard chat endpoint if stream fetch failed
                    try {
                        const fallbackRes = await fetch('{{ route('admin.settings.ai.chat') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                provider: provider,
                                api_key: config.key.trim(),
                                model: config.model,
                                message: message
                            })
                        });
                        const data = await fallbackRes.json();
                        if (data.success && data.reply) {
                            this.chatMessages[assistantIndex].text = data.reply;
                            this.chatMessages[assistantIndex].latency = data.latency_ms || Math.round(performance.now() - startTime);
                        } else {
                            this.chatMessages[assistantIndex].text = '⚠️ ' + (data.message || 'Generation failed.');
                        }
                    } catch (fallbackErr) {
                        this.chatMessages[assistantIndex].text = '⚠️ Connection error: ' + (err.message || 'Server unreachable.');
                    }
                    this.chatMessages[assistantIndex].streaming = false;
                    this.chatStreaming = false;
                }
            },

            toast: {
                show: false,
                type: 'success',
                message: ''
            },

            showToast(message, type = 'success') {
                this.toast.message = message;
                this.toast.type = type;
                this.toast.show = true;
                setTimeout(() => {
                    this.toast.show = false;
                }, 4000);
            },

            getProviderDisplayName(provider) {
                const names = {
                    'gemini': 'Google Gemini',
                    'openai': 'OpenAI',
                    'groq': 'Groq Cloud',
                    'claude': 'Claude'
                };
                return names[provider] || provider;
            },

            saveSettings() {
                this.saving = true;
                document.getElementById('aiConfigForm').submit();
            },

            checkGeminiModels() {
                const key = this.form.gemini.key;
                if (!key || !key.trim()) {
                    this.showToast('Please enter your Gemini API key first.', 'error');
                    return;
                }

                this.loadingModels = true;
                this.showModelsPanel = false;

                fetch('{{ route('admin.settings.ai.gemini-models') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        api_key: key.trim()
                    })
                })
                .then(res => res.json())
                .then(data => {
                    this.loadingModels = false;
                    if (data.success && data.models) {
                        this.geminiModels = data.models;
                        this.showModelsPanel = true;
                        this.showToast(data.message, 'success');
                    } else {
                        this.showToast(data.message || 'Failed to fetch models from Google.', 'error');
                    }
                })
                .catch(err => {
                    this.loadingModels = false;
                    console.error(err);
                    this.showToast('Server error while checking models.', 'error');
                });
            },

            selectGeminiModel(modelId) {
                this.form.gemini.model = modelId;
                this.showModelsPanel = false;
                this.showToast(`Selected model: ${modelId}`, 'success');
            },

            checkGeminiLimits() {
                const key = this.form.gemini.key;
                const model = this.form.gemini.model;

                if (!key || !key.trim()) {
                    this.showToast('Please enter your Gemini API key first.', 'error');
                    return;
                }

                this.loadingLimits = true;
                this.showLimitsPanel = false;

                fetch('{{ route('admin.settings.ai.gemini-limits') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        api_key: key.trim(),
                        model: model
                    })
                })
                .then(res => res.json())
                .then(data => {
                    this.loadingLimits = false;
                    if (data.success) {
                        this.geminiLimits = data;
                        this.showLimitsPanel = true;
                        this.showToast(data.message, 'success');
                    } else {
                        this.showToast(data.message || 'Failed to fetch limits from Google.', 'error');
                    }
                })
                .catch(err => {
                    this.loadingLimits = false;
                    console.error(err);
                    this.showToast('Server error while checking limits.', 'error');
                });
            },

            testActiveProvider() {
                const provider = this.activeTab;
                const config = this.form[provider];

                if (!config || !config.key || !config.key.trim()) {
                    this.showToast(`Please enter an API key for ${this.getProviderDisplayName(provider)} first.`, 'error');
                    return;
                }

                this.testing = true;
                this.testResult = null;

                fetch('{{ route('admin.settings.ai.test') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        provider: provider,
                        api_key: config.key.trim(),
                        model: config.model
                    })
                })
                .then(res => res.json())
                .then(data => {
                    this.testing = false;
                    this.testResult = data;
                    this.lastTest = {
                        tested: true,
                        success: !!data.success,
                        latency: data.latency_ms || null
                    };

                    if (data.success) {
                        this.showToast(data.message, 'success');
                    } else {
                        this.showToast(data.message || 'Connection test failed.', 'error');
                    }
                })
                .catch(err => {
                    this.testing = false;
                    console.error(err);
                    this.testResult = {
                        success: false,
                        message: 'Network error or server unreachable while testing.'
                    };
                    this.lastTest = {
                        tested: true,
                        success: false,
                        latency: null
                    };
                    this.showToast('Failed to reach server for testing.', 'error');
                });
            }
        }));
    });
</script>
@endpush
