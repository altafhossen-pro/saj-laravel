@extends('layouts.admin')

@section('title', 'Laravel Logs - Admin Panel')
@section('header_title', 'Laravel System Logs')

@section('content')
<div class="w-full mx-auto space-y-6" x-data="logEditor({
    currentFile: '{{ $selectedFile }}',
    saveUrl: '{{ route('admin.settings.logs.save') }}',
    clearUrl: '{{ route('admin.settings.logs.clear') }}',
    downloadUrl: '{{ route('admin.settings.logs.download') }}',
    csrf: '{{ csrf_token() }}'
})">

    <!-- Top Breadcrumb & Quick Nav -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.settings.index') }}" 
               class="w-9 h-9 rounded-xl bg-white border border-gray-200 text-gray-600 hover:text-brand-600 hover:border-brand-300 flex items-center justify-center transition-all shadow-2xs">
                <i class="ph ph-arrow-left text-lg"></i>
            </a>
            <div>
                <h1 class="text-xl font-black text-gray-900 tracking-tight flex items-center gap-2">
                    <i class="ph ph-terminal-window text-brand-600 text-2xl"></i>
                    Laravel System Logs
                </h1>
                <p class="text-xs text-gray-500 font-medium">Inspect, debug, edit and clear application execution logs in real-time</p>
            </div>
        </div>

        <!-- Quick File Switcher -->
        <div class="flex items-center gap-2">
            <label class="text-xs font-bold text-gray-500 uppercase tracking-wider shrink-0">Log File:</label>
            <div class="relative">
                <select @change="switchFile($event.target.value)" 
                        class="pl-3 pr-8 py-2 bg-white border border-gray-200 rounded-xl text-xs font-bold text-gray-800 shadow-2xs focus:ring-2 focus:ring-brand-500 focus:border-brand-500 cursor-pointer">
                    @foreach($logFiles as $log)
                        <option value="{{ $log['name'] }}" {{ $selectedFile === $log['name'] ? 'selected' : '' }}>
                            {{ $log['name'] }} ({{ $log['size'] }})
                        </option>
                    @endforeach
                </select>
            </div>
            
            <a href="{{ route('admin.settings.logs', ['file' => $selectedFile]) }}" 
               class="p-2 bg-white border border-gray-200 rounded-xl text-gray-600 hover:text-brand-600 hover:border-brand-300 transition-colors shadow-2xs"
               title="Reload Log">
                <i class="ph ph-arrows-clockwise text-base"></i>
            </a>
        </div>
    </div>

    <!-- Alert / Toast Message -->
    <template x-if="toast.show">
        <div class="p-4 rounded-xl text-sm font-semibold flex items-center justify-between transition-all"
             :class="toast.type === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-red-50 text-red-800 border border-red-200'">
            <div class="flex items-center gap-2">
                <i :class="toast.type === 'success' ? 'ph ph-check-circle text-lg text-emerald-600' : 'ph ph-warning-circle text-lg text-red-600'"></i>
                <span x-text="toast.message"></span>
            </div>
            <button @click="toast.show = false" class="text-gray-400 hover:text-gray-600">
                <i class="ph ph-x text-sm"></i>
            </button>
        </div>
    </template>

    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold flex items-center gap-2">
            <i class="ph ph-check-circle text-lg text-emerald-600"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm font-semibold flex items-center gap-2">
            <i class="ph ph-warning-circle text-lg text-red-600"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Metadata Bar & Actions Card -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-xs p-4 flex flex-wrap items-center justify-between gap-4">
        
        <!-- File Info Badges -->
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-gray-100 text-gray-700 text-xs font-bold font-mono">
                <i class="ph ph-file-text text-gray-500"></i>
                {{ $selectedFile }}
            </span>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700 text-xs font-bold">
                <i class="ph ph-database text-blue-500"></i>
                <span x-text="fileSize">{{ $fileSize }}</span>
            </span>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-purple-50 text-purple-700 text-xs font-bold">
                <i class="ph ph-list-numbers text-purple-500"></i>
                <span x-text="totalLines">{{ number_format($totalLines) }}</span> lines
            </span>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-50 text-amber-700 text-xs font-medium">
                <i class="ph ph-clock text-amber-500"></i>
                Modified: {{ $lastModified }}
            </span>

            @if($isTruncated)
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-orange-100 text-orange-800 text-xs font-bold"
                      title="File is over 2.5 MB. Showing latest 2,500 lines for high performance.">
                    <i class="ph ph-warning-circle text-orange-600"></i>
                    Tail Preview (2,500 lines)
                </span>
                <a href="{{ route('admin.settings.logs', ['file' => $selectedFile, 'full' => 1]) }}"
                   class="text-xs font-bold text-brand-600 hover:underline">
                    Load Full File
                </a>
            @endif
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-2">
            <!-- Download Button -->
            <a :href="downloadUrl + '?file=' + currentFile" 
               class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition-colors">
                <i class="ph ph-download-simple text-base"></i>
                Download
            </a>

            <!-- Clear Button -->
            <button @click="openClearModal()" 
                    :disabled="isClearing || isSaving"
                    type="button"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-red-50 hover:bg-red-100 text-red-700 rounded-xl text-xs font-bold transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="ph ph-trash text-base"></i>
                <span x-text="isClearing ? 'Clearing...' : 'Clear Log'"></span>
            </button>

            <!-- Save Changes Button -->
            <button @click="saveLog()" 
                    :disabled="isSaving || isClearing"
                    type="button"
                    class="inline-flex items-center gap-2 px-5 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-xl text-xs font-black shadow-xs transition-all disabled:opacity-50">
                <i class="ph ph-floppy-disk text-base" x-show="!isSaving"></i>
                <svg x-show="isSaving" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span x-text="isSaving ? 'Saving Changes...' : 'Save Log File'"></span>
            </button>
        </div>
    </div>

    <!-- Terminal Code Editor Canvas -->
    <div class="bg-[#0D1117] rounded-2xl border border-gray-800 shadow-xl overflow-hidden flex flex-col">
        
        <!-- Editor Top Header Bar -->
        <div class="bg-[#161B22] px-4 py-2.5 border-b border-gray-800 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-red-500/80 inline-block"></span>
                <span class="w-3 h-3 rounded-full bg-amber-500/80 inline-block"></span>
                <span class="w-3 h-3 rounded-full bg-emerald-500/80 inline-block"></span>
                <span class="ml-2 font-mono text-xs text-gray-400 font-semibold tracking-wider">
                    storage/logs/<span class="text-white" x-text="currentFile"></span>
                </span>
            </div>

            <!-- Editor Helper Controls -->
            <div class="flex items-center gap-2 sm:gap-3 text-xs text-gray-400">
                <button type="button" 
                        @click="scrollToTop()" 
                        class="p-1 px-2 rounded bg-gray-800 hover:bg-gray-700 text-gray-300 transition-colors flex items-center gap-1 text-[11px]"
                        title="Scroll to Top">
                    <i class="ph ph-arrow-up"></i> Top
                </button>
                <button type="button" 
                        @click="scrollToBottom()" 
                        class="p-1 px-2 rounded bg-gray-800 hover:bg-gray-700 text-gray-300 transition-colors flex items-center gap-1 text-[11px]"
                        title="Scroll to Bottom">
                    <i class="ph ph-arrow-down"></i> Bottom
                </button>
                <span class="text-gray-700">|</span>
                <label class="flex items-center gap-1.5 cursor-pointer hover:text-gray-200 select-none">
                    <input type="checkbox" x-model="wrapLines" class="rounded bg-gray-800 border-gray-700 text-brand-600 focus:ring-0">
                    <span>Wrap Lines</span>
                </label>
                <span class="text-gray-700 hidden sm:inline">|</span>
                <span class="text-[11px] text-gray-500 font-mono hidden sm:inline">Shortcut: <kbd class="px-1.5 py-0.5 rounded bg-gray-800 text-gray-300 border border-gray-700">Ctrl + S</kbd> to save</span>
            </div>
        </div>

        <!-- Textarea Container -->
        <div class="relative w-full">
            <textarea x-ref="logTextarea"
                      @input="onContentChange()"
                      @keydown.window.ctrl.s.prevent="saveLog()"
                      @keydown.window.meta.s.prevent="saveLog()"
                      rows="28"
                      :class="wrapLines ? 'whitespace-pre-wrap' : 'whitespace-pre overflow-x-auto'"
                      placeholder="Log file is currently empty..."
                      class="w-full bg-[#0D1117] text-[#7EE787] font-mono text-xs p-4 sm:p-5 outline-none border-0 focus:ring-0 resize-y leading-relaxed selection:bg-brand-600 selection:text-white"
                      style="min-height: 480px; tab-size: 4;">{{ $content }}</textarea>
        </div>

        <!-- Terminal Footer -->
        <div class="bg-[#161B22] px-4 py-2 border-t border-gray-800 flex items-center justify-between text-xs text-gray-400 font-mono">
            <div>
                <span>Encoding: <strong>UTF-8</strong></span> &bull; 
                <span>Format: <strong>Monolog Plaintext</strong></span>
            </div>
            <div>
                <span x-text="charCount.toLocaleString() + ' chars'"></span>
            </div>
        </div>
    </div>

    <!-- CUSTOM CLEAR LOG CONFIRMATION MODAL -->
    <div x-show="clearModalOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" 
         role="dialog" 
         aria-modal="true">
        
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-900/75 backdrop-blur-xs transition-opacity"
             @click="clearModalOpen = false"></div>

        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-red-100"
                 @click.away="clearModalOpen = false">
                
                <div class="p-6">
                    <div class="w-12 h-12 rounded-2xl bg-red-100 text-red-600 flex items-center justify-center mb-4 mx-auto shadow-xs">
                        <i class="ph ph-trash text-2xl"></i>
                    </div>
                    
                    <h3 class="text-base font-black text-gray-900 text-center mb-1">Clear Log File?</h3>
                    <p class="text-xs text-gray-500 text-center mb-4 leading-relaxed">
                        Are you sure you want to permanently erase all records from this log file? Any unsaved changes will be lost.
                    </p>

                    <div class="p-3 bg-gray-50 rounded-xl border border-gray-200 text-center mb-6">
                        <span class="font-mono text-xs font-bold text-gray-800" x-text="currentFile"></span>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="button" 
                                @click="clearModalOpen = false"
                                class="w-1/2 py-2.5 rounded-xl border border-gray-200 text-gray-700 font-bold text-xs hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                        <button type="button"
                                @click="confirmClearLog()"
                                :disabled="isClearing"
                                class="w-1/2 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 active:bg-red-800 text-white font-bold text-xs shadow-xs transition-all flex items-center justify-center gap-1.5 disabled:opacity-50">
                            <i class="ph ph-trash" x-show="!isClearing"></i>
                            <span x-text="isClearing ? 'Clearing...' : 'Yes, Clear Log'"></span>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function logEditor(config) {
    return {
        currentFile: config.currentFile,
        saveUrl: config.saveUrl,
        clearUrl: config.clearUrl,
        downloadUrl: config.downloadUrl,
        csrf: config.csrf,
        wrapLines: false,
        isSaving: false,
        isClearing: false,
        clearModalOpen: false,
        fileSize: '{{ $fileSize }}',
        totalLines: {{ $totalLines }},
        charCount: 0,
        toast: {
            show: false,
            message: '',
            type: 'success'
        },

        init() {
            this.updateStats();
            // Automatically scroll to bottom so the most recent log entries are visible
            this.$nextTick(() => {
                this.scrollToBottom();
            });
        },

        onContentChange() {
            this.updateStats();
        },

        updateStats() {
            if (this.$refs.logTextarea) {
                const val = this.$refs.logTextarea.value;
                this.charCount = val.length;
                this.totalLines = (val.match(/\n/g) || []).length + (val.length > 0 ? 1 : 0);
            }
        },

        scrollToBottom() {
            if (this.$refs.logTextarea) {
                this.$refs.logTextarea.scrollTop = this.$refs.logTextarea.scrollHeight;
            }
        },

        scrollToTop() {
            if (this.$refs.logTextarea) {
                this.$refs.logTextarea.scrollTop = 0;
            }
        },

        switchFile(fileName) {
            window.location.href = `{{ route('admin.settings.logs') }}?file=${encodeURIComponent(fileName)}`;
        },

        showToast(message, type = 'success') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            setTimeout(() => {
                this.toast.show = false;
            }, 4500);
        },

        async saveLog() {
            if (this.isSaving) return;
            this.isSaving = true;

            const content = this.$refs.logTextarea ? this.$refs.logTextarea.value : '';

            try {
                const response = await fetch(this.saveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        file: this.currentFile,
                        content: content
                    })
                });

                const data = await response.json();
                if (data.success) {
                    this.showToast(data.message, 'success');
                    if (data.size) this.fileSize = data.size;
                    this.updateStats();
                } else {
                    this.showToast(data.message || 'Failed to save log file.', 'error');
                }
            } catch (err) {
                this.showToast('Network error while saving log file.', 'error');
            } finally {
                this.isSaving = false;
            }
        },

        openClearModal() {
            this.clearModalOpen = true;
        },

        async confirmClearLog() {
            this.isClearing = true;

            try {
                const response = await fetch(this.clearUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        file: this.currentFile
                    })
                });

                const data = await response.json();
                if (data.success) {
                    if (this.$refs.logTextarea) {
                        this.$refs.logTextarea.value = '';
                    }
                    this.fileSize = '0 B';
                    this.totalLines = 0;
                    this.charCount = 0;
                    this.clearModalOpen = false;
                    this.showToast(data.message, 'success');
                } else {
                    this.showToast(data.message || 'Failed to clear log.', 'error');
                }
            } catch (err) {
                this.showToast('Network error while clearing log.', 'error');
            } finally {
                this.isClearing = false;
            }
        }
    };
}
</script>
@endpush
@endsection
