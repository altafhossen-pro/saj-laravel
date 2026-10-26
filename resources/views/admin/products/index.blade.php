@extends('layouts.admin')

@section('title', 'All Products - Admin Panel')
@section('header_title', 'Products')

@section('content')
<div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4">
    <div class="max-w-2xl">
        <h1 class="text-3xl font-bold text-gray-900 tracking-tight mb-2">Product Catalog</h1>
        <p class="text-gray-500 leading-relaxed">Manage your store's inventory, pricing, and visibility. Keep your catalog fresh and updated to drive more sales.</p>
    </div>
    <div class="flex items-center gap-3">
        <button class="px-4 py-2.5 bg-white border border-gray-200 text-gray-700 rounded-xl text-sm font-medium hover:bg-gray-50 transition-colors shadow-sm flex items-center gap-2">
            <i class="ph ph-export text-lg"></i> Export
        </button>
        <a href="{{ route('admin.products.create') }}" class="px-5 py-2.5 bg-gradient-to-r from-brand-600 to-brand-500 text-white rounded-xl text-sm font-medium hover:from-brand-700 hover:to-brand-600 transition-all duration-300 shadow-sm shadow-brand-500/20 flex items-center gap-2 transform hover:-translate-y-0.5">
            <i class="ph ph-plus-circle text-lg"></i> Add New Product
        </a>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" x-data="productFilter()">
    <!-- Filter Section -->
    <div class="p-5 border-b border-gray-100 flex flex-wrap items-center justify-between gap-4 bg-gray-50/50">
        <div class="flex items-center gap-3 flex-1 min-w-[300px]">
            <div class="relative flex-1 max-w-md">
                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" x-model="search" @input.debounce.500ms="fetchData()" placeholder="Search products by title..." class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
            </div>
            <select x-model="category_id" @change="fetchData()" class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Table Container -->
    <div class="relative min-h-[400px]">
        <!-- Loading Overlay -->
        <div x-show="loading" class="absolute inset-0 bg-white/60 backdrop-blur-sm z-10 flex items-center justify-center" style="display: none;">
            <i class="ph ph-spinner animate-spin text-3xl text-brand-600"></i>
        </div>
        
        <!-- Table Data -->
        <div id="table-container" x-ref="tableContainer">
            @include('admin.products.partials.table', ['products' => $products])
        </div>
    </div>
</div>

@push('scripts')
<script>
function productFilter() {
    return {
        search: '',
        category_id: '',
        per_page: '{{ $perPage }}',
        loading: false,

        fetchData(url = null) {
            this.loading = true;
            
            // Build query parameters
            let params = new URLSearchParams();
            if (this.search) params.append('search', this.search);
            if (this.category_id) params.append('category_id', this.category_id);
            if (this.per_page) params.append('per_page', this.per_page);

            let fetchUrl = url ? url : `{{ route('admin.products.index') }}?${params.toString()}`;

            fetch(fetchUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.text())
            .then(html => {
                this.$refs.tableContainer.innerHTML = html;
                this.loading = false;
            })
            .catch(error => {
                console.error('Error fetching data:', error);
                this.loading = false;
            });
        },
        
        init() {
            // Intercept pagination clicks
            this.$refs.tableContainer.addEventListener('click', (e) => {
                const link = e.target.closest('nav[role="navigation"] a, nav[aria-label="Pagination Navigation"] a, .page-link');
                if (link) {
                    e.preventDefault();
                    this.fetchData(link.href);
                }
            });
        }
    }
}
</script>
@endpush
@endsection
