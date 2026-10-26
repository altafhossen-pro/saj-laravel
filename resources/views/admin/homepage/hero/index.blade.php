@extends('layouts.admin')

@section('title', 'Hero Banner - Admin Panel')
@section('header_title', 'Hero Banner')

@section('content')
<div class="w-full">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Hero Banner Slides</h1>
            <p class="text-sm text-gray-500 mt-1">Manage the image and custom HTML slides on the homepage hero section.</p>
        </div>
        <a href="{{ route('admin.homepage.hero.create') }}" class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-bold shadow-sm transition-colors flex items-center gap-2">
            <i class="ph-bold ph-plus"></i> Add New Slide
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden relative w-full">
        <div class="p-6">
            @if($slides->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($slides as $slide)
                        <div x-data="{ 
                                isActive: {{ $slide->is_active ? 'true' : 'false' }}, 
                                isDeleted: false,
                                showDeleteModal: false,
                                showToggleModal: false,
                                isSubmitting: false,
                                async toggleSlide() {
                                    this.isSubmitting = true;
                                    try {
                                        let res = await fetch('{{ route('admin.homepage.hero.toggle', $slide->id) }}', {
                                            method: 'PATCH',
                                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                                        });
                                        if(res.ok) {
                                            let data = await res.json();
                                            this.isActive = data.is_active;
                                            this.showToggleModal = false;
                                        }
                                    } catch(e) {}
                                    this.isSubmitting = false;
                                },
                                async deleteSlide() {
                                    this.isSubmitting = true;
                                    try {
                                        let res = await fetch('{{ route('admin.homepage.hero.destroy', $slide->id) }}', {
                                            method: 'DELETE',
                                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                                        });
                                        if(res.ok) {
                                            this.isDeleted = true;
                                            this.showDeleteModal = false;
                                        }
                                    } catch(e) {}
                                    this.isSubmitting = false;
                                }
                            }" 
                            x-show="!isDeleted"
                            class="border border-gray-200 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow bg-gray-50 flex flex-col">
                            
                            <!-- Toggle Confirmation Modal -->
                            <div x-show="showToggleModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                                <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                                    <div x-show="showToggleModal" x-transition.opacity class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" aria-hidden="true" @click="showToggleModal = false"></div>
                                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                                    <div x-show="showToggleModal" x-transition.scale class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-[2rem] shadow-xl sm:my-8 sm:align-middle sm:max-w-md w-full border border-gray-100">
                                        <div class="px-6 pt-8 pb-6 bg-white sm:p-8 sm:pb-6">
                                            <div class="sm:flex sm:items-start">
                                                <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto bg-blue-100 rounded-full sm:mx-0 sm:h-10 sm:w-10">
                                                    <i class="text-xl text-blue-600 ph-bold ph-eye"></i>
                                                </div>
                                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                                    <h3 class="text-xl font-bold leading-6 text-gray-900" id="modal-title">Change Visibility</h3>
                                                    <div class="mt-2">
                                                        <p class="text-sm text-gray-500">Are you sure you want to <span x-text="isActive ? 'hide' : 'show'"></span> this slide on the homepage?</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="px-6 py-4 bg-gray-50 sm:flex sm:flex-row-reverse sm:px-8 border-t border-gray-100 gap-3">
                                            <button type="button" @click="toggleSlide()" :disabled="isSubmitting" class="inline-flex justify-center w-full px-5 py-2.5 text-sm font-semibold text-white transition-colors bg-brand-600 border border-transparent rounded-xl shadow-sm hover:bg-brand-700 sm:w-auto flex-1">
                                                <span x-show="!isSubmitting">Yes, proceed</span>
                                                <span x-show="isSubmitting"><i class="ph ph-spinner animate-spin"></i> Processing</span>
                                            </button>
                                            <button type="button" @click="showToggleModal = false" :disabled="isSubmitting" class="inline-flex justify-center w-full px-5 py-2.5 mt-3 text-sm font-semibold text-gray-700 transition-colors bg-white border border-gray-300 rounded-xl shadow-sm hover:bg-gray-50 sm:mt-0 sm:w-auto flex-1">Cancel</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Delete Confirmation Modal -->
                            <div x-show="showDeleteModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                                <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                                    <div x-show="showDeleteModal" x-transition.opacity class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" aria-hidden="true" @click="showDeleteModal = false"></div>
                                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                                    <div x-show="showDeleteModal" x-transition.scale class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-[2rem] shadow-xl sm:my-8 sm:align-middle sm:max-w-md w-full border border-gray-100">
                                        <div class="absolute top-0 left-0 right-0 h-32 bg-gradient-to-b from-red-50 to-white"></div>
                                        <div class="relative px-6 pt-10 pb-6 bg-white sm:p-8 sm:pb-6">
                                            <div class="flex flex-col items-center">
                                                <div class="flex items-center justify-center flex-shrink-0 w-20 h-20 mx-auto mb-5 bg-gradient-to-br from-red-100 to-red-50 rounded-full shadow-inner">
                                                    <i class="text-4xl text-red-600 drop-shadow-sm ph-fill ph-trash"></i>
                                                </div>
                                                <div class="mt-2 text-center">
                                                    <h3 class="text-2xl font-bold tracking-tight text-gray-900" id="modal-title">Confirm Deletion</h3>
                                                    <div class="mt-3">
                                                        <p class="text-base leading-relaxed text-gray-500">Are you sure you want to delete this slide? This action cannot be undone.</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="relative px-6 py-5 bg-gray-50/80 sm:flex sm:flex-row-reverse sm:px-8 border-t border-gray-100 gap-3">
                                            <button type="button" @click="deleteSlide()" :disabled="isSubmitting" class="inline-flex justify-center items-center gap-2 w-full px-5 py-3 text-sm font-semibold text-white transition-all duration-300 bg-gradient-to-r from-red-600 to-red-500 rounded-xl shadow-md shadow-red-500/20 hover:from-red-700 hover:to-red-600 sm:w-auto transform hover:-translate-y-0.5 flex-1">
                                                <span x-show="!isSubmitting">Yes, Delete It</span>
                                                <span x-show="isSubmitting"><i class="ph ph-spinner animate-spin"></i> Deleting</span>
                                            </button>
                                            <button type="button" @click="showDeleteModal = false" :disabled="isSubmitting" class="inline-flex justify-center items-center w-full px-5 py-3 mt-3 text-sm font-semibold text-gray-700 transition-all bg-white border border-gray-200 rounded-xl shadow-sm ring-1 ring-inset ring-gray-200 hover:bg-gray-50 sm:mt-0 sm:w-auto flex-1">Cancel</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="h-40 bg-gray-100 flex items-center justify-center relative overflow-hidden group">
                                @if($slide->type === 'image')
                                    <img src="{{ $slide->content }}" alt="Slide Image" class="w-full h-full object-cover">
                                @else
                                    <div class="p-4 w-full h-full overflow-hidden text-xs text-gray-600 font-mono bg-gray-800 text-green-400 opacity-80 break-all">
                                        {{ Str::limit($slide->content, 200) }}
                                    </div>
                                    <div class="absolute inset-0 flex items-center justify-center bg-black/40 backdrop-blur-sm pointer-events-none">
                                        <span class="px-3 py-1 bg-white/20 text-white font-bold rounded-lg text-sm backdrop-blur-md border border-white/30 shadow-sm"><i class="ph-bold ph-code"></i> Custom HTML</span>
                                    </div>
                                @endif
                                
                                <div class="absolute top-3 left-3 z-20">
                                    <button type="button" @click="showToggleModal = true" class="group flex items-center gap-1.5 bg-white/90 backdrop-blur-sm px-2 py-1 rounded shadow-sm border border-white/50 hover:bg-white transition-all">
                                        <div class="relative w-8 h-4 rounded-full transition-colors" :class="isActive ? 'bg-brand-500' : 'bg-gray-400'">
                                            <div class="absolute top-[2px] left-[2px] bg-white w-3 h-3 rounded-full transition-transform" :class="isActive ? 'translate-x-4' : ''"></div>
                                        </div>
                                        <span class="text-[10px] font-bold uppercase tracking-wide" :class="isActive ? 'text-brand-600' : 'text-gray-500'" x-text="isActive ? 'Visible' : 'Hidden'"></span>
                                    </button>
                                </div>
                                <div class="absolute top-3 right-3 bg-black/60 backdrop-blur-sm text-white text-[10px] font-bold px-2 py-0.5 rounded">Order: {{ $slide->sort_order }}</div>
                            </div>
                            
                            <div class="p-4 flex-1 flex flex-col justify-between">
                                <div>
                                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Type: {{ ucfirst($slide->type) }}</div>
                                    @if($slide->link)
                                        <a href="{{ $slide->link }}" target="_blank" class="text-sm text-brand-600 hover:underline font-medium truncate block"><i class="ph ph-link"></i> {{ $slide->link }}</a>
                                    @else
                                        <span class="text-sm text-gray-400 italic">No Link</span>
                                    @endif
                                </div>
                                
                                <div class="flex items-center gap-2 mt-4 pt-4 border-t border-gray-200">
                                    <a href="{{ route('admin.homepage.hero.edit', $slide->id) }}" class="flex-1 text-center py-2 bg-white border border-gray-200 hover:border-brand-500 hover:text-brand-600 rounded-lg text-sm font-semibold transition-colors">Edit</a>
                                    <button type="button" @click="showDeleteModal = true" class="flex-1 text-center py-2 bg-white border border-gray-200 hover:border-red-500 hover:text-red-600 rounded-lg text-sm font-semibold transition-colors">Delete</button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12 text-gray-500">
                    <i class="ph ph-images text-5xl text-gray-300 mb-3"></i>
                    <h3 class="text-lg font-medium text-gray-900">No slides found</h3>
                    <p class="text-sm">Get started by creating a new hero banner slide.</p>
                    <a href="{{ route('admin.homepage.hero.create') }}" class="inline-block mt-4 text-brand-600 font-bold hover:underline">Add New Slide</a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
