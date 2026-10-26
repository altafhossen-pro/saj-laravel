@extends('layouts.admin')

@section('title', 'Manual Order (POS) - Admin Panel')
@section('header_title', 'POS System')

@push('styles')
    <style>
        /* Toggle Switch */
        .toggle-checkbox:checked {
            right: 0;
            border-color: #0d9488;
        }

        .toggle-checkbox:checked+.toggle-label {
            background-color: #0d9488;
        }

        .toggle-checkbox {
            right: 0;
            z-index: 1;
            border-color: #e5e7eb;
            transition: all 0.3s;
        }

        .toggle-label {
            background-color: #e5e7eb;
            transition: all 0.3s;
        }

        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }

        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
@endpush

@section('content')
    <div x-data="posSystem()" x-init="$nextTick(() => $refs.searchInput.focus())"
        class="flex flex-col lg:flex-row gap-6 h-[calc(100vh-120px)]">
        <!-- Left Column: Search & Cart -->
        <div class="flex-1 flex flex-col gap-4 min-w-0">
            <!-- Header equivalent from image -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <h2 class="text-lg font-bold text-gray-900">POS System</h2>
            </div>

            <!-- Search Bar -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 relative">
                <i class="ph ph-magnifying-glass absolute left-8 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                <input type="text" x-ref="searchInput" x-model="searchQuery" @input.debounce.300ms="searchProducts()"
                    placeholder="Scan Barcode or Search by Name/SKU.."
                    class="w-full pl-12 pr-4 py-2.5 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 transition-colors text-sm">

                <i x-show="isSearching"
                    class="ph ph-spinner animate-spin absolute right-8 top-1/2 -translate-y-1/2 text-gray-400"></i>

                <!-- Search Suggestions -->
                <div x-show="suggestions.length > 0 && showSuggestions" @click.away="showSuggestions = false"
                    class="absolute z-50 left-4 right-4 mt-2 bg-white rounded-xl shadow-xl border border-gray-100 max-h-96 overflow-y-auto">
                    <template x-for="product in suggestions" :key="product.id">
                        <div class="p-3 border-b border-gray-50 last:border-0 hover:bg-gray-50 transition-colors cursor-pointer"
                            @click="if(product.product_type === 'single') { addToCart(product) } else { expandedProduct = expandedProduct === product.id ? null : product.id }">
                            <div class="flex items-center gap-3 mb-2">
                                <div
                                    class="w-12 h-12 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0 flex items-center justify-center relative">
                                    <img x-show="product.featured_image" :src="getImageUrl(product.featured_image)"
                                        class="w-full h-full object-cover">
                                    <i x-show="!product.featured_image" class="ph ph-image text-gray-400 text-xl"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-medium text-gray-900 truncate text-sm" x-text="product.title"></h4>
                                        <span x-show="product.product_type === 'single'"
                                            class="px-1.5 py-0.5 bg-gray-100 text-gray-600 rounded text-[9px] font-medium uppercase tracking-wider">Single</span>
                                        <span x-show="product.product_type === 'variable'"
                                            class="px-1.5 py-0.5 bg-brand-50 text-brand-600 rounded text-[9px] font-medium uppercase tracking-wider">Variable</span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1" x-show="product.product_type === 'single'">
                                        <span x-text="'SKU: ' + (product.sku || 'N/A') + ' · ৳' + (product.current_price || product.regular_price)"></span>
                                        <span class="mx-1">&bull;</span>
                                        <span :class="product.stock > 0 ? 'text-green-600' : 'text-red-500 font-medium'" x-text="product.stock > 0 ? 'Stock: ' + product.stock : 'Out of Stock'"></span>
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1" x-show="product.product_type === 'variable'">
                                        <span x-text="product.variants.length + ' variants'"></span>
                                        <span class="mx-1">&bull;</span>
                                        <span :class="product.stock > 0 ? 'text-green-600' : 'text-red-500 font-medium'" x-text="product.stock > 0 ? 'Total Stock: ' + product.stock : 'Out of Stock'"></span>
                                    </p>
                                </div>
                                <div x-show="product.product_type === 'single'">
                                    <button @click.stop="addToCart(product)"
                                        class="px-3 py-1.5 bg-brand-50 hover:bg-brand-100 text-brand-600 rounded text-xs font-medium transition-colors cursor-pointer">Add</button>
                                </div>
                            </div>

                            <!-- Variants (if applicable) -->
                            <div x-show="product.product_type === 'variable' && product.variants.length > 0 && expandedProduct === product.id"
                                class="pl-14 pr-2 pb-2 space-y-1.5 mt-2">
                                <template x-for="variant in product.variants" :key="variant.id">
                                    <div class="flex items-center justify-between bg-white border border-gray-100 rounded-lg p-2.5 hover:border-brand-300 hover:shadow-sm transition-all cursor-pointer group"
                                        @click="addToCart(product, variant)">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-10 h-10 bg-gray-50 rounded-md overflow-hidden flex items-center justify-center flex-shrink-0 border border-gray-100">
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
                                                        <div
                                                            class="inline-flex items-center text-[11px] leading-none bg-gray-50 border border-gray-200 rounded-md px-1.5 py-1">
                                                            <span class="text-gray-500 mr-1" x-text="key + ':'"></span>
                                                            <span class="font-medium text-gray-800" x-text="val"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                                <!-- SKU, Price & Stock -->
                                                <div class="flex items-center gap-2 text-[11px] mt-1">
                                                    <span class="text-brand-600 font-bold"
                                                        x-text="'৳' + variant.current_price"></span>
                                                    <span class="text-gray-300">&bull;</span>
                                                    <span class="text-gray-500"
                                                        x-text="'SKU: ' + (variant.sku || 'N/A')"></span>
                                                    <span class="text-gray-300">&bull;</span>
                                                    <span :class="variant.stock > 0 ? 'text-green-600 font-medium' : 'text-red-500 font-bold'" 
                                                        x-text="variant.stock > 0 ? 'In Stock (' + variant.stock + ')' : 'Out of Stock'"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <button @click.stop="addToCart(product, variant)"
                                            class="px-3 py-1.5 bg-brand-50 group-hover:bg-brand-600 group-hover:text-white text-brand-600 rounded-md text-xs font-semibold transition-colors flex-shrink-0 flex items-center">
                                            <i class="ph ph-plus mr-1"></i> Add
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Cart Area -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 flex-1 flex flex-col overflow-hidden relative">
                <!-- Empty State -->
                <div x-show="cart.length === 0"
                    class="absolute inset-0 flex flex-col items-center justify-center text-gray-400">
                    <i class="ph ph-shopping-cart text-6xl mb-4 opacity-50"></i>
                    <p class="text-xl font-medium text-gray-500">Cart is empty</p>
                    <p class="text-sm mt-1">Scan a product to add it to the cart</p>
                </div>

                <!-- Cart Items -->
                <div x-show="cart.length > 0" style="display: none;" class="flex-1 overflow-y-auto p-4 space-y-3">
                    <template x-for="(item, index) in cart" :key="item.cartId">
                        <div class="flex items-center gap-3 p-3 bg-gray-50 border border-gray-100 rounded-lg">
                            <!-- Image with Hover Zoom -->
                            <div>
                                <div class="w-12 h-12 bg-white rounded border border-gray-100 overflow-hidden flex-shrink-0 flex items-center justify-center cursor-crosshair"
                                     @mouseenter="if(item.image) hoveredImage = item.image"
                                     @mouseleave="hoveredImage = null">
                                    <img x-show="item.image" :src="item.image" class="w-full h-full object-cover">
                                    <i x-show="!item.image" class="ph ph-image text-gray-300"></i>
                                </div>
                            </div>
                            
                            <!-- Title & SKU -->
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-[15px] text-gray-900 truncate" x-text="item.name"></p>
                                <p class="text-[13px] text-gray-500" x-show="item.variantLabel" x-text="item.variantLabel">
                                </p>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <span x-show="item.variant_sku || item.product_sku" class="text-[12px] text-gray-500 font-mono"
                                        x-text="'SKU: ' + (item.variant_sku || item.product_sku)"></span>
                                    <span class="text-gray-300" x-show="item.variant_sku || item.product_sku">&bull;</span>
                                    <span class="text-[12px] text-gray-500">
                                        Available: <span :class="(item.max_stock - item.qty) > 0 ? 'font-medium text-green-600' : 'font-medium text-red-500'" 
                                                         x-text="(item.max_stock - item.qty)"></span>
                                    </span>
                                </div>
                            </div>

                            <!-- Editable Price -->
                            <div class="w-28 flex-shrink-0">
                                <div class="relative">
                                    <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-500 text-sm">৳</span>
                                    <input type="text" x-model.number="item.price" @input="item.price = $event.target.value.replace(/[^0-9.]/g, '')"
                                        class="w-full pl-6 pr-7 py-2 text-sm font-bold border border-gray-200 rounded-md focus:outline-none focus:ring-1 focus:ring-brand-500 bg-white text-right">
                                    
                                    <!-- Reset Price Button -->
                                    <button x-show="Number(item.price) !== Number(item.original_price)" 
                                            @click="item.price = item.original_price"
                                            title="Reset to original price"
                                            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-brand-600 transition-colors cursor-pointer"
                                            style="display: none;">
                                        <i class="ph ph-arrow-counter-clockwise text-xs"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="flex items-center border border-gray-200 rounded bg-white">
                                    <button @click="updateQty(index, -1)"
                                        class="w-8 h-8 flex items-center justify-center text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition-colors cursor-pointer"><i
                                            class="ph ph-minus text-sm"></i></button>
                                    <input type="text" x-model.number="item.qty"
                                        @input="item.qty = $event.target.value.replace(/[^0-9]/g, ''); if(item.qty === '') item.qty = 1"
                                        class="w-12 h-8 text-center text-sm font-semibold border-x border-gray-200 focus:outline-none">
                                    <button @click="updateQty(index, 1)"
                                        class="w-8 h-8 flex items-center justify-center text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition-colors cursor-pointer"><i
                                            class="ph ph-plus text-sm"></i></button>
                                </div>
                                <button @click="removeItem(index)"
                                    class="w-8 h-8 flex items-center justify-center text-red-400 hover:bg-red-50 hover:text-red-600 rounded transition-colors cursor-pointer">
                                    <i class="ph ph-trash text-base"></i>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Right Column: Sidebar -->
        <div class="flex-none flex flex-col gap-4 w-full lg:w-auto" style="width: 400px; max-width: 100%;">
            <!-- Customer Info -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <div class="flex justify-between items-center mb-5 border-b border-gray-100 pb-3">
                    <h3 class="font-bold text-gray-900 flex items-center gap-2"><i
                            class="ph ph-user text-brand-600 text-lg"></i> Customer Info</h3>
                    <label class="flex items-center gap-2 text-xs font-medium text-gray-600 cursor-pointer">
                        Draft Mode
                        <div class="relative inline-block w-10 h-5 align-middle select-none">
                            <input type="checkbox" x-model="form.is_draft" class="peer sr-only">
                            <div
                                class="w-10 h-5 bg-gray-200 rounded-full peer-checked:bg-brand-600 transition-colors duration-300">
                            </div>
                            <div
                                class="absolute left-0 top-0 w-5 h-5 bg-white border-2 border-gray-200 rounded-full peer-checked:translate-x-full peer-checked:border-brand-600 transition-transform duration-300">
                            </div>
                        </div>
                    </label>
                </div>

                <div class="space-y-4">
                    <div class="flex gap-4">
                        <div class="w-1/3">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Source <span
                                    class="text-red-500">*</span></label>
                            <select x-model="form.source"
                                class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition-colors">
                                <option value="Facebook">Facebook</option>
                                <option value="WhatsApp">WhatsApp</option>
                                <option value="Website">Website</option>
                                <option value="Phone">Phone</option>
                                <option value="Walk-in">Walk-in</option>
                                <option value="Instagram">Instagram</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="flex-1">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Customer Phone / Email <span
                                    class="text-red-500">*</span></label>
                            <div class="relative">
                                <i class="ph ph-user absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input type="text" x-model="form.customer_contact" @input.debounce.500ms="checkCustomer()"
                                    placeholder="Enter 11 digit phone or valid email..."
                                    class="w-full pl-9 pr-3 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition-colors">
                                <!-- Badges -->
                                <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center">
                                    <i x-show="isCheckingCustomer" class="ph ph-spinner animate-spin text-gray-400"
                                        style="display:none;"></i>
                                    <span x-show="customerStatus === 'new'" style="display:none;"
                                        class="px-2 py-0.5 bg-blue-50 text-blue-600 border border-blue-200 rounded text-[10px] font-medium">New</span>
                                    <span x-show="customerStatus === 'returning'" style="display:none;"
                                        class="px-2 py-0.5 bg-green-50 text-green-600 border border-green-200 rounded text-[10px] font-medium">Returning</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Customer Name <span
                                class="text-red-500">*</span></label>
                        <input type="text" x-model="form.customer_name" placeholder="Full Name"
                            class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition-colors">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Shipping Address <span
                                class="text-red-500">*</span></label>
                        <textarea x-model="form.shipping_address" rows="2" placeholder="Full Delivery Address"
                            class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition-colors resize-none"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Order Notes</label>
                        <textarea x-model="form.notes" rows="3" placeholder="Add any special instructions or notes"
                            class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition-colors resize-none"></textarea>
                    </div>
                </div>
            </div>

            <!-- Payment Details -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex-1 flex flex-col">
                <h3 class="font-bold text-gray-900 mb-5 border-b border-gray-100 pb-3 flex items-center gap-2">Payment
                    Details</h3>

                <div class="space-y-4 flex-1">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-600">Subtotal</span>
                        <span class="font-medium text-gray-900" x-text="'৳' + money(subtotal)"></span>
                    </div>

                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-600">Delivery Charge <span class="text-red-500">*</span></span>
                        <div class="flex gap-2">
                            <template x-for="fee in deliveryOptions" :key="fee">
                                <button @click="form.delivery_charge = fee" type="button"
                                    class="px-2 py-1 rounded text-xs font-medium transition-colors border cursor-pointer"
                                    :class="form.delivery_charge === fee ? 'bg-brand-50 border-brand-200 text-brand-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'">
                                    ৳ <span x-text="fee"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-600">Extra Discount</span>
                        <div class="relative w-20">
                            <span class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 text-xs">-৳</span>
                            <input type="text" inputmode="decimal"
                                @input="form.discount = $event.target.value.replace(/[^0-9.]/g, '')"
                                x-model.number="form.discount"
                                class="w-full pl-6 pr-2 py-1 text-right bg-white border border-gray-200 rounded text-xs focus:outline-none focus:ring-1 focus:ring-brand-500">
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center text-sm" x-show="itemDiscount > 0" style="display: none;">
                        <span class="text-gray-600">Item Discount</span>
                        <span class="font-medium text-red-500" x-text="'-৳' + money(itemDiscount)"></span>
                    </div>

                    <div class="pt-4 mt-2 border-t border-gray-100 flex justify-between items-center">
                        <span class="font-bold text-gray-900 text-base">Total</span>
                        <span class="font-bold text-brand-600 text-xl" x-text="'৳' + money(total)"></span>
                    </div>
                </div>

                <button @click="submitOrder()" :disabled="cart.length === 0 || isSubmitting"
                    class="w-full py-3 mt-4 rounded-lg font-medium text-sm flex items-center justify-center gap-2 transition-colors cursor-pointer"
                    :class="cart.length === 0 || isSubmitting ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-brand-600 hover:bg-brand-700 text-white shadow-sm'">
                    <i x-show="isSubmitting" class="ph ph-spinner animate-spin"></i>
                    <span x-show="!isSubmitting">Complete Order</span>
                    <span x-show="isSubmitting">Processing...</span>
                </button>
            </div>
        </div>

        <!-- Image Zoom Modal -->
        <div x-show="hoveredImage" 
             style="display: none;"
             x-transition.opacity.duration.200ms
             class="fixed inset-0 z-[100] flex items-center justify-center pointer-events-none bg-gray-900/20 backdrop-blur-sm">
            <div class="bg-white p-3 rounded-2xl shadow-2xl border border-gray-100 transform transition-transform"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-90 translate-y-4">
                <img :src="hoveredImage" class="w-80 h-80 lg:w-96 lg:h-96 object-cover rounded-xl shadow-inner">
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('posSystem', () => ({
                    searchQuery: '',
                    isSearching: false,
                    showSuggestions: false,
                    suggestions: [],
                    expandedProduct: null,
                    hoveredImage: null,

                    cart: [],
                    deliveryOptions: [80, 120, 150],

                    form: {
                        is_draft: false,
                        source: 'Facebook',
                        customer_contact: '',
                        customer_name: '',
                        shipping_address: '',
                        notes: '',
                        delivery_charge: 80,
                        discount: 0
                    },

                    isCheckingCustomer: false,
                    customerStatus: null, // 'new' or 'returning'

                    isSubmitting: false,

                    get subtotal() {
                        return this.cart.reduce((sum, item) => sum + (Number(item.original_price) * (parseInt(item.qty) || 1)), 0);
                    },

                    get itemDiscount() {
                        return this.cart.reduce((sum, item) => {
                            let diff = Number(item.original_price) - Number(item.price);
                            return sum + (diff > 0 ? diff * (parseInt(item.qty) || 1) : 0);
                        }, 0);
                    },

                    get totalDiscount() {
                        const num = (val) => isNaN(parseFloat(val)) ? 0 : parseFloat(val);
                        return this.itemDiscount + num(this.form.discount);
                    },

                    get total() {
                        const num = (val) => isNaN(parseFloat(val)) ? 0 : parseFloat(val);
                        return Math.max(0, this.subtotal + num(this.form.delivery_charge) - this.totalDiscount);
                    },

                    money(val) {
                        return Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    },

                    getImageUrl(path) {
                        if (!path) return '';
                        if (path.startsWith('http://') || path.startsWith('https://')) return path;
                        return '/storage/' + path;
                    },

                    async searchProducts() {
                        if (this.searchQuery.length < 2) {
                            this.suggestions = [];
                            this.showSuggestions = false;
                            return;
                        }

                        this.isSearching = true;
                        try {
                            const res = await fetch(`/admin/dashboard/products/search?q=${encodeURIComponent(this.searchQuery)}`);
                            const data = await res.json();

                            // Direct match logic for barcode scanning
                            if (this.searchQuery.length >= 3) {
                                let exactMatchVariant = null;
                                let exactMatchProduct = null;

                                for (const product of data) {
                                    if (product.product_type === 'variable' && product.variants) {
                                        const match = product.variants.find(v => (v.sku || '').toLowerCase().trim() === this.searchQuery.toLowerCase().trim());
                                        if (match) {
                                            exactMatchVariant = match;
                                            exactMatchProduct = product;
                                            break;
                                        }
                                    }
                                }

                                if (exactMatchVariant) {
                                    this.addToCart(exactMatchProduct, exactMatchVariant);
                                    return; // stops suggestions from opening and clears search
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

                    async checkCustomer() {
                        const contact = this.form.customer_contact.trim();
                        if (contact.length < 11) {
                            this.customerStatus = null;
                            return;
                        }

                        this.isCheckingCustomer = true;
                        try {
                            const res = await fetch(`/admin/dashboard/customers/search?phone=${encodeURIComponent(contact)}`);
                            const data = await res.json();

                            if (data.found) {
                                this.customerStatus = 'returning';
                                if (data.name) this.form.customer_name = data.name;
                                if (data.address) this.form.shipping_address = data.address;
                            } else {
                                this.customerStatus = 'new';
                            }
                        } catch (err) {
                            console.error(err);
                            this.customerStatus = null;
                        } finally {
                            this.isCheckingCustomer = false;
                        }
                    },

                    addToCart(product, variant = null) {
                        const stockToCheck = variant ? variant.stock : product.stock;
                        if (!stockToCheck || stockToCheck <= 0) {
                            alert('This item is currently out of stock!');
                            return;
                        }

                        // Generate unique cart ID
                        const cartId = variant ? `p${product.id}-v${variant.id}` : `p${product.id}`;

                        // Check if already in cart
                        const existingIndex = this.cart.findIndex(i => i.cartId === cartId);
                        if (existingIndex > -1) {
                            if (this.cart[existingIndex].qty >= stockToCheck) {
                                alert(`Only ${stockToCheck} items available in stock!`);
                                return;
                            }
                            this.cart[existingIndex].qty++;
                            this.showSuggestions = false;
                            this.searchQuery = '';
                            this.$refs.searchInput.focus();
                            return;
                        }

                        // Create new cart item
                        let item = {
                            cartId: cartId,
                            product_id: product.id,
                            name: product.title,
                            qty: 1,
                            max_stock: stockToCheck,
                            price: product.current_price || product.regular_price,
                            original_price: product.current_price || product.regular_price,
                            image: product.featured_image ? this.getImageUrl(product.featured_image) : null,
                            product_sku: product.sku || null
                        };

                        if (variant) {
                            item.variant_id = variant.id;
                            item.variant_sku = variant.sku;
                            item.price = variant.current_price || item.price;
                            item.original_price = item.price;
                            item.image = (variant.images && variant.images.length > 0) ? this.getImageUrl(variant.images[0]) : item.image;

                            // create readable label
                            let attrParts = [];
                            if (variant.attributes) {
                                for (const [key, val] of Object.entries(variant.attributes)) {
                                    attrParts.push(val);
                                }
                            }
                            item.variantLabel = attrParts.join(', ');
                        } else {
                            item.variant_id = null;
                            item.variantLabel = null;
                        }

                        this.cart.unshift(item);
                        this.showSuggestions = false;
                        this.searchQuery = '';
                        this.$refs.searchInput.focus();
                    },

                    updateQty(index, change) {
                        const item = this.cart[index];
                        let newQty = parseInt(item.qty) + change;
                        
                        // We need the stock from the original item, but we didn't save it in cart item.
                        // Let's assume we can save max_stock in cart item during addToCart.
                        if (newQty < 1) newQty = 1;
                        if (item.max_stock && newQty > item.max_stock) {
                            alert(`Only ${item.max_stock} items available!`);
                            newQty = item.max_stock;
                        }
                        this.cart[index].qty = newQty;
                    },

                    removeItem(index) {
                        this.cart.splice(index, 1);
                    },

                    async submitOrder() {
                        if (!this.form.customer_contact) {
                            alert('Please enter customer phone or email.');
                            return;
                        }
                        if (this.cart.length === 0) return;

                        this.isSubmitting = true;

                        const payload = {
                            ...this.form,
                            items: this.cart,
                            subtotal: this.subtotal,
                            discount: this.totalDiscount,
                            total: this.total,
                            _token: document.querySelector('meta[name="csrf-token"]').content
                        };

                        try {
                            const res = await fetch('{{ route('admin.orders.store') }}', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify(payload)
                            });

                            const data = await res.json();
                            if (data.success) {
                                window.location.href = data.redirect;
                            } else {
                                alert('Error: ' + data.message);
                            }
                        } catch (e) {
                            console.error(e);
                            alert('Something went wrong.');
                        } finally {
                            this.isSubmitting = false;
                        }
                    }
                }));
            });
        </script>
    @endpush
@endsection