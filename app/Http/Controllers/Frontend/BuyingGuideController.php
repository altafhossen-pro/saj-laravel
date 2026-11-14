<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\BuyingGuide;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BuyingGuideController extends Controller
{
    /**
     * Display a listing of published buying guides.
     */
    public function index(Request $request): View
    {
        $query = BuyingGuide::published()->with('author');

        if ($search = trim($request->input('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
                    ->orWhere('topic', 'like', "%{$search}%");
            });
        }

        if ($topic = trim($request->input('topic', ''))) {
            $query->where('topic', $topic);
        }

        $guides = $query->latest('published_at')->latest('id')->paginate(12)->withQueryString();

        $topics = BuyingGuide::published()
            ->whereNotNull('topic')
            ->distinct()
            ->pluck('topic')
            ->filter()
            ->values();

        $siteName = Setting::get('site_name', 'SAJ');
        $seo = [
            'title' => "Buying Guides & Shopping Advice | {$siteName}",
            'description' => "Explore authentic buying guides, expert recommendations, and product reviews to choose the best beauty, fashion, and lifestyle essentials at {$siteName}.",
            'keywords' => 'buying guides, product reviews, shopping advice, beauty tips, fashion guides',
            'canonical' => route('guides.index'),
        ];

        return view('frontend.guides.index', compact('guides', 'topics', 'seo'));
    }

    /**
     * Display the specified buying guide.
     */
    public function show(string $slug): View
    {
        $guide = BuyingGuide::published()
            ->where('slug', $slug)
            ->with('author')
            ->firstOrFail();

        // Increment view count on every visit
        $guide->increment('views_count');

        $relatedProducts = $guide->related_products;

        $moreGuides = BuyingGuide::published()
            ->where('id', '!=', $guide->id)
            ->when($guide->topic, fn($q) => $q->orderByRaw("CASE WHEN topic = ? THEN 0 ELSE 1 END", [$guide->topic]))
            ->latest('published_at')
            ->take(3)
            ->get();

        $siteName = Setting::get('site_name', 'SAJ');
        $siteLogo = Setting::get('site_logo');
        $siteLogoUrl = $siteLogo ? asset($siteLogo) : url('/images/logo.png');

        $primaryImageUrl = $guide->og_image_url ?: $guide->featured_image_url;
        $allGuideImages = array_values(array_unique(array_filter([
            $primaryImageUrl,
            $guide->featured_image_url,
        ])));

        $seo = [
            'title' => $guide->meta_title ?: "{$guide->title} | {$siteName}",
            'description' => $guide->meta_description ?: ($guide->excerpt ?: strip_tags(substr($guide->content, 0, 160))),
            'keywords' => $guide->meta_keywords,
            'canonical' => $guide->canonical_url ?: route('guides.show', $guide->slug),
            'og_image' => $primaryImageUrl,
        ];

        // Schema.org Article Structured Data (Strictly Compliant with Google Search Central Documentation)
        $articleSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $guide->title,
            'image' => $allGuideImages,
            'thumbnailUrl' => $primaryImageUrl,
            'datePublished' => ($guide->published_at ?? $guide->created_at)->toIso8601String(),
            'dateModified' => $guide->updated_at->toIso8601String(),
            'author' => [
                '@type' => 'Organization',
                'name' => $siteName,
                'url' => url('/'),
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $siteName,
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $siteLogoUrl,
                ],
            ],
            'description' => $seo['description'],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => route('guides.show', $guide->slug),
                'primaryImageOfPage' => [
                    '@type' => 'ImageObject',
                    'url' => $primaryImageUrl,
                ],
            ],
        ];

        // BreadcrumbList Schema
        $breadcrumbSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Home',
                    'item' => url('/'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'Buying Guides',
                    'item' => route('guides.index'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $guide->title,
                    'item' => route('guides.show', $guide->slug),
                ],
            ],
        ];

        return view('frontend.guides.show', compact(
            'guide',
            'relatedProducts',
            'moreGuides',
            'seo',
            'articleSchema',
            'breadcrumbSchema'
        ));
    }
}
