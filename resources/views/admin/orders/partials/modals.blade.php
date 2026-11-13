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
                                <option value="pending" :disabled="currentStatus === 'shipped' || currentStatus === 'delivered'">Pending</option>
                                <option value="confirmed" :disabled="currentStatus === 'shipped' || currentStatus === 'delivered'">Confirmed</option>
                                <option value="processing" :disabled="currentStatus === 'shipped' || currentStatus === 'delivered'">Processing</option>
                                <option value="shipped" :disabled="currentStatus === 'delivered'">Shipped</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled" :disabled="currentStatus === 'shipped' || currentStatus === 'delivered'" x-text="currentStatus === 'shipped' ? 'Cancelled (Not allowed after shipped)' : 'Cancelled'"></option>
                                <option value="returned">Returned</option>
                            </select>
                            <p x-show="currentStatus === 'shipped'" class="text-xs text-amber-600 mt-2 flex items-center gap-1 font-medium">
                                <i class="ph-bold ph-info"></i> Shipped orders cannot be cancelled. Use <strong>Returned</strong> if delivery failed.
                            </p>
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
                <button @click="handleStatusSelect(selectedStatus)" type="button" class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-transparent shadow-sm px-4 py-2 bg-brand-600 text-base font-medium text-white hover:bg-brand-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer transition-colors" :disabled="loading">
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
                <button @click="openConfirmModal = false; handleStatusSelect(nextStatus);" type="button" class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-transparent shadow-sm px-4 py-2 bg-brand-600 text-base font-medium text-white hover:bg-brand-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer transition-colors" :disabled="loading">
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

<!-- Customer Delivery Record Refresh Confirmation Modal with 1-Digit Math Captcha -->
<div x-show="showFraudCaptchaModal" 
     style="display: none;" 
     class="fixed inset-0 z-[120] overflow-y-auto" 
     aria-labelledby="fraud-modal-title" 
     role="dialog" 
     aria-modal="true">
    <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
        <!-- Backdrop -->
        <div x-show="showFraudCaptchaModal" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0" 
             x-transition:enter-end="opacity-100" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100" 
             x-transition:leave-end="opacity-0" 
             class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity cursor-pointer" 
             @click="showFraudCaptchaModal = false"></div>

        <!-- Modal Card -->
        <div x-show="showFraudCaptchaModal" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
             class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-gray-100 p-6 z-10">
            
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center text-2xl shrink-0 border border-brand-100">
                    <i class="ph-bold ph-shield-check"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900" id="fraud-modal-title">
                        Refresh Customer Delivery Record?
                    </h3>
                    <p class="text-xs text-gray-600 mt-1 leading-relaxed">
                        Steadfast API has strict per-merchant rate limits. Please verify to query a fresh customer delivery record live from Steadfast.
                    </p>
                </div>
            </div>

            <!-- 1-Digit Math Captcha Challenge Box -->
            <div class="mt-5 p-4 rounded-xl bg-gray-50 border border-gray-200 space-y-3">
                <div class="flex items-center justify-between">
                    <label for="fraudCaptchaInput" class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                        <i class="ph-bold ph-calculator text-brand-600"></i>
                        Security Verification
                    </label>
                    <button type="button" 
                            @click="refreshFraudCaptcha()" 
                            class="text-xs text-brand-600 hover:text-brand-800 font-medium inline-flex items-center gap-1 cursor-pointer">
                        <i class="ph ph-arrows-clockwise"></i> New Problem
                    </button>
                </div>

                <div class="flex items-center gap-3">
                    <div class="px-4 py-2 bg-white rounded-lg border border-gray-300 font-mono text-base font-bold text-gray-800 shadow-2xs tracking-wider select-none shrink-0">
                        <span x-text="fraudCaptchaDigit1"></span> + <span x-text="fraudCaptchaDigit2"></span> = ?
                    </div>
                    <input type="number" 
                           id="fraudCaptchaInput" 
                           x-model="fraudCaptchaAnswer" 
                           @keydown.enter.prevent="confirmFraudRefresh()" 
                           placeholder="Answer" 
                           class="w-full px-3 py-2 text-sm font-bold font-mono bg-white border border-gray-300 rounded-lg focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-gray-900">
                </div>

                <p x-show="fraudCaptchaError" x-text="fraudCaptchaError" class="text-xs text-red-600 font-semibold" style="display: none;"></p>
                <p class="text-[11px] text-gray-500">Solve this simple 1-digit calculation to confirm live refresh.</p>
            </div>

            <!-- Modal Actions -->
            <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                <button type="button" 
                        @click="showFraudCaptchaModal = false" 
                        class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-700 hover:bg-gray-100 border border-gray-200 transition-colors cursor-pointer">
                    Cancel
                </button>
                <button type="button" 
                        @click="confirmFraudRefresh()" 
                        class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 shadow-xs transition-colors inline-flex items-center gap-1.5 cursor-pointer">
                    <i class="ph-bold ph-arrows-clockwise"></i>
                    <span>Confirm & Refresh</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Large Image Preview Lightbox Modal -->
