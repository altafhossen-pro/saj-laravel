<!-- Customer (account profile - Matches Image 2) -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <h2 class="text-lg font-bold text-gray-900 mb-4">Customer</h2>

    <div class="flex items-center gap-3 mb-6">
        @if($account && $account->avatar)
            <img src="{{ $account->avatar }}" alt="{{ $customerName }}" class="w-12 h-12 rounded-full object-cover border border-gray-200">
        @else
            <div class="w-12 h-12 rounded-full bg-brand-50 flex items-center justify-center text-brand-600 font-bold text-xl">
                {{ strtoupper(substr($customerName, 0, 1)) }}
            </div>
        @endif
        <div>
            @if($account)
                <a href="{{ route('admin.customers.show', $account->id) }}" class="font-bold text-gray-900 hover:text-brand-600 hover:underline transition-colors inline-flex items-center gap-1.5" title="View Customer Details">
                    <span>{{ $customerName }}</span>
                    <i class="ph ph-arrow-square-out text-sm text-gray-400"></i>
                </a>
            @else
                <h3 class="font-bold text-gray-900">{{ $customerName }}</h3>
            @endif
            <p class="text-sm text-gray-500">{{ $account ? 'Customer since ' . $account->created_at->format('M Y') : 'Guest checkout' }}</p>
        </div>
    </div>

    <div class="space-y-4">
        <div class="flex items-start gap-3">
            <i class="ph ph-envelope-simple text-gray-400 text-lg mt-0.5"></i>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Email</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $customerEmail ?: 'Not provided' }}</p>
            </div>
        </div>
        <div class="flex items-start gap-3">
            <i class="ph ph-phone text-gray-400 text-lg mt-0.5"></i>
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</p>
                <p class="text-sm text-gray-900 mt-0.5">{{ $customerPhone ?: 'Not provided' }}</p>
            </div>
        </div>
    </div>
</div>
