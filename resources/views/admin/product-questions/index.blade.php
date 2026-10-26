@extends('layouts.admin')

@section('title', 'Products Q&A - Admin Panel')
@section('header_title', 'Products Q&A')

@section('content')
    <div class="mb-8 flex justify-between items-end">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Products Q&A</h1>
            <p class="text-sm text-gray-500 mt-1">Manage and reply to customer questions about products.</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" x-data="questionFilter()">
        <!-- Filter Section -->
        <div class="p-5 border-b border-gray-100 flex flex-wrap items-center justify-between gap-4 bg-gray-50/50">
            <div class="flex flex-wrap items-center gap-3 flex-1">
                <div class="relative w-full max-w-sm">
                    <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" x-model="search" @input.debounce.500ms="fetchData()" placeholder="Search questions or customers..." class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                </div>
                <select x-model="status" @change="fetchData()" class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="answered">Answered (Public)</option>
                    <option value="private">Private Reply</option>
                    <option value="rejected">Rejected</option>
                </select>
                <select x-model="sort" @change="updateSort()" class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                    <option value="desc">Latest First</option>
                    <option value="asc">Oldest First</option>
                </select>
                <button @click="resetFilters()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 border border-transparent rounded-lg text-sm transition-colors flex items-center gap-2">
                    <i class="ph ph-arrow-counter-clockwise"></i> Reset
                </button>
            </div>
        </div>

        <div class="relative min-h-[400px]">
            <!-- Loading Overlay -->
            <div x-show="loading" class="absolute inset-0 bg-white/60 backdrop-blur-sm z-10 flex items-center justify-center" style="display: none;">
                <i class="ph ph-spinner animate-spin text-3xl text-brand-600"></i>
            </div>
            
            <div id="table-container" x-ref="tableContainer">
                @include('admin.product-questions.partials.table', ['questions' => $questions])
            </div>
        </div>

        <!-- Reply Modal -->
        <div x-show="replyModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div x-show="replyModalOpen" x-transition.opacity class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"
                @click="replyModalOpen = false"></div>

            <!-- Modal Content -->
            <div x-show="replyModalOpen" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]">

                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50 shrink-0">
                    <h3 class="text-lg font-bold text-gray-900">Reply to Question</h3>
                    <button @click="replyModalOpen = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="ph ph-x text-xl"></i>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto" x-data="{ 
                    formAction: '',
                    init() {
                        this.$watch('selectedQuestion', value => {
                            if(value) {
                                this.formAction = '/admin/dashboard/product-questions/' + value.id;
                            }
                        });
                    }
                }">
                    <template x-if="selectedQuestion">
                        <form :action="formAction" method="POST" class="space-y-6">
                            @csrf
                            @method('PUT')

                            <!-- Product Info -->
                            <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl border border-gray-100 mb-6">
                                <div class="w-12 h-12 rounded-lg bg-white border border-gray-200 overflow-hidden shrink-0">
                                    <template x-if="selectedQuestion.product_image">
                                        <img :src="selectedQuestion.product_image" class="w-full h-full object-cover">
                                    </template>
                                    <template x-if="!selectedQuestion.product_image">
                                        <div class="w-full h-full flex items-center justify-center text-gray-400">
                                            <i class="ph ph-image text-xl"></i>
                                        </div>
                                    </template>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900" x-text="selectedQuestion.product_title">
                                    </p>
                                    <p class="text-xs text-gray-500">Product Question</p>
                                </div>
                            </div>

                            <!-- Customer Info -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Customer Name</label>
                                    <input type="text" name="customer_name" :value="selectedQuestion.customer_name"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-brand-500 focus:ring-brand-500 text-sm outline-none">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number
                                        (Readonly)</label>
                                    <input type="text" readonly :value="selectedQuestion.phone"
                                        class="w-full rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-gray-500 text-sm cursor-not-allowed outline-none">
                                </div>
                            </div>

                            <!-- Question -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Question (Editable)</label>
                                <textarea name="question" rows="4" :value="selectedQuestion.question"
                                    class="w-full rounded-lg border border-blue-200 bg-blue-50/50 text-blue-900 px-4 py-2.5 focus:border-brand-500 focus:ring-brand-500 text-sm outline-none"></textarea>
                            </div>

                            <!-- Answer -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Your Answer</label>
                                <textarea name="answer" rows="3" :value="selectedQuestion.answer"
                                    placeholder="Type your answer here..."
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-brand-500 focus:ring-brand-500 text-sm outline-none"></textarea>
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select name="status"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-brand-500 focus:ring-brand-500 text-sm outline-none bg-white">
                                    <option value="pending" :selected="selectedQuestion.status === 'pending'">Pending (Draft
                                        / Hidden)</option>
                                    <option value="answered" :selected="selectedQuestion.status === 'answered'">Answered
                                        (Published)</option>
                                    <option value="private" :selected="selectedQuestion.status === 'private'">Answered
                                        (Private - User Only)</option>
                                    <option value="rejected" :selected="selectedQuestion.status === 'rejected'">Rejected
                                    </option>
                                </select>
                            </div>

                            <!-- Actions -->
                            <div class="pt-4 flex justify-end gap-3 border-t border-gray-100">
                                <button type="button" @click="replyModalOpen = false"
                                    class="px-5 py-2.5 rounded-xl border border-gray-200 text-gray-700 font-medium text-sm hover:bg-gray-50 transition-colors">Cancel</button>
                                <button type="submit"
                                    class="px-5 py-2.5 rounded-xl bg-brand-600 text-white font-medium text-sm hover:bg-brand-700 transition-colors">Save
                                    & Update</button>
                            </div>
                        </form>
                    </template>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function questionFilter() {
    return {
        search: new URLSearchParams(window.location.search).get('search') || '',
        status: new URLSearchParams(window.location.search).get('status') || '',
        sort: localStorage.getItem('question_sort_order') || 'desc',
        loading: false,
        
        replyModalOpen: false,
        selectedQuestion: null,

        openReplyModal(question) {
            this.selectedQuestion = question;
            this.replyModalOpen = true;
        },

        updateSort() {
            localStorage.setItem('question_sort_order', this.sort);
            this.fetchData();
        },

        resetFilters() {
            this.search = '';
            this.status = '';
            this.sort = 'desc';
            localStorage.removeItem('question_sort_order');
            this.fetchData();
        },

        fetchData(url = null) {
            this.loading = true;
            
            // Build query parameters
            let params = new URLSearchParams();
            if (this.search) params.append('search', this.search);
            if (this.status) params.append('status', this.status);
            if (this.sort) params.append('sort', this.sort);

            let fetchUrl = url ? url : `{{ route('admin.questions.index') }}?${params.toString()}`;
            
            // Update URL without refresh
            if(!url) {
                window.history.pushState({}, '', fetchUrl);
            }

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
                    window.history.pushState({}, '', link.href);
                }
            });
        }
    }
}
</script>
@endpush