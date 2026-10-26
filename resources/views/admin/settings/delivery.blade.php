@extends('layouts.admin')

@section('title', 'Delivery Settings - Admin Panel')
@section('header_title', 'Delivery Settings')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Back Button & Title -->
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('admin.settings.index') }}" class="w-10 h-10 rounded-full bg-white shadow-sm flex items-center justify-center text-gray-500 hover:text-brand-600 hover:shadow transition-all duration-300 border border-gray-100">
            <i class="ph ph-arrow-left text-xl"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Delivery Settings</h1>
            <p class="text-sm text-gray-500 mt-1">Configure shipping rates and zones for your customers.</p>
        </div>
    </div>

    <!-- Main Card -->
    <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-gray-100 relative overflow-hidden">
        <!-- Decorative Header Element -->
        <div class="absolute top-0 left-0 right-0 h-3 bg-gradient-to-r from-brand-400 to-brand-600"></div>

        <form action="{{ route('admin.settings.delivery.update') }}" method="POST" class="space-y-8 mt-4" x-data="{ isSubmitting: false }" @submit="isSubmitting = true">
            @csrf

            <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100">
                <div class="w-12 h-12 bg-brand-50 text-brand-600 rounded-xl flex items-center justify-center">
                    <i class="ph ph-truck text-2xl"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-800">Standard Delivery Charges</h2>
                    <p class="text-xs text-gray-500">These rates will be applied during checkout based on the customer's location.</p>
                </div>
            </div>

            <!-- Form Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <!-- Inside Dhaka -->
                <div class="flex flex-col gap-2 relative group">
                    <label class="text-sm font-bold text-gray-800 cursor-pointer" for="inside_dhaka">Inside Dhaka (TK)</label>
                    <div class="relative flex items-center">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-gray-400 group-focus-within:text-brand-500 transition-colors">
                            <i class="ph ph-map-pin"></i>
                        </div>
                        <input type="number" step="1" min="0" id="inside_dhaka" name="delivery_charge_inside_dhaka" value="{{ old('delivery_charge_inside_dhaka', $insideDhaka) }}" class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block pl-10 p-3 transition-colors duration-300" placeholder="e.g. 70" required>
                    </div>
                    @error('delivery_charge_inside_dhaka')
                        <p class="text-red-500 text-xs mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Sub Dhaka -->
                <div class="flex flex-col gap-2 relative group">
                    <label class="text-sm font-bold text-gray-800 cursor-pointer" for="sub_dhaka">Sub Dhaka (TK)</label>
                    <div class="relative flex items-center">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-gray-400 group-focus-within:text-brand-500 transition-colors">
                            <i class="ph ph-map-pin-line"></i>
                        </div>
                        <input type="number" step="1" min="0" id="sub_dhaka" name="delivery_charge_sub_dhaka" value="{{ old('delivery_charge_sub_dhaka', $subDhaka) }}" class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block pl-10 p-3 transition-colors duration-300" placeholder="e.g. 110" required>
                    </div>
                    @error('delivery_charge_sub_dhaka')
                        <p class="text-red-500 text-xs mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Outside Dhaka -->
                <div class="flex flex-col gap-2 relative group">
                    <label class="text-sm font-bold text-gray-800 cursor-pointer" for="outside_dhaka">Outside Dhaka (TK)</label>
                    <div class="relative flex items-center">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-gray-400 group-focus-within:text-brand-500 transition-colors">
                            <i class="ph ph-globe-hemisphere-west"></i>
                        </div>
                        <input type="number" step="1" min="0" id="outside_dhaka" name="delivery_charge_outside_dhaka" value="{{ old('delivery_charge_outside_dhaka', $outsideDhaka) }}" class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block pl-10 p-3 transition-colors duration-300" placeholder="e.g. 130" required>
                    </div>
                    @error('delivery_charge_outside_dhaka')
                        <p class="text-red-500 text-xs mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Free Delivery Min Order -->
                <div class="flex flex-col gap-2 relative group md:col-span-3 mt-4 pt-6 border-t border-gray-100">
                    <label class="text-sm font-bold text-gray-800 cursor-pointer" for="free_min_order">Free Delivery Minimum Order Amount (TK)</label>
                    <p class="text-xs text-gray-500">If the customer's cart total reaches this amount, the delivery charge will automatically become 0 (Free Delivery).</p>
                    <div class="relative flex items-center max-w-sm mt-2">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-brand-500">
                            <i class="ph-fill ph-gift text-lg"></i>
                        </div>
                        <input type="number" step="1" min="0" id="free_min_order" name="delivery_free_min_order" value="{{ old('delivery_free_min_order', $freeMinOrder ?? 3000) }}" class="w-full bg-brand-50/30 border border-brand-200 text-brand-900 font-semibold text-sm rounded-xl focus:ring-brand-500 focus:border-brand-500 block pl-12 p-3 transition-colors duration-300" placeholder="e.g. 3000" required>
                    </div>
                    @error('delivery_free_min_order')
                        <p class="text-red-500 text-xs mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>

            </div>

            <!-- Submit Action -->
            <div class="pt-6 mt-8 border-t border-gray-100 flex justify-end">
                <button type="submit" 
                        :disabled="isSubmitting"
                        :class="{'opacity-75 cursor-not-allowed': isSubmitting, 'cursor-pointer hover:-translate-y-0.5 hover:bg-brand-700 hover:shadow-lg hover:shadow-brand-500/30': !isSubmitting}"
                        class="inline-flex items-center gap-2 bg-brand-600 text-white px-8 py-3.5 rounded-xl font-bold transition-all duration-300 shadow-md transform">
                    <i class="ph ph-floppy-disk text-xl" x-show="!isSubmitting"></i>
                    <i class="ph ph-spinner-gap animate-spin text-xl" x-show="isSubmitting" style="display: none;"></i>
                    <span x-text="isSubmitting ? 'Saving...' : 'Save Delivery Settings'"></span>
                </button>
            </div>

        </form>
    </div>
</div>
@endsection
