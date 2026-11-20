@extends('layouts.admin')

@section('header_title', 'Order Details')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800;900&display=swap" rel="stylesheet">
<style>
/* Label Typography */
#printable-shipping-label {
    font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    letter-spacing: -0.01em;
}
#printable-shipping-label .font-bn {
    font-family: 'Hind Siliguri', 'Segoe UI', Arial, sans-serif !important;
}

/* Modal Preview Dimensions - Real 3x3 Aspect Ratio */
#printable-shipping-label.size-3x3 {
    width: 330px !important;
    height: 330px !important;
    max-width: 330px !important;
    max-height: 330px !important;
    aspect-ratio: 1 / 1 !important;
    padding: 10px !important;
    box-sizing: border-box !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: space-between !important;
}

#printable-shipping-label.size-2x3 {
    width: 330px !important;
    height: 220px !important;
    max-width: 330px !important;
    max-height: 220px !important;
    aspect-ratio: 3 / 2 !important;
    padding: 8px !important;
    box-sizing: border-box !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: space-between !important;
}

@media print {
    html, body {
        visibility: hidden !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
        width: 100% !important;
        height: 100% !important;
    }
    #printable-shipping-label,
    #printable-shipping-label * {
        visibility: visible !important;
    }
    #printable-shipping-label {
        position: absolute !important;
        left: 8mm !important;
        top: 8mm !important;
        transform: none !important;
        margin: 0 !important;
        border: 2px solid #000000 !important;
        border-radius: 4px !important;
        box-shadow: none !important;
        background: #ffffff !important;
        color: #000000 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        z-index: 9999999 !important;
        box-sizing: border-box !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: space-between !important;
    }
    /* Exact Physical Dimensions for Print */
    #printable-shipping-label.size-3x3 {
        width: 76.2mm !important;
        height: 76.2mm !important;
        max-width: 76.2mm !important;
        max-height: 76.2mm !important;
        padding: 3mm !important;
    }
    #printable-shipping-label.size-2x3 {
        width: 76.2mm !important;
        height: 50.8mm !important;
        max-width: 76.2mm !important;
        max-height: 50.8mm !important;
        padding: 2mm !important;
    }
    .no-print {
        display: none !important;
    }
    @page {
        size: A4 portrait;
        margin: 0;
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
        $note = $item['text'] ?? ($item['note'] ?? ($item['raw_note'] ?? ($item['message'] ?? '')));
        $statusVal = $item['status'] ?? null;
        if (empty($note)) {
            if (!empty($statusVal) && $statusVal !== 'undefined') {
                $note = 'Consignment status: ' . ucfirst(str_replace(['_', '-'], ' ', $statusVal));
            } else {
                $note = 'Consignment created by Sender(API).';
                $statusVal = 'in_review';
            }
        }
        $trackingList[] = array_merge($item, [
            'status'         => $statusVal ?: 'in_review',
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
    $statusTimestampsArray = is_array($order->status_timestamps) ? $order->status_timestamps : [];
    $initialFbPurchaseSentAt = $statusTimestampsArray['fb_purchase_sent_at'] ?? null;
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
    {{ json_encode($orderItemsJson) }},
    '{{ $initialFbPurchaseSentAt }}'
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
            <!-- Manual Send Purchase Event to Meta (Shows ONLY if not sent yet) -->
            <template x-if="!fbPurchaseSentAt">
                <button type="button"
                        @click="openMetaPurchaseModal()"
                        class="px-4 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-lg text-sm font-semibold cursor-pointer transition-colors flex items-center gap-1.5 shadow-2xs"
                        title="Manually send Purchase event to Meta Conversions API">
                    <i class="ph-bold ph-share-network text-base text-blue-600"></i>
                    <span>Send Purchase Event (Meta)</span>
                </button>
            </template>
            <template x-if="fbPurchaseSentAt">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200"
                      :title="'Purchase event sent to Meta on ' + fbPurchaseSentAt">
                    <i class="ph-bold ph-check-circle text-blue-600 text-sm"></i>
                    <span>Meta Purchase Sent</span>
                </span>
            </template>

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
