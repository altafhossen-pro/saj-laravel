<!-- Order Status Progress -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-lg font-bold text-gray-900">Order Status</h2>
        <template x-if="currentStatus === 'returned'">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                <i class="ph-bold ph-arrow-u-up-left"></i>
                Order Returned
            </span>
        </template>
        <template x-if="isPartialDelivery && currentStatus !== 'returned'">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                <i class="ph-bold ph-truck"></i>
                Partial Delivered
            </span>
        </template>
        <template x-if="currentStatus === 'cancelled'">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                <i class="ph-bold ph-x"></i>
                Order Cancelled
            </span>
        </template>
    </div>

    <div class="flex items-start overflow-x-auto pb-2">
        <template x-for="(step, i) in steps" :key="step">
            <div class="contents">
                <div class="flex flex-col items-center text-center w-20 shrink-0">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold border-2 transition-all duration-300"
                         :class="{
                             'bg-amber-600 border-amber-600 text-white ring-4 ring-amber-100': step === 'returned' && step === currentStatus,
                             'bg-rose-600 border-rose-600 text-white ring-4 ring-rose-100': step === 'cancelled' && step === currentStatus,
                             'bg-brand-600 border-brand-600 text-white ring-4 ring-brand-100': step === currentStatus && step !== 'returned' && step !== 'cancelled',
                             'bg-brand-600 border-brand-600 text-white': isDone(step),
                             'bg-white border-gray-200 text-gray-300': !isDone(step) && step !== currentStatus
                         }">
                        <template x-if="step === 'returned'">
                            <i class="ph-bold ph-arrow-u-up-left"></i>
                        </template>
                        <template x-if="step === 'cancelled'">
                            <i class="ph-bold ph-x"></i>
                        </template>
                        <template x-if="step !== 'returned' && step !== 'cancelled' && (isDone(step) || step === currentStatus)">
                            <i class="ph-bold ph-check"></i>
                        </template>
                        <template x-if="!isDone(step) && step !== currentStatus">
                            <span class="w-2 h-2 rounded-full bg-gray-200"></span>
                        </template>
                    </div>
                    <span class="mt-2 text-xs" 
                          :class="{
                              'font-bold text-amber-700': step === 'returned' && step === currentStatus,
                              'font-bold text-rose-700': step === 'cancelled' && step === currentStatus,
                              'font-bold text-brand-700': step === currentStatus && step !== 'returned' && step !== 'cancelled',
                              'font-medium text-gray-700': isDone(step),
                              'text-gray-400': !isDone(step) && step !== currentStatus
                          }" 
                          x-text="getStatusLabel(step)"></span>
                </div>
                <div x-show="i < steps.length - 1" class="flex-1 h-0.5 mt-[17px] transition-colors duration-300 min-w-[20px]" 
                     :class="{
                         'bg-amber-500': steps[i+1] === 'returned' && currentStatus === 'returned',
                         'bg-rose-500': steps[i+1] === 'cancelled' && currentStatus === 'cancelled',
                         'bg-brand-600': isDone(steps[i+1]) || steps[i+1] === currentStatus,
                         'bg-gray-200': !isDone(steps[i+1]) && steps[i+1] !== currentStatus
                     }"></div>
            </div>
        </template>
    </div>

    <template x-if="isPartialDelivery && currentStatus !== 'returned'">
        <div class="mt-4 flex items-center gap-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl px-4 py-2.5 text-xs">
            <i class="ph-bold ph-package text-lg text-amber-600 shrink-0"></i>
            <div>
                <span class="font-bold">Partial Delivered</span>
                <span class="text-amber-700/90 ml-1">• Customer accepted and paid partial amount (<span class="font-bold" x-text="'৳' + collectedAmount"></span>), while returned items were restored to stock.</span>
            </div>
        </div>
    </template>
    <template x-if="currentStatus === 'returned'">
        <div class="mt-4 flex items-center gap-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl px-4 py-2.5 text-xs">
            <i class="ph-bold ph-arrow-u-up-left text-lg text-amber-600 shrink-0"></i>
            <div>
                <span class="font-bold">Order Returned</span>
                <span class="text-amber-700/90 ml-1">• Items eligible have been restocked to inventory. Delivery charges counted as return loss.</span>
            </div>
        </div>
    </template>
    <template x-if="currentStatus === 'cancelled'">
        <div class="mt-4 flex items-center gap-3 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl px-4 py-2.5 text-xs">
            <i class="ph-bold ph-x-circle text-lg text-rose-600 shrink-0"></i>
            <div>
                <span class="font-bold">Order Cancelled</span>
                <span class="text-rose-700/90 ml-1">• This order was cancelled. No further courier or fulfillment steps will be processed.</span>
            </div>
        </div>
    </template>
</div>
