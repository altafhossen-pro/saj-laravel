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
                            class="w-full py-2 px-3 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 bg-white">
                            <option value="gemini-2.0-flash">gemini-2.0-flash (Recommended, Fast & Free)</option>
                            <option value="gemini-1.5-flash">gemini-1.5-flash (High Quota)</option>
                            <option value="gemini-1.5-pro">gemini-1.5-pro (Deep Reasoning)</option>
                        </select>
                        <p class="text-[11px] text-gray-400 mt-1">gemini-2.0-flash produces top quality SEO content instantly.</p>
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
                    model: '{{ old('gemini_model', $settings['gemini_model'] ?? 'gemini-2.0-flash') }}'
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
