@extends('layouts.admin')

@section('title', 'Database Backup & Restore - Admin Panel')
@section('header_title', 'Database Backup & Recovery')

@section('content')
<div class="w-full mx-auto space-y-6" x-data="backupManager({
    captchaUrl: '{{ route('admin.settings.backup.captcha') }}',
    initialQuestion: '{{ $mathCaptcha['question'] }}',
    csrf: '{{ csrf_token() }}'
})">

    <!-- Top Breadcrumb & Quick Nav -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.settings.index') }}" 
               class="w-9 h-9 rounded-xl bg-white border border-gray-200 text-gray-600 hover:text-brand-600 hover:border-brand-300 flex items-center justify-center transition-all shadow-2xs cursor-pointer">
                <i class="ph ph-arrow-left text-lg"></i>
            </a>
            <div>
                <h1 class="text-xl font-black text-gray-900 tracking-tight flex items-center gap-2">
                    <i class="ph ph-database text-brand-600 text-2xl"></i>
                    Database Backup & Recovery
                </h1>
                <p class="text-xs text-gray-500 font-medium">Create compressed SQL snapshots, store server archives, and safely restore with security safeguards</p>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold flex items-center gap-2 shadow-xs">
            <i class="ph ph-check-circle text-xl text-emerald-600 shrink-0"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm font-semibold flex items-center gap-2 shadow-xs">
            <i class="ph ph-warning-circle text-xl text-red-600 shrink-0"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Database Overview Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-xs">
            <div class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Active Database</div>
            <div class="text-base font-black text-gray-900 font-mono truncate" title="{{ $dbName }}">{{ $dbName }}</div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-xs">
            <div class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Total Tables</div>
            <div class="text-xl font-black text-brand-600">{{ $tableCount }} tables</div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-xs">
            <div class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Database Size</div>
            <div class="text-xl font-black text-gray-900">{{ $approxSizeFormatted }}</div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-xs">
            <div class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Stored Backups</div>
            <div class="text-xl font-black text-purple-600">{{ count($backups) }} archives</div>
        </div>
    </div>

    <!-- Actions Section: Create & Upload -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Card 1: Create Backup Now -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-xs p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                        <i class="ph ph-file-zip text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-gray-900">Create Database Backup</h2>
                        <p class="text-xs text-gray-500">Fast PDO streaming export with ZIP compression</p>
                    </div>
                </div>

                <p class="text-xs text-gray-600 leading-relaxed mb-6">
                    Generates a full transactional dump of all <strong>{{ $tableCount }} tables</strong>, table structures, and rows. The output is packaged into a compressed ZIP file with automated Telegram alerts.
                </p>

                <!-- Form -->
                <form action="{{ route('admin.settings.backup.create') }}" method="POST" @submit="isCreating = true">
                    @csrf
                    
                    <!-- Store in Server Toggle -->
                    <div class="p-4 rounded-xl bg-gray-50 border border-gray-200 mb-6 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-gray-900 block">Store in Server Vault</span>
                            <span class="text-[11px] text-gray-500">Keep a timestamped copy on the server for instant future restores</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="store_in_server" value="1" checked class="sr-only peer cursor-pointer">
                            <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600 cursor-pointer"></div>
                        </label>
                    </div>

                    <button type="submit" 
                            :disabled="isCreating"
                            class="w-full py-3 px-5 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-bold text-sm shadow-xs transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="ph ph-download-simple text-lg" x-show="!isCreating"></i>
                        <svg x-show="isCreating" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span x-text="isCreating ? 'Generating Database Backup...' : 'Create DB Backup Now'"></span>
                    </button>
                </form>
            </div>

            <div class="mt-4 pt-4 border-t border-gray-100 flex items-center gap-2 text-[11px] text-gray-400">
                <i class="ph ph-shield-check text-emerald-600 text-sm"></i>
                <span>Zero database locking &bull; Memory safe chunking &bull; Instant Telegram ping</span>
            </div>
        </div>

        <!-- Card 2: Upload Backup to Restore -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-xs p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
                        <i class="ph ph-upload-simple text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-gray-900">Upload & Restore Backup</h2>
                        <p class="text-xs text-gray-500">Restore database from an external .zip or .sql file</p>
                    </div>
                </div>

                <p class="text-xs text-gray-600 leading-relaxed mb-6">
                    Need to migrate from another server or restore a previously downloaded backup? Upload your backup file here. For safety, an automatic fallback snapshot is taken before executing.
                </p>

                <!-- Trigger Modal with Upload Form -->
                <button type="button" 
                        @click="openUploadModal()"
                        class="w-full py-3 px-5 rounded-xl bg-amber-600 hover:bg-amber-700 active:bg-amber-800 text-white font-bold text-sm shadow-xs transition-all flex items-center justify-center gap-2 cursor-pointer">
                    <i class="ph ph-arrow-counter-clockwise text-lg"></i>
                    <span>Select File to Restore</span>
                </button>
            </div>

            <div class="mt-4 pt-4 border-t border-gray-100 flex items-center gap-2 text-[11px] text-gray-400">
                <i class="ph ph-lock-key text-amber-600 text-sm"></i>
                <span>Math Captcha Protected &bull; Emergency Email Alert &bull; Pre-Restore Snapshot</span>
            </div>
        </div>

    </div>

    <!-- Stored Server Backups Vault Table -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                    <i class="ph ph-archive text-purple-600 text-xl"></i>
                    Server Backup Vault
                </h3>
                <p class="text-xs text-gray-500">Stored backup archives currently retained on the server disk</p>
            </div>
            <div class="text-xs font-semibold text-gray-500 bg-gray-50 px-3 py-1.5 rounded-lg border border-gray-100">
                Directory: <code class="text-gray-800">storage/app/backups/</code>
            </div>
        </div>

        @if(empty($backups))
            <div class="p-12 text-center">
                <div class="w-14 h-14 bg-gray-50 text-gray-400 rounded-2xl flex items-center justify-center mx-auto mb-3">
                    <i class="ph ph-folder-dashed text-3xl"></i>
                </div>
                <h4 class="text-sm font-bold text-gray-700 mb-1">No Stored Backups Found</h4>
                <p class="text-xs text-gray-500 max-w-sm mx-auto mb-4">Click "Create DB Backup Now" with the server toggle enabled to retain a backup archive here.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-50/75 border-b border-gray-100 text-gray-400 font-bold uppercase tracking-wider">
                            <th class="py-3 px-5">Archive Name</th>
                            <th class="py-3 px-4">Size</th>
                            <th class="py-3 px-4">Created Date</th>
                            <th class="py-3 px-4">Type</th>
                            <th class="py-3 px-5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        @foreach($backups as $backup)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="py-3.5 px-5 font-mono font-bold text-gray-900">
                                    <div class="flex items-center gap-2">
                                        <i class="ph {{ $backup['is_safety'] ? 'ph-shield-check text-emerald-600' : 'ph-file-zip text-purple-600' }} text-base shrink-0"></i>
                                        <span class="truncate max-w-xs sm:max-w-md">{{ $backup['filename'] }}</span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-gray-600">
                                    {{ $backup['size_formatted'] }}
                                </td>
                                <td class="py-3.5 px-4 text-gray-500 whitespace-nowrap">
                                    {{ $backup['created_at'] }}
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if($backup['is_safety'])
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800">
                                            Safety Snapshot
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800">
                                            Standard Backup
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-5 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <!-- Download -->
                                        <a href="{{ route('admin.settings.backup.download', $backup['filename']) }}" 
                                           class="p-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-bold transition-colors cursor-pointer"
                                           title="Download ZIP">
                                            <i class="ph ph-download-simple text-sm"></i>
                                        </a>

                                        <!-- Restore Button -->
                                        <button type="button" 
                                                @click="openRestoreModal('{{ $backup['filename'] }}')"
                                                class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 rounded-lg font-bold transition-colors flex items-center gap-1 cursor-pointer"
                                                title="Restore this backup">
                                            <i class="ph ph-arrow-counter-clockwise text-sm"></i>
                                            <span>Restore</span>
                                        </button>

                                        <!-- Delete Button -->
                                        <button type="button" 
                                                @click="openDeleteModal('{{ $backup['filename'] }}')"
                                                class="p-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg font-bold transition-colors cursor-pointer"
                                                title="Delete archive">
                                            <i class="ph ph-trash text-sm"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- CUSTOM DELETE CONFIRMATION MODAL -->
    <div x-show="deleteModalOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" 
         role="dialog" 
         aria-modal="true">
        
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-900/75 backdrop-blur-xs transition-opacity"
             @click="deleteModalOpen = false"></div>

        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-red-100"
                 @click.away="deleteModalOpen = false">
                
                <div class="p-6">
                    <div class="w-12 h-12 rounded-2xl bg-red-100 text-red-600 flex items-center justify-center mb-4 mx-auto shadow-xs">
                        <i class="ph ph-trash text-2xl"></i>
                    </div>
                    
                    <h3 class="text-base font-black text-gray-900 text-center mb-1">Delete Backup Archive?</h3>
                    <p class="text-xs text-gray-500 text-center mb-4 leading-relaxed">
                        Are you sure you want to permanently delete this backup file from your server vault? This action cannot be undone.
                    </p>

                    <div class="p-3 bg-gray-50 rounded-xl border border-gray-200 text-center mb-6">
                        <span class="font-mono text-xs font-bold text-gray-800 break-all" x-text="deleteTargetFilename"></span>
                    </div>

                    <form :action="deleteFormAction" method="POST" class="flex items-center gap-3">
                        @csrf
                        @method('DELETE')
                        <button type="button" 
                                @click="deleteModalOpen = false"
                                class="w-1/2 py-2.5 rounded-xl border border-gray-200 text-gray-700 font-bold text-xs hover:bg-gray-50 transition-colors cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="w-1/2 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 active:bg-red-800 text-white font-bold text-xs shadow-xs transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                            <i class="ph ph-trash"></i>
                            <span>Yes, Delete</span>
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <!-- RESTORE CONFIRMATION MODAL WITH MATH CAPTCHA -->
    <div x-show="restoreModalOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" 
         aria-labelledby="modal-title" 
         role="dialog" 
         aria-modal="true">
        
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-900/75 backdrop-blur-xs transition-opacity"
             @click="closeModal()"></div>

        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-red-100"
                 @click.away="closeModal()">
                
                <!-- Danger Header -->
                <div class="bg-red-600 p-5 text-white flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center font-bold text-white shrink-0">
                        <i class="ph ph-warning-octagon text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-black leading-tight">Critical Confirmation: Restore Database</h3>
                        <p class="text-xs text-red-100">All current tables will be overwritten with the selected snapshot</p>
                    </div>
                </div>

                <!-- Form -->
                <form :action="restoreFormAction" method="POST" enctype="multipart/form-data" class="p-6 space-y-4" @submit="isRestoring = true">
                    @csrf

                    <!-- Target File Info -->
                    <div class="p-3.5 rounded-xl bg-gray-50 border border-gray-200">
                        <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider block mb-1">Target Backup Snapshot:</span>
                        <div class="font-mono text-xs font-bold text-gray-900 truncate" x-text="restoreTargetFilename"></div>
                    </div>

                    <!-- File input if Upload Mode -->
                    <div x-show="isUploadMode" class="space-y-1">
                        <label class="text-xs font-bold text-gray-700 block">Select Backup ZIP / SQL File:</label>
                        <input type="file" 
                               name="backup_file" 
                               accept=".zip,.sql"
                               :required="isUploadMode"
                               class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 cursor-pointer">
                    </div>

                    <!-- Simple Math Captcha Section -->
                    <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black text-amber-900 flex items-center gap-1.5">
                                <i class="ph ph-calculator text-base text-amber-700"></i>
                                Security Math Captcha Verification
                            </span>
                            <button type="button" 
                                    @click="reloadCaptcha()" 
                                    class="text-[11px] font-bold text-amber-700 hover:text-amber-900 flex items-center gap-1 cursor-pointer">
                                <i class="ph ph-arrows-clockwise text-xs"></i> New Problem
                            </button>
                        </div>
                        <p class="text-[11px] text-amber-800">
                            To confirm database restoration, please solve the simple equation below:
                        </p>
                        
                        <div class="flex items-center gap-3">
                            <div class="px-4 py-2 bg-white rounded-xl border border-amber-300 font-mono text-sm font-black text-gray-900 tracking-wider shadow-2xs select-none"
                                 x-text="captchaQuestion + ' = ?'"></div>
                            
                            <input type="number" 
                                   name="math_answer" 
                                   required 
                                   autocomplete="off"
                                   placeholder="Answer"
                                   class="w-28 px-3 py-2 bg-white border border-amber-300 rounded-xl text-sm font-black text-gray-900 focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                        </div>
                    </div>

                    <div class="text-[11px] text-gray-500 leading-relaxed bg-gray-50 p-3 rounded-xl border border-gray-100">
                        <strong>Fail-safe Guarantee:</strong> A safety snapshot of your current database will be saved before restoring. Telegram & Email alerts will be broadcast immediately upon completion.
                    </div>

                    <!-- Modal Actions -->
                    <div class="pt-3 flex items-center justify-end gap-3 border-t border-gray-100">
                        <button type="button" 
                                @click="closeModal()" 
                                class="px-4 py-2.5 rounded-xl border border-gray-200 text-gray-700 font-bold text-xs hover:bg-gray-50 transition-colors cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" 
                                :disabled="isRestoring"
                                class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 active:bg-red-800 text-white font-bold text-xs shadow-xs transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="ph ph-warning text-sm" x-show="!isRestoring"></i>
                            <svg x-show="isRestoring" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span x-text="isRestoring ? 'Restoring Database...' : 'Confirm & Restore Database'"></span>
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function backupManager(config) {
    return {
        captchaUrl: config.captchaUrl,
        captchaQuestion: config.initialQuestion,
        csrf: config.csrf,
        isCreating: false,
        isRestoring: false,
        restoreModalOpen: false,
        deleteModalOpen: false,
        isUploadMode: false,
        restoreTargetFilename: '',
        restoreFormAction: '',
        deleteTargetFilename: '',
        deleteFormAction: '',

        openRestoreModal(filename) {
            this.isUploadMode = false;
            this.restoreTargetFilename = filename;
            this.restoreFormAction = `{{ url('admin/settings/backup/restore') }}/${encodeURIComponent(filename)}`;
            this.restoreModalOpen = true;
            this.reloadCaptcha();
        },

        openUploadModal() {
            this.isUploadMode = true;
            this.restoreTargetFilename = 'Uploaded ZIP / SQL file';
            this.restoreFormAction = `{{ route('admin.settings.backup.restore-upload') }}`;
            this.restoreModalOpen = true;
            this.reloadCaptcha();
        },

        openDeleteModal(filename) {
            this.deleteTargetFilename = filename;
            this.deleteFormAction = `{{ url('admin/settings/backup') }}/${encodeURIComponent(filename)}`;
            this.deleteModalOpen = true;
        },

        closeModal() {
            this.restoreModalOpen = false;
            this.deleteModalOpen = false;
            this.isRestoring = false;
        },

        async reloadCaptcha() {
            try {
                const res = await fetch(this.captchaUrl);
                const data = await res.json();
                if (data.success && data.question) {
                    this.captchaQuestion = data.question;
                }
            } catch (e) {
                console.error('Failed to reload captcha', e);
            }
        }
    };
}
</script>
@endpush
@endsection
