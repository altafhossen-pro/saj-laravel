@extends('layouts.admin')

@section('title', 'Categories - Admin Panel')
@section('header_title', 'Categories')

@section('content')
@php
    $treeJson = $treeCategories->map(function($root) {
        return [
            'id' => $root->id,
            'name' => $root->name,
            'slug' => $root->slug,
            'image' => $root->image ? (\Illuminate\Support\Str::startsWith($root->image, ['http://', 'https://']) ? $root->image : \Illuminate\Support\Facades\Storage::url($root->image)) : null,
            'is_active' => (bool)$root->is_active,
            'is_featured' => (bool)$root->is_featured,
            'sort_order' => (int)$root->sort_order,
            'products_count' => (int)$root->products_count,
            'children_count' => (int)$root->children_count,
            'is_expanded' => true,
            'children' => $root->children->map(function($sub) {
                return [
                    'id' => $sub->id,
                    'parent_id' => $sub->parent_id,
                    'name' => $sub->name,
                    'slug' => $sub->slug,
                    'image' => $sub->image ? (\Illuminate\Support\Str::startsWith($sub->image, ['http://', 'https://']) ? $sub->image : \Illuminate\Support\Facades\Storage::url($sub->image)) : null,
                    'is_active' => (bool)$sub->is_active,
                    'is_featured' => (bool)$sub->is_featured,
                    'sort_order' => (int)$sub->sort_order,
                    'products_count' => (int)$sub->products_count,
                    'children_count' => (int)($sub->children ? $sub->children->count() : 0),
                    'is_expanded' => false,
                    'children' => $sub->children ? $sub->children->map(function($subsub) {
                        return [
                            'id' => $subsub->id,
                            'parent_id' => $subsub->parent_id,
                            'name' => $subsub->name,
                            'slug' => $subsub->slug,
                            'image' => $subsub->image ? (\Illuminate\Support\Str::startsWith($subsub->image, ['http://', 'https://']) ? $subsub->image : \Illuminate\Support\Facades\Storage::url($subsub->image)) : null,
                            'is_active' => (bool)$subsub->is_active,
                            'sort_order' => (int)$subsub->sort_order,
                            'products_count' => (int)$subsub->products_count,
                        ];
                    })->values()->toArray() : [],
                ];
            })->values()->toArray()
        ];
    })->values()->toArray();
@endphp

