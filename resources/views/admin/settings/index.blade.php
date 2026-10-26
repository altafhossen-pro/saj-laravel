@extends('layouts.admin')

@section('title', 'Settings - Admin Panel')
@section('header_title', 'Settings')

@section('content')
<div class="w-full mx-auto">
    <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-4">
        
        <!-- Branding Settings -->
        <a href="{{ route('admin.settings.branding') }}" class="group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:border-brand-300 hover:shadow-md transition-all duration-300 flex flex-col items-center text-center">
            <div class="w-14 h-14 bg-brand-50 text-brand-600 rounded-xl flex items-center justify-center mb-4 group-hover:bg-brand-600 group-hover:text-white transition-colors duration-300">
                <i class="ph ph-palette text-2xl"></i>
            </div>
            <h3 class="text-sm font-bold text-gray-800 mb-1">Branding Settings</h3>
            <p class="text-xs text-gray-500">Logo, colors & identity</p>
        </a>

        <!-- Email SMTP -->
        <a href="{{ route('admin.settings.smtp') }}" class="group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:border-brand-300 hover:shadow-md transition-all duration-300 flex flex-col items-center text-center">
            <div class="w-14 h-14 bg-brand-50 text-brand-600 rounded-xl flex items-center justify-center mb-4 group-hover:bg-brand-600 group-hover:text-white transition-colors duration-300">
                <i class="ph ph-envelope-simple text-2xl"></i>
            </div>
            <h3 class="text-sm font-bold text-gray-800 mb-1">Email SMTP</h3>
            <p class="text-xs text-gray-500">Mail server configuration</p>
        </a>

        <!-- Email Templates -->
        <a href="{{ route('admin.settings.email-templates') }}" class="group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:border-brand-300 hover:shadow-md transition-all duration-300 flex flex-col items-center text-center">
            <div class="w-14 h-14 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center mb-4 group-hover:bg-purple-600 group-hover:text-white transition-colors duration-300">
                <i class="ph ph-file-text text-2xl"></i>
            </div>
            <h3 class="text-sm font-bold text-gray-800 mb-1">Email Templates</h3>
            <p class="text-xs text-gray-500">Design, preview & test</p>
        </a>

        <!-- Steadfast Configuration -->
        <a href="{{ route('admin.settings.steadfast') }}" class="group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:border-brand-300 hover:shadow-md transition-all duration-300 flex flex-col items-center text-center">
            <div class="w-14 h-14 bg-brand-50 text-brand-600 rounded-xl flex items-center justify-center mb-4 group-hover:bg-brand-600 group-hover:text-white transition-colors duration-300">
                <i class="ph ph-truck text-2xl"></i>
            </div>
            <h3 class="text-sm font-bold text-gray-800 mb-1">Steadfast Configuration</h3>
            <p class="text-xs text-gray-500">Courier integration keys</p>
        </a>

        <!-- Delivery Settings -->
        <a href="{{ route('admin.settings.delivery') }}" class="group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:border-brand-300 hover:shadow-md transition-all duration-300 flex flex-col items-center text-center">
            <div class="w-14 h-14 bg-brand-50 text-brand-600 rounded-xl flex items-center justify-center mb-4 group-hover:bg-brand-600 group-hover:text-white transition-colors duration-300">
                <i class="ph ph-package text-2xl"></i>
            </div>
            <h3 class="text-sm font-bold text-gray-800 mb-1">Delivery Settings</h3>
            <p class="text-xs text-gray-500">Shipping rates & zones</p>
        </a>

        <!-- Payment Settings -->
        <a href="#" class="group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:border-brand-300 hover:shadow-md transition-all duration-300 flex flex-col items-center text-center">
            <div class="w-14 h-14 bg-brand-50 text-brand-600 rounded-xl flex items-center justify-center mb-4 group-hover:bg-brand-600 group-hover:text-white transition-colors duration-300">
                <i class="ph ph-credit-card text-2xl"></i>
            </div>
            <h3 class="text-sm font-bold text-gray-800 mb-1">Payment Settings</h3>
            <p class="text-xs text-gray-500">Payment gateways</p>
        </a>

        <!-- Roles & Permissions -->
        <a href="#" class="group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:border-brand-300 hover:shadow-md transition-all duration-300 flex flex-col items-center text-center">
            <div class="w-14 h-14 bg-brand-50 text-brand-600 rounded-xl flex items-center justify-center mb-4 group-hover:bg-brand-600 group-hover:text-white transition-colors duration-300">
                <i class="ph ph-shield-check text-2xl"></i>
            </div>
            <h3 class="text-sm font-bold text-gray-800 mb-1">Roles & Permissions</h3>
            <p class="text-xs text-gray-500">Admin access control</p>
        </a>

        <!-- Site Information -->
        <a href="{{ route('admin.settings.site-info') }}" class="group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:border-brand-300 hover:shadow-md transition-all duration-300 flex flex-col items-center text-center">
            <div class="w-14 h-14 bg-brand-50 text-brand-600 rounded-xl flex items-center justify-center mb-4 group-hover:bg-brand-600 group-hover:text-white transition-colors duration-300">
                <i class="ph ph-info text-2xl"></i>
            </div>
            <h3 class="text-sm font-bold text-gray-800 mb-1">Site Information</h3>
            <p class="text-xs text-gray-500">Contact & Social Links</p>
        </a>

        <!-- Dynamic Scripts -->
        <a href="{{ route('admin.settings.scripts') }}" class="group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:border-brand-300 hover:shadow-md transition-all duration-300 flex flex-col items-center text-center">
            <div class="w-14 h-14 bg-brand-50 text-brand-600 rounded-xl flex items-center justify-center mb-4 group-hover:bg-brand-600 group-hover:text-white transition-colors duration-300">
                <i class="ph ph-code text-2xl"></i>
            </div>
            <h3 class="text-sm font-bold text-gray-800 mb-1">Site Scripts</h3>
            <p class="text-xs text-gray-500">FB Pixel, Analytics tags</p>
        </a>

        <!-- Telegram Configuration -->
        <a href="{{ route('admin.settings.telegram') }}" class="group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:border-brand-300 hover:shadow-md transition-all duration-300 flex flex-col items-center text-center">
            <div class="w-14 h-14 bg-sky-50 text-sky-600 rounded-xl flex items-center justify-center mb-4 group-hover:bg-sky-600 group-hover:text-white transition-colors duration-300">
                <i class="ph ph-paper-plane-tilt text-2xl"></i>
            </div>
            <h3 class="text-sm font-bold text-gray-800 mb-1">Telegram Bot</h3>
            <p class="text-xs text-gray-500">Alerts & notification bots</p>
        </a>

        <!-- Tawk.to Configuration -->
        <a href="{{ route('admin.settings.tawkto') }}" class="group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:border-emerald-300 hover:shadow-md transition-all duration-300 flex flex-col items-center text-center">
            <div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center mb-4 group-hover:bg-emerald-600 group-hover:text-white transition-colors duration-300">
                <i class="ph ph-chat-teardrop-dots text-2xl"></i>
            </div>
            <h3 class="text-sm font-bold text-gray-800 mb-1">Tawk.to Tracker</h3>
            <p class="text-xs text-gray-500">Live chat & hidden visitor tracker</p>
        </a>

        <!-- Meta Pixel & Conversions API -->
        <a href="{{ route('admin.settings.facebook-pixel') }}" class="group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:border-blue-300 hover:shadow-md transition-all duration-300 flex flex-col items-center text-center">
            <div class="w-14 h-14 bg-blue-50 text-[#1877F2] rounded-xl flex items-center justify-center mb-4 group-hover:bg-[#1877F2] group-hover:text-white transition-colors duration-300">
                <i class="ph-fill ph-facebook-logo text-2xl"></i>
            </div>
            <h3 class="text-sm font-bold text-gray-800 mb-1">Meta Pixel & CAPI</h3>
            <p class="text-xs text-gray-500">Server-side tracking & ads</p>
        </a>

        <!-- Laravel System Logs -->
        <a href="{{ route('admin.settings.logs') }}" class="group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:border-slate-400 hover:shadow-md transition-all duration-300 flex flex-col items-center text-center">
            <div class="w-14 h-14 bg-slate-900 text-emerald-400 rounded-xl flex items-center justify-center mb-4 group-hover:bg-emerald-600 group-hover:text-white transition-colors duration-300">
                <i class="ph ph-terminal-window text-2xl"></i>
            </div>
            <h3 class="text-sm font-bold text-gray-800 mb-1">Laravel Logs</h3>
            <p class="text-xs text-gray-500">Inspect, edit & manage logs</p>
        </a>

        <!-- Database Backup & Recovery -->
        <a href="{{ route('admin.settings.backup') }}" class="group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:border-rose-400 hover:shadow-md transition-all duration-300 flex flex-col items-center text-center">
            <div class="w-14 h-14 bg-rose-50 text-rose-600 rounded-xl flex items-center justify-center mb-4 group-hover:bg-rose-600 group-hover:text-white transition-colors duration-300">
                <i class="ph ph-database text-2xl"></i>
            </div>
            <h3 class="text-sm font-bold text-gray-800 mb-1">Database Backup</h3>
            <p class="text-xs text-gray-500">Zip export, vault & restore</p>
        </a>

    </div>
</div>
@endsection

