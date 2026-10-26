@extends('layouts.admin')

@section('header_title', 'Coupons & Discounts')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Coupons & Promo Codes</h1>
            <p class="text-sm text-gray-500 mt-1">Create and manage dynamic discount codes, usage limits, and product/category restrictions.</p>
        </div>
        <div>
            <a href="{{ route('admin.coupons.create') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-brand-600 text-white font-semibold text-sm hover:bg-brand-700 transition shadow-sm cursor-pointer">
                <i class="ph-bold ph-plus text-base"></i>
                Create Coupon
            </a>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col sm:flex-row gap-4 items-center justify-between">
        <form action="{{ route('admin.coupons.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto flex-1">
            <div class="relative flex-1 max-w-md">
                <i class="ph ph-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search by code or description..."
                       class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-10 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-colors">
            </div>

            <div class="w-full sm:w-44">
                <select name="status" onchange="this.form.submit()"
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-colors">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                </select>
            </div>

            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('admin.coupons.index') }}"
                   class="px-4 py-2 bg-gray-100 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-200 transition text-center flex items-center justify-center gap-1">
                    <i class="ph ph-x"></i> Clear
                </a>
            @endif
        </form>
    </div>

    <!-- Coupons Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-gray-50/80 text-gray-500 font-semibold border-b border-gray-100 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Coupon Code</th>
                        <th class="px-6 py-4">Discount</th>
                        <th class="px-6 py-4">Scope</th>
                        <th class="px-6 py-4">Validity</th>
                        <th class="px-6 py-4">Usage</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($coupons as $coupon)
                        @php
                            $isExpired = $coupon->expires_at && $coupon->expires_at->isPast();
                        @endphp
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center font-bold text-sm shrink-0 border border-brand-100">
                                        <i class="ph-bold ph-ticket text-lg"></i>
                                    </div>
                                    <div>
                                        <div class="font-mono font-bold text-gray-900 tracking-wider text-sm flex items-center gap-2">
                                            <span>{{ $coupon->code }}</span>
                                            @if($coupon->first_order_only)
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">1st Order</span>
                                            @endif
                                        </div>
                                        @if($coupon->description)
                                            <p class="text-xs text-gray-500 mt-0.5 truncate max-w-xs">{{ $coupon->description }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if($coupon->discount_type === 'free_shipping')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                        <i class="ph-bold ph-truck"></i> Free Shipping
                                    </span>
                                @elseif($coupon->discount_type === 'percentage')
                                    <div>
                                        <span class="font-bold text-gray-900 text-sm">{{ (float)$coupon->discount_amount }}% OFF</span>
                                        @if($coupon->max_discount_amount)
                                            <span class="text-xs text-gray-500 block">Max ৳{{ number_format($coupon->max_discount_amount, 2) }}</span>
                                        @endif
                                    </div>
                                @else
                                    <div>
                                        <span class="font-bold text-gray-900 text-sm">৳{{ number_format($coupon->discount_amount, 2) }} OFF</span>
                                    </div>
                                @endif

                                @if($coupon->min_order_amount)
                                    <span class="text-[11px] text-gray-400 block mt-0.5">Min Order: ৳{{ number_format($coupon->min_order_amount, 2) }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($coupon->target_type === 'all')
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-medium bg-gray-100 text-gray-700">All Products</span>
                                @elseif($coupon->target_type === 'category')
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-medium bg-purple-50 text-purple-700 border border-purple-200">
                                        Categories ({{ count($coupon->target_ids ?? []) }})
                                    </span>
                                @elseif($coupon->target_type === 'product')
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        Specific Products ({{ count($coupon->target_ids ?? []) }})
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($isExpired)
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-red-50 text-red-700 border border-red-200">Expired</span>
                                    <span class="text-[11px] text-gray-400 block mt-0.5">{{ $coupon->expires_at->format('M d, Y') }}</span>
                                @elseif($coupon->expires_at)
                                    <span class="text-xs font-medium text-gray-700 block">Until {{ $coupon->expires_at->format('M d, Y') }}</span>
                                    <span class="text-[11px] text-gray-400">{{ $coupon->expires_at->diffForHumans() }}</span>
                                @else
                                    <span class="text-xs text-gray-500 font-medium">Lifetime / No Expiry</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-xs">
                                    <span class="font-bold text-gray-900">{{ $coupon->used_count }}</span>
                                    @if($coupon->usage_limit_total)
                                        <span class="text-gray-400">/ {{ $coupon->usage_limit_total }} used</span>
                                        @php
                                            $percent = min(100, round(($coupon->used_count / $coupon->usage_limit_total) * 100));
                                        @endphp
                                        <div class="w-24 h-1.5 bg-gray-100 rounded-full mt-1.5 overflow-hidden">
                                            <div class="h-full bg-brand-500 rounded-full" style="width: {{ $percent }}%"></div>
                                        </div>
                                    @else
                                        <span class="text-gray-400">used (unlimited)</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <form action="{{ route('admin.coupons.toggle-status', $coupon) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold transition cursor-pointer {{ $coupon->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100' : 'bg-gray-100 text-gray-500 border border-gray-200 hover:bg-gray-200' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $coupon->is_active ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                                        {{ $coupon->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </form>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.coupons.edit', $coupon) }}"
                                       class="p-2 text-gray-500 hover:text-brand-600 hover:bg-brand-50 rounded-lg transition"
                                       title="Edit Coupon">
                                        <i class="ph-bold ph-pencil-simple text-base"></i>
                                    </a>

                                    <form action="{{ route('admin.coupons.destroy', $coupon) }}" method="POST"
                                          onsubmit="return confirm('Are you sure you want to delete coupon {{ $coupon->code }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition cursor-pointer"
                                                title="Delete Coupon">
                                            <i class="ph-bold ph-trash text-base"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <div class="w-16 h-16 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center mx-auto mb-3 text-2xl">
                                    <i class="ph ph-ticket"></i>
                                </div>
                                <h3 class="text-base font-bold text-gray-800">No Coupons Found</h3>
                                <p class="text-xs text-gray-500 mt-1 max-w-sm mx-auto">Create discount coupons to boost your sales and reward your shoppers.</p>
                                <a href="{{ route('admin.coupons.create') }}" class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-brand-600 text-white rounded-xl text-xs font-semibold hover:bg-brand-700 transition">
                                    <i class="ph-bold ph-plus"></i> Create First Coupon
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($coupons->hasPages())
            <div class="p-4 border-t border-gray-100">
                {{ $coupons->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
