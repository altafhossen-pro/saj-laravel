@extends('layouts.admin')

@section('title', 'Contact Messages - Admin Panel')
@section('header_title', 'Contact Messages')

@section('content')
<div class="space-y-6" x-data="{
    detailModalOpen: false,
    deleteModalOpen: false,
    activeItem: null,
    deletingItem: null,
    statusForm: 'new',
    adminNotes: '',

    openDetail(item) {
        this.activeItem = item;
        this.statusForm = item.status;
        this.adminNotes = item.admin_notes || '';
        this.detailModalOpen = true;

        // Auto mark as read if it is new
        if (item.status === 'new') {
            this.updateStatusDirect(item, 'read');
        }
    },

    confirmDelete(item) {
        this.deletingItem = item;
        this.deleteModalOpen = true;
    },

    updateStatusDirect(item, newStatus) {
        fetch(`/admin/dashboard/contact-messages/${item.id}/status`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                status: newStatus,
                admin_notes: this.adminNotes
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                item.status = newStatus;
                this.statusForm = newStatus;
            }
        })
        .catch(err => console.error('Status update failed', err));
    }
}">

    <!-- Page Header & Overview Counters -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Contact Messages & Inquiries</h1>
            <p class="text-sm text-gray-500 mt-1">Review, filter, track and reply to visitor inquiries sent through the Contact Us page.</p>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            <div class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white border border-gray-200 text-xs shadow-xs">
                <span class="w-2.5 h-2.5 rounded-full bg-blue-500 animate-pulse"></span>
                <span class="text-gray-500 font-medium">New Messages:</span>
                <span class="font-bold text-blue-600 text-sm">{{ $counts['new'] }}</span>
            </div>
            <div class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white border border-gray-200 text-xs shadow-xs">
                <span class="text-gray-500 font-medium">Total Messages:</span>
                <span class="font-bold text-gray-800 text-sm">{{ $counts['total'] }}</span>
            </div>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- All -->
        <a href="{{ route('admin.contact-messages.index') }}" 
           class="p-4 rounded-2xl bg-white border {{ !request('status') ? 'border-brand-500 ring-2 ring-brand-100 shadow-sm' : 'border-gray-100 hover:border-gray-300' }} transition-all flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-gray-100 text-gray-700 flex items-center justify-center shrink-0 text-xl">
                <i class="ph ph-chat-text"></i>
            </div>
            <div>
                <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">All Messages</span>
                <span class="text-xl font-bold text-gray-900 block mt-0.5">{{ $counts['total'] }}</span>
            </div>
        </a>

        <!-- New / Unread -->
        <a href="{{ route('admin.contact-messages.index', array_merge(request()->except('page'), ['status' => 'new'])) }}" 
           class="p-4 rounded-2xl bg-white border {{ request('status') === 'new' ? 'border-blue-500 ring-2 ring-blue-100 shadow-sm' : 'border-gray-100 hover:border-blue-200' }} transition-all flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 text-xl">
                <i class="ph ph-envelope-simple"></i>
            </div>
            <div>
                <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">New</span>
                <span class="text-xl font-bold text-blue-600 block mt-0.5">{{ $counts['new'] }}</span>
            </div>
        </a>

        <!-- Read -->
        <a href="{{ route('admin.contact-messages.index', array_merge(request()->except('page'), ['status' => 'read'])) }}" 
           class="p-4 rounded-2xl bg-white border {{ request('status') === 'read' ? 'border-amber-500 ring-2 ring-amber-100 shadow-sm' : 'border-gray-100 hover:border-amber-200' }} transition-all flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 text-xl">
                <i class="ph ph-envelope-open"></i>
            </div>
            <div>
                <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">Read / In Review</span>
                <span class="text-xl font-bold text-amber-600 block mt-0.5">{{ $counts['read'] }}</span>
            </div>
        </a>

        <!-- Replied -->
        <a href="{{ route('admin.contact-messages.index', array_merge(request()->except('page'), ['status' => 'replied'])) }}" 
           class="p-4 rounded-2xl bg-white border {{ request('status') === 'replied' ? 'border-emerald-500 ring-2 ring-emerald-100 shadow-sm' : 'border-gray-100 hover:border-emerald-200' }} transition-all flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 text-xl">
                <i class="ph ph-check-circle"></i>
            </div>
            <div>
                <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">Replied</span>
                <span class="text-xl font-bold text-emerald-600 block mt-0.5">{{ $counts['replied'] }}</span>
            </div>
        </a>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <form method="GET" action="{{ route('admin.contact-messages.index') }}" class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-3 flex-1 min-w-[280px]">
                <!-- Search Input -->
                <div class="relative w-full max-w-md">
                    <i class="ph ph-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-base"></i>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Search by name, phone, email, IP, or message..." 
                           class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 focus:bg-white transition-all">
                </div>

                <!-- Status Selector -->
                <select name="status" class="px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 focus:bg-white cursor-pointer">
                    <option value="">All Statuses</option>
                    <option value="new" {{ request('status') === 'new' ? 'selected' : '' }}>New</option>
                    <option value="read" {{ request('status') === 'read' ? 'selected' : '' }}>Read</option>
                    <option value="replied" {{ request('status') === 'replied' ? 'selected' : '' }}>Replied</option>
                </select>

                <!-- Sort Direction -->
                <select name="sort" class="px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 focus:bg-white cursor-pointer">
                    <option value="desc" {{ request('sort') !== 'asc' ? 'selected' : '' }}>Newest First</option>
                    <option value="asc" {{ request('sort') === 'asc' ? 'selected' : '' }}>Oldest First</option>
                </select>

                <button type="submit" class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-sm font-semibold transition-colors flex items-center gap-2 cursor-pointer shadow-xs">
                    <i class="ph ph-funnel"></i>
                    <span>Filter</span>
                </button>

                @if(request('search') || request('status') || request('sort'))
                    <a href="{{ route('admin.contact-messages.index') }}" 
                       class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-sm font-medium transition-colors flex items-center gap-1.5 cursor-pointer">
                        <i class="ph ph-arrow-counter-clockwise"></i>
                        <span>Reset</span>
                    </a>
                @endif
            </div>

            <div class="text-xs text-gray-500">
                Showing <strong>{{ $messages->firstItem() ?? 0 }}</strong> to <strong>{{ $messages->lastItem() ?? 0 }}</strong> of <strong>{{ $messages->total() }}</strong> records
            </div>
        </form>
    </div>

    <!-- Messages Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-100 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                        <th class="py-3.5 px-5">Sender</th>
                        <th class="py-3.5 px-4">Subject & Message Preview</th>
                        <th class="py-3.5 px-4">IP Address & Details</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-4">Received At</th>
                        <th class="py-3.5 px-5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($messages as $msg)
                        <tr class="hover:bg-gray-50/70 transition-colors {{ $msg->status === 'new' ? 'bg-blue-50/20 font-medium' : '' }}">
                            <!-- Sender Info -->
                            <td class="py-4 px-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl {{ $msg->status === 'new' ? 'bg-brand-100 text-brand-700 font-bold' : 'bg-gray-100 text-gray-600' }} flex items-center justify-center shrink-0 text-sm">
                                        {{ mb_substr($msg->name, 0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-gray-900 flex items-center gap-1.5">
                                            <span>{{ $msg->name }}</span>
                                            @if($msg->status === 'new')
                                                <span class="w-2 h-2 rounded-full bg-blue-600 shrink-0" title="Unread message"></span>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <a href="tel:{{ $msg->phone }}" class="text-xs font-mono text-gray-600 hover:text-brand-600 transition-colors flex items-center gap-1">
                                                <i class="ph ph-phone text-gray-400"></i>
                                                <span>{{ $msg->phone }}</span>
                                            </a>
                                            @if($msg->email)
                                                <span class="text-gray-300">&bull;</span>
                                                <a href="mailto:{{ $msg->email }}" class="text-xs text-gray-500 hover:text-brand-600 transition-colors truncate max-w-[130px]" title="{{ $msg->email }}">
                                                    {{ $msg->email }}
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Subject & Message -->
                            <td class="py-4 px-4 max-w-xs">
                                <div>
                                    <span class="inline-block px-2 py-0.5 rounded text-[11px] font-semibold bg-gray-100 text-gray-700 mb-1">
                                        {{ $msg->subject ?: 'General Inquiry' }}
                                    </span>
                                    <p class="text-xs text-gray-600 line-clamp-2 leading-relaxed" title="{{ $msg->message }}">
                                        {{ $msg->message }}
                                    </p>
                                </div>
                            </td>

                            <!-- IP Address -->
                            <td class="py-4 px-4 whitespace-nowrap">
                                <div class="flex flex-col gap-1">
                                    @if($msg->ip_address)
                                        <a href="https://ipinfo.io/{{ $msg->ip_address }}" target="_blank" rel="noopener noreferrer"
                                           class="font-mono text-xs font-semibold text-brand-600 hover:text-brand-700 hover:underline flex items-center gap-1">
                                            <i class="ph ph-globe text-gray-400"></i>
                                            <span>{{ $msg->ip_address }}</span>
                                            <i class="ph ph-arrow-square-out text-[10px]"></i>
                                        </a>
                                    @else
                                        <span class="text-xs text-gray-400">N/A</span>
                                    @endif

                                    @if(\App\Models\Blacklist::isBlocked($msg->ip_address, $msg->phone))
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200 w-fit">
                                            <i class="ph ph-prohibit text-xs"></i> Blocked
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- Status Badge -->
                            <td class="py-4 px-4 text-center whitespace-nowrap">
                                @if($msg->status === 'new')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                                        New
                                    </span>
                                @elseif($msg->status === 'read')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Read
                                    </span>
                                @elseif($msg->status === 'replied')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                        Replied
                                    </span>
                                @endif
                            </td>

                            <!-- Received Date -->
                            <td class="py-4 px-4 whitespace-nowrap text-xs text-gray-500">
                                <span class="font-medium text-gray-800 block">{{ $msg->created_at->format('d M, Y') }}</span>
                                <span class="text-gray-400">{{ $msg->created_at->format('h:i A') }} ({{ $msg->created_at->diffForHumans() }})</span>
                            </td>

                            <!-- Actions -->
                            <td class="py-4 px-5 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <!-- View Modal Trigger -->
                                    <button type="button" 
                                            @click="openDetail({{ json_encode($msg) }})"
                                            title="View message details"
                                            class="p-2 rounded-xl text-gray-600 hover:text-brand-600 hover:bg-brand-50 transition-colors cursor-pointer">
                                        <i class="ph ph-eye text-lg"></i>
                                    </button>

                                    <!-- Quick WhatsApp Reply -->
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $msg->phone) }}" target="_blank" rel="noopener noreferrer"
                                       title="Chat on WhatsApp"
                                       class="p-2 rounded-xl text-gray-600 hover:text-green-600 hover:bg-green-50 transition-colors cursor-pointer">
                                        <i class="ph ph-whatsapp-logo text-lg"></i>
                                    </a>

                                    <!-- Direct Phone Call -->
                                    <a href="tel:{{ $msg->phone }}" 
                                       title="Call phone"
                                       class="p-2 rounded-xl text-gray-600 hover:text-blue-600 hover:bg-blue-50 transition-colors cursor-pointer">
                                        <i class="ph ph-phone-call text-lg"></i>
                                    </a>

                                    <!-- Delete Button -->
                                    <button type="button" 
                                            @click="confirmDelete({{ json_encode($msg) }})"
                                            title="Delete message"
                                            class="p-2 rounded-xl text-gray-400 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer">
                                        <i class="ph ph-trash text-lg"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center">
                                <div class="w-16 h-16 rounded-2xl bg-gray-50 text-gray-300 mx-auto flex items-center justify-center text-3xl mb-3">
                                    <i class="ph ph-chat-circle-dots"></i>
                                </div>
                                <h3 class="font-bold text-gray-800 text-base">No contact messages found</h3>
                                <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">There are no messages matching your search or filters at the moment.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($messages->hasPages())
            <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                {{ $messages->links() }}
            </div>
        @endif
    </div>

    <!-- DETAIL / VIEW MODAL -->
    <div x-show="detailModalOpen" style="display: none;" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div x-show="detailModalOpen" x-transition.opacity 
             class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm cursor-pointer"
             @click="detailModalOpen = false"></div>

        <!-- Modal Dialog -->
        <div x-show="detailModalOpen" 
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative bg-white rounded-2xl shadow-2xl border border-gray-100 max-w-2xl w-full max-h-[90vh] overflow-y-auto z-10 p-6 md:p-8">

            <div class="flex items-start justify-between pb-4 border-b border-gray-100">
                <div>
                    <span class="inline-block px-3 py-1 bg-brand-50 text-brand-600 rounded-full text-xs font-bold uppercase tracking-wider mb-2">
                        Contact Message Details
                    </span>
                    <h2 class="text-xl font-bold text-gray-900" x-text="activeItem ? activeItem.subject || 'General Inquiry' : ''"></h2>
                    <p class="text-xs text-gray-400 mt-1">
                        Received on <span x-text="activeItem ? activeItem.created_at : ''"></span>
                    </p>
                </div>
                <button type="button" @click="detailModalOpen = false" 
                        class="p-2 text-gray-400 hover:text-gray-600 rounded-xl hover:bg-gray-100 transition-colors cursor-pointer">
                    <i class="ph ph-x text-xl"></i>
                </button>
            </div>

            <!-- Sender Information Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 py-5 border-b border-gray-100 bg-gray-50/50 -mx-6 md:-mx-8 px-6 md:px-8">
                <div>
                    <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">Sender Name</span>
                    <span class="font-bold text-gray-900 text-sm block mt-0.5" x-text="activeItem ? activeItem.name : ''"></span>
                </div>
                <div>
                    <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">Phone Number</span>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="font-mono font-bold text-gray-900 text-sm" x-text="activeItem ? activeItem.phone : ''"></span>
                        <template x-if="activeItem && activeItem.phone">
                            <a :href="'https://wa.me/' + activeItem.phone.replace(/[^0-9]/g, '')" target="_blank"
                               class="text-xs text-green-600 hover:underline flex items-center gap-0.5 cursor-pointer font-medium">
                                <i class="ph ph-whatsapp-logo"></i> WhatsApp
                            </a>
                        </template>
                    </div>
                </div>
                <div>
                    <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">Email Address</span>
                    <span class="text-gray-700 text-sm block mt-0.5" x-text="activeItem && activeItem.email ? activeItem.email : 'Not provided'"></span>
                </div>
                <div>
                    <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">IP Address & Network</span>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="font-mono text-xs font-bold text-brand-600" x-text="activeItem ? activeItem.ip_address : ''"></span>
                        <template x-if="activeItem && activeItem.ip_address">
                            <a :href="'https://ipinfo.io/' + activeItem.ip_address" target="_blank" rel="noopener noreferrer"
                               class="text-xs text-gray-500 hover:text-brand-600 hover:underline flex items-center gap-0.5 cursor-pointer">
                                <i class="ph ph-arrow-square-out text-xs"></i> Lookup
                            </a>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Full Message Body -->
            <div class="py-5">
                <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Message Content</span>
                <div class="p-4.5 rounded-xl bg-gray-50 border border-gray-200 text-sm text-gray-800 leading-relaxed whitespace-pre-wrap selection:bg-brand-100"
                     x-text="activeItem ? activeItem.message : ''">
                </div>
            </div>

            <!-- Status & Internal Admin Notes Form -->
            <form :action="'/admin/dashboard/contact-messages/' + (activeItem ? activeItem.id : 0) + '/status'" method="POST" class="pt-4 border-t border-gray-100 space-y-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Change Status</label>
                        <select name="status" x-model="statusForm" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 cursor-pointer">
                            <option value="new">New (Unread)</option>
                            <option value="read">Read / Reviewed</option>
                            <option value="replied">Replied (Completed)</option>
                        </select>
                    </div>

                    <div class="sm:pt-5">
                        <button type="submit" 
                                class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-gray-900 hover:bg-black text-white font-bold text-sm shadow-sm transition-colors cursor-pointer flex items-center justify-center gap-2">
                            <i class="ph ph-floppy-disk"></i>
                            <span>Save Status & Notes</span>
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Internal Admin Notes (Optional)</label>
                    <textarea name="admin_notes" x-model="adminNotes" rows="2" placeholder="e.g. Called customer at 3pm, inquiry resolved."
                              class="w-full bg-white border border-gray-200 rounded-xl p-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"></textarea>
                </div>
            </form>

            <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-between">
                <!-- Direct Blacklist Action -->
                <template x-if="activeItem && activeItem.ip_address">
                    <a :href="'{{ route('admin.blacklist.index') }}?type=ip&value=' + encodeURIComponent(activeItem.ip_address)"
                       class="text-xs text-rose-600 hover:text-rose-700 hover:underline flex items-center gap-1 font-semibold cursor-pointer">
                        <i class="ph ph-shield-warning"></i>
                        <span>Block This IP in Blacklist</span>
                    </a>
                </template>

                <button type="button" @click="detailModalOpen = false" 
                        class="px-5 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold transition-colors cursor-pointer ml-auto">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- DELETE CONFIRMATION MODAL -->
    <div x-show="deleteModalOpen" style="display: none;" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div x-show="deleteModalOpen" x-transition.opacity 
             class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm cursor-pointer"
             @click="deleteModalOpen = false"></div>

        <!-- Modal Dialog -->
        <div x-show="deleteModalOpen" 
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="relative bg-white rounded-2xl shadow-xl border border-gray-100 max-w-md w-full z-10 p-6 text-center">

            <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 mx-auto flex items-center justify-center text-2xl mb-4">
                <i class="ph ph-trash"></i>
            </div>

            <h3 class="text-lg font-bold text-gray-900">Delete Contact Message?</h3>
            <p class="text-sm text-gray-500 mt-2">
                Are you sure you want to delete the message from <strong class="text-gray-900" x-text="deletingItem ? deletingItem.name : ''"></strong>? This action cannot be undone.
            </p>

            <form :action="'/admin/dashboard/contact-messages/' + (deletingItem ? deletingItem.id : 0)" method="POST" class="mt-6 flex items-center justify-center gap-3">
                @csrf
                @method('DELETE')

                <button type="button" @click="deleteModalOpen = false" 
                        class="px-5 py-2.5 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold transition-colors cursor-pointer">
                    Cancel
                </button>

                <button type="submit" 
                        class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-sm font-bold shadow-sm transition-colors cursor-pointer">
                    Yes, Delete
                </button>
            </form>
        </div>
    </div>

</div>
@endsection
