@extends('layouts.admin')

@section('header_title', 'Order Details')

@push('styles')
<style>
@media print {
    body {
        visibility: hidden !important;
        background: #ffffff !important;
    }
    #printable-shipping-label,
    #printable-shipping-label * {
        visibility: visible !important;
    }
    #printable-shipping-label {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100mm !important;
        max-width: 100mm !important;
        margin: 0 !important;
        padding: 4mm !important;
        border: 2px solid #000000 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        background: #ffffff !important;
        color: #000000 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        z-index: 9999999 !important;
    }
    .no-print {
        display: none !important;
    }
    @page {
        size: auto;
        margin: 4mm;
    }
}
</style>
@endpush

@php
    $shippingInfo = is_array($order->shipping_address)
        ? $order->shipping_address
        : json_decode($order->shipping_address ?? '[]', true);
    $orderPhone = $shippingInfo['phone'] ?? optional($order->user)->phone ?? '';

    $steadfastServiceInstance = app(\App\Services\SteadfastService::class);
    $cleanCustomerPhone = $steadfastServiceInstance->formatPhone((string)$orderPhone);
    $cachedCustomerRecord = !empty($cleanCustomerPhone)
        ? \App\Models\CustomerDeliveryRecord::where('phone', $cleanCustomerPhone)->first()
        : null;
    $isOrderOlderThan10Days = $order->created_at ? $order->created_at->lt(now()->subDays(10)) : false;
    $initialFraudData = $cachedCustomerRecord ? $cachedCustomerRecord->toDisplayArray() : null;

    $rawTracking = is_array($order->tracking) ? $order->tracking : [];
    $trackingList = [];
    foreach ($rawTracking as $item) {
        $ts = $item['timestamp'] ?? ($item['updated_at'] ?? ($item['created_at'] ?? null));
        $dateObj = null;
        if ($ts) {
            try {
                $dateObj = \Carbon\Carbon::parse($ts)->setTimezone('Asia/Dhaka');
            } catch (\Throwable $e) {}
        }
        $formattedDate = $item['formatted_date'] ?? ($dateObj ? $dateObj->format('d M Y') : 'Recent');
        $formattedTime = $item['formatted_time'] ?? ($dateObj ? $dateObj->format('h:i a') : '');
        $note = $item['note'] ?? ($item['raw_note'] ?? ($item['message'] ?? ''));
        if (empty($note) && !empty($item['status'])) {
            $note = 'Consignment status: ' . ucfirst(str_replace(['_', '-'], ' ', $item['status']));
        }
        $trackingList[] = array_merge($item, [
            'timestamp'      => $dateObj ? $dateObj->toDateTimeString() : ($ts ?: now()->toDateTimeString()),
            'formatted_date' => $formattedDate,
            'formatted_time' => $formattedTime,
            'timezone'       => 'BDT',
            'note'           => $note,
        ]);
    }
    usort($trackingList, fn($a, $b) => strcmp($b['timestamp'] ?? '', $a['timestamp'] ?? ''));

    $rawAdminNotes = $order->admin_notes;
    $adminNotesList = [];
    if (!empty($rawAdminNotes)) {
        $decoded = json_decode($rawAdminNotes, true);
        if (is_array($decoded)) {
            $adminNotesList = $decoded;
        } else {
            $adminNotesList = [[
                'id'             => 1,
                'note'           => (string) $rawAdminNotes,
                'admin_name'     => 'System/Admin',
                'admin_id'       => null,
                'timestamp'      => $order->updated_at ? $order->updated_at->toDateTimeString() : now()->toDateTimeString(),
                'formatted_date' => $order->updated_at ? $order->updated_at->setTimezone('Asia/Dhaka')->format('d M Y') : now()->setTimezone('Asia/Dhaka')->format('d M Y'),
                'formatted_time' => $order->updated_at ? $order->updated_at->setTimezone('Asia/Dhaka')->format('h:i a') : now()->setTimezone('Asia/Dhaka')->format('h:i a'),
                'timezone'       => 'BDT',
            ]];
        }
    }

    $orderItemsJson = $order->items->map(function ($it) {
        $sku  = $it->variant_sku ?: ($it->product ? $it->product->sku : '');
        $info = is_array($it->variant_info) ? $it->variant_info : [];
        return [
            'id'         => $it->id,
            'product_id' => $it->product_id,
            'name'       => $it->name,
            'sku'        => $sku,
            'label'      => $info['label'] ?? null,
            'quantity'   => (int) $it->quantity,
            'image'      => $it->image,
            'return_qty' => (int) $it->quantity,
            'selected'   => true,
        ];
    })->values();

    $account  = $order->user;
    $guest    = is_array($order->guest_info)       ? $order->guest_info       : [];
    $shipping = is_array($order->shipping_address) ? $order->shipping_address : [];

    $customerName  = $account->name  ?? ($guest['name']  ?? ($shipping['name']  ?? 'Guest'));
    $customerPhone = $account->phone  ?? ($guest['phone'] ?? ($shipping['phone'] ?? null));
    $customerEmail = $account->email  ?? ($guest['email'] ?? null);

    $shipName    = $shipping['name']    ?? null;
    $shipPhone   = $shipping['phone']   ?? null;
    $shipAddress = collect([
        $shipping['address'] ?? null,
        $shipping['city']    ?? null,
        $shipping['zone']    ?? null,
    ])->filter()->implode(', ');
