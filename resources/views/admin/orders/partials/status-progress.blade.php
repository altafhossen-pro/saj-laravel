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
