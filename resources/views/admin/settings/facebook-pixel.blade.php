@extends('layouts.admin')

@section('title', 'Meta Pixel & Conversions API - Admin Panel')
@section('header_title', 'Meta Pixel & Conversions API (CAPI)')

@section('content')
<div class="w-full space-y-6" x-data="pixelSettings()">
    <!-- Toast Notification -->
    <div x-show="toast.show" x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200 transform"
        x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-4"
        class="fixed bottom-6 right-6 z-50 flex items-center gap-3 px-5 py-3.5 rounded-xl shadow-xl border text-sm font-medium"
        :class="toast.type === 'success' ? 'bg-gray-900 border-gray-800 text-white' : 'bg-red-900 border-red-800 text-white'"
        style="display: none;">
        <i class="text-xl"
            :class="toast.type === 'success' ? 'ph-fill ph-check-circle text-emerald-400' : 'ph-fill ph-warning-circle text-red-400'"></i>
        <span x-text="toast.message"></span>
        <button type="button" @click="toast.show = false" class="ml-2 text-gray-400 hover:text-white transition-colors">
            <i class="ph ph-x"></i>
        </button>
    </div>

    <!-- Header Breadcrumbs & Action -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.settings.index') }}"
            class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-brand-600 transition-colors">
            <i class="ph ph-arrow-left text-lg"></i>
            <span>Back to Settings</span>
        </a>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-50 text-[#1877F2] font-medium border border-blue-100 text-xs">
                <i class="ph-fill ph-facebook-logo"></i> Meta Graph v19.0
            </span>
            <button type="button" @click="document.getElementById('pixelForm').requestSubmit()" :disabled="saving"
                class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs rounded-xl shadow-sm shadow-brand-500/20 transition flex items-center gap-1.5 cursor-pointer disabled:opacity-60">
                <i x-show="!saving" class="ph-bold ph-floppy-disk text-sm text-white"></i>
                <i x-show="saving" class="ph-bold ph-spinner animate-spin text-sm text-white" style="display: none;"></i>
                <span class="text-white" x-text="saving ? 'Saving...' : 'Save Changes'"></span>
            </button>
        </div>
    </div>

    <!-- Quick Overview Banner -->
    <div class="bg-gradient-to-r from-[#1877F2] to-[#0D5BC6] rounded-2xl p-6 text-white shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div class="space-y-1.5">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 text-xs font-semibold backdrop-blur-xs">
                <span class="w-2 h-2 rounded-full bg-blue-200 animate-pulse"></span>
                <span>Hybrid Browser + Server-Side Tracking</span>
            </div>
            <h2 class="text-xl font-bold">Meta Pixel & Conversions API (CAPI)</h2>
            <p class="text-blue-100 text-xs max-w-2xl leading-relaxed">
                Send purchases, carts, and user activity directly from your Laravel server to Facebook. Bypasses iOS 14.5+ ad blockers, boosts Event Match Quality (EMQ) to 8-9/10, and lowers Facebook Ad costs!
            </p>
        </div>
        <div class="shrink-0 flex items-center gap-3">
            <a href="https://business.facebook.com/events_manager2" target="_blank" rel="noopener noreferrer"
                class="px-4 py-2.5 bg-white text-[#1877F2] hover:bg-blue-50 text-xs font-bold rounded-xl transition shadow-sm inline-flex items-center gap-2">
                <span>Open Events Manager</span>
                <i class="ph-bold ph-arrow-square-out text-sm"></i>
            </a>
        </div>
    </div>

    <!-- Main Form Grid -->
    <form id="pixelForm" @submit.prevent="saveSettings" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        @csrf

        <!-- Left 2 Cols: Main Settings -->
        <div class="lg:col-span-2 space-y-6">

            <!-- 1. Enable / Disable Toggle -->
            <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900">Enable Meta Pixel & CAPI</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Toggle browser pixel and server-side event tracking across your storefront.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="fb_pixel_enabled" value="1" x-model="form.enabled" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#1877F2]"></div>
                    </label>
                </div>
            </div>

            <!-- 2. Credentials -->
            <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm space-y-5">
                <h3 class="text-sm font-bold text-gray-900 pb-2 border-b border-gray-100 flex items-center gap-2">
                    <i class="ph-bold ph-key text-[#1877F2]"></i> Meta Credentials
                </h3>

                <!-- Option 1: Full Meta Pixel Code Paste (Auto Extract) -->
                <div class="p-4 bg-blue-50/50 rounded-xl border border-blue-100/80 space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-gray-900 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="ph-bold ph-code text-[#1877F2]"></i> Option A: Paste Full Meta Pixel Code
                        </label>
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-blue-100 text-[#1877F2]">
                            ⚡ Auto-detects Pixel ID
                        </span>
                    </div>
                    <p class="text-xs text-gray-500">
                        Meta Events Manager থেকে পাওয়া সম্পূর্ণ কোডটি এখানে পেস্ট করলে সিস্টেম স্বয়ংক্রিয়ভাবে Pixel ID চিনে নিবে।
                    </p>
                    <textarea name="fb_pixel_script" x-model="form.script" @input="extractPixelId()" rows="4"
                        placeholder="<!-- Meta Pixel Code -->&#10;<script>&#10;...&#10;fbq('init', '978090525342889');&#10;...</script>"
                        class="w-full bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs font-mono text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#1877F2] transition-colors"></textarea>
                    <div x-show="autoDetected" x-transition.opacity style="display: none;" class="flex items-center gap-1.5 text-xs text-emerald-700 font-semibold pt-1">
                        <i class="ph-bold ph-check-circle text-emerald-600 text-sm"></i>
                        <span>Auto-detected Pixel ID: <strong class="font-mono bg-emerald-100/80 px-1.5 py-0.5 rounded" x-text="form.pixelId"></strong></span>
                    </div>
                </div>

                <!-- Divider with OR -->
                <div class="relative flex items-center justify-center my-1">
                    <div class="border-t border-gray-200 w-full"></div>
                    <span class="bg-white px-3 text-xs font-bold text-gray-400 uppercase tracking-wider shrink-0">OR Enter Directly</span>
                    <div class="border-t border-gray-200 w-full"></div>
                </div>

                <!-- Option 2: Pixel ID Directly -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                        <i class="ph-bold ph-identification-badge text-gray-500"></i> Option B: Meta Pixel ID <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="fb_pixel_id" x-model="form.pixelId"
                        placeholder="e.g. 978090525342889"
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm font-mono text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#1877F2] focus:bg-white transition-colors">
                    <p class="text-[11px] text-gray-400 mt-1">If you pasted the full code above, this field fills automatically. You can also edit or type it directly.</p>
                </div>

                <!-- CAPI Access Token -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                            Conversions API Access Token <span class="text-red-500">*</span>
                        </label>
                        <button type="button" @click="showToken = !showToken" class="text-xs text-[#1877F2] hover:underline font-semibold flex items-center gap-1">
                            <i class="ph" :class="showToken ? 'ph-eye-slash' : 'ph-eye'"></i>
                            <span x-text="showToken ? 'Hide Token' : 'Show Token'"></span>
                        </button>
                    </div>
                    <div class="relative">
                        <textarea name="fb_capi_token" x-model="form.capiToken" rows="3"
                            :type="showToken ? 'text' : 'password'"
                            placeholder="e.g. EAA..."
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-xs font-mono text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#1877F2] focus:bg-white transition-colors"></textarea>
                    </div>
                    <p class="text-[11px] text-gray-400 mt-1">
                        Events Manager &rarr; Settings &rarr; Set up Conversions API &rarr; <strong>Generate access token</strong>.
                    </p>
                </div>
            </div>

            <!-- 3. Event Tracking Checkboxes -->
            <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm space-y-4">
                <h3 class="text-sm font-bold text-gray-900 pb-2 border-b border-gray-100 flex items-center gap-2">
                    <i class="ph-bold ph-activity text-emerald-600"></i> Events To Track
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                    <!-- PageView -->
                    <label class="flex items-start gap-3 p-3 bg-gray-50/70 hover:bg-gray-50 rounded-xl border border-gray-200/70 cursor-pointer transition">
                        <input type="checkbox" name="fb_track_page_view" value="1" x-model="form.trackPageView" class="mt-0.5 rounded text-[#1877F2] focus:ring-[#1877F2]">
                        <div>
                            <span class="font-bold text-gray-900 text-xs block">PageView</span>
                            <span class="text-[11px] text-gray-500">Every storefront page visit</span>
                        </div>
                    </label>

                    <!-- ViewCategory -->
                    <label class="flex items-start gap-3 p-3 bg-gray-50/70 hover:bg-gray-50 rounded-xl border border-gray-200/70 cursor-pointer transition">
                        <input type="checkbox" name="fb_track_view_category" value="1" x-model="form.trackViewCategory" class="mt-0.5 rounded text-[#1877F2] focus:ring-[#1877F2]">
                        <div>
                            <span class="font-bold text-gray-900 text-xs block">ViewCategory</span>
                            <span class="text-[11px] text-gray-500">Category page browsing & filtering</span>
                        </div>
                    </label>

                    <!-- ViewContent -->
                    <label class="flex items-start gap-3 p-3 bg-gray-50/70 hover:bg-gray-50 rounded-xl border border-gray-200/70 cursor-pointer transition">
                        <input type="checkbox" name="fb_track_view_content" value="1" x-model="form.trackViewContent" class="mt-0.5 rounded text-[#1877F2] focus:ring-[#1877F2]">
                        <div>
                            <span class="font-bold text-gray-900 text-xs block">ViewContent</span>
                            <span class="text-[11px] text-gray-500">Single product detail view</span>
                        </div>
                    </label>

                    <!-- Search -->
                    <label class="flex items-start gap-3 p-3 bg-gray-50/70 hover:bg-gray-50 rounded-xl border border-gray-200/70 cursor-pointer transition">
                        <input type="checkbox" name="fb_track_search" value="1" x-model="form.trackSearch" class="mt-0.5 rounded text-[#1877F2] focus:ring-[#1877F2]">
                        <div>
                            <span class="font-bold text-gray-900 text-xs block">Search</span>
                            <span class="text-[11px] text-gray-500">Keyword searches in the search bar</span>
                        </div>
                    </label>

                    <!-- AddToCart -->
                    <label class="flex items-start gap-3 p-3 bg-gray-50/70 hover:bg-gray-50 rounded-xl border border-gray-200/70 cursor-pointer transition">
                        <input type="checkbox" name="fb_track_add_to_cart" value="1" x-model="form.trackAddToCart" class="mt-0.5 rounded text-[#1877F2] focus:ring-[#1877F2]">
                        <div>
                            <span class="font-bold text-gray-900 text-xs block">AddToCart</span>
                            <span class="text-[11px] text-gray-500">Items added to shopping bag</span>
                        </div>
                    </label>

                    <!-- InitiateCheckout -->
                    <label class="flex items-start gap-3 p-3 bg-gray-50/70 hover:bg-gray-50 rounded-xl border border-gray-200/70 cursor-pointer transition">
                        <input type="checkbox" name="fb_track_initiate_checkout" value="1" x-model="form.trackInitiateCheckout" class="mt-0.5 rounded text-[#1877F2] focus:ring-[#1877F2]">
                        <div>
                            <span class="font-bold text-gray-900 text-xs block">InitiateCheckout</span>
                            <span class="text-[11px] text-gray-500">Checkout form reached/started</span>
                        </div>
                    </label>

                    <!-- CompleteRegistration -->
                    <label class="flex items-start gap-3 p-3 bg-gray-50/70 hover:bg-gray-50 rounded-xl border border-gray-200/70 cursor-pointer transition">
                        <input type="checkbox" name="fb_track_complete_registration" value="1" x-model="form.trackCompleteRegistration" class="mt-0.5 rounded text-[#1877F2] focus:ring-[#1877F2]">
                        <div>
                            <span class="font-bold text-gray-900 text-xs block">CompleteRegistration</span>
                            <span class="text-[11px] text-gray-500">User sign-up / account creation</span>
                        </div>
                    </label>

                    <!-- Contact -->
                    <label class="flex items-start gap-3 p-3 bg-gray-50/70 hover:bg-gray-50 rounded-xl border border-gray-200/70 cursor-pointer transition">
                        <input type="checkbox" name="fb_track_contact" value="1" x-model="form.trackContact" class="mt-0.5 rounded text-[#1877F2] focus:ring-[#1877F2]">
                        <div>
                            <span class="font-bold text-gray-900 text-xs block">Contact</span>
                            <span class="text-[11px] text-gray-500">Contact form submitted / WhatsApp support</span>
                        </div>
                    </label>

                    <!-- Purchase -->
                    <label class="sm:col-span-2 flex items-start gap-3 p-3 bg-emerald-50/50 hover:bg-emerald-50 rounded-xl border border-emerald-200/80 cursor-pointer transition">
                        <input type="checkbox" name="fb_track_purchase" value="1" x-model="form.trackPurchase" class="mt-0.5 rounded text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="font-bold text-emerald-900 text-xs block">Purchase (Highest Value Event)</span>
                            <span class="text-[11px] text-emerald-700">Order successfully completed. Hashes phone, email, and name for 8.5+ EMQ score.</span>
                        </div>
                    </label>
                </div>
            </div>

        </div>

        <!-- Right Col: Diagnostic Test & Guide -->
        <div class="space-y-6">

            <!-- Test Connection Box -->
            <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm space-y-4">
                <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                    <i class="ph-bold ph-flask text-amber-500"></i> Test Events Tool
                </h3>
                <p class="text-xs text-gray-500 leading-relaxed">
                    Verify server-side connectivity without affecting your live production stats.
                </p>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">
                        Test Event Code (From Events Manager)
                    </label>
                    <input type="text" name="fb_test_event_code" x-model="form.testEventCode"
                        placeholder="e.g. TEST12345"
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-xs font-mono text-gray-800 focus:outline-none focus:ring-2 focus:ring-[#1877F2] focus:bg-white transition-colors">
                    <p class="text-[10px] text-gray-400 mt-1">Leave blank when running live production ads.</p>
                </div>

                <button type="button" @click="testConnection" :disabled="testing"
                    class="w-full py-2.5 px-4 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 shadow-xs cursor-pointer disabled:opacity-60">
                    <i x-show="!testing" class="ph-bold ph-paper-plane-tilt text-sm"></i>
                    <i x-show="testing" class="ph-bold ph-spinner animate-spin text-sm" style="display: none;"></i>
                    <span x-text="testing ? 'Sending Diagnostic Ping...' : 'Send Test Event to Meta'"></span>
                </button>

                <!-- Test Result Box -->
                <div x-show="testResult.show" x-transition.opacity x-cloak
                    class="p-3 rounded-xl border text-xs"
                    :class="testResult.success ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-red-50 border-red-200 text-red-800'">
                    <div class="font-bold flex items-center gap-1.5 mb-1">
                        <i :class="testResult.success ? 'ph-fill ph-check-circle text-emerald-600' : 'ph-fill ph-x-circle text-red-600'"></i>
                        <span x-text="testResult.success ? 'Connection Successful!' : 'Connection Failed'"></span>
                    </div>
                    <p x-text="testResult.message" class="leading-relaxed"></p>
                    <template x-if="testResult.fbtrace">
                        <div class="text-[10px] text-gray-500 font-mono mt-1">FBTrace ID: <span x-text="testResult.fbtrace"></span></div>
                    </template>
                </div>
            </div>

            <!-- Setup Steps Checklist -->
            <div class="bg-gray-50/80 rounded-2xl p-6 border border-gray-200/70 space-y-3.5">
                <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wider">How to get your credentials:</h4>
                <ol class="space-y-2.5 text-xs text-gray-600 list-decimal list-inside leading-relaxed">
                    <li>Go to <strong>Meta Business Suite &rarr; Events Manager</strong>.</li>
                    <li>Click <strong>Connect Data Sources &rarr; Web</strong> and create your pixel.</li>
                    <li>Copy your <strong>Pixel ID</strong> and paste above.</li>
                    <li>Under the <strong>Settings</strong> tab, scroll to <em>Conversions API</em> and click <strong>Generate access token</strong>.</li>
                    <li>Paste the token here, enable toggle, and click <strong>Save Changes</strong>!</li>
                </ol>
            </div>

        </div>

    </form>
</div>

<script>
    function pixelSettings() {
        return {
            form: {
                enabled: {{ $enabled ? 'true' : 'false' }},
                script: {!! json_encode($pixelScript) !!},
                pixelId: '{{ addslashes($pixelId) }}',
                capiToken: '{{ addslashes($capiToken) }}',
                testEventCode: '{{ addslashes($testEventCode) }}',
                trackPageView: {{ $trackPageView ? 'true' : 'false' }},
                trackViewCategory: {{ $trackViewCategory ? 'true' : 'false' }},
                trackViewContent: {{ $trackViewContent ? 'true' : 'false' }},
                trackSearch: {{ $trackSearch ? 'true' : 'false' }},
                trackAddToCart: {{ $trackAddToCart ? 'true' : 'false' }},
                trackInitiateCheckout: {{ $trackInitiateCheckout ? 'true' : 'false' }},
                trackCompleteRegistration: {{ $trackCompleteRegistration ? 'true' : 'false' }},
                trackContact: {{ $trackContact ? 'true' : 'false' }},
                trackPurchase: {{ $trackPurchase ? 'true' : 'false' }},
            },
            autoDetected: false,
            showToken: false,
            saving: false,
            testing: false,

            init() {
                if (this.form.script) {
                    this.extractPixelId();
                }
            },

            extractPixelId() {
                if (!this.form.script) return;
                const raw = this.form.script;
                const match = raw.match(/fbq\(['"]init['"],\s*['"](\d+)['"]\)/i)
                           || raw.match(/tr\?id=(\d+)/i)
                           || raw.match(/(\d{14,17})/);
                if (match && match[1]) {
                    this.form.pixelId = match[1];
                    this.autoDetected = true;
                }
            },
            testResult: {
                show: false,
                success: false,
                message: '',
                fbtrace: ''
            },
            toast: {
                show: false,
                message: '',
                type: 'success'
            },

            showToast(message, type = 'success') {
                this.toast = { show: true, message, type };
                setTimeout(() => { this.toast.show = false; }, 4000);
            },

            async saveSettings() {
                this.saving = true;
                try {
                    const formData = new FormData(document.getElementById('pixelForm'));
                    const res = await fetch('{{ route('admin.settings.facebook-pixel.update') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });

                    const data = await res.json();
                    if (res.ok && data.status === 'success') {
                        this.showToast(data.message, 'success');
                    } else {
                        throw new Error(data.message || 'Validation error');
                    }
                } catch (e) {
                    this.showToast(e.message || 'Failed to save settings.', 'error');
                } finally {
                    this.saving = false;
                }
            },

            async testConnection() {
                if (!this.form.pixelId || !this.form.capiToken) {
                    this.showToast('Please enter both Pixel ID and CAPI Token first.', 'error');
                    return;
                }

                this.testing = true;
                this.testResult.show = false;

                try {
                    const res = await fetch('{{ route('admin.settings.facebook-pixel.test') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            pixel_id: this.form.pixelId,
                            capi_token: this.form.capiToken,
                            test_code: this.form.testEventCode
                        })
                    });

                    const data = await res.json();
                    this.testResult = {
                        show: true,
                        success: !!data.success,
                        message: data.message || (data.success ? 'Meta received test event successfully!' : 'Request rejected by Meta.'),
                        fbtrace: data.fbtrace_id || ''
                    };
                } catch (e) {
                    this.testResult = {
                        show: true,
                        success: false,
                        message: e.message || 'Network connection failed.',
                        fbtrace: ''
                    };
                } finally {
                    this.testing = false;
                }
            }
        };
    }
</script>
@endsection