@endphp

@section('content')
<div x-data="orderActions(
    '{{ $order->order_id }}',
    '{{ $order->status }}',
    {{ $order->is_added_into_steadfast ? 'true' : 'false' }},
    '{{ $order->steadfast_consignment_id ?? '' }}',
    '{{ $order->steadfast_tracking_code ?? '' }}',
    '{{ $orderPhone }}',
    {{ json_encode($initialFraudData) }},
    {{ $isOrderOlderThan10Days ? 'true' : 'false' }},
    {{ $order->is_stock_deducted ? 'true' : 'false' }},
    {{ json_encode($orderItemsJson) }}
)">

    {{-- ─── Page Header ─── --}}
    <div class="mb-8 flex justify-between items-end">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold text-gray-900">Order {{ $order->order_id }}</h1>
                <button type="button"
                        @click="copyOrderId()"
                        :title="copied ? 'Copied' : 'Copy order ID'"
                        aria-label="Copy order ID"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-brand-600 hover:bg-gray-100 transition-colors cursor-pointer">
                    <i x-show="!copied" class="ph ph-copy text-lg"></i>
                    <i x-show="copied" style="display:none;" class="ph-bold ph-check text-lg text-green-600"></i>
                </button>
            </div>
            <p class="text-sm text-gray-500 mt-1 flex flex-wrap items-center gap-4">
                <a href="{{ route('admin.orders') }}"
                   class="text-brand-600 hover:text-brand-800 font-medium flex items-center gap-1">
                    <i class="ph ph-arrow-left"></i> Back to Orders
                </a>
                <span>Placed on {{ $order->created_at->format('F d, Y \a\t h:i A') }}</span>
                <span class="px-2 py-0.5 rounded text-[11px] font-bold uppercase tracking-wider"
                      :class="badgeClass(currentStatus)"
                      x-text="getStatusLabel(currentStatus)">
                    {{ ucfirst($order->status) }}
                </span>
                <span class="px-2 py-0.5 rounded text-[11px] font-bold uppercase tracking-wider bg-gray-100 text-gray-700 border border-gray-200">
                    Source: {{ ucfirst($order->order_source ?? 'website') }}
                </span>
            </p>
        </div>

        <div class="flex items-center gap-2">
            <template x-if="isSteadfastBooked">
                <button type="button"
                        @click="openPrintLabelModal()"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium cursor-pointer transition-colors flex items-center gap-1.5 shadow-xs">
                    <i class="ph-bold ph-printer text-base"></i>
                    <span>Print Label</span>
                </button>
            </template>
            @if(auth()->user()->hasRole('super_admin') || auth()->user()->can('edit_orders') || auth()->user()->can('manage_orders'))
            <a href="{{ route('admin.orders.edit', $order->order_id) }}"
               class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 cursor-pointer transition-colors flex items-center gap-1.5">
                <i class="ph ph-pencil-simple"></i> Edit Order
            </a>
            @endif
            <button type="button"
                    class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 cursor-pointer transition-colors">
                Print Invoice
            </button>
            @if(auth()->user()->hasRole('super_admin') || auth()->user()->can('manage_order_status') || auth()->user()->can('cancel_orders') || auth()->user()->can('manage_orders'))
            <button type="button"
                    @click="openStatusModal = true"
                    class="px-4 py-2 bg-brand-600 border border-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700 cursor-pointer transition-colors">
                Update Status
            </button>
            @endif
            @if(auth()->user()->hasRole('super_admin') || auth()->user()->can('delete_orders') || auth()->user()->can('manage_orders'))
            <button type="button"
                    @click="openDeleteModal = true"
                    class="px-4 py-2 bg-white border border-rose-200 text-rose-600 rounded-lg text-sm font-medium hover:bg-rose-50 cursor-pointer transition-colors flex items-center gap-1.5 shadow-2xs">
                <i class="ph ph-trash text-base"></i>
                <span>Delete Order</span>
            </button>
            @endif
        </div>
    </div>

    {{-- ─── Main Grid ─── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left Column (2/3) --}}
        <div class="lg:col-span-2 space-y-6">
            @include('admin.orders.partials.status-progress')
            @include('admin.orders.partials.order-items')
            @include('admin.orders.partials.admin-notes')
            @include('admin.orders.partials.courier-tracking')
            @include('admin.orders.partials.edit-history')
        </div>

        {{-- Right Sidebar (1/3) --}}
        <div class="space-y-6">
            @include('admin.orders.partials.customer-card')
            @include('admin.orders.partials.shipping-card')
            @include('admin.orders.partials.payment-summary')
            @include('admin.orders.partials.courier-card')
            @include('admin.orders.partials.workflow-action')
        </div>

    </div>

    {{-- ─── Modals ─── --}}
    @include('admin.orders.partials.modals')

</div>

{{-- ─── Alpine.js Logic ─── --}}
@include('admin.orders.partials.scripts')

@endsection
