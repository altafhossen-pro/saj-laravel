@extends('layouts.admin')

@section('title', 'Product Reviews')

@section('content')
<div x-data="reviewManager()">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Product Reviews</h1>
            <p class="text-sm text-gray-500 mt-1">Manage and moderate customer reviews for your products.</p>
        </div>
        
        <form method="GET" action="{{ route('admin.reviews.index') }}" class="flex flex-wrap items-center gap-3">
            <div class="relative">
                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search reviews..." class="w-full sm:w-64 pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all outline-none">
            </div>
            
            <select name="status" class="px-4 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all outline-none">
                <option value="">All Statuses</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
            
            <button type="submit" class="px-4 py-2 bg-brand-600 text-white rounded-xl text-sm font-bold hover:bg-brand-700 transition-colors shadow-sm">
                Filter
            </button>
            
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('admin.reviews.index') }}" class="px-4 py-2 bg-white border border-gray-200 text-gray-600 rounded-xl text-sm font-bold hover:bg-gray-50 transition-colors">
                    Clear
                </a>
            @endif
        </form>
    </div>

    @if(session('success'))
    <div class="mb-6 p-4 rounded-xl bg-green-50 border border-green-100 flex items-center gap-3">
        <i class="ph-fill ph-check-circle text-green-500 text-xl"></i>
        <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
    </div>
    @endif
    @if(session('error'))
    <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-100 flex items-center gap-3">
        <i class="ph-fill ph-warning-circle text-red-500 text-xl"></i>
        <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
    </div>
    @endif
    @if($errors->any())
    <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-100">
        <ul class="list-disc pl-5 text-sm font-medium text-red-800">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-6 py-4 font-medium">Customer</th>
                        <th class="px-6 py-4 font-medium">Product</th>
                        <th class="px-6 py-4 font-medium">Rating</th>
                        <th class="px-6 py-4 font-medium max-w-xs">Review</th>
                        <th class="px-6 py-4 font-medium">Status</th>
                        <th class="px-6 py-4 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($reviews as $review)
                    @php
                        $reviewData = $review->toArray();
                        $reviewData['attachment_urls'] = [];
                        if(is_array($review->attachments)) {
                            foreach($review->attachments as $key => $path) {
                                $reviewData['attachment_urls'][$key] = \Illuminate\Support\Facades\Storage::url($path);
                            }
                        }
                        $reviewData['product_title'] = $review->product->title;
                        $reviewData['user_name'] = $review->user->name;
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors" x-data="{ rowData: {{ json_encode($reviewData) }}, isDeleted: false }" x-show="!isDeleted" @review-updated.window="if($event.detail.id === rowData.id) { rowData = {...rowData, ...$event.detail}; }">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900" x-text="rowData.user_name"></div>
                            <div class="text-xs text-gray-500">{{ $review->user->email }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <a href="{{ route('product.show', $review->product->slug) }}" target="_blank" class="font-medium text-brand-600 hover:underline line-clamp-1" x-text="rowData.product_title"></a>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center text-amber-400">
                                <template x-for="i in 5">
                                    <i class="ph-fill ph-star" :class="i <= rowData.rating ? 'text-amber-400' : 'text-gray-200'"></i>
                                </template>
                            </div>
                        </td>
                        <td class="px-6 py-4 max-w-xs">
                            <template x-if="rowData.review">
                                <p class="text-gray-600 line-clamp-2" :title="rowData.review" x-text="rowData.review"></p>
                            </template>
                            <template x-if="!rowData.review">
                                <span class="text-gray-400 italic">No text provided</span>
                            </template>
                            
                            <template x-if="rowData.attachments && rowData.attachments.length > 0">
                                <p class="text-xs text-brand-600 mt-1"><i class="ph ph-image"></i> <span x-text="rowData.attachments.length"></span> photos attached</p>
                            </template>
                        </td>
                        <td class="px-6 py-4">
                            <span x-show="rowData.status === 'approved'" class="px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700" style="display: none;">Approved</span>
                            <span x-show="rowData.status === 'pending'" class="px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700" style="display: none;">Pending</span>
                            <span x-show="rowData.status === 'rejected'" class="px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700" style="display: none;">Rejected</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" @click="openViewModal(rowData)" class="px-3 py-1.5 text-sm font-medium text-brand-600 hover:bg-brand-50 rounded-lg transition-colors border border-transparent hover:border-brand-200 flex items-center gap-1.5">
                                    <i class="ph ph-eye text-lg"></i> View & Edit
                                </button>
                                
                                <button type="button" @click="openDeleteModal(rowData.id)" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors border border-transparent hover:border-red-200" title="Delete Review">
                                    <i class="ph ph-trash text-lg"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="ph ph-star text-3xl text-gray-400"></i>
                            </div>
                            <h3 class="text-base font-bold text-gray-900 mb-1">No reviews found</h3>
                            <p class="text-sm text-gray-500">There are currently no product reviews.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($reviews->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="text-sm text-gray-500">
                Showing {{ $reviews->firstItem() ?? 0 }} to {{ $reviews->lastItem() ?? 0 }} of {{ $reviews->total() }} entries
            </div>
            <div>
                {{ $reviews->appends(request()->query())->links('vendor.pagination.tailwind') }}
            </div>
        </div>
        @endif
    </div>

    <!-- View & Edit Modal -->
    <div x-show="viewModalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="viewModalOpen" x-transition.opacity class="fixed inset-0 transition-opacity bg-gray-900/40 backdrop-blur-sm" @click="viewModalOpen = false"></div>

            <div x-show="viewModalOpen" x-transition.scale.origin.bottom class="relative inline-block w-full max-w-2xl px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-2xl shadow-xl sm:my-8 sm:align-middle sm:p-6">
                <div class="absolute top-0 right-0 hidden pt-4 pr-4 sm:block">
                    <button type="button" @click="viewModalOpen = false" class="text-gray-400 bg-white rounded-md hover:text-gray-500 focus:outline-none">
                        <i class="ph ph-x text-xl"></i>
                    </button>
                </div>
                
                <div class="sm:flex sm:items-start mb-6 border-b border-gray-100 pb-4">
                    <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                        <h3 class="text-lg font-bold leading-6 text-gray-900" x-text="'Review by ' + (activeReview ? activeReview.user_name : '')"></h3>
                        <p class="text-sm text-gray-500 mt-1" x-text="'Product: ' + (activeReview ? activeReview.product_title : '')"></p>
                    </div>
                </div>
                
                <template x-if="activeReview">
                    <form @submit.prevent="submitEdit" enctype="multipart/form-data" id="edit-review-form">
                        <input type="hidden" name="remove_attachments" :value="JSON.stringify(removedAttachments)">

                        <!-- Rating -->
                        <div class="mb-4">
                            <label class="block text-sm font-bold text-gray-700 mb-1">Rating</label>
                            <div class="flex items-center gap-1 text-2xl">
                                <template x-for="i in 5">
                                    <i class="ph-fill ph-star cursor-pointer transition-colors" 
                                       :class="i <= rating ? 'text-amber-400' : 'text-gray-200'"
                                       @click="rating = i"></i>
                                </template>
                            </div>
                            <input type="hidden" name="rating" :value="rating">
                        </div>

                        <!-- Review Text -->
                        <div class="mb-4">
                            <label class="block text-sm font-bold text-gray-700 mb-1">Review Text</label>
                            <textarea name="review" x-model="reviewText" rows="4" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-1 focus:ring-brand-500 focus:border-brand-500 text-sm"></textarea>
                        </div>

                        <!-- Status -->
                        <div class="mb-6">
                            <label class="block text-sm font-bold text-gray-700 mb-1">Status</label>
                            <select name="status" x-model="status" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-1 focus:ring-brand-500 focus:border-brand-500 text-sm">
                                <option value="pending">Pending</option>
                                <option value="approved">Approved (Published)</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>

                        <!-- Existing Photos -->
                        <div class="mb-4" x-show="hasExistingAttachments()">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Existing Photos (Click <i class="ph ph-trash text-red-500"></i> to remove)</label>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="(url, key) in activeReview.attachment_urls" :key="key">
                                    <div x-show="!removedAttachments.includes(key)" class="relative w-16 h-16 rounded border border-gray-200 group">
                                        <img :src="url" class="w-full h-full object-cover rounded">
                                        <button type="button" @click="removedAttachments.push(key)" class="absolute -top-2 -right-2 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity z-10 shadow-sm">
                                            <i class="ph ph-trash text-[10px]"></i>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Add New Photos -->
                        <div class="mb-6">
                            <label class="block text-sm font-bold text-gray-700 mb-1">Add New Photos</label>
                            <input type="file" name="attachments[]" multiple accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 border border-gray-200 rounded-xl p-1 cursor-pointer">
                        </div>

                        <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse border-t border-gray-100 pt-4">
                            <button type="submit" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-brand-600 text-base font-bold text-white hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Save Changes
                            </button>
                            <button type="button" @click="viewModalOpen = false" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-bold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 sm:mt-0 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </template>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div x-show="deleteModalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="deleteModalOpen" x-transition.opacity class="fixed inset-0 transition-opacity bg-gray-900/40 backdrop-blur-sm" @click="deleteModalOpen = false"></div>

            <div x-show="deleteModalOpen" x-transition.scale.origin.bottom class="relative inline-block w-full max-w-md px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-2xl shadow-xl sm:my-8 sm:align-middle sm:p-6">
                <div class="sm:flex sm:items-start">
                    <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto bg-red-100 rounded-full sm:mx-0 sm:h-10 sm:w-10">
                        <i class="ph ph-warning text-red-600 text-xl"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg font-bold leading-6 text-gray-900">Delete Review</h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">Are you sure you want to delete this review? This action cannot be undone.</p>
                        </div>
                    </div>
                </div>

                <div class="mt-4 bg-gray-50 p-4 rounded-xl border border-gray-200">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Please solve the math to confirm:</label>
                    <div class="flex items-center gap-3">
                        <span class="text-lg font-bold text-gray-900 bg-white px-3 py-1.5 rounded border border-gray-200 shadow-sm" x-text="num1 + ' + ' + num2 + ' ='"></span>
                        <input type="number" x-model="captchaInput" class="w-24 px-3 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-red-500 focus:border-red-500 font-bold" placeholder="?">
                    </div>
                    <p class="text-xs text-red-500 mt-2 font-medium" x-show="captchaInput !== '' && parseInt(captchaInput) !== (num1 + num2)">Incorrect answer.</p>
                </div>

                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                    <form @submit.prevent="submitDelete" class="inline">
                        <button type="submit" :disabled="parseInt(captchaInput) !== (num1 + num2) || isDeleting" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-bold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                            <i x-show="isDeleting" class="ph ph-spinner animate-spin" style="display: none;"></i>
                            <span x-text="isDeleting ? 'Deleting...' : 'Delete Review'"></span>
                        </button>
                    </form>
                    <button type="button" @click="deleteModalOpen = false" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-bold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:mt-0 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- JS Toast Notification -->
    <div x-data="{ toasts: [] }" @notify.window="toasts.push({id: Date.now(), ...$event.detail}); setTimeout(() => { toasts = toasts.filter(t => t.id !== toasts[0]?.id) }, 3000)" class="fixed bottom-6 right-6 z-50 flex flex-col gap-2 pointer-events-none">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-transition.opacity class="flex items-center gap-3 px-5 py-3.5 rounded-xl shadow-xl text-white pointer-events-auto" :class="toast.type === 'error' ? 'bg-red-600' : 'bg-gray-900'">
                <i class="ph-fill text-xl" :class="toast.type === 'error' ? 'ph-warning-circle text-white' : 'ph-check-circle text-green-400'"></i>
                <p class="text-sm font-medium" x-text="toast.message"></p>
                <button @click="toasts = toasts.filter(t => t.id !== toast.id)" class="ml-2 text-gray-400 hover:text-white transition-colors">
                    <i class="ph ph-x"></i>
                </button>
            </div>
        </template>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('reviewManager', () => ({
            viewModalOpen: false,
            deleteModalOpen: false,
            activeReview: null,
            rating: 5,
            reviewText: '',
            status: 'pending',
            removedAttachments: [],
            
            // Delete Captcha
            deleteReviewId: null,
            num1: 0,
            num2: 0,
            captchaInput: '',
            isDeleting: false,
            isSaving: false,

            openViewModal(review) {
                this.activeReview = review;
                this.rating = review.rating;
                this.reviewText = review.review || '';
                this.status = review.status;
                this.removedAttachments = [];
                this.viewModalOpen = true;
            },
            
            hasExistingAttachments() {
                if(!this.activeReview || !this.activeReview.attachment_urls) return false;
                const totalKeys = Object.keys(this.activeReview.attachment_urls);
                return totalKeys.length > 0;
            },

            openDeleteModal(id) {
                this.deleteReviewId = id;
                this.num1 = Math.floor(Math.random() * 10) + 1;
                this.num2 = Math.floor(Math.random() * 10) + 1;
                this.captchaInput = '';
                this.deleteModalOpen = true;
            },

            async submitEdit() {
                if (this.isSaving) return;
                this.isSaving = true;

                const form = document.getElementById('edit-review-form');
                const formData = new FormData(form);
                formData.append('_method', 'PUT');
                formData.append('_token', '{{ csrf_token() }}');

                try {
                    const response = await fetch(`/admin/dashboard/reviews/${this.activeReview.id}`, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    const data = await response.json();
                    if (response.ok && data.success) {
                        // Keep our custom properties
                        const updatedData = {
                            ...data.review,
                            product_title: this.activeReview.product_title,
                            user_name: this.activeReview.user_name,
                            attachment_urls: {}
                        };
                        
                        // Parse urls if they are returned (simple hack: just fetch again or let alpine update status)
                        window.dispatchEvent(new CustomEvent('review-updated', { detail: updatedData }));
                        window.dispatchEvent(new CustomEvent('notify', { detail: { message: data.message, type: 'success' } }));
                        this.viewModalOpen = false;
                    } else {
                        window.dispatchEvent(new CustomEvent('notify', { detail: { message: data.message || 'Error saving review.', type: 'error' } }));
                    }
                } catch (error) {
                    window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'An error occurred.', type: 'error' } }));
                } finally {
                    this.isSaving = false;
                }
            },

            async submitDelete() {
                if (this.isDeleting) return;
                this.isDeleting = true;

                const formData = new FormData();
                formData.append('_method', 'DELETE');
                formData.append('_token', '{{ csrf_token() }}');

                try {
                    const response = await fetch(`/admin/dashboard/reviews/${this.deleteReviewId}`, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    const data = await response.json();
                    if (response.ok && data.success) {
                        const tr = document.getElementById('review-row-' + this.deleteReviewId);
                        if(tr) tr.style.display = 'none'; // fallback
                        
                        // Using Alpine event to remove
                        const el = document.querySelector(`tr[x-data*="${this.deleteReviewId}"]`);
                        if(el && el.__x) el.__x.$data.isDeleted = true;
                        
                        window.dispatchEvent(new CustomEvent('notify', { detail: { message: data.message, type: 'success' } }));
                        this.deleteModalOpen = false;
                    } else {
                        window.dispatchEvent(new CustomEvent('notify', { detail: { message: data.message || 'Error deleting review.', type: 'error' } }));
                    }
                } catch (error) {
                    window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'An error occurred.', type: 'error' } }));
                } finally {
                    this.isDeleting = false;
                }
            }
        }));
    });
</script>
@endpush
@endsection
