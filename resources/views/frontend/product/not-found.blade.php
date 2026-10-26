@extends('layouts.app')

@section('title', 'Product Not Found - ' . \App\Models\Setting::get('site_name', 'SAJ'))

@section('meta')
    <meta name="robots" content="noindex, nofollow">
@endsection

@section('content')
<div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-24">
    <div class="text-center max-w-2xl mx-auto mb-20 md:mb-28 relative">
        <div class="inline-flex items-center justify-center w-32 h-32 rounded-full bg-brand-50 mb-8 relative shadow-sm">
            <i class="ph-fill ph-package text-7xl text-brand-500 opacity-50"></i>
            <div class="absolute top-0 right-0 w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-sm">
                <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                    <i class="ph-bold ph-x text-red-500 text-sm"></i>
                </div>
            </div>
        </div>
        
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4 tracking-tight">Product Unavailable</h1>
        <p class="text-gray-500 text-lg mb-8 leading-relaxed">
            We're sorry, but the product you are looking for is currently out of stock, has been removed, or the link is incorrect.
        </p>
        
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <button onclick="window.history.length > 1 ? window.history.back() : window.location.href='/'" class="w-full sm:w-auto px-6 py-3 bg-white border border-gray-200 text-gray-700 font-bold rounded-xl hover:bg-gray-50 hover:border-gray-300 transition-all flex items-center justify-center gap-2 shadow-sm cursor-pointer">
                <i class="ph ph-arrow-left text-xl"></i> Go Back
            </button>
            <a href="{{ route('home') }}" class="w-full sm:w-auto px-6 py-3 bg-brand-600 text-white font-bold rounded-xl shadow-sm hover:bg-brand-700 transition-all flex items-center justify-center gap-2">
                <i class="ph ph-storefront text-xl"></i> Browse All Items
            </a>
        </div>
    </div>

    @if($suggestedProducts && $suggestedProducts->count() > 0)
    <div class="border-t border-gray-100 pt-16 md:pt-20">
        <div class="flex items-center justify-center mb-10">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900 flex items-center gap-2">
                <i class="ph-fill ph-sparkle text-amber-400"></i> You May Also Like
            </h2>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3 sm:gap-4 lg:gap-5">
            @foreach($suggestedProducts as $suggested)
                <x-frontend.product-card :item="$suggested" />
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
