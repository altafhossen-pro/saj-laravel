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

        <button type="button"
                @click="openPrintLabelModal()"
                class="w-full mt-2 py-2 px-3 bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs rounded-xl transition-colors inline-flex items-center justify-center gap-2 shadow-xs cursor-pointer">
            <i class="ph-bold ph-printer text-base"></i>
            <span>Print Shipping Label</span>
        </button>
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

    <!-- Customer Delivery & Fraud Record Widget (Inside Steadfast Courier Card as in original) -->
    @if($orderPhone)
    <div class="mt-4 pt-4 border-t border-gray-100">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-gray-700 flex items-center gap-1.5">
                <i class="ph-bold ph-shield-check text-brand-600"></i> Customer Delivery Record
            </span>
            <button type="button" 
                    @click="openFraudRefreshModal()" 
                    :disabled="checkingFraud" 
                    class="text-xs text-brand-600 hover:text-brand-800 font-medium cursor-pointer inline-flex items-center gap-1 disabled:opacity-50"
                    title="Refresh customer record from Steadfast">
                <i class="ph ph-arrows-clockwise text-xs" :class="checkingFraud ? 'animate-spin' : ''"></i>
                <span x-text="checkingFraud ? 'Checking...' : (fraudCheckData ? 'Refresh' : 'Check Record')"></span>
            </button>
        </div>

        <div x-show="fraudCheckData" x-transition class="p-3 bg-gradient-to-br from-gray-50 to-white rounded-xl border border-gray-200 shadow-2xs text-xs space-y-2.5 mt-2">
            <!-- 100% Dual Progress Bar (Delivery Success vs Cancellation) -->
            <div>
                <div class="flex justify-between items-baseline mb-1">
                    <span class="text-[11px] font-semibold text-gray-700">Delivery Ratio</span>
                    <span class="text-xs font-bold font-mono">
                        <span class="text-emerald-600" x-text="(fraudCheckData?.delivery_ratio ?? 0) + '%'"></span>
                        <span class="text-gray-300 mx-0.5">/</span>
                        <span class="text-rose-600" x-text="(fraudCheckData?.cancellation_ratio ?? 0) + '%'"></span>
                    </span>
                </div>
                <!-- Dual Color Ratio Bar -->
                <div class="w-full h-2.5 bg-gray-200 rounded-full overflow-hidden flex shadow-inner">
                    <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 h-full transition-all duration-500 rounded-l-full"
                         :style="'width: ' + Math.min(100, Math.max(0, fraudCheckData?.delivery_ratio ?? 0)) + '%'"
                         :title="'Success: ' + (fraudCheckData?.delivery_ratio ?? 0) + '%'"></div>
                    <div class="bg-gradient-to-r from-rose-500 to-rose-600 h-full transition-all duration-500 rounded-r-full"
                         :style="'width: ' + Math.min(100, Math.max(0, fraudCheckData?.cancellation_ratio ?? 0)) + '%'"
                         :title="'Cancelled: ' + (fraudCheckData?.cancellation_ratio ?? 0) + '%'"></div>
                </div>
                <div class="flex justify-between text-[10px] text-gray-400 mt-1">
                    <span class="flex items-center gap-1 font-medium text-emerald-700">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span>
                        <span x-text="'Success: ' + (fraudCheckData?.delivery_ratio ?? 'N/A') + '%'"></span>
                        <template x-if="fraudCheckData?.delivered_count !== null && fraudCheckData?.delivered_count !== undefined">
                            <span class="text-gray-500 font-mono" x-text="'(' + fraudCheckData.delivered_count + ')'"></span>
                        </template>
                    </span>
                    <span class="flex items-center gap-1 font-medium text-rose-700">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 inline-block"></span>
                        <span x-text="'Cancel: ' + (fraudCheckData?.cancellation_ratio ?? 'N/A') + '%'"></span>
                        <template x-if="fraudCheckData?.cancelled_count !== null && fraudCheckData?.cancelled_count !== undefined">
                            <span class="text-gray-500 font-mono" x-text="'(' + fraudCheckData.cancelled_count + ')'"></span>
                        </template>
                    </span>
                </div>
            </div>

            <!-- Focused Metric Highlights: Parcel Volume & Merchant Reports -->
            <div class="grid grid-cols-2 gap-2 pt-1 border-t border-gray-100">
                <!-- Parcel Volume Highlight -->
                <div class="p-2 bg-indigo-50/70 border border-indigo-100 rounded-lg flex flex-col">
                    <span class="text-[10px] uppercase font-semibold text-indigo-700 tracking-wider">Parcel Volume</span>
                    <div class="flex items-baseline gap-1 mt-0.5">
                        <span class="text-base font-extrabold text-indigo-900 font-mono"
                              x-text="fraudCheckData?.volume_range ? (fraudCheckData.volume_range + ' Parcels') : (fraudCheckData?.volume_band || 'Unknown')"></span>
                    </div>
                    <span class="text-[10px] text-indigo-600/90 font-medium capitalize mt-0.5"
                          x-show="fraudCheckData?.volume_range && fraudCheckData?.volume_band"
                          x-text="'Band: ' + fraudCheckData.volume_band"></span>
                </div>

                <!-- Merchant Reports Alert Badge -->
                <div class="p-2 rounded-lg flex flex-col border transition-colors"
                     :class="(fraudCheckData?.total_reports > 0) ? 'bg-amber-50/80 border-amber-200 text-amber-900' : 'bg-gray-50 border-gray-200/80 text-gray-700'">
                    <span class="text-[10px] uppercase font-semibold tracking-wider"
                          :class="(fraudCheckData?.total_reports > 0) ? 'text-amber-700' : 'text-gray-500'">Merchant Reports</span>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <i class="ph text-base"
                           :class="(fraudCheckData?.total_reports > 0) ? 'ph-warning-circle text-amber-600 font-bold' : 'ph-check-circle text-emerald-600'"></i>
                        <span class="text-base font-extrabold font-mono"
                              :class="(fraudCheckData?.total_reports > 0) ? 'text-amber-800' : 'text-gray-700'"
                              x-text="fraudCheckData?.total_reports || 0"></span>
                    </div>
                    <span class="text-[10px] font-medium mt-0.5"
                          :class="(fraudCheckData?.total_reports > 0) ? 'text-amber-700' : 'text-gray-400'"
                          x-text="(fraudCheckData?.total_reports > 0) ? 'Reported by seller' : 'Clean record'"></span>
                </div>
            </div>

            <!-- Timestamp & Source Footer -->
            <div class="flex justify-between items-center pt-1.5 text-[10px] text-gray-400 font-mono border-t border-gray-200/60">
                <span class="inline-flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full"
                          :class="fraudCheckData?.is_cached ? 'bg-gray-400' : 'bg-emerald-500 animate-pulse'"></span>
                    <span x-text="fraudCheckData?.is_cached ? '24h DB Cache' : 'Live Steadfast Data'"></span>
                </span>
                <span x-show="fraudCheckData?.checked_at" x-text="fraudCheckData?.checked_at"></span>
            </div>
        </div>

        <div x-show="!fraudCheckData && !checkingFraud && isOrderOlderThan10Days" class="p-2.5 bg-amber-50/60 rounded-xl border border-amber-200/70 text-[11px] text-amber-800 mt-2">
            <span class="font-bold">Old Order (&gt; 10 days):</span> Click Refresh to query live record.
        </div>
    </div>
    @endif
</div>
