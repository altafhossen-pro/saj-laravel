@extends('layouts.app')
@section('title', 'Checkout')

@section('content')
    @push('styles')
        <style>
            @media (min-width: 992px) {
                .checkout-layout {
                    display: flex;
                    flex-direction: row;
                    gap: 2rem;
                    align-items: flex-start;
                }

                .checkout-left {
                    flex: 0 0 calc(58.333% - 1rem);
                    max-width: calc(58.333% - 1rem);
                }

                .checkout-right {
                    flex: 0 0 calc(41.667% - 1rem);
                    max-width: calc(41.667% - 1rem);
                }

                .checkout-actions-desktop {
                    display: flex !important;
                }

                .checkout-actions-mobile {
                    display: none !important;
                }
            }

            @media (max-width: 991px) {
                .checkout-layout {
                    display: flex;
                    flex-direction: column;
                    gap: 1.5rem;
                }

                .checkout-left,
                .checkout-right {
                    width: 100%;
                }

                .checkout-actions-desktop {
                    display: none !important;
                }

                .checkout-actions-mobile {
                    display: flex !important;
                }
            }
        </style>
    @endpush

    <div class="w-full  max-w-full mx-auto px-4 lg:px-8 pt-6 pb-24" x-data="checkoutPage()">
        <!-- If not logged in, show login prompt -->
        @guest
            <div
                class="mb-8 border border-gray-200 bg-white rounded-lg p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <i class="ph ph-info text-brand-600 text-xl"></i>
                    <p class="text-sm text-gray-600">Returning customer? <a href="{{ route('login') }}"
                            class="text-brand-600 font-bold hover:underline">Click here to login</a></p>
                </div>
                <p class="text-sm text-gray-500">Don't have an account? <a href="{{ route('register') }}"
                        class="text-brand-600 font-bold hover:underline border border-gray-200 px-3 py-1.5 rounded ms-2 hover:bg-gray-50 transition-colors">Register</a>
                </p>
            </div>
        @endguest

        <div class="checkout-layout">

            <!-- Left Column: Forms -->
            <div class="checkout-left space-y-5">

                <!-- 01. Customer Info -->
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                    <div class="border-b border-gray-100 px-6 py-4 bg-gray-50/50">
                        <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wide">01. Fill in the following
                            information:</h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Full Name / আপনার পুরো নামঃ
                                    <span class="text-red-500">*</span></label>
                                <input type="text" x-model="form.name" @input="syncContactInfo()" placeholder="Enter your full name here"
                                    class="w-full bg-white border border-gray-200 rounded-md px-4 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-brand-500 focus:border-brand-500 transition-colors text-gray-800 placeholder-gray-400">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Mobile Number / মোবাইল নম্বরঃ
                                    <span class="text-red-500">*</span></label>
                                <input type="tel" x-model="form.phone" @input="syncContactInfo()" placeholder="Enter your mobile number here"
                                    class="w-full bg-white border border-gray-200 rounded-md px-4 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-brand-500 focus:border-brand-500 transition-colors text-gray-800 placeholder-gray-400">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Delivery Address / ডেলিভারি
                                    এড্রেসঃ <span class="text-red-500">*</span></label>
                                <textarea x-model="form.address" rows="3" placeholder="Delivery Address"
                                    class="w-full bg-white border border-gray-200 rounded-md px-4 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-brand-500 focus:border-brand-500 transition-colors text-gray-800 placeholder-gray-400"></textarea>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Order Notes / নোট লিখুনঃ</label>
                                <textarea x-model="form.notes" rows="2" placeholder="Enter notes here (optional)"
                                    class="w-full bg-white border border-gray-200 rounded-md px-4 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-brand-500 focus:border-brand-500 transition-colors text-gray-800 placeholder-gray-400"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 02. Delivery Type -->
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                    <div class="border-b border-gray-100 px-6 py-4 bg-gray-50/50">
                        <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wide">02. Delivery Type <span
                                class="text-red-500">*</span></h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <label
                                class="relative flex items-start gap-3 p-4 border rounded-md cursor-pointer transition-colors"
                                :class="form.delivery_type === 'inside' ? 'border-brand-500 bg-brand-50/20' : 'border-gray-200 bg-white hover:border-gray-300'">
                                <input type="radio" x-model="form.delivery_type" value="inside"
                                    class="mt-0.5 w-4 h-4 text-brand-600 border-gray-300 focus:ring-brand-500 cursor-pointer">
                                <div>
                                    <span class="block text-sm font-semibold text-gray-900">Inside Dhaka</span>
                                    <span class="block text-sm text-gray-600 mt-1">{{ $deliveryCharges['inside'] ?? 80 }}
                                        ৳</span>
                                </div>
                            </label>
                            <label
                                class="relative flex items-start gap-3 p-4 border rounded-md cursor-pointer transition-colors"
                                :class="form.delivery_type === 'sub' ? 'border-brand-500 bg-brand-50/20' : 'border-gray-200 bg-white hover:border-gray-300'">
                                <input type="radio" x-model="form.delivery_type" value="sub"
                                    class="mt-0.5 w-4 h-4 text-brand-600 border-gray-300 focus:ring-brand-500 cursor-pointer">
                                <div>
                                    <span class="block text-sm font-semibold text-gray-900">Sub Dhaka</span>
                                    <span class="block text-sm text-gray-600 mt-1">{{ $deliveryCharges['sub'] ?? 120 }}
                                        ৳</span>
                                </div>
                            </label>
                            <label
                                class="relative flex items-start gap-3 p-4 border rounded-md cursor-pointer transition-colors"
                                :class="form.delivery_type === 'outside' ? 'border-brand-500 bg-brand-50/20' : 'border-gray-200 bg-white hover:border-gray-300'">
                                <input type="radio" x-model="form.delivery_type" value="outside"
                                    class="mt-0.5 w-4 h-4 text-brand-600 border-gray-300 focus:ring-brand-500 cursor-pointer">
                                <div>
                                    <span class="block text-sm font-semibold text-gray-900">Outside Dhaka</span>
                                    <span class="block text-sm text-gray-600 mt-1">{{ $deliveryCharges['outside'] ?? 150 }}
                                        ৳</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- 03. Payment Method -->
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden mb-6">
                    <div class="border-b border-gray-100 px-6 py-4 bg-gray-50/50">
                        <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wide">03. Payment Method <span
                                class="text-red-500">*</span></h2>
                    </div>
                    <div class="p-6">
                        <label
                            class="relative flex items-center gap-4 p-4 border border-brand-500 bg-brand-50/20 rounded-md cursor-pointer">
                            <input type="radio" x-model="form.payment_method" value="cod"
                                class="w-4 h-4 text-brand-600 border-gray-300 focus:ring-brand-500 cursor-pointer" checked>
                            <div
                                class="w-10 h-10 bg-white rounded flex items-center justify-center text-xl border border-brand-100 shadow-sm">
                                💳
                            </div>
                            <div>
                                <span class="block text-sm font-semibold text-gray-900">Cash On Delivery</span>
                                <span class="block text-xs text-gray-500 mt-0.5">Pay when you receive the product</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Actions (Desktop Only) -->
                <div class="checkout-actions-desktop hidden lg:flex flex-col-reverse sm:flex-row justify-end items-center gap-4 mt-8">
                    <a href="{{ route('home') }}"
                        class="cursor-pointer inline-flex items-center justify-center w-full sm:w-auto px-6 py-3 text-gray-600 font-semibold hover:bg-gray-100 hover:text-gray-900 rounded-md transition-colors text-center text-sm">Back
                        to Shopping</a>
                    <button @click="submitOrder" :disabled="isSubmitting"
                        class="cursor-pointer w-full sm:w-auto px-8 py-3 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-md shadow transition-colors flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-text="isSubmitting ? 'Processing...' : 'Confirm Order'" class="pointer-events-none"></span>
                        <i class="ph-bold ph-arrow-right text-lg pointer-events-none" x-show="!isSubmitting"></i>
                        <i class="ph-bold ph-spinner animate-spin text-lg pointer-events-none" x-show="isSubmitting" style="display: none;"></i>
                    </button>
                </div>

            </div>

            <!-- Right Column: Order Summary -->
            <div class="checkout-right">
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                    <h3 class="text-sm font-bold text-gray-900 mb-5 pb-4 border-b border-gray-100 uppercase tracking-wide">
                        Order Summary</h3>

                    <div class="space-y-4 max-h-[400px] overflow-y-auto custom-scrollbar pr-2 mb-6">
                        <template x-for="(item, index) in $store.cart.items" :key="index">
                            <div
                                class="flex gap-4 items-start pb-4 border-b border-gray-50 last:border-0 last:pb-0 relative group">
                                <div class="w-16 h-16 rounded border border-gray-100 bg-gray-50 overflow-hidden shrink-0">
                                    <img :src="item.image" :alt="item.title" class="w-full h-full object-cover">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-semibold text-gray-900 truncate" x-text="item.title"></h4>
                                    <div class="text-[11px] text-gray-500 mt-0.5 truncate"
                                        x-text="item.variant_label ? 'Variant: ' + item.variant_label : 'Single Piece'">
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1 font-semibold" x-text="item.price + ' ৳'"></div>

                                    <!-- Quantity Control -->
                                    <div class="flex items-center gap-2 mt-2">
                                        <div class="flex items-center border border-gray-200 rounded-md bg-white shadow-sm">
                                            <button type="button"
                                                @click="$store.cart.updateQuantity(index, item.quantity - 1)"
                                                class="w-7 h-7 flex items-center justify-center text-gray-500 hover:text-brand-600 hover:bg-brand-50 transition-colors rounded-l-md disabled:opacity-30 disabled:cursor-not-allowed"
                                                :disabled="item.quantity <= 1">
                                                <i class="ph-bold ph-minus text-[10px]"></i>
                                            </button>
                                            <span class="w-8 text-center text-xs font-bold text-gray-700"
                                                x-text="item.quantity"></span>
                                            <button type="button"
                                                @click="$store.cart.updateQuantity(index, item.quantity + 1)"
                                                class="w-7 h-7 flex items-center justify-center text-gray-500 hover:text-brand-600 hover:bg-brand-50 transition-colors rounded-r-md">
                                                <i class="ph-bold ph-plus text-[10px]"></i>
                                            </button>
                                        </div>
                                        <button type="button" @click="confirmRemove(index)"
                                            class="text-gray-400 hover:text-red-500 transition-colors text-xs font-medium flex items-center gap-1 px-2 py-1 rounded hover:bg-red-50">
                                            <i class="ph ph-trash"></i> Remove
                                        </button>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end justify-between shrink-0 h-16">
                                    <div class="text-sm font-bold text-gray-900 mt-auto"
                                        x-text="(item.price * item.quantity) + ' ৳'"></div>
                                </div>
                            </div>
                        </template>
                        <template x-if="$store.cart.items.length === 0">
                            <div class="text-center text-gray-500 py-6">
                                <p class="text-sm">Your cart is empty.</p>
                                <a href="{{ route('home') }}"
                                    class="text-brand-600 font-semibold text-xs mt-2 inline-block hover:underline">Continue
                                    Shopping</a>
                            </div>
                        </template>
                    </div>

                    <!-- Coupon Section -->
                    <div class="mb-6">
                        <label class="block text-[10px] font-bold text-gray-500 mb-1.5 uppercase tracking-wider">
                            Enter your coupon code
                        </label>
                        
                        <!-- When NO coupon applied -->
                        <div x-show="!appliedCoupon">
                            <div class="relative">
                                <input type="text" x-model="couponInput" @keydown.enter.prevent="applyCoupon"
                                    placeholder="Enter Coupon Code"
                                    class="w-full bg-white border border-gray-200 rounded-md pl-4 pr-24 py-2.5 text-sm uppercase focus:outline-none focus:ring-1 focus:ring-brand-500 focus:border-brand-500 transition-colors text-gray-800 placeholder-gray-400">
                                <button type="button" @click="applyCoupon" :disabled="isApplyingCoupon"
                                    class="absolute top-1 right-1 bottom-1 px-4 bg-brand-500 hover:bg-brand-600 text-white text-xs font-bold rounded transition-colors shadow-sm disabled:opacity-50 flex items-center gap-1 cursor-pointer">
                                    <i x-show="isApplyingCoupon" class="ph ph-spinner animate-spin"></i>
                                    <span x-text="isApplyingCoupon ? 'Checking...' : 'Apply'"></span>
                                </button>
                            </div>
                            <p x-show="couponError" x-text="couponError" style="display: none;" class="text-xs text-red-500 mt-1.5 font-medium"></p>
                        </div>

                        <!-- When coupon IS applied -->
                        <div x-show="appliedCoupon" style="display: none;"
                            class="flex items-center justify-between p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                    <i class="ph-fill ph-tag text-lg"></i>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-emerald-950 font-mono tracking-wider uppercase" x-text="appliedCoupon ? appliedCoupon.code : ''"></span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-200/80 text-emerald-800" x-text="couponRateLabel"></span>
                                    </div>
                                    <span class="text-xs text-emerald-700 font-medium block mt-0.5">
                                        <span x-text="couponRateLabel"></span> applied! You saved <span class="font-bold text-emerald-800" x-text="couponDiscount"></span> ৳
                                    </span>
                                </div>
                            </div>
                            <button type="button" @click="removeCoupon"
                                class="text-xs font-bold text-red-500 hover:text-red-700 hover:bg-red-50 px-2.5 py-1 rounded-md transition cursor-pointer">
                                Remove
                            </button>
                        </div>
                    </div>

                    <div class="space-y-3 mb-5 text-sm">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500">Subtotal</span>
                            <span class="font-semibold text-gray-900" x-text="cartTotal + ' ৳'"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500">Delivery Charge</span>
                            <span class="font-semibold text-gray-900" x-text="deliveryCharge + ' ৳'"></span>
                        </div>
                        <!-- Coupon Discount Line -->
                        <div x-show="couponDiscount > 0" style="display: none;" class="flex justify-between items-center text-emerald-600 font-medium">
                            <span class="flex items-center gap-1.5">
                                <i class="ph-bold ph-tag"></i>
                                <span>Coupon Discount</span>
                                <span class="text-[11px] text-emerald-700 bg-emerald-100/80 px-1.5 py-0.5 rounded-md font-bold" x-text="'(' + couponRateLabel + ')'"></span>
                            </span>
                            <span class="font-bold" x-text="'- ' + couponDiscount + ' ৳'"></span>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-200 flex justify-between items-center">
                        <span class="text-sm font-bold text-gray-900 uppercase tracking-wide">TOTAL COST:</span>
                        <span class="text-lg font-black text-brand-600" x-text="grandTotal + ' ৳'"></span>
                    </div>
                </div>

                <!-- Actions (Mobile Only: Below Order Summary) -->
                <div class="checkout-actions-mobile flex lg:hidden flex-col items-center gap-3 mt-6">
                    <button @click="submitOrder" :disabled="isSubmitting"
                        class="cursor-pointer w-full py-3.5 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-md shadow transition-colors flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed text-base">
                        <span x-text="isSubmitting ? 'Processing...' : 'Confirm Order'" class="pointer-events-none"></span>
                        <i class="ph-bold ph-arrow-right text-lg pointer-events-none" x-show="!isSubmitting"></i>
                        <i class="ph-bold ph-spinner animate-spin text-lg pointer-events-none" x-show="isSubmitting" style="display: none;"></i>
                    </button>
                    <a href="{{ route('home') }}"
                        class="cursor-pointer inline-flex items-center justify-center w-full py-2.5 text-gray-600 font-semibold hover:bg-gray-100 hover:text-gray-900 rounded-md transition-colors text-center text-sm">Back
                        to Shopping</a>
                </div>
            </div>
        </div>

        <!-- Remove Confirmation Modal -->
        <div x-show="removeModalOpen" style="display: none;"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div x-show="removeModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="cancelRemove"></div>
            <div x-show="removeModalOpen" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative bg-white rounded-[2rem] shadow-2xl w-full max-w-sm overflow-hidden z-10 border border-gray-100">
                <div class="p-8 text-center">
                    <div
                        class="w-20 h-20 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-5 border-4 border-white shadow-sm">
                        <i class="ph-fill ph-trash text-4xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2 tracking-tight">Remove Item?</h3>
                    <p class="text-sm text-gray-500 mb-8 leading-relaxed">Are you sure you want to remove this item from
                        your cart? You can add it back later.</p>
                    <div class="flex items-center gap-3">
                        <button type="button" @click="cancelRemove"
                            class="flex-1 px-5 py-3.5 bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 rounded-xl text-sm font-bold transition-all shadow-sm">Keep
                            It</button>
                        <button type="button" @click="executeRemove"
                            class="flex-1 px-5 py-3.5 bg-red-500 hover:bg-red-600 text-white shadow-lg shadow-red-500/25 rounded-xl text-sm font-bold transition-all transform hover:-translate-y-0.5">Yes,
                            Remove</button>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        function checkoutPage() {
            return {
                form: {
                    name: '{{ Auth::check() ? addslashes(Auth::user()->name) : '' }}',
                    phone: '',
                    address: '',
                    notes: '',
                    delivery_type: 'inside', // inside, sub, outside
                    payment_method: 'cod',
                    coupon_code: ''
                },

                syncContactTimer: null,
                syncContactInfo() {
                    if (this.syncContactTimer) clearTimeout(this.syncContactTimer);
                    this.syncContactTimer = setTimeout(() => {
                        try {
                            const vid = localStorage.getItem('fb_vid') || '';
                            if (!vid || (!this.form.name && !this.form.phone)) return;

                            // Persist shopper profile locally for live monitor recognition
                            try {
                                var p = JSON.parse(localStorage.getItem('fb_v_profile') || '{}') || {};
                                if (this.form.name) p.name = this.form.name;
                                if (this.form.phone) p.phone = this.form.phone;
                                if (this.form.name) p.last_seen_as = this.form.name;
                                localStorage.setItem('fb_v_profile', JSON.stringify(p));
                            } catch (e) {}

                            fetch('{{ route('api.cart.update-info') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    visitor_token: vid,
                                    name: this.form.name,
                                    phone: this.form.phone
                                })
                            }).catch(() => {});
                        } catch (e) {}
                    }, 1000);
                },

                init() {
                    this.$nextTick(() => {
                        if (typeof window.trackFbEvent === 'function') {
                            const cart = typeof Alpine !== 'undefined' ? Alpine.store('cart') : null;
                            if (cart && cart.items && cart.items.length > 0) {
                                window.trackFbEvent('InitiateCheckout', {
                                    num_items: cart.totalItems,
                                    value: cart.totalAmount,
                                    currency: 'BDT',
                                    content_type: 'product',
                                    content_ids: cart.items.map(i => String(i.product_id || i.id))
                                });
                            }
                        }
                    });
                },

                isSubmitting: false,
                removeModalOpen: false,
                itemToRemove: null,

                couponInput: '',
                appliedCoupon: null,
                isApplyingCoupon: false,
                couponError: '',

                get couponRateLabel() {
                    if (!this.appliedCoupon) return '';
                    if (this.appliedCoupon.discount_type === 'percentage') {
                        return this.appliedCoupon.discount_amount + '% OFF';
                    }
                    if (this.appliedCoupon.discount_type === 'free_shipping') {
                        return 'Free Shipping';
                    }
                    return '৳' + this.appliedCoupon.discount_amount + ' OFF';
                },

                get couponDiscount() {
                    return this.appliedCoupon ? (parseFloat(this.appliedCoupon.discount) || 0) : 0;
                },

                get grandTotal() {
                    return Math.max(0, this.cartTotal + this.deliveryCharge - this.couponDiscount);
                },

                async applyCoupon() {
                    const code = this.couponInput.trim();
                    if (!code) {
                        this.couponError = 'Please enter a coupon code.';
                        return;
                    }
                    if (this.cartTotal <= 0) {
                        this.couponError = 'Your cart is empty.';
                        return;
                    }

                    this.isApplyingCoupon = true;
                    this.couponError = '';

                    try {
                        const cartItems = Alpine.store('cart').items.map(item => ({
                            product_id: item.product_id || item.id,
                            quantity: item.quantity,
                            price: item.price
                        }));

                        const response = await fetch('{{ route('checkout.apply-coupon') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                coupon_code: code,
                                delivery_type: this.form.delivery_type,
                                items: cartItems,
                                phone: this.form.phone
                            })
                        });

                        const data = await response.json();

                        if (response.ok && data.success) {
                            this.appliedCoupon = {
                                code: data.coupon_code,
                                discount: parseFloat(data.discount) || 0,
                                discount_type: data.discount_type,
                                discount_amount: parseFloat(data.discount_amount) || 0,
                                message: data.message
                            };
                            this.form.coupon_code = data.coupon_code;
                            this.couponInput = '';
                            this.couponError = '';
                            window.dispatchEvent(new CustomEvent('notify', { detail: { message: data.message || 'Coupon applied successfully!', type: 'success' } }));
                        } else {
                            this.couponError = data.message || 'Invalid coupon code.';
                            window.dispatchEvent(new CustomEvent('notify', { detail: { message: this.couponError, type: 'error' } }));
                        }
                    } catch (e) {
                        this.couponError = 'Failed to apply coupon. Please try again.';
                    } finally {
                        this.isApplyingCoupon = false;
                    }
                },

                removeCoupon() {
                    this.appliedCoupon = null;
                    this.form.coupon_code = '';
                    this.couponError = '';
                    window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Coupon removed.', type: 'info' } }));
                },

                confirmRemove(index) {
                    this.itemToRemove = index;
                    this.removeModalOpen = true;
                },

                executeRemove() {
                    if (this.itemToRemove !== null) {
                        if (typeof Alpine !== 'undefined' && Alpine.store('cart')) {
                            Alpine.store('cart').remove(this.itemToRemove);
                            window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Item removed from cart.', type: 'success' } }));
                        }
                        this.removeModalOpen = false;
                        this.itemToRemove = null;
                    }
                },

                cancelRemove() {
                    this.removeModalOpen = false;
                    this.itemToRemove = null;
                },

                get cartTotal() {
                    return typeof Alpine !== 'undefined' && Alpine.store('cart') ? Alpine.store('cart').totalAmount : 0;
                },

                get deliveryCharge() {
                    if (this.cartTotal >= {{ $deliveryCharges['free_min_order'] ?? 3000 }}) return 0;

                    if (this.form.delivery_type === 'inside') return {{ $deliveryCharges['inside'] ?? 80 }};
                    if (this.form.delivery_type === 'sub') return {{ $deliveryCharges['sub'] ?? 120 }};
                    if (this.form.delivery_type === 'outside') return {{ $deliveryCharges['outside'] ?? 150 }};
                    return {{ $deliveryCharges['inside'] ?? 80 }};
                },

                async submitOrder() {
                    if (typeof Alpine !== 'undefined' && Alpine.store('cart') && Alpine.store('cart').items.length === 0) {
                        window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Your cart is empty. Please add items to proceed.', type: 'warning' } }));
                        return;
                    }
                    if (!this.form.name || !this.form.phone || !this.form.address) {
                        window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Please fill in all required fields.', type: 'error' } }));
                        return;
                    }

                    if (this.isSubmitting) return;
                    this.isSubmitting = true;

                    try {
                        const cartItems = Alpine.store('cart').items.map(item => ({
                            product_id: item.product_id || item.id,
                            quantity: item.quantity,
                            price: item.price,
                            variant_label: item.variant_label || null,
                            variant_id: item.variant_id || null,
                            variant_sku: item.variant_sku || item.sku || null
                        }));

                        const payload = {
                            ...this.form,
                            visitor_token: localStorage.getItem('fb_vid') || '',
                            items: cartItems
                        };

                        const response = await fetch('{{ route('checkout.process') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(payload)
                        });

                        const data = await response.json();

                        if (response.ok && data.success) {
                            window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Order Placed Successfully!', type: 'success' } }));

                            // Clear Cart
                            Alpine.store('cart').items = [];
                            Alpine.store('cart').save();

                            setTimeout(() => {
                                window.location.href = '/checkout/success/' + data.order_id;
                            }, 1000);
                        } else {
                            throw new Error(data.message || 'Something went wrong while placing order.');
                        }
                    } catch (error) {
                        window.dispatchEvent(new CustomEvent('notify', { detail: { message: error.message || 'Server error occurred.', type: 'error' } }));
                    } finally {
                        this.isSubmitting = false;
                    }
                }
            }
        }
    </script>
@endpush