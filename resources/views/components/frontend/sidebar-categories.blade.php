@props(['categories'])

@php
    $initialActiveId = 'null';
    if(isset($categories) && $categories->count() > 0) {
        foreach($categories as $cat) {
            if($cat->children && $cat->children->count() > 0) {
                $childSlugs = $cat->children->pluck('slug')->map(fn($s) => 'shop/' . $s)->toArray();
                if(request()->is('shop/' . $cat->slug) || request()->is($childSlugs)) {
                    $initialActiveId = $cat->id;
                    break;
                }
            }
        }
    }
@endphp

<div x-data="{ activeAccordion: {{ $initialActiveId }} }" class="w-full">
@if(isset($categories) && $categories->count() > 0)
    @foreach($categories as $category)
        @if($category->children && $category->children->count() > 0)
            @php
                $childSlugs = $category->children->pluck('slug')->map(fn($slug) => 'shop/' . $slug)->toArray();
                $isParentActive = request()->is('shop/' . $category->slug) || request()->is($childSlugs);
            @endphp
            <div class="w-full">
                <div class="relative flex items-center group">
                    <a href="{{ route('shop.category', ['category_slug' => $category->slug]) }}" class="w-full flex items-center gap-3 px-3 py-2.5 pr-10 rounded-lg text-sm font-semibold transition-colors cursor-pointer {{ $isParentActive ? 'bg-pink-50 text-pink-600 font-bold' : 'text-gray-800 hover:bg-pink-50/80 hover:text-pink-500' }}">
                        @if($category->image)
                            <img src="{{ Str::startsWith($category->image, ['http://', 'https://']) ? $category->image : Storage::url($category->image) }}" class="w-6 h-6 object-cover rounded-full">
                        @else
                            <i class="ph ph-squares-four text-lg {{ $isParentActive ? 'text-pink-500' : 'text-gray-400 group-hover:text-pink-500' }}"></i>
                        @endif
                        <span>{{ $category->name }}</span>
                    </a>
                    <button @click.prevent="activeAccordion = activeAccordion === {{ $category->id }} ? null : {{ $category->id }}" class="absolute right-2 p-1 cursor-pointer focus:outline-none flex-shrink-0 text-gray-400 hover:text-pink-500 transition-colors {{ $isParentActive ? 'text-pink-500' : '' }}" aria-label="Toggle Subcategories">
                        <i class="ph ph-caret-down transition-transform duration-200" :class="activeAccordion === {{ $category->id }} ? 'rotate-180' : ''"></i>
                    </button>
                </div>
                
                <div x-show="activeAccordion === {{ $category->id }}" x-transition.opacity.duration.200ms x-cloak class="pl-11 pr-3 py-1 space-y-1" style="{{ $initialActiveId == $category->id ? '' : 'display: none;' }}">
                    @foreach($category->children as $child)
                        @php
                            $isChildActive = request()->is('shop/' . $child->slug);
                        @endphp
                        <a href="{{ route('shop.category', ['category_slug' => $child->slug]) }}" class="block px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ $isChildActive ? 'bg-pink-50 text-pink-600 font-bold' : 'text-gray-600 hover:bg-pink-50/80 hover:text-pink-500' }}">
                            {{ $child->name }}
                        </a>
                    @endforeach
                </div>
            </div>
        @else
            @php
                $isSingleActive = request()->is('shop/' . $category->slug);
            @endphp
            <a href="{{ route('shop.category', ['category_slug' => $category->slug]) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition-colors cursor-pointer group {{ $isSingleActive ? 'bg-pink-50 text-pink-600 font-bold' : 'text-gray-800 hover:bg-pink-50/80 hover:text-pink-500' }}">
                @if($category->image)
                    <img src="{{ Str::startsWith($category->image, ['http://', 'https://']) ? $category->image : Storage::url($category->image) }}" class="w-5 h-5 object-cover rounded-full">
                @else
                    <i class="ph ph-squares-four text-lg {{ $isSingleActive ? 'text-pink-500' : 'text-gray-400 group-hover:text-pink-500' }}"></i>
                @endif
                <span>{{ $category->name }}</span>
            </a>
        @endif
    @endforeach
@endif
</div>
