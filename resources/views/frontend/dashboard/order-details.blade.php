@extends('layouts.app')
@section('title', 'Order Details #' . $order->order_id)

@section('content')
<div class="w-full px-4 lg:px-8 py-4 md:py-8">
    <div class="flex flex-col md:flex-row gap-4 md:gap-8">
        
        @if(Auth::check())
        <!-- Sidebar Menu -->
        <x-frontend.dashboard-sidebar active-tab="orders" />
        @endif

        <!-- Main Content Area -->
        <main class="flex-1 {{ Auth::check() ? '' : 'max-w-4xl mx-auto' }}">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Order Details</h1>
            <p class="text-gray-500 mt-1">Order #{{ $order->order_id }} <span class="mx-2">•</span> {{ \Carbon\Carbon::parse($order->created_at)->format('M d, Y h:i A') }}</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('dashboard.orders.invoice', $order->order_id) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white font-medium rounded-lg transition-colors">
                <i class="ph ph-receipt text-lg"></i>
                Invoice
            </a>
            @if(Auth::check())
            <a href="{{ route('dashboard.orders') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors">
                <i class="ph ph-arrow-left text-lg"></i>
                Back to Orders
            </a>
            @endif
        </div>
    </div>

    <!-- Order Status -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-1">Order Status</h3>
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium
                    @if($order->status === 'pending') bg-amber-100 text-amber-700
                    @elseif($order->status === 'processing') bg-blue-100 text-blue-700
                    @elseif($order->status === 'shipped') bg-purple-100 text-purple-700
                    @elseif($order->status === 'delivered') bg-green-100 text-green-700
                    @elseif($order->status === 'cancelled') bg-red-100 text-red-700
                    @else bg-gray-100 text-gray-700 @endif
                ">
                    <span class="capitalize">{{ $order->status }}</span>
                </div>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-1">Payment Status</h3>
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium
                    @if($order->payment_status === 'pending') bg-amber-100 text-amber-700
                    @elseif($order->payment_status === 'paid') bg-green-100 text-green-700
                    @elseif($order->payment_status === 'failed') bg-red-100 text-red-700
                    @else bg-gray-100 text-gray-700 @endif
                ">
                    <span class="capitalize">{{ $order->payment_status }}</span>
                </div>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-1">Payment Method</h3>
                <p class="font-medium text-gray-900 capitalize">{{ $order->payment_method === 'cod' ? 'Cash on Delivery' : $order->payment_method }}</p>
            </div>
        </div>
    </div>

    <!-- Order Items -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4 border-b border-gray-100 pb-3">Order Items</h3>
        <div class="overflow-x-auto custom-scrollbar pb-2">
            <table class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                    <tr class="text-xs font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100">
                        <th class="pb-3 font-medium min-w-[250px]">Product</th>
                        <th class="pb-3 font-medium text-center px-4">Price</th>
                        <th class="pb-3 font-medium text-center px-4">Qty</th>
                        <th class="pb-3 font-medium text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($order->items as $item)
                    <tr>
                        <td class="py-4 whitespace-normal">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-gray-50 rounded-lg overflow-hidden border border-gray-100 shrink-0">
                                    @if($item->image)
                                        <img src="{{ asset('storage/' . $item->image) }}" class="w-full h-full object-cover" alt="{{ $item->name }}">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-gray-300">
                                            <i class="ph ph-image text-xl"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="min-w-[150px] max-w-[200px] sm:max-w-[300px]">
                                    <p class="font-medium text-gray-900 line-clamp-2" title="{{ $item->name }}">{{ $item->name }}</p>
                                    @php $variantInfo = is_string($item->variant_info) ? json_decode($item->variant_info, true) : $item->variant_info; @endphp
                                    @if(!empty($variantInfo['label']))
                                        <p class="text-xs text-gray-500 mt-0.5">{{ $variantInfo['label'] }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="py-4 text-center text-gray-600 px-4">{{ number_format($item->price) }} ৳</td>
                        <td class="py-4 text-center text-gray-600 px-4">{{ $item->quantity }}</td>
                        <td class="py-4 text-right font-medium text-gray-900">{{ number_format($item->subtotal) }} ৳</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Customer Info -->
        <div class="md:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4 border-b border-gray-100 pb-3">Shipping Information</h3>
            @php 
                $shipping = is_string($order->shipping_address) ? json_decode($order->shipping_address, true) : $order->shipping_address; 
            @endphp
            <div class="space-y-3 text-gray-600">
                <div class="flex items-start gap-3">
                    <i class="ph ph-user mt-1 text-gray-400"></i>
                    <div>
                        <p class="font-medium text-gray-900">{{ $shipping['name'] ?? 'N/A' }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <i class="ph ph-phone mt-1 text-gray-400"></i>
                    <div>
                        <p>{{ $shipping['phone'] ?? 'N/A' }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <i class="ph ph-map-pin mt-1 text-gray-400"></i>
                    <div>
                        <p>{{ $shipping['address'] ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
            @if($order->order_notes)
            <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                <h4 class="text-xs font-bold text-gray-500 uppercase mb-1">Order Notes</h4>
                <p class="text-sm text-gray-700">{{ $order->order_notes }}</p>
            </div>
            @endif
        </div>

        <!-- Order Summary -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4 border-b border-gray-100 pb-3">Order Summary</h3>
            <div class="space-y-3 text-sm">
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
                    <span>Discount ({{ $order->coupon }})</span>
                    <span>-{{ number_format($order->coupon_discount) }} ৳</span>
                </div>
                @endif
                <div class="pt-3 border-t border-gray-200 flex justify-between items-center text-base font-bold text-gray-900">
                    <span>Total Amount</span>
                    <span>{{ number_format($order->total) }} ৳</span>
                </div>
            </div>
        </div>
    </div>
        </main>
    </div>
</div>
@endsection
