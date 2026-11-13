<!-- In This Article / Table of Contents Component -->
@php
    $toc = $guide->table_of_contents;
@endphp

@if(!empty($toc) && count($toc) > 0)
<div class="my-6 rounded-2xl bg-linear-to-br from-brand-50/40 via-white to-gray-50 border border-brand-100/80 shadow-xs p-5"
     x-data="{ isOpen: true }">
    <!-- TOC Header -->
    <div class="flex items-center justify-between gap-3 cursor-pointer select-none" @click="isOpen = !isOpen">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-brand-500 text-white flex items-center justify-center text-base shadow-xs shrink-0">
                <i class="ph-bold ph-list-numbers"></i>
            </div>
            <div>
                <h2 class="text-sm font-bold text-gray-900 tracking-wide flex items-center gap-2">
                    <span>In This Article</span>
                    <span class="text-[11px] font-normal text-gray-400">({{ count($toc) }} sections)</span>
                </h2>
                <p class="text-[11px] text-gray-500">Click any topic to jump directly to that section</p>
            </div>
        </div>

        <button type="button"
                class="px-2.5 py-1 text-xs font-semibold text-gray-500 hover:text-brand-600 rounded-lg hover:bg-white transition-colors flex items-center gap-1">
            <span x-text="isOpen ? 'Hide' : 'Show'"></span>
            <i class="ph-bold ph-caret-down transition-transform duration-200" :class="{ 'rotate-180': isOpen }"></i>
        </button>
    </div>

    <!-- TOC Navigation Links -->
    <nav x-show="isOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="mt-4 pt-4 border-t border-brand-100/60"
         aria-label="Table of contents">
        <ol class="space-y-2 text-xs font-medium text-gray-700">
            @php $h2Index = 1; @endphp
            @foreach($toc as $item)
                @if($item['level'] === 2)
                    <li class="flex items-start gap-2 group">
                        <span class="w-5 h-5 rounded-full bg-brand-100 text-brand-700 font-bold text-[10px] flex items-center justify-center shrink-0 mt-0.5 group-hover:bg-brand-600 group-hover:text-white transition-colors">
                            {{ $h2Index++ }}
                        </span>
                        <a href="#{{ $item['id'] }}"
                           class="text-gray-800 hover:text-brand-600 hover:underline leading-relaxed transition-colors font-semibold">
                            {{ $item['title'] }}
                        </a>
                    </li>
                @else
                    <li class="flex items-start gap-2 ml-7 group">
                        <span class="text-brand-400 font-bold text-xs shrink-0 select-none">↳</span>
                        <a href="#{{ $item['id'] }}"
                           class="text-gray-600 hover:text-brand-600 hover:underline leading-relaxed transition-colors">
                            {{ $item['title'] }}
                        </a>
                    </li>
                @endif
            @endforeach
        </ol>
    </nav>
</div>
@endif
