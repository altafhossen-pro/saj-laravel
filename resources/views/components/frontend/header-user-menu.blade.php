@auth
    <div class="hidden sm:flex items-center relative group" 
         x-data="{ open: false }" 
         @click.outside="open = false">
         
        <button @click="open = !open" class="flex items-center gap-2.5 text-sm font-bold text-gray-800 hover:text-brand-600 transition-colors focus:outline-none cursor-pointer py-2">
            <img src="{{ Auth::user()->avatar ?? '' }}" alt="{{ Auth::user()->name }}" class="update-user-avatar w-9 h-9 rounded-full object-cover border-2 border-gray-100 {{ Auth::user()->avatar ? '' : 'hidden' }}">
            <div class="update-user-initial-wrapper w-9 h-9 rounded-full bg-brand-100 border-2 border-brand-50 flex items-center justify-center text-brand-600 {{ Auth::user()->avatar ? 'hidden' : '' }}">
                <span class="update-user-initial">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
            </div>
            <span class="update-user-name">{{ Auth::user()->name }}</span>
            <i class="ph ph-caret-down text-gray-400 text-xs transition-transform duration-200" :class="{'rotate-180': open}"></i>
        </button>

        <!-- Dropdown Wrapper -->
        <div x-show="open" style="display: none;" 
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-2"
            class="absolute top-[calc(100%-4px)] right-0 pt-2 z-[99999]">
            
            <div class="w-48 bg-white rounded-xl shadow-lg shadow-black/5 border border-gray-100 py-2 relative">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-brand-600 transition-colors font-medium">
                    <i class="ph ph-squares-four text-lg text-gray-400"></i> Dashboard
                </a>
                
                @if(Auth::user()->role === 'super_admin')
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-brand-600 transition-colors font-medium border-t border-gray-50 mt-1 pt-1">
                    <i class="ph ph-shield-star text-lg text-brand-500"></i> Admin Panel
                </a>
                @endif
                
                <a href="{{ route('dashboard.orders') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-brand-600 transition-colors font-medium">
                    <i class="ph ph-package text-lg text-gray-400"></i> My Orders
                </a>
                <a href="{{ route('dashboard.profile') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-brand-600 transition-colors font-medium border-b border-gray-100 mb-1 pb-3">
                    <i class="ph ph-user text-lg text-gray-400"></i> Profile Setting
                </a>
                
                <!-- Logout -->
                <form method="POST" action="{{ route('logout') }}" class="px-2">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-2.5 px-2 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg transition-colors font-medium cursor-pointer">
                        <i class="ph ph-sign-out text-lg"></i> Sign Out
                    </button>
                </form>
            </div>
            
            <!-- Arrow -->
            <div class="absolute top-[2px] right-6 w-3 h-3 bg-white border-t border-l border-gray-100 rotate-45"></div>
        </div>
    </div>
@else
    <a href="{{ route('login') }}" class="hidden sm:flex items-center gap-2 text-sm font-medium text-gray-700 hover:text-brand-600 transition-colors">
        <i class="ph ph-user text-xl"></i> Login
    </a>
@endauth
