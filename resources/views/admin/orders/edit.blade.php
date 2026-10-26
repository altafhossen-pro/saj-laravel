@extends('layouts.admin')

@section('header_title', 'Edit Order')

@section('content')
@php
    $shipping = is_array($order->shipping_address) ? $order->shipping_address : [];

    $itemsPayload = $order->items->map(function ($item) {
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

        return [
            'id' => $item->id,
            'name' => $item->name,
            'variant' => $info['label'] ?? null,
            'image' => $imageUrl,
            'quantity' => (int) $item->quantity,
            'price' => (float) $item->price,
        ];
    })->values();

    $config = [
        'orderId' => $order->order_id,
        'items' => $itemsPayload,
        'form' => [
            'status' => $order->status,
            'payment_status' => $order->payment_status ?? 'pending',
            'payment_method' => $order->payment_method,
            'order_source' => $order->order_source ?? 'website',
            'shipping' => [
                'name' => $shipping['name'] ?? '',
                'phone' => $shipping['phone'] ?? '',
                'address' => $shipping['address'] ?? '',
                'city' => $shipping['city'] ?? '',
                'zone' => $shipping['zone'] ?? '',
            ],
            'discount' => (float) $order->discount,
            'coupon_discount' => (float) $order->coupon_discount,
            'shipping_cost' => (float) $order->shipping_cost,
            'order_notes' => $order->order_notes,
            'admin_notes' => $order->admin_notes,
            'is_gift' => (bool) $order->is_gift,
            'gift_message' => $order->gift_message,
            'steadfast_consignment_id' => $order->steadfast_consignment_id,
            'steadfast_tracking_code' => $order->steadfast_tracking_code,
        ],
    ];

    $inputClass = 'block w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 disabled:opacity-60';
    $labelClass = 'block text-xs font-medium text-gray-500 mb-1';
@endphp

