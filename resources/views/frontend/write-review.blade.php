@extends('layouts.app')

@section('title', 'Write a Review')

@section('content')
<div class="w-full px-4 lg:px-8 py-4 md:py-8">
    <div class="flex flex-col md:flex-row gap-4 md:gap-8">
        
        <!-- Sidebar Menu -->
        <x-frontend.dashboard-sidebar active-tab="reviews" />

        <!-- Main Content Area -->
        <main class="flex-1">
            <div class="flex items-center gap-3 mb-4">
            <a href="{{ route('dashboard.reviews') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900 flex items-center gap-1">
                <i class="ph ph-arrow-left"></i> Back
            </a>
            <h1 class="text-xl font-bold text-gray-900">Write a Review</h1>
        </div>

        <div class="grid lg:grid-cols-4 gap-4 items-start">
            
            <!-- Left Column: Product Info -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    @php
                        $imageUrl = str_starts_with($product->featured_image, 'http') ? $product->featured_image : Storage::url($product->featured_image);
                    @endphp
                    <div class="aspect-square bg-gray-50 border-b border-gray-200 rounded-t-xl overflow-hidden">
                        <img src="{{ $imageUrl }}" alt="{{ $product->title }}" class="w-full h-full object-cover">
                    </div>
                    <div class="p-4 sm:p-5">
                        <h2 class="font-bold text-sm text-gray-900 line-clamp-2 mb-2">{{ $product->title }}</h2>
                        <p class="text-xs text-gray-500 mb-4">Please share your experience with this product.</p>

                        <div class="border-t border-gray-100 pt-4">
                            <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider mb-3">Review Guidelines</h3>
                            <ul class="space-y-2.5 text-[13px] text-gray-600">
                                <li class="flex gap-2 items-start"><i class="ph-fill ph-check-circle text-brand-500 mt-0.5"></i> Focus on features and quality</li>
                                <li class="flex gap-2 items-start"><i class="ph-fill ph-check-circle text-brand-500 mt-0.5"></i> Upload clear real photos</li>
                                <li class="flex gap-2 items-start"><i class="ph-fill ph-check-circle text-brand-500 mt-0.5"></i> Keep it respectful</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Form -->
            <div class="lg:col-span-3" x-data="reviewForm()" @file-validation.window="hasFileError = $event.detail">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 sm:p-6">
                    
                    @php
                        $isLocked = $review && $review->status === 'approved';
                        $hasNewOrder = isset($latestOrderDate) && $review && $latestOrderDate > $review->updated_at;
                        if ($hasNewOrder) {
                            $isLocked = false;
                        }
                    @endphp

                    @if($review && $review->status === 'pending')
                    <div class="mb-5 p-3.5 bg-amber-50 border border-amber-200 rounded-lg text-amber-800 text-sm flex gap-3 items-start">
                        <i class="ph-fill ph-clock mt-0.5 text-lg"></i>
                        <div>
                            <strong class="block mb-0.5">Pending approval</strong> Your review will be visible once approved.
                        </div>
                    </div>
                    @elseif($review && $review->status === 'rejected')
                    <div class="mb-5 p-3.5 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm flex gap-3 items-start">
                        <i class="ph-fill ph-warning-circle mt-0.5 text-lg"></i>
                        <div>
                            <strong class="block mb-0.5">Rejected</strong> Please update your review to follow guidelines.
                        </div>
                    </div>
                    @elseif($review && $review->status === 'approved')
                    <div class="mb-5 p-3.5 {{ $isLocked ? 'bg-green-50 border-green-200 text-green-800' : 'bg-brand-50 border-brand-200 text-brand-800' }} border rounded-lg text-sm flex gap-3 items-start">
                        <i class="ph-fill {{ $isLocked ? 'ph-check-circle' : 'ph-info' }} mt-0.5 text-lg"></i>
                        <div>
                            @if($isLocked)
                            <strong class="block mb-0.5">Published</strong> This review has been published and cannot be edited.
                            @else
                            <strong class="block mb-0.5">Update Available</strong> You recently ordered this item again, so you can update your previous review.
                            @endif
                        </div>
                    </div>
                    @endif

                    <form action="{{ route('dashboard.reviews.store') }}" method="POST" enctype="multipart/form-data" id="write-review-form">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        

                        
                        <!-- Rating -->
                        <div class="mb-6">
                            <label class="block text-sm font-bold text-gray-900 mb-2">Overall Rating <span class="text-red-500">*</span></label>
                            <div class="flex items-center gap-1.5 text-4xl" id="star-rating-container" data-locked="{{ $isLocked ? 'true' : 'false' }}">
                                @for($i=1; $i<=5; $i++)
                                    <i class="ph-fill ph-star {{ !$isLocked ? 'cursor-pointer transition-transform hover:scale-110' : '' }} {{ ($review && $review->rating >= $i) ? 'text-amber-400 drop-shadow-sm' : 'text-gray-200' }}" data-rating="{{ $i }}"></i>
                                @endfor
                            </div>
                            <input type="hidden" name="rating" id="review-rating" value="{{ $review ? $review->rating : '' }}" required min="1" max="5">
                            @error('rating') <p class="text-xs font-medium text-red-500 mt-1.5">{{ $message }}</p> @enderror
                            <p class="text-xs font-medium text-red-500 mt-1.5 hidden" id="rating-error">Please select a rating.</p>
                        </div>

                        <!-- Review Text -->
                        <div class="mb-6">
                            <label class="block text-sm font-bold text-gray-900 mb-2" for="review-text">Written Review <span class="text-gray-400 font-normal ml-1">(Optional)</span></label>
                            <textarea name="review" id="review-text" rows="4" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 placeholder-gray-400 bg-gray-50 focus:bg-white transition-colors disabled:opacity-75 disabled:bg-gray-100" placeholder="What did you like or dislike?" {{ $isLocked ? 'disabled' : '' }}>{{ old('review', $review ? $review->review : '') }}</textarea>
                            @error('review') <p class="text-xs font-medium text-red-500 mt-1.5">{{ $message }}</p> @enderror
                        </div>

                        <!-- Attachments -->
                        <div class="mb-8">
                            <label class="block text-sm font-bold text-gray-900 mb-2">Add Photos <span class="text-gray-400 font-normal ml-1">(Optional)</span></label>
                            
                            @php
                                $currentCount = $review && is_array($review->attachments) ? count($review->attachments) : 0;
                                $canUploadMore = $currentCount < 3;
                            @endphp

                            @if($currentCount > 0)
                            <div class="mb-4 p-4 bg-gray-50 border border-gray-200 rounded-xl">
                                <p class="text-xs font-semibold text-gray-600 mb-3 uppercase tracking-wide">Current Photos:</p>
                                <div class="flex flex-wrap gap-3">
                                    @foreach($review->attachments as $attachment)
                                    <img src="{{ Storage::url($attachment) }}" class="w-16 h-16 sm:w-20 sm:h-20 object-cover border border-gray-200 rounded-lg shadow-sm bg-white p-1">
                                    @endforeach
                                </div>
                                <p class="text-[11px] text-gray-500 mt-2"><i class="ph-fill ph-info"></i> {{ $isLocked ? 'Attached photos.' : 'New photos will be added to these (Max 3 total).' }}</p>
                            </div>
                            @endif

                            @if(!$isLocked && $canUploadMore)
                            <div class="relative group">
                                <input type="file" name="attachments[]" id="attachments" multiple accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" onchange="handleFileSelect(event)">
                                <div class="w-full border-2 border-dashed border-gray-300 rounded-xl py-8 text-center bg-gray-50 group-hover:bg-brand-50/30 group-hover:border-brand-300 transition-all">
                                    <i class="ph ph-image text-3xl text-gray-400 mb-2"></i>
                                    <p class="text-sm font-bold text-gray-800">Click to upload photos (Max {{ 3 - $currentCount }} more)</p>
                                    <p class="text-xs text-gray-500 mt-1">JPG, PNG, WEBP (Max 2MB)</p>
                                </div>
                            </div>
                            
                            <div id="image-previews" class="flex flex-wrap gap-3 mt-4 hidden"></div>
                            @error('attachments.*') <p class="text-xs font-medium text-red-500 mt-1.5">{{ $message }}</p> @enderror
                            @endif
                        </div>

                        <!-- Submit -->
                        <div class="pt-5 border-t border-gray-100 flex justify-end gap-3">
                            <a href="{{ route('dashboard.reviews') }}" class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm font-bold text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer">{{ $isLocked ? 'Go Back' : 'Cancel' }}</a>
                            @if(!$isLocked)
                            <button type="button" @click="if(document.getElementById('review-rating').value) { showConfirmModal = true } else { document.getElementById('rating-error').classList.remove('hidden'); document.getElementById('star-rating-container').scrollIntoView({ behavior: 'smooth', block: 'center' }); }" :disabled="isSubmitting || hasFileError" class="px-6 py-2.5 bg-brand-600 text-white rounded-xl text-sm font-bold hover:bg-brand-700 transition-colors shadow-sm flex items-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                                <i class="ph ph-paper-plane-right"></i> {{ $review ? 'Update Review' : 'Submit Review' }}
                            </button>
                            @endif
                        </div>
                    </form>

                    <!-- Confirmation Modal -->
                    <div x-show="showConfirmModal" style="display: none;" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6">
                        <!-- Backdrop -->
                        <div x-show="showConfirmModal" x-transition.opacity class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showConfirmModal = false"></div>
                        
                        <!-- Modal Content -->
                        <div x-show="showConfirmModal"
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                             x-transition:leave="transition ease-in duration-200"
                             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                             class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6 overflow-hidden">
                             
                             <div class="flex items-center gap-4 mb-4">
                                 <div class="w-12 h-12 rounded-full bg-brand-50 flex items-center justify-center shrink-0">
                                     <i class="ph-fill ph-warning-circle text-brand-600 text-2xl"></i>
                                 </div>
                                 <div>
                                     <h3 class="text-lg font-bold text-gray-900">Confirm Submission</h3>
                                 </div>
                             </div>
                             
                             <p class="text-gray-600 text-sm mb-6 leading-relaxed">
                                 Are you sure you want to confirm? <br>
                                 <strong class="text-gray-900">Note:</strong> You cannot edit this review once published, unless you order the item again.
                             </p>
                             
                             <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                                 <button type="button" @click="showConfirmModal = false" :disabled="isSubmitting" class="px-5 py-2.5 rounded-xl border border-gray-200 text-sm font-bold text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">Cancel</button>
                                 <button type="button" @click="submitReview()" :disabled="isSubmitting" class="px-5 py-2.5 rounded-xl bg-brand-600 text-white text-sm font-bold hover:bg-brand-700 transition-colors shadow-sm cursor-pointer disabled:opacity-75 disabled:cursor-not-allowed flex items-center gap-2">
                                     <i x-show="isSubmitting" class="ph ph-spinner animate-spin"></i>
                                     <span x-text="isSubmitting ? 'Submitting...' : 'Yes, Confirm'"></span>
                                 </button>
                             </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </main>
    </div>
