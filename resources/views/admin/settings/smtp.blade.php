@extends('layouts.admin')

@section('title', 'Email & SMTP Server Settings - Admin Panel')
@section('header_title', 'Email & SMTP Settings')

@section('content')
<div class="w-full mx-auto space-y-6" x-data="smtpSettingsManager({
    initialMailer: '{{ $settings['mail_mailer'] }}',
    initialEnabled: {{ $settings['mail_enabled'] ? 'true' : 'false' }},
    initialEncryption: '{{ $settings['mail_encryption'] }}',
    reachabilityUrl: '{{ route('admin.settings.smtp.reachability') }}',
    sendTestUrl: '{{ route('admin.settings.smtp.test') }}',
    saveUrl: '{{ route('admin.settings.smtp.update') }}',
    csrf: '{{ csrf_token() }}'
})">

    <!-- Top Breadcrumb & Quick Nav -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.settings.index') }}" 
               class="w-9 h-9 rounded-xl bg-white border border-gray-200 text-gray-600 hover:text-brand-600 hover:border-brand-300 flex items-center justify-center transition-all shadow-2xs cursor-pointer">
                <i class="ph ph-arrow-left text-lg"></i>
            </a>
            <div>
                <h1 class="text-xl font-black text-gray-900 tracking-tight flex items-center gap-2">
                    <i class="ph ph-envelope-simple text-brand-600 text-2xl"></i>
                    Email & SMTP Server Configuration
                </h1>
                <p class="text-xs text-gray-500 font-medium">Manage mail drivers, server host ports, cPanel local sendmail, connection diagnostics & test delivery</p>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.settings.email-templates') }}" 
               class="px-3.5 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 flex items-center gap-2 shadow-2xs transition-all cursor-pointer">
                <i class="ph ph-palette text-sm text-purple-600"></i>
                Email Templates
            </a>
            <a href="{{ route('admin.settings.logs') }}" 
               class="px-3.5 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 flex items-center gap-2 shadow-2xs transition-all cursor-pointer">
                <i class="ph ph-terminal-window text-sm text-slate-700"></i>
                System Logs
            </a>
        </div>
    </div>

    <!-- Overview Stats Bar -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-2xs flex items-center justify-between">
            <div>
                <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Mail Status</div>
                <div class="mt-1 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full {{ $settings['mail_enabled'] ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500' }}"></span>
                    <span class="text-sm font-black text-gray-900">
                        {{ $settings['mail_enabled'] ? 'Active & Ready' : 'Global Sending Disabled' }}
                    </span>
                </div>
            </div>
            <div class="w-10 h-10 rounded-xl {{ $settings['mail_enabled'] ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }} flex items-center justify-center text-xl">
                <i class="ph {{ $settings['mail_enabled'] ? 'ph-check-circle' : 'ph-pause-circle' }}"></i>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-2xs flex items-center justify-between">
            <div>
                <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Active Mail Driver</div>
                <div class="mt-1 text-sm font-black text-gray-900 flex items-center gap-1.5">
                    <span class="px-2 py-0.5 rounded-md text-xs font-bold uppercase {{ $settings['mail_mailer'] === 'sendmail' ? 'bg-blue-50 text-blue-700' : ($settings['mail_mailer'] === 'smtp' ? 'bg-brand-50 text-brand-700' : 'bg-gray-100 text-gray-700') }}">
                        {{ $settings['mail_mailer'] }}
                    </span>
                    @if($settings['mail_mailer'] === 'smtp')
                        <span class="text-xs text-gray-500 font-mono">({{ $settings['mail_host'] }}:{{ $settings['mail_port'] }})</span>
                    @elseif($settings['mail_mailer'] === 'sendmail')
                        <span class="text-xs text-gray-500">(cPanel Local)</span>
                    @endif
                </div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center text-xl">
                <i class="ph ph-cpu"></i>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-2xs flex items-center justify-between">
            <div>
                <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">System Deliveries</div>
                <div class="mt-1 text-sm font-black text-gray-900 flex items-center gap-2">
                    <span class="text-emerald-600 font-bold">{{ $totalSent }} Sent</span>
                    <span class="text-gray-300">|</span>
                    <span class="text-rose-600 font-bold">{{ $totalFailed }} Failed</span>
                </div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl">
                <i class="ph ph-chart-line-up"></i>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    <div x-show="alert.show" x-cloak 
         class="p-4 rounded-2xl border transition-all flex items-start gap-3 shadow-sm"
         :class="alert.type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-rose-50 border-rose-200 text-rose-900'">
        <i class="text-xl shrink-0 mt-0.5 ph" :class="alert.type === 'success' ? 'ph-check-circle text-emerald-600' : 'ph-warning-circle text-rose-600'"></i>
        <div class="flex-1 text-xs leading-relaxed">
            <span class="font-bold" x-text="alert.title"></span>
            <p class="mt-0.5 text-xs opacity-90" x-text="alert.message"></p>
        </div>
        <button type="button" @click="alert.show = false" class="text-gray-400 hover:text-gray-700 cursor-pointer">
            <i class="ph ph-x text-sm"></i>
        </button>
    </div>

    <!-- Main Configuration Form -->
    <form @submit.prevent="saveSettings" class="space-y-6">
        
        <!-- Section 1: Master Control & Driver Selection -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-2xs overflow-hidden">
            <div class="p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-sm font-black text-gray-900 uppercase tracking-wider flex items-center gap-2">
                        <i class="ph ph-toggle-left text-brand-600 text-lg"></i>
                        Master Email Dispatch Controls
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">Global on/off switch and primary mail transport system</p>
                </div>

                <!-- Master Toggle Switch -->
                <div class="flex items-center gap-3 bg-gray-50 p-2 px-3.5 rounded-xl border border-gray-200">
                    <span class="text-xs font-bold text-gray-700">Enable Email Dispatch:</span>
                    <button type="button" 
                            @click="form.mail_enabled = !form.mail_enabled"
                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-hidden"
                            :class="form.mail_enabled ? 'bg-emerald-600' : 'bg-gray-300'">
                        <span aria-hidden="true" 
                              class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
                              :class="form.mail_enabled ? 'translate-x-5' : 'translate-x-0'"></span>
                    </button>
                    <span class="text-xs font-black" :class="form.mail_enabled ? 'text-emerald-700' : 'text-gray-400'" 
                          x-text="form.mail_enabled ? 'ON' : 'OFF'"></span>
                </div>
            </div>

            <!-- Warning if Disabled -->
            <div x-show="!form.mail_enabled" x-cloak class="p-4 bg-amber-50 border-b border-amber-200 text-amber-900 text-xs flex items-start gap-3">
                <i class="ph ph-info text-amber-600 text-lg shrink-0 mt-0.5"></i>
                <div class="leading-relaxed">
                    <strong>Email sending is globally disabled:</strong> Orders, customer registrations, and notifications will be processed without attempting any email connections. This ensures zero delays and prevents any network error logs.
                </div>
            </div>

            <div class="p-6 space-y-6">
                <!-- Driver Selector Cards -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-3">
                        Select Mail Transport Driver
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        
                        <!-- 1. cPanel Local Sendmail -->
                        <div @click="form.mail_mailer = 'sendmail'"
                             class="p-4 rounded-xl border-2 transition-all cursor-pointer relative"
                             :class="form.mail_mailer === 'sendmail' ? 'border-brand-600 bg-brand-50/30 ring-1 ring-brand-500' : 'border-gray-200 hover:border-gray-300 bg-white'">
                            <div class="flex items-center justify-between mb-2">
                                <div class="w-9 h-9 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center text-lg">
                                    <i class="ph ph-server"></i>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 uppercase tracking-wide">cPanel Recommended</span>
                            </div>
                            <h3 class="text-sm font-bold text-gray-900">PHP Local (Sendmail)</h3>
                            <p class="text-xs text-gray-500 mt-1">Uses cPanel's local mail agent directly. Bypasses external SMTP port blocks & SSL cert mismatch.</p>
                            <div class="mt-3 text-[11px] font-semibold text-blue-700 flex items-center gap-1">
                                <i class="ph ph-shield-check"></i> Zero Firewall Block Risk
                            </div>
                        </div>

                        <!-- 2. Custom SMTP Server -->
                        <div @click="form.mail_mailer = 'smtp'"
                             class="p-4 rounded-xl border-2 transition-all cursor-pointer relative"
                             :class="form.mail_mailer === 'smtp' ? 'border-brand-600 bg-brand-50/30 ring-1 ring-brand-500' : 'border-gray-200 hover:border-gray-300 bg-white'">
                            <div class="flex items-center justify-between mb-2">
                                <div class="w-9 h-9 rounded-lg bg-brand-100 text-brand-700 flex items-center justify-center text-lg">
                                    <i class="ph ph-paper-plane-tilt"></i>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-700 uppercase tracking-wide">Custom Host</span>
                            </div>
                            <h3 class="text-sm font-bold text-gray-900">Custom SMTP Server</h3>
                            <p class="text-xs text-gray-500 mt-1">Connect with Gmail, cPanel Webmail, Brevo, Mailgun, Amazon SES or transactional SMTP.</p>
                            <div class="mt-3 text-[11px] font-semibold text-brand-700 flex items-center gap-1">
                                <i class="ph ph-gear"></i> Full Port & Auth Control
                            </div>
                        </div>

                        <!-- 3. Log Driver -->
                        <div @click="form.mail_mailer = 'log'"
                             class="p-4 rounded-xl border-2 transition-all cursor-pointer relative"
                             :class="form.mail_mailer === 'log' ? 'border-brand-600 bg-brand-50/30 ring-1 ring-brand-500' : 'border-gray-200 hover:border-gray-300 bg-white'">
                            <div class="flex items-center justify-between mb-2">
                                <div class="w-9 h-9 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center text-lg">
                                    <i class="ph ph-file-text"></i>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 uppercase tracking-wide">Development</span>
                            </div>
                            <h3 class="text-sm font-bold text-gray-900">Log Driver (Dev Mode)</h3>
                            <p class="text-xs text-gray-500 mt-1">Does not send emails. Writes entire email payload directly into <code class="text-[10px] bg-gray-100 px-1 py-0.5 rounded">laravel.log</code>.</p>
                            <div class="mt-3 text-[11px] font-semibold text-slate-700 flex items-center gap-1">
                                <i class="ph ph-code"></i> Testing Only
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Sendmail Specific Config (If Sendmail is chosen) -->
                <div x-show="form.mail_mailer === 'sendmail'" x-transition class="p-4 bg-gray-50 rounded-xl border border-gray-200 space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-gray-700 uppercase tracking-wider">Sendmail Binary Path</label>
                        <span class="text-[11px] text-gray-400">Default: <code class="bg-white px-1.5 py-0.5 rounded border border-gray-200 text-gray-600">/usr/sbin/sendmail -bs -i</code></span>
                    </div>
                    <input type="text" x-model="form.mail_sendmail_path"
                           placeholder="/usr/sbin/sendmail -bs -i"
                           class="w-full text-xs font-mono bg-white border border-gray-300 rounded-lg px-3 py-2 text-gray-900 focus:ring-2 focus:ring-brand-500 focus:outline-hidden">
                    <p class="text-[11px] text-gray-500">Standard path for cPanel / LiteSpeed / AlmaLinux servers. No credentials or ports required.</p>
                </div>

                <!-- SMTP Server Config (If SMTP chosen) -->
                <div x-show="form.mail_mailer === 'smtp'" x-transition class="space-y-5">
                    
                    <!-- Presets Bar -->
                    <div class="p-3.5 bg-brand-50/40 rounded-xl border border-brand-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <i class="ph ph-magic-wand text-brand-600 text-lg"></i>
                            <span class="text-xs font-bold text-brand-900">Quick Configuration Presets:</span>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" @click="applyPreset('cpanel')"
                                    class="px-2.5 py-1 text-xs font-semibold bg-white border border-brand-300 rounded-lg text-brand-700 hover:bg-brand-50 transition-all cursor-pointer shadow-2xs">
                                cPanel Webmail (mail.sajbd.com:465)
                            </button>
                            <button type="button" @click="applyPreset('gmail')"
                                    class="px-2.5 py-1 text-xs font-semibold bg-white border border-brand-300 rounded-lg text-brand-700 hover:bg-brand-50 transition-all cursor-pointer shadow-2xs">
                                Gmail (smtp.gmail.com:587 TLS)
                            </button>
                            <button type="button" @click="applyPreset('brevo')"
                                    class="px-2.5 py-1 text-xs font-semibold bg-white border border-brand-300 rounded-lg text-brand-700 hover:bg-brand-50 transition-all cursor-pointer shadow-2xs">
                                Brevo / Sendinblue (587 TLS)
                            </button>
                        </div>
                    </div>

                    <!-- Host & Port & Encryption Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                        <div class="sm:col-span-6">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                SMTP Server Host <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" x-model="form.mail_host" required
                                   placeholder="e.g. smtp.gmail.com or mail.sajbd.com"
                                   class="w-full text-xs font-mono bg-white border border-gray-300 rounded-lg px-3 py-2 text-gray-900 focus:ring-2 focus:ring-brand-500 focus:outline-hidden">
                        </div>

                        <div class="sm:col-span-3">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                Port <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" x-model.number="form.mail_port" required
                                   placeholder="587 / 465 / 25"
                                   class="w-full text-xs font-mono bg-white border border-gray-300 rounded-lg px-3 py-2 text-gray-900 focus:ring-2 focus:ring-brand-500 focus:outline-hidden">
                        </div>

                        <div class="sm:col-span-3">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                Encryption Protocol
                            </label>
                            <select x-model="form.mail_encryption"
                                    class="w-full text-xs bg-white border border-gray-300 rounded-lg px-3 py-2 text-gray-900 focus:ring-2 focus:ring-brand-500 focus:outline-hidden cursor-pointer">
                                <option value="tls">TLS (STARTTLS - Port 587)</option>
                                <option value="ssl">SSL (SMTPS - Port 465)</option>
                                <option value="none">None (Plaintext - Port 25)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Username & Password Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                        <div class="sm:col-span-6">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                SMTP Username / Account Email
                            </label>
                            <input type="text" x-model="form.mail_username"
                                   placeholder="e.g. support@sajbd.com or your_email@gmail.com"
                                   class="w-full text-xs bg-white border border-gray-300 rounded-lg px-3 py-2 text-gray-900 focus:ring-2 focus:ring-brand-500 focus:outline-hidden">
                        </div>

                        <div class="sm:col-span-6">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                SMTP Password / App Password
                            </label>
                            <div class="relative">
                                <input :type="showPassword ? 'text' : 'password'" x-model="form.mail_password"
                                       placeholder="{{ empty($settings['mail_password']) ? 'Enter password' : '•••••••••••• (Leave blank to keep existing)' }}"
                                       class="w-full text-xs bg-white border border-gray-300 rounded-lg px-3 py-2 pr-10 text-gray-900 focus:ring-2 focus:ring-brand-500 focus:outline-hidden">
                                <button type="button" @click="showPassword = !showPassword"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 cursor-pointer">
                                    <i class="ph text-base" :class="showPassword ? 'ph-eye-slash' : 'ph-eye'"></i>
                                </button>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-1">For Gmail, use a 16-character Google "App Password" (2-Step Verification required).</p>
                        </div>
                    </div>

                    <!-- Advanced Options (SSL verify & Timeout) -->
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 pt-2">
                        <div class="sm:col-span-6 flex items-center gap-3 bg-gray-50 p-3 rounded-xl border border-gray-200">
                            <input type="checkbox" id="ignore_ssl" x-model="form.mail_ignore_ssl"
                                   class="w-4 h-4 text-brand-600 rounded border-gray-300 focus:ring-brand-500 cursor-pointer">
                            <label for="ignore_ssl" class="text-xs text-gray-700 font-medium cursor-pointer">
                                <strong>Bypass SSL Peer Verification</strong>
                                <span class="block text-[11px] text-gray-500">Enable if using self-signed certs or to bypass cPanel hostname mismatch</span>
                            </label>
                        </div>

                        <div class="sm:col-span-6">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                Connection Timeout (Seconds)
                            </label>
                            <input type="number" x-model.number="form.mail_timeout" min="3" max="60"
                                   class="w-full text-xs bg-white border border-gray-300 rounded-lg px-3 py-2 text-gray-900 focus:ring-2 focus:ring-brand-500 focus:outline-hidden">
                        </div>
                    </div>

                </div>

            </div>
        </div>

        <!-- Section 2: Sender Identity (From Name & Address) -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-2xs overflow-hidden">
            <div class="p-5 border-b border-gray-100">
                <h2 class="text-sm font-black text-gray-900 uppercase tracking-wider flex items-center gap-2">
                    <i class="ph ph-identification-card text-brand-600 text-lg"></i>
                    Sender Identity & Branding
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">The name and email address that will appear in your customer's inbox</p>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                    <div class="sm:col-span-6">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Default "From" Email Address <span class="text-rose-500">*</span>
                        </label>
                        <input type="email" x-model="form.mail_from_address" required
                               placeholder="e.g. support@sajbd.com"
                               class="w-full text-xs bg-white border border-gray-300 rounded-lg px-3 py-2 text-gray-900 focus:ring-2 focus:ring-brand-500 focus:outline-hidden">
                    </div>

                    <div class="sm:col-span-6">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Default "From" Sender Name <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" x-model="form.mail_from_name" required
                               placeholder="e.g. SAJ"
                               class="w-full text-xs bg-white border border-gray-300 rounded-lg px-3 py-2 text-gray-900 focus:ring-2 focus:ring-brand-500 focus:outline-hidden">
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button Bar -->
        <div class="flex items-center justify-between bg-white p-4 rounded-2xl border border-gray-100 shadow-2xs">
            <div class="text-xs text-gray-500">
                Changes will take effect immediately across all application background mailers.
            </div>
            <button type="submit" 
                    :disabled="isSaving"
                    class="px-6 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs rounded-xl shadow-xs hover:shadow-md transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50">
                <i class="ph" :class="isSaving ? 'ph-spinner animate-spin text-base' : 'ph-floppy-disk text-base'"></i>
                <span x-text="isSaving ? 'Applying Settings...' : 'Save & Apply Mail Settings'"></span>
            </button>
        </div>

    </form>

    <!-- Section 3: Diagnostic Tools (Reachability Ping & End-to-End Test Email) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Tool 1: Low-Level Server Reachability & Socket Diagnostic -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-2xs overflow-hidden flex flex-col justify-between">
            <div>
                <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-black text-gray-900 uppercase tracking-wider flex items-center gap-2">
                            <i class="ph ph-broadcast text-blue-600 text-lg"></i>
                            Server Reachability & Socket Diagnostic
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">Test TCP connection, port access, latency and firewall blockage</p>
                    </div>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 uppercase">Fast Ping</span>
                </div>

                <div class="p-5 space-y-4">
                    <p class="text-xs text-gray-600 leading-relaxed">
                        Performs a real-time socket handshake to <strong class="font-mono text-gray-800" x-text="(form.mail_host || 'Host') + ':' + (form.mail_port || 'Port')"></strong> without dispatching any emails. Useful for detecting whether cPanel firewall is intercepting outgoing SMTP ports.
                    </p>

                    <!-- Reachability Output Card -->
                    <div x-show="reachability.result !== null" x-cloak class="p-4 rounded-xl border transition-all text-xs space-y-2.5"
                         :class="reachability.result?.success ? 'bg-emerald-50/50 border-emerald-200 text-emerald-950' : 'bg-rose-50/50 border-rose-200 text-rose-950'">
                        <div class="flex items-center justify-between">
                            <span class="font-bold flex items-center gap-1.5" :class="reachability.result?.success ? 'text-emerald-700' : 'text-rose-700'">
                                <i class="ph text-base" :class="reachability.result?.success ? 'ph-check-circle' : 'ph-x-circle'"></i>
                                <span x-text="reachability.result?.success ? 'Mail Host Reachable' : 'Connection Failed'"></span>
                            </span>
                            <span class="font-mono text-[11px] px-2 py-0.5 rounded bg-white border"
                                  x-text="'Latency: ' + (reachability.result?.latency_ms || 0) + 'ms'"></span>
                        </div>

                        <div class="text-[11px] font-mono bg-white/70 p-2.5 rounded-lg border border-gray-200 space-y-1">
                            <div><strong>IP Address:</strong> <span x-text="reachability.result?.ip || 'N/A'"></span></div>
                            <div><strong>Server Banner:</strong> <span class="break-all" x-text="reachability.result?.banner || 'None'"></span></div>
                        </div>

                        <p class="text-xs font-medium" x-text="reachability.result?.message"></p>

                        <div x-show="reachability.result?.warning" class="p-2.5 rounded bg-amber-100/70 border border-amber-300 text-amber-900 text-[11px] flex items-start gap-1.5">
                            <i class="ph ph-warning text-amber-700 shrink-0 mt-0.5"></i>
                            <span x-text="reachability.result?.warning"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-5 bg-gray-50/50 border-t border-gray-100">
                <button type="button" @click="testReachability" :disabled="reachability.loading"
                        class="w-full py-2.5 px-4 bg-white hover:bg-gray-50 border border-gray-300 text-gray-800 font-bold text-xs rounded-xl shadow-2xs hover:shadow-xs transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50">
                    <i class="ph" :class="reachability.loading ? 'ph-spinner animate-spin text-base text-blue-600' : 'ph-pulse text-base text-blue-600'"></i>
                    <span x-text="reachability.loading ? 'Testing Network Connection...' : 'Test Server Reachability (Socket Check)'"></span>
                </button>
            </div>
        </div>

        <!-- Tool 2: End-to-End Test Email Sender -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-2xs overflow-hidden flex flex-col justify-between">
            <div>
                <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-black text-gray-900 uppercase tracking-wider flex items-center gap-2">
                            <i class="ph ph-paper-plane-tilt text-emerald-600 text-lg"></i>
                            Live Test Email Dispatch
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">Dispatch an actual branded email to verify inbox delivery</p>
                    </div>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 uppercase">Live Delivery</span>
                </div>

                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Recipient Test Email Address
                        </label>
                        <input type="email" x-model="testEmailAddress"
                               placeholder="your_email@gmail.com"
                               class="w-full text-xs bg-white border border-gray-300 rounded-lg px-3 py-2 text-gray-900 focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                        <p class="text-[11px] text-gray-400 mt-1">Dispatches a complete diagnostic template using current configuration.</p>
                    </div>

                    <!-- Test Email Result Card -->
                    <div x-show="testEmail.result !== null" x-cloak class="p-4 rounded-xl border transition-all text-xs space-y-1.5"
                         :class="testEmail.result?.success ? 'bg-emerald-50 border-emerald-200 text-emerald-950' : 'bg-rose-50 border-rose-200 text-rose-950'">
                        <div class="flex items-center gap-1.5 font-bold" :class="testEmail.result?.success ? 'text-emerald-700' : 'text-rose-700'">
                            <i class="ph text-base" :class="testEmail.result?.success ? 'ph-check-circle' : 'ph-x-circle'"></i>
                            <span x-text="testEmail.result?.success ? 'Email Successfully Delivered!' : 'Delivery Attempt Failed'"></span>
                        </div>
                        <p class="text-xs font-medium leading-relaxed" x-text="testEmail.result?.message"></p>
                    </div>
                </div>
            </div>

            <div class="p-5 bg-gray-50/50 border-t border-gray-100">
                <button type="button" @click="sendTestEmail" :disabled="testEmail.loading || !testEmailAddress"
                        class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-2xs hover:shadow-xs transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50">
                    <i class="ph" :class="testEmail.loading ? 'ph-spinner animate-spin text-base' : 'ph-paper-plane-tilt text-base'"></i>
                    <span x-text="testEmail.loading ? 'Sending Test Email...' : 'Send Test Email Now'"></span>
                </button>
            </div>
        </div>

    </div>

    <!-- Section 4: Recent System Email Logs -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-2xs overflow-hidden">
        <div class="p-5 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-black text-gray-900 uppercase tracking-wider flex items-center gap-2">
                    <i class="ph ph-clock-counter-clockwise text-gray-600 text-lg"></i>
                    Recent Email Dispatch Activity
                </h3>
                <p class="text-xs text-gray-500 mt-0.5">Live audit trail of transaction emails dispatched across the platform</p>
            </div>
            <a href="{{ route('admin.settings.email-templates') }}" class="text-xs font-bold text-brand-600 hover:text-brand-700 cursor-pointer">
                View Full Log History &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-600">
                <thead class="bg-gray-50/60 text-gray-500 font-bold uppercase text-[10px] tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="py-3 px-4">Recipient</th>
                        <th class="py-3 px-4">Subject</th>
                        <th class="py-3 px-4">Type</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 font-medium">
                    @forelse($recentLogs as $log)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="py-3 px-4 font-bold text-gray-900">
                                {{ $log->recipient_email }}
                            </td>
                            <td class="py-3 px-4 text-gray-700 max-w-[280px] truncate" title="{{ $log->subject }}">
                                {{ $log->subject }}
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-gray-100 text-gray-700">
                                    {{ str_replace('_', ' ', $log->template_key) }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                @if($log->status === 'sent')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="ph ph-check"></i> Sent
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200" title="{{ $log->error_message }}">
                                        <i class="ph ph-warning"></i> Failed
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-gray-400 font-mono text-[11px]">
                                {{ $log->created_at->diffForHumans() }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-gray-400">
                                No email logs recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@push('scripts')
<script>
function smtpSettingsManager(config) {
    return {
        form: {
            mail_enabled: config.initialEnabled,
            mail_mailer: config.initialMailer,
            mail_sendmail_path: '{{ addslashes($settings['mail_sendmail_path']) }}',
            mail_host: '{{ addslashes($settings['mail_host']) }}',
            mail_port: {{ $settings['mail_port'] }},
            mail_encryption: config.initialEncryption,
            mail_username: '{{ addslashes($settings['mail_username']) }}',
            mail_password: '',
            mail_timeout: {{ $settings['mail_timeout'] }},
            mail_ignore_ssl: {{ $settings['mail_ignore_ssl'] ? 'true' : 'false' }},
            mail_from_address: '{{ addslashes($settings['mail_from_address']) }}',
            mail_from_name: '{{ addslashes($settings['mail_from_name']) }}',
        },
        showPassword: false,
        isSaving: false,
        testEmailAddress: '{{ auth()->user()->email ?? 'support@sajbd.com' }}',
        alert: {
            show: false,
            type: 'success',
            title: '',
            message: ''
        },
        reachability: {
            loading: false,
            result: null
        },
        testEmail: {
            loading: false,
            result: null
        },

        applyPreset(type) {
            if (type === 'cpanel') {
                this.form.mail_host = 'mail.sajbd.com';
                this.form.mail_port = 465;
                this.form.mail_encryption = 'ssl';
                this.form.mail_ignore_ssl = true;
            } else if (type === 'gmail') {
                this.form.mail_host = 'smtp.gmail.com';
                this.form.mail_port = 587;
                this.form.mail_encryption = 'tls';
                this.form.mail_ignore_ssl = false;
            } else if (type === 'brevo') {
                this.form.mail_host = 'smtp-relay.brevo.com';
                this.form.mail_port = 587;
                this.form.mail_encryption = 'tls';
                this.form.mail_ignore_ssl = false;
            }
        },

        async saveSettings() {
            this.isSaving = true;
            this.alert.show = false;

            try {
                const response = await fetch(config.saveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrf,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.form)
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.alert = {
                        show: true,
                        type: 'success',
                        title: 'Success!',
                        message: data.message || 'Email & SMTP settings updated successfully.'
                    };
                } else {
                    throw new Error(data.message || 'Failed to save settings.');
                }
            } catch (err) {
                this.alert = {
                    show: true,
                    type: 'error',
                    title: 'Save Failed',
                    message: err.message
                };
            } finally {
                this.isSaving = false;
            }
        },

        async testReachability() {
            this.reachability.loading = true;
            this.reachability.result = null;

            try {
                const response = await fetch(config.reachabilityUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrf,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        mail_host: this.form.mail_host,
                        mail_port: this.form.mail_port,
                        mail_encryption: this.form.mail_encryption,
                        mail_timeout: this.form.mail_timeout
                    })
                });

                const data = await response.json();
                this.reachability.result = data;
            } catch (err) {
                this.reachability.result = {
                    success: false,
                    message: 'Network error occurred while calling reachability test API: ' + err.message
                };
            } finally {
                this.reachability.loading = false;
            }
        },

        async sendTestEmail() {
            if (!this.testEmailAddress) return;
            this.testEmail.loading = true;
            this.testEmail.result = null;

            try {
                const response = await fetch(config.sendTestUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrf,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        test_email: this.testEmailAddress,
                        mail_mailer: this.form.mail_mailer,
                        mail_sendmail_path: this.form.mail_sendmail_path,
                        mail_host: this.form.mail_host,
                        mail_port: this.form.mail_port,
                        mail_encryption: this.form.mail_encryption,
                        mail_username: this.form.mail_username,
                        mail_password: this.form.mail_password,
                        mail_ignore_ssl: this.form.mail_ignore_ssl,
                        mail_from_address: this.form.mail_from_address,
                        mail_from_name: this.form.mail_from_name
                    })
                });

                const data = await response.json();
                this.testEmail.result = data;
            } catch (err) {
                this.testEmail.result = {
                    success: false,
                    message: 'Exception while dispatching test email: ' + err.message
                };
            } finally {
                this.testEmail.loading = false;
            }
        }
    };
}
</script>
@endpush
@endsection
