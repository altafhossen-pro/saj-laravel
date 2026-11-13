<!-- Shipping Details (entered at checkout - Matches Image 2) -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <h2 class="text-lg font-bold text-gray-900 mb-4">Shipping Details</h2>
    <div class="space-y-4">
        <div class="flex items-start gap-3">
            <i class="ph ph-user text-gray-400 text-lg mt-0.5"></i>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Receiver</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $shipName ?: 'Not provided' }}</p>
            </div>
        </div>
        <div class="flex items-start gap-3">
            <i class="ph ph-phone-call text-gray-400 text-lg mt-0.5"></i>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $shipPhone ?: 'Not provided' }}</p>
            </div>
        </div>
        <div class="flex items-start gap-3 pt-4 border-t border-gray-100">
            <i class="ph ph-map-pin text-gray-400 text-lg mt-0.5"></i>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Address</p>
                <p class="text-sm text-gray-900 mt-1 leading-relaxed">{{ $shipAddress ?: 'No address provided' }}</p>
            </div>
        </div>
        @if($order->order_notes)
        <div class="pt-3 border-t border-gray-100">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Customer Note</p>
            <div class="bg-amber-50 rounded-lg p-2.5 text-xs text-amber-900">
                {{ $order->order_notes }}
            </div>
        </div>
        @endif
    </div>
</div>
