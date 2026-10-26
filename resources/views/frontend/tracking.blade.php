@extends('layouts.app')

@section('title', 'Order Tracking - ' . \App\Models\Setting::get('site_name', 'SAJ'))

@section('content')
<div class="px-4 lg:px-8 py-6 bg-gray-50 min-h-[70vh]">
    <div class="max-w-2xl mx-auto" x-data="orderTracking()">
        
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Order Tracking</h1>
            <p class="text-base text-gray-500">Enter your order ID to track your order status</p>
        </div>

        <!-- Search Box -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 mb-8">
            <form @submit.prevent="fetchOrder" class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Your Order ID:</label>
                    <input type="text" x-model="orderId" autocomplete="off" placeholder="e.g. 134472" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-colors" required>
                </div>
                <div class="sm:pt-6">
                    <button type="submit" class="w-full sm:w-auto px-8 py-3 bg-brand-600 text-white font-medium rounded-lg hover:bg-brand-700 focus:ring-4 focus:ring-brand-500/20 transition-all flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed cursor-pointer" :disabled="isLoading">
                        <i class="ph ph-magnifying-glass text-lg" x-show="!isLoading"></i>
                        <i class="ph ph-spinner animate-spin text-lg" x-show="isLoading" style="display: none;"></i>
                        <span x-text="isLoading ? 'Searching...' : 'Search'"></span>
                    </button>
                </div>
            </form>
            
            <!-- Error Message -->
            <div x-show="error" x-transition class="mt-4 p-3 bg-red-50 text-red-600 rounded-lg text-sm flex items-center gap-2 border border-red-100" style="display: none;">
                <i class="ph-fill ph-warning-circle text-lg shrink-0"></i>
                <span x-text="error"></span>
            </div>
        </div>

        <!-- Results Area -->
        <div x-show="order" x-transition.opacity.duration.300ms style="display: none;" class="space-y-6">
            
            <!-- Order Summary Card -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-row items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Order ID: <span x-text="order?.order_id"></span></h2>
                    <p class="text-base text-gray-500 mt-1">Status: <span class="font-medium capitalize text-brand-600" x-text="order?.status"></span></p>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-bold text-gray-900"><span x-text="order?.total"></span> ৳</p>
                    <p class="text-sm text-gray-500">Total Amount</p>
                </div>
            </div>

            <!-- Tracking Timeline -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h3 class="text-lg font-bold text-gray-900 mb-8">Order Progress</h3>
                
                <div class="relative pl-10 space-y-8 before:absolute before:inset-0 before:ml-[15px] before:top-2 before:h-[calc(100%-32px)] before:w-0.5 before:bg-gray-200">
                    <template x-for="(step, index) in steps" :key="index">
                        <div class="relative flex items-start gap-5 group">
                            
                            <!-- Icon/Marker -->
                            <div class="flex items-center justify-center w-8 h-8 rounded-full border-2 absolute left-[-40px] bg-white z-10"
                                 :class="{
                                     'border-green-500 text-green-500': step.completed && !step.isCurrent,
                                     'border-brand-500 text-brand-500 shadow-[0_0_0_4px_rgba(var(--color-brand-500),0.1)]': step.isCurrent,
                                     'border-gray-300 text-gray-300': !step.completed && !step.isCurrent,
                                     'border-red-500 text-red-500': step.isCancelled
                                 }">
                                <i class="ph text-lg" :class="{
                                    'ph-house': !step.completed && !step.isCurrent && !step.isCancelled && index === steps.length - 1,
                                    'ph-x': step.isCancelled,
                                    'ph-check': !step.isCancelled && (step.completed || step.isCurrent || index !== steps.length - 1)
                                }"></i>
                            </div>
                            
                            <!-- Content -->
                            <div class="flex-1 pb-2">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 mb-1">
                                    <h4 class="font-bold text-base" 
                                        :class="{ 
                                            'text-green-600': step.completed && !step.isCurrent, 
                                            'text-brand-600': step.isCurrent, 
                                            'text-gray-500': !step.completed && !step.isCurrent,
                                            'text-red-600': step.isCancelled 
                                        }" 
                                        x-text="step.title"></h4>
                                    <span class="text-xs font-medium text-gray-400" x-show="step.time" x-text="step.time"></span>
                                </div>
                                <p class="text-sm text-gray-500 leading-relaxed" 
                                   :class="{ 'opacity-60': !step.completed && !step.isCurrent }"
                                   x-text="step.desc"></p>
                            </div>
                            
                        </div>
                    </template>
                </div>
            </div>

            <!-- Shipping Address -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100" x-show="order?.shipping_address">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Shipping Address</h3>
                <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 text-sm text-gray-700">
                    <div class="grid grid-cols-[100px_1fr] gap-y-2">
                        <div class="text-gray-500 font-medium">Name:</div>
                        <div class="font-medium text-gray-900" x-text="order?.shipping_address?.name || 'N/A'"></div>
                        
                        <div class="text-gray-500 font-medium">Phone:</div>
                        <div class="font-medium text-gray-900" x-text="order?.shipping_address?.phone || 'N/A'"></div>
                        
                        <div class="text-gray-500 font-medium">Address:</div>
                        <div class="font-medium text-gray-900 leading-relaxed" x-text="order?.shipping_address?.address || 'N/A'"></div>
                        
                        <div class="text-gray-500 font-medium" x-show="order?.shipping_address?.city">City:</div>
                        <div class="font-medium text-gray-900" x-show="order?.shipping_address?.city" x-text="order?.shipping_address?.city"></div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('orderTracking', () => ({
        orderId: '',
        isLoading: false,
        error: null,
        order: null,
        
        // Define timeline steps logic
        get steps() {
            if (!this.order) return [];
            
            const status = this.order.status; // pending, confirmed, processing, shipped, delivered, cancelled, returned
            const timestamps = this.order.timestamps || {};
            
            const formatTime = (isoString) => {
                if (!isoString) return null;
                const date = new Date(isoString);
                return date.toLocaleString('en-US', { weekday: 'short', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
            };

            const createdTime = this.order.created_at;

            // Base template sequence
            let sequence = [
                { id: 'pending', title: 'Order Received', desc: 'Your order has been received and is being verified.', isCancelled: false },
                { id: 'confirmed', title: 'Order Confirmed', desc: 'Your order has been confirmed by our team.', isCancelled: false },
                { id: 'processing', title: 'Order Processing', desc: 'Your order is being prepared and packed.', isCancelled: false },
                { id: 'shipped', title: 'Order Shipped', desc: 'Your order has been handed over to the courier.', isCancelled: false },
                { id: 'delivered', title: 'Delivered', desc: 'Order has been successfully delivered to you.', isCancelled: false },
            ];

            if (status === 'cancelled') {
                sequence = [
                    { id: 'pending', title: 'Order Received', desc: 'Your order was received.', isCancelled: false, completed: true, time: createdTime },
                    { id: 'cancelled', title: 'Order Cancelled', desc: 'Your order has been cancelled.', isCancelled: true, completed: true, isCurrent: true, time: formatTime(timestamps['cancelled']) }
                ];
                return sequence;
            }
            
            if (status === 'returned') {
                 sequence.push({ id: 'returned', title: 'Returned', desc: 'Order was returned to seller.', isCancelled: true });
            }

            // Determine current index
            const statusIndex = sequence.findIndex(s => s.id === status);
            const activeIndex = statusIndex !== -1 ? statusIndex : 0;

            return sequence.map((step, index) => {
                let time = null;
                if (step.id === 'pending') time = createdTime;
                else if (timestamps[step.id]) time = formatTime(timestamps[step.id]);

                return {
                    ...step,
                    completed: index <= activeIndex,
                    isCurrent: index === activeIndex,
                    time: time
                };
            });
        },

        async fetchOrder() {
            if (!this.orderId) return;
            
            this.isLoading = true;
            this.error = null;
            this.order = null;

            try {
                const response = await fetch(`/api/order-tracking/${this.orderId}`);
                if (!response.ok) {
                    const data = await response.json();
                    throw new Error(data.error || 'Failed to fetch order tracking information.');
                }
                
                this.order = await response.json();
                
            } catch (err) {
                this.error = err.message;
            } finally {
                this.isLoading = false;
            }
        }
    }));
});
</script>
@endpush
@endsection
