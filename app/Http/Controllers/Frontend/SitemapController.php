<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\BuyingGuide;
use App\Models\Category;
use App\Models\Product\Product;
use App\Models\Setting;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SitemapController extends Controller
{
    /**
     * Maximum URLs per sub-sitemap (Google's official limit is 50,000;
     * 10,000 gives blazing-fast responses and minimal memory footprint).
     */
    const CHUNK_SIZE = 10000;

    /**
     * Root Sitemap Index (/sitemap.xml)
     * Lists sub-sitemaps for Pages, Categories, and chunked Products.
     */
    public function index(): Response
    {
        $baseUrl = $this->getBaseUrl();
        $cacheKey = $this->getCacheKey('sitemap_index_xml');

        $xml = Cache::remember($cacheKey, 3600, function () use ($baseUrl) {
            $totalProducts = Product::where('is_active', true)->count();
            $totalProductChunks = max(1, (int) ceil($totalProducts / self::CHUNK_SIZE));

            $productSitemaps = [];
            for ($page = 1; $page <= $totalProductChunks; $page++) {
                $productSitemaps[] = [
                    'loc' => "{$baseUrl}/sitemap-products-{$page}.xml",
                    'lastmod' => now()->toAtomString(),
                ];
            }

            try {
                Setting::set('last_sitemap_cached_at', now()->toDateTimeString(), 'datetime', 'system');
                Setting::set('last_sitemap_cached_host', $baseUrl, 'string', 'system');
            } catch (\Throwable $e) {
                // Ignore DB write errors in read-only setups
            }

            return view('frontend.sitemap-index', compact('productSitemaps', 'baseUrl'))->render();
        });

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'X-Robots-Tag' => 'noindex, follow',
        ]);
    }

    /**
     * Static Pages Sitemap (/sitemap-pages.xml)
     */
    public function pages(): Response
    {
        $baseUrl = $this->getBaseUrl();
        $cacheKey = $this->getCacheKey('sitemap_pages_xml');

        $xml = Cache::remember($cacheKey, 7200, function () use ($baseUrl) {
            $urls = [
                [
                    'loc' => $baseUrl,
                    'lastmod' => now()->toAtomString(),
                    'changefreq' => 'daily',
                    'priority' => '1.0',
                ],
                [
                    'loc' => "{$baseUrl}/shop",
                    'lastmod' => now()->toAtomString(),
                    'changefreq' => 'daily',
                    'priority' => '0.9',
                ],
                [
                    'loc' => "{$baseUrl}/guides",
                    'lastmod' => now()->toAtomString(),
                    'changefreq' => 'daily',
                    'priority' => '0.8',
                ],
                [
                    'loc' => "{$baseUrl}/about-us",
                    'lastmod' => now()->subDays(3)->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority' => '0.7',
                ],
                [
                    'loc' => "{$baseUrl}/contact-us",
                    'lastmod' => now()->subDays(3)->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority' => '0.7',
                ],
                [
                    'loc' => "{$baseUrl}/terms-conditions",
                    'lastmod' => now()->subDays(7)->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority' => '0.5',
                ],
                [
                    'loc' => "{$baseUrl}/privacy-policy",
                    'lastmod' => now()->subDays(7)->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority' => '0.5',
                ],
                [
                    'loc' => "{$baseUrl}/return-refund-policy",
                    'lastmod' => now()->subDays(7)->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority' => '0.6',
                ],
                [
                    'loc' => "{$baseUrl}/track-order",
                    'lastmod' => now()->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.6',
                ],
            ];

            return view('frontend.sitemap-section', compact('urls'))->render();
        });

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'X-Robots-Tag' => 'noindex, follow',
        ]);
    }

    /**
     * Categories Sitemap (/sitemap-categories.xml)
     */
    public function categories(): Response
    {
        $baseUrl = $this->getBaseUrl();
        $cacheKey = $this->getCacheKey('sitemap_categories_xml');

        $xml = Cache::remember($cacheKey, 3600, function () use ($baseUrl) {
            $urls = [];
            Category::where('is_active', true)
                ->select(['id', 'slug', 'image', 'name', 'updated_at'])
                ->chunk(1000, function ($categories) use (&$urls, $baseUrl) {
                    foreach ($categories as $cat) {
                        $item = [
                            'loc' => "{$baseUrl}/category/{$cat->slug}",
                            'lastmod' => ($cat->updated_at ?? now())->toAtomString(),
                            'changefreq' => 'weekly',
                            'priority' => '0.8',
                        ];

                        if (!empty($cat->image)) {
                            $item['image'] = [
                                'loc' => $this->resolveImageUrl($cat->image, $baseUrl),
                                'title' => $cat->name,
                            ];
                        }
                        $urls[] = $item;
                    }
                });

            return view('frontend.sitemap-section', compact('urls'))->render();
        });

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'X-Robots-Tag' => 'noindex, follow',
        ]);
    }

    /**
     * Chunked Products Sitemap (/sitemap-products-{page}.xml)
     * e.g., /sitemap-products-1.xml, /sitemap-products-2.xml
     */
    public function products(int $page = 1): Response
    {
        $page = max(1, $page);
        $baseUrl = $this->getBaseUrl();
        $cacheKey = $this->getCacheKey("sitemap_products_{$page}_xml");

        $xml = Cache::remember($cacheKey, 1800, function () use ($page, $baseUrl) {
            $offset = ($page - 1) * self::CHUNK_SIZE;

            $urls = [];
            Product::where('is_active', true)
                ->select(['id', 'slug', 'featured_image', 'title', 'updated_at'])
                ->orderBy('id', 'asc')
                ->skip($offset)
                ->take(self::CHUNK_SIZE)
                ->chunk(1000, function ($products) use (&$urls, $baseUrl) {
                    foreach ($products as $product) {
                        $item = [
                            'loc' => "{$baseUrl}/product/{$product->slug}",
                            'lastmod' => ($product->updated_at ?? now())->toAtomString(),
                            'changefreq' => 'daily',
                            'priority' => '0.9',
                        ];

                        if (!empty($product->featured_image)) {
                            $item['image'] = [
                                'loc' => $this->resolveImageUrl($product->featured_image, $baseUrl),
                                'title' => $product->title,
                            ];
                        }
                        $urls[] = $item;
                    }
                });

            return view('frontend.sitemap-section', compact('urls'))->render();
        });

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'X-Robots-Tag' => 'noindex, follow',
        ]);
    }

    /**
     * Buying Guides Sitemap (/sitemap-guides.xml)
     */
    public function guides(): Response
    {
        $baseUrl = $this->getBaseUrl();
        $cacheKey = $this->getCacheKey('sitemap_guides_xml');

        $xml = Cache::remember($cacheKey, 3600, function () use ($baseUrl) {
            $urls = [
                [
                    'loc' => "{$baseUrl}/guides",
                    'lastmod' => now()->toAtomString(),
                    'changefreq' => 'daily',
                    'priority' => '0.8',
                ],
            ];

            BuyingGuide::published()
                ->select(['id', 'slug', 'title', 'featured_image', 'updated_at', 'published_at'])
                ->chunk(1000, function ($guides) use (&$urls, $baseUrl) {
                    foreach ($guides as $guide) {
                        $item = [
                            'loc' => "{$baseUrl}/guides/{$guide->slug}",
                            'lastmod' => ($guide->updated_at ?? $guide->published_at ?? now())->toAtomString(),
                            'changefreq' => 'weekly',
                            'priority' => '0.8',
                        ];

                        if (!empty($guide->featured_image)) {
                            $item['image'] = [
                                'loc' => $this->resolveImageUrl($guide->featured_image, $baseUrl),
                                'title' => $guide->title,
                            ];
                        }

                        $urls[] = $item;
                    }
                });

            return view('frontend.sitemap-section', compact('urls'))->render();
        });

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'X-Robots-Tag' => 'noindex, follow',
        ]);
    }

    /**
     * Resolve full absolute public URL for an image (handling external URLs, local storage, etc.)
     */
    private function resolveImageUrl(?string $image, string $baseUrl): ?string
    {
        if (empty($image)) {
            return null;
        }

        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            return $image;
        }

        $trimmed = ltrim($image, '/');

        if (str_starts_with($trimmed, 'storage/')) {
            return "{$baseUrl}/{$trimmed}";
        }

        $storageUrl = Storage::url($image);
        return $baseUrl . '/' . ltrim($storageUrl, '/');
    }

    /**
     * Determine current active public base URL with priority on incoming request host
     */
    private function getBaseUrl(): string
    {
        // 1. If running under a real web request (via browser/curl/http), ALWAYS use current request host and scheme
        if (!app()->runningInConsole() || request()->hasHeader('Host')) {
            $scheme = request()->header('X-Forwarded-Proto') ?? (request()->isSecure() ? 'https' : 'http');
            $host = request()->header('X-Forwarded-Host') ?? request()->header('Host') ?? request()->getHttpHost();
            if (!empty($host)) {
                return "{$scheme}://{$host}";
            }
        }

        // 2. In CLI console, check config('app.url')
        $appUrl = rtrim(config('app.url', ''), '/');
        if (!empty($appUrl)) {
            return $appUrl;
        }

        return 'http://localhost';
    }

    /**
     * Scoped cache key by host to prevent cross-environment cache poisoning
     */
    private function getCacheKey(string $prefix): string
    {
        $host = request()->header('X-Forwarded-Host') ?? request()->header('Host') ?? request()->getHttpHost();
        if (empty($host)) {
            $host = parse_url(config('app.url', ''), PHP_URL_HOST) ?: 'cli';
        }
        $sanitized = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $host);
        return "{$prefix}_{$sanitized}";
    }
}
