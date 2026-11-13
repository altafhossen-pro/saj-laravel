@extends('layouts.admin')

@section('title', 'Access Restricted - Admin Panel')
@section('header_title', 'Access Restricted')

@section('content')
<div class="max-w-2xl mx-auto py-8 sm:py-12">
    <!-- Breadcrumb -->
    <div class="mb-6">
        <a href="{{ route('admin.dashboard') }}" class="text-xs font-semibold text-gray-500 hover:text-brand-600 inline-flex items-center gap-1.5 transition-colors">
            <i class="ph ph-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <!-- Main Card Container -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 sm:p-12 text-center space-y-6 relative overflow-hidden">
        
        <!-- Subtle Top Glow Bar -->
        <div class="absolute top-0 inset-x-0 h-1.5 bg-gradient-to-r from-amber-500 via-rose-500 to-brand-500"></div>

        <!-- 403 Icon Visual Graphic -->
        <div class="relative flex justify-center items-center pt-2">
            <div class="relative">
                <div class="w-20 h-20 rounded-3xl bg-amber-50 border border-amber-100 text-amber-600 flex items-center justify-center text-4xl shadow-sm shadow-amber-500/10">
                    <i class="ph-bold ph-lock-key"></i>
                </div>
                <div class="absolute -bottom-1 -right-1 w-7 h-7 rounded-full bg-rose-500 text-white flex items-center justify-center text-xs font-black shadow-xs">
                    403
                </div>
            </div>
        </div>

        <!-- Heading & Explanation -->
        <div class="space-y-2">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">Access Restricted</h1>
            <p class="text-sm text-gray-500 leading-relaxed max-w-md mx-auto">
                You do not have permission to view or manage this section. Access is restricted according to your assigned staff role and system permissions.
            </p>
        </div>

        <!-- Logged-in User Account Info Card -->
        @if(auth()->check())
            @php
                $currentUser = auth()->user();
                $userRole = $currentUser->roles->first();
                $roleName = $userRole ? $userRole->name : ($currentUser->role ?: 'staff');
            @endphp
            <div class="bg-gray-50/80 border border-gray-200/80 rounded-2xl p-4 text-left max-w-md mx-auto shadow-2xs">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-brand-100 text-brand-700 font-bold flex items-center justify-center text-xs shrink-0">
                            {{ strtoupper(substr($currentUser->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs font-bold text-gray-900 truncate">{{ $currentUser->name }}</div>
                            <div class="text-[11px] text-gray-400 font-mono truncate">{{ $currentUser->email }}</div>
                        </div>
                    </div>

                    <!-- Role Badge -->
                    <div class="shrink-0 ml-2">
                        <span class="text-[10px] uppercase font-bold px-2.5 py-1 rounded-full bg-brand-50 text-brand-700 border border-brand-200 inline-flex items-center gap-1">
                            <i class="ph-bold ph-shield-check text-xs"></i> {{ str_replace(['_', '-'], ' ', $roleName) }} (Staff)
                        </span>
                    </div>
                </div>
            </div>
        @endif

        <!-- Action CTAs -->
        <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ route('admin.dashboard') }}" 
               class="w-full sm:w-auto px-6 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs rounded-xl shadow-xs transition-all inline-flex items-center justify-center gap-2 cursor-pointer">
                <i class="ph-bold ph-squares-four text-base"></i>
                <span>Admin Overview</span>
            </a>

            <button onclick="window.history.length > 1 ? window.history.back() : window.location.href='{{ route('admin.dashboard') }}'" 
                    class="w-full sm:w-auto px-5 py-2.5 bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 hover:border-gray-300 font-bold text-xs rounded-xl transition-all inline-flex items-center justify-center gap-2 shadow-2xs cursor-pointer">
                <i class="ph-bold ph-arrow-left text-base"></i>
                <span>Go Back</span>
            </button>
        </div>

        <!-- Footer Notice -->
        <p class="text-[11px] text-gray-400 pt-2">
            Need elevated permissions? Contact your system Super Administrator to adjust your role access.
        </p>
    </div>
</div>
@endsection
