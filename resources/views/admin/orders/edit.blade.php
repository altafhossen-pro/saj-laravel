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
        $sku = $item->variant_sku ?: ($item->product->sku ?? null);

        return [
            'id' => $item->id,
            'product_id' => $item->product_id,
            'variant_sku' => $item->variant_sku,
            'sku' => $sku,
            'name' => $item->name,
            'variant' => $info['label'] ?? null,
            'image' => $imageUrl,
            'quantity' => (int) $item->quantity,
            'price' => (float) $item->price,
        ];
    })->values();

    $rawAdminNotes = $order->admin_notes;
    $parsedAdminNotes = [];
    if (!empty($rawAdminNotes)) {
        $decodedNotes = json_decode($rawAdminNotes, true);
        if (is_array($decodedNotes)) {
            foreach ($decodedNotes as $n) {
                if (is_array($n)) {
                    $ts = $n['timestamp'] ?? null;
                    $dObj = null;
                    if ($ts) {
                        try {
                            $dObj = \Carbon\Carbon::parse($ts)->setTimezone('Asia/Dhaka');
                        } catch (\Throwable $e) {}
                    }
                    $n['formatted_date'] = $n['formatted_date'] ?? ($dObj ? $dObj->format('d M Y') : 'Recent');
                    $n['formatted_time'] = $n['formatted_time'] ?? ($dObj ? $dObj->format('h:i a') : '');
                    $n['timezone'] = $n['timezone'] ?? 'BDT';
                    $parsedAdminNotes[] = $n;
                }
            }
        } else {
            $parsedAdminNotes = [[
                'id' => uniqid('note_'),
                'note' => (string) $rawAdminNotes,
                'admin_name' => 'Admin',
                'formatted_date' => $order->updated_at ? $order->updated_at->setTimezone('Asia/Dhaka')->format('d M Y') : now()->setTimezone('Asia/Dhaka')->format('d M Y'),
                'formatted_time' => $order->updated_at ? $order->updated_at->setTimezone('Asia/Dhaka')->format('h:i a') : now()->setTimezone('Asia/Dhaka')->format('h:i a'),
                'timezone' => 'BDT'
            ]];
        }
    }

    $config = [
        'orderId' => $order->order_id,
        'items' => $itemsPayload,
        'adminNotes' => $parsedAdminNotes,
        'adminUserName' => auth()->user() ? auth()->user()->name : 'Admin',
        'adminUserId' => auth()->id(),
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
            'is_gift' => (bool) $order->is_gift,
            'gift_message' => $order->gift_message,
            'steadfast_consignment_id' => $order->steadfast_consignment_id,
            'steadfast_tracking_code' => $order->steadfast_tracking_code,
            'steadfast_delivery_charge' => (float) ($order->steadfast_delivery_charge ?: 0),
            'steadfast_collected_amount' => (float) ($order->steadfast_collected_amount ?: 0),
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
                    <div class="flex items-center gap-2">
                        <h2 class="text-lg font-bold text-gray-900">Order items (<span x-text="items.length"></span>)</h2>
                    </div>
                </div>

                <!-- Product Search & Add Bar -->
                <div class="p-4 sm:p-5 border-b border-gray-100 bg-gray-50/50 relative">
                    <div class="relative">
                        <i class="ph ph-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-base"></i>
                        <input type="text" 
                               x-ref="searchInput" 
                               x-model="searchQuery" 
                               @input.debounce.300ms="searchProducts()"
                               @focus="if(searchQuery.length >= 2 && suggestions.length > 0) showSuggestions = true"
                               @keydown.enter.prevent="handleSearchEnter()"
                               placeholder="Scan barcode or search products by name / SKU to add..."
                               class="w-full pl-10 pr-10 py-2.5 bg-white border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500 transition-colors text-sm shadow-2xs">
                        <i x-show="isSearching" class="ph ph-spinner animate-spin absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-base"></i>
                        <button type="button" 
                                x-show="searchQuery && !isSearching" 
                                @click="searchQuery = ''; suggestions = []; showSuggestions = false;" 
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <i class="ph ph-x"></i>
                        </button>
                    </div>

                    <!-- Suggestions Dropdown -->
                    <div x-show="suggestions.length > 0 && showSuggestions" 
                         @click.away="showSuggestions = false"
                         x-transition
                         class="absolute z-50 left-4 right-4 mt-2 bg-white rounded-xl shadow-xl border border-gray-200 max-h-96 overflow-y-auto">
                        <template x-for="product in suggestions" :key="product.id">
                            <div class="p-3 border-b border-gray-100 last:border-0 hover:bg-gray-50 transition-colors">
                                <div class="flex items-center gap-3 cursor-pointer"
                                     @click="if(product.product_type === 'variable' && product.variants && product.variants.length > 0) { expandedProduct = expandedProduct === product.id ? null : product.id } else { addItem(product) }">
                                    <div class="w-12 h-12 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0 flex items-center justify-center relative border border-gray-200">
                                        <img x-show="product.featured_image" :src="getImageUrl(product.featured_image)" class="w-full h-full object-cover">
                                        <i x-show="!product.featured_image" class="ph ph-image text-gray-400 text-xl"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <h4 class="font-medium text-gray-900 truncate text-sm" x-text="product.title"></h4>
                                            <span x-show="product.product_type !== 'variable'"
                                                class="px-1.5 py-0.5 bg-gray-100 text-gray-600 rounded text-[9px] font-medium uppercase tracking-wider">Single</span>
                                            <span x-show="product.product_type === 'variable'"
                                                class="px-1.5 py-0.5 bg-brand-50 text-brand-700 rounded text-[9px] font-medium uppercase tracking-wider">Variable</span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1" x-show="product.product_type !== 'variable'">
                                            <span x-text="'SKU: ' + (product.sku || 'N/A') + ' · ৳' + (product.current_price || product.base_price)"></span>
                                            <span class="mx-1">&bull;</span>
                                            <span :class="product.stock > 0 ? 'text-green-600' : 'text-red-500 font-medium'" x-text="product.stock > 0 ? 'Stock: ' + product.stock : 'Out of Stock'"></span>
                                        </p>
                                        <p class="text-xs text-gray-500 mt-1" x-show="product.product_type === 'variable'">
                                            <span x-text="product.variants.length + ' variants'"></span>
                                            <span class="mx-1">&bull;</span>
                                            <span :class="product.stock > 0 ? 'text-green-600' : 'text-red-500 font-medium'" x-text="product.stock > 0 ? 'Total Stock: ' + product.stock : 'Out of Stock'"></span>
                                        </p>
                                    </div>
                                    <div x-show="product.product_type !== 'variable'">
                                        <button type="button" @click.stop="addItem(product)"
                                            class="px-3 py-1.5 bg-brand-50 hover:bg-brand-100 text-brand-600 rounded-lg text-xs font-semibold transition-colors cursor-pointer inline-flex items-center gap-1">
                                            <i class="ph-bold ph-plus text-xs"></i> Add
                                        </button>
                                    </div>
                                    <div x-show="product.product_type === 'variable'">
                                        <button type="button" 
                                            class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs font-semibold transition-colors cursor-pointer inline-flex items-center gap-1">
                                            <span x-text="expandedProduct === product.id ? 'Close' : 'Choose Variant'"></span>
                                            <i class="ph" :class="expandedProduct === product.id ? 'ph-caret-up' : 'ph-caret-down'"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Variable Product Variants List -->
                                <div x-show="product.product_type === 'variable' && product.variants && product.variants.length > 0 && expandedProduct === product.id"
                                     x-transition
                                     class="pl-14 pr-2 pb-2 space-y-1.5 mt-3 pt-2 border-t border-gray-100">
                                    <template x-for="variant in product.variants" :key="variant.id">
                                        <div class="flex items-center justify-between bg-white border border-gray-200 rounded-lg p-2.5 hover:border-brand-300 hover:shadow-2xs transition-all cursor-pointer group"
                                             @click.stop="addItem(product, variant)">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 bg-gray-50 rounded-md overflow-hidden flex items-center justify-center flex-shrink-0 border border-gray-200">
                                                    <img x-show="variant.images && variant.images.length > 0"
                                                         :src="getImageUrl(variant.images ? variant.images[0] : null)"
                                                         class="w-full h-full object-cover">
                                                    <i x-show="!variant.images || variant.images.length === 0"
                                                       class="ph ph-image text-gray-300 text-lg"></i>
                                                </div>
                                                <div>
                                                    <!-- Attributes Badges -->
                                                    <div class="flex flex-wrap gap-1.5 mb-1">
                                                        <template x-for="(val, key) in variant.attributes">
                                                            <div class="inline-flex items-center text-[11px] leading-none bg-gray-100 border border-gray-200 rounded-md px-1.5 py-1">
                                                                <span class="text-gray-500 mr-1" x-text="key + ':'"></span>
                                                                <span class="font-medium text-gray-800" x-text="val"></span>
                                                            </div>
                                                        </template>
                                                    </div>
                                                    <!-- SKU, Price & Stock -->
                                                    <div class="flex items-center gap-2 text-[11px]">
                                                        <span class="text-brand-600 font-bold" x-text="'৳' + (variant.current_price || product.current_price)"></span>
                                                        <span class="text-gray-300">&bull;</span>
                                                        <span class="text-gray-500 font-mono" x-text="'SKU: ' + (variant.sku || 'N/A')"></span>
                                                        <span class="text-gray-300">&bull;</span>
                                                        <span :class="variant.stock > 0 ? 'text-green-600 font-medium' : 'text-red-500 font-bold'" 
                                                              x-text="variant.stock > 0 ? 'Stock: ' + variant.stock : 'Out of Stock'"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="button" @click.stop="addItem(product, variant)"
                                                class="px-3 py-1.5 bg-brand-50 group-hover:bg-brand-600 group-hover:text-white text-brand-600 rounded-md text-xs font-semibold transition-colors flex-shrink-0 flex items-center gap-1 cursor-pointer">
                                                <i class="ph-bold ph-plus text-xs"></i> Add
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
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
                            <template x-for="(item, index) in items" :key="item.id || item.cartKey || index">
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="relative group w-12 h-12 rounded-lg bg-gray-100 flex-shrink-0 overflow-hidden flex items-center justify-center text-gray-400 border border-gray-200 cursor-pointer"
                                                 @click="if(item.image) openImagePreview(item.image, item.name)"
                                                 title="Click to view large image">
                                                <img x-show="item.image" :src="item.image" :alt="item.name" class="w-full h-full object-cover">
                                                <div x-show="item.image" class="absolute inset-0 bg-black/35 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white">
                                                    <i class="ph-bold ph-magnifying-glass-plus text-base"></i>
                                                </div>
                                                <i x-show="!item.image" class="ph ph-image text-xl"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-medium text-gray-900 truncate max-w-[220px]" x-text="item.name"></p>
                                                <div class="flex flex-wrap items-center gap-1.5 mt-0.5">
                                                    <span x-show="item.variant_sku || item.sku" 
                                                          class="inline-flex items-center gap-1 bg-gray-100 text-gray-700 px-1.5 py-0.5 rounded text-[11px] font-mono font-medium border border-gray-200">
                                                        <span class="text-gray-400">SKU:</span>
                                                        <span x-text="item.variant_sku || item.sku"></span>
                                                    </span>
                                                    <span x-show="item.variant" class="inline-block bg-brand-50 text-brand-700 border border-brand-100 px-1.5 py-0.5 rounded text-xs font-medium" x-text="item.variant"></span>
                                                    <span x-show="!item.id" class="inline-block bg-emerald-50 text-emerald-700 border border-emerald-200 px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider">New</span>
                                                </div>
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
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-gray-900">Notes</h2>
                    <span class="text-xs text-gray-400">Order communications</span>
                </div>

                <!-- Customer checkout note -->
                <div>
                    <label class="{{ $labelClass }}" for="order_notes">Customer checkout note</label>
                    <textarea id="order_notes" rows="2" x-model="form.order_notes" :disabled="saving" class="{{ $inputClass }}" placeholder="Note left by the customer at checkout"></textarea>
                </div>

                <!-- Internal Admin Notes (Timeline) -->
                <div class="pt-2 border-t border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-bold text-gray-900">Admin Notes</h3>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-gray-100 text-gray-700" x-text="adminNotes.length + ' Notes'"></span>
                            </div>
                            <p class="text-[11px] text-gray-500">Internal notes &amp; updates for this order (visible only to admins)</p>
                        </div>
                    </div>

                    <!-- Add New Note Form -->
                    <div class="mb-5 p-4 bg-gray-50/80 rounded-2xl border border-gray-200">
                        <label for="new_admin_note" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Write / Add Note:</label>
                        <textarea id="new_admin_note" 
                                  x-model="newNoteText" 
                                  rows="2" 
                                  placeholder="Type internal note here (e.g. verified phone with customer, customer requested delivery after 5pm)..."
                                  class="w-full text-xs p-3 bg-white border border-gray-300 rounded-xl focus:border-brand-500 focus:ring-1 focus:ring-brand-500/20 text-gray-900 resize-y"></textarea>
                        <div class="mt-2.5 flex items-center justify-between gap-2">
                            <span class="text-[11px] text-gray-400">Added notes are saved to this order when you click "Save changes".</span>
                            <button type="button" 
                                    @click="addAdminNote()" 
                                    :disabled="!newNoteText.trim()" 
                                    class="px-4 py-1.5 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-xl shadow-xs transition-colors inline-flex items-center gap-1.5 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed shrink-0">
                                <i class="ph-bold ph-plus"></i>
                                <span>Add Note</span>
                            </button>
                        </div>
                    </div>

                    <!-- Timeline Container -->
                    <div class="pt-2">
                        <!-- Empty State -->
                        <template x-if="adminNotes.length === 0">
                            <div class="text-center py-8 px-4 text-gray-400 text-xs bg-gray-50/50 rounded-2xl border border-dashed border-gray-200">
                                <div class="w-10 h-10 rounded-xl bg-gray-100 text-gray-400 mx-auto flex items-center justify-center text-lg mb-2">
                                    <i class="ph ph-notepad"></i>
                                </div>
                                <h4 class="text-xs font-bold text-gray-700">No admin notes added yet</h4>
                                <p class="text-[11px] text-gray-400 mt-0.5">Use the box above to add communications or internal instructions.</p>
                            </div>
                        </template>

                        <!-- Timeline List (3-Column Layout: Date/Time -> Vertical Line & Node Dot -> Note Card) -->
                        <template x-if="adminNotes.length > 0">
                            <div class="space-y-0 relative">
                                <template x-for="(item, index) in adminNotes" :key="item.id || index">
                                    <div class="flex items-start group">
                                        <!-- Left: Date & Time in BDT -->
                                        <div class="w-28 sm:w-32 text-right shrink-0 pr-4 sm:pr-5 pt-1">
                                            <div class="font-bold text-gray-900 text-xs sm:text-sm tracking-tight" x-text="item.formatted_date || 'Recent'"></div>
                                            <div class="text-[11px] sm:text-xs font-semibold text-brand-600 flex items-center justify-end gap-1 mt-0.5">
                                                <i class="ph ph-clock text-xs"></i>
                                                <span x-text="item.formatted_time || ''"></span>
                                            </div>
                                            <div class="text-[10px] font-bold text-gray-400 tracking-wider uppercase mt-0.5" x-text="item.timezone || 'BDT'"></div>
                                        </div>

                                        <!-- Center: Line & Node Dot -->
                                        <div class="relative flex flex-col items-center self-stretch shrink-0">
                                            <!-- Dot -->
                                            <div class="relative z-10 w-3.5 h-3.5 rounded-full bg-brand-600 ring-4 ring-brand-100 shrink-0 my-1 transition-transform group-hover:scale-110"></div>
                                            <!-- Vertical Connecting Line -->
                                            <div class="w-0.5 bg-brand-200 grow -mt-1" x-show="index < adminNotes.length - 1"></div>
                                        </div>

                                        <!-- Right: Note Description Card -->
                                        <div class="flex-1 pl-4 sm:pl-5 pb-5">
                                            <div class="bg-slate-50/80 group-hover:bg-white border border-slate-200/80 group-hover:border-slate-300 rounded-2xl p-4 shadow-xs transition-all duration-200">
                                                <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-200/60">
                                                    <div class="flex items-center gap-1.5 text-xs font-bold text-gray-800">
                                                        <div class="w-5 h-5 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center text-[10px]">
                                                            <i class="ph-bold ph-user"></i>
                                                        </div>
                                                        <span x-text="item.admin_name || 'System/Admin'"></span>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-[10px] uppercase font-semibold tracking-wider px-1.5 py-0.5 rounded bg-gray-100 text-gray-600">Admin Note</span>
                                                        <button type="button" 
                                                                @click="removeAdminNote(index)" 
                                                                class="text-gray-400 hover:text-red-600 hover:bg-red-50 p-1 rounded-md transition-colors cursor-pointer" 
                                                                title="Delete note">
                                                            <i class="ph ph-trash text-sm"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <p class="text-xs sm:text-sm text-gray-800 whitespace-pre-wrap leading-relaxed font-normal" x-text="item.note"></p>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
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
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                    <div>
                        <label class="{{ $labelClass }}" for="steadfast_delivery_charge">Steadfast Courier Delivery Fee (৳)</label>
                        <input id="steadfast_delivery_charge" type="text" inputmode="decimal" oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1')" x-model.number="form.steadfast_delivery_charge" :disabled="saving" class="{{ $inputClass }}" placeholder="e.g. 150">
                        <p class="mt-1 text-[11px] text-gray-400">Actual courier delivery charge deducted by Steadfast (updates our financial settlement).</p>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}" for="steadfast_collected_amount">Steadfast COD Amount (৳)</label>
                        <input id="steadfast_collected_amount" type="text" inputmode="decimal" oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1')" x-model.number="form.steadfast_collected_amount" :disabled="saving" class="{{ $inputClass }}" placeholder="Defaults to Order Total">
                        <p class="mt-1 text-[11px] text-gray-400">Cash collection amount booked with courier.</p>
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

    <!-- Large Image Preview Lightbox Modal -->
    <div x-show="previewImageUrl" 
         style="display: none;"
         class="fixed inset-0 z-[150] overflow-y-auto"
         aria-labelledby="edit-image-preview-title" role="dialog" aria-modal="true"
         @keydown.escape.window="closeImagePreview()">
        <div class="flex items-center justify-center min-h-screen p-4 sm:p-6 text-center">
            <!-- Backdrop -->
            <div x-show="previewImageUrl" 
                 x-transition.opacity.duration.300ms 
                 class="fixed inset-0 bg-gray-950/80 backdrop-blur-sm cursor-pointer" 
                 @click="closeImagePreview()"></div>

            <!-- Modal Dialog -->
            <div x-show="previewImageUrl" 
                 x-transition.scale.duration.300ms 
                 class="relative z-10 bg-white rounded-2xl overflow-hidden shadow-2xl max-w-3xl w-full p-4 sm:p-6 text-left border border-gray-100">
                <div class="flex items-center justify-between pb-3 mb-3 border-b border-gray-100">
                    <div class="min-w-0 pr-4">
                        <h3 class="text-base font-bold text-gray-900 truncate" id="edit-image-preview-title" x-text="previewImageTitle || 'Product Image'"></h3>
                        <p class="text-xs text-gray-500">Full Resolution Product Preview</p>
                    </div>
                    <button type="button" 
                            @click="closeImagePreview()" 
                            class="w-9 h-9 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-600 flex items-center justify-center transition-colors cursor-pointer shrink-0">
                        <i class="ph-bold ph-x text-lg"></i>
                    </button>
                </div>
                <div class="w-full max-h-[75vh] flex items-center justify-center bg-gray-50 rounded-xl overflow-hidden p-3 border border-gray-100">
                    <img :src="previewImageUrl" :alt="previewImageTitle" class="max-h-[70vh] max-w-full object-contain rounded-lg shadow-sm">
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
            adminNotes: config.adminNotes || [],
            adminUserName: config.adminUserName || 'Admin',
            adminUserId: config.adminUserId || null,
            newNoteText: '',
            saving: false,
            errors: {},
            toast: { show: false, message: '', type: 'success' },
            toastTimer: null,
            searchQuery: '',
            suggestions: [],
            showSuggestions: false,
            isSearching: false,
            expandedProduct: null,
            previewImageUrl: null,
            previewImageTitle: '',

            get removed() {
                const keep = this.items.map(i => i.id).filter(Boolean);
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

            getImageUrl(path) {
                if (!path) return '';
                if (typeof path === 'object') {
                    path = path.url || path.path || path.image || '';
                }
                if (path.startsWith('http://') || path.startsWith('https://')) return path;
                return '/storage/' + path.replace(/^\//, '');
            },

            async searchProducts() {
                const q = (this.searchQuery || '').trim();
                if (q.length < 2) {
                    this.suggestions = [];
                    this.showSuggestions = false;
                    return;
                }

                this.isSearching = true;
                try {
                    const res = await fetch(`/admin/dashboard/products/search?q=${encodeURIComponent(q)}`);
                    const data = await res.json();

                    // Direct match logic for barcode scanning / exact SKU
                    if (q.length >= 3) {
                        const queryClean = q.toLowerCase();
                        let exactMatchVariant = null;
                        let exactMatchProduct = null;

                        for (const product of data) {
                            if (product.product_type === 'variable' && product.variants) {
                                const match = product.variants.find(v => (v.sku || '').toLowerCase().trim() === queryClean);
                                if (match) {
                                    exactMatchVariant = match;
                                    exactMatchProduct = product;
                                    break;
                                }
                            } else {
                                const pSku = (product.sku || (product.variants && product.variants[0] ? product.variants[0].sku : '') || '').toLowerCase().trim();
                                if (pSku && pSku === queryClean) {
                                    this.addItem(product);
                                    return;
                                }
                            }
                        }

                        if (exactMatchVariant) {
                            this.addItem(exactMatchProduct, exactMatchVariant);
                            return;
                        }
                    }

                    this.suggestions = data;
                    this.showSuggestions = true;
                } catch (err) {
                    console.error(err);
                } finally {
                    this.isSearching = false;
                }
            },

            handleSearchEnter() {
                const q = (this.searchQuery || '').trim().toLowerCase();
                if (!q) return;

                if (this.suggestions.length > 0) {
                    for (const product of this.suggestions) {
                        if (product.product_type === 'variable' && product.variants) {
                            const match = product.variants.find(v => (v.sku || '').toLowerCase().trim() === q);
                            if (match) {
                                this.addItem(product, match);
                                return;
                            }
                        } else {
                            const pSku = (product.sku || '').toLowerCase().trim();
                            if (pSku === q || (product.title || '').toLowerCase().trim() === q) {
                                this.addItem(product);
                                return;
                            }
                        }
                    }
                    if (this.suggestions.length === 1 && this.suggestions[0].product_type !== 'variable') {
                        this.addItem(this.suggestions[0]);
                        return;
                    }
                }
            },

            addItem(product, variant = null) {
                const targetSku = (variant ? variant.sku : (product.sku || (product.variants && product.variants[0] ? product.variants[0].sku : ''))) || '';

                // Check if item already exists in this.items
                const existingIndex = this.items.findIndex(i => {
                    if (variant && variant.sku && i.variant_sku) {
                        return i.variant_sku.toLowerCase().trim() === variant.sku.toLowerCase().trim();
                    }
                    if (variant && variant.id && i.variant_id) {
                        return i.variant_id == variant.id;
                    }
                    if (!variant && product.id && i.product_id) {
                        return i.product_id == product.id && (!i.variant_sku || i.variant_sku === (product.sku || ''));
                    }
                    if (targetSku && (i.variant_sku || i.sku)) {
                        return (i.variant_sku || i.sku).toLowerCase().trim() === targetSku.toLowerCase().trim();
                    }
                    return false;
                });

                if (existingIndex > -1) {
                    this.items[existingIndex].quantity = (parseInt(this.items[existingIndex].quantity) || 0) + 1;
                    this.showToast(`Increased quantity for ${this.items[existingIndex].name} to ${this.items[existingIndex].quantity}`);
                    this.searchQuery = '';
                    this.suggestions = [];
                    this.showSuggestions = false;
                    this.expandedProduct = null;
                    return;
                }

                let variantLabel = null;
                let variantImage = null;
                if (variant) {
                    if (variant.attributes) {
                        variantLabel = Object.values(variant.attributes).join(', ');
                    }
                    if (variant.images && variant.images.length > 0) {
                        const first = variant.images[0];
                        const imgPath = typeof first === 'object' ? (first.url || first.path || first.image) : first;
                        variantImage = this.getImageUrl(imgPath);
                    }
                }
                const finalImage = variantImage || (product.featured_image ? this.getImageUrl(product.featured_image) : null);
                const rawPrice = variant ? (variant.current_price || product.current_price || product.base_price) : (product.current_price || product.base_price);

                const newItem = {
                    id: null,
                    cartKey: 'item_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5),
                    product_id: product.id,
                    variant_id: variant ? variant.id : null,
                    variant_sku: variant ? variant.sku : null,
                    sku: variant ? variant.sku : (product.sku || null),
                    name: product.title,
                    variant: variantLabel,
                    image: finalImage,
                    image_path: variantImage ? null : (product.featured_image || null),
                    quantity: 1,
                    price: this.num(rawPrice),
                };

                this.items.unshift(newItem);
                this.showToast(`Added ${product.title} to order`);
                this.searchQuery = '';
                this.suggestions = [];
                this.showSuggestions = false;
                this.expandedProduct = null;
            },

            removeItem(index) {
                if (this.items.length === 1) return;
                this.items.splice(index, 1);
            },

            restoreItems() {
                // Put removed items back in their original order, keeping edits made to the rest
                const current = Object.fromEntries(this.items.filter(i => i.id).map(i => [i.id, i]));
                const restored = this.originalItems.map(o => current[o.id] || JSON.parse(JSON.stringify(o)));
                const newItems = this.items.filter(i => !i.id);
                this.items = [...restored, ...newItems];
            },

            showToast(message, type = 'success') {
                clearTimeout(this.toastTimer);
                this.toast = { show: true, message, type };
                this.toastTimer = setTimeout(() => this.toast.show = false, 3000);
            },

            addAdminNote() {
                const text = (this.newNoteText || '').trim();
                if (!text) return;

                const now = new Date();
                const formattedDate = now.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                const formattedTime = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }).toLowerCase();

                this.adminNotes.unshift({
                    id: 'note_' + Math.random().toString(36).substring(2, 11),
                    note: text,
                    admin_name: this.adminUserName,
                    admin_id: this.adminUserId,
                    timestamp: now.toISOString(),
                    formatted_date: formattedDate,
                    formatted_time: formattedTime,
                    timezone: 'BDT'
                });

                this.newNoteText = '';
            },

            removeAdminNote(idx) {
                this.adminNotes.splice(idx, 1);
            },

            async save() {
                if (this.saving) return;
                this.saving = true;
                this.errors = {};

                if (this.newNoteText && this.newNoteText.trim()) {
                    this.addAdminNote();
                }

                const payload = {
                    ...this.form,
                    admin_notes: JSON.stringify(this.adminNotes),
                    is_gift: !!this.form.is_gift,
                    discount: this.num(this.form.discount),
                    coupon_discount: this.num(this.form.coupon_discount),
                    shipping_cost: this.num(this.form.shipping_cost),
                    items: this.items.map(i => ({
                        id: i.id || null,
                        product_id: i.product_id || null,
                        variant_sku: i.variant_sku || null,
                        name: i.name,
                        image: i.image_path || (i.image && !i.image.startsWith('http') ? i.image.replace(/^\/storage\//, '') : null),
                        variant_info: i.variant ? { label: i.variant } : null,
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
            },

            openImagePreview(url, title = '') {
                if (!url) return;
                this.previewImageUrl = url;
                this.previewImageTitle = title;
            },

            closeImagePreview() {
                this.previewImageUrl = null;
                this.previewImageTitle = '';
            }
        }));
    });
</script>
@endsection