<form x-data="orderEdit(@js($config))" @submit.prevent="save()" novalidate>
    <!-- Header -->
    <div class="mb-8 flex flex-wrap justify-between items-end gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit order {{ $order->order_id }}</h1>
            <p class="text-sm text-gray-500 mt-1 flex flex-wrap items-center gap-4">
                <a href="{{ route('admin.orders.show', $order->order_id) }}" class="text-brand-600 hover:text-brand-800 font-medium flex items-center gap-1">
                    <i class="ph ph-arrow-left"></i> Back to order
                </a>
                <span>Placed on {{ $order->created_at->format('F d, Y \a\t h:i A') }}</span>
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.orders.show', $order->order_id) }}" class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 cursor-pointer transition-colors" :class="saving ? 'pointer-events-none opacity-60' : ''">Cancel</a>
            <button type="submit" :disabled="saving" class="px-4 py-2 bg-brand-600 border border-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700 cursor-pointer transition-colors flex items-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">
                <i x-show="saving" style="display: none;" class="ph ph-spinner animate-spin"></i>
                <span x-text="saving ? 'Saving...' : 'Save changes'"></span>
            </button>
        </div>
    </div>

    <div x-show="Object.keys(errors).length" style="display: none;" class="mb-6 flex items-start gap-3 bg-red-50 border border-red-100 text-red-700 rounded-xl px-4 py-3">
        <i class="ph-fill ph-warning-circle text-xl mt-0.5"></i>
        <div>
            <p class="font-bold text-sm">Fix these fields before saving</p>
            <ul class="mt-1 text-sm list-disc list-inside">
                <template x-for="(messages, field) in errors" :key="field">
                    <li x-text="messages[0]"></li>
                </template>
            </ul>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left column -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Items -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <h2 class="text-lg font-bold text-gray-900">Order items (<span x-text="items.length"></span>)</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-gray-50 text-gray-500 font-medium">
                            <tr>
                                <th class="px-6 py-4">Product</th>
                                <th class="px-4 py-4 w-28">Qty</th>
                                <th class="px-4 py-4 w-36">Unit price (৳)</th>
                                <th class="px-4 py-4 text-right">Total</th>
                                <th class="px-4 py-4 w-12"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="(item, index) in items" :key="item.id">
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-12 h-12 rounded-lg bg-gray-100 flex-shrink-0 overflow-hidden flex items-center justify-center text-gray-400">
                                                <img x-show="item.image" :src="item.image" :alt="item.name" class="w-full h-full object-cover">
                                                <i x-show="!item.image" class="ph ph-image text-xl"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-medium text-gray-900 truncate max-w-[220px]" x-text="item.name"></p>
                                                <span x-show="item.variant" class="inline-block mt-0.5 bg-gray-100 px-1.5 py-0.5 rounded text-xs text-gray-500" x-text="item.variant"></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <input type="text" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')" x-model.number="item.quantity" :disabled="saving" class="{{ $inputClass }}" :aria-label="'Quantity for ' + item.name">
                                    </td>
                                    <td class="px-4 py-4">
                                        <input type="text" inputmode="decimal" oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1')" x-model.number="item.price" :disabled="saving" class="{{ $inputClass }}" :aria-label="'Unit price for ' + item.name">
                                    </td>
                                    <td class="px-4 py-4 text-right font-medium text-gray-900" x-text="'৳' + money(lineTotal(item))"></td>
                                    <td class="px-4 py-4 text-right">
                                        <button type="button" @click="removeItem(index)" :disabled="saving || items.length === 1"
                                            :title="items.length === 1 ? 'An order needs at least one item' : 'Remove item'"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-transparent disabled:hover:text-gray-400">
                                            <i class="ph ph-trash text-lg"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <div x-show="removed.length" style="display: none;" class="px-6 py-3 bg-amber-50 border-t border-amber-100 text-sm text-amber-800 flex items-center justify-between gap-3">
                    <span><span x-text="removed.length"></span> item(s) will be removed when you save.</span>
                    <button type="button" @click="restoreItems()" :disabled="saving" class="font-medium text-amber-900 hover:underline cursor-pointer">Undo</button>
                </div>
            </div>

            <!-- Notes -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
                <h2 class="text-lg font-bold text-gray-900">Notes</h2>
                <div>
                    <label class="{{ $labelClass }}" for="order_notes">Customer note</label>
                    <textarea id="order_notes" rows="3" x-model="form.order_notes" :disabled="saving" class="{{ $inputClass }}" placeholder="Note left by the customer at checkout"></textarea>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="admin_notes">Admin note</label>
                    <textarea id="admin_notes" rows="3" x-model="form.admin_notes" :disabled="saving" class="{{ $inputClass }}" placeholder="Only visible to admins"></textarea>
                </div>
            </div>

            <!-- Gift & courier -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
                <h2 class="text-lg font-bold text-gray-900">Gift &amp; courier tracking</h2>
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer w-fit">
                    <input type="checkbox" x-model="form.is_gift" :disabled="saving" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500 cursor-pointer">
                    This order is a gift
                </label>
                <div x-show="form.is_gift" style="display: none;">
                    <label class="{{ $labelClass }}" for="gift_message">Gift message</label>
                    <textarea id="gift_message" rows="2" x-model="form.gift_message" :disabled="saving" class="{{ $inputClass }}"></textarea>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="{{ $labelClass }}" for="steadfast_consignment_id">Steadfast consignment ID</label>
                        <input id="steadfast_consignment_id" type="text" x-model="form.steadfast_consignment_id" :disabled="saving" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}" for="steadfast_tracking_code">Steadfast tracking code</label>
                        <input id="steadfast_tracking_code" type="text" x-model="form.steadfast_tracking_code" :disabled="saving" class="{{ $inputClass }}">
                    </div>
                </div>
            </div>
        </div>

        <!-- Right column -->
        <div class="space-y-6">

            <!-- Status & payment -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
                <h2 class="text-lg font-bold text-gray-900">Status &amp; payment</h2>
                <div>
                    <label class="{{ $labelClass }}" for="status">Order status</label>
                    <select id="status" x-model="form.status" :disabled="saving" class="{{ $inputClass }} cursor-pointer">
                        @foreach(['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'returned'] as $s)
                            <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="payment_status">Payment status</label>
                    <select id="payment_status" x-model="form.payment_status" :disabled="saving" class="{{ $inputClass }} cursor-pointer">
                        @foreach(['pending', 'paid', 'failed', 'refunded'] as $s)
                            <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="payment_method">Payment method</label>
                    <input id="payment_method" type="text" x-model="form.payment_method" :disabled="saving" class="{{ $inputClass }}" placeholder="e.g. Cash on Delivery">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="order_source">Order source</label>
                    <select id="order_source" x-model="form.order_source" :disabled="saving" class="{{ $inputClass }} cursor-pointer">
                        @foreach(['website', 'facebook', 'whatsapp', 'phone', 'email', 'walk-in', 'instagram', 'manual', 'other'] as $s)
                            <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Shipping -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
                <h2 class="text-lg font-bold text-gray-900">Shipping details</h2>
                <div>
                    <label class="{{ $labelClass }}" for="ship_name">Receiver name</label>
                    <input id="ship_name" type="text" x-model="form.shipping.name" :disabled="saving" class="{{ $inputClass }}" :class="errors['shipping.name'] ? 'border-red-400' : ''">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="ship_phone">Phone</label>
                    <input id="ship_phone" type="text" x-model="form.shipping.phone" :disabled="saving" class="{{ $inputClass }}" :class="errors['shipping.phone'] ? 'border-red-400' : ''">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="ship_address">Address</label>
                    <textarea id="ship_address" rows="3" x-model="form.shipping.address" :disabled="saving" class="{{ $inputClass }}" :class="errors['shipping.address'] ? 'border-red-400' : ''"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="{{ $labelClass }}" for="ship_city">City</label>
                        <input id="ship_city" type="text" x-model="form.shipping.city" :disabled="saving" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}" for="ship_zone">Zone</label>
                        <input id="ship_zone" type="text" x-model="form.shipping.zone" :disabled="saving" class="{{ $inputClass }}">
                    </div>
                </div>
            </div>

            <!-- Pricing -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
                <h2 class="text-lg font-bold text-gray-900">Pricing</h2>
                <div>
                    <label class="{{ $labelClass }}" for="shipping_cost">Shipping cost (৳)</label>
                    <input id="shipping_cost" type="text" inputmode="decimal" oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1')" x-model.number="form.shipping_cost" :disabled="saving" class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="discount">Discount (৳)</label>
                    <input id="discount" type="text" inputmode="decimal" oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1')" x-model.number="form.discount" :disabled="saving" class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}" for="coupon_discount">Coupon discount (৳)@if($order->coupon) <span class="text-gray-400">· {{ $order->coupon }}</span>@endif</label>
                    <input id="coupon_discount" type="text" inputmode="decimal" oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1')" x-model.number="form.coupon_discount" :disabled="saving" class="{{ $inputClass }}">
                </div>

                <div class="pt-4 border-t border-gray-100 space-y-2 text-sm">
                    <div class="flex justify-between text-gray-600"><span>Subtotal</span><span x-text="'৳' + money(subtotal)"></span></div>
                    <div class="flex justify-between text-gray-600"><span>Shipping</span><span x-text="'+ ৳' + money(form.shipping_cost)"></span></div>
                    <div class="flex justify-between text-green-600" x-show="num(form.discount) > 0"><span>Discount</span><span x-text="'- ৳' + money(form.discount)"></span></div>
                    <div class="flex justify-between text-green-600" x-show="num(form.coupon_discount) > 0"><span>Coupon</span><span x-text="'- ৳' + money(form.coupon_discount)"></span></div>
                    <div class="flex justify-between items-center pt-2 border-t border-gray-100">
                        <span class="font-bold text-gray-900">Total</span>
                        <span class="font-bold text-brand-600 text-lg" x-text="'৳' + money(total)"></span>
                    </div>
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
</form>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('orderEdit', (config) => ({
            orderId: config.orderId,
            items: config.items,
            originalItems: JSON.parse(JSON.stringify(config.items)),
            form: config.form,
            saving: false,
            errors: {},
            toast: { show: false, message: '', type: 'success' },
            toastTimer: null,

            get removed() {
                const keep = this.items.map(i => i.id);
                return this.originalItems.filter(i => !keep.includes(i.id));
            },

            get subtotal() {
                return this.items.reduce((sum, item) => sum + this.lineTotal(item), 0);
            },

            get total() {
                return Math.max(0, this.subtotal + this.num(this.form.shipping_cost) - this.num(this.form.discount) - this.num(this.form.coupon_discount));
            },

            num(value) {
                const n = parseFloat(value);
                return isNaN(n) ? 0 : n;
            },

            lineTotal(item) {
                return this.num(item.price) * (parseInt(item.quantity) || 0);
            },

            money(value) {
                return this.num(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },

            removeItem(index) {
                if (this.items.length === 1) return;
                this.items.splice(index, 1);
            },

            restoreItems() {
                // Put removed items back in their original order, keeping edits made to the rest
                const current = Object.fromEntries(this.items.map(i => [i.id, i]));
                this.items = this.originalItems.map(o => current[o.id] || JSON.parse(JSON.stringify(o)));
            },

            showToast(message, type = 'success') {
                clearTimeout(this.toastTimer);
                this.toast = { show: true, message, type };
                this.toastTimer = setTimeout(() => this.toast.show = false, 3000);
            },

            async save() {
                if (this.saving) return;
                this.saving = true;
                this.errors = {};

                const payload = {
                    ...this.form,
                    is_gift: !!this.form.is_gift,
                    discount: this.num(this.form.discount),
                    coupon_discount: this.num(this.form.coupon_discount),
                    shipping_cost: this.num(this.form.shipping_cost),
                    items: this.items.map(i => ({
                        id: i.id,
                        quantity: parseInt(i.quantity) || 0,
                        price: this.num(i.price),
                    })),
                };

                try {
                    const response = await fetch(`/admin/dashboard/api/orders/${this.orderId}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });

                    const data = await response.json().catch(() => ({}));

                    if (response.status === 422 && data.errors) {
                        this.errors = data.errors;
                        throw new Error('Some fields need your attention.');
                    }
                    if (!response.ok) {
                        throw new Error(data.message || 'Failed to save the order.');
                    }

                    this.showToast('Order updated');
                    setTimeout(() => { window.location.href = data.redirect; }, 600);
                } catch (error) {
                    this.showToast(error.message, 'error');
                    this.saving = false;
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    document.querySelector('main')?.scrollTo({ top: 0, behavior: 'smooth' });
                }
            }
        }));
    });
</script>
@endsection
