<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BuyingGuide;
use App\Models\Product\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BuyingGuideController extends Controller
{
    /**
     * Display a listing of buying guides.
     */
    public function index(Request $request): View
    {
        $query = BuyingGuide::query()->with('author');

        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
                    ->orWhere('topic', 'like', "%{$search}%");
            });
        }

        if ($topic = $request->input('topic')) {
            $query->where('topic', $topic);
        }

        if ($request->has('status') && $request->input('status') !== '') {
            $isPublished = $request->input('status') === 'published';
            $query->where('is_published', $isPublished);
        }

        $guides = $query->latest()->paginate(15)->withQueryString();

        $topics = BuyingGuide::whereNotNull('topic')
            ->distinct()
            ->pluck('topic')
            ->filter()
            ->values();

        $totalCount = BuyingGuide::count();
        $publishedCount = BuyingGuide::where('is_published', true)->count();
        $draftCount = BuyingGuide::where('is_published', false)->count();

        return view('admin.guides.index', compact(
            'guides',
            'topics',
            'totalCount',
            'publishedCount',
            'draftCount'
        ));
    }

    /**
     * Show the form for creating a new guide.
     */
    public function create(): View
    {
        $existingTopics = BuyingGuide::whereNotNull('topic')
            ->distinct()
            ->pluck('topic')
            ->filter()
            ->values();

        return view('admin.guides.create', compact('existingTopics'));
    }

    /**
     * Store a newly created guide.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:buying_guides,slug',
            'topic' => 'nullable|string|max:100',
            'excerpt' => 'nullable|string|max:1000',
            'content' => 'required|string',
            'featured_image' => 'nullable|image|max:4096',
            'featured_image_url' => 'nullable|url|max:500',
            'related_product_ids' => 'nullable|array',
            'related_product_ids.*' => 'integer|exists:products,id',
            'is_published' => 'nullable|boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:255',
            'canonical_url' => 'nullable|string|max:255',
        ]);

        // Auto-generate unique slug
        $baseSlug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['title']);
        $slug = $baseSlug;
        $counter = 1;
        while (BuyingGuide::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $imagePath = null;
        if ($request->hasFile('featured_image')) {
            $imagePath = $request->file('featured_image')->store('guides', 'public');
        } elseif (!empty($validated['featured_image_url'])) {
            $imagePath = $validated['featured_image_url'];
        }

        $isPublished = $request->boolean('is_published', true);
        $readingTime = BuyingGuide::estimateReadingTime($validated['content']);

        $topic = !empty($validated['topic']) ? trim($validated['topic']) : null;
        $excerpt = !empty($validated['excerpt']) ? trim($validated['excerpt']) : Str::limit(strip_tags($validated['content']), 180);
        $metaTitle = !empty($validated['meta_title']) ? $validated['meta_title'] : $validated['title'];
        $metaDesc = !empty($validated['meta_description']) ? $validated['meta_description'] : ($excerpt ?: Str::limit(strip_tags($validated['content']), 155));

        $guide = BuyingGuide::create([
            'title' => $validated['title'],
            'slug' => $slug,
            'topic' => $topic,
            'excerpt' => $excerpt,
            'content' => $validated['content'],
            'featured_image' => $imagePath,
            'related_product_ids' => array_values(array_unique($validated['related_product_ids'] ?? [])),
            'reading_time_minutes' => $readingTime,
            'is_published' => $isPublished,
            'published_at' => $isPublished ? now() : null,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDesc,
            'meta_keywords' => $validated['meta_keywords'] ?? null,
            'canonical_url' => $validated['canonical_url'] ?? null,
            'created_by' => auth()->id(),
        ]);

        $this->clearSitemapCache();

        return redirect()->route('admin.guides.index')
            ->with('success', 'Buying Guide "' . $guide->title . '" created successfully.');
    }

    /**
     * Show the form for editing the specified guide.
     */
    public function edit(BuyingGuide $guide): View
    {
        $existingTopics = BuyingGuide::whereNotNull('topic')
            ->distinct()
            ->pluck('topic')
            ->filter()
            ->values();

        // Load initially selected products
        $selectedProducts = [];
        if (!empty($guide->related_product_ids)) {
            $selectedProducts = Product::whereIn('id', $guide->related_product_ids)
                ->with(['variants'])
                ->get(['id', 'title', 'slug', 'base_price', 'featured_image'])
                ->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'title' => $p->title,
                        'sku' => $p->variants->first()?->sku ?? ('ID: ' . $p->id),
                        'price' => (float) $p->base_price,
                        'image' => $p->primary_image_url,
                    ];
                });
        }

        return view('admin.guides.edit', compact('guide', 'existingTopics', 'selectedProducts'));
    }

    /**
     * Update the specified guide.
     */
    public function update(Request $request, BuyingGuide $guide): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:buying_guides,slug,' . $guide->id,
            'topic' => 'nullable|string|max:100',
            'excerpt' => 'nullable|string|max:1000',
            'content' => 'required|string',
            'featured_image' => 'nullable|image|max:4096',
            'featured_image_url' => 'nullable|url|max:500',
            'related_product_ids' => 'nullable|array',
            'related_product_ids.*' => 'integer|exists:products,id',
            'is_published' => 'nullable|boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:255',
            'canonical_url' => 'nullable|string|max:255',
        ]);

        $cleanSlug = Str::slug($validated['slug']);
        if ($cleanSlug !== $guide->slug) {
            $baseSlug = $cleanSlug;
            $counter = 1;
            while (BuyingGuide::where('slug', $cleanSlug)->where('id', '!=', $guide->id)->exists()) {
                $cleanSlug = "{$baseSlug}-{$counter}";
                $counter++;
            }
        }

        $imagePath = $guide->featured_image;
        if ($request->hasFile('featured_image')) {
            // Remove old local image if applicable
            if ($guide->featured_image && !Str::startsWith($guide->featured_image, ['http://', 'https://'])) {
                Storage::disk('public')->delete($guide->featured_image);
            }
            $imagePath = $request->file('featured_image')->store('guides', 'public');
        } elseif (!empty($validated['featured_image_url'])) {
            $imagePath = $validated['featured_image_url'];
        }

        $isPublished = $request->boolean('is_published', true);
        $readingTime = BuyingGuide::estimateReadingTime($validated['content']);

        $publishedAt = $guide->published_at;
        if ($isPublished && !$publishedAt) {
            $publishedAt = now();
        }

        $topic = !empty($validated['topic']) ? trim($validated['topic']) : null;
        $excerpt = !empty($validated['excerpt']) ? trim($validated['excerpt']) : Str::limit(strip_tags($validated['content']), 180);
        $metaTitle = !empty($validated['meta_title']) ? $validated['meta_title'] : $validated['title'];
        $metaDesc = !empty($validated['meta_description']) ? $validated['meta_description'] : ($excerpt ?: Str::limit(strip_tags($validated['content']), 155));

        $guide->update([
            'title' => $validated['title'],
            'slug' => $cleanSlug,
            'topic' => $topic,
            'excerpt' => $excerpt,
            'content' => $validated['content'],
            'featured_image' => $imagePath,
            'related_product_ids' => array_values(array_unique($validated['related_product_ids'] ?? [])),
            'reading_time_minutes' => $readingTime,
            'is_published' => $isPublished,
            'published_at' => $publishedAt,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDesc,
            'meta_keywords' => $validated['meta_keywords'] ?? null,
            'canonical_url' => $validated['canonical_url'] ?? null,
        ]);

        $this->clearSitemapCache();

        return redirect()->route('admin.guides.index')
            ->with('success', 'Buying Guide "' . $guide->title . '" updated successfully.');
    }

    /**
     * Remove the specified guide.
     */
    public function destroy(BuyingGuide $guide): RedirectResponse
    {
        $title = $guide->title;
        $guide->delete();

        $this->clearSitemapCache();

        return redirect()->route('admin.guides.index')
            ->with('success', 'Buying Guide "' . $title . '" moved to trash.');
    }

    /**
     * Toggle publish status via AJAX or form.
     */
    public function togglePublish(BuyingGuide $guide): JsonResponse
    {
        $newStatus = !$guide->is_published;
        $guide->is_published = $newStatus;
        if ($newStatus && !$guide->published_at) {
            $guide->published_at = now();
        }
        $guide->save();

        $this->clearSitemapCache();

        return response()->json([
            'success' => true,
            'is_published' => $guide->is_published,
            'message' => $guide->is_published ? 'Guide published live.' : 'Guide changed to draft.',
        ]);
    }

    /**
     * AJAX search endpoint for related products picker.
     */
    public function searchProducts(Request $request): JsonResponse
    {
        $query = trim($request->input('q', ''));
        if (strlen($query) < 2) {
            return response()->json(['products' => []]);
        }

        $products = Product::where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhereHas('variants', function ($vq) use ($query) {
                        $vq->where('sku', 'like', "%{$query}%");
                    });
            })
            ->with(['variants'])
            ->take(10)
            ->get(['id', 'title', 'slug', 'base_price', 'featured_image'])
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'title' => $p->title,
                    'sku' => $p->variants->first()?->sku ?? ('ID: ' . $p->id),
                    'price' => (float) $p->base_price,
                    'image' => $p->primary_image_url,
                ];
            });

        return response()->json(['products' => $products]);
    }

    /**
     * Helper to clear cached XML sitemaps.
     */
    private function clearSitemapCache(): void
    {
        $host = request()->getHost() ?: 'default';
        Cache::forget('sitemap_index_xml');
        Cache::forget('sitemap_pages_xml');
        Cache::forget('sitemap_guides_xml');
        Cache::forget("sitemap_index_xml_{$host}");
        Cache::forget("sitemap_pages_xml_{$host}");
        Cache::forget("sitemap_guides_xml_{$host}");
    }
}
