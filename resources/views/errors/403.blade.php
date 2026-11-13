<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 Forbidden - Access Restricted</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4 relative overflow-x-hidden selection:bg-brand-500 selection:text-white">

    <!-- Ambient Glow Background -->
    <div class="fixed inset-0 pointer-events-none flex items-center justify-center z-0 opacity-40">
        <div class="absolute -top-[20%] -left-[10%] w-[500px] h-[500px] rounded-full bg-brand-100 blur-3xl mix-blend-multiply"></div>
        <div class="absolute top-[20%] -right-[10%] w-[450px] h-[450px] rounded-full bg-amber-100 blur-3xl mix-blend-multiply"></div>
        <div class="absolute -bottom-[20%] left-[20%] w-[600px] h-[600px] rounded-full bg-rose-100 blur-3xl mix-blend-multiply"></div>
    </div>

    <!-- Main Card Container -->
    <div class="relative z-10 w-full max-w-lg bg-white rounded-3xl shadow-xl border border-slate-200/80 p-8 sm:p-10 text-center space-y-6">
        
        <!-- 403 Visual Graphic -->
        <div class="relative flex justify-center items-center">
            <h1 class="leading-none font-black text-gray-900 flex items-center justify-center gap-2 select-none tracking-tighter" style="font-size: 96px;">
                <span class="text-slate-800">4</span>
                <span class="w-20 h-20 rounded-2xl bg-gradient-to-tr from-brand-600 to-rose-500 text-white flex items-center justify-center text-4xl shadow-lg shadow-brand-500/25 animate-bounce">
                    <i class="ph-bold ph-lock-key"></i>
                </span>
                <span class="text-slate-800">3</span>
            </h1>
        </div>

        <!-- Heading & Explanation -->
        <div class="space-y-2">
            <h2 class="text-2xl font-black text-gray-900 tracking-tight">Access Restricted</h2>
            <p class="text-sm text-gray-600 leading-relaxed max-w-sm mx-auto">
                You do not have permission to access this section or perform this action. If you require access, please contact your system administrator.
            </p>
        </div>

        <!-- Logged-in User Account Info Card -->
        @if(auth()->check())
            @php
                $currentUser = auth()->user();
                $userRole = $currentUser->roles->first();
                $roleName = $userRole ? $userRole->name : ($currentUser->role ?: 'user');
                $isCust = $userRole ? (bool)$userRole->is_customer : true;
            @endphp
            <div class="bg-slate-50 border border-slate-200/80 rounded-2xl p-4 text-left shadow-2xs">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-full bg-brand-100 text-brand-700 font-bold flex items-center justify-center text-xs">
                            {{ strtoupper(substr($currentUser->name, 0, 1)) }}
                        </div>
                        <div>
                            <div class="text-xs font-bold text-gray-900">{{ $currentUser->name }}</div>
                            <div class="text-[11px] text-gray-400 font-mono">{{ $currentUser->email }}</div>
                        </div>
                    </div>

                    <!-- Role Badge -->
                    <div>
                        @if($roleName === 'super_admin')
                            <span class="text-[10px] uppercase font-bold px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 border border-amber-200 inline-flex items-center gap-1">
                                <i class="ph-bold ph-crown text-xs"></i> Super Admin
                            </span>
                        @elseif($isCust)
                            <span class="text-[10px] uppercase font-bold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200 inline-flex items-center gap-1">
                                <i class="ph-bold ph-shopping-bag text-xs"></i> Customer Role
                            </span>
                        @else
                            <span class="text-[10px] uppercase font-bold px-2.5 py-1 rounded-full bg-brand-100 text-brand-700 border border-brand-200 inline-flex items-center gap-1">
                                <i class="ph-bold ph-shield-check text-xs"></i> {{ str_replace(['_', '-'], ' ', $roleName) }} (Staff)
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- Action CTAs -->
        <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
            @if(auth()->check() && auth()->user()->hasAdminAccess())
                <a href="{{ route('admin.dashboard') }}" 
                   class="w-full sm:w-auto px-6 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs rounded-xl shadow-xs transition-all inline-flex items-center justify-center gap-2 cursor-pointer">
                    <i class="ph-bold ph-squares-four text-base"></i>
                    <span>Admin Overview</span>
                </a>
            @else
                <a href="{{ route('home') }}" 
                   class="w-full sm:w-auto px-6 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs rounded-xl shadow-xs transition-all inline-flex items-center justify-center gap-2 cursor-pointer">
                    <i class="ph-bold ph-house text-base"></i>
                    <span>Back to Store</span>
                </a>
            @endif

            <button onclick="window.history.length > 1 ? window.history.back() : window.location.href='/'" 
                    class="w-full sm:w-auto px-5 py-2.5 bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 hover:border-gray-300 font-bold text-xs rounded-xl transition-all inline-flex items-center justify-center gap-2 shadow-2xs cursor-pointer">
                <i class="ph-bold ph-arrow-left text-base"></i>
                <span>Go Back</span>
            </button>
        </div>

        <!-- Footer Notice -->
        <p class="text-[11px] text-gray-400">
            Need elevated permissions? Contact your system Super Administrator to adjust your role access.
        </p>
    </div>

</body>
</html>
