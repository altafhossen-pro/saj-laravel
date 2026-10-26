<!-- Questions -->
<div x-data="questionForm()"
    class="bg-white p-4 sm:p-6 lg:p-8 rounded-2xl shadow-sm border border-gray-100 flex flex-col relative overflow-hidden">
    @php 
        $totalQuestionsCount = (isset($myQuestionsCount) ? $myQuestionsCount : 0) + (isset($answeredQuestionsCount) ? $answeredQuestionsCount : 0);
    @endphp
    <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-6 pb-4 border-b border-gray-100 flex justify-between items-center gap-2">
        <span>Questions About this Product</span>
        <span class="text-xs sm:text-sm font-normal text-gray-500 shrink-0">({{ $totalQuestionsCount }})</span>
    </h2>

    <div x-show="errorMessage" style="display: none;"
        class="mb-4 p-3 bg-red-50 text-red-700 rounded-lg text-sm border border-red-100" x-text="errorMessage"></div>

    <div class="mb-6 flex flex-col gap-3">
        <textarea x-model="question" rows="3" required minlength="5"
            placeholder="Enter your questions here..."
            class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3.5 sm:p-4 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-colors resize-y min-h-[90px] text-gray-800 placeholder-gray-400"></textarea>
        <div class="flex justify-end">
            <button type="button" @click="submitQuestion" :disabled="loading"
                class="w-full sm:w-auto px-6 py-2.5 sm:py-3 bg-brand-600 text-white rounded-xl font-medium text-sm hover:bg-brand-700 transition-colors whitespace-nowrap disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 cursor-pointer shadow-sm">
                <i x-show="loading" class="ph ph-spinner animate-spin"></i>
                <span x-text="loading ? 'Submitting...' : 'Ask Question'"></span>
            </button>
        </div>
    </div>

    <!-- Guest Info Modal -->
    <div x-show="showGuestModal" style="display: none;"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div @click.away="showGuestModal = false" class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6"
            x-transition>
            <div class="flex justify-between items-center mb-5">
                <h3 class="text-lg font-bold text-gray-900">Your Contact Info</h3>
                <button @click="showGuestModal = false" class="text-gray-400 hover:text-gray-600 cursor-pointer">
                    <i class="ph ph-x text-xl"></i>
                </button>
            </div>
            <p class="text-sm text-gray-600 mb-5">Please provide your name and phone number so we can notify you once
                your question is answered.</p>

            <div class="space-y-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name <span
                            class="text-red-500">*</span></label>
                    <input type="text" x-model="guestName"
                        class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number <span
                            class="text-red-500">*</span></label>
                    <input type="tel" x-model="guestPhone"
                        class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-colors">
                </div>
            </div>

            <div class="flex gap-3 justify-end">
                <button @click="showGuestModal = false"
                    class="px-5 py-2 text-gray-600 hover:bg-gray-50 rounded-lg font-medium text-sm transition-colors cursor-pointer">Cancel</button>
                <button @click="submitGuestQuestion" :disabled="loading"
                    class="px-5 py-2 bg-brand-600 text-white rounded-lg font-medium text-sm hover:bg-brand-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 cursor-pointer">
                    <i x-show="loading" class="ph ph-spinner animate-spin"></i>
                    Submit Question
                </button>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div x-show="showSuccessModal" style="display: none;"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div @click.away="showSuccessModal = false"
            class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-8 text-center" x-transition>
            <div
                class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-5">
                <i class="ph ph-check-circle text-3xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Successfully Submitted!</h3>
            <p class="text-sm text-gray-600 mb-6">Your question has been received. After review and answering, it will
                be published here.</p>
            <button @click="showSuccessModal = false"
                class="w-full py-2.5 bg-gray-100 text-gray-800 rounded-lg font-medium hover:bg-gray-200 transition-colors cursor-pointer">Okay,
                got it</button>
        </div>
    </div>

    <!-- My Questions -->
    @if(auth()->check())
        @if($myQuestionsCount > 0)
            <div class="mb-8" x-show="true">
                <h3 class="text-md font-bold text-gray-800 mb-4 flex justify-between items-center">
                    My Questions
                    <span class="text-xs font-normal text-gray-500">({{ $myQuestionsCount }})</span>
                </h3>
                <div class="flex-1 flex flex-col gap-4">
                    <!-- Dynamically prepended pending questions from JS -->
                    <template x-for="q in newPendingQuestions" :key="q.id">
                        <div class="bg-brand-50 p-4 rounded-lg border border-brand-100 relative" x-transition>
                            <span class="absolute top-4 right-4 text-[10px] font-bold tracking-wider uppercase bg-brand-200 text-brand-700 px-2 py-0.5 rounded-full">Pending Approval</span>
                            <div class="flex gap-3 mb-2 pr-24">
                                <div class="w-8 h-8 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center font-bold flex-shrink-0"
                                    x-text="q.customer_name ? q.customer_name.charAt(0).toUpperCase() : 'C'"></div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900" x-text="(q.customer_name || 'Customer') + ' (You)'"></p>
                                    <p class="text-xs text-gray-500">Just now</p>
                                    <p class="text-gray-700 text-sm mt-1 font-medium" x-text="q.question"></p>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Initial My Questions -->
                    <template x-for="q in myQuestions" :key="q.id">
                        <div class="bg-brand-50 p-4 rounded-lg border border-brand-100 relative" x-transition>
                            <template x-if="q.status === 'private'">
                                <span class="absolute top-4 right-4 text-[10px] font-bold tracking-wider uppercase bg-blue-200 text-blue-800 px-2 py-0.5 rounded-full">Private Reply</span>
                            </template>
                            <template x-if="q.status === 'pending'">
                                <span class="absolute top-4 right-4 text-[10px] font-bold tracking-wider uppercase bg-brand-200 text-brand-700 px-2 py-0.5 rounded-full">Pending Approval</span>
                            </template>
                            <template x-if="q.status === 'answered'">
                                <span class="absolute top-4 right-4 text-[10px] font-bold tracking-wider uppercase bg-green-200 text-green-700 px-2 py-0.5 rounded-full">Approved</span>
                            </template>
                            
                            <div class="flex gap-3 mb-2 pr-28">
                                <div class="w-8 h-8 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center font-bold flex-shrink-0"
                                    x-text="q.customer_name ? q.customer_name.charAt(0).toUpperCase() : 'C'"></div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900"
                                        x-text="(q.customer_name || 'Customer') + ' (You)'"></p>
                                    <p class="text-xs text-gray-500" x-text="q.time_ago || 'Just now'"></p>
                                    <p class="text-gray-700 text-sm mt-1 font-medium" x-text="q.question"></p>
                                </div>
                            </div>

                            <template x-if="q.status === 'private' || q.status === 'answered'">
                                <div class="ml-11 mt-3 pl-4 border-l-2 border-brand-200">
                                    <div class="flex gap-2">
                                        <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                                            <i class="ph-fill ph-storefront text-blue-600 text-xs"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">Seller</p>
                                            <p class="text-gray-700 text-sm mt-1" x-text="q.answer || 'No answer provided yet.'"></p>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    <!-- Pagination for My Questions -->
                    <div x-show="lastMyPage > 1" class="mt-2 flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-t border-brand-100 pt-4">
                        <p class="text-sm text-gray-500">Page <span x-text="currentMyPage"></span> of <span x-text="lastMyPage"></span></p>
                        <div class="flex gap-2">
                            <button @click="loadMyPage(currentMyPage - 1)" :disabled="currentMyPage === 1 || loadingMy"
                                class="px-3 sm:px-4 py-2 border border-brand-200 text-brand-700 bg-white rounded-lg font-medium text-xs sm:text-sm hover:bg-brand-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1.5 sm:gap-2 cursor-pointer">
                                <i class="ph ph-caret-left"></i> Previous
                            </button>
                            <button @click="loadMyPage(currentMyPage + 1)" :disabled="currentMyPage === lastMyPage || loadingMy"
                                class="px-3 sm:px-4 py-2 border border-brand-200 text-brand-700 bg-white rounded-lg font-medium text-xs sm:text-sm hover:bg-brand-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1.5 sm:gap-2 cursor-pointer">
                                Next <i class="ph ph-caret-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <!-- If no questions yet but might add one dynamically -->
            <div x-show="newPendingQuestions.length > 0" class="mb-8 flex-1 flex flex-col gap-4">
                <h3 class="text-md font-bold text-gray-800 mb-2">My Questions</h3>
                <template x-for="q in newPendingQuestions" :key="q.id">
                    <div class="bg-brand-50 p-4 rounded-lg border border-brand-100 relative" x-transition>
                        <span class="absolute top-4 right-4 text-[10px] font-bold tracking-wider uppercase bg-brand-200 text-brand-700 px-2 py-0.5 rounded-full">Pending Approval</span>
                        <div class="flex gap-3 mb-2 pr-24">
                            <div class="w-8 h-8 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center font-bold flex-shrink-0"
                                x-text="q.customer_name ? q.customer_name.charAt(0).toUpperCase() : 'C'"></div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900"
                                    x-text="(q.customer_name || 'Customer') + ' (You)'"></p>
                                <p class="text-xs text-gray-500">Just now</p>
                                <p class="text-gray-700 text-sm mt-1 font-medium" x-text="q.question"></p>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        @endif
    @else
        <!-- Guest User: Just show newly submitted questions here if any -->
        <div x-show="newPendingQuestions.length > 0" class="mb-8 flex-1 flex flex-col gap-4">
            <h3 class="text-md font-bold text-gray-800 mb-2">Recently Asked</h3>
            <template x-for="q in newPendingQuestions" :key="q.id">
                <div class="bg-brand-50 p-4 rounded-lg border border-brand-100 relative" x-transition>
                    <span class="absolute top-4 right-4 text-[10px] font-bold tracking-wider uppercase bg-brand-200 text-brand-700 px-2 py-0.5 rounded-full">Pending Approval</span>
                    <div class="flex gap-3 mb-2 pr-24">
                        <div class="w-8 h-8 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center font-bold flex-shrink-0"
                            x-text="q.customer_name ? q.customer_name.charAt(0).toUpperCase() : 'C'"></div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900"
                                x-text="(q.customer_name || 'Customer') + ' (You)'"></p>
                            <p class="text-xs text-gray-500">Just now</p>
                            <p class="text-gray-700 text-sm mt-1 font-medium" x-text="q.question"></p>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    @endif

    <!-- Answered Questions -->
    <div>
        <h3 class="text-md font-bold text-gray-800 mb-4 border-t border-gray-100 pt-4">
            {{ auth()->check() ? 'All Questions by Others' : 'All Questions' }}
        </h3>
        @if($answeredQuestionsCount > 0)
            <div class="flex-1 flex flex-col gap-4">
                <!-- Initial Answered Questions -->
                <template x-for="q in otherQuestions" :key="q.id">
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-100" x-transition>
                        <div class="flex gap-3 mb-2">
                            <div class="w-8 h-8 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center font-bold flex-shrink-0"
                                x-text="q.customer_name ? q.customer_name.charAt(0).toUpperCase() : 'C'"></div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900" x-text="q.customer_name || 'Customer'"></p>
                                <p class="text-xs text-gray-500" x-text="q.time_ago || 'Just now'"></p>
                                <p class="text-gray-700 text-sm mt-1 font-medium" x-text="q.question"></p>
                            </div>
                        </div>
                        <div class="flex gap-3 ml-6 mt-3 pt-3 border-t border-gray-200">
                            <div
                                class="w-8 h-8 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center font-bold flex-shrink-0">
                                <i class="ph-fill ph-storefront"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Store Admin</p>
                                <p class="text-xs text-gray-500" x-text="q.answer_time_ago || 'Just now'"></p>
                                <p class="text-gray-600 text-sm mt-1" x-text="q.answer || 'No answer provided yet.'"></p>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Pagination for Other Questions -->
                <div x-show="lastOtherPage > 1" class="mt-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-t border-gray-200 pt-4">
                    <p class="text-sm text-gray-500">Page <span x-text="currentOtherPage"></span> of <span x-text="lastOtherPage"></span></p>
                    <div class="flex gap-2">
                        <button @click="loadOtherPage(currentOtherPage - 1)" :disabled="currentOtherPage === 1 || loadingOther"
                            class="px-3 sm:px-4 py-2 border border-gray-300 text-gray-700 bg-white rounded-lg font-medium text-xs sm:text-sm hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1.5 sm:gap-2 cursor-pointer">
                            <i class="ph ph-caret-left"></i> Previous
                        </button>
                        <button @click="loadOtherPage(currentOtherPage + 1)" :disabled="currentOtherPage === lastOtherPage || loadingOther"
                            class="px-3 sm:px-4 py-2 border border-gray-300 text-gray-700 bg-white rounded-lg font-medium text-xs sm:text-sm hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1.5 sm:gap-2 cursor-pointer">
                            Next <i class="ph ph-caret-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        @else
            <!-- When absolutely no questions -->
            <div class="py-12 flex-1 flex flex-col justify-center">
                <p class="text-gray-500 text-center">No answered question found yet.</p>
            </div>
        @endif
    </div>
