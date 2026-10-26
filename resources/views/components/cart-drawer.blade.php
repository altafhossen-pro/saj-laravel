<div x-data="{ open: false }" @open-cart-drawer.window="open = true">
    <!-- Backdrop -->
    <div x-show="open" style="display: none;" class="fixed inset-0 z-[90] bg-black/50 backdrop-blur-sm" @click="open = false" x-transition.opacity></div>

    <!-- Drawer -->
    <div x-show="open" style="display: none;"
         x-transition:enter="transition-transform duration-300 ease-out"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition-transform duration-200 ease-in"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed inset-y-0 right-0 z-[100] w-full max-w-sm bg-white shadow-2xl flex flex-col h-full border-l border-gray-100">
         
         <!-- Header -->
         <div class="flex items-center justify-between p-4 border-b border-gray-100 shrink-0">
            <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center">
                    <i class="ph ph-shopping-bag"></i>
                </div>
                Shopping Cart
            </h2>
            <button @click="open = false" class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
                <i class="ph ph-x text-lg"></i>
            </button>
         </div>

         <!-- Body / Items -->
         <div class="flex-1 overflow-y-auto p-4 flex flex-col gap-4">
            <template x-if="$store.cart.items.length === 0">
                <div class="flex flex-col items-center justify-center h-full text-gray-500 gap-3">
                    <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center text-gray-300">
                        <i class="ph ph-shopping-cart text-4xl"></i>
                    </div>
                    <p class="font-medium">Your cart is empty.</p>
                </div>
            </template>
            <template x-for="(item, index) in $store.cart.items" :key="index">
                <div class="flex gap-4 p-3 bg-white rounded-xl border border-gray-100 relative shadow-sm hover:border-brand-100 transition-colors">
                    <!-- Image -->
                    <div class="w-16 h-16 rounded-lg overflow-hidden shrink-0 bg-gray-50 border border-gray-100">
                        <img :src="item.image" :alt="item.title" class="w-full h-full object-cover">
                    </div>
                    <!-- Details -->
                    <div class="flex-1 flex flex-col justify-center min-w-0 pr-6">
                        <h4 class="text-sm font-bold text-gray-900 truncate" x-text="item.title"></h4>
                        <div class="text-[11px] text-gray-500 mt-0.5 truncate" x-text="item.variant_label || 'Single Piece'"></div>
                        <div class="text-[11px] text-gray-500 mb-1">Item Price: <span x-text="item.price"></span> ৳</div>
                        
                        <div class="flex items-center justify-between mt-auto">
                            <span class="text-sm font-bold text-brand-600" x-text="(item.price * item.quantity) + '৳'"></span>
                            
                            <!-- Quantity -->
                            <div class="flex items-center border border-brand-200 rounded-lg h-7 bg-brand-50 shrink-0">
                                <button @click="$store.cart.updateQuantity(index, item.quantity - 1)" class="w-6 h-full flex items-center justify-center text-brand-600 hover:text-brand-800 transition-colors cursor-pointer"><i class="ph ph-minus text-xs"></i></button>
                                <span class="text-xs font-bold text-brand-700 w-6 text-center" x-text="item.quantity"></span>
                                <button @click="$store.cart.updateQuantity(index, item.quantity + 1)" class="w-6 h-full flex items-center justify-center text-brand-600 hover:text-brand-800 transition-colors cursor-pointer"><i class="ph ph-plus text-xs"></i></button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Delete -->
                    <button @click="$store.cart.remove(index)" class="absolute top-2 right-2 text-red-400 hover:text-red-600 hover:bg-red-50 p-1.5 rounded-md transition-colors cursor-pointer">
                        <i class="ph ph-trash text-sm font-bold"></i>
                    </button>
                </div>
            </template>
         </div>

         <!-- Footer -->
         <div class="border-t border-gray-100 p-4 shrink-0 bg-white">
            @php
                $cartDelivery = \Illuminate\Support\Facades\Cache::rememberForever('delivery_charges', function () {
                    return [
                        'inside' => (int) \App\Models\Setting::get('delivery_charge_inside_dhaka', 70),
                        'sub' => (int) \App\Models\Setting::get('delivery_charge_sub_dhaka', 110),
                        'outside' => (int) \App\Models\Setting::get('delivery_charge_outside_dhaka', 130),
                        'free_min_order' => (int) \App\Models\Setting::get('delivery_free_min_order', 3000),
                    ];
                });
            @endphp
            <div class="flex justify-between items-center mb-2">
                <h3 class="text-sm font-bold text-gray-900">Delivery Charges</h3>
                <span class="text-[10px] font-bold px-2 py-0.5 bg-brand-50 text-brand-600 rounded-full">Min {{ $cartDelivery['free_min_order'] ?? 3000 }}৳ = Free</span>
            </div>
            
            <!-- Standard Delivery View -->
            <div x-show="$store.cart.totalAmount < {{ $cartDelivery['free_min_order'] ?? 3000 }}" class="bg-gray-50 rounded-lg p-3 space-y-2 mb-4 border border-gray-100">
                <div class="flex justify-between items-center text-xs text-gray-600">
                    <span class="flex items-center gap-1.5"><i class="ph-fill ph-map-pin text-gray-400"></i> Inside Dhaka:</span>
                    <span class="text-brand-600 font-bold">{{ $cartDelivery['inside'] ?? 80 }} ৳</span>
                </div>
                <div class="flex justify-between items-center text-xs text-gray-600">
                    <span class="flex items-center gap-1.5"><i class="ph-fill ph-truck text-gray-400"></i> Outside Dhaka:</span>
                    <span class="text-brand-600 font-bold">{{ $cartDelivery['outside'] ?? 150 }} ৳</span>
                </div>
            </div>

            <!-- Free Delivery Achieved View -->
            <div x-show="$store.cart.totalAmount >= {{ $cartDelivery['free_min_order'] ?? 3000 }}" style="display: none;" class="bg-green-50 rounded-lg p-3 mb-4 border border-green-200 flex items-center justify-center gap-2 text-green-700">
                <i class="ph-fill ph-gift text-lg"></i>
                <span class="text-xs font-bold uppercase tracking-wide">You got Free Delivery!</span>
            </div>
            
            <button class="w-full h-12 bg-brand-600 hover:bg-brand-700 text-white rounded-xl font-bold flex items-center justify-between px-5 transition-colors cursor-pointer shadow-md shadow-brand-500/20" @click="if($store.cart.items.length > 0) window.location.href='/checkout'">
                <span class="flex items-center gap-2">Proceed To Checkout <i class="ph-bold ph-arrow-right"></i></span>
                <span class="bg-white/20 px-2 py-0.5 rounded text-sm" x-text="$store.cart.totalAmount + ' ৳'"></span>
            </button>
         </div>
    </div>
</div>
