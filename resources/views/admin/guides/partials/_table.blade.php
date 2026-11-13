<!-- Guides Data Table Component -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50/70 text-[11px] font-bold uppercase tracking-wider text-gray-400">
                    <th class="py-3 px-4">Article</th>
                    <th class="py-3 px-4">Topic</th>
                    <th class="py-3 px-4">Read Time</th>
                    <th class="py-3 px-4 text-center">Views</th>
                    <th class="py-3 px-4 text-center">Status</th>
                    <th class="py-3 px-4">Published Date</th>
                    <th class="py-3 px-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-xs text-gray-600">
                @forelse($guides as $guide)
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="py-3.5 px-4">
                        <div class="flex items-center gap-3">
                            <div class="w-14 h-11 rounded-lg bg-gray-100 border border-gray-200 overflow-hidden shrink-0 flex items-center justify-center">
                                @if($guide->featured_image)
                                    <img src="{{ $guide->featured_image_url }}" alt="{{ $guide->title }}" class="w-full h-full object-cover">
                                @else
                                    <i class="ph ph-image text-gray-400 text-lg"></i>
                                @endif
                            </div>
                            <div class="min-w-0 max-w-sm">
                                <a href="{{ route('admin.guides.edit', $guide) }}" class="font-bold text-gray-900 hover:text-brand-600 transition-colors line-clamp-1">
                                    {{ $guide->title }}
                                </a>
                                <span class="text-[11px] text-gray-400 font-mono flex items-center gap-1 mt-0.5">
                                    /guides/{{ $guide->slug }}
                                </span>
                            </div>
                        </div>
                    </td>

                    <td class="py-3.5 px-4">
                        @if($guide->topic)
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-brand-50 text-brand-700 border border-brand-100">
                                {{ $guide->topic }}
                            </span>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>

                    <td class="py-3.5 px-4 font-medium text-gray-700">
                        {{ $guide->reading_time_minutes }} min read
                    </td>

                    <td class="py-3.5 px-4 text-center font-bold text-gray-800">
                        {{ number_format($guide->views_count) }}
                    </td>

                    <td class="py-3.5 px-4 text-center">
                        <label class="relative inline-flex items-center cursor-pointer select-none">
                            <input type="checkbox"
                                   class="sr-only peer"
                                   {{ $guide->is_published ? 'checked' : '' }}
                                   @change="togglePublish('{{ route('admin.guides.toggle-publish', $guide) }}', $event)">
                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                        </label>
                    </td>

                    <td class="py-3.5 px-4 text-gray-500">
                        {{ $guide->published_at ? $guide->published_at->format('M d, Y') : 'Draft' }}
                    </td>

                    <td class="py-3.5 px-4 text-right">
                        <div class="flex items-center justify-end gap-1.5">
                            @if($guide->is_published)
                            <a href="{{ route('guides.show', $guide->slug) }}" target="_blank"
                               class="p-2 text-gray-400 hover:text-brand-600 rounded-lg hover:bg-gray-100 transition-colors"
                               title="View on site">
                                <i class="ph ph-arrow-square-out text-base"></i>
                            </a>
                            @endif

                            <a href="{{ route('admin.guides.edit', $guide) }}"
                               class="p-2 text-gray-500 hover:text-brand-600 rounded-lg hover:bg-brand-50 transition-colors"
                               title="Edit guide">
                                <i class="ph-bold ph-pencil-simple text-base"></i>
                            </a>

                            <form method="POST" action="{{ route('admin.guides.destroy', $guide) }}"
                                  onsubmit="return confirm('Are you sure you want to delete this guide?')"
                                  class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="p-2 text-gray-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition-colors cursor-pointer"
                                        title="Delete guide">
                                    <i class="ph-bold ph-trash text-base"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-12 px-4 text-center">
                        <div class="max-w-xs mx-auto text-center space-y-3">
                            <div class="w-12 h-12 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center text-2xl mx-auto">
                                <i class="ph ph-book-open"></i>
                            </div>
                            <h3 class="text-sm font-bold text-gray-900">No Buying Guides Found</h3>
                            <p class="text-xs text-gray-500">
                                Create in-depth buying guides to improve your search engine rankings and assist customers in making smart choices.
                            </p>
                            <a href="{{ route('admin.guides.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-brand-600 text-white font-bold text-xs rounded-xl shadow-xs hover:bg-brand-700 transition-colors">
                                <i class="ph-bold ph-plus"></i>
                                <span>Create Your First Guide</span>
                            </a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($guides->hasPages())
    <div class="p-4 border-t border-gray-100">
        {{ $guides->links() }}
    </div>
    @endif
</div>
