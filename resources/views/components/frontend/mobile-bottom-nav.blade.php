@php
    $isHome = request()->routeIs('home');
    $isProfile = request()->routeIs('dashboard*') || request()->routeIs('login');
    // Category active state isn't strictly defined by route since it's a sidebar, 
    // but we can give it active state if sidebar is open via Alpine
@endphp

<div class="sm:hidden fixed bottom-0 left-0 right-0 bg-white rounded-t-3xl shadow-[0_-10px_40px_rgba(0,0,0,0.08)] z-50 px-2 h-[64px] flex items-center justify-between" style="padding-bottom: env(safe-area-inset-bottom);">
    
    <!-- Category -->
    <button @click="sidebarOpen = true" class="relative w-1/4 h-full flex flex-col items-center justify-center text-gray-500 hover:text-brand-600">
        <i class="ph ph-squares-four text-[22px] mb-1"></i>
        <span class="text-[10px] font-medium">Category</span>
    </button>
    
    <!-- Home -->
    <a href="{{ route('home') }}" class="relative w-1/4 h-full flex flex-col items-center justify-center text-gray-500 hover:text-brand-600 {{ $isHome ? 'text-brand-600' : '' }}">
        <!-- Floating Active Icon -->
        <div class="absolute -top-4 left-1/2 -translate-x-1/2 w-12 h-12 bg-white rounded-full flex items-center justify-center shadow-[0_-4px_10px_rgba(0,0,0,0.04)] transition-all duration-300 {{ $isHome ? 'opacity-100 scale-100 translate-y-0' : 'opacity-0 scale-50 translate-y-4 pointer-events-none' }}">
            <div class="w-9 h-9 bg-brand-50 rounded-full flex items-center justify-center">
                <i class="ph-fill ph-house text-xl text-brand-600"></i>
            </div>
        </div>
        
        <!-- Default Icon -->
        <i class="ph ph-house text-[22px] mb-1 transition-all duration-300 {{ $isHome ? 'opacity-0 translate-y-4' : 'opacity-100 translate-y-0' }}"></i>
        <span class="text-[10px] font-medium transition-all duration-300 {{ $isHome ? '-translate-y-1' : '' }}">Home</span>
    </a>
    
    <!-- Cart -->
    <button @click="$dispatch('open-cart-drawer')" class="relative w-1/4 h-full flex flex-col items-center justify-center text-gray-500 hover:text-brand-600">
        <div class="relative mb-1">
            <i class="ph ph-shopping-cart text-[22px]"></i>
            <span x-show="$store.cart.totalItems > 0" x-text="$store.cart.totalItems" class="absolute -top-1.5 -right-2 min-w-[16px] h-[16px] bg-red-500 text-white rounded-full text-[9px] font-bold flex items-center justify-center px-1 shadow-sm leading-none" style="display: none;"></span>
        </div>
        <span class="text-[10px] font-medium">Cart</span>
    </button>
    
    <!-- Profile / Login -->
    <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="relative w-1/4 h-full flex flex-col items-center justify-center text-gray-500 hover:text-brand-600 {{ $isProfile ? 'text-brand-600' : '' }}">
        <!-- Floating Active Icon -->
        <div class="absolute -top-4 left-1/2 -translate-x-1/2 w-12 h-12 bg-white rounded-full flex items-center justify-center shadow-[0_-4px_10px_rgba(0,0,0,0.04)] transition-all duration-300 {{ $isProfile ? 'opacity-100 scale-100 translate-y-0' : 'opacity-0 scale-50 translate-y-4 pointer-events-none' }}">
            <div class="w-9 h-9 bg-brand-50 rounded-full flex items-center justify-center overflow-hidden">
                @if(auth()->check() && auth()->user()->avatar)
                    <img src="{{ auth()->user()->avatar }}" class="update-user-avatar w-full h-full object-cover" alt="Profile">
                @else
                    <i class="ph-fill ph-user text-xl text-brand-600"></i>
                @endif
            </div>
        </div>
        
        <!-- Default Icon -->
        <div class="mb-1 transition-all duration-300 flex items-center justify-center h-[22px] {{ $isProfile ? 'opacity-0 translate-y-4 pointer-events-none' : 'opacity-100 translate-y-0' }}">
            @if(auth()->check() && auth()->user()->avatar)
                <img src="{{ auth()->user()->avatar }}" class="update-user-avatar w-5 h-5 rounded-full object-cover shadow-sm border border-gray-200 block" alt="Profile">
            @else
                <i class="ph ph-user text-[22px]"></i>
            @endif
        </div>
        <span class="text-[10px] font-medium transition-all duration-300 {{ $isProfile ? '-translate-y-1' : '' }}">{{ auth()->check() ? 'Dashboard' : 'Login' }}</span>
    </a>
</div>
