@extends('layouts.admin')

@section('title', 'Brands - Admin Panel')
@section('header_title', 'Brands')

@section('content')
    <div x-data="brandManager()">
        <div class="mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">All Brands</h1>
                <p class="text-sm text-gray-500 mt-1">Manage product brands and their visibility.</p>
            </div>
            <button @click="openCreateModal()" class="px-4 py-2 bg-brand-600 rounded-lg text-sm font-medium text-white hover:bg-brand-700 flex items-center gap-2 shadow-sm transition-colors cursor-pointer">
                <i class="ph ph-plus"></i> Add Brand
            </button>
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
                        <input type="text" x-model.debounce.500ms="search" placeholder="Search brands..." class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-white">
                    </div>
                    <select x-model="status" class="py-2 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-white min-w-[120px]">
                        <option value="">All Status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-gray-50 text-gray-500 font-medium border-b border-gray-100 uppercase text-xs tracking-wider">
                        <tr>
                            <th class="px-6 py-4">ID</th>
                            <th class="px-6 py-4">Name</th>
                            <th class="px-6 py-4">Sort Order</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="brand in brands" :key="brand.id">
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-gray-500" x-text="brand.id"></td>
                                <td class="px-6 py-4 font-medium text-gray-900" x-text="brand.name"></td>
                                <td class="px-6 py-4 text-gray-500" x-text="brand.sort_order"></td>
                                <td class="px-6 py-4">
                                    <span x-show="brand.is_active" class="px-2.5 py-1 rounded-full text-xs font-semibold bg-brand-100 text-brand-700">Active</span>
                                    <span x-show="!brand.is_active" class="px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">Inactive</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="openEditModal(brand)" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors cursor-pointer" title="Edit">
                                            <i class="ph ph-pencil-simple text-lg"></i>
                                        </button>
                                        <button @click="openDeleteModal(brand)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors cursor-pointer" title="Delete">
                                            <i class="ph ph-trash text-lg"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="brands.length === 0 && !loading" style="display:none;">
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="ph ph-list-dashes text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-base font-medium text-gray-900">No brands found</p>
                                    <p class="text-sm mt-1">Get started by creating a new brand.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Controls -->
            <div x-show="pagination.last_page > 1" class="px-6 py-4 border-t border-gray-100 flex items-center justify-between" style="display:none;">
                <button @click="page--" :disabled="page <= 1" class="px-3 py-1 bg-gray-100 text-gray-700 rounded hover:bg-gray-200 disabled:opacity-50 cursor-pointer disabled:cursor-not-allowed">Previous</button>
                <span class="text-sm text-gray-600">Page <span x-text="page"></span> of <span x-text="pagination.last_page"></span></span>
                <button @click="page++" :disabled="page >= pagination.last_page" class="px-3 py-1 bg-gray-100 text-gray-700 rounded hover:bg-gray-200 disabled:opacity-50 cursor-pointer disabled:cursor-not-allowed">Next</button>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <template x-teleport="body">
            <div x-show="isModalOpen" class="fixed inset-0 z-[100] overflow-y-auto" style="display: none;">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                    <div x-show="isModalOpen" x-transition.opacity class="fixed inset-0 transition-opacity bg-black/50 backdrop-blur-sm" @click="closeModal()"></div>
                    <div x-show="isModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-xl shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                        <div class="absolute top-0 right-0 pt-4 pr-4">
                            <button @click="closeModal()" class="text-gray-400 hover:text-gray-500 focus:outline-none cursor-pointer">
                                <i class="ph ph-x text-xl"></i>
                            </button>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium leading-6 text-gray-900" x-text="isEdit ? 'Edit Brand' : 'Add Brand'"></h3>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                                    <input type="text" x-model="form.name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                                    <input type="number" x-model="form.sort_order" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" id="brand_active" x-model="form.is_active" class="h-4 w-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500">
                                    <label for="brand_active" class="ml-2 block text-sm text-gray-900">Active</label>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5 sm:mt-6 sm:flex sm:flex-row-reverse">
                            <button @click="saveBrand()" :disabled="saving" class="cursor-pointer w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-brand-600 text-base font-medium text-white hover:bg-brand-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                <span x-show="!saving">Save</span>
                                <span x-show="saving">Saving...</span>
                            </button>
                            <button @click="closeModal()" class="cursor-pointer mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Delete Modal -->
        <template x-teleport="body">
            <div x-show="isDeleteModalOpen" class="fixed inset-0 z-[100] overflow-y-auto" style="display: none;">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                    <div x-show="isDeleteModalOpen" x-transition.opacity class="fixed inset-0 transition-opacity bg-black/50 backdrop-blur-sm" @click="closeDeleteModal()"></div>
                    <div x-show="isDeleteModalOpen" class="relative z-10 inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-xl shadow-xl sm:my-8 sm:align-middle sm:max-w-md sm:w-full sm:p-6">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="ph ph-warning text-red-600 text-xl"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Delete Brand</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">Are you sure you want to delete <span class="font-bold" x-text="brandToDelete?.name"></span>? This action cannot be undone.</p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                            <button @click="deleteBrand()" :disabled="saving" class="cursor-pointer w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                <span x-show="!saving">Delete</span>
                                <span x-show="saving">Deleting...</span>
                            </button>
                            <button @click="closeDeleteModal()" class="cursor-pointer mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('brandManager', () => ({
            brands: [],
            loading: false,
            search: '',
            status: '',
            page: 1,
            pagination: {},
            max_sort_order: 0,
            
            isModalOpen: false,
            isDeleteModalOpen: false,
            isEdit: false,
            saving: false,
            
            form: {
                id: null,
                name: '',
                sort_order: 0,
                is_active: true
            },
            
            brandToDelete: null,

            init() {
                this.fetchBrands();
                this.$watch('search', () => { this.page = 1; this.fetchBrands(); });
                this.$watch('status', () => { this.page = 1; this.fetchBrands(); });
                this.$watch('page', () => { this.fetchBrands(); });
            },

            async fetchBrands() {
                this.loading = true;
                let url = `/admin/dashboard/api/brands?page=${this.page}`;
                if (this.search) url += `&search=${encodeURIComponent(this.search)}`;
                if (this.status !== '') url += `&is_active=${this.status}`;

                try {
                    const response = await fetch(url);
                    const data = await response.json();
                    this.brands = data.brands;
                    this.pagination = data.pagination;
                    if(data.max_sort_order !== undefined) {
                        this.max_sort_order = data.max_sort_order;
                    }
                } catch (error) {
                    console.error('Error fetching brands:', error);
                } finally {
                    this.loading = false;
                }
            },

            openCreateModal() {
                this.isEdit = false;
                this.form = { id: null, name: '', sort_order: this.max_sort_order + 1, is_active: true };
                this.isModalOpen = true;
            },

            openEditModal(brand) {
                this.isEdit = true;
                this.form = { ...brand };
                this.isModalOpen = true;
            },

            closeModal() {
                this.isModalOpen = false;
            },

            async saveBrand() {
                if (!this.form.name) return alert('Name is required');
                
                this.saving = true;
                const url = this.isEdit ? `/admin/dashboard/brands/${this.form.id}` : `/admin/dashboard/brands`;
                const method = this.isEdit ? 'PUT' : 'POST';
                
                try {
                    const response = await fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(this.form)
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        this.closeModal();
                        this.fetchBrands();
                    } else {
                        alert(data.message || 'Error saving brand');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred.');
                } finally {
                    this.saving = false;
                }
            },

            openDeleteModal(brand) {
                this.brandToDelete = brand;
                this.isDeleteModalOpen = true;
            },

            closeDeleteModal() {
                this.isDeleteModalOpen = false;
                this.brandToDelete = null;
            },

            async deleteBrand() {
                this.saving = true;
                try {
                    const response = await fetch(`/admin/dashboard/brands/${this.brandToDelete.id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json();
                    if (data.success) {
                        this.closeDeleteModal();
                        this.fetchBrands();
                    }
                } catch (error) {
                    console.error('Error:', error);
                } finally {
                    this.saving = false;
                }
            }
        }));
    });
</script>
@endpush
