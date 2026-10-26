@extends('layouts.admin')

@section('title', (isset($activeTab) && $activeTab == 'staff' ? 'Staff' : 'Customers') . ' - Admin Panel')
@section('header_title', isset($activeTab) && $activeTab == 'staff' ? 'Staff' : 'Customers')

@section('content')
    <div x-data="customerManager('{{ isset($activeTab) ? $activeTab : 'customers' }}')">
        <div class="mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ isset($activeTab) && $activeTab == 'staff' ? 'All Staff' : 'All Customers' }}</h1>
                <p class="text-sm text-gray-500 mt-1">Manage your {{ isset($activeTab) && $activeTab == 'staff' ? 'staff members' : 'customers' }} and their roles.</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden relative">

            <!-- Loading Overlay -->
            <div x-show="loading" class="absolute inset-0 bg-white/60 backdrop-blur-[2px] z-10 flex items-center justify-center" style="display: none;">
                <i class="ph ph-spinner animate-spin text-4xl text-brand-600"></i>
            </div>

            <!-- Filter Bar -->
            <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row gap-4 justify-between items-center">
                <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                    <div class="relative w-full sm:w-64">
                        <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" x-model.debounce.500ms="search" placeholder="Search by name, phone, email..." class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-white">
                    </div>
                    <select x-show="tab === 'staff'" x-model="role" class="py-2 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-white min-w-[120px]">
                        <option value="">All Roles</option>
                        <option value="admin">Admin</option>
                        <option value="manager">Manager</option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-gray-50 text-gray-500 font-medium border-b border-gray-100 uppercase text-xs tracking-wider">
                        <tr>
                            <th class="px-6 py-4">ID</th>
                            <th class="px-6 py-4">Customer Info</th>
                            <th class="px-6 py-4">Phone</th>
                            <th class="px-6 py-4">Role</th>
                            <th class="px-6 py-4">Joined</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="customer in customers" :key="customer.id">
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-gray-500" x-text="'#' + customer.id"></td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <template x-if="customer.avatar">
                                            <a :href="'/admin/dashboard/customers/' + customer.id">
                                                <img :src="customer.avatar.startsWith('http') ? customer.avatar : (customer.avatar.startsWith('/storage') ? customer.avatar : '/storage/' + customer.avatar)" class="w-8 h-8 rounded-full object-cover">
                                            </a>
                                        </template>
                                        <template x-if="!customer.avatar">
                                            <a :href="'/admin/dashboard/customers/' + customer.id" class="w-8 h-8 rounded-full bg-brand-100 flex items-center justify-center text-brand-600 font-bold text-xs" x-text="customer.name.charAt(0).toUpperCase()"></a>
                                        </template>
                                        <div>
                                            <a :href="'/admin/dashboard/customers/' + customer.id" class="font-medium text-gray-900 hover:text-brand-600 transition-colors block" x-text="customer.name"></a>
                                            <div class="text-xs text-gray-500" x-text="customer.email || 'No email'"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-gray-600" x-text="customer.phone || 'N/A'"></td>
                                <td class="px-6 py-4">
                                    <span x-show="customer.display_role === 'super admin' || customer.display_role === 'admin'" class="px-2.5 py-1 rounded-full text-xs font-semibold bg-brand-100 text-brand-700 capitalize" x-text="customer.display_role"></span>
                                    <span x-show="customer.display_role === 'user'" class="px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 capitalize" x-text="customer.display_role"></span>
                                    <span x-show="customer.display_role !== 'super admin' && customer.display_role !== 'admin' && customer.display_role !== 'user'" class="px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700 capitalize" x-text="customer.display_role"></span>
                                </td>
                                <td class="px-6 py-4 text-gray-500" x-text="new Date(customer.created_at).toLocaleDateString()"></td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a :href="'/admin/dashboard/customers/' + customer.id"
                                           class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-brand-600 hover:bg-brand-50 transition-colors"
                                           title="View Customer Details">
                                            <i class="ph ph-eye text-lg"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="customers.length === 0 && !loading" style="display:none;">
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="ph ph-users text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-base font-medium text-gray-900">No customers found</p>
                                    <p class="text-sm mt-1">Try adjusting your search or filter.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Controls -->
            <div x-show="pagination.last_page > 1" class="px-6 py-4 border-t border-gray-100" style="display:none;" x-html="pagination_html" x-ref="paginationContainer">
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('customerManager', (activeTab = 'customers') => ({
            customers: [],
            loading: false,
            search: '',
            role: '',
            tab: activeTab,
            page: 1,
            pagination: {},
            pagination_html: '',

            init() {
                this.fetchCustomers();
                this.$watch('search', () => { this.page = 1; this.fetchCustomers(); });
                this.$watch('role', () => { this.page = 1; this.fetchCustomers(); });
                
                // Intercept pagination clicks
                this.$nextTick(() => {
                    this.$refs.paginationContainer.addEventListener('click', (e) => {
                        const link = e.target.closest('nav[role="navigation"] a, nav[aria-label="Pagination Navigation"] a, .page-link');
                        if (link) {
                            e.preventDefault();
                            this.fetchCustomers(link.href);
                        }
                    });
                });
            },

            async fetchCustomers(overrideUrl = null) {
                this.loading = true;
                let url = overrideUrl;
                if (!url) {
                    url = `/admin/dashboard/api/customers?page=${this.page}&tab=${this.tab}`;
                    if (this.search) url += `&search=${encodeURIComponent(this.search)}`;
                    if (this.role !== '') url += `&role=${this.role}`;
                } else {
                    // Append tab parameter if not present in pagination links
                    const urlObj = new URL(url, window.location.origin);
                    urlObj.searchParams.set('tab', this.tab);
                    if (this.search) urlObj.searchParams.set('search', this.search);
                    if (this.role !== '') urlObj.searchParams.set('role', this.role);
                    url = urlObj.toString();
                }

                try {
                    const response = await fetch(url);
                    const data = await response.json();
                    this.customers = data.customers;
                    this.pagination = data.pagination;
                    this.pagination_html = data.pagination_html;
                    this.page = data.pagination.current_page;
                } catch (error) {
                    console.error('Error fetching customers:', error);
                } finally {
                    this.loading = false;
                }
            }
        }));
    });
</script>
@endpush
