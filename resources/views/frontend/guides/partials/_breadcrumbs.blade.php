<!-- Breadcrumb Component -->
<nav class="flex items-center gap-2 text-xs text-gray-500 py-1.5 overflow-x-auto whitespace-nowrap" aria-label="Breadcrumb">
    <a href="/" class="hover:text-brand-600 transition-colors flex items-center gap-1">
        <i class="ph ph-house text-sm"></i>
        <span>Home</span>
    </a>
    <span class="text-gray-300">/</span>
    <a href="{{ route('guides.index') }}" class="hover:text-brand-600 transition-colors font-medium {{ !isset($guide) ? 'text-brand-600 font-bold' : '' }}">
        Buying Guides
    </a>
    @if(isset($guide))
        <span class="text-gray-300">/</span>
        @if($guide->topic)
            <a href="{{ route('guides.index', ['topic' => $guide->topic]) }}" class="hover:text-brand-600 transition-colors">
                {{ $guide->topic }}
            </a>
            <span class="text-gray-300">/</span>
        @endif
        <span class="text-gray-800 font-semibold truncate max-w-xs sm:max-w-md" title="{{ $guide->title }}">
            {{ $guide->title }}
        </span>
    @endif
</nav>