<div x-show="previewImageUrl" 
     style="display: none;" 
     class="fixed inset-0 z-[150] overflow-y-auto"
     aria-labelledby="image-preview-title" role="dialog" aria-modal="true"
     @keydown.escape.window="closeImagePreview()">
    <div class="flex items-center justify-center min-h-screen p-4 sm:p-6 text-center">
        <div x-show="previewImageUrl" 
             x-transition.opacity.duration.300ms 
             class="fixed inset-0 bg-gray-950/80 backdrop-blur-sm cursor-pointer" 
             @click="closeImagePreview()"></div>

        <div x-show="previewImageUrl" 
             x-transition.scale.duration.300ms 
             class="relative z-10 bg-white rounded-2xl overflow-hidden shadow-2xl max-w-3xl w-full p-4 sm:p-6 text-left border border-gray-100">
            <div class="flex items-center justify-between pb-3 mb-3 border-b border-gray-100">
                <div class="min-w-0 pr-4">
                    <h3 class="text-base font-bold text-gray-900 truncate" id="image-preview-title" x-text="previewImageTitle || 'Product Image'"></h3>
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

<!-- Custom SAJ BD Shipping Label Modal (Minimal, Clean & Direct) -->
<div x-show="showPrintLabelModal" 
     style="display: none;" 
     class="fixed inset-0 z-[140] overflow-y-auto"
     aria-labelledby="print-label-modal-title" 
     role="dialog" 
     aria-modal="true"
     @keydown.escape.window="closePrintLabelModal()">
    <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-6">
        <div x-show="showPrintLabelModal" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0" 
             x-transition:enter-end="opacity-100" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100" 
             x-transition:leave-end="opacity-0" 
             class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity cursor-pointer no-print" 
             @click="closePrintLabelModal()"></div>

        <div x-show="showPrintLabelModal" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
             class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-xl border border-gray-100 z-10">
            
            <div class="bg-gray-50/90 px-6 py-4 border-b border-gray-100 flex items-center justify-between no-print">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center text-xl font-bold border border-brand-100">
                        <i class="ph-bold ph-printer"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900" id="print-label-modal-title">
                            Shipping Label (SAJ BD)
                        </h3>
                        <p class="text-xs text-gray-500">Steadfast Courier Delivery Sticker</p>
                    </div>
                </div>
                <button type="button" 
                        @click="closePrintLabelModal()" 
                        class="w-8 h-8 rounded-lg bg-white border border-gray-200 hover:bg-gray-100 text-gray-500 flex items-center justify-center transition-colors cursor-pointer"
                        title="Close">
                    <i class="ph-bold ph-x text-base"></i>
                </button>
            </div>

            <div class="p-6 bg-gray-100/70 max-h-[72vh] overflow-y-auto flex justify-center">
                <div id="printable-shipping-label" class="bg-white border-2 border-black rounded-lg p-5 w-full max-w-[440px] shadow-sm text-gray-900 font-sans print:shadow-none print:m-0 print:p-4 print:border-black print:rounded-none">
                    
                    <div class="flex justify-between items-start pb-3 border-b-2 border-black">
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-2xl font-black tracking-tight text-black uppercase">SAJ</span>
                                <span class="text-xs font-bold text-gray-700 uppercase tracking-widest pl-1 border-l border-gray-400">BD</span>
                            </div>
                            <p class="text-[11px] font-bold text-gray-700 tracking-wide mt-0.5">JEWELRY & COSMETICS</p>
                            <p class="text-[10px] text-gray-700 mt-0.5">Hotline: <span class="font-bold text-black font-mono">{{ \App\Models\Setting::get('site_phone') ?: '01700-000000' }}</span></p>
                            <p class="text-[9px] text-gray-500">{{ config('app.url', 'saj.com.bd') }}</p>
                        </div>
                        <div class="text-right">
                            <div class="inline-block bg-black text-white px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider mb-1">
                                STEADFAST COURIER
                            </div>
                            <div class="text-[10px] text-gray-600 font-medium">Consignment ID:</div>
                            <div class="font-mono font-black text-base text-black tracking-wider" x-text="'#' + (steadfastCid || '{{ $order->steadfast_consignment_id ?: 'PENDING' }}')"></div>
                            <template x-if="steadfastTracking || '{{ $order->steadfast_tracking_code }}'">
                                <div class="text-[10px] font-mono font-semibold text-gray-700">
                                    TRK: <span x-text="steadfastTracking || '{{ $order->steadfast_tracking_code }}'"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="py-2.5 border-b-2 border-black flex flex-col items-center justify-center">
                        <svg class="w-64 h-12" viewBox="0 0 240 45" xmlns="http://www.w3.org/2000/svg">
                            <rect x="0" y="0" width="3" height="35" fill="#000"/>
                            <rect x="5" y="0" width="2" height="35" fill="#000"/>
                            <rect x="9" y="0" width="4" height="35" fill="#000"/>
                            <rect x="16" y="0" width="1" height="35" fill="#000"/>
                            <rect x="20" y="0" width="3" height="35" fill="#000"/>
                            <rect x="25" y="0" width="5" height="35" fill="#000"/>
                            <rect x="33" y="0" width="2" height="35" fill="#000"/>
                            <rect x="38" y="0" width="4" height="35" fill="#000"/>
                            <rect x="45" y="0" width="1" height="35" fill="#000"/>
                            <rect x="48" y="0" width="3" height="35" fill="#000"/>
                            <rect x="54" y="0" width="2" height="35" fill="#000"/>
                            <rect x="59" y="0" width="5" height="35" fill="#000"/>
                            <rect x="67" y="0" width="2" height="35" fill="#000"/>
                            <rect x="72" y="0" width="4" height="35" fill="#000"/>
                            <rect x="79" y="0" width="1" height="35" fill="#000"/>
                            <rect x="83" y="0" width="3" height="35" fill="#000"/>
                            <rect x="89" y="0" width="5" height="35" fill="#000"/>
                            <rect x="97" y="0" width="2" height="35" fill="#000"/>
                            <rect x="102" y="0" width="3" height="35" fill="#000"/>
                            <rect x="108" y="0" width="1" height="35" fill="#000"/>
                            <rect x="112" y="0" width="4" height="35" fill="#000"/>
                            <rect x="119" y="0" width="3" height="35" fill="#000"/>
                            <rect x="125" y="0" width="5" height="35" fill="#000"/>
                            <rect x="133" y="0" width="2" height="35" fill="#000"/>
                            <rect x="138" y="0" width="4" height="35" fill="#000"/>
                            <rect x="145" y="0" width="1" height="35" fill="#000"/>
                            <rect x="149" y="0" width="3" height="35" fill="#000"/>
                            <rect x="155" y="0" width="2" height="35" fill="#000"/>
                            <rect x="160" y="0" width="5" height="35" fill="#000"/>
                            <rect x="168" y="0" width="2" height="35" fill="#000"/>
                            <rect x="173" y="0" width="4" height="35" fill="#000"/>
                            <rect x="180" y="0" width="1" height="35" fill="#000"/>
                            <rect x="184" y="0" width="3" height="35" fill="#000"/>
                            <rect x="190" y="0" width="4" height="35" fill="#000"/>
                            <rect x="197" y="0" width="2" height="35" fill="#000"/>
                            <rect x="202" y="0" width="3" height="35" fill="#000"/>
                            <rect x="208" y="0" width="5" height="35" fill="#000"/>
                            <rect x="216" y="0" width="2" height="35" fill="#000"/>
                            <rect x="221" y="0" width="4" height="35" fill="#000"/>
                            <rect x="228" y="0" width="2" height="35" fill="#000"/>
                            <rect x="233" y="0" width="4" height="35" fill="#000"/>
                        </svg>
                        <span class="font-mono text-xs font-bold text-black tracking-widest mt-1" x-text="(steadfastCid || '{{ $order->steadfast_consignment_id }}') ? ('CID: #' + (steadfastCid || '{{ $order->steadfast_consignment_id }}')) : 'ORDER: #{{ $order->order_id }}'"></span>
                    </div>

                    <div class="grid grid-cols-2 divide-x-2 divide-black border-b-2 border-black">
                        <div class="p-2.5">
                            <div class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Order Reference:</div>
                            <div class="font-mono font-bold text-base text-black">#{{ $order->order_id }}</div>
                            <div class="text-[10px] text-gray-600 mt-0.5">Date: {{ $order->created_at ? $order->created_at->format('d M Y') : 'N/A' }}</div>
                        </div>
                        <div class="p-2.5 flex flex-col justify-center items-center text-center">
                            @if($order->payment_status === 'paid')
                                <div class="w-full bg-black text-white py-1.5 px-2 font-black text-xs uppercase tracking-wider rounded-xs">
                                    PAID - NO COD
                                </div>
                                <div class="text-[9px] font-bold text-gray-700 mt-0.5">Do NOT collect cash</div>
                            @else
                                <div class="text-[10px] font-black uppercase text-gray-700 tracking-wider">CASH ON DELIVERY (COD)</div>
                                <div class="text-xl font-black text-black tracking-tight mt-0.5 font-mono">
                                    ৳{{ number_format($order->total, 0) }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="p-3 bg-gray-50/50">
                        <div class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">
                            DELIVER TO (RECIPIENT):
                        </div>
                        <div class="text-base font-black text-black leading-tight">
                            {{ $shippingInfo['name'] ?? optional($order->user)->name ?? 'Customer' }}
                        </div>
                        <div class="text-lg font-black text-black font-mono tracking-wide my-1 flex items-center gap-1.5">
                            <span class="text-base">☎</span> {{ $shippingInfo['phone'] ?? optional($order->user)->phone ?? 'N/A' }}
                        </div>
                        <div class="text-xs font-semibold text-gray-900 leading-snug mt-1">
                            {{ $shippingInfo['address'] ?? '' }}
                            @if(!empty($shippingInfo['city']))
                                , {{ $shippingInfo['city'] }}
                            @endif
                            @if(!empty($shippingInfo['zone']))
                                , {{ $shippingInfo['zone'] }}
                            @endif
                        </div>
                        @if(!empty($order->note))
                            <div class="mt-2 p-1.5 bg-yellow-50 border border-yellow-300 rounded text-[11px] text-gray-800">
                                <span class="font-bold">Note:</span> {{ $order->note }}
                            </div>
                        @endif
                    </div>

                    <div class="pt-2 border-t border-gray-200 text-center text-[10px] font-bold text-gray-800 tracking-wider">
                        ✨ THANK YOU FOR SHOPPING WITH SAJ BD
                    </div>

                </div>
            </div>

            <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex flex-wrap items-center justify-between gap-3 no-print">
                <div class="text-xs text-gray-500 flex items-center gap-1.5">
                    <i class="ph ph-info text-base text-gray-400"></i>
                    <span>Optimized for standard 4" x 6" thermal label printers and standard A4 paper.</span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" 
                            @click="closePrintLabelModal()" 
                            class="px-4 py-2 bg-white border border-gray-200 hover:bg-gray-100 text-gray-700 text-xs font-semibold rounded-xl transition-colors cursor-pointer">
                        Close
                    </button>
                    <button type="button" 
                            @click="printShippingLabel()" 
                            class="px-5 py-2 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold rounded-xl shadow-xs transition-colors inline-flex items-center gap-1.5 cursor-pointer">
                        <i class="ph-bold ph-printer text-base"></i>
                        <span>Print Label</span>
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Cancel / Return & Restock Modal -->
<div x-show="showRestockModal" 
     style="display: none;" 
     class="fixed inset-0 z-[130] overflow-y-auto" 
     aria-labelledby="restock-modal-title" 
     role="dialog" 
     aria-modal="true"
     @keydown.escape.window="if (!submittingRestock) showRestockModal = false">
    <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-6">
        <div x-show="showRestockModal" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0" 
             x-transition:enter-end="opacity-100" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100" 
             x-transition:leave-end="opacity-0" 
             class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity cursor-pointer" 
             @click="if (!submittingRestock) showRestockModal = false"></div>

        <div x-show="showRestockModal" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
             class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-xl border border-gray-100 z-10">
            
            <div class="bg-gray-50/90 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl font-bold border border-amber-100">
                        <i class="ph-bold" :class="restockTargetStatus === 'returned' ? 'ph-arrow-u-up-left' : 'ph-x-circle'"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900" id="restock-modal-title" x-text="restockTargetStatus === 'returned' ? 'Order Return & Restock Items' : 'Order Cancel & Restock Items'">
                        </h3>
                        <p class="text-xs text-gray-500">Choose which items and quantities to add back to stock inventory.</p>
                    </div>
                </div>
                <button type="button" 
                        @click="if (!submittingRestock) showRestockModal = false" 
                        class="w-8 h-8 rounded-lg bg-white border border-gray-200 hover:bg-gray-100 text-gray-500 flex items-center justify-center transition-colors cursor-pointer"
                        title="Close">
                    <i class="ph-bold ph-x text-base"></i>
                </button>
            </div>

            <div class="p-6 space-y-4 max-h-[65vh] overflow-y-auto">
                <div class="p-3 bg-blue-50/70 border border-blue-200 rounded-xl text-xs text-blue-900 flex items-start gap-2">
                    <i class="ph-fill ph-info text-blue-600 text-base shrink-0 mt-0.5"></i>
                    <div>
                        <p class="font-bold text-blue-950">Inventory Restock Control</p>
                        <p class="text-blue-800/90 text-[11px] mt-0.5">
                            This order previously deducted stock. You can restore all items or choose specific items/quantities for partial returns.
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-2 pt-1">
                    <span class="text-xs font-bold text-gray-700 uppercase tracking-wider">Ordered Items:</span>
                    <div class="flex items-center gap-2">
                        <button type="button" 
                                @click="selectFullRestock()" 
                                class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition-colors cursor-pointer">
                            Full Restock (All)
                        </button>
                        <button type="button" 
                                @click="selectNoRestock()" 
                                class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-gray-100 text-gray-600 border border-gray-200 hover:bg-gray-200 transition-colors cursor-pointer">
                            No Restock
                        </button>
                    </div>
                </div>

                <div class="divide-y divide-gray-100 border border-gray-200 rounded-xl overflow-hidden">
                    <template x-for="(item, idx) in restockItems" :key="item.id">
                        <div class="p-3.5 flex items-center justify-between gap-3 hover:bg-gray-50/60 transition-colors" :class="item.selected ? 'bg-white' : 'bg-gray-50/40 opacity-75'">
                            <div class="flex items-center gap-3 min-w-0">
                                <input type="checkbox" 
                                       :id="'rstk_item_' + item.id" 
                                       x-model="item.selected" 
                                       @change="if (item.selected && item.return_qty === 0) item.return_qty = item.quantity; if (!item.selected) item.return_qty = 0;"
                                       class="rounded border-gray-300 text-brand-600 focus:ring-brand-500 w-4 h-4 cursor-pointer">
                                <label :for="'rstk_item_' + item.id" class="cursor-pointer min-w-0">
                                    <p class="text-xs font-bold text-gray-900 truncate" x-text="item.name"></p>
                                    <div class="flex flex-wrap items-center gap-2 text-[11px] text-gray-500 mt-0.5">
                                        <template x-if="item.sku">
                                            <span class="font-mono bg-gray-100 px-1 py-0.5 rounded text-[10px] text-gray-600" x-text="'SKU: ' + item.sku"></span>
                                        </template>
                                        <span x-text="'Ordered: ' + item.quantity + ' pcs'"></span>
                                    </div>
                                </label>
                            </div>

                            <div class="flex items-center gap-1.5 shrink-0" x-show="item.selected">
                                <label class="text-[11px] text-gray-500 font-medium">Restock Qty:</label>
                                <input type="number" 
                                       x-model.number="item.return_qty" 
                                       :min="0" 
                                       :max="item.quantity" 
                                       class="w-16 h-8 text-center text-xs font-bold font-mono border border-gray-300 rounded-lg focus:ring-1 focus:ring-brand-500 focus:border-brand-500">
                            </div>
                        </div>
                    </template>
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-gray-700">Reason / Notes (Optional)</label>
                    <input type="text" 
                           x-model="restockReason" 
                           placeholder="e.g. Customer cancelled on phone, parcel returned by courier..." 
                           class="w-full text-xs px-3 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-brand-500 focus:border-brand-500">
                </div>
            </div>

            <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3">
                <button type="button" 
                        @click="if (!submittingRestock) showRestockModal = false" 
                        :disabled="submittingRestock"
                        class="px-4 py-2 bg-white border border-gray-200 hover:bg-gray-100 text-gray-700 text-xs font-semibold rounded-xl transition-colors cursor-pointer disabled:opacity-60">
                    Cancel
                </button>
                <button type="button" 
                        @click="confirmRestockSubmit()" 
                        :disabled="submittingRestock"
                        class="px-5 py-2 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold rounded-xl shadow-xs transition-colors inline-flex items-center gap-1.5 cursor-pointer disabled:opacity-60">
                    <i x-show="submittingRestock" class="ph ph-spinner animate-spin"></i>
                    <i x-show="!submittingRestock" class="ph-bold ph-check"></i>
                    <span x-text="submittingRestock ? 'Processing...' : 'Confirm Status & Restock'"></span>
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