</div>
<script>
    document.addEventListener('alpine:init', () => {

        Alpine.data('questionForm', () => ({
            question: '',
            guestName: '',
            guestPhone: '',
            loading: false,
            showGuestModal: false,
            showSuccessModal: false,
            errorMessage: '',
            newPendingQuestions: [],
            isAuthenticated: {{ auth()->check() ? 'true' : 'false' }},
            productId: {{ $product->id }},

            myQuestions: @json($initialMyQuestions),
            currentMyPage: 1,
            lastMyPage: {{ ceil(max($myQuestionsCount ?? 0, 1) / 5) }},
            loadingMy: false,
            
            otherQuestions: @json($initialOtherQuestions),
            currentOtherPage: 1,
            lastOtherPage: {{ ceil(max($answeredQuestionsCount, 1) / 5) }},
            loadingOther: false,

            submitQuestion() {
                if (this.question.trim().length < 5) {
                    this.errorMessage = 'Question must be at least 5 characters.';
                    return;
                }
                this.errorMessage = '';

                if (!this.isAuthenticated) {
                    this.showGuestModal = true;
                } else {
                    this.sendRequest();
                }
            },

            submitGuestQuestion() {
                if (!this.guestName.trim() || !this.guestPhone.trim()) {
                    window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'Name and Phone are required.', type: 'warning' } }));
                    return;
                }
                this.sendRequest();
            },

            sendRequest() {
                this.loading = true;
                this.errorMessage = '';

                fetch(`/product/${this.productId}/questions`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        question: this.question,
                        customer_name: this.guestName,
                        phone: this.guestPhone
                    })
                })
                    .then(response => response.json().then(data => ({ status: response.status, body: data })))
                    .then(res => {
                        this.loading = false;
                        if (res.status === 200 || res.status === 201) {
                            this.showGuestModal = false;
                            this.showSuccessModal = true;
                            
                            if (this.isAuthenticated) {
                                this.myQuestions.unshift(res.body.question);
                            } else {
                                this.newPendingQuestions.unshift(res.body.question);
                            }
                            
                            this.question = '';
                        } else {
                            this.errorMessage = res.body.message || 'Something went wrong. Please try again.';
                        }
                    })
                    .catch(err => {
                        this.loading = false;
                        this.errorMessage = 'Network error. Please try again.';
                        console.error(err);
                    });
            },

            loadMyPage(page) {
                if (page < 1 || page > this.lastMyPage || this.loadingMy) return;
                this.loadingMy = true;
                
                fetch(`/product/${this.productId}/questions?type=my&page=${page}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(data => {
                    this.loadingMy = false;
                    if (data.data) {
                        this.myQuestions = data.data;
                        this.currentMyPage = data.current_page;
                        this.lastMyPage = data.last_page;
                    }
                })
                .catch(err => {
                    this.loadingMy = false;
                    console.error('Error loading my questions:', err);
                });
            },

            loadOtherPage(page) {
                if (page < 1 || page > this.lastOtherPage || this.loadingOther) return;
                this.loadingOther = true;
                
                fetch(`/product/${this.productId}/questions?type=answered&page=${page}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(data => {
                    this.loadingOther = false;
                    if (data.data) {
                        this.otherQuestions = data.data;
                        this.currentOtherPage = data.current_page;
                        this.lastOtherPage = data.last_page;
                    }
                })
                .catch(err => {
                    this.loadingOther = false;
                    console.error('Error loading other questions:', err);
                });
            }
        }));
    });
</script>