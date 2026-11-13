<!-- Quick Stats Cards Component -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-xs flex items-center justify-between">
        <div>
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Articles</span>
            <p class="text-2xl font-black text-gray-900 mt-0.5">{{ number_format($totalCount) }}</p>
        </div>
        <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center text-lg">
            <i class="ph-bold ph-article"></i>
        </div>
    </div>

    <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-xs flex items-center justify-between">
        <div>
            <span class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Published Live</span>
            <p class="text-2xl font-black text-gray-900 mt-0.5">{{ number_format($publishedCount) }}</p>
        </div>
        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
            <i class="ph-bold ph-check-circle"></i>
        </div>
    </div>

    <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-xs flex items-center justify-between">
        <div>
            <span class="text-xs font-semibold text-amber-600 uppercase tracking-wider">Drafts</span>
            <p class="text-2xl font-black text-gray-900 mt-0.5">{{ number_format($draftCount) }}</p>
        </div>
        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
            <i class="ph-bold ph-file-dashed"></i>
        </div>
    </div>
</div>
