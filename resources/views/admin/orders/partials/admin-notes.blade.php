<!-- Admin Notes Component -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center font-bold text-base border border-brand-100">
                <i class="ph-bold ph-note-pencil"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-base font-bold text-gray-900 leading-tight">Admin Notes</h2>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-gray-100 text-gray-700" x-text="adminNotes.length + ' Notes'"></span>
                </div>
                <p class="text-[11px] text-gray-500">Internal notes & updates for this order</p>
            </div>
        </div>
        <button type="button" 
                @click="showAddNote = !showAddNote" 
                class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-lg transition-colors inline-flex items-center gap-1 cursor-pointer">
            <i class="ph ph-plus" x-show="!showAddNote"></i>
            <i class="ph ph-x" x-show="showAddNote" style="display: none;"></i>
            <span x-text="showAddNote ? 'Cancel' : 'Edit / Add Note'"></span>
        </button>
    </div>

    <!-- Add/Edit Note Form -->
    <div x-show="showAddNote" x-transition style="display: none;" class="mb-5 p-4 bg-gray-50 rounded-xl border border-gray-200">
        <label for="adminNoteTextarea" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Write Note:</label>
        <textarea id="adminNoteTextarea" 
                  x-model="newAdminNote" 
                  rows="3" 
                  placeholder="Type internal note here (e.g. customer requested delivery after 5pm, confirmed over phone)..."
                  class="w-full text-xs p-3 bg-white border border-gray-300 rounded-lg focus:border-brand-500 focus:ring-1 focus:ring-brand-500/20 text-gray-900 resize-y"></textarea>
        <div class="mt-3 flex justify-end gap-2">
            <button type="button" 
                    @click="showAddNote = false; newAdminNote = ''" 
                    class="px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-200 rounded-lg transition-colors cursor-pointer">
                Cancel
            </button>
            <button type="button" 
                    @click="saveAdminNote()" 
                    :disabled="savingAdminNote || !newAdminNote.trim()" 
                    class="px-4 py-1.5 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-lg shadow-xs transition-colors inline-flex items-center gap-1.5 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="ph ph-spinner animate-spin" x-show="savingAdminNote"></i>
                <i class="ph-bold ph-check" x-show="!savingAdminNote"></i>
                <span x-text="savingAdminNote ? 'Saving...' : 'Save Note'"></span>
            </button>
        </div>
    </div>

    <!-- Timeline Container -->
    <div class="pt-2">
        <!-- Empty State -->
        <template x-if="adminNotes.length === 0">
            <div class="text-center py-8 px-4 text-gray-400 text-xs">
                <div class="w-12 h-12 rounded-2xl bg-gray-50 text-gray-400 mx-auto flex items-center justify-center text-xl mb-2.5 border border-gray-100">
                    <i class="ph ph-notepad"></i>
                </div>
                <h4 class="text-sm font-bold text-gray-700">No admin notes added yet</h4>
                <p class="text-xs text-gray-400 max-w-sm mx-auto mt-1">
                    Click "Edit / Add Note" above to add customer communications or internal instructions.
                </p>
            </div>
        </template>

        <!-- Timeline List (3-Column Layout: Date/Time -> Vertical Line & Node Dot -> Note Card) -->
        <template x-if="adminNotes.length > 0">
            <div class="space-y-0 relative">
                <template x-for="(item, index) in adminNotes" :key="item.id || index">
                    <div class="flex items-start group">
                        <!-- Left: Date & Time in BDT -->
                        <div class="w-28 sm:w-32 text-right shrink-0 pr-4 sm:pr-5 pt-1">
                            <div class="font-bold text-gray-900 text-xs sm:text-sm tracking-tight" x-text="item.formatted_date || 'Recent'"></div>
                            <div class="text-[11px] sm:text-xs font-semibold text-brand-600 flex items-center justify-end gap-1 mt-0.5">
                                <i class="ph ph-clock text-xs"></i>
                                <span x-text="item.formatted_time || ''"></span>
                            </div>
                            <div class="text-[10px] font-bold text-gray-400 tracking-wider uppercase mt-0.5" x-text="item.timezone || 'BDT'"></div>
                        </div>

                        <!-- Center: Line & Node Dot -->
                        <div class="relative flex flex-col items-center self-stretch shrink-0">
                            <!-- Dot -->
                            <div class="relative z-10 w-3.5 h-3.5 rounded-full bg-brand-600 ring-4 ring-brand-100 shrink-0 my-1 transition-transform group-hover:scale-110"></div>
                            <!-- Vertical Connecting Line -->
                            <div class="w-0.5 bg-brand-200 grow -mt-1" x-show="index < adminNotes.length - 1"></div>
                        </div>

                        <!-- Right: Note Description Card -->
                        <div class="flex-1 pl-4 sm:pl-5 pb-5">
                            <div class="bg-slate-50/80 group-hover:bg-white border border-slate-200/80 group-hover:border-slate-300 rounded-2xl p-4 shadow-xs transition-all duration-200">
                                <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-200/60">
                                    <div class="flex items-center gap-1.5 text-xs font-bold text-gray-800">
                                        <div class="w-5 h-5 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center text-[10px]">
                                            <i class="ph-bold ph-user"></i>
                                        </div>
                                        <span x-text="item.admin_name || 'System/Admin'"></span>
                                    </div>
                                    <span class="text-[10px] uppercase font-semibold tracking-wider px-1.5 py-0.5 rounded bg-gray-100 text-gray-600">Admin Note</span>
                                </div>
                                <p class="text-xs sm:text-sm text-gray-800 whitespace-pre-wrap leading-relaxed font-normal" x-text="item.note"></p>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </div>
</div>
