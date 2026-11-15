<!-- Courier Tracking Log Component -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-pink-50 text-pink-600 flex items-center justify-center font-bold text-base border border-pink-100">
                <i class="ph-bold ph-truck"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-base font-bold text-gray-900 leading-tight">Courier Tracking Log</h2>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-pink-100 text-pink-700 uppercase tracking-wider">Steadfast</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-gray-100 text-gray-700" x-text="trackingLogs.length + ' Events'"></span>
                </div>
                <p class="text-[11px] text-gray-500">Real-time parcel journey, rider updates & financial settlement.</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <template x-if="steadfastTracking || '{{ $order->steadfast_tracking_code }}'">
                <a :href="'https://steadfast.com.bd/t/' + (steadfastTracking || '{{ $order->steadfast_tracking_code }}')" 
                   target="_blank" 
                   rel="noopener"
                   class="px-2.5 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-lg transition-colors inline-flex items-center gap-1 cursor-pointer">
                    <i class="ph ph-arrow-square-out"></i>
                    <span>Steadfast</span>
                </a>
            </template>
            <button type="button" 
                    @click="syncTracking()" 
                    :disabled="syncingTracking || !isSteadfastBooked"
                    class="px-3 py-1.5 bg-pink-600 hover:bg-pink-700 text-white text-xs font-semibold rounded-lg shadow-xs transition-colors inline-flex items-center gap-1.5 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="ph ph-arrows-clockwise text-xs" :class="syncingTracking ? 'animate-spin' : ''"></i>
                <span x-text="syncingTracking ? 'Syncing...' : 'Sync Tracking'"></span>
            </button>
        </div>
    </div>

    <!-- Financial Settlement Box (When values are available) -->
    <template x-if="isSteadfastBooked && (collectedAmount > 0 || deliveryCharge > 0 || receivableAmount > 0)">
        <div class="mb-5 p-4 rounded-xl bg-gray-50 border border-gray-200">
            <div class="flex items-center justify-between mb-3 pb-2 border-b border-gray-200">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider">Financial Breakdown</h3>
                </div>
                <span class="text-[11px] font-mono text-gray-500" x-text="steadfastCid ? 'CID: #' + steadfastCid : ''"></span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                <div class="p-2.5 bg-white rounded-lg border border-gray-200/80">
                    <p class="text-gray-500 text-[11px] font-medium">Cash Collected</p>
                    <p class="text-sm font-bold text-gray-900 mt-0.5">৳<span x-text="Number(collectedAmount).toLocaleString('en-US', {minimumFractionDigits: 2})"></span></p>
                </div>
                <div class="p-2.5 bg-white rounded-lg border border-gray-200/80">
                    <p class="text-gray-500 text-[11px] font-medium">Delivery Charge</p>
                    <p class="text-sm font-bold text-red-600 mt-0.5">−৳<span x-text="Number(deliveryCharge).toLocaleString('en-US', {minimumFractionDigits: 2})"></span></p>
                </div>
                <div class="p-2.5 bg-white rounded-lg border border-gray-200/80">
                    <p class="text-gray-500 text-[11px] font-medium">COD Fee (1%)</p>
                    <p class="text-sm font-bold text-amber-600 mt-0.5">−৳<span x-text="Number(codCharge).toLocaleString('en-US', {minimumFractionDigits: 2})"></span></p>
                </div>
                <div class="p-2.5 bg-emerald-50 rounded-lg border border-emerald-200">
                    <p class="text-emerald-800 text-[11px] font-bold">You Receive</p>
                    <p class="text-sm font-black text-emerald-700 mt-0.5">৳<span x-text="Number(receivableAmount).toLocaleString('en-US', {minimumFractionDigits: 2})"></span></p>
                </div>
            </div>
        </div>
    </template>

    <!-- Timeline Container -->
    <div class="pt-2">
        <!-- Empty State -->
        <template x-if="trackingLogs.length === 0">
            <div class="text-center py-10 px-4">
                <div class="w-12 h-12 rounded-2xl bg-pink-50 text-pink-500 mx-auto flex items-center justify-center text-xl mb-2.5 border border-pink-100">
                    <i class="ph ph-map-pin-line"></i>
                </div>
                <h4 class="text-sm font-bold text-gray-800">No courier tracking events yet</h4>
                <p class="text-xs text-gray-500 max-w-sm mx-auto mt-1 leading-relaxed">
                    Tracking events from Steadfast will appear here automatically via webhook callback, or click "Sync Tracking" above to check live.
                </p>
            </div>
        </template>

        <!-- Timeline List (3-Column Layout: Date/Time -> Vertical Line & Node Dot -> Event Card) -->
        <template x-if="trackingLogs.length > 0">
            <div class="space-y-0 relative">
                <template x-for="(log, index) in trackingLogs" :key="log.id || index">
                    <div class="flex items-start group">
                        <!-- Left: Date & Time in BDT -->
                        <div class="w-28 sm:w-32 text-right shrink-0 pr-4 sm:pr-5 pt-1">
                            <div class="font-bold text-gray-900 text-xs sm:text-sm tracking-tight" x-text="log.formatted_date || 'Recent'"></div>
                            <div class="text-[11px] sm:text-xs font-semibold text-pink-600 flex items-center justify-end gap-1 mt-0.5">
                                <i class="ph ph-clock text-xs"></i>
                                <span x-text="log.formatted_time || ''"></span>
                            </div>
                            <div class="text-[10px] font-bold text-gray-400 tracking-wider uppercase mt-0.5" x-text="log.timezone || 'BDT'"></div>
                        </div>

                        <!-- Center: Line & Node Dot -->
                        <div class="relative flex flex-col items-center self-stretch shrink-0">
                            <!-- Dot -->
                            <div class="relative z-10 w-3.5 h-3.5 rounded-full bg-pink-500 ring-4 ring-pink-100 shrink-0 my-1 transition-transform group-hover:scale-110"></div>
                            <!-- Vertical Connecting Line -->
                            <div class="w-0.5 bg-pink-200 grow -mt-1" x-show="index < trackingLogs.length - 1"></div>
                        </div>

                        <!-- Right: Event Description Box -->
                        <div class="flex-1 pl-4 sm:pl-5 pb-5">
                            <div class="bg-slate-50/80 group-hover:bg-white border border-slate-200/80 group-hover:border-slate-300 rounded-2xl p-4 shadow-xs transition-all duration-200">
                                <div class="text-sm font-semibold text-gray-800 leading-relaxed" x-text="log.note || ('Status: ' + (log.status || 'In Review'))"></div>
                                
                                <div class="mt-2.5 flex flex-wrap items-center gap-2 pt-2 border-t border-slate-200/50" x-show="(log.status && log.status !== 'undefined') || log.receivable_amount > 0 || log.source || log.dispatched_by">
                                    <template x-if="log.status && log.status !== 'undefined'">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-semibold border"
                                              :class="courierStatusBadgeClass(log.status)"
                                              x-text="getStatusLabel(log.status)"></span>
                                    </template>
                                    <template x-if="log.receivable_amount > 0">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200" x-text="'• Payout: ৳' + Number(log.receivable_amount).toFixed(2)"></span>
                                    </template>
                                    <template x-if="log.source">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium text-gray-400" x-text="'via ' + log.source"></span>
                                    </template>
                                    <template x-if="log.dispatched_by">
                                        <span class="inline-flex items-center gap-1 text-[11px] text-gray-500">
                                            <i class="ph ph-user text-xs"></i>
                                            <span x-text="log.dispatched_by"></span>
                                        </span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </div>
</div>