</div>

@push('scripts')
<script>
    function reviewForm() {
        return {
            showConfirmModal: false,
            isSubmitting: false,
            hasFileError: false,
            
            async submitReview() {
                if (this.isSubmitting) return;
                this.isSubmitting = true;
                
                const form = document.getElementById('write-review-form');
                const formData = new FormData(form);
                
                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });
                    
                    const data = await response.json();
                    
                    if (response.ok && data.success) {
                        window.location.href = data.redirect;
                    } else {
                        window.dispatchEvent(new CustomEvent('notify', { detail: { message: data.message || 'Something went wrong.', type: 'error' } }));
                        this.isSubmitting = false;
                        this.showConfirmModal = false;
                    }
                } catch (error) {
                    window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'An error occurred. Please try again.', type: 'error' } }));
                    this.isSubmitting = false;
                    this.showConfirmModal = false;
                }
            }
        }
    }

    document.querySelectorAll('#star-rating-container i').forEach(star => {
        star.addEventListener('click', function() {
            if (document.getElementById('star-rating-container').getAttribute('data-locked') === 'true') return;
            const rating = this.getAttribute('data-rating');
            document.getElementById('review-rating').value = rating;
            document.getElementById('rating-error').classList.add('hidden');
            
            document.querySelectorAll('#star-rating-container i').forEach((s, index) => {
                if (index < rating) {
                    s.classList.remove('text-gray-200');
                    s.classList.add('text-amber-400');
                } else {
                    s.classList.remove('text-amber-400');
                    s.classList.add('text-gray-200');
                }
            });
        });
    });

    document.getElementById('write-review-form').addEventListener('submit', function(e) {
        if (!document.getElementById('review-rating').value) {
            e.preventDefault();
            document.getElementById('rating-error').classList.remove('hidden');
        }
    });

    let selectedFiles = [];

    function handleFileSelect(event) {
        const files = Array.from(event.target.files);
        const maxSize = 2 * 1024 * 1024; // 2MB

        const newFiles = files.filter(file => file.type.startsWith('image/')).map(file => {
            return { file: file, sizeError: file.size > maxSize };
        });

        if (newFiles.some(f => f.sizeError)) {
            window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Some files exceed the 2MB limit. Please remove them.', type: 'error' } }));
        }

        // Merge with existing files
        selectedFiles = [...selectedFiles, ...newFiles];
        
        // Re-evaluate total errors
        const totalError = selectedFiles.some(item => item.sizeError);
        window.dispatchEvent(new CustomEvent('file-validation', { detail: totalError }));

        updateFileInput();
        renderPreviews();
    }

    function removeFile(index) {
        selectedFiles.splice(index, 1);
        const totalError = selectedFiles.some(item => item.sizeError);
        window.dispatchEvent(new CustomEvent('file-validation', { detail: totalError }));
        updateFileInput();
        renderPreviews();
    }

    function updateFileInput() {
        const dataTransfer = new DataTransfer();
        selectedFiles.filter(item => !item.sizeError).forEach(item => dataTransfer.items.add(item.file));
        document.getElementById('attachments').files = dataTransfer.files;
    }

    function renderPreviews() {
        const container = document.getElementById('image-previews');
        container.innerHTML = ''; 
        
        if (selectedFiles.length > 0) {
            container.classList.remove('hidden');
            selectedFiles.forEach((item, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    const errorClass = item.sizeError ? 'border-red-500 bg-red-50' : 'border-gray-200 bg-white';
                    div.className = `relative w-24 h-24 p-1 border rounded-lg shrink-0 group shadow-sm flex flex-col items-center justify-center ${errorClass}`;
                    
                    let innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover rounded ${item.sizeError ? 'opacity-30' : ''}">`;
                    
                    if (item.sizeError) {
                        innerHTML += `<div class="absolute inset-0 flex flex-col items-center justify-center text-center p-1 cursor-default pointer-events-none">
                            <span class="text-[10px] font-bold text-red-600 bg-white/90 px-1 py-0.5 rounded shadow-sm leading-tight">> 2MB<br>Remove</span>
                        </div>`;
                    }
                    
                    innerHTML += `<button type="button" onclick="removeFile(${index})" class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all shadow-md z-20 cursor-pointer">
                            <i class="ph ph-x text-[10px] font-bold"></i>
                        </button>`;
                        
                    div.innerHTML = innerHTML;
                    container.appendChild(div);
                }
                reader.readAsDataURL(item.file);
            });
        } else {
            container.classList.add('hidden');
            document.getElementById('attachments').value = '';
        }
    }
</script>
@endpush
@endsection
