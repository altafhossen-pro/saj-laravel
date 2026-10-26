@props(['href', 'icon' => null, 'active' => false, 'theme' => 'light'])

@php
    $baseClasses = 'flex items-center gap-3 px-4 transition-colors text-sm font-medium cursor-pointer';
    
    if ($theme === 'dark') {
        $themeClasses = $active 
            ? 'py-2.5 rounded-lg bg-brand-600 text-white' 
            : 'py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white';
    } else {
        $themeClasses = $active 
            ? 'py-3 rounded-xl bg-brand-50 text-brand-600' 
            : 'py-3 rounded-xl text-gray-600 hover:bg-gray-50';
    }

    $classes = $baseClasses . ' ' . $themeClasses;
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    @if($icon)
        <i class="ph ph-{{ $icon }} text-xl"></i>
    @endif
    {{ $slot }}
</a>
