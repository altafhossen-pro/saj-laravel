@props(['paginator'])

@if ($paginator->hasPages())
    {{ $paginator->onEachSide(1)->withQueryString()->links() }}
@else
    <!-- Fallback for 1 page so pagination always shows (or just hide it) -->
    <div class="text-sm text-gray-500 text-right">
        Showing {{ $paginator->total() }} results
    </div>
@endif
