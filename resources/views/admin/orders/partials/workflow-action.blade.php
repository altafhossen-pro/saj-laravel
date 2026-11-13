<!-- Order Workflow Action -->
<template x-if="nextStatus">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-sm font-bold text-gray-900 mb-1">Order Workflow</h3>
        <p class="text-xs text-gray-500 mb-4">Advance order to the next workflow stage.</p>
        <button type="button" @click="openConfirmModal = true" :disabled="loading" class="w-full py-3.5 bg-brand-600 hover:bg-brand-700 text-white font-medium rounded-xl shadow-sm transition-colors cursor-pointer flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">
            <i x-show="loading" class="ph ph-spinner animate-spin text-lg"></i>
            <span x-text="loading ? 'Updating...' : 'Proceed to ' + getStatusLabel(nextStatus)"></span>
            <i x-show="!loading" class="ph ph-arrow-right"></i>
        </button>
    </div>
</template>