<div x-data="categoryList()">
    <!-- Header -->
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Categories Management</h1>
            <p class="text-sm text-gray-500 mt-1">Manage categories, subcategories hierarchy, and store display order.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.homepage.category-sections') }}"
                class="px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-xl text-sm font-medium hover:bg-gray-50 flex items-center gap-2 shadow-xs transition-colors">
                <i class="ph ph-squares-four text-base text-gray-500"></i> Homepage Sections
            </a>
            <a href="{{ route('admin.categories.create') }}"
                class="px-4 py-2 bg-brand-600 rounded-xl text-sm font-medium text-white hover:bg-brand-700 flex items-center gap-2 shadow-sm transition-colors">
                <i class="ph ph-plus"></i> Add Category
            </a>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-gray-200 mb-6 bg-white px-2 pt-2 rounded-t-2xl border-t border-x border-gray-100 shadow-2xs">
        <button type="button" @click="activeTab = 'general'"
            :class="activeTab === 'general' ? 'border-brand-600 text-brand-600 font-bold bg-brand-50/20' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium'"
            class="px-5 py-3 border-b-2 text-sm flex items-center gap-2.5 transition-all cursor-pointer rounded-t-lg">
            <i class="ph ph-list-dashes text-lg"></i>
            <span>General Categories</span>
            <span class="px-2 py-0.5 rounded-full text-xs font-semibold"
                :class="activeTab === 'general' ? 'bg-brand-100 text-brand-700' : 'bg-gray-100 text-gray-600'">
                {{ $categories->total() }}
            </span>
        </button>

        <button type="button" @click="activeTab = 'ordering'"
            :class="activeTab === 'ordering' ? 'border-brand-600 text-brand-600 font-bold bg-brand-50/20' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium'"
            class="px-5 py-3 border-b-2 text-sm flex items-center gap-2.5 transition-all cursor-pointer rounded-t-lg">
            <i class="ph ph-tree-structure text-lg"></i>
            <span>Ordering Category (Tree)</span>
            <span class="px-2 py-0.5 rounded-full text-xs font-semibold"
                :class="activeTab === 'ordering' ? 'bg-brand-100 text-brand-700' : 'bg-indigo-50 text-indigo-700'">
                {{ $treeCategories->count() }} Roots
            </span>
        </button>
    </div>

    <!-- ========================================== -->
    <!-- TAB 1: GENERAL CATEGORY (STANDARD TABLE)   -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'general'" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden relative">

        <!-- Loading Overlay -->
        <div x-show="loading" class="absolute inset-0 bg-white/60 backdrop-blur-[2px] z-10 flex items-center justify-center"
            style="display: none;">
            <i class="ph ph-spinner animate-spin text-4xl text-brand-600"></i>
        </div>

        <!-- Filter Bar -->
        <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row gap-4 justify-between items-center">
            <div class="flex flex-wrap w-full gap-3 items-center justify-between">
                <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                    <div class="relative w-full sm:w-64">
                        <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" x-model.debounce.500ms="search" placeholder="Search categories..."
                            class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-white">
                    </div>
                    <select x-model="status"
                        class="py-2 px-3 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-white min-w-[120px]">
                        <option value="">All Status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>

                    <button x-show="search !== '' || status !== ''" @click="search = ''; status = ''"
                        class="px-3 py-2 text-red-600 hover:text-red-700 text-sm font-medium flex items-center gap-1 transition-colors"
                        style="display: none;">
                        <i class="ph ph-x-circle text-lg"></i> Clear
                    </button>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div id="table-container" class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-gray-50 text-gray-500 font-medium border-b border-gray-100 uppercase text-xs tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Image</th>
                        <th class="px-6 py-4">Name</th>
                        <th class="px-6 py-4">Parent</th>
                        <th class="px-6 py-4">Order</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($categories as $category)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                @if($category->image)
                                    @if(Str::startsWith($category->image, ['http://', 'https://']))
                                        <img src="{{ $category->image }}"
                                            class="w-10 h-10 rounded-lg object-cover border border-gray-200">
                                    @else
                                        <img src="{{ Storage::url($category->image) }}"
                                            class="w-10 h-10 rounded-lg object-cover border border-gray-200">
                                    @endif
                                @else
                                    <div class="w-10 h-10 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center text-gray-400">
                                        <i class="ph ph-image text-xl"></i>
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-900">
                                {{ $category->name }}
                                @if($category->is_featured)
                                    <span class="ml-2 px-2 py-0.5 text-[10px] uppercase font-bold bg-amber-100 text-amber-700 rounded-full">Featured</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-500">
                                @if($category->parent)
                                    <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs">{{ $category->parent->name }}</span>
                                @else
                                    <span class="text-gray-400 text-xs italic">None (Root)</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-500 font-mono font-medium">{{ $category->sort_order }}</td>
                            <td class="px-6 py-4">
                                @if($category->is_active)
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-brand-100 text-brand-700">Active</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">Inactive</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.categories.edit', $category) }}"
                                        class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                                        <i class="ph ph-pencil-simple text-lg"></i>
                                    </a>
                                    <form action="{{ route('admin.categories.destroy', $category) }}" method="POST"
                                        class="inline-block"
                                        onsubmit="return confirm('Are you sure you want to delete this category?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors cursor-pointer"
                                            title="Delete">
                                            <i class="ph ph-trash text-lg"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="ph ph-list-dashes text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-base font-medium text-gray-900">No categories found</p>
                                    <p class="text-sm mt-1">Get started by creating a new category.</p>
                                    <a href="{{ route('admin.categories.create') }}"
                                        class="mt-4 px-4 py-2 bg-brand-50 text-brand-600 rounded-lg text-sm font-medium hover:bg-brand-100 transition-colors">
                                        Create Category
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div id="pagination-container" class="px-6 py-4 border-t border-gray-100 bg-gray-50/30">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500 font-medium">Per Page:</span>
                    <select x-model="per_page"
                        class="py-1.5 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-white">
                        <option value="1">1</option>
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div class="flex-1 w-full sm:w-auto">
                    <x-pagination :paginator="$categories" />
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- TAB 2: ORDERING CATEGORY (TREE & ARROWS VIEW)  -->
    <!-- ============================================== -->
    <div x-show="activeTab === 'ordering'" class="space-y-4" style="display: none;">
        
        <!-- Info Helper Card -->
        <div class="p-4 bg-indigo-50/80 border border-indigo-200/80 rounded-2xl flex items-start gap-3.5 text-indigo-950">
            <div class="w-8 h-8 rounded-xl bg-indigo-100 flex items-center justify-center flex-shrink-0 text-indigo-600 mt-0.5">
                <i class="ph ph-arrows-down-up text-xl"></i>
            </div>
            <div class="text-sm">
                <h4 class="font-semibold text-indigo-950 mb-0.5">Sidebar & Menu Ordering Control</h4>
                <p class="text-indigo-800 leading-relaxed">
                    Use the <span class="font-semibold">Up (▲)</span> and <span class="font-semibold">Down (▼)</span> arrows to reorder main categories and their subcategories. 
                    Changes are <span class="font-bold underline decoration-indigo-300">saved automatically</span> and immediately update the order in the frontend <span class="font-medium">Sidebar Categories Menu</span> and <span class="font-medium">Explore Categories</span>!
                </p>
            </div>
        </div>

        <!-- Controls Bar for Tree View -->
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-2xs flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <div class="relative w-full sm:w-72">
                <i class="ph ph-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" x-model="treeSearch" placeholder="Filter categories in tree..."
                    class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-white">
            </div>

            <div class="flex items-center gap-2">
                <button type="button" @click="expandAll(true)"
                    class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-lg transition-colors cursor-pointer flex items-center gap-1.5">
                    <i class="ph ph-arrows-out-simple"></i> Expand All
                </button>
                <button type="button" @click="expandAll(false)"
                    class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-lg transition-colors cursor-pointer flex items-center gap-1.5">
                    <i class="ph ph-arrows-in-simple"></i> Collapse All
                </button>
            </div>
        </div>

        <!-- Tree List Container -->
        <div class="space-y-3">
            <template x-for="(root, rootIndex) in filteredTreeItems" :key="root.id">
                <div class="bg-white rounded-2xl border border-gray-200/80 shadow-2xs overflow-hidden transition-all hover:border-gray-300">
                    
                    <!-- Main Category Card Header -->
                    <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white">
                        <div class="flex items-center gap-3">
                            
                            <!-- Up & Down Buttons for Main Category -->
                            <div class="inline-flex items-center gap-1.5 bg-gray-50 border border-gray-200 px-2 py-1 rounded-xl shadow-2xs flex-shrink-0">
                                <span class="w-6 text-center font-mono font-bold text-xs text-gray-600" x-text="'#' + (rootIndex + 1)"></span>
                                <div class="flex flex-col gap-0.5">
                                    <button type="button" 
                                        @click="moveRootUp(rootIndex)"
                                        :disabled="rootIndex === 0"
                                        class="w-6 h-5 rounded flex items-center justify-center text-gray-600 hover:text-brand-600 hover:bg-brand-50 disabled:opacity-20 disabled:hover:bg-transparent disabled:hover:text-gray-600 disabled:cursor-not-allowed transition-colors cursor-pointer"
                                        title="Move Main Category Up">
                                        <i class="ph ph-caret-up font-bold text-sm"></i>
                                    </button>
                                    <button type="button" 
                                        @click="moveRootDown(rootIndex)"
                                        :disabled="rootIndex === filteredTreeItems.length - 1"
                                        class="w-6 h-5 rounded flex items-center justify-center text-gray-600 hover:text-brand-600 hover:bg-brand-50 disabled:opacity-20 disabled:hover:bg-transparent disabled:hover:text-gray-600 disabled:cursor-not-allowed transition-colors cursor-pointer"
                                        title="Move Main Category Down">
                                        <i class="ph ph-caret-down font-bold text-sm"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Expand / Collapse chevron button -->
                            <button type="button" 
                                @click="root.is_expanded = !root.is_expanded"
                                class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors cursor-pointer flex-shrink-0"
                                :title="root.is_expanded ? 'Collapse Subcategories' : 'Expand Subcategories'">
                                <i class="ph text-base transition-transform" 
                                    :class="root.is_expanded ? 'ph-caret-down text-brand-600' : 'ph-caret-right'"></i>
                            </button>

                            <!-- Thumbnail Image -->
                            <template x-if="root.image">
                                <img :src="root.image" :alt="root.name"
                                    class="w-10 h-10 rounded-xl object-cover border border-gray-200 shadow-2xs flex-shrink-0">
                            </template>
                            <template x-if="!root.image">
                                <div class="w-10 h-10 rounded-xl bg-gray-100 border border-gray-200 flex items-center justify-center text-gray-400 flex-shrink-0">
                                    <i class="ph ph-image text-lg"></i>
                                </div>
                            </template>

                            <!-- Name & Meta -->
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="font-bold text-gray-900 text-sm" x-text="root.name"></h3>
                                    <template x-if="root.is_featured">
                                        <span class="px-2 py-0.5 text-[9px] uppercase font-bold bg-amber-100 text-amber-700 rounded-full">Featured</span>
                                    </template>
                                </div>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-xs text-gray-400 font-mono" x-text="'/' + root.slug"></span>
                                    <span class="text-gray-300">•</span>
                                    <span class="text-xs font-semibold text-brand-600 cursor-pointer hover:underline"
                                        @click="root.is_expanded = !root.is_expanded"
                                        x-text="root.children.length + ' subcategories'"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Right Badges & Edit Link -->
                        <div class="flex items-center gap-2.5 sm:self-center pl-14 sm:pl-0">
                            <span class="px-2.5 py-1 rounded-lg text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200/60 flex items-center gap-1">
                                <i class="ph ph-package text-gray-500"></i>
                                <span x-text="root.products_count + ' products'"></span>
                            </span>

                            <template x-if="root.is_active">
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-brand-50 text-brand-700 border border-brand-100">Active</span>
                            </template>
                            <template x-if="!root.is_active">
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-100">Inactive</span>
                            </template>

                            <a :href="'/admin/categories/' + root.id + '/edit'"
                                class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors ml-1" title="Edit Category">
                                <i class="ph ph-pencil-simple text-base"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Subcategories Panel (Children) -->
                    <div x-show="root.is_expanded" 
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        class="p-4 pt-1 bg-gray-50/70 border-t border-gray-100">
                        
                        <div class="ml-4 sm:ml-8 pl-4 sm:pl-6 border-l-2 border-brand-300 py-2 space-y-2.5">
                            <template x-for="(sub, subIndex) in root.children" :key="sub.id">
                                <div class="bg-white p-3 rounded-xl border border-gray-200/90 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:border-brand-200 transition-colors">
                                    <div class="flex items-center gap-3">
                                        
                                        <!-- Up & Down Buttons for Subcategory (relative to parent) -->
                                        <div class="inline-flex items-center gap-1 bg-gray-50 border border-gray-200 px-1.5 py-0.5 rounded-lg flex-shrink-0">
                                            <span class="w-5 text-center font-mono font-bold text-[11px] text-gray-600" x-text="'#' + (subIndex + 1)"></span>
                                            <div class="flex flex-col gap-0.5">
                                                <button type="button" 
                                                    @click="moveChildUp(rootIndex, subIndex)"
                                                    :disabled="subIndex === 0"
                                                    class="w-5 h-4 rounded flex items-center justify-center text-gray-600 hover:text-brand-600 hover:bg-brand-50 disabled:opacity-20 disabled:hover:bg-transparent disabled:hover:text-gray-600 disabled:cursor-not-allowed transition-colors cursor-pointer"
                                                    title="Move Subcategory Up">
                                                    <i class="ph ph-caret-up font-bold text-xs"></i>
                                                </button>
                                                <button type="button" 
                                                    @click="moveChildDown(rootIndex, subIndex)"
                                                    :disabled="subIndex === root.children.length - 1"
                                                    class="w-5 h-4 rounded flex items-center justify-center text-gray-600 hover:text-brand-600 hover:bg-brand-50 disabled:opacity-20 disabled:hover:bg-transparent disabled:hover:text-gray-600 disabled:cursor-not-allowed transition-colors cursor-pointer"
                                                    title="Move Subcategory Down">
                                                    <i class="ph ph-caret-down font-bold text-xs"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Subcategory Thumbnail -->
                                        <template x-if="sub.image">
                                            <img :src="sub.image" :alt="sub.name"
                                                class="w-8 h-8 rounded-lg object-cover border border-gray-200 flex-shrink-0">
                                        </template>
                                        <template x-if="!sub.image">
                                            <div class="w-8 h-8 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center text-gray-400 flex-shrink-0">
                                                <i class="ph ph-folder text-sm"></i>
                                            </div>
                                        </template>

                                        <!-- Subcategory Name -->
                                        <div>
                                            <div class="font-semibold text-gray-900 text-sm flex items-center gap-2">
                                                <span x-text="sub.name"></span>
                                                <template x-if="sub.is_featured">
                                                    <span class="px-1.5 py-0.2 text-[8px] uppercase font-bold bg-amber-100 text-amber-700 rounded-full">Featured</span>
                                                </template>
                                            </div>
                                            <div class="text-[11px] text-gray-400 font-mono" x-text="'/' + sub.slug"></div>
                                        </div>
                                    </div>

                                    <!-- Subcategory Right Info -->
                                    <div class="flex items-center gap-2 pl-12 sm:pl-0">
                                        <span class="px-2 py-0.5 rounded-md text-[11px] font-medium bg-gray-100 text-gray-600 border border-gray-200/50" x-text="sub.products_count + ' products'"></span>
                                        
                                        <template x-if="sub.is_active">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-brand-50 text-brand-700">Active</span>
                                        </template>
                                        <template x-if="!sub.is_active">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-red-50 text-red-700">Inactive</span>
                                        </template>

                                        <a :href="'/admin/categories/' + sub.id + '/edit'"
                                            class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit Subcategory">
                                            <i class="ph ph-pencil-simple text-sm"></i>
                                        </a>
                                    </div>
                                </div>
                            </template>

                            <!-- If no subcategories -->
                            <template x-if="root.children.length === 0">
                                <div class="py-3 px-4 bg-white/70 rounded-xl border border-dashed border-gray-300 text-center">
                                    <p class="text-xs text-gray-500">No subcategories under <strong x-text="root.name"></strong>.</p>
                                    <a :href="'{{ route('admin.categories.create') }}?parent_id=' + root.id" 
                                        class="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-brand-600 hover:text-brand-700 hover:underline">
                                        <i class="ph ph-plus"></i> Add Subcategory
                                    </a>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Tree View Empty Search State -->
            <template x-if="filteredTreeItems.length === 0">
                <div class="bg-white rounded-2xl border border-gray-200 p-12 text-center text-gray-500">
                    <i class="ph ph-folder-notch-open text-4xl text-gray-300 mb-2"></i>
                    <p class="font-semibold text-gray-900">No matching categories in tree</p>
                    <p class="text-xs text-gray-400 mt-1">Try clearing your filter or search query.</p>
                </div>
            </template>
        </div>
    </div>

    <!-- Toast Notification -->
    <div x-show="toast.show" 
        x-transition:enter="transform ease-out duration-300 transition"
        x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed bottom-6 right-6 z-50 max-w-sm w-full bg-gray-900 text-white px-4 py-3 rounded-xl shadow-xl flex items-center gap-3 border border-gray-800"
        style="display: none;">
        <i class="ph ph-check-circle text-emerald-400 text-xl flex-shrink-0"></i>
        <span class="text-sm font-medium" x-text="toast.message"></span>
    </div>
