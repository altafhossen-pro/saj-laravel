@extends('layouts.admin')

@section('title', 'Tawk.to Configuration - Admin Panel')
@section('header_title', 'Tawk.to Live Chat & Visitor Tracker')

@section('content')
<div class="w-full space-y-6" x-data="tawktoSettings()">
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
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 font-medium border border-emerald-100 text-xs">
                <i class="ph-bold ph-chat-teardrop-dots"></i> Tawk.to API
            </span>
            <button type="button" @click="document.getElementById('tawktoForm').requestSubmit()" :disabled="saving"
                class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs rounded-xl shadow-sm shadow-brand-500/20 transition flex items-center gap-1.5 cursor-pointer disabled:opacity-60">
                <i x-show="!saving" class="ph-bold ph-floppy-disk text-sm text-white"></i>
                <i x-show="saving" class="ph-bold ph-spinner animate-spin text-sm text-white" style="display: none;"></i>
                <span class="text-white" x-text="saving ? 'Saving...' : 'Save Changes'"></span>
            </button>
        </div>
    </div>

    <!-- Quick Overview Banner -->
    <div class="bg-gradient-to-r from-emerald-600 to-teal-700 rounded-2xl p-6 text-white shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div class="space-y-1.5">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 text-xs font-semibold backdrop-blur-xs">
                <span class="w-2 h-2 rounded-full bg-emerald-300 animate-pulse"></span>
                <span>Realtime Customer Tracking</span>
            </div>
            <h2 class="text-xl font-bold">Tawk.to Invisible Tracker & Live Chat</h2>
            <p class="text-emerald-100 text-xs max-w-2xl leading-relaxed">
                Connect your Tawk.to property to watch active visitors, what products they view, and their live journey in real-time. Choose <strong>Hidden Mode</strong> to keep the widget 100% invisible on your storefront!
            </p>
        </div>
        <div class="shrink-0 flex items-center gap-3">
            <a href="https://dashboard.tawk.to" target="_blank"
                class="px-4 py-2.5 bg-white text-emerald-800 hover:bg-emerald-50 text-xs font-bold rounded-xl transition shadow-sm inline-flex items-center gap-2">
                <span>Open Tawk.to Dashboard</span>
                <i class="ph-bold ph-arrow-square-out text-sm"></i>
            </a>
        </div>
    </div>

    <!-- Main Form Grid -->
    <form id="tawktoForm" @submit.prevent="saveSettings" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        @csrf

        <!-- Left 2 Cols: Main Settings -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-6">
                <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                    <div>
                        <h3 class="text-base font-bold text-gray-800">Connection & Status</h3>
                        <p class="text-xs text-gray-500">Enable or disable Tawk.to integration</p>
                    </div>

                    <!-- Master Switch -->
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="tawkto_enabled" value="1" x-model="form.enabled" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                        <span class="ml-3 text-xs font-bold" :class="form.enabled ? 'text-emerald-600' : 'text-gray-400'" x-text="form.enabled ? 'ENABLED' : 'DISABLED'"></span>
                    </label>
                </div>

                <!-- Direct Embed Script / Link Paste Box with Auto-Extraction -->
                <div class="bg-gray-50/80 rounded-xl p-4 border border-gray-200/80 space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-gray-800 uppercase tracking-wider">
                            Paste Tawk.to Embed Script or Link
                        </label>
                        <span class="text-[11px] text-emerald-600 font-semibold bg-emerald-50 px-2 py-0.5 rounded-md">Auto-Detects Keys</span>
                    </div>
                    <textarea x-model="pastedSnippet" @input="extractFromSnippet()" rows="3"
                        placeholder="<!--Start of Tawk.to Script-->&#10;<script type='text/javascript'>...&#10;s1.src='https://embed.tawk.to/67f0d10ca25edd190d3869b5/1io2b16na';&#10;...</script>"
                        class="w-full px-3.5 py-2.5 rounded-lg border border-gray-200 text-xs font-mono bg-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition"></textarea>
                    <p class="text-[11px] text-gray-500">
                        সম্পূর্ণ Tawk.to স্ক্রিপ্ট বা লিংক এখানে পেস্ট করলেই নিচে <strong>Property ID</strong> এবং <strong>Widget ID</strong> অটোমেটিক পূরণ হয়ে যাবে!
                    </p>
                </div>

                <!-- Property ID & Widget ID -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                            Property ID <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="tawkto_property_id" x-model="form.propertyId"
                            placeholder="e.g. 67f0d10ca25edd190d3869b5"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm font-mono focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition">
                        <p class="text-[11px] text-gray-400 mt-1.5">Property Hex ID (e.g. 67f0d10ca25edd190d3869b5)</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                            Widget ID
                        </label>
                        <input type="text" name="tawkto_widget_id" x-model="form.widgetId"
                            placeholder="e.g. 1io2b16na or default"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm font-mono focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition">
                        <p class="text-[11px] text-gray-400 mt-1.5">Widget Code (e.g. 1io2b16na)</p>
                    </div>
                </div>

                <!-- Display Mode Option -->
                <div class="border-t border-gray-100 pt-6 space-y-4">
                    <div>
                        <h4 class="text-sm font-bold text-gray-800">Widget Visibility Mode</h4>
                        <p class="text-xs text-gray-500">Decide whether customers can see the chat button</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Mode 1: 100% Invisible -->
                        <label class="relative flex flex-col p-4 rounded-xl border cursor-pointer transition-all"
                            :class="form.visibilityMode === 'hidden' ? 'border-emerald-500 bg-emerald-50/40 ring-1 ring-emerald-500' : 'border-gray-200 hover:border-gray-300 bg-white'">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                                        <i class="ph-bold ph-eye-slash text-base"></i>
                                    </div>
                                    <span class="text-xs font-bold text-gray-900">100% Invisible Tracker</span>
                                </div>
                                <input type="radio" name="tawkto_visibility_mode" value="hidden" x-model="form.visibilityMode" class="text-emerald-600 focus:ring-emerald-500">
                            </div>
                            <p class="text-[11px] text-gray-500 leading-normal">
                                <strong>Recommended:</strong> The widget is completely hidden from customers. Zero UI clutter, but you still track all visitor pageviews, IP, and location in real-time.
                            </p>
                        </label>

                        <!-- Mode 2: Visible Live Chat -->
                        <label class="relative flex flex-col p-4 rounded-xl border cursor-pointer transition-all"
                            :class="form.visibilityMode === 'visible' ? 'border-emerald-500 bg-emerald-50/40 ring-1 ring-emerald-500' : 'border-gray-200 hover:border-gray-300 bg-white'">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center">
                                        <i class="ph-bold ph-chat-centered-dots text-base"></i>
                                    </div>
                                    <span class="text-xs font-bold text-gray-900">Visible Live Chat Widget</span>
                                </div>
                                <input type="radio" name="tawkto_visibility_mode" value="visible" x-model="form.visibilityMode" class="text-emerald-600 focus:ring-emerald-500">
                            </div>
                            <p class="text-[11px] text-gray-500 leading-normal">
                                Normal chat widget displays in the bottom-right corner. Customers can click and talk directly to your support team.
                            </p>
                        </label>
                    </div>
                </div>

                <!-- Customer Privacy / Filter Option -->
                <div class="border-t border-gray-100 pt-6">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="tawkto_track_logged_in_only" value="1" x-model="form.trackOnlyLoggedIn"
                            class="mt-0.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <span class="text-xs font-bold text-gray-800">Track Logged-In Customers Only</span>
                            <p class="text-[11px] text-gray-400 leading-relaxed">
                                If checked, Tawk.to script will only run for registered users and not guest visitors. (Default: Unchecked to track all visitors).
                            </p>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Right 1 Col: Instructions & Live Embed Preview -->
        <div class="space-y-6">
            <!-- How to get Keys Card -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-4">
                <div class="flex items-center gap-2 text-brand-600 font-bold text-sm">
                    <i class="ph-bold ph-question text-base"></i>
                    <span>How to find your Tawk.to IDs</span>
                </div>

                <div class="space-y-3 text-xs text-gray-600 leading-relaxed">
                    <div class="flex gap-2.5">
                        <span class="w-5 h-5 rounded-full bg-gray-100 text-gray-700 font-bold text-[10px] flex items-center justify-center shrink-0">1</span>
                        <span>Log in to your <strong>Tawk.to</strong> account.</span>
                    </div>
                    <div class="flex gap-2.5">
                        <span class="w-5 h-5 rounded-full bg-gray-100 text-gray-700 font-bold text-[10px] flex items-center justify-center shrink-0">2</span>
                        <span>Go to <strong>Administration (Gear Icon)</strong> &gt; <strong>Chat Widget</strong>.</span>
                    </div>
                    <div class="flex gap-2.5">
                        <span class="w-5 h-5 rounded-full bg-gray-100 text-gray-700 font-bold text-[10px] flex items-center justify-center shrink-0">3</span>
                        <span>Look at the <strong>Direct Chat Link</strong> or <strong>Widget Code</strong>. It looks like:</span>
                    </div>
                </div>

                <div class="p-3 bg-gray-900 rounded-xl text-gray-300 font-mono text-[11px] break-all">
                    https://embed.tawk.to/<span class="text-emerald-400 font-bold">PROPERTY_ID</span>/<span class="text-sky-300 font-bold">WIDGET_ID</span>
                </div>
                <p class="text-[11px] text-gray-400">Copy the two parts and paste them into the Property ID and Widget ID fields.</p>
            </div>

            <!-- Preview Card -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-3">
                <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider">Embed Status Summary</h4>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between py-1.5 border-b border-gray-100">
                        <span class="text-gray-500">Status</span>
                        <span class="font-bold" :class="form.enabled ? 'text-emerald-600' : 'text-gray-400'" x-text="form.enabled ? 'Active' : 'Inactive'"></span>
                    </div>
                    <div class="flex justify-between py-1.5 border-b border-gray-100">
                        <span class="text-gray-500">Display</span>
                        <span class="font-bold capitalize text-gray-800" x-text="form.visibilityMode"></span>
                    </div>
                    <div class="flex justify-between py-1.5">
                        <span class="text-gray-500">Mobile App Sync</span>
                        <span class="font-bold text-emerald-600">Supported</span>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function tawktoSettings() {
        return {
            form: {
                enabled: {{ $enabled ? 'true' : 'false' }},
                propertyId: '{{ addslashes($propertyId) }}',
                widgetId: '{{ addslashes($widgetId) }}',
                visibilityMode: '{{ $visibilityMode }}',
                trackOnlyLoggedIn: {{ $trackOnlyLoggedIn ? 'true' : 'false' }},
            },
            pastedSnippet: '',
            extractFromSnippet() {
                if (!this.pastedSnippet) return;
                
                // Matches embed.tawk.to/{propertyId}/{widgetId}
                let match = this.pastedSnippet.match(/embed\.tawk\.to\/([a-zA-Z0-9]+)\/([a-zA-Z0-9]+)/i);
                if (match) {
                    this.form.propertyId = match[1];
                    this.form.widgetId = match[2];
                    this.form.enabled = true;
                    this.showToast('✅ Tawk.to Property ID & Widget ID auto-detected!', 'success');
                }
            },
            saving: false,
            toast: {
                show: false,
                message: '',
                type: 'success'
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
                    let formData = new FormData();
                    formData.append('_token', '{{ csrf_token() }}');
                    if (this.form.enabled) formData.append('tawkto_enabled', '1');
                    formData.append('tawkto_property_id', this.form.propertyId);
                    formData.append('tawkto_widget_id', this.form.widgetId);
                    formData.append('tawkto_visibility_mode', this.form.visibilityMode);
                    if (this.form.trackOnlyLoggedIn) formData.append('tawkto_track_logged_in_only', '1');

                    let res = await fetch('{{ route('admin.settings.tawkto.update') }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json'
                        },
                        body: formData
                    });

                    let data = await res.json();
                    if (res.ok) {
                        this.showToast(data.message || 'Settings saved successfully!', 'success');
                    } else {
                        this.showToast(data.message || 'Failed to save settings.', 'error');
                    }
                } catch (e) {
                    this.showToast('Network error while saving.', 'error');
                } finally {
                    this.saving = false;
                }
            }
        }
    }
</script>
@endpush
@endsection
