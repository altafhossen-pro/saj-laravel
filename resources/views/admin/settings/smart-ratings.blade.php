@extends('layouts.admin')

@section('title', 'Smart Ratings Settings - Admin Panel')
@section('header_title', 'Smart Ratings Settings')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 md:p-8">
            <div class="mb-8">
                <h2 class="text-xl font-bold text-gray-900">Smart Product Ratings</h2>
                <p class="text-sm text-gray-500 mt-1">Configure automatic realistic product ratings generation for SEO and conversion rate optimization.</p>
            </div>

            <form action="{{ route('admin.settings.smart-ratings.update') }}" method="POST" class="space-y-6">
                @csrf

                <!-- Enable/Disable -->
                <div class="bg-brand-50 p-6 rounded-xl border border-brand-100 mb-6">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="smart_rating_enabled" value="0">
                        <input type="checkbox" name="smart_rating_enabled" value="1" class="sr-only peer" {{ $smartRatingEnabled ? 'checked' : '' }}>
                        <div class="w-14 h-7 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-brand-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-brand-600"></div>
                        <span class="ml-3 text-base font-semibold text-gray-900">Enable Smart Ratings</span>
                    </label>
                    <p class="text-sm text-gray-600 mt-2 ml-[68px]">If enabled, products will automatically gain optimized ratings over time based on their creation date.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Interval Days -->
                    <div class="col-span-1 md:col-span-2">
                        <label for="smart_rating_interval_days" class="block text-sm font-semibold text-gray-700 mb-1">
                            Interval (Days)
                        </label>
                        <div class="relative">
                            <input type="number" name="smart_rating_interval_days" id="smart_rating_interval_days" value="{{ old('smart_rating_interval_days', $smartRatingInterval) }}" class="w-full pl-4 pr-12 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors" min="1" required>
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-gray-500 font-medium">
                                Days
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">On average, a new rating will be added every X days after the product is created. A slight random variation will be added automatically to make it look realistic.</p>
                        @error('smart_rating_interval_days')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Min Rating -->
                    <div>
                        <label for="smart_rating_min_rating" class="block text-sm font-semibold text-gray-700 mb-1">
                            Minimum Rating
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-amber-400">
                                <i class="ph-fill ph-star"></i>
                            </div>
                            <input type="number" step="0.1" name="smart_rating_min_rating" id="smart_rating_min_rating" value="{{ old('smart_rating_min_rating', $smartRatingMin) }}" class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors" min="1" max="5" required>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Example: 4.2</p>
                        @error('smart_rating_min_rating')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Max Rating -->
                    <div>
                        <label for="smart_rating_max_rating" class="block text-sm font-semibold text-gray-700 mb-1">
                            Maximum Rating
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-amber-400">
                                <i class="ph-fill ph-star"></i>
                            </div>
                            <input type="number" step="0.1" name="smart_rating_max_rating" id="smart_rating_max_rating" value="{{ old('smart_rating_max_rating', $smartRatingMax) }}" class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors" min="1" max="5" required>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Example: 5.0</p>
                        @error('smart_rating_max_rating')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Info Alert -->
                <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 flex gap-3 text-blue-800">
                    <i class="ph-fill ph-info text-2xl shrink-0"></i>
                    <div class="text-sm">
                        <p class="font-semibold mb-1">How it works with actual reviews</p>
                        <p>If a product receives real reviews from actual customers, the system will use a weighted average formula to seamlessly combine the real ratings with the smart ratings. Your actual customers' voices will still count!</p>
                    </div>
                </div>

                <div class="pt-6 border-t border-gray-100 flex justify-end gap-3">
                    <a href="{{ route('admin.settings.index') }}" class="px-6 py-2.5 rounded-xl font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-xl font-medium text-white bg-brand-600 hover:bg-brand-700 shadow-sm hover:shadow transition-all">
                        Save Configuration
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