</div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('categoryList', () => ({
                activeTab: 'general', // 'general' or 'ordering'
                search: '{{ request('search') }}',
                status: '{{ request('status') }}',
                per_page: '{{ request('per_page', 10) }}',
                loading: false,

                // Tree ordering state
                treeSearch: '',
                treeItems: @json($treeJson),

                // Toast notification
                toast: {
                    show: false,
                    message: ''
                },

                get filteredTreeItems() {
                    if (!this.treeSearch.trim()) {
                        return this.treeItems;
                    }
                    const q = this.treeSearch.toLowerCase().trim();
                    return this.treeItems.filter(root => {
                        const rootMatch = root.name.toLowerCase().includes(q) || root.slug.toLowerCase().includes(q);
                        const childMatch = root.children && root.children.some(c => c.name.toLowerCase().includes(q) || c.slug.toLowerCase().includes(q));
                        return rootMatch || childMatch;
                    });
                },

                init() {
                    // Check URL hash
                    if (window.location.hash === '#ordering') {
                        this.activeTab = 'ordering';
                    }

                    // Watch for changes and fetch new table data
                    this.$watch('search', () => this.fetchData());
                    this.$watch('status', () => this.fetchData());
                    this.$watch('per_page', () => this.fetchData());
                },

                showToast(message) {
                    this.toast.message = message;
                    this.toast.show = true;
                    setTimeout(() => {
                        this.toast.show = false;
                    }, 3000);
                },

                expandAll(state) {
                    this.treeItems.forEach(root => root.is_expanded = state);
                },

                // Reorder Main Categories
                moveRootUp(index) {
                    if (index <= 0) return;
                    const temp = this.filteredTreeItems[index];
                    const target = this.filteredTreeItems[index - 1];

                    const i1 = this.treeItems.findIndex(i => i.id === temp.id);
                    const i2 = this.treeItems.findIndex(i => i.id === target.id);

                    if (i1 !== -1 && i2 !== -1) {
                        this.treeItems[i1] = this.treeItems[i2];
                        this.treeItems[i2] = temp;
                        this.treeItems = [...this.treeItems];
                        this.saveRootOrder();
                    }
                },

                moveRootDown(index) {
                    if (index >= this.filteredTreeItems.length - 1) return;
                    const temp = this.filteredTreeItems[index];
                    const target = this.filteredTreeItems[index + 1];

                    const i1 = this.treeItems.findIndex(i => i.id === temp.id);
                    const i2 = this.treeItems.findIndex(i => i.id === target.id);

                    if (i1 !== -1 && i2 !== -1) {
                        this.treeItems[i1] = this.treeItems[i2];
                        this.treeItems[i2] = temp;
                        this.treeItems = [...this.treeItems];
                        this.saveRootOrder();
                    }
                },

                saveRootOrder() {
                    const orderIds = this.treeItems.map(c => c.id);
                    this.sendReorderRequest(orderIds, 'Main categories reordered successfully!');
                },

                // Reorder Subcategories under a Parent
                moveChildUp(rootIndex, childIndex) {
                    const root = this.filteredTreeItems[rootIndex];
                    if (!root || childIndex <= 0) return;

                    const temp = root.children[childIndex];
                    root.children[childIndex] = root.children[childIndex - 1];
                    root.children[childIndex - 1] = temp;
                    root.children = [...root.children];
                    this.treeItems = [...this.treeItems];

                    this.saveChildOrder(root);
                },

                moveChildDown(rootIndex, childIndex) {
                    const root = this.filteredTreeItems[rootIndex];
                    if (!root || childIndex >= root.children.length - 1) return;

                    const temp = root.children[childIndex];
                    root.children[childIndex] = root.children[childIndex + 1];
                    root.children[childIndex + 1] = temp;
                    root.children = [...root.children];
                    this.treeItems = [...this.treeItems];

                    this.saveChildOrder(root);
                },

                saveChildOrder(root) {
                    const orderIds = root.children.map(c => c.id);
                    this.sendReorderRequest(orderIds, `Subcategories under "${root.name}" reordered!`);
                },

                sendReorderRequest(orderIds, successMsg) {
                    fetch('{{ route('admin.categories.reorder') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ order: orderIds })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            this.showToast(successMsg);
                        }
                    })
                    .catch(err => {
                        console.error(err);
                    });
                },

                fetchData() {
                    this.loading = true;

                    // Build the new URL with current params
                    const url = new URL(window.location.href);

                    if (this.search) url.searchParams.set('search', this.search);
                    else url.searchParams.delete('search');

                    if (this.status !== '') url.searchParams.set('status', this.status);
                    else url.searchParams.delete('status');

                    url.searchParams.set('per_page', this.per_page);
                    url.searchParams.delete('page');

                    window.history.pushState({}, '', url);

                    fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');

                        const newTable = doc.getElementById('table-container');
                        const newPagination = doc.getElementById('pagination-container');

                        if (newTable) document.getElementById('table-container').innerHTML = newTable.innerHTML;
                        if (newPagination) document.getElementById('pagination-container').innerHTML = newPagination.innerHTML;

                        this.loading = false;
                    })
                    .catch(error => {
                        console.error('Error fetching data:', error);
                        this.loading = false;
                    });
                }
            }));
        });
    </script>
@endpush