@props(['title', 'icon' => null, 'active' => false, 'theme' => 'light'])

@php
    $baseClasses = 'w-full flex items-center justify-between px-4 transition-colors text-sm font-medium cursor-pointer';
    
    if ($theme === 'dark') {
        $btnClasses = $active 
            ? 'py-2.5 rounded-lg bg-gray-800 text-white' 
            : 'py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white';
        $dropdownBg = 'bg-gray-800';
    } else {
        $btnClasses = $active 
            ? 'py-3 rounded-xl bg-brand-50 text-brand-600' 
            : 'py-3 rounded-xl text-gray-600 hover:bg-gray-50';
        $dropdownBg = '';
    }

    $classes = $baseClasses . ' ' . $btnClasses;
@endphp

<div x-data="{ open: {{ $active ? 'true' : 'false' }} }" class="space-y-1">
    <button @click="open = !open" type="button" {{ $attributes->merge(['class' => $classes]) }}>
        <div class="flex items-center gap-3">
            @if($icon)
                <i class="ph ph-{{ $icon }} text-xl"></i>
            @endif
            {{ $title }}
        </div>
        <i class="ph ph-caret-down text-xs transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
    </button>
    
    <div x-show="open" x-transition.opacity>
        <div class="pl-11 pr-4 py-1 space-y-1 {{ $dropdownBg }} rounded-lg mt-1">
            {{ $slot }}
        </div>
    </div>
</div>
