<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product\Product;
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
        $xml = Cache::remember('sitemap_index_xml', 3600, function () {
            $totalProducts = Product::where('is_active', true)->count();
            $totalProductChunks = max(1, (int) ceil($totalProducts / self::CHUNK_SIZE));

            $productSitemaps = [];
            for ($page = 1; $page <= $totalProductChunks; $page++) {
                $productSitemaps[] = [
                    'loc' => url("/sitemap-products-{$page}.xml"),
                    'lastmod' => now()->toAtomString(),
                ];
            }

            return view('frontend.sitemap-index', compact('productSitemaps'))->render();
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
        $xml = Cache::remember('sitemap_pages_xml', 7200, function () {
            $urls = [
                [
                    'loc' => url('/'),
                    'lastmod' => now()->toAtomString(),
                    'changefreq' => 'daily',
                    'priority' => '1.0',
                ],
                [
                    'loc' => route('shop.search'),
                    'lastmod' => now()->toAtomString(),
                    'changefreq' => 'daily',
                    'priority' => '0.9',
                ],
                [
                    'loc' => route('page.about'),
                    'lastmod' => now()->subDays(3)->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority' => '0.7',
                ],
                [
                    'loc' => route('page.contact'),
                    'lastmod' => now()->subDays(3)->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority' => '0.7',
                ],
                [
                    'loc' => route('page.terms'),
                    'lastmod' => now()->subDays(7)->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority' => '0.5',
                ],
                [
                    'loc' => route('page.privacy'),
                    'lastmod' => now()->subDays(7)->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority' => '0.5',
                ],
                [
                    'loc' => route('page.return-refund'),
                    'lastmod' => now()->subDays(7)->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority' => '0.6',
                ],
                [
                    'loc' => route('page.tracking'),
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
        $xml = Cache::remember('sitemap_categories_xml', 3600, function () {
            $urls = [];
            Category::where('is_active', true)
                ->select(['id', 'slug', 'image', 'name', 'updated_at'])
                ->chunk(1000, function ($categories) use (&$urls) {
                    foreach ($categories as $cat) {
                        $item = [
                            'loc' => route('shop.category', $cat->slug),
                            'lastmod' => ($cat->updated_at ?? now())->toAtomString(),
                            'changefreq' => 'weekly',
                            'priority' => '0.8',
                        ];

                        if (!empty($cat->image)) {
                            $item['image'] = [
                                'loc' => $this->resolveImageUrl($cat->image),
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
        $cacheKey = "sitemap_products_{$page}_xml";

        $xml = Cache::remember($cacheKey, 1800, function () use ($page) {
            $offset = ($page - 1) * self::CHUNK_SIZE;

            $urls = [];
            Product::where('is_active', true)
                ->select(['id', 'slug', 'featured_image', 'title', 'updated_at'])
                ->orderBy('id', 'asc')
                ->skip($offset)
                ->take(self::CHUNK_SIZE)
                ->chunk(1000, function ($products) use (&$urls) {
                    foreach ($products as $product) {
                        $item = [
                            'loc' => route('product.show', $product->slug),
                            'lastmod' => ($product->updated_at ?? now())->toAtomString(),
                            'changefreq' => 'daily',
                            'priority' => '0.9',
                        ];

                        if (!empty($product->featured_image)) {
                            $item['image'] = [
                                'loc' => $this->resolveImageUrl($product->featured_image),
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
     * Resolve full absolute public URL for an image (handling external URLs, local storage, etc.)
     */
    private function resolveImageUrl(?string $image): ?string
    {
        if (empty($image)) {
            return null;
        }

        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            return $image;
        }

        $trimmed = ltrim($image, '/');

        if (str_starts_with($trimmed, 'storage/')) {
            return url($trimmed);
        }

        return url(Storage::url($image));
    }
}
