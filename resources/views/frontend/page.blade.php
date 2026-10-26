@extends('layouts.app')

@section('title', $title . ' - ' . \App\Models\Setting::get('site_name', 'SAJ'))

@section('content')
<div class="px-4 lg:px-8 py-8 md:py-12 bg-white min-h-[60vh] flex flex-col items-center justify-center text-center">
    <div class="w-16 h-16 bg-brand-50 text-brand-600 rounded-2xl flex items-center justify-center mb-6 mx-auto">
        <i class="ph ph-file-text text-3xl"></i>
    </div>
    <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">{{ $title }}</h1>
    <p class="text-lg text-gray-500 max-w-xl mx-auto leading-relaxed">
        We are currently working on our {{ $title }}. <br>
        This page will be updated with full details very soon.
    </p>
    
    <div class="mt-8 px-6 py-2 bg-brand-50 text-brand-700 rounded-lg border border-brand-100 font-semibold tracking-wide uppercase text-sm">
        Coming Soon
    </div>

    <a href="/" class="mt-8 text-gray-500 hover:text-brand-600 font-medium transition-colors inline-flex items-center gap-2">
        <i class="ph ph-arrow-left"></i> Back to Homepage
    </a>
</div>
@endsection
