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
        $aiPendingCount = BuyingGuide::aiPending()->count();

        return view('admin.guides.index', compact(
            'guides',
            'topics',
            'totalCount',
            'publishedCount',
            'draftCount',
            'aiPendingCount'
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
            'og_image' => 'nullable|image|max:4096',
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

        $ogImagePath = null;
        if ($request->hasFile('og_image')) {
            $ogImagePath = $request->file('og_image')->store('guides/seo', 'public');
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
            'og_image' => $ogImagePath,
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
            'og_image' => 'nullable|image|max:4096',
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

        $ogImagePath = $guide->og_image;
        if ($request->hasFile('og_image')) {
            if ($guide->og_image && !Str::startsWith($guide->og_image, ['http://', 'https://'])) {
                Storage::disk('public')->delete($guide->og_image);
            }
            $ogImagePath = $request->file('og_image')->store('guides/seo', 'public');
        } elseif ($request->boolean('remove_og_image')) {
            if ($guide->og_image && !Str::startsWith($guide->og_image, ['http://', 'https://'])) {
                Storage::disk('public')->delete($guide->og_image);
            }
            $ogImagePath = null;
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
            'og_image' => $ogImagePath,
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
     * View dedicated list of AI Generated Pending Guides
     */
    public function aiPending(Request $request): View
    {
        $query = BuyingGuide::aiPending()->with('author');

        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
                    ->orWhere('topic', 'like', "%{$search}%");
            });
        }

        $guides = $query->latest()->paginate(15)->withQueryString();
        $pendingCount = BuyingGuide::aiPending()->count();

        return view('admin.guides.ai-pending', compact('guides', 'pendingCount'));
    }

    /**
     * Generate a new AI Buying Guide and save in pending/draft state
     */
    public function aiGenerate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mode' => 'required|in:product,custom_topic',
            'product_id' => 'nullable|integer|exists:products,id',
            'topic' => 'nullable|string|max:150',
            'instructions' => 'nullable|string|max:1000',
            'web_research' => 'nullable|boolean',
        ]);

        try {
            $data = \App\Services\AiService::generateBuyingGuide($validated);

            $baseSlug = Str::slug($data['title']);
            $slug = $baseSlug ?: ('guide-' . uniqid());
            $counter = 1;
            while (BuyingGuide::where('slug', $slug)->exists()) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }

            // Assign featured image from source product if available
            $featuredImage = null;
            if (!empty($validated['product_id'])) {
                $product = Product::find($validated['product_id']);
                $featuredImage = $product?->featured_image;
            }

            $guide = BuyingGuide::create([
                'title' => $data['title'],
                'slug' => $slug,
                'topic' => $data['topic'],
                'excerpt' => $data['excerpt'],
                'content' => $data['content'],
                'featured_image' => $featuredImage,
                'related_product_ids' => $data['related_product_ids'] ?? [],
                'reading_time_minutes' => $data['reading_time_minutes'] ?? 4,
                'is_published' => false, // Strictly Pending
                'is_ai_generated' => true,
                'ai_metadata' => $data['ai_metadata'] ?? [],
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'meta_keywords' => $data['meta_keywords'] ?? null,
                'created_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'AI Buying Guide successfully generated and stored in Pending Review!',
                'guide_id' => $guide->id,
                'slug' => $guide->slug,
                'preview_url' => route('admin.guides.preview', $guide->id),
                'edit_url' => route('admin.guides.edit', $guide->id),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Generation failed: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Refine / Edit an existing pending AI Buying Guide with instructions.
     * STRICT SECURITY: Only pending guides can be modified by AI!
     */
    public function aiRefine(Request $request, BuyingGuide $guide): JsonResponse
    {
        // Enforce user rule: Published guides are permanently locked from AI modification
        if (!$guide->canBeModifiedByAi()) {
            return response()->json([
                'success' => false,
                'message' => 'Security Guard: Published guides cannot be modified by AI. Only pending guides are eligible for AI refinement.',
            ], 403);
        }

        $validated = $request->validate([
            'instruction' => 'required|string|min:3|max:1000',
        ]);

        try {
            $result = \App\Services\AiService::refineBuyingGuide($guide, $validated['instruction']);

            return response()->json([
                'success' => true,
                'message' => 'Guide refined successfully by AI!',
                'guide' => [
                    'id' => $guide->id,
                    'title' => $guide->title,
                    'topic' => $guide->topic,
                    'excerpt' => $guide->excerpt,
                    'content' => $guide->content,
                    'reading_time_minutes' => $guide->reading_time_minutes,
                ]
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Refinement failed: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Safe Admin Preview: Preview the guide in full public layout without publishing it!
     */
    public function preview(BuyingGuide $guide): View
    {
        $relatedProducts = $guide->related_products;

        $moreGuides = BuyingGuide::published()
            ->where('id', '!=', $guide->id)
            ->when($guide->topic, fn($q) => $q->orderByRaw("CASE WHEN topic = ? THEN 0 ELSE 1 END", [$guide->topic]))
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('admin.guides.preview', compact('guide', 'relatedProducts', 'moreGuides'));
    }

    /**
     * Confirm / Publish a pending guide
     */
    public function publishGuide(Request $request, BuyingGuide $guide)
    {
        $guide->update([
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->clearSitemapCache();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Guide published successfully and is now live!',
                'public_url' => route('guides.show', $guide->slug),
            ]);
        }

        session()->flash('success', 'Buying Guide has been published successfully and is now live!');
        return redirect()->route('admin.guides.index');
    }

    /**
     * Quick Import / Paste AI-generated guide (from ChatGPT, Claude, Gemini, etc.)
     * Parses raw JSON or Markdown and either returns the parsed structure or saves as an AI Pending guide.
     */
    public function quickImport(Request $request): JsonResponse
    {
        $rawText = trim((string) $request->input('raw_text', ''));
        $action = $request->input('action', 'save'); // 'parse' (for modal preview/autofill) or 'save' (creates pending guide)

        if (empty($rawText) && empty($request->input('title'))) {
            return response()->json([
                'success' => false,
                'message' => 'Please paste your AI generated content or enter guide details.',
            ], 422);
        }

        // If direct fields are supplied
        if ($request->has('title') && $request->has('content') && empty($rawText)) {
            $parsed = [
                'title' => trim($request->input('title')),
                'topic' => trim($request->input('topic', 'Buying Guide')),
                'excerpt' => trim($request->input('excerpt', '')),
                'content' => BuyingGuide::normalizeHeadings($request->input('content')),
                'reading_time_minutes' => (int) $request->input('reading_time_minutes', 4),
                'meta_title' => trim($request->input('meta_title', $request->input('title'))),
                'meta_description' => trim($request->input('meta_description', '')),
                'meta_keywords' => trim($request->input('meta_keywords', '')),
            ];
        } else {
            // Parse from rawText (handles JSON or Markdown)
            $parsed = $this->parseRawAiText($rawText);
        }

        if (empty($parsed['title'])) {
            return response()->json([
                'success' => false,
                'message' => 'Could not detect a title in the pasted text. Please make sure the output contains a title.',
            ], 422);
        }

        if (empty($parsed['content'])) {
            return response()->json([
                'success' => false,
                'message' => 'Could not detect guide content. Please paste the full guide output.',
            ], 422);
        }

        // Action 1: Parse only (returns parsed structure for live preview or manual autofill)
        if ($action === 'parse') {
            return response()->json([
                'success' => true,
                'data' => $parsed,
                'message' => 'Content successfully parsed and validated!',
            ]);
        }

        // Action 2: Save as AI Pending Guide
        $baseSlug = Str::slug($parsed['title']);
        $slug = $baseSlug ?: ('guide-' . uniqid());
        $counter = 1;
        while (BuyingGuide::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $readingTime = !empty($parsed['reading_time_minutes'])
            ? (int) $parsed['reading_time_minutes']
            : BuyingGuide::estimateReadingTime($parsed['content']);

        $guide = BuyingGuide::create([
            'title' => $parsed['title'],
            'slug' => $slug,
            'topic' => !empty($parsed['topic']) ? $parsed['topic'] : 'Buying Guide',
            'excerpt' => $parsed['excerpt'] ?: Str::limit(strip_tags($parsed['content']), 180),
            'content' => $parsed['content'],
            'reading_time_minutes' => $readingTime,
            'is_published' => false, // Strictly Pending
            'is_ai_generated' => true,
            'ai_metadata' => [
                'mode' => 'quick_import',
                'source' => 'external_ai_import',
                'imported_at' => now()->toDateTimeString(),
            ],
            'meta_title' => !empty($parsed['meta_title']) ? $parsed['meta_title'] : $parsed['title'],
            'meta_description' => !empty($parsed['meta_description']) ? $parsed['meta_description'] : Str::limit(strip_tags($parsed['excerpt'] ?: $parsed['content']), 155),
            'meta_keywords' => $parsed['meta_keywords'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Guide imported successfully and saved in AI Pending for review!',
            'guide_id' => $guide->id,
            'slug' => $guide->slug,
            'preview_url' => route('admin.guides.preview', $guide->id),
            'edit_url' => route('admin.guides.edit', $guide->id),
        ]);
    }

    /**
     * Parse raw AI text which may be JSON (pure or in ```json ... ```) or structured Markdown
     */
    private function parseRawAiText(string $raw): array
    {
        $raw = trim($raw);

        // Attempt 1: Check for JSON block
        $jsonCandidate = null;
        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/is', $raw, $m)) {
            $jsonCandidate = trim($m[1]);
        } else {
            $firstBrace = strpos($raw, '{');
            $lastBrace = strrpos($raw, '}');
            if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
                $jsonCandidate = trim(substr($raw, $firstBrace, $lastBrace - $firstBrace + 1));
            }
        }

        if (!empty($jsonCandidate)) {
            $decoded = json_decode($jsonCandidate, true);
            if (is_array($decoded) && (!empty($decoded['title']) || !empty($decoded['content']))) {
                $content = BuyingGuide::normalizeHeadings($decoded['content'] ?? ($decoded['body'] ?? ''));
                $readingTime = !empty($decoded['reading_time_minutes'])
                    ? (int) $decoded['reading_time_minutes']
                    : BuyingGuide::estimateReadingTime($content);

                return [
                    'title' => trim((string) ($decoded['title'] ?? '')),
                    'topic' => trim((string) ($decoded['topic'] ?? ($decoded['category'] ?? 'Buying Guide'))),
                    'excerpt' => trim((string) ($decoded['excerpt'] ?? ($decoded['summary'] ?? ''))),
                    'content' => $content,
                    'reading_time_minutes' => $readingTime,
                    'meta_title' => trim((string) ($decoded['meta_title'] ?? ($decoded['title'] ?? ''))),
                    'meta_description' => trim((string) ($decoded['meta_description'] ?? '')),
                    'meta_keywords' => trim((string) ($decoded['meta_keywords'] ?? '')),
                ];
            }
        }

        // Attempt 2: Markdown or Structured Text Parser
        $lines = preg_split('/\r\n|\r|\n/', $raw);
        $title = '';
        $topic = 'Buying Guide';
        $excerpt = '';
        $metaTitle = '';
        $metaDesc = '';
        $metaKeywords = '';
        $contentLines = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (empty($trimmed)) {
                $contentLines[] = '';
                continue;
            }

            // Extract Topic if labeled: Topic: ... or ক্যাটাগরি: ...
            if (preg_match('/^(?:Topic|Category|টপিক|ক্যাটাগরি)\s*:\s*(.+)$/iu', $trimmed, $m) && empty($topic)) {
                $topic = trim($m[1]);
                continue;
            }

            // Extract Excerpt / Summary if labeled
            if (preg_match('/^(?:Excerpt|Summary|সারসংক্ষেপ|সারাংশ)\s*:\s*(.+)$/iu', $trimmed, $m) && empty($excerpt)) {
                $excerpt = trim($m[1]);
                continue;
            }

            // Extract Meta keywords if labeled
            if (preg_match('/^(?:Keywords|Meta Keywords|কি-ওয়ার্ড)\s*:\s*(.+)$/iu', $trimmed, $m)) {
                $metaKeywords = trim($m[1]);
                continue;
            }

            // Extract Title from markdown heading # Title or Title: ...
            if (empty($title)) {
                if (preg_match('/^#\s+(.+)$/u', $trimmed, $m)) {
                    $title = trim($m[1]);
                    continue;
                }
                if (preg_match('/^(?:Title|শিরোনাম)\s*:\s*(.+)$/iu', $trimmed, $m)) {
                    $title = trim($m[1]);
                    continue;
                }
                if (!preg_match('/^#{2,6}\s+/u', $trimmed)) {
                    // First substantial non-heading line could be title if short
                    if (mb_strlen($trimmed) < 120 && count($contentLines) < 2) {
                        $title = $trimmed;
                        continue;
                    }
                }
            }

            $contentLines[] = $line;
        }

        $rawBody = implode("\n", $contentLines);
        // Normalize headings in markdown body
        $normalizedBody = BuyingGuide::normalizeHeadings($rawBody);

        // If the body doesn't have HTML tags, convert double linebreaks into <p> tags
        if (!str_contains($normalizedBody, '<p>') && !str_contains($normalizedBody, '<h')) {
            $paragraphs = preg_split('/\n\s*\n/', $normalizedBody);
            $htmlParts = [];
            foreach ($paragraphs as $p) {
                $p = trim($p);
                if (empty($p)) continue;
                if (preg_match('/^<h[1-6]/i', $p)) {
                    $htmlParts[] = $p;
                } else {
                    $htmlParts[] = "<p>{$p}</p>";
                }
            }
            $normalizedBody = implode("\n", $htmlParts);
        }

        // Fallback excerpt from content if missing
        if (empty($excerpt)) {
            $excerpt = Str::limit(strip_tags($normalizedBody), 180);
        }

        $readingTime = BuyingGuide::estimateReadingTime($normalizedBody);

        return [
            'title' => $title ?: 'Untitled Buying Guide',
            'topic' => $topic ?: 'Buying Guide',
            'excerpt' => $excerpt,
            'content' => $normalizedBody,
            'reading_time_minutes' => $readingTime,
            'meta_title' => $metaTitle ?: $title,
            'meta_description' => $metaDesc ?: Str::limit($excerpt, 155),
            'meta_keywords' => $metaKeywords,
        ];
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
