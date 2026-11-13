<!-- Filters & Search Toolbar Component -->
<div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs">
    <form method="GET" action="{{ route('admin.guides.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
        <div class="sm:col-span-5 relative">
            <i class="ph ph-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-base"></i>
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search by title, topic, excerpt..."
                   class="w-full pl-10 pr-4 py-2 text-xs border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500 bg-gray-50 focus:bg-white transition-colors">
        </div>

        <div class="sm:col-span-3">
            <select name="topic" class="w-full px-3 py-2 text-xs border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500 bg-gray-50 focus:bg-white font-medium text-gray-700">
                <option value="">All Topics</option>
                @foreach($topics as $t)
                    <option value="{{ $t }}" {{ request('topic') === $t ? 'selected' : '' }}>{{ $t }}</option>
                @endforeach
            </select>
        </div>

        <div class="sm:col-span-2">
            <select name="status" class="w-full px-3 py-2 text-xs border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500 bg-gray-50 focus:bg-white font-medium text-gray-700">
                <option value="">All Status</option>
                <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Published</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
            </select>
        </div>

        <div class="sm:col-span-2 flex items-center gap-2">
            <button type="submit" class="flex-1 px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white text-xs font-bold rounded-xl transition-colors cursor-pointer">
                Filter
            </button>
            @if(request()->hasAny(['search', 'topic', 'status']))
            <a href="{{ route('admin.guides.index') }}" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-semibold rounded-xl transition-colors cursor-pointer" title="Reset Filters">
                <i class="ph ph-arrows-counter-clockwise text-sm"></i>
            </a>
            @endif
        </div>
    </form>
</div>
