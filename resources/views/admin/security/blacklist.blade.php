@extends('layouts.admin')

@section('header_title', 'Blacklist & Security')

@section('content')
<div class="space-y-6" x-data="{ 
    activeTab: '{{ $tab }}',
    modalOpen: false,
    editModalOpen: false,
    deleteModalOpen: false,
    blockType: 'ip',
    durationType: 'permanent',
    editingItem: null,
    deletingItem: null,
    editValue: '',
    editReason: '',
    editMessage: '',
    editDurationType: 'keep',
    prefill(type, value) {
        this.blockType = type;
        this.modalOpen = true;
        this.$nextTick(() => {
            document.getElementById('blacklistValue').value = value || '';
        });
    },
    openEdit(item) {
        this.editingItem = item;
        this.editValue = item.value || '';
        this.editReason = item.reason || '';
        this.editMessage = item.custom_message || '';
        this.editDurationType = item.expires_at ? 'keep' : 'permanent';
        this.editModalOpen = true;
    },
    confirmDelete(item) {
        this.deletingItem = item;
        this.deleteModalOpen = true;
    }
}">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Blacklist & Ban Rules</h1>
            <p class="text-sm text-gray-500 mt-1">Restrict malicious visitors by Single IP, Subnet CIDR (e.g. /24), or Customer Phone number.</p>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            <!-- Current Admin IP Badge with link to ipinfo.io -->
            @php
                $myIp = request()->ip() ?? '127.0.0.1';
            @endphp
            <div class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-white border border-gray-200 text-xs shadow-xs">
                <span class="text-gray-400 font-medium">Your Current IP:</span>
                <a href="https://ipinfo.io/{{ $myIp }}" target="_blank" rel="noopener noreferrer" 
                   title="View IP geolocation & network info on ipinfo.io"
                   class="font-mono font-bold text-brand-600 hover:text-brand-700 hover:underline flex items-center gap-1">
                    <span>{{ $myIp }}</span>
                    <i class="ph ph-arrow-square-out text-sm"></i>
                </a>
                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Whitelisted</span>
            </div>

            <button @click="modalOpen = true; blockType = activeTab" 
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold text-xs shadow-sm transition-colors cursor-pointer self-start sm:self-auto">
                <i class="ph ph-plus-circle text-base"></i>
                <span>Add New Block Rule</span>
            </button>
        </div>
    </div>

    <!-- Alert Banners -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-semibold flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="ph ph-check-circle text-lg text-emerald-600"></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs font-semibold flex items-center gap-2">
            <i class="ph ph-warning-circle text-lg text-rose-600"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Top Counter Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-2xl shrink-0">
                <i class="ph ph-globe"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium">Active IP & CIDR Blocks</p>
                <h3 class="text-xl font-bold text-gray-900">{{ number_format($activeIpCount) }}</h3>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-2xl shrink-0">
                <i class="ph ph-phone-disconnect"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium">Active Phone Blacklists</p>
                <h3 class="text-xl font-bold text-gray-900">{{ number_format($activePhoneCount) }}</h3>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-2xl shrink-0">
                <i class="ph ph-shield-check"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium">Firewall Engine</p>
                <h3 class="text-sm font-bold text-emerald-600 flex items-center gap-1.5 mt-0.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Protected & SuperAdmin Safe
                </h3>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs (IP vs Phone) -->
    <div class="border-b border-gray-200">
        <nav class="flex space-x-6">
            <button @click="activeTab = 'ip'" 
                    :class="activeTab === 'ip' ? 'border-brand-600 text-brand-600 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-3 px-1 border-b-2 text-sm flex items-center gap-2 cursor-pointer transition-colors">
                <i class="ph ph-globe text-base"></i>
                <span>IP & CIDR Blacklist</span>
                <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600" x-text="'{{ $ipList->total() }}'"></span>
            </button>

            <button @click="activeTab = 'phone'" 
                    :class="activeTab === 'phone' ? 'border-brand-600 text-brand-600 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-3 px-1 border-b-2 text-sm flex items-center gap-2 cursor-pointer transition-colors">
                <i class="ph ph-phone text-base"></i>
                <span>Phone Blacklist (11-digit)</span>
                <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600" x-text="'{{ $phoneList->total() }}'"></span>
            </button>
        </nav>
    </div>

    <!-- TAB 1: IP / CIDR Blacklist Table -->
    <div x-show="activeTab === 'ip'" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h3 class="font-bold text-gray-900">Restricted IP Addresses & Subnets</h3>
                <p class="text-xs text-gray-500 mt-0.5">Blocked visitors see a dedicated custom security page on storefront; Super Admin access is never blocked.</p>
            </div>
            <form method="GET" action="{{ route('admin.blacklist.index') }}" class="flex items-center gap-2">
                <input type="hidden" name="tab" value="ip">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search IP or reason..." 
                       class="text-xs bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none w-56">
                <button type="submit" class="px-3 py-2 bg-gray-900 hover:bg-black text-white rounded-lg text-xs font-semibold">Filter</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs font-semibold uppercase tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-3.5">IP / CIDR Target</th>
                        <th class="px-6 py-3.5">Reason & Admin Note</th>
                        <th class="px-6 py-3.5">Custom Frontend Message</th>
                        <th class="px-6 py-3.5">Expiration</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse($ipList as $item)
                        <tr class="hover:bg-gray-50/70 transition-colors">
                            <td class="px-6 py-4 font-mono font-bold text-gray-900">
                                <div class="flex items-center gap-2">
                                    @php
                                        $cleanIpForLookup = explode('/', $item->value)[0];
                                    @endphp
                                    <a href="https://ipinfo.io/{{ $cleanIpForLookup }}" target="_blank" rel="noopener noreferrer" 
                                       title="View details on ipinfo.io"
                                       class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-gray-100 hover:bg-brand-50 hover:text-brand-700 transition-colors text-xs text-gray-900 font-mono group">
                                        <span>{{ $item->value }}</span>
                                        <i class="ph ph-arrow-square-out text-gray-400 group-hover:text-brand-600 text-xs"></i>
                                    </a>
                                    @if(str_contains($item->value, '/'))
                                        <span class="text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 bg-blue-50 text-blue-700 border border-blue-200 rounded">Subnet CIDR</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-xs">
                                <div class="text-gray-800 font-medium">{{ $item->reason ?: '— No reason provided —' }}</div>
                                <div class="text-[11px] text-gray-400">By {{ $item->creator->name ?? 'Admin' }} on {{ $item->created_at->format('M d, Y') }}</div>
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-600 max-w-xs truncate" title="{{ $item->custom_message }}">
                                {{ $item->custom_message ?: 'Default restriction notice' }}
                            </td>
                            <td class="px-6 py-4 text-xs">
                                @if($item->expires_at)
                                    @if($item->expires_at->isPast())
                                        <span class="inline-flex items-center gap-1 text-gray-400 font-semibold line-through">
                                            Expired ({{ $item->expires_at->diffForHumans() }})
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-amber-700 font-bold bg-amber-50 px-2 py-0.5 rounded-full">
                                            <i class="ph ph-hourglass text-sm"></i>
                                            Until {{ $item->expires_at->format('M d, Y h:i A') }}
                                        </span>
                                    @endif
                                @else
                                    <span class="inline-flex items-center gap-1 text-rose-700 font-bold bg-rose-50 px-2 py-0.5 rounded-full">
                                        <i class="ph ph-lock-key text-sm"></i> Permanent
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if(!$item->is_active)
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-600">Inactive</span>
                                @elseif($item->expires_at && $item->expires_at->isPast())
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700">Expired</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700">Enforcing</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <button type="button" 
                                        @click="openEdit({{ json_encode($item) }})" 
                                        class="text-xs font-semibold text-brand-600 hover:text-brand-800 cursor-pointer">
                                    Edit
                                </button>
                                <span class="text-gray-300">|</span>
                                <form method="POST" action="{{ route('admin.blacklist.toggle', $item->id) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-xs font-semibold text-gray-600 hover:text-brand-600 cursor-pointer">
                                        {{ $item->is_active ? 'Pause' : 'Activate' }}
                                    </button>
                                </form>
                                <span class="text-gray-300">|</span>
                                <button type="button" 
                                        @click="confirmDelete({{ json_encode($item) }})" 
                                        class="text-xs font-semibold text-rose-600 hover:text-rose-800 cursor-pointer">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                <i class="ph ph-shield-check text-4xl mb-2 text-gray-300 block"></i>
                                No IP addresses currently blacklisted.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($ipList->hasPages())
            <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                {{ $ipList->appends(['tab' => 'ip'])->links() }}
            </div>
        @endif
    </div>

    <!-- TAB 2: Phone Blacklist Table -->
    <div x-show="activeTab === 'phone'" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" style="display: none;">
        <div class="p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h3 class="font-bold text-gray-900">Restricted Phone Numbers (11-Digit)</h3>
                <p class="text-xs text-gray-500 mt-0.5">Manage blacklisted phone numbers. Note: Currently recorded for review without blocking existing checkout.</p>
            </div>
            <form method="GET" action="{{ route('admin.blacklist.index') }}" class="flex items-center gap-2">
                <input type="hidden" name="tab" value="phone">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search phone or reason..." 
                       class="text-xs bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none w-56">
                <button type="submit" class="px-3 py-2 bg-gray-900 hover:bg-black text-white rounded-lg text-xs font-semibold">Filter</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs font-semibold uppercase tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-3.5">Phone Number</th>
                        <th class="px-6 py-3.5">Reason & Note</th>
                        <th class="px-6 py-3.5">Custom Reason / Message</th>
                        <th class="px-6 py-3.5">Expiration</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse($phoneList as $item)
                        <tr class="hover:bg-gray-50/70 transition-colors">
                            <td class="px-6 py-4 font-mono font-bold text-gray-900">
                                <span class="px-2.5 py-1 rounded bg-amber-50 text-amber-900 border border-amber-200 text-xs font-mono">
                                    {{ $item->value }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs">
                                <div class="text-gray-800 font-medium">{{ $item->reason ?: '— No reason provided —' }}</div>
                                <div class="text-[11px] text-gray-400">Added on {{ $item->created_at->format('M d, Y') }}</div>
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-600 max-w-xs truncate">
                                {{ $item->custom_message ?: '—' }}
                            </td>
                            <td class="px-6 py-4 text-xs">
                                @if($item->expires_at)
                                    @if($item->expires_at->isPast())
                                        <span class="text-gray-400 font-semibold line-through">Expired</span>
                                    @else
                                        <span class="text-amber-700 font-bold bg-amber-50 px-2 py-0.5 rounded-full">
                                            Until {{ $item->expires_at->format('M d, Y') }}
                                        </span>
                                    @endif
                                @else
                                    <span class="text-rose-700 font-bold bg-rose-50 px-2 py-0.5 rounded-full">Permanent</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if(!$item->is_active)
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-600">Inactive</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800">Blacklisted</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <button type="button" 
                                        @click="openEdit({{ json_encode($item) }})" 
                                        class="text-xs font-semibold text-brand-600 hover:text-brand-800 cursor-pointer">
                                    Edit
                                </button>
                                <span class="text-gray-300">|</span>
                                <form method="POST" action="{{ route('admin.blacklist.toggle', $item->id) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-xs font-semibold text-gray-600 hover:text-brand-600 cursor-pointer">
                                        {{ $item->is_active ? 'Pause' : 'Activate' }}
                                    </button>
                                </form>
                                <span class="text-gray-300">|</span>
                                <button type="button" 
                                        @click="confirmDelete({{ json_encode($item) }})" 
                                        class="text-xs font-semibold text-rose-600 hover:text-rose-800 cursor-pointer">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                <i class="ph ph-phone text-4xl mb-2 text-gray-300 block"></i>
                                No phone numbers currently blacklisted.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($phoneList->hasPages())
            <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                {{ $phoneList->appends(['tab' => 'phone'])->links() }}
            </div>
        @endif
    </div>

    <!-- Modal: Add New Blacklist Rule -->
    <div x-show="modalOpen" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         style="display: none;">
        
        <div @click.away="modalOpen = false" class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl relative">
            <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-5">
                <div class="flex items-center gap-2">
                    <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg">
                        <i class="ph ph-shield-warning"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Add Blacklist Rule</h3>
                </div>
                <button @click="modalOpen = false" class="text-gray-400 hover:text-gray-600 text-xl p-1 rounded-lg hover:bg-gray-100">
                    <i class="ph ph-x"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.blacklist.store') }}" class="space-y-4">
                @csrf
                
                <!-- Rule Type -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Rule Type</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition-all"
                               :class="blockType === 'ip' ? 'border-brand-600 bg-brand-50/40 text-brand-900 font-bold' : 'border-gray-200 text-gray-600'">
                            <input type="radio" name="type" value="ip" x-model="blockType" class="hidden">
                            <i class="ph ph-globe text-lg"></i>
                            <span class="text-xs">IP / Subnet CIDR</span>
                        </label>
                        <label class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition-all"
                               :class="blockType === 'phone' ? 'border-brand-600 bg-brand-50/40 text-brand-900 font-bold' : 'border-gray-200 text-gray-600'">
                            <input type="radio" name="type" value="phone" x-model="blockType" class="hidden">
                            <i class="ph ph-phone text-lg"></i>
                            <span class="text-xs">Customer Phone</span>
                        </label>
                    </div>
                </div>

                <!-- Target Value Input -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        <span x-text="blockType === 'ip' ? 'IP Address or CIDR Range' : '11-Digit Customer Phone Number'"></span>
                    </label>
                    <input type="text" 
                           id="blacklistValue"
                           name="value" 
                           required 
                           :placeholder="blockType === 'ip' ? 'e.g. 103.14.25.12 or 103.14.25.0/24' : 'e.g. 01712345678'"
                           class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm font-mono focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <p class="text-[11px] text-gray-400 mt-1" x-show="blockType === 'ip'">
                        Tip: You can block an entire IP block using CIDR like <code class="font-bold text-gray-600">192.168.1.0/24</code> or <code class="font-bold text-gray-600">/23</code>.
                    </p>
                </div>

                <!-- Expiration Duration -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Ban Duration / Expiry</label>
                    <div class="grid grid-cols-2 gap-3 mb-2.5">
                        <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer text-xs"
                               :class="durationType === 'permanent' ? 'border-brand-600 bg-brand-50 text-brand-900 font-bold' : 'border-gray-200 text-gray-600'">
                            <input type="radio" name="duration_type" value="permanent" x-model="durationType" class="hidden">
                            <i class="ph ph-infinity text-base"></i>
                            <span>Permanent Ban</span>
                        </label>
                        <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer text-xs"
                               :class="durationType === 'custom' ? 'border-brand-600 bg-brand-50 text-brand-900 font-bold' : 'border-gray-200 text-gray-600'">
                            <input type="radio" name="duration_type" value="custom" x-model="durationType" class="hidden">
                            <i class="ph ph-timer text-base"></i>
                            <span>Set Expiry Timer</span>
                        </label>
                    </div>

                    <!-- Custom Duration Fields -->
                    <div x-show="durationType === 'custom'" class="flex items-center gap-2 pt-1" style="display: none;">
                        <input type="number" 
                               name="duration_value" 
                               value="1" 
                               min="1" 
                               class="w-24 bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm text-center font-bold focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        
                        <select name="duration_unit" class="flex-1 bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                            <option value="minutes">Minutes</option>
                            <option value="hours">Hours</option>
                            <option value="days" selected>Days</option>
                            <option value="months">Months</option>
                            <option value="years">Years</option>
                        </select>
                    </div>
                </div>

                <!-- Reason / Admin Note -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Reason / Admin Note (Internal)</label>
                    <input type="text" 
                           name="reason" 
                           placeholder="e.g. Malicious scraper, Fake COD orders, Spam bot" 
                           class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>

                <!-- Custom Client Facing Message (for IP block page) -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Custom Client Message (Optional)</label>
                    <textarea name="custom_message" 
                              rows="2" 
                              placeholder="Message shown to visitor on restricted screen. Leave empty for default text." 
                              class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none"></textarea>
                </div>

                <div class="pt-3 flex items-center justify-end gap-3 border-t border-gray-100">
                    <button type="button" @click="modalOpen = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-600 hover:bg-gray-100 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-sm transition-colors cursor-pointer">
                        Confirm & Enforce Rule
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Edit Blacklist Rule -->
    <div x-show="editModalOpen" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         style="display: none;">
        
        <div @click.away="editModalOpen = false" class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl relative">
            <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-5">
                <div class="flex items-center gap-2">
                    <div class="w-9 h-9 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center text-lg">
                        <i class="ph ph-pencil-simple"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Edit Blacklist Rule</h3>
                </div>
                <button @click="editModalOpen = false" class="text-gray-400 hover:text-gray-600 text-xl p-1 rounded-lg hover:bg-gray-100">
                    <i class="ph ph-x"></i>
                </button>
            </div>

            <form method="POST" :action="'/admin/dashboard/security/blacklist/' + (editingItem ? editingItem.id : '')" class="space-y-4">
                @csrf
                @method('PUT')
                
                <!-- Target Value Input -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        <span x-text="editingItem && editingItem.type === 'ip' ? 'IP Address or Subnet CIDR' : 'Customer Phone Number'"></span>
                    </label>
                    <input type="text" 
                           name="value" 
                           required 
                           x-model="editValue"
                           class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm font-mono focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>

                <!-- Expiration Duration -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Ban Duration / Expiry</label>
                    <div class="grid grid-cols-3 gap-2 mb-2.5">
                        <label class="flex items-center justify-center gap-1.5 p-2 rounded-xl border cursor-pointer text-xs"
                               :class="editDurationType === 'keep' ? 'border-brand-600 bg-brand-50 text-brand-900 font-bold' : 'border-gray-200 text-gray-600'">
                            <input type="radio" name="duration_type" value="keep" x-model="editDurationType" class="hidden">
                            <span>Keep Current</span>
                        </label>
                        <label class="flex items-center justify-center gap-1.5 p-2 rounded-xl border cursor-pointer text-xs"
                               :class="editDurationType === 'permanent' ? 'border-brand-600 bg-brand-50 text-brand-900 font-bold' : 'border-gray-200 text-gray-600'">
                            <input type="radio" name="duration_type" value="permanent" x-model="editDurationType" class="hidden">
                            <span>Permanent</span>
                        </label>
                        <label class="flex items-center justify-center gap-1.5 p-2 rounded-xl border cursor-pointer text-xs"
                               :class="editDurationType === 'custom' ? 'border-brand-600 bg-brand-50 text-brand-900 font-bold' : 'border-gray-200 text-gray-600'">
                            <input type="radio" name="duration_type" value="custom" x-model="editDurationType" class="hidden">
                            <span>Set New</span>
                        </label>
                    </div>

                    <!-- Custom Duration Fields -->
                    <div x-show="editDurationType === 'custom'" class="flex items-center gap-2 pt-1" style="display: none;">
                        <input type="number" 
                               name="duration_value" 
                               value="1" 
                               min="1" 
                               class="w-24 bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm text-center font-bold focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        
                        <select name="duration_unit" class="flex-1 bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                            <option value="minutes">Minutes</option>
                            <option value="hours">Hours</option>
                            <option value="days" selected>Days</option>
                            <option value="months">Months</option>
                            <option value="years">Years</option>
                        </select>
                    </div>
                </div>

                <!-- Reason / Admin Note -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Reason / Admin Note (Internal)</label>
                    <input type="text" 
                           name="reason" 
                           x-model="editReason"
                           placeholder="e.g. Malicious scraper, Fake COD orders, Spam bot" 
                           class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>

                <!-- Custom Client Facing Message (for IP block page) -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Custom Client Message (Optional)</label>
                    <textarea name="custom_message" 
                              rows="2" 
                              x-model="editMessage"
                              placeholder="Message shown to visitor on restricted screen. Leave empty for default text." 
                              class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none"></textarea>
                </div>

                <div class="pt-3 flex items-center justify-end gap-3 border-t border-gray-100">
                    <button type="button" @click="editModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-600 hover:bg-gray-100 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-sm transition-colors cursor-pointer">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Custom Delete Confirmation -->
    <div x-show="deleteModalOpen" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        
        <div @click.away="deleteModalOpen = false" 
             class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-7 shadow-2xl relative border border-gray-100 text-center">
            
            <!-- Warning Icon -->
            <div class="w-14 h-14 mx-auto rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-3xl mb-4 shadow-xs">
                <i class="ph ph-warning-circle"></i>
            </div>

            <!-- Title & Notice -->
            <h3 class="text-lg font-bold text-gray-900 mb-1">Remove from Blacklist?</h3>
            <p class="text-xs text-gray-500 mb-4 leading-relaxed">
                Are you sure you want to remove this restriction rule? The visitor will regain normal access immediately.
            </p>

            <!-- Target Detail Pill -->
            <div class="bg-gray-50 rounded-2xl p-3 border border-gray-100 mb-6 flex items-center justify-center gap-2">
                <span class="text-xs text-gray-400 font-medium">Target:</span>
                <span class="font-mono text-sm font-bold text-gray-900" x-text="deletingItem ? deletingItem.value : ''"></span>
                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-white border border-gray-200 text-gray-600"
                      x-text="deletingItem ? deletingItem.type : ''"></span>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-center gap-3">
                <button type="button" 
                        @click="deleteModalOpen = false" 
                        class="w-1/2 py-2.5 rounded-xl border border-gray-200 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer">
                    No, Cancel
                </button>

                <form method="POST" 
                      :action="'/admin/dashboard/security/blacklist/' + (deletingItem ? deletingItem.id : '')" 
                      class="w-1/2 inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            class="w-full py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-sm transition-colors cursor-pointer flex items-center justify-center gap-1.5">
                        <i class="ph ph-trash text-sm"></i>
                        <span>Yes, Remove</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
