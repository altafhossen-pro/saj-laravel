<div class="overflow-x-auto">
    <table class="w-full text-left text-sm whitespace-nowrap">
        <thead class="bg-gray-50 text-gray-500 font-medium border-b border-gray-100">
            <tr>
                <th class="px-6 py-4">Product</th>
                <th class="px-6 py-4">Customer</th>
                <th class="px-6 py-4 max-w-[200px]">Question</th>
                <th class="px-6 py-4">Status</th>
                <th class="px-6 py-4">Date</th>
                <th class="px-6 py-4 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($questions as $q)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg overflow-hidden border border-gray-200 shrink-0">
                                @if($q->product->featured_image)
                                    <img src="{{ Str::startsWith($q->product->featured_image, 'http') ? $q->product->featured_image : '/storage/' . $q->product->featured_image }}"
                                        class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full bg-gray-100 flex items-center justify-center">
                                        <i class="ph ph-image text-gray-400 text-lg"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="max-w-[150px] truncate">
                                <a href="{{ route('product.show', $q->product->slug) }}" target="_blank"
                                    class="font-medium text-gray-900 hover:text-brand-600 truncate block">{{ $q->product->title }}</a>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="font-medium text-gray-900">{{ $q->customer_name }}</div>
                        <div class="text-xs text-gray-500 flex items-center gap-1">
                            <span>{{ $q->phone }}</span>
                            <span>&bull;</span>
                            <span
                                class="{{ $q->user_id ? 'text-brand-600' : 'text-gray-400' }}">{{ $q->user_id ? 'Registered' : 'Guest' }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 max-w-xs whitespace-normal" title="{{ $q->question }}">
                        <div class="text-gray-700">
                            {{ \Illuminate\Support\Str::limit($q->question, 70, '...') }}
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        @if($q->status === 'pending')
                            <span
                                class="px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">Pending</span>
                        @elseif($q->status === 'answered')
                            <span
                                class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">Answered</span>
                        @elseif($q->status === 'private')
                            <span
                                class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">Private Reply</span>
                        @else
                            <span
                                class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">Rejected</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-gray-500">{{ $q->created_at->format('M d, Y h:i A') }}</td>
                    <td class="px-6 py-4 text-right">
                        <button @click="openReplyModal({{ json_encode([
        'id' => $q->id,
        'customer_name' => $q->customer_name,
        'phone' => $q->phone,
        'question' => $q->question,
        'answer' => $q->answer,
        'status' => $q->status,
        'product_title' => $q->product->title,
        'product_image' => $q->product->featured_image ? (Str::startsWith($q->product->featured_image, 'http') ? $q->product->featured_image : '/storage/' . $q->product->featured_image) : null
    ]) }})"
                            class="text-brand-600 hover:text-brand-800 font-medium text-sm flex items-center justify-end gap-1 ml-auto">
                            <i class="ph ph-chat-text"></i> Reply / Edit
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        <div class="flex flex-col items-center justify-center">
                            <i class="ph ph-chats-circle text-4xl mb-3 text-gray-300"></i>
                            <p>No product questions found.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="px-6 py-4 border-t border-gray-100">
    {{ $questions->links('vendor.pagination.tailwind-centered', ['alwaysShow' => true]) }}
</div>
