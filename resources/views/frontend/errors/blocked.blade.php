<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $siteTitle = \App\Models\Setting::get('site_title') ?: 'SAJ';
    @endphp
    <title>Access Restricted - {{ $siteTitle }}</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <!-- Tailwind CSS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4 antialiased text-gray-800">
    <div class="max-w-md w-full bg-white rounded-3xl shadow-xl shadow-gray-200/50 border border-gray-100 p-8 sm:p-10 text-center relative overflow-hidden">
        
        <!-- Top decorative amber/red badge -->
        <div class="w-20 h-20 bg-rose-50 rounded-2xl flex items-center justify-center text-rose-500 mx-auto mb-6 relative">
            <span class="absolute inset-0 rounded-2xl bg-rose-100/60 animate-ping opacity-75"></span>
            <i class="ph-duotone ph-shield-warning text-4xl relative z-10"></i>
        </div>

        <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight mb-2">Access Restricted</h1>
        
        <!-- Custom Message dynamically set by Admin -->
        <div class="bg-rose-50/70 border border-rose-100 rounded-2xl p-4 my-5 text-left">
            <p class="text-sm font-medium text-rose-900 leading-relaxed">
                {{ $customMessage ?: 'Your IP address has been temporarily restricted from accessing this online store due to security or policy measures.' }}
            </p>
        </div>

        <!-- IP & Expiry Information -->
        <div class="space-y-2 text-xs text-gray-500 border-t border-gray-100 pt-5 mb-6 text-left">
            <div class="flex justify-between items-center">
                <span class="font-medium text-gray-400">Detected IP:</span>
                <span class="font-mono font-bold text-gray-700 bg-gray-100 px-2 py-0.5 rounded">{{ $clientIp }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="font-medium text-gray-400">Restriction Status:</span>
                @if($expiresAt)
                    <span class="font-semibold text-amber-700">Expires on {{ $expiresAt->format('M d, Y h:i A') }}</span>
                @else
                    <span class="font-semibold text-rose-600">Permanent Ban</span>
                @endif
            </div>
        </div>

        <!-- Help / Support Assistance -->
        <div class="bg-gray-50 rounded-xl p-3 text-xs text-gray-500 mb-6">
            If you believe this is an error or require assistance, please contact our support team.
        </div>

        <div class="flex flex-col gap-2">
            @if(\App\Models\Setting::get('site_phone'))
                <a href="tel:{{ \App\Models\Setting::get('site_phone') }}" 
                   class="inline-flex items-center justify-center gap-2 w-full py-3 px-4 rounded-xl bg-gray-900 hover:bg-black text-white text-xs font-bold transition-colors">
                    <i class="ph ph-phone-call text-base"></i>
                    <span>Call Support: {{ \App\Models\Setting::get('site_phone') }}</span>
                </a>
            @endif
        </div>

        <div class="mt-6 text-[11px] text-gray-400">
            &copy; {{ date('Y') }} {{ $siteTitle }}. Security Firewall.
        </div>
    </div>
</body>
</html>
