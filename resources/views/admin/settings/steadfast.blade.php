@extends('layouts.admin')

@section('title', 'Steadfast Courier Configuration - Admin Panel')
@section('header_title', 'Steadfast Courier Configuration')

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
        <button type="button" @click="toast.show = false" class="ml-2 text-gray-400 hover:text-white transition-colors">
            <i class="ph ph-x"></i>
        </button>
    </div>

    <!-- Header Breadcrumbs / Back button -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.settings.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-brand-600 transition-colors">
            <i class="ph ph-arrow-left text-lg"></i>
            <span>Back to Settings</span>
        </a>
        <div class="flex items-center gap-2 text-xs">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-50 text-blue-700 font-medium border border-blue-100">
                <i class="ph-bold ph-shield-check"></i> Official Steadfast API v1
            </span>
        </div>
    </div>

    <!-- Quick Status Cards -->
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

        <!-- Current Balance Card -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl bg-brand-50 text-brand-600">
                    <i :class="balanceStatus.loading ? 'ph-bold ph-spinner animate-spin' : 'ph-bold ph-wallet'"></i>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Courier Balance</p>
                    <h4 class="text-base font-extrabold text-gray-800" x-text="balanceStatus.balance !== null ? '৳ ' + Number(balanceStatus.balance).toLocaleString('en-US', {minimumFractionDigits: 2}) : '—'"></h4>
                    <p class="text-[11px] text-gray-500">Delivered COD payout ready</p>
                </div>
            </div>
            <button type="button" @click="fetchBalance()" :disabled="balanceStatus.loading" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-brand-50 hover:bg-brand-100 text-brand-700 transition flex items-center gap-1 cursor-pointer disabled:opacity-50">
                <i class="ph ph-arrows-clockwise" :class="{'animate-spin': balanceStatus.loading}"></i>
                <span>Check Balance</span>
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

    <!-- Main Configuration Form -->
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

                <!-- Webhook Auth Token -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                        Webhook Auth Token (Optional / Recommended)
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
                                @click="generateWebhookToken()" 
                                class="absolute inset-y-1 right-1 px-3 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-lg transition-colors flex items-center gap-1 cursor-pointer">
                            <i class="ph ph-sparkle"></i> Generate
                        </button>
                    </div>
                    <p class="text-[11px] text-gray-500 mt-1">Passed in Steadfast portal webhook settings. Steadfast will use this token to sign <code class="bg-gray-100 px-1 py-0.5 rounded text-gray-800">X-Signature</code> (HMAC-SHA256).</p>
                </div>

                <!-- Base URL (Advanced) -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700">
                            Base API URL
                        </label>
                        <button type="button" @click="baseUrl = 'https://portal.packzy.com/api/v1'" class="text-[11px] text-brand-600 hover:underline">Reset to default</button>
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
                        <span>Signature: <strong>X-Signature (HMAC-SHA256)</strong></span>
                    </div>
                    <div class="flex items-start gap-2">
                        <i class="ph-fill ph-check-circle text-emerald-500 text-sm mt-0.5 shrink-0"></i>
                        <span>Events: <strong>delivery_status, tracking_update, etc.</strong></span>
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
                    <span class="text-[11px] text-gray-400">Real-time test</span>
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
                            @click="fetchBalance()" 
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
@endsection

@push('scripts')
<script>
function steadfastSettings() {
    return {
        apiKey: @json($apiKey),
        secretKey: @json($secretKey),
        webhookToken: @json($webhookToken),
        baseUrl: @json($baseUrl),
        showSecret: false,
        saving: false,
        copiedCallback: false,
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
            balance: null
        },
        lastResponse: {
            action: '',
            success: false,
            status: '',
            text: ''
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

        generateWebhookToken() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';
            let token = '';
            for (let i = 0; i < 32; i++) {
                token += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            this.webhookToken = token;
            this.triggerToast('New webhook auth token generated! Remember to save.');
        },

        async saveSettings() {
            if (this.saving) return;
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
                        steadfast_webhook_token: this.webhookToken,
                        steadfast_base_url: this.baseUrl
                    })
                });

                const data = await response.json();
                if (response.ok) {
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

        async fetchBalance() {
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
                    this.triggerToast('Balance loaded: ৳ ' + res.current_balance);
                } else {
                    this.triggerToast('Failed to fetch balance: ' + res.message, 'error');
                }
            } catch (err) {
                this.triggerToast('Balance request error: ' + err.message, 'error');
            } finally {
                this.balanceStatus.loading = false;
            }
        }
    }
}
</script>
@endpush
