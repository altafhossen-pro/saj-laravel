@extends('layouts.admin')

@section('header_title', 'Order Details')

@php
    $shippingInfo = is_array($order->shipping_address) ? $order->shipping_address : json_decode($order->shipping_address ?? '[]', true);
    $orderPhone = $shippingInfo['phone'] ?? optional($order->user)->phone ?? '';
@endphp

@section('content')
<div x-data="orderActions('{{ $order->order_id }}', '{{ $order->status }}', {{ $order->is_added_into_steadfast ? 'true' : 'false' }}, '{{ $order->steadfast_consignment_id ?? '' }}', '{{ $order->steadfast_tracking_code ?? '' }}', '{{ $orderPhone }}')">
<div class="mb-8 flex justify-between items-end">
    <div>
        <div class="flex items-center gap-2">
            <h1 class="text-2xl font-bold text-gray-900">Order {{ $order->order_id }}</h1>
            <button type="button" @click="copyOrderId()" :title="copied ? 'Copied' : 'Copy order ID'" aria-label="Copy order ID"
                class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-brand-600 hover:bg-gray-100 transition-colors cursor-pointer">
                <i x-show="!copied" class="ph ph-copy text-lg"></i>
                <i x-show="copied" style="display: none;" class="ph-bold ph-check text-lg text-green-600"></i>
            </button>
        </div>
        <p class="text-sm text-gray-500 mt-1 flex flex-wrap items-center gap-4">
            <a href="{{ route('admin.orders') }}" class="text-brand-600 hover:text-brand-800 font-medium flex items-center gap-1">
                <i class="ph ph-arrow-left"></i> Back to Orders
            </a>
            <span>Placed on {{ $order->created_at->format('F d, Y \a\t h:i A') }}</span>
            <span class="px-2 py-0.5 rounded text-[11px] font-bold uppercase tracking-wider"
                :class="badgeClass(currentStatus)" x-text="getStatusLabel(currentStatus)">
                {{ ucfirst($order->status) }}
            </span>
            <span class="px-2 py-0.5 rounded text-[11px] font-bold uppercase tracking-wider bg-gray-100 text-gray-700 border border-gray-200">
                Source: {{ ucfirst($order->order_source ?? 'website') }}
            </span>
        </p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('admin.orders.edit', $order->order_id) }}" class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 cursor-pointer transition-colors flex items-center gap-1.5">
            <i class="ph ph-pencil-simple"></i> Edit Order
        </a>
        <button type="button" class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 cursor-pointer transition-colors">Print Invoice</button>
        <button type="button" @click="openStatusModal = true" class="px-4 py-2 bg-brand-600 border border-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700 cursor-pointer transition-colors">
            Update Status
        </button>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <!-- Order Status Progress -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-6">Order Status</h2>

            <div x-show="isTerminalBad" style="display: none;" class="flex items-center gap-3 bg-red-50 border border-red-100 text-red-700 rounded-xl px-4 py-3">
                <i class="ph-fill ph-x-circle text-2xl"></i>
                <div>
                    <p class="font-bold text-sm" x-text="'This order was ' + currentStatus"></p>
                    <p class="text-xs text-red-600/80">No further steps will be processed.</p>
                </div>
            </div>

            <div x-show="!isTerminalBad" class="flex items-start">
                <template x-for="(step, i) in steps" :key="step">
                    <div class="contents">
                        <div class="flex flex-col items-center text-center w-16 shrink-0">
                            <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold border-2 transition-all duration-300"
                                 :class="step === currentStatus ? 'bg-brand-600 border-brand-600 text-white ring-4 ring-brand-100' : (isDone(step) ? 'bg-brand-600 border-brand-600 text-white' : 'bg-white border-gray-200 text-gray-300')">
                                <i x-show="isDone(step) || step === currentStatus" class="ph-bold ph-check"></i>
                                <span x-show="!isDone(step) && step !== currentStatus" class="w-2 h-2 rounded-full bg-gray-200"></span>
                            </div>
                            <span class="mt-2 text-xs" :class="step === currentStatus ? 'font-bold text-brand-700' : (isDone(step) ? 'font-medium text-gray-700' : 'text-gray-400')" x-text="getStatusLabel(step)"></span>
                        </div>
                        <div x-show="i < steps.length - 1" class="flex-1 h-0.5 mt-[17px] transition-colors duration-300" :class="isDone(step) ? 'bg-brand-600' : 'bg-gray-200'"></div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Order Items -->

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-lg font-bold text-gray-900">Order Items ({{ $order->items->count() }})</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-gray-50 text-gray-500 font-medium">
                        <tr>
                            <th class="px-6 py-4">Product</th>
                            <th class="px-6 py-4 text-center">Qty</th>
                            <th class="px-6 py-4 text-right">Unit Price</th>
                            <th class="px-6 py-4 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($order->items as $item)
                        @php
                            $variant = ($item->product && $item->variant_sku)
                                ? $item->product->variants->firstWhere('sku', $item->variant_sku)
                                : null;

                            $variantImage = null;
                            if ($variant && is_array($variant->images) && count($variant->images)) {
                                $first = collect($variant->images)->first();
                                $variantImage = is_array($first) ? ($first['url'] ?? $first['path'] ?? $first['image'] ?? null) : $first;
                            }
                            $imagePath = $variantImage ?: ($item->product->featured_image ?? $item->image);
                            $imageUrl = $imagePath
                                ? (\Illuminate\Support\Str::startsWith($imagePath, ['http://', 'https://']) ? $imagePath : \Illuminate\Support\Facades\Storage::url($imagePath))
                                : null;

                            $info = is_array($item->variant_info) ? $item->variant_info : [];
                            $variantLabel = $info['label'] ?? null;
                            $variantAttrs = (!$variantLabel && isset($info['attributes']) && is_array($info['attributes'])) ? $info['attributes'] : [];
                        @endphp
                        <tr>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-lg bg-gray-100 flex-shrink-0 overflow-hidden">
                                        @if($imageUrl)
                                            <img src="{{ $imageUrl }}" alt="{{ $item->name }}" class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-gray-400">
                                                <i class="ph ph-image text-xl"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 line-clamp-1">{{ $item->name }}</p>
                                        @if($variantLabel)
                                            <span class="inline-block mt-0.5 bg-gray-100 px-1.5 py-0.5 rounded text-xs text-gray-500">{{ $variantLabel }}</span>
                                        @elseif(count($variantAttrs))
                                            <p class="text-xs text-gray-500 mt-0.5">
                                                @foreach($variantAttrs as $key => $value)
                                                    <span class="inline-block bg-gray-100 px-1.5 py-0.5 rounded mr-1">{{ is_string($key) ? ucfirst($key) . ': ' : '' }}{{ is_array($value) ? implode(', ', $value) : $value }}</span>
                                                @endforeach
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center font-medium">{{ $item->quantity }}</td>
                            <td class="px-6 py-4 text-right text-gray-600">৳{{ number_format($item->price, 2) }}</td>
                            <td class="px-6 py-4 text-right font-medium text-gray-900">৳{{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

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
        
        <!-- Order Logs -->
        @if($order->logs->count() > 0)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mt-6">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-lg font-bold text-gray-900">Order Edit History</h2>
            </div>
            <div class="divide-y divide-gray-100 max-h-96 overflow-y-auto">
                @foreach($order->logs as $log)
                <div class="p-6 hover:bg-gray-50 transition-colors">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <p class="font-medium text-gray-900">Edited by {{ $log->user ? $log->user->name : 'System/Admin' }}</p>
                            <p class="text-xs text-gray-500">{{ $log->created_at->format('M d, Y h:i A') }}</p>
                        </div>
                    </div>
                    @php
                        $old = $log->old_data ?? [];
                        $new = $log->new_data ?? [];
                        $changes = [];
                        
                        $fieldsToCheck = ['status', 'payment_status', 'shipping_cost', 'discount', 'coupon_discount', 'total', 'order_notes', 'admin_notes'];
                        foreach ($fieldsToCheck as $field) {
                            if (isset($old[$field]) && isset($new[$field]) && $old[$field] != $new[$field]) {
                                $changes[] = [
                                    'field' => ucfirst(str_replace('_', ' ', $field)),
                                    'old' => $old[$field],
                                    'new' => $new[$field]
                                ];
                            }
                        }
                        
                        // Check if items changed
                        $oldItems = collect($old['items'] ?? [])->keyBy('id');
                        $newItems = collect($new['items'] ?? [])->keyBy('id');
                        
                        if ($oldItems->count() !== $newItems->count() || $oldItems->toJson() !== $newItems->toJson()) {
                            $changes[] = [
                                'field' => 'Order Items',
                                'old' => 'Modified',
                                'new' => 'Updated'
                            ];
                        }
                    @endphp

                    @if(count($changes) > 0)
                    <div class="mt-3 bg-white rounded-lg p-0 border border-gray-200 overflow-hidden">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="bg-gray-50 text-gray-500 font-medium border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-2 w-1/3">Field</th>
                                    <th class="px-4 py-2 w-1/3">Previous Value</th>
                                    <th class="px-4 py-2 w-1/3">New Value</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($changes as $change)
                                <tr>
                                    <td class="px-4 py-2 text-gray-900">{{ $change['field'] }}</td>
                                    <td class="px-4 py-2 text-red-600 line-through truncate max-w-[150px]" title="{{ $change['old'] }}">{{ $change['old'] ?: 'Empty' }}</td>
                                    <td class="px-4 py-2 text-green-600 font-medium truncate max-w-[150px]" title="{{ $change['new'] }}">{{ $change['new'] ?: 'Empty' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="mt-2 text-sm text-gray-500 italic">No specific field changes tracked in this update.</p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    
    <div class="space-y-6">
        @php
            $account = $order->user;
            $guest = is_array($order->guest_info) ? $order->guest_info : [];
            $shipping = is_array($order->shipping_address) ? $order->shipping_address : [];

            // Customer card: account profile (guest info for guest orders)
            $customerName = $account->name ?? ($guest['name'] ?? 'Guest');
            $customerPhone = $account->phone ?? ($guest['phone'] ?? null);
            $customerEmail = $account->email ?? ($guest['email'] ?? null);

            // Shipping card: details entered at checkout
            $shipName = $shipping['name'] ?? null;
            $shipPhone = $shipping['phone'] ?? null;
            $shipAddress = collect([$shipping['address'] ?? null, $shipping['city'] ?? null, $shipping['zone'] ?? null])->filter()->implode(', ');
        @endphp

        <!-- Customer (account profile) -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">Customer</h2>

            <div class="flex items-center gap-3 mb-6">
                @if($account && $account->avatar)
                    <img src="{{ $account->avatar }}" alt="{{ $customerName }}" class="w-12 h-12 rounded-full object-cover border border-gray-200">
                @else
                    <div class="w-12 h-12 rounded-full bg-brand-50 flex items-center justify-center text-brand-600 font-bold text-xl">
                        {{ strtoupper(substr($customerName, 0, 1)) }}
                    </div>
                @endif
                <div>
                    @if($account)
                        <a href="{{ route('admin.customers.show', $account->id) }}" class="font-bold text-gray-900 hover:text-brand-600 hover:underline transition-colors inline-flex items-center gap-1.5" title="View Customer Details">
                            <span>{{ $customerName }}</span>
                            <i class="ph ph-arrow-square-out text-sm text-gray-400"></i>
                        </a>
                    @else
                        <h3 class="font-bold text-gray-900">{{ $customerName }}</h3>
                    @endif
                    <p class="text-sm text-gray-500">{{ $account ? 'Customer since ' . $account->created_at->format('M Y') : 'Guest checkout' }}</p>
                </div>
            </div>

            <div class="space-y-4">
                <div class="flex items-start gap-3">
                    <i class="ph ph-envelope-simple text-gray-400 text-lg mt-0.5"></i>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Email</p>
                        <p class="text-sm text-gray-900 mt-0.5">{{ $customerEmail ?: 'Not provided' }}</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <i class="ph ph-phone text-gray-400 text-lg mt-0.5"></i>
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</p>
                        <p class="text-sm text-gray-900 mt-0.5">{{ $customerPhone ?: 'Not provided' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Shipping (entered at checkout) -->
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
            </div>
        </div>

        <!-- Steadfast Courier Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center font-bold text-lg border border-orange-100">
                        <i class="ph-bold ph-truck"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-gray-900 leading-tight">Steadfast Courier</h2>
                        <p class="text-[11px] text-gray-500">Parcel Delivery & Tracking</p>
                    </div>
                </div>
                <template x-if="isSteadfastBooked">
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <i class="ph-bold ph-check text-xs"></i> Booked
                    </span>
                </template>
                <template x-if="!isSteadfastBooked">
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600 border border-gray-200">
                        Not Sent
                    </span>
                </template>
            </div>

            <!-- When Booked -->
            <div x-show="isSteadfastBooked" class="space-y-3">
                <div class="bg-gray-50 rounded-xl p-3.5 border border-gray-200/70 space-y-2 text-xs">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500 font-medium">Consignment ID:</span>
                        <div class="flex items-center gap-1.5">
                            <span class="font-mono font-bold text-gray-900" x-text="'#' + (steadfastCid || '{{ $order->steadfast_consignment_id }}')"></span>
                            <button type="button" @click="copyText(steadfastCid || '{{ $order->steadfast_consignment_id }}')" title="Copy CID" class="text-gray-400 hover:text-brand-600 cursor-pointer">
                                <i class="ph ph-copy text-sm"></i>
                            </button>
                        </div>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500 font-medium">Tracking Code:</span>
                        <div class="flex items-center gap-1.5">
                            <span class="font-mono font-bold text-gray-900" x-text="steadfastTracking || '{{ $order->steadfast_tracking_code }}'"></span>
                            <button type="button" @click="copyText(steadfastTracking || '{{ $order->steadfast_tracking_code }}')" title="Copy Tracking" class="text-gray-400 hover:text-brand-600 cursor-pointer">
                                <i class="ph ph-copy text-sm"></i>
                            </button>
                        </div>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500 font-medium">COD Amount:</span>
                        <span class="font-bold text-gray-900">৳{{ number_format($order->steadfast_collected_amount ?: ($order->payment_status === 'paid' ? 0 : $order->total), 0) }}</span>
                    </div>
                </div>

                <!-- Live Status Check Alert -->
                <div x-show="liveStatus" class="p-3 bg-blue-50/70 border border-blue-200 rounded-xl text-xs text-blue-900">
                    <div class="flex items-center justify-between">
                        <span class="font-semibold text-blue-700">Steadfast Status:</span>
                        <span class="px-2 py-0.5 rounded font-bold uppercase text-[10px] bg-blue-600 text-white" x-text="liveStatus"></span>
                    </div>
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <a :href="'https://steadfast.com.bd/t/' + (steadfastTracking || '{{ $order->steadfast_tracking_code }}')"
                       target="_blank"
                       rel="noopener"
                       class="flex-1 py-2 px-3 text-center bg-gray-900 hover:bg-gray-800 text-white font-medium text-xs rounded-xl transition-colors inline-flex items-center justify-center gap-1.5 shadow-sm">
                        <i class="ph ph-arrow-square-out text-sm"></i> Track on Steadfast
                    </a>
                    <button type="button"
                            @click="checkSteadfastLiveStatus()"
                            :disabled="checkingLiveStatus"
                            class="py-2 px-3 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-medium text-xs rounded-xl transition-colors inline-flex items-center justify-center gap-1 cursor-pointer disabled:opacity-60">
                        <i class="ph ph-arrows-clockwise text-sm" :class="checkingLiveStatus ? 'animate-spin' : ''"></i>
                        <span x-text="checkingLiveStatus ? 'Checking...' : 'Check Status'"></span>
                    </button>
                </div>
            </div>

            <!-- When Not Booked -->
            <div x-show="!isSteadfastBooked" class="space-y-3">
                <template x-if="currentStatus === 'processing'">
                    <div>
                        <p class="text-xs text-gray-500 leading-relaxed mb-3">
                            This order is in processing. You can book it to Steadfast now or check "Add to Steadfast" when updating status to Shipped.
                        </p>
                        <div class="p-3 bg-amber-50/70 border border-amber-200 rounded-xl text-xs flex justify-between items-center text-amber-900 mb-3">
                            <span>Applicable COD:</span>
                            <strong class="font-bold">৳{{ number_format($order->payment_status === 'paid' ? 0 : $order->total, 0) }}</strong>
                        </div>
                        <button type="button"
                                @click="bookSteadfastDirect()"
                                :disabled="steadfastLoading"
                                class="w-full py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-medium text-xs rounded-xl transition-colors inline-flex items-center justify-center gap-2 shadow-sm cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                            <i x-show="steadfastLoading" class="ph ph-spinner animate-spin text-sm"></i>
                            <i x-show="!steadfastLoading" class="ph-bold ph-paper-plane-tilt text-sm"></i>
                            <span x-text="steadfastLoading ? 'Booking with Steadfast...' : 'Send to Steadfast Courier Now'"></span>
                        </button>
                    </div>
                </template>
                <template x-if="currentStatus !== 'processing'">
                    <div class="p-3.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-500 text-center">
                        <i class="ph ph-info text-base text-gray-400 mb-1 block"></i>
                        <span>Order must be moved to <strong class="text-gray-700">Processing</strong> status before sending to Steadfast.</span>
                    </div>
                </template>
            </div>

            <!-- Customer Delivery & Fraud Record Widget -->
            @if($orderPhone)
            <div class="mt-4 pt-4 border-t border-gray-100">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-gray-700 flex items-center gap-1.5">
                        <i class="ph-bold ph-shield-check text-brand-600"></i> Customer Delivery Record
                    </span>
                    <button type="button"
                            @click="checkFraudScore()"
                            :disabled="checkingFraud"
                            class="text-xs text-brand-600 hover:text-brand-800 font-medium cursor-pointer inline-flex items-center gap-1 disabled:opacity-50">
                        <i class="ph ph-magnifying-glass text-xs" :class="checkingFraud ? 'animate-spin' : ''"></i>
                        <span x-text="checkingFraud ? 'Checking...' : (fraudCheckData ? 'Recheck' : 'Check Record')"></span>
                    </button>
                </div>

                <div x-show="fraudCheckData" x-transition class="p-3 bg-gray-50 rounded-xl border border-gray-200 text-xs space-y-1.5 mt-2">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500">Delivery Success:</span>
                        <span class="font-bold text-emerald-600" x-text="(fraudCheckData?.delivery_ratio ?? 'N/A') + (fraudCheckData?.delivery_ratio !== null ? '%' : '')"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500">Cancellation Rate:</span>
                        <span class="font-bold text-red-600" x-text="(fraudCheckData?.cancellation_ratio ?? 'N/A') + (fraudCheckData?.cancellation_ratio !== null ? '%' : '')"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500">Order Volume:</span>
                        <span class="font-semibold text-gray-800 uppercase text-[10px]" x-text="fraudCheckData?.volume_band || 'Unknown'"></span>
                    </div>
                    <div class="flex justify-between items-center" x-show="fraudCheckData?.total_reports">
                        <span class="text-gray-500">Merchant Reports:</span>
                        <span class="font-bold text-amber-600" x-text="fraudCheckData?.total_reports || 0"></span>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Admin Notes -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-bold text-gray-900">Notes</h2>
                <button type="button" class="text-sm text-brand-600 font-medium hover:text-brand-800 cursor-pointer">Edit</button>
            </div>
            @if($order->order_notes)
                <div class="mb-4">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Customer Note</p>
                    <div class="bg-amber-50 rounded-lg p-3 text-sm text-amber-900">
                        {{ $order->order_notes }}
                    </div>
                </div>
            @endif
            <div class="mb-6">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Admin Note</p>
                @if($order->admin_notes)
                    <p class="text-sm text-gray-700 bg-gray-50 p-3 rounded-lg border border-gray-200">{{ $order->admin_notes }}</p>
                @else
                    <p class="text-sm text-gray-400 italic">No admin notes added.</p>
                @endif
            </div>

            <!-- Proceed to Next Step -->
            <template x-if="nextStatus">
                <button type="button" @click="openConfirmModal = true" :disabled="loading" class="w-full py-3.5 bg-brand-600 hover:bg-brand-700 text-white font-medium rounded-xl shadow-sm transition-colors cursor-pointer flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">
                    <i x-show="loading" class="ph ph-spinner animate-spin text-lg"></i>
                    <span x-text="loading ? 'Updating...' : 'Proceed to ' + getStatusLabel(nextStatus)"></span>
                    <i x-show="!loading" class="ph ph-arrow-right"></i>
                </button>
            </template>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div x-show="openStatusModal" 
     style="display: none;"
     class="fixed inset-0 z-[100] overflow-y-auto" 
     aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div x-show="openStatusModal" x-transition.opacity class="fixed inset-0 z-0 bg-gray-900/50 backdrop-blur-sm cursor-pointer" aria-hidden="true" @click="if (!loading) openStatusModal = false"></div>
        <div x-show="openStatusModal" x-transition.scale class="relative z-10 bg-white rounded-2xl text-left overflow-hidden shadow-xl sm:max-w-md w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-brand-50 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="ph ph-list-dashes text-brand-600 text-xl"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Update Order Status</h3>
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select new status</label>
                            <select x-model="selectedStatus" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500 sm:text-sm h-11 px-3 border cursor-pointer">
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="processing">Processing</option>
                                <option value="shipped">Shipped</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="returned">Returned</option>
                            </select>
                        </div>

                        <!-- Add to Steadfast Option in Modal (only when current status is processing and target is shipped) -->
                        <div x-show="currentStatus === 'processing' && selectedStatus === 'shipped'" x-transition class="mt-4 p-3.5 rounded-xl border bg-brand-50/60 border-brand-200">
                            <template x-if="isSteadfastBooked">
                                <div class="flex items-center gap-2.5 text-emerald-800 text-xs font-medium">
                                    <i class="ph-fill ph-check-circle text-emerald-600 text-lg shrink-0"></i>
                                    <div>
                                        <p class="font-bold text-gray-900">Already booked in Steadfast</p>
                                        <p class="text-[11px] text-gray-600 mt-0.5">Consignment ID: #<span x-text="steadfastCid" class="font-mono font-semibold"></span></p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="!isSteadfastBooked">
                                <label class="flex items-start gap-3 cursor-pointer select-none">
                                    <input type="checkbox" x-model="sendToSteadfast" class="mt-0.5 w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-gray-300 cursor-pointer">
                                    <div class="flex-1">
                                        <span class="text-sm font-bold text-gray-900 flex items-center gap-1.5">
                                            <i class="ph-bold ph-truck text-brand-600"></i> Add to Steadfast Courier
                                        </span>
                                        <p class="text-xs text-gray-600 mt-1 leading-normal">
                                            Auto-creates parcel in Steadfast with COD amount: 
                                            <strong class="text-brand-700">৳{{ number_format($order->payment_status === 'paid' ? 0 : $order->total, 0) }}</strong>.
                                        </p>
                                    </div>
                                </label>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button @click="updateStatus(selectedStatus)" type="button" class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-transparent shadow-sm px-4 py-2 bg-brand-600 text-base font-medium text-white hover:bg-brand-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer transition-colors" :disabled="loading">
                    <i x-show="loading" class="ph ph-spinner animate-spin"></i>
                    <span x-show="!loading">Update</span>
                    <span x-show="loading">Updating...</span>
                </button>
                <button @click="openStatusModal = false" :disabled="loading" type="button" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm cursor-pointer transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Modal -->
<div x-show="openConfirmModal" 
     style="display: none;"
     class="fixed inset-0 z-[100] overflow-y-auto" 
     aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div x-show="openConfirmModal" x-transition.opacity class="fixed inset-0 z-0 bg-gray-900/50 backdrop-blur-sm cursor-pointer" aria-hidden="true" @click="if (!loading) openConfirmModal = false"></div>
        <div x-show="openConfirmModal" x-transition.scale class="relative z-10 bg-white rounded-2xl text-left overflow-hidden shadow-xl sm:max-w-md w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-amber-50 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="ph ph-warning text-amber-600 text-xl"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Confirm Status Update</h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                Are you sure you want to update the order status to <strong class="text-gray-900" x-text="getStatusLabel(nextStatus)"></strong>? This will send a notification to the customer.
                            </p>
                        </div>

                        <!-- Add to Steadfast Option in Confirm Modal (only from processing to shipped) -->
                        <template x-if="currentStatus === 'processing' && nextStatus === 'shipped' && !isSteadfastBooked">
                            <div class="mt-4 p-3.5 rounded-xl border bg-brand-50/60 border-brand-200 text-left">
                                <label class="flex items-start gap-3 cursor-pointer select-none">
                                    <input type="checkbox" x-model="sendToSteadfast" class="mt-0.5 w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-gray-300 cursor-pointer">
                                    <div class="flex-1">
                                        <span class="text-sm font-bold text-gray-900 flex items-center gap-1.5">
                                            <i class="ph-bold ph-truck text-brand-600"></i> Add to Steadfast Courier
                                        </span>
                                        <p class="text-xs text-gray-600 mt-1 leading-normal">
                                            Auto-creates parcel in Steadfast with COD 
                                            <strong class="text-brand-700">৳{{ number_format($order->payment_status === 'paid' ? 0 : $order->total, 0) }}</strong>.
                                        </p>
                                    </div>
                                </label>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button @click="updateStatus(nextStatus)" type="button" class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-transparent shadow-sm px-4 py-2 bg-brand-600 text-base font-medium text-white hover:bg-brand-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer transition-colors" :disabled="loading">
                    <i x-show="loading" class="ph ph-spinner animate-spin"></i>
                    <span x-show="!loading">Confirm</span>
                    <span x-show="loading">Updating...</span>
                </button>
                <button @click="openConfirmModal = false" :disabled="loading" type="button" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm cursor-pointer transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div x-show="toast.show" x-transition style="display: none;"
     class="fixed bottom-6 right-6 z-[110] flex items-center gap-3 bg-gray-900 text-white px-5 py-3.5 rounded-xl shadow-xl">
    <i class="ph-fill text-xl" :class="toast.type === 'error' ? 'ph-warning-circle text-red-400' : 'ph-check-circle text-green-400'"></i>
    <p class="text-sm font-medium" x-text="toast.message"></p>
</div>

</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('orderActions', (orderId, currentStatus, isBooked, cid, trackingCode, customerPhone) => ({
            orderId: orderId,
            currentStatus: currentStatus,
            selectedStatus: currentStatus,
            isSteadfastBooked: Boolean(isBooked),
            steadfastCid: cid || '',
            steadfastTracking: trackingCode || '',
            customerPhone: customerPhone || '',
            sendToSteadfast: false,
            steadfastLoading: false,
            liveStatus: null,
            checkingLiveStatus: false,
            fraudCheckData: null,
            checkingFraud: false,
            openStatusModal: false,
            openConfirmModal: false,
            loading: false,
            nextStatus: null,
            steps: ['pending', 'confirmed', 'processing', 'shipped', 'delivered'],
            toast: { show: false, message: '', type: 'success' },
            toastTimer: null,
            copied: false,
            copiedTimer: null,

            init() {
                this.calculateNextStatus();
                if (this.currentStatus === 'processing' && !this.isSteadfastBooked) {
                    this.sendToSteadfast = true;
                }
            },

            get isTerminalBad() {
                return ['cancelled', 'returned'].includes(this.currentStatus);
            },

            isDone(step) {
                const current = this.steps.indexOf(this.currentStatus);
                return current !== -1 && this.steps.indexOf(step) < current;
            },

            badgeClass(status) {
                return {
                    'bg-amber-50 text-amber-600': status === 'pending',
                    'bg-blue-50 text-blue-600': status === 'confirmed',
                    'bg-indigo-50 text-indigo-600': status === 'processing',
                    'bg-purple-50 text-purple-600': status === 'shipped',
                    'bg-emerald-50 text-emerald-600': status === 'delivered',
                    'bg-red-50 text-red-600': status === 'cancelled' || status === 'returned',
                };
            },

            calculateNextStatus() {
                const flow = {
                    'pending': 'confirmed',
                    'confirmed': 'processing',
                    'processing': 'shipped',
                    'shipped': 'delivered'
                };
                this.nextStatus = flow[this.currentStatus] || null;
                if (this.currentStatus === 'processing' && this.nextStatus === 'shipped' && !this.isSteadfastBooked) {
                    this.sendToSteadfast = true;
                } else {
                    this.sendToSteadfast = false;
                }
            },

            getStatusLabel(status) {
                if (!status) return '';
                return status.charAt(0).toUpperCase() + status.slice(1);
            },

            showToast(message, type = 'success') {
                clearTimeout(this.toastTimer);
                this.toast = { show: true, message, type };
                this.toastTimer = setTimeout(() => this.toast.show = false, 3500);
            },

            async copyOrderId() {
                await this.copyText(this.orderId, 'Order ID copied');
            },

            async copyText(text, successMsg = 'Copied to clipboard') {
                if (!text) return;
                try {
                    if (navigator.clipboard && window.isSecureContext) {
                        await navigator.clipboard.writeText(text);
                    } else {
                        const input = document.createElement('textarea');
                        input.value = text;
                        input.style.position = 'fixed';
                        input.style.opacity = '0';
                        document.body.appendChild(input);
                        input.select();
                        const ok = document.execCommand('copy');
                        document.body.removeChild(input);
                        if (!ok) throw new Error('copy failed');
                    }
                    this.copied = true;
                    this.showToast(successMsg);
                    clearTimeout(this.copiedTimer);
                    this.copiedTimer = setTimeout(() => this.copied = false, 2000);
                } catch (e) {
                    this.showToast('Could not copy text', 'error');
                }
            },

            async updateStatus(newStatus) {
                if (!newStatus || this.loading) return;

                if (newStatus === this.currentStatus && !(this.currentStatus === 'processing' && newStatus === 'shipped' && this.sendToSteadfast && !this.isSteadfastBooked)) {
                    this.openStatusModal = false;
                    this.openConfirmModal = false;
                    return;
                }

                this.loading = true;

                try {
                    const shouldSendToSteadfast = (this.currentStatus === 'processing' && newStatus === 'shipped' && this.sendToSteadfast && !this.isSteadfastBooked);
                    const response = await fetch(`/admin/dashboard/api/orders/${this.orderId}/status`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ 
                            status: newStatus,
                            send_to_steadfast: shouldSendToSteadfast
                        })
                    });

                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        throw new Error(data.message || 'Failed to update status');
                    }

                    this.currentStatus = data.status || newStatus;
                    this.selectedStatus = this.currentStatus;
                    this.calculateNextStatus();

                    if (data.is_added_into_steadfast) {
                        this.isSteadfastBooked = true;
                        this.steadfastCid = data.steadfast_consignment_id || this.steadfastCid;
                        this.steadfastTracking = data.steadfast_tracking_code || this.steadfastTracking;
                        this.sendToSteadfast = false;
                    }

                    this.openStatusModal = false;
                    this.openConfirmModal = false;
                    this.showToast(data.message || ('Order status updated to ' + this.getStatusLabel(this.currentStatus)));
                } catch (error) {
                    this.showToast(error.message, 'error');
                } finally {
                    this.loading = false;
                }
            },

            async bookSteadfastDirect() {
                if (this.steadfastLoading || this.isSteadfastBooked) return;

                this.steadfastLoading = true;
                try {
                    const response = await fetch(`/admin/dashboard/api/orders/${this.orderId}/send-to-steadfast`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    });

                    const data = await response.json().catch(() => ({}));

                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Failed to book in Steadfast');
                    }

                    this.isSteadfastBooked = true;
                    this.steadfastCid = data.consignment_id || data.consignment?.consignment_id || '';
                    this.steadfastTracking = data.tracking_code || data.consignment?.tracking_code || '';
                    if (data.status) {
                        this.currentStatus = data.status;
                        this.selectedStatus = data.status;
                        this.calculateNextStatus();
                    }
                    this.showToast(data.message || 'Successfully booked into Steadfast!');
                } catch (error) {
                    this.showToast(error.message, 'error');
                } finally {
                    this.steadfastLoading = false;
                }
            },

            async checkSteadfastLiveStatus() {
                if (this.checkingLiveStatus) return;
                this.checkingLiveStatus = true;
                try {
                    const response = await fetch(`/admin/dashboard/api/orders/${this.orderId}/steadfast-status`, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Could not fetch status from Steadfast');
                    }
                    const statusVal = data.data?.delivery_status || data.data?.status || 'Unknown';
                    this.liveStatus = statusVal;
                    this.showToast('Steadfast Status: ' + statusVal);
                } catch (error) {
                    this.showToast(error.message, 'error');
                } finally {
                    this.checkingLiveStatus = false;
                }
            },

            async checkFraudScore() {
                if (!this.customerPhone || this.checkingFraud) return;
                this.checkingFraud = true;
                try {
                    const response = await fetch(`/admin/dashboard/api/orders/steadfast-fraud-check?phone=${encodeURIComponent(this.customerPhone)}`, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Failed to check Steadfast record');
                    }
                    this.fraudCheckData = data.data || null;
                    this.showToast('Customer record retrieved from Steadfast');
                } catch (error) {
                    this.showToast(error.message, 'error');
                } finally {
                    this.checkingFraud = false;
                }
            }
        }));
    });
</script>
@endsection
