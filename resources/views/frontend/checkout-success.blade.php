@extends('layouts.app')
@section('title', 'Order Placed Successfully')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-12">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        
        <!-- Header -->
        <div class="bg-brand-50/50 p-8 text-center border-b border-brand-100">
            <div class="w-20 h-20 bg-brand-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="ph-fill ph-check-circle text-5xl text-brand-600"></i>
            </div>
            <h1 class="text-3xl font-black text-gray-900 mb-2">Thank you for your order!</h1>
            <p class="text-gray-600 text-lg">Your order has been received and is now being processed.</p>
            
            <div class="inline-block mt-6 px-4 py-2 bg-white rounded-full border border-gray-200 shadow-sm">
                <span class="text-gray-500">Order ID:</span>
                <span class="font-bold text-gray-900 ml-2">#{{ $order->order_id }}</span>
            </div>
        </div>

        <!-- Customer & Order Details -->
        <div class="p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                <div>
                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">Shipping Information</h3>
                    @php 
                        $shipping = is_string($order->shipping_address) ? json_decode($order->shipping_address, true) : $order->shipping_address; 
                    @endphp
                    <div class="text-gray-700 space-y-1">
                        <p class="font-bold text-gray-900">{{ $shipping['name'] ?? '' }}</p>
                        <p>{{ $shipping['phone'] ?? '' }}</p>
                        <p>{{ $shipping['address'] ?? '' }}</p>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">Payment Method</h3>
                    <div class="text-gray-700">
                        <p class="font-medium">
                            @if($order->payment_method === 'cod')
                                Cash on Delivery
                            @else
                                {{ ucfirst($order->payment_method) }}
                            @endif
                        </p>
                        <p class="text-sm text-gray-500 mt-1">Status: <span class="capitalize text-amber-600 font-medium">{{ $order->payment_status }}</span></p>
                    </div>
                </div>
            </div>



            <!-- Summary -->
            <div class="bg-gray-50 p-6 rounded-xl space-y-3 text-sm">
                <div class="flex justify-between text-gray-600">
                    <span>Subtotal</span>
                    <span>{{ number_format($order->items->sum('subtotal')) }} ৳</span>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span>Delivery Charge</span>
                    <span>{{ number_format($order->shipping_cost) }} ৳</span>
                </div>
                @if($order->coupon_discount > 0)
                <div class="flex justify-between text-brand-600">
                    <span>Discount</span>
                    <span>-{{ number_format($order->coupon_discount) }} ৳</span>
                </div>
                @endif
                <div class="pt-3 border-t border-gray-200 flex justify-between items-center text-lg font-black text-gray-900">
                    <span>Total Amount</span>
                    <span>{{ number_format($order->total) }} ৳</span>
                </div>
            </div>

            <!-- Action -->
            <div class="mt-10 flex flex-col sm:flex-row justify-center items-center gap-4">
                <a href="/dashboard/my-orders/{{ $order->order_id }}" class="inline-flex items-center justify-center gap-2 px-8 py-3 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-lg transition-colors w-full sm:w-auto">
                    <i class="ph ph-receipt"></i>
                    View Order Details
                </a>
                <a href="{{ route('home') }}" class="inline-flex items-center justify-center gap-2 px-8 py-3 bg-gray-100 hover:bg-gray-200 text-gray-900 font-bold rounded-lg transition-colors w-full sm:w-auto">
                    <i class="ph ph-arrow-left"></i>
                    Continue Shopping
                </a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
{{-- 
    Note: Meta 'Purchase' event is strictly fired server-side via Conversions API (CAPI) 
    when Admin verifies and sets the order status from 'Pending' to 'Confirmed'.
    This prevents fake / unverified COD orders from polluting Meta ad pixel training.
--}}
@endpush
@endsection
