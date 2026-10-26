@props(['activeTab' => 'overview'])

<aside class="w-full md:w-64 shrink-0">
    <!-- Profile Info Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 md:p-5 mb-4">
        <div class="flex items-center gap-4">
            <img src="{{ Auth::user()->avatar ?? '' }}" alt="{{ Auth::user()->name }}" class="update-user-avatar w-14 h-14 rounded-full object-cover border-4 border-brand-50 shadow-sm shrink-0 {{ Auth::user()->avatar ? '' : 'hidden' }}">
            <div class="update-user-initial-wrapper w-14 h-14 rounded-full bg-brand-100 flex items-center justify-center text-brand-600 font-bold text-lg shrink-0 {{ Auth::user()->avatar ? 'hidden' : '' }}">
                <span class="update-user-initial">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
            </div>
            <div class="min-w-0 flex-1">
                <h2 class="update-user-name font-bold text-gray-900 truncate" title="{{ Auth::user()->name }}">{{ Auth::user()->name }}</h2>
                <p class="text-xs text-gray-500 truncate" title="{{ Auth::user()->phone ?? Auth::user()->email }}">{{ Auth::user()->phone ?? Auth::user()->email }}</p>
            </div>
        </div>
    </div>

    <!-- Menu Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 md:p-6 mb-0 md:mb-6">
        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 px-2 md:hidden">Menu</h3>
        <nav class="space-y-1">
            <x-nav-link href="{{ route('dashboard') }}" icon="squares-four" theme="light" :active="$activeTab === 'overview'">
                Overview
            </x-nav-link>
            <x-nav-link href="{{ route('dashboard.orders') }}" icon="shopping-bag" theme="light" :active="$activeTab === 'orders'">
                My Orders
            </x-nav-link>
            <x-nav-link href="{{ route('dashboard.reviews') }}" icon="star" theme="light" :active="$activeTab === 'reviews'">
                My Reviews
            </x-nav-link>
            <x-nav-link href="{{ route('dashboard.profile') }}" icon="user" theme="light" :active="$activeTab === 'profile'">
                Profile Settings
            </x-nav-link>
        </nav>

        <div class="mt-2 pt-2 border-t border-gray-100">
            <a href="{{ route('logout') }}" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-red-500 hover:bg-red-50 transition-colors text-left text-sm font-medium cursor-pointer">
                <i class="ph ph-sign-out text-xl"></i> Logout
            </a>
        </div>
    </div>
</aside>
