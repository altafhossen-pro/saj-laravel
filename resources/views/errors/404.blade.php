@extends('layouts.app')

@section('title', 'Page Not Found - ' . \App\Models\Setting::get('site_name', 'SAJ'))

@section('content')
<div class="h-full flex flex-col items-center justify-center p-6 min-h-[60vh]">
    <div class="text-center max-w-md mx-auto relative z-10">
        <!-- 404 Graphic -->
        <div class="relative flex justify-center items-center mb-6">
            <h1 class="leading-none font-black text-brand-600 flex items-center justify-center gap-2 select-none" style="font-size: 160px; text-shadow: 0 10px 20px rgba(0,0,0,0.05);">
                <span>4</span>
                <span class="text-brand-400 animate-pulse"><i class="ph-fill ph-ghost"></i></span>
                <span>4</span>
            </h1>
        </div>
        
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-3 mt-4">Oops! This page is missing.</h2>
        <p class="text-gray-500 mb-8 leading-relaxed">The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.</p>
        
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="{{ route('home') }}" class="w-full sm:w-auto px-8 py-3.5 bg-brand-600 text-white font-bold rounded-xl shadow-sm hover:bg-brand-700 hover:shadow transition-all flex items-center justify-center gap-2">
                <i class="ph ph-house text-xl"></i> Back to Homepage
            </a>
            <button onclick="window.history.length > 1 ? window.history.back() : window.location.href='/'" class="w-full sm:w-auto px-8 py-3.5 bg-white border border-gray-200 text-gray-700 font-bold rounded-xl hover:bg-gray-50 hover:border-gray-300 transition-all flex items-center justify-center gap-2 shadow-sm cursor-pointer">
                <i class="ph ph-arrow-left text-xl"></i> Go Back
            </button>
        </div>
    </div>
    
    <!-- Background Decor -->
    <div class="fixed inset-0 pointer-events-none flex items-center justify-center overflow-hidden z-0 opacity-40">
        <div class="absolute -top-[20%] -left-[10%] w-[500px] h-[500px] rounded-full bg-brand-50 blur-3xl mix-blend-multiply"></div>
        <div class="absolute top-[20%] -right-[10%] w-[400px] h-[400px] rounded-full bg-orange-50 blur-3xl mix-blend-multiply"></div>
        <div class="absolute -bottom-[20%] left-[20%] w-[600px] h-[600px] rounded-full bg-brand-50 blur-3xl mix-blend-multiply"></div>
    </div>
</div>
@endsection
