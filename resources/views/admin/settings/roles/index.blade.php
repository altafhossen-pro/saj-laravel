@extends('layouts.admin')

@section('title', 'Role Based Access Control (RBAC) - Admin Panel')
@section('header_title', 'Access Control (RBAC)')

@section('content')
<div x-data="roleManager({{ Js::from($roles) }}, {{ Js::from($permissionGroups) }})" class="w-full mx-auto space-y-6">

    <!-- Top Header & Breadcrumb -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="{{ route('admin.settings.index') }}" class="text-xs font-semibold text-gray-500 hover:text-brand-600 inline-flex items-center gap-1.5 mb-1.5 transition-colors">
                <i class="ph ph-arrow-left"></i> Back to Settings
            </a>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Role Based Access Control (RBAC)</h1>
            <p class="text-xs text-gray-500 mt-0.5">Manage administrative roles, customer roles, and configure granular permissions across the system.</p>
        </div>
        <div class="flex items-center gap-2.5 shrink-0">
            <a href="{{ route('admin.customers.staff') }}" class="px-3.5 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 rounded-xl transition-colors inline-flex items-center gap-1.5 shadow-2xs">
                <i class="ph ph-users text-sm"></i>
                <span>Manage Staff</span>
            </a>
            <button type="button" 
                    @click="openCreateModal()" 
                    class="px-4 py-2 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-xl shadow-xs transition-colors inline-flex items-center gap-1.5 cursor-pointer">
                <i class="ph-bold ph-plus"></i>
                <span>Create Role</span>
            </button>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="bg-emerald-50 text-emerald-800 p-4 rounded-2xl flex items-center gap-3 border border-emerald-200/80 shadow-2xs" x-data="{ show: true }" x-show="show" x-transition>
            <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                <i class="ph-bold ph-check text-base"></i>
            </div>
            <div class="flex-1 text-xs font-medium">{{ session('success') }}</div>
            <button @click="show = false" class="text-emerald-500 hover:text-emerald-800 p-1 cursor-pointer">
                <i class="ph ph-x text-base"></i>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 text-red-800 p-4 rounded-2xl flex items-center gap-3 border border-red-200/80 shadow-2xs" x-data="{ show: true }" x-show="show" x-transition>
            <div class="w-8 h-8 rounded-xl bg-red-100 text-red-700 flex items-center justify-center shrink-0">
                <i class="ph-bold ph-warning-circle text-base"></i>
            </div>
            <div class="flex-1 text-xs font-medium">{{ session('error') }}</div>
            <button @click="show = false" class="text-red-500 hover:text-red-800 p-1 cursor-pointer">
                <i class="ph ph-x text-base"></i>
            </button>
        </div>
    @endif

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-2xl shrink-0">
                <i class="ph ph-shield-check"></i>
            </div>
            <div>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">All Roles</span>
                <div class="text-2xl font-extrabold text-gray-900 mt-0.5">{{ $roles->count() }}</div>
                <span class="text-[11px] text-gray-500">Configured in system</span>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center text-2xl shrink-0">
                <i class="ph ph-key"></i>
            </div>
            <div>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Staff Roles</span>
                <div class="text-2xl font-extrabold text-brand-700 mt-0.5">{{ $roles->where('is_customer', false)->count() }}</div>
                <span class="text-[11px] text-gray-500">With administrative access</span>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl shrink-0">
                <i class="ph ph-shopping-bag"></i>
            </div>
            <div>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Customer Roles</span>
                <div class="text-2xl font-extrabold text-emerald-700 mt-0.5">{{ $roles->where('is_customer', true)->count() }}</div>
                <span class="text-[11px] text-gray-500">Front store only (No admin access)</span>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="flex items-center gap-2 border-b border-gray-200/80 pb-1">
        <button type="button" 
                @click="filterTab = 'all'" 
                :class="filterTab === 'all' ? 'bg-gray-900 text-white shadow-xs' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer">
            All Roles ({{ $roles->count() }})
        </button>
        <button type="button" 
                @click="filterTab = 'staff'" 
                :class="filterTab === 'staff' ? 'bg-brand-600 text-white shadow-xs' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer inline-flex items-center gap-1.5">
            <i class="ph-bold ph-shield-check"></i>
            Staff Roles ({{ $roles->where('is_customer', false)->count() }})
        </button>
        <button type="button" 
                @click="filterTab = 'customer'" 
                :class="filterTab === 'customer' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer inline-flex items-center gap-1.5">
            <i class="ph-bold ph-shopping-bag"></i>
            Customer Roles ({{ $roles->where('is_customer', true)->count() }})
        </button>
    </div>

    <!-- Roles Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($roles as $role)
            <div x-show="shouldShowRole({{ $role->is_customer ? 'true' : 'false' }})" 
                 x-transition 
                 class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 sm:p-6 flex flex-col justify-between hover:border-slate-300 transition-all duration-200 group">
                
                <div class="space-y-3.5">
                    <!-- Card Top Header -->
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="text-base font-bold text-gray-900 capitalize">
                                    {{ str_replace(['_', '-'], ' ', $role->name) }}
                                </h3>
                                <code class="text-[11px] font-mono px-2 py-0.5 rounded bg-gray-100 text-gray-600 border border-gray-200">
                                    {{ $role->name }}
                                </code>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-gray-500 mt-1">
                                <span class="flex items-center gap-1">
                                    <i class="ph ph-user"></i>
                                    {{ $role->users_count }} {{ Str::plural('User', $role->users_count) }}
                                </span>
                            </div>
                        </div>

                        <!-- Role Type Badge -->
                        <div>
                            @if($role->name === 'super_admin')
                                <span class="text-[10px] uppercase font-bold tracking-wider px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 border border-amber-200 inline-flex items-center gap-1 shrink-0">
                                    <i class="ph-bold ph-crown text-xs"></i> Super Admin
                                </span>
                            @elseif($role->is_customer)
                                <span class="text-[10px] uppercase font-bold tracking-wider px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200 inline-flex items-center gap-1 shrink-0">
                                    <i class="ph-bold ph-shopping-bag text-xs"></i> Customer Role
                                </span>
                            @else
                                <span class="text-[10px] uppercase font-bold tracking-wider px-2.5 py-1 rounded-full bg-indigo-100 text-indigo-800 border border-indigo-200 inline-flex items-center gap-1 shrink-0">
                                    <i class="ph-bold ph-shield-check text-xs"></i> Staff Role
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Permissions Summary / Customer Info -->
                    <div class="pt-2 border-t border-gray-100">
                        @if($role->is_customer)
                            <div class="p-3 bg-emerald-50/60 border border-emerald-200/70 rounded-xl text-xs text-emerald-900 flex items-center gap-2.5">
                                <i class="ph-bold ph-shield-slash text-emerald-600 text-base shrink-0"></i>
                                <span><strong>Customer Restricted:</strong> Store shoppers only. No administrative panel permissions can be assigned to this role.</span>
                            </div>
                        @elseif($role->name === 'super_admin')
                            <div class="p-3 bg-amber-50/60 border border-amber-200/70 rounded-xl text-xs text-amber-900 flex items-center gap-2.5">
                                <i class="ph-bold ph-lightning text-amber-600 text-base shrink-0"></i>
                                <span><strong>Full System Access:</strong> Unrestricted super administrator privileges across all features and settings.</span>
                            </div>
                        @else
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-bold text-gray-700">Permissions ({{ $role->permissions->count() }} of {{ $allPermissions->count() }}):</span>
                                    <span class="text-[11px] text-gray-400 font-mono">{{ round(($role->permissions->count() / max(1, $allPermissions->count())) * 100) }}% coverage</span>
                                </div>
                                @if($role->permissions->count() === 0)
                                    <div class="p-2.5 bg-gray-50 border border-dashed border-gray-200 rounded-xl text-xs text-gray-400 italic">
                                        No permissions assigned yet. Click "Edit Role" to grant access.
                                    </div>
                                @else
                                    <div class="flex flex-wrap gap-1.5 max-h-20 overflow-y-auto pt-1">
                                        @foreach($role->permissions as $perm)
                                            <span class="text-[10px] font-medium px-2 py-0.5 rounded bg-slate-100 text-slate-700 border border-slate-200">
                                                {{ str_replace('_', ' ', $perm->name) }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Card Actions Footer -->
                <div class="pt-4 mt-4 border-t border-gray-100 flex items-center justify-between">
                    <span class="text-[11px] text-gray-400">
                        @if($role->is_customer)
                            Shopper Account Role
                        @elseif($role->name === 'super_admin')
                            System Root Role
                        @else
                            Staff Management Role
                        @endif
                    </span>

                    <div class="flex items-center gap-2">
                        <button type="button" 
                                @click="openEditModal({{ Js::from($role) }})" 
                                class="px-3 py-1.5 text-xs font-bold text-gray-700 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-xl transition-colors inline-flex items-center gap-1.5 cursor-pointer">
                            <i class="ph ph-pencil-simple text-sm"></i>
                            <span>Edit Role</span>
                        </button>

                        @if($role->name !== 'super_admin')
                            <form action="{{ route('admin.settings.roles.destroy', $role->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete role {{ $role->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition-colors cursor-pointer" 
                                        title="Delete role">
                                    <i class="ph ph-trash text-sm"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Create / Edit Role Modal -->
    <div x-show="isModalOpen" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-hidden bg-gray-950/70 backdrop-blur-xs">

        <div @click.away="closeModal()" 
             class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl border border-gray-100 overflow-hidden max-h-[88vh] flex flex-col">
            
            <form :action="formAction" method="POST" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                @csrf
                <template x-if="modalMode === 'edit'">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <!-- Fixed Modal Header -->
                <div class="px-6 py-4.5 border-b border-gray-100 flex items-center justify-between shrink-0 bg-slate-50/90">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-xl"
                             :class="isCustomer ? 'bg-emerald-100 text-emerald-700' : 'bg-brand-100 text-brand-700'">
                            <i :class="isCustomer ? 'ph-bold ph-shopping-bag' : 'ph-bold ph-shield-check'"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 leading-tight" x-text="modalMode === 'create' ? 'Create New Role' : 'Edit Role Configuration'"></h3>
                            <p class="text-xs text-gray-500" x-text="isCustomer ? 'Configuring Customer Account Role' : 'Configuring Administrative Staff Role'"></p>
                        </div>
                    </div>
                    <button type="button" @click="closeModal()" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-600 flex items-center justify-center transition-colors cursor-pointer">
                        <i class="ph ph-x text-base font-bold"></i>
                    </button>
                </div>

                <!-- Scrollable Modal Form Body -->
                <div class="flex-1 overflow-y-auto p-6 space-y-5 min-h-0">

                <!-- Role Name Input -->
                <div>
                    <label for="modal_role_name" class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                        Role Name / Identifier <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="modal_role_name" 
                           name="name" 
                           x-model="roleName" 
                           required 
                           :disabled="roleName === 'super_admin' && modalMode === 'edit'"
                           placeholder="e.g. Order Manager, Wholesale Customer, Support Staff" 
                           class="w-full text-sm px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:border-brand-500 focus:ring-1 focus:ring-brand-500/20 text-gray-900 disabled:opacity-60 disabled:bg-gray-100">
                    <p class="text-[11px] text-gray-400 mt-1">Name will be automatically saved in clean format (e.g. <code>wholesale_customer</code>).</p>
                </div>

                <!-- Customer Role Checkmark / Toggle Card -->
                <div class="p-4 rounded-2xl border transition-all duration-200" 
                     :class="isCustomer ? 'bg-emerald-50/70 border-emerald-300 ring-2 ring-emerald-500/20' : 'bg-gray-50/80 border-gray-200'">
                    <label class="flex items-start gap-3.5 cursor-pointer select-none">
                        <input type="checkbox" 
                               name="is_customer" 
                               value="1" 
                               x-model="isCustomer"
                               @change="onCustomerToggle()"
                               :disabled="roleName === 'super_admin' && modalMode === 'edit'"
                               class="mt-1 w-5 h-5 rounded-lg border-gray-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-bold text-gray-900">Customer Role</span>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full" 
                                      :class="isCustomer ? 'bg-emerald-200 text-emerald-800' : 'bg-gray-200 text-gray-700'" 
                                      x-text="isCustomer ? 'Customer Account' : 'Staff / Admin'"></span>
                            </div>
                            <p class="text-xs text-gray-600 mt-0.5 leading-relaxed">
                                Check this if this role is for retail customers or shoppers. Customer roles <strong>cannot have administrative permissions</strong>.
                            </p>
                        </div>
                    </label>
                </div>

                <!-- Case 1: Customer Role Notice (When Customer Checkmark is ON) -->
                <div x-show="isCustomer" x-transition class="p-5 bg-emerald-50/50 border border-emerald-200/70 rounded-2xl text-center space-y-2">
                    <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-700 mx-auto flex items-center justify-center text-lg">
                        <i class="ph-bold ph-shopping-bag"></i>
                    </div>
                    <h4 class="text-sm font-bold text-emerald-950">Administrative Permissions Disabled</h4>
                    <p class="text-xs text-emerald-800 max-w-md mx-auto leading-relaxed">
                        This is designated as a <strong>Customer Role</strong>. Customers only access the front-end store and their personal account dashboard. They are never granted admin panel permissions.
                    </p>
                </div>

                <!-- Case 2: Permissions Matrix (When Customer Checkmark is OFF) -->
                <div x-show="!isCustomer" x-transition class="space-y-4">
                    <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                        <div>
                            <h4 class="text-sm font-bold text-gray-900">Administrative Permissions</h4>
                            <p class="text-xs text-gray-500">Selected permissions (<span class="font-bold text-brand-600" x-text="selectedPermissions.length"></span>) will be authorized for this role.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="selectAllPermissions()" class="text-xs font-bold text-brand-600 hover:text-brand-800 hover:underline cursor-pointer">
                                Select All
                            </button>
                            <span class="text-gray-300">|</span>
                            <button type="button" @click="deselectAllPermissions()" class="text-xs font-medium text-gray-500 hover:text-gray-800 hover:underline cursor-pointer">
                                Clear
                            </button>
                        </div>
                    </div>

                    <!-- Super Admin notice inside modal -->
                    <template x-if="roleName === 'super_admin' && modalMode === 'edit'">
                        <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800 flex items-center gap-2">
                            <i class="ph-bold ph-crown text-base text-amber-600 shrink-0"></i>
                            <span>The super_admin role automatically possesses root access to all permissions and modules.</span>
                        </div>
                    </template>

                    <!-- Grouped Permission Blocks -->
                    <div class="space-y-3">
                        <template x-for="(group, groupTitle) in permissionGroups" :key="groupTitle">
                            <div class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-2xs">
                                <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200/80 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <i class="ph-bold text-brand-600 text-sm" :class="group.icon || 'ph-shield'"></i>
                                        <span class="text-xs font-bold text-gray-900" x-text="groupTitle"></span>
                                    </div>
                                    <label class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-gray-600 cursor-pointer select-none">
                                        <input type="checkbox" 
                                               :checked="isGroupFullySelected(group.permissions)" 
                                               @change="toggleGroup(group.permissions, $event.target.checked)"
                                               class="rounded border-gray-300 text-brand-600 focus:ring-brand-500 cursor-pointer">
                                        <span>Select Group</span>
                                    </label>
                                </div>
                                <div class="p-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <template x-for="(desc, permKey) in group.permissions" :key="permKey">
                                        <label class="flex items-start gap-2.5 p-2 rounded-xl hover:bg-slate-50 transition-colors border border-transparent hover:border-slate-200 cursor-pointer">
                                            <input type="checkbox" 
                                                   name="permissions[]" 
                                                   :value="permKey" 
                                                   x-model="selectedPermissions"
                                                   class="mt-0.5 rounded border-gray-300 text-brand-600 focus:ring-brand-500 cursor-pointer">
                                            <div class="min-w-0">
                                                <div class="text-xs font-bold text-gray-800 capitalize" x-text="permKey.replace(/_/g, ' ')"></div>
                                                <div class="text-[11px] text-gray-500 leading-snug mt-0.5" x-text="desc"></div>
                                            </div>
                                        </label>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                </div> <!-- /End Scrollable Modal Form Body -->

                <!-- Fixed Modal Actions Footer (Always Pinned at bottom) -->
                <div class="px-6 py-4 bg-slate-50 border-t border-gray-200 flex items-center justify-end gap-2.5 shrink-0 sticky bottom-0 z-20">
                    <button type="button" @click="closeModal()" class="px-4 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-200 hover:bg-gray-100 rounded-xl transition-colors cursor-pointer shadow-2xs">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-xl shadow-xs transition-colors inline-flex items-center gap-1.5 cursor-pointer">
                        <i class="ph-bold ph-check"></i>
                        <span x-text="modalMode === 'create' ? 'Create Role' : 'Save Changes'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function roleManager(initialRoles, permissionGroups) {
        return {
            roles: initialRoles || [],
            permissionGroups: permissionGroups || {},
            filterTab: 'all',
            isModalOpen: false,
            modalMode: 'create',
            roleId: null,
            roleName: '',
            isCustomer: false,
            selectedPermissions: [],
            formAction: '{{ route('admin.settings.roles.store') }}',

            shouldShowRole(isCust) {
                if (this.filterTab === 'all') return true;
                if (this.filterTab === 'staff') return !isCust;
                if (this.filterTab === 'customer') return isCust;
                return true;
            },

            openCreateModal() {
                this.modalMode = 'create';
                this.roleId = null;
                this.roleName = '';
                this.isCustomer = false;
                this.selectedPermissions = [];
                this.formAction = '{{ route('admin.settings.roles.store') }}';
                this.isModalOpen = true;
            },

            openEditModal(role) {
                this.modalMode = 'edit';
                this.roleId = role.id;
                this.roleName = role.name;
                this.isCustomer = !!role.is_customer;
                this.selectedPermissions = (role.permissions || []).map(p => p.name);
                this.formAction = `/admin/dashboard/settings/roles/${role.id}`;
                this.isModalOpen = true;
            },

            closeModal() {
                this.isModalOpen = false;
            },

            onCustomerToggle() {
                if (this.isCustomer) {
                    // Instantly wipe any permissions when customer role is checked
                    this.selectedPermissions = [];
                }
            },

            isGroupFullySelected(groupPermissions) {
                const keys = Object.keys(groupPermissions || {});
                if (keys.length === 0) return false;
                return keys.every(k => this.selectedPermissions.includes(k));
            },

            toggleGroup(groupPermissions, shouldSelect) {
                const keys = Object.keys(groupPermissions || {});
                if (shouldSelect) {
                    keys.forEach(k => {
                        if (!this.selectedPermissions.includes(k)) {
                            this.selectedPermissions.push(k);
                        }
                    });
                } else {
                    this.selectedPermissions = this.selectedPermissions.filter(p => !keys.includes(p));
                }
            },

            selectAllPermissions() {
                const all = [];
                Object.values(this.permissionGroups).forEach(group => {
                    Object.keys(group.permissions || {}).forEach(k => {
                        if (!all.includes(k)) all.push(k);
                    });
                });
                this.selectedPermissions = all;
            },

            deselectAllPermissions() {
                this.selectedPermissions = [];
            }
        };
    }
</script>
@endsection
