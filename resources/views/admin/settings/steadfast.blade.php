@extends('layouts.admin')

@section('title', 'Steadfast Courier Configuration & Logs - Admin Panel')
@section('header_title', 'Steadfast Courier Integration')

@section('content')
<div class="w-full space-y-6" x-data="steadfastSettings()">
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
        <button type="button" @click="toast.show = false" class="ml-2 text-gray-400 hover:text-white transition-colors cursor-pointer">
            <i class="ph ph-x"></i>
        </button>
    </div>

    <!-- Header Breadcrumbs / Back button -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <a href="{{ route('admin.settings.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-brand-600 transition-colors">
            <i class="ph ph-arrow-left text-lg"></i>
            <span>Back to Settings</span>
        </a>
        <div class="flex items-center gap-2 text-xs">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-50 text-blue-700 font-medium border border-blue-100">
                <i class="ph-bold ph-shield-check"></i> Official Steadfast API v1
            </span>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-pink-50 text-pink-700 font-medium border border-pink-100">
                <i class="ph-bold ph-git-fork"></i> Automated Webhook Enabled
            </span>
        </div>
    </div>

    <!-- Top Tabs System -->
    <div class="flex items-center gap-2 border-b border-gray-200">
        <button type="button" 
                @click="activeTab = 'config'" 
                class="px-5 py-3 text-sm font-bold border-b-2 flex items-center gap-2 transition-all cursor-pointer"
                :class="activeTab === 'config' ? 'border-brand-600 text-brand-600 bg-brand-50/50 rounded-t-xl' : 'border-transparent text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-t-xl'">
            <i class="ph-bold ph-sliders text-base"></i>
            <span>Configuration & Credentials</span>
        </button>

        <button type="button" 
                @click="activeTab = 'logs'; if (!logLoaded) fetchLog();" 
                class="px-5 py-3 text-sm font-bold border-b-2 flex items-center gap-2 transition-all cursor-pointer relative"
                :class="activeTab === 'logs' ? 'border-brand-600 text-brand-600 bg-brand-50/50 rounded-t-xl' : 'border-transparent text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-t-xl'">
            <i class="ph-bold ph-terminal-window text-base"></i>
            <span>Error & Webhook Logs</span>
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-mono font-bold"
                  :class="activeTab === 'logs' ? 'bg-brand-100 text-brand-700' : 'bg-gray-100 text-gray-600'"
                  x-text="logMeta.lines + ' lines'"></span>
        </button>
    </div>

    <!-- ==================== TAB 1: CONFIGURATION ==================== -->
    <div x-show="activeTab === 'config'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="space-y-6">
        
        <!-- Quick Status Cards (Live balance auto-loads upon page entry!) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <!-- API Connection Card -->
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl"
                         :class="pingStatus.success ? 'bg-emerald-50 text-emerald-600' : (pingStatus.tested ? 'bg-red-50 text-red-600' : 'bg-gray-50 text-gray-400')">
                        <i :class="pingStatus.loading ? 'ph-bold ph-spinner animate-spin text-brand-600' : (pingStatus.success ? 'ph-fill ph-check-circle' : 'ph ph-broadcast')"></i>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Steadfast Server</p>
                        <h4 class="text-sm font-bold text-gray-800" x-text="pingStatus.loading ? 'Pinging...' : (pingStatus.tested ? (pingStatus.success ? 'Online & Reachable' : 'Offline / Failed') : 'Ready to Test')"></h4>
                        <p class="text-[11px] text-gray-500" x-show="pingStatus.latency !== null">
                            Latency: <span class="font-semibold text-emerald-600" x-text="pingStatus.latency + ' ms'"></span>
                        </p>
                    </div>
                </div>
                <button type="button" @click="testPing()" :disabled="pingStatus.loading" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition flex items-center gap-1 cursor-pointer disabled:opacity-50">
                    <i class="ph ph-arrows-clockwise" :class="{'animate-spin': pingStatus.loading}"></i>
                    <span>Ping</span>
                </button>
            </div>

            <!-- Current Balance Card (Auto-called on page entry!) -->
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl bg-brand-50 text-brand-600">
                        <i :class="balanceStatus.loading ? 'ph-bold ph-spinner animate-spin' : 'ph-bold ph-wallet'"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Courier Balance</p>
                            <span x-show="balanceStatus.autoFetched" class="w-2 h-2 rounded-full bg-emerald-500" title="Live Auto-Loaded"></span>
                        </div>
                        <h4 class="text-base font-extrabold text-gray-800" x-text="balanceStatus.loading ? 'Loading...' : (balanceStatus.balance !== null ? '৳ ' + Number(balanceStatus.balance).toLocaleString('en-US', {minimumFractionDigits: 2}) : '—')"></h4>
                        <p class="text-[11px] text-gray-500" x-text="balanceStatus.loading ? 'Fetching live balance...' : 'Delivered COD payout ready'"></p>
                    </div>
                </div>
                <button type="button" @click="fetchBalance(false)" :disabled="balanceStatus.loading" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-brand-50 hover:bg-brand-100 text-brand-700 transition flex items-center gap-1 cursor-pointer disabled:opacity-50" title="Refresh Live Balance">
                    <i class="ph ph-arrows-clockwise" :class="{'animate-spin': balanceStatus.loading}"></i>
                    <span>Refresh</span>
                </button>
            </div>

            <!-- Configuration State Card -->
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl"
                         :class="apiKey.length > 5 && secretKey.length > 5 ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'">
                        <i :class="apiKey.length > 5 && secretKey.length > 5 ? 'ph-bold ph-key' : 'ph-bold ph-warning'"></i>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Credential Status</p>
                        <h4 class="text-sm font-bold text-gray-800" x-text="apiKey.length > 5 && secretKey.length > 5 ? 'Keys Configured' : 'Keys Missing'"></h4>
                        <p class="text-[11px] text-gray-500" x-text="apiKey.length > 5 && secretKey.length > 5 ? 'Ready for auto booking' : 'Enter API & Secret keys'"></p>
                    </div>
                </div>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold"
                      :class="apiKey.length > 5 && secretKey.length > 5 ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'">
                    <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="apiKey.length > 5 && secretKey.length > 5 ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                    <span x-text="apiKey.length > 5 && secretKey.length > 5 ? 'Active' : 'Incomplete'"></span>
                </span>
            </div>
        </div>

        <!-- Main Configuration Form Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Form Left: 2 Columns -->
            <div class="lg:col-span-2">
                <form @submit.prevent="saveSettings" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-6">
                    @csrf
                    <div class="border-b border-gray-100 pb-4 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                                <i class="ph-bold ph-sliders text-brand-600"></i>
                                API Credentials & Webhook Settings
                            </h3>
                            <p class="text-xs text-gray-500 mt-0.5">Collect your Api-Key and Secret-Key from your Steadfast merchant portal.</p>
                        </div>
                    </div>

                    <!-- API Key -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                            Steadfast API Key <span class="text-red-500">*</span>
                        </label>
                        <div class="relative rounded-xl shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <i class="ph ph-key text-lg"></i>
                            </div>
                            <input type="text" 
                                   name="steadfast_api_key" 
                                   x-model="apiKey" 
                                   class="w-full pl-10 pr-4 py-2.5 text-sm rounded-xl border border-gray-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 font-mono transition-colors" 
                                   placeholder="e.g. qdrng9p340hmzaiyeoxw8z4ovzf1g1qh" 
                                   required>
                        </div>
                        <p class="text-[11px] text-gray-500 mt-1">Provided in your Steadfast Portal &gt; Settings &gt; API Information.</p>
                    </div>

                    <!-- Secret Key -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                            Steadfast Secret Key <span class="text-red-500">*</span>
                        </label>
                        <div class="relative rounded-xl shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <i class="ph ph-lock-key text-lg"></i>
                            </div>
                            <input :type="showSecret ? 'text' : 'password'" 
                                   name="steadfast_secret_key" 
                                   x-model="secretKey" 
                                   class="w-full pl-10 pr-10 py-2.5 text-sm rounded-xl border border-gray-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 font-mono transition-colors" 
                                   placeholder="e.g. u8fi8aaitrol4wcdbm8hidxx" 
                                   required>
                            <button type="button" 
                                    @click="showSecret = !showSecret" 
                                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 transition-colors cursor-pointer">
                                <i class="ph text-lg" :class="showSecret ? 'ph-eye-slash' : 'ph-eye'"></i>
                            </button>
                        </div>
                        <p class="text-[11px] text-gray-500 mt-1">Keep this strictly secret. Used to sign and authorize parcel bookings.</p>
                    </div>

                    <!-- Steadfast Merchant ID -->
                    <div class="p-4 rounded-xl bg-pink-50/50 border border-pink-100 space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-800 flex items-center gap-1.5">
                                <i class="ph-bold ph-identification-card text-pink-600 text-base"></i>
                                Steadfast Merchant ID
                            </label>
                            <span class="text-[10px] font-bold uppercase tracking-wider bg-pink-100 text-pink-700 px-2 py-0.5 rounded-full">Courier Ref</span>
                        </div>
                        <div class="relative rounded-xl shadow-2xs">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <i class="ph ph-hash text-lg"></i>
                            </div>
                            <input type="text" 
                                   name="steadfast_merchant_id" 
                                   x-model="merchantId" 
                                   class="w-full pl-10 pr-4 py-2.5 text-sm rounded-xl border border-gray-200 focus:border-pink-500 focus:ring-2 focus:ring-pink-500/20 font-mono transition-colors bg-white text-gray-900" 
                                   placeholder="e.g. 24891">
                        </div>
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pt-2 border-t border-pink-100">
                            <p class="text-[11px] text-gray-600">
                                Used when communicating with Steadfast support or riders. Changing requires math verification.
                            </p>
                            <!-- Show / Hide Toggle in Header -->
                            <label class="flex items-center gap-2 cursor-pointer select-none shrink-0">
                                <input type="checkbox" 
                                       name="steadfast_show_merchant_id_in_header" 
                                       x-model="showMerchantIdInHeader" 
                                       class="w-4 h-4 rounded text-pink-600 focus:ring-pink-500 border-gray-300 cursor-pointer">
                                <span class="text-xs font-bold text-gray-700">Show in Dashboard Header</span>
                            </label>
                        </div>
                    </div>

                    <!-- Webhook Auth Token -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                            Webhook Auth Token (Bearer Token / Secret)
                        </label>
                        <div class="relative rounded-xl shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <i class="ph ph-shield-check text-lg"></i>
                            </div>
                            <input type="text" 
                                   name="steadfast_webhook_token" 
                                   x-model="webhookToken" 
                                   class="w-full pl-10 pr-24 py-2.5 text-sm rounded-xl border border-gray-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 font-mono transition-colors" 
                                   placeholder="Enter or generate secret token">
                            <button type="button" 
                                    @click="openGenerateTokenModal()" 
                                    class="absolute inset-y-1 right-1 px-3 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-lg transition-colors flex items-center gap-1 cursor-pointer">
                                <i class="ph ph-sparkle"></i> Generate
                            </button>
                        </div>
                        <p class="text-[11px] text-gray-500 mt-1">Copy and paste this token into the Steadfast portal webhook settings as the Auth Bearer token.</p>
                    </div>

                    <!-- Base URL -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700">
                                Base API URL
                            </label>
                            <button type="button" @click="baseUrl = 'https://portal.packzy.com/api/v1'" class="text-[11px] text-brand-600 hover:underline cursor-pointer">Reset to default</button>
                        </div>
                        <div class="relative rounded-xl shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <i class="ph ph-globe text-lg"></i>
                            </div>
                            <input type="text" 
                                   name="steadfast_base_url" 
                                   x-model="baseUrl" 
                                   class="w-full pl-10 pr-4 py-2.5 text-sm rounded-xl border border-gray-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 font-mono transition-colors" 
                                   placeholder="https://portal.packzy.com/api/v1">
                        </div>
                        <p class="text-[11px] text-gray-500 mt-1">Default: <code class="bg-gray-100 px-1 py-0.5 rounded text-gray-800">https://portal.packzy.com/api/v1</code></p>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="submit" 
                                :disabled="saving" 
                                class="px-6 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-semibold rounded-xl transition-all shadow-sm shadow-brand-500/30 flex items-center gap-2 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                            <i x-show="!saving" class="ph-bold ph-floppy-disk text-lg"></i>
                            <i x-show="saving" class="ph-bold ph-spinner animate-spin text-lg" style="display: none;"></i>
                            <span x-text="saving ? 'Saving Changes...' : 'Save Configuration'"></span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Sidebar Right: Webhook Callback URL & Testing Details -->
            <div class="space-y-6">
                <!-- Webhook Callback URL Card -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-4">
                    <div class="flex items-center gap-2 text-brand-600 font-bold text-sm">
                        <i class="ph-bold ph-git-fork text-lg"></i>
                        <h4>Webhook Callback URL</h4>
                    </div>
                    <p class="text-xs text-gray-600 leading-relaxed">
                        Copy this callback URL and paste it into the Steadfast merchant portal Webhook settings to receive automatic parcel tracking & status updates.
                    </p>

                    <div class="p-3 bg-gray-50 rounded-xl border border-gray-200">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1">Your Callback URL</label>
                        <div class="flex items-center gap-2">
                            <input type="text" 
                                   readonly 
                                   value="{{ $callbackUrl }}" 
                                   id="callbackUrlInput" 
                                   class="w-full text-xs font-mono bg-white border border-gray-200 rounded-lg px-2.5 py-2 text-gray-800 focus:outline-none select-all">
                            <button type="button" 
                                    @click="copyCallbackUrl()" 
                                    class="px-3 py-2 bg-gray-900 hover:bg-gray-800 text-white rounded-lg text-xs font-semibold flex items-center gap-1 cursor-pointer transition shrink-0">
                                <i :class="copiedCallback ? 'ph-bold ph-check text-green-400' : 'ph ph-copy'"></i>
                                <span x-text="copiedCallback ? 'Copied' : 'Copy'"></span>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-2 text-xs text-gray-500 pt-2 border-t border-gray-100">
                        <div class="flex items-start gap-2">
                            <i class="ph-fill ph-check-circle text-emerald-500 text-sm mt-0.5 shrink-0"></i>
                            <span>Method: <strong>POST (JSON)</strong></span>
                        </div>
                        <div class="flex items-start gap-2">
                            <i class="ph-fill ph-check-circle text-emerald-500 text-sm mt-0.5 shrink-0"></i>
                            <span>Authentication: <strong>Bearer Token / X-Signature</strong></span>
                        </div>
                        <div class="flex items-start gap-2">
                            <i class="ph-fill ph-check-circle text-emerald-500 text-sm mt-0.5 shrink-0"></i>
                            <span>CSRF Check: <strong class="text-emerald-700">Exempted & Live</strong></span>
                        </div>
                        <div class="flex items-start gap-2">
                            <i class="ph-fill ph-check-circle text-emerald-500 text-sm mt-0.5 shrink-0"></i>
                            <span>Safety: <strong>Safe exception logging to steadfast.log</strong></span>
                        </div>
                    </div>
                </div>

                <!-- Diagnostics & Test Result Box -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-4">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                            <i class="ph-bold ph-activity text-brand-600"></i>
                            Live Diagnostics
                        </h4>
                        <span class="text-[11px] text-gray-400">Server test</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" 
                                @click="testPing()" 
                                :disabled="pingStatus.loading" 
                                class="w-full py-2.5 px-3 bg-gray-50 hover:bg-gray-100 text-gray-800 border border-gray-200 rounded-xl text-xs font-semibold flex flex-col items-center justify-center gap-1 cursor-pointer transition disabled:opacity-50">
                            <i class="ph-bold ph-broadcast text-brand-600 text-lg" :class="{'animate-pulse': pingStatus.loading}"></i>
                            <span x-text="pingStatus.loading ? 'Pinging...' : 'Test Ping'"></span>
                        </button>

                        <button type="button" 
                                @click="fetchBalance(false)" 
                                :disabled="balanceStatus.loading" 
                                class="w-full py-2.5 px-3 bg-brand-50 hover:bg-brand-100 text-brand-800 border border-brand-200 rounded-xl text-xs font-semibold flex flex-col items-center justify-center gap-1 cursor-pointer transition disabled:opacity-50">
                            <i class="ph-bold ph-currency-circle-dollar text-brand-600 text-lg" :class="{'animate-spin': balanceStatus.loading}"></i>
                            <span x-text="balanceStatus.loading ? 'Fetching...' : 'Get Balance'"></span>
                        </button>
                    </div>

                    <!-- Last diagnostic log output -->
                    <div x-show="lastResponse.text" class="mt-3 pt-3 border-t border-gray-100" style="display: none;">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500" x-text="lastResponse.action + ' Response:'"></span>
                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full"
                                  :class="lastResponse.success ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'"
                                  x-text="lastResponse.success ? 'Success (' + lastResponse.status + ')' : 'Failed (' + lastResponse.status + ')'"></span>
                        </div>
                        <pre class="bg-gray-900 text-gray-100 p-3 rounded-xl text-[11px] font-mono overflow-x-auto max-h-48 whitespace-pre-wrap" x-text="lastResponse.text"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== TAB 2: ERROR & WEBHOOK LOGS ==================== -->
    <div x-show="activeTab === 'logs'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="space-y-6" style="display: none;">
        
        <!-- Metadata Bar & Action Buttons -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex flex-wrap items-center justify-between gap-4">
            
            <!-- File Info Badges -->
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-gray-100 text-gray-700 text-xs font-bold font-mono">
                    <i class="ph ph-file-text text-gray-500"></i>
                    storage/logs/steadfast.log
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700 text-xs font-bold">
                    <i class="ph ph-database text-blue-500"></i>
                    <span x-text="logMeta.size"></span>
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-purple-50 text-purple-700 text-xs font-bold">
                    <i class="ph ph-list-numbers text-purple-500"></i>
                    <span x-text="logMeta.lines"></span> lines
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-50 text-amber-700 text-xs font-medium">
                    <i class="ph ph-clock text-amber-500"></i>
                    Modified: <span x-text="logMeta.modified"></span>
                </span>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2">
                <!-- Search within log -->
                <div class="relative hidden sm:block">
                    <input type="text" 
                           x-model="logSearch" 
                           placeholder="Filter lines..." 
                           class="pl-8 pr-3 py-1.5 text-xs bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500 w-40 font-mono">
                    <i class="ph ph-magnifying-glass absolute left-2.5 top-2 text-gray-400 text-xs"></i>
                </div>

                <!-- Reload Button -->
                <button type="button" 
                        @click="fetchLog()" 
                        :disabled="isFetchingLog" 
                        class="p-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition-colors cursor-pointer"
                        title="Reload Log">
                    <i class="ph ph-arrows-clockwise text-base" :class="{'animate-spin': isFetchingLog}"></i>
                </button>

                <!-- Download Button -->
                <a href="{{ route('admin.settings.steadfast.log.download') }}" 
                   class="inline-flex items-center gap-1.5 px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition-colors cursor-pointer">
                    <i class="ph ph-download-simple text-base"></i>
                    <span>Download</span>
                </a>

                <!-- Clear Log Button -->
                <button type="button" 
                        @click="clearLog()" 
                        :disabled="isClearingLog || isSavingLog" 
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-red-50 hover:bg-red-100 text-red-700 rounded-xl text-xs font-bold transition-colors cursor-pointer disabled:opacity-50">
                    <i class="ph ph-trash text-base"></i>
                    <span x-text="isClearingLog ? 'Clearing...' : 'Clear Log'"></span>
                </button>

                <!-- Save Changes Button -->
                <button type="button" 
                        @click="saveLog()" 
                        :disabled="isSavingLog || isClearingLog" 
                        class="inline-flex items-center gap-2 px-5 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-xl text-xs font-black shadow-xs transition-all cursor-pointer disabled:opacity-50">
                    <i class="ph ph-floppy-disk text-base" x-show="!isSavingLog"></i>
                    <i class="ph ph-spinner animate-spin text-base" x-show="isSavingLog" style="display: none;"></i>
                    <span x-text="isSavingLog ? 'Saving...' : 'Save Changes'"></span>
                </button>
            </div>
        </div>

        <!-- Terminal Code Editor Canvas -->
        <div class="bg-[#0D1117] rounded-2xl border border-gray-800 shadow-xl overflow-hidden flex flex-col">
            <!-- Editor Top Header Bar -->
            <div class="bg-[#161B22] px-4 py-2.5 border-b border-gray-800 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-red-500/80 inline-block"></span>
                    <span class="w-3 h-3 rounded-full bg-amber-500/80 inline-block"></span>
                    <span class="w-3 h-3 rounded-full bg-emerald-500/80 inline-block"></span>
                    <span class="ml-2 font-mono text-xs text-gray-400 font-semibold tracking-wider">
                        storage/logs/<span class="text-white">steadfast.log</span>
                    </span>
                </div>

                <!-- Editor Helper Controls -->
                <div class="flex items-center gap-2 sm:gap-3 text-xs text-gray-400">
                    <button type="button" 
                            @click="scrollToTop()" 
                            class="p-1 px-2 rounded bg-gray-800 hover:bg-gray-700 text-gray-300 transition-colors flex items-center gap-1 text-[11px] cursor-pointer"
                            title="Scroll to Top">
                        <i class="ph ph-arrow-up"></i> Top
                    </button>
                    <button type="button" 
                            @click="scrollToBottom()" 
                            class="p-1 px-2 rounded bg-gray-800 hover:bg-gray-700 text-gray-300 transition-colors flex items-center gap-1 text-[11px] cursor-pointer"
                            title="Scroll to Bottom">
                        <i class="ph ph-arrow-down"></i> Bottom
                    </button>
                    <span class="text-gray-700">|</span>
                    <label class="flex items-center gap-1.5 cursor-pointer hover:text-gray-200 select-none">
                        <input type="checkbox" x-model="wrapLines" class="rounded bg-gray-800 border-gray-700 text-brand-600 focus:ring-0 cursor-pointer">
                        <span>Wrap Lines</span>
                    </label>
                    <span class="text-gray-700 hidden sm:inline">|</span>
                    <span class="text-[11px] text-gray-500 font-mono hidden sm:inline">Shortcut: <kbd class="px-1.5 py-0.5 rounded bg-gray-800 text-gray-300 border border-gray-700">Ctrl + S</kbd> to save</span>
                </div>
            </div>

            <!-- Textarea Container -->
            <div class="relative w-full">
                <textarea x-ref="logTextarea" 
                          x-model="logContent" 
                          @input="onContentChange()" 
                          @keydown.window.ctrl.s.prevent="saveLog()" 
                          @keydown.window.meta.s.prevent="saveLog()" 
                          rows="24" 
                          :class="wrapLines ? 'whitespace-pre-wrap' : 'whitespace-pre overflow-x-auto'" 
                          placeholder="Steadfast log is currently empty. Incoming webhook requests, errors, and delivery updates will appear here in real-time."
                          class="w-full bg-[#0D1117] text-[#7EE787] font-mono text-xs p-4 sm:p-5 outline-none border-0 focus:ring-0 resize-y leading-relaxed selection:bg-brand-600 selection:text-white"
                          style="min-height: 480px; tab-size: 4;"></textarea>
            </div>

            <!-- Terminal Footer Info -->
            <div class="bg-[#161B22] px-4 py-2 border-t border-gray-800/80 flex items-center justify-between text-[11px] font-mono text-gray-400">
                <div class="flex items-center gap-4">
                    <span>Lines: <strong class="text-white" x-text="logMeta.lines"></strong></span>
                    <span>Size: <strong class="text-white" x-text="logMeta.size"></strong></span>
                    <span class="text-emerald-400 flex items-center gap-1">
                        <i class="ph-bold ph-shield-check"></i> Crash-Safe Webhook Active
                    </span>
                </div>
                <div class="text-gray-500 hidden sm:block">
                    Logs include incoming payload, signature checks & profit breakdowns in BDT time.
                </div>
            </div>
        </div>
    </div>

    <!-- Regenerate Webhook Token Confirmation Modal with Math Captcha -->
    <div x-show="showTokenModal" 
         style="display: none;" 
         class="fixed inset-0 z-[100] overflow-y-auto" 
         aria-labelledby="token-modal-title" 
         role="dialog" 
         aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
            <!-- Backdrop -->
            <div x-show="showTokenModal" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0" 
                 x-transition:enter-end="opacity-100" 
                 x-transition:leave="ease-in duration-200" 
                 x-transition:leave-start="opacity-100" 
                 x-transition:leave-end="opacity-0" 
                 class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity cursor-pointer" 
                 @click="showTokenModal = false"></div>

            <!-- Modal Card -->
            <div x-show="showTokenModal" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave="ease-in duration-200" 
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                 class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-gray-100 p-6 z-10">
                
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-2xl shrink-0 border border-amber-100">
                        <i class="ph-bold ph-shield-warning"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900" id="token-modal-title">
                            Regenerate Webhook Auth Token?
                        </h3>
                        <p class="text-xs text-gray-600 mt-1 leading-relaxed">
                            Generating a new token will invalidate your current webhook connection. Any automated delivery callbacks from Steadfast will fail with <strong class="text-red-600">401 Unauthorized</strong> until you copy and paste this new token into the Steadfast Merchant Portal.
                        </p>
                    </div>
                </div>

                <!-- Math Captcha Challenge Box -->
                <div class="mt-5 p-4 rounded-xl bg-gray-50 border border-gray-200 space-y-3">
                    <div class="flex items-center justify-between">
                        <label for="tokenCaptchaInput" class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="ph-bold ph-calculator text-brand-600"></i>
                            Security Verification
                        </label>
                        <button type="button" 
                                @click="refreshCaptcha()" 
                                class="text-xs text-brand-600 hover:text-brand-800 font-medium inline-flex items-center gap-1 cursor-pointer">
                            <i class="ph ph-arrows-clockwise"></i> New Problem
                        </button>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="px-4 py-2 bg-white rounded-lg border border-gray-300 font-mono text-base font-bold text-gray-800 shadow-2xs tracking-wider select-none shrink-0">
                            <span x-text="captchaNum1"></span> + <span x-text="captchaNum2"></span> = ?
                        </div>
                        <input type="number" 
                               id="tokenCaptchaInput" 
                               x-model="captchaAnswer" 
                               @keydown.enter.prevent="confirmGenerateToken()" 
                               placeholder="Answer" 
                               class="w-full px-3 py-2 text-sm font-bold font-mono bg-white border border-gray-300 rounded-lg focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-gray-900">
                    </div>

                    <p x-show="captchaError" x-text="captchaError" class="text-xs text-red-600 font-semibold" style="display: none;"></p>
                    <p class="text-[11px] text-gray-500">Solve this simple math challenge to unlock token regeneration.</p>
                </div>

                <!-- Modal Actions -->
                <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" 
                            @click="showTokenModal = false" 
                            class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-700 hover:bg-gray-100 border border-gray-200 transition-colors cursor-pointer">
                        Cancel
                    </button>
                    <button type="button" 
                            @click="confirmGenerateToken()" 
                            class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 shadow-xs transition-colors inline-flex items-center gap-1.5 cursor-pointer">
                        <i class="ph-bold ph-key"></i>
                        <span>Confirm & Generate Token</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirm Merchant ID Change Modal with Math Captcha -->
    <div x-show="showMerchantModal" 
         style="display: none;" 
         class="fixed inset-0 z-[100] overflow-y-auto" 
         aria-labelledby="merchant-modal-title" 
         role="dialog" 
         aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
            <!-- Backdrop -->
            <div x-show="showMerchantModal" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0" 
                 x-transition:enter-end="opacity-100" 
                 x-transition:leave="ease-in duration-200" 
                 x-transition:leave-start="opacity-100" 
                 x-transition:leave-end="opacity-0" 
                 class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity cursor-pointer" 
                 @click="showMerchantModal = false"></div>

            <!-- Modal Card -->
            <div x-show="showMerchantModal" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave="ease-in duration-200" 
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                 class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-gray-100 p-6 z-10">
                
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-pink-50 text-pink-600 flex items-center justify-center text-2xl shrink-0 border border-pink-100">
                        <i class="ph-bold ph-identification-card"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900" id="merchant-modal-title">
                            Confirm Merchant ID Change
                        </h3>
                        <p class="text-xs text-gray-600 mt-1 leading-relaxed">
                            You are changing your Steadfast Merchant ID from 
                            <strong class="font-mono text-gray-800" x-text="initialMerchantId || 'Empty'"></strong> to 
                            <strong class="font-mono text-pink-600" x-text="merchantId || 'Empty'"></strong>. 
                            Solve the math verification below to confirm.
                        </p>
                    </div>
                </div>

                <!-- Math Captcha Challenge Box -->
                <div class="mt-5 p-4 rounded-xl bg-gray-50 border border-gray-200 space-y-3">
                    <div class="flex items-center justify-between">
                        <label for="merchantCaptchaInput" class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="ph-bold ph-calculator text-pink-600"></i>
                            Security Verification
                        </label>
                        <button type="button" 
                                @click="refreshMerchantCaptcha()" 
                                class="text-xs text-pink-600 hover:text-pink-800 font-medium inline-flex items-center gap-1 cursor-pointer">
                            <i class="ph ph-arrows-clockwise"></i> New Problem
                        </button>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="px-4 py-2 bg-white rounded-lg border border-gray-300 font-mono text-base font-bold text-gray-800 shadow-2xs tracking-wider select-none shrink-0">
                            <span x-text="merchantCaptchaNum1"></span> + <span x-text="merchantCaptchaNum2"></span> = ?
                        </div>
                        <input type="number" 
                               id="merchantCaptchaInput" 
                               x-model="merchantCaptchaAnswer" 
                               @keydown.enter.prevent="confirmMerchantIdChange()" 
                               placeholder="Answer" 
                               class="w-full px-3 py-2 text-sm font-bold font-mono bg-white border border-gray-300 rounded-lg focus:border-pink-500 focus:ring-2 focus:ring-pink-500/20 text-gray-900">
                    </div>

                    <p x-show="merchantCaptchaError" x-text="merchantCaptchaError" class="text-xs text-red-600 font-semibold" style="display: none;"></p>
                    <p class="text-[11px] text-gray-500">Solve this 1-digit calculation to authorize updating your Merchant ID.</p>
                </div>

                <!-- Modal Actions -->
                <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" 
                            @click="showMerchantModal = false" 
                            class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-700 hover:bg-gray-100 border border-gray-200 transition-colors cursor-pointer">
                        Cancel
                    </button>
                    <button type="button" 
                            @click="confirmMerchantIdChange()" 
                            class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-pink-600 hover:bg-pink-700 shadow-xs transition-colors inline-flex items-center gap-1.5 cursor-pointer">
                        <i class="ph-bold ph-check"></i>
                        <span>Confirm &amp; Save</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function steadfastSettings() {
    return {
        activeTab: 'config',
        apiKey: @json($apiKey),
        secretKey: @json($secretKey),
        merchantId: @json($merchantId),
        initialMerchantId: @json($merchantId),
        showMerchantIdInHeader: {{ $showMerchantIdInHeader ? 'true' : 'false' }},
        merchantIdConfirmed: false,
        webhookToken: @json($webhookToken),
        baseUrl: @json($baseUrl),
        showSecret: false,
        saving: false,
        copiedCallback: false,
        wrapLines: true,

        // Webhook Token Regeneration Confirmation & Captcha State
        showTokenModal: false,
        captchaNum1: 0,
        captchaNum2: 0,
        captchaExpected: 0,
        captchaAnswer: '',
        captchaError: '',

        // Merchant ID Change Confirmation & Captcha State
        showMerchantModal: false,
        merchantCaptchaNum1: 0,
        merchantCaptchaNum2: 0,
        merchantCaptchaExpected: 0,
        merchantCaptchaAnswer: '',
        merchantCaptchaError: '',

        // Log Data State
        logContent: @json($logContent),
        logMeta: @json($logMeta),
        logSearch: '',
        logLoaded: true,
        isSavingLog: false,
        isClearingLog: false,
        isFetchingLog: false,

        toast: {
            show: false,
            message: '',
            type: 'success'
        },
        pingStatus: {
            loading: false,
            tested: false,
            success: false,
            latency: null
        },
        balanceStatus: {
            loading: false,
            balance: null,
            autoFetched: false
        },
        lastResponse: {
            action: '',
            success: false,
            status: '',
            text: ''
        },

        init() {
            // Auto fetch balance on page entry without requiring manual click!
            if (this.apiKey && this.apiKey.length > 5 && this.secretKey && this.secretKey.length > 5) {
                this.fetchBalance(true);
            }
        },

        triggerToast(msg, type = 'success') {
            this.toast.message = msg;
            this.toast.type = type;
            this.toast.show = true;
            setTimeout(() => { this.toast.show = false; }, 4000);
        },

        copyCallbackUrl() {
            const input = document.getElementById('callbackUrlInput');
            if (input) {
                input.select();
                navigator.clipboard.writeText(input.value).then(() => {
                    this.copiedCallback = true;
                    this.triggerToast('Webhook URL copied to clipboard!');
                    setTimeout(() => { this.copiedCallback = false; }, 2000);
                });
            }
        },

        openGenerateTokenModal() {
            this.refreshCaptcha();
            this.captchaAnswer = '';
            this.captchaError = '';
            this.showTokenModal = true;
            this.$nextTick(() => {
                const el = document.getElementById('tokenCaptchaInput');
                if (el) el.focus();
            });
        },

        refreshCaptcha() {
            this.captchaNum1 = Math.floor(Math.random() * 20) + 5;
            this.captchaNum2 = Math.floor(Math.random() * 15) + 3;
            this.captchaExpected = this.captchaNum1 + this.captchaNum2;
            this.captchaAnswer = '';
            this.captchaError = '';
        },

        confirmGenerateToken() {
            const answer = parseInt(this.captchaAnswer, 10);
            if (isNaN(answer) || answer !== this.captchaExpected) {
                this.captchaError = 'Incorrect calculation answer! Please solve correctly.';
                return;
            }

            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';
            let token = '';
            for (let i = 0; i < 32; i++) {
                token += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            this.webhookToken = token;
            this.showTokenModal = false;
            this.captchaAnswer = '';
            this.captchaError = '';
            this.triggerToast('New webhook auth token generated! Remember to click "Save Configuration" & update Steadfast portal.');
        },

        openMerchantModal() {
            this.refreshMerchantCaptcha();
            this.merchantCaptchaAnswer = '';
            this.merchantCaptchaError = '';
            this.showMerchantModal = true;
            this.$nextTick(() => {
                const el = document.getElementById('merchantCaptchaInput');
                if (el) el.focus();
            });
        },

        refreshMerchantCaptcha() {
            this.merchantCaptchaNum1 = Math.floor(Math.random() * 9) + 1;
            this.merchantCaptchaNum2 = Math.floor(Math.random() * 9) + 1;
            this.merchantCaptchaExpected = this.merchantCaptchaNum1 + this.merchantCaptchaNum2;
            this.merchantCaptchaAnswer = '';
            this.merchantCaptchaError = '';
        },

        confirmMerchantIdChange() {
            const answer = parseInt(this.merchantCaptchaAnswer, 10);
            if (isNaN(answer) || answer !== this.merchantCaptchaExpected) {
                this.merchantCaptchaError = 'Incorrect calculation answer! Please solve correctly.';
                return;
            }
            this.merchantIdConfirmed = true;
            this.showMerchantModal = false;
            this.merchantCaptchaAnswer = '';
            this.merchantCaptchaError = '';
            this.saveSettings();
        },

        async saveSettings() {
            if (this.saving) return;

            // Check if Merchant ID was modified and needs confirmation
            if (this.merchantId !== this.initialMerchantId && !this.merchantIdConfirmed) {
                this.openMerchantModal();
                return;
            }

            this.saving = true;

            try {
                const response = await fetch("{{ route('admin.settings.steadfast.update') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        steadfast_api_key: this.apiKey,
                        steadfast_secret_key: this.secretKey,
                        steadfast_merchant_id: this.merchantId,
                        steadfast_show_merchant_id_in_header: this.showMerchantIdInHeader ? 1 : 0,
                        steadfast_webhook_token: this.webhookToken,
                        steadfast_base_url: this.baseUrl
                    })
                });

                const data = await response.json();
                if (response.ok) {
                    this.initialMerchantId = this.merchantId;
                    this.merchantIdConfirmed = false;
                    this.triggerToast(data.message || 'Steadfast settings saved successfully!');
                } else {
                    this.triggerToast(data.message || 'Validation failed while saving.', 'error');
                }
            } catch (err) {
                this.triggerToast('An error occurred while saving: ' + err.message, 'error');
            } finally {
                this.saving = false;
            }
        },

        async testPing() {
            if (this.pingStatus.loading) return;
            this.pingStatus.loading = true;

            try {
                const response = await fetch("{{ route('admin.settings.steadfast.ping') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                const res = await response.json();
                this.pingStatus.tested = true;
                this.pingStatus.success = res.success;
                this.pingStatus.latency = res.latency;

                this.lastResponse = {
                    action: 'Ping',
                    success: res.success,
                    status: res.status || (res.success ? 200 : 500),
                    text: JSON.stringify(res, null, 2)
                };

                if (res.success) {
                    this.triggerToast('Steadfast ping succeeded! (' + res.latency + 'ms)');
                } else {
                    this.triggerToast('Ping failed: ' + res.message, 'error');
                }
            } catch (err) {
                this.pingStatus.tested = true;
                this.pingStatus.success = false;
                this.triggerToast('Failed to ping Steadfast: ' + err.message, 'error');
            } finally {
                this.pingStatus.loading = false;
            }
        },

        async fetchBalance(silent = false) {
            if (this.balanceStatus.loading) return;
            this.balanceStatus.loading = true;

            try {
                const response = await fetch("{{ route('admin.settings.steadfast.balance') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                const res = await response.json();
                this.lastResponse = {
                    action: 'Get Balance',
                    success: res.success,
                    status: res.status || (res.success ? 200 : 500),
                    text: JSON.stringify(res, null, 2)
                };

                if (res.success) {
                    this.balanceStatus.balance = res.current_balance;
                    this.balanceStatus.autoFetched = true;
                    if (!silent) {
                        this.triggerToast('Balance loaded: ৳ ' + res.current_balance);
                    }
                } else {
                    if (!silent) {
                        this.triggerToast('Failed to fetch balance: ' + res.message, 'error');
                    }
                }
            } catch (err) {
                if (!silent) {
                    this.triggerToast('Balance request error: ' + err.message, 'error');
                }
            } finally {
                this.balanceStatus.loading = false;
            }
        },

        // ================= Log Management Methods =================
        async fetchLog() {
            if (this.isFetchingLog) return;
            this.isFetchingLog = true;

            try {
                const response = await fetch("{{ route('admin.settings.steadfast.log.content') }}", {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    this.logContent = data.content;
                    this.logMeta = data.meta;
                    this.triggerToast('Steadfast log reloaded successfully!');
                }
            } catch (e) {
                this.triggerToast('Failed to reload log: ' + e.message, 'error');
            } finally {
                this.isFetchingLog = false;
            }
        },

        async saveLog() {
            if (this.isSavingLog) return;
            this.isSavingLog = true;

            try {
                const response = await fetch("{{ route('admin.settings.steadfast.log.save') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        content: this.logContent
                    })
                });

                const data = await response.json();
                if (data.success) {
                    this.logMeta = data.meta;
                    this.triggerToast(data.message || 'Steadfast log saved successfully!');
                } else {
                    this.triggerToast(data.message || 'Failed to save log.', 'error');
                }
            } catch (e) {
                this.triggerToast('Error saving log: ' + e.message, 'error');
            } finally {
                this.isSavingLog = false;
            }
        },

        async clearLog() {
            if (!confirm('Are you sure you want to clear the entire steadfast.log file? This cannot be undone.')) {
                return;
            }

            if (this.isClearingLog) return;
            this.isClearingLog = true;

            try {
                const response = await fetch("{{ route('admin.settings.steadfast.log.clear') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();
                if (data.success) {
                    this.logContent = '';
                    this.logMeta = data.meta;
                    this.triggerToast('Steadfast log cleared successfully!');
                } else {
                    this.triggerToast(data.message || 'Failed to clear log.', 'error');
                }
            } catch (e) {
                this.triggerToast('Error clearing log: ' + e.message, 'error');
            } finally {
                this.isClearingLog = false;
            }
        },

        onContentChange() {
            const lines = (this.logContent.match(/\n/g) || []).length + (this.logContent.length > 0 ? 1 : 0);
            this.logMeta.lines = lines;
            const bytes = new Blob([this.logContent]).size;
            this.logMeta.size = this.formatBytes(bytes);
        },

        formatBytes(bytes) {
            if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
            if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB';
            return bytes + ' B';
        },

        scrollToTop() {
            if (this.$refs.logTextarea) {
                this.$refs.logTextarea.scrollTop = 0;
            }
        },

        scrollToBottom() {
            if (this.$refs.logTextarea) {
                this.$refs.logTextarea.scrollTop = this.$refs.logTextarea.scrollHeight;
            }
        }
    }
}
</script>
@endpush
