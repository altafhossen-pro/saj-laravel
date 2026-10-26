@if ($paginator->hasPages() || (isset($alwaysShow) && $alwaysShow))
    <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-center">
        <div class="flex items-center gap-1 sm:gap-2">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span class="w-10 h-10 flex items-center justify-center rounded-xl border border-gray-100 bg-gray-50 text-gray-400 cursor-not-allowed">
                    <i class="ph ph-caret-left"></i>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="w-10 h-10 flex items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-700 hover:border-brand-500 hover:text-brand-600 transition-colors">
                    <i class="ph ph-caret-left"></i>
                </a>
            @endif

            {{-- Custom Pagination Window --}}
            @php
                $start = max(1, $paginator->currentPage() - 2);
                $end = min($paginator->lastPage(), $paginator->currentPage() + 2);
            @endphp

            @if($start > 1)
                <a href="{{ $paginator->url(1) }}" class="w-10 h-10 flex items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-700 hover:border-brand-500 hover:text-brand-600 transition-colors font-medium">1</a>
                @if($start > 2)
                    <span class="w-10 h-10 flex items-center justify-center text-gray-500">...</span>
                @endif
            @endif

            @for ($page = $start; $page <= $end; $page++)
                @if ($page == $paginator->currentPage())
                    <span class="w-10 h-10 flex items-center justify-center rounded-xl bg-brand-600 text-white font-medium shadow-sm" aria-current="page">{{ $page }}</span>
                @else
                    <a href="{{ $paginator->url($page) }}" class="w-10 h-10 flex items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-700 hover:border-brand-500 hover:text-brand-600 transition-colors font-medium">{{ $page }}</a>
                @endif
            @endfor

            @if($end < $paginator->lastPage())
                @if($end < $paginator->lastPage() - 1)
                    <span class="w-10 h-10 flex items-center justify-center text-gray-500">...</span>
                @endif
                <a href="{{ $paginator->url($paginator->lastPage()) }}" class="w-10 h-10 flex items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-700 hover:border-brand-500 hover:text-brand-600 transition-colors font-medium">{{ $paginator->lastPage() }}</a>
            @endif

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="w-10 h-10 flex items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-700 hover:border-brand-500 hover:text-brand-600 transition-colors">
                    <i class="ph ph-caret-right"></i>
                </a>
            @else
                <span class="w-10 h-10 flex items-center justify-center rounded-xl border border-gray-100 bg-gray-50 text-gray-400 cursor-not-allowed">
                    <i class="ph ph-caret-right"></i>
                </span>
            @endif
        </div>
    </nav>
@endif