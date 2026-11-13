<!-- Payment Summary -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <h2 class="text-lg font-bold text-gray-900 mb-4">Payment Summary</h2>
    <div class="space-y-3 text-sm">
        <div class="flex justify-between text-gray-600">
            <p>Subtotal</p>
            <p>৳{{ number_format($order->items->sum('subtotal'), 2) }}</p>
        </div>
        <div class="flex justify-between text-gray-600">
            <p>Shipping Cost</p>
            <p>+ ৳{{ number_format($order->shipping_cost, 2) }}</p>
        </div>
        @if(($order->coupon_discount ?? 0) > 0)
        <div class="flex justify-between text-emerald-600 font-medium">
            <p class="flex items-center gap-1.5">
                <span>Coupon Discount</span>
                @if($order->coupon)
                    <span class="text-xs bg-emerald-50 text-emerald-700 border border-emerald-200 px-1.5 py-0.5 rounded font-mono font-bold">({{ $order->coupon }})</span>
                @endif
            </p>
            <p>- ৳{{ number_format($order->coupon_discount, 2) }}</p>
        </div>
        @endif
        @if(($order->discount ?? 0) > 0)
        <div class="flex justify-between text-green-600">
            <p>Special Discount</p>
            <p>- ৳{{ number_format($order->discount, 2) }}</p>
        </div>
        @endif
        <div class="pt-3 border-t border-gray-100 flex justify-between items-center">
            <p class="font-bold text-gray-900 text-base">Total Amount</p>
            <p class="font-bold text-brand-600 text-lg">৳{{ number_format($order->total, 2) }}</p>
        </div>
    </div>
    
    <div class="mt-6 pt-4 border-t border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-900">Payment Status</p>
                <p class="text-xs text-gray-500 mt-1">Method: {{ $order->payment_method ?? 'Cash on Delivery' }}</p>
            </div>
            <span class="px-3 py-1 rounded text-xs font-semibold uppercase tracking-wider border
                {{ $order->payment_status === 'paid' ? 'bg-green-50 text-green-600 border-green-200' : '' }}
                {{ $order->payment_status === 'pending' ? 'bg-amber-50 text-amber-600 border-amber-200' : '' }}
                {{ $order->payment_status === 'failed' ? 'bg-red-50 text-red-600 border-red-200' : '' }}
                {{ $order->payment_status === 'refunded' ? 'bg-orange-50 text-orange-600 border-orange-200' : '' }}">
                {{ $order->payment_status ?? 'Pending' }}
            </span>
        </div>
    </div>
</div>
