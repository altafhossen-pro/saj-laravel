<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CacheManagerController extends Controller
{
    /**
     * Display the Purge Cache management dashboard.
     */
    public function index(Request $request): View
    {
        $lastSitemapCachedAt = Setting::get('last_sitemap_cached_at');
        $lastSitemapCachedHost = Setting::get('last_sitemap_cached_host');
        $lastSitemapPurgedAt = Setting::get('last_sitemap_purged_at');
        $lastSitemapPurgedBy = Setting::get('last_sitemap_purged_by');

        $currentHost = $request->getHttpHost();
        $sanitizedHost = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $currentHost);
        $sitemapIndexKey = "sitemap_index_xml_{$sanitizedHost}";

        // Check if currently cached
        $isSitemapCached = Cache::has($sitemapIndexKey) || Cache::has('sitemap_index_xml');

        $sitemapLinks = [
            ['name' => 'Root Sitemap Index', 'path' => '/sitemap.xml'],
            ['name' => 'Buying Guides Sitemap', 'path' => '/sitemap-guides.xml'],
            ['name' => 'Categories Sitemap', 'path' => '/sitemap-categories.xml'],
            ['name' => 'Static Pages Sitemap', 'path' => '/sitemap-pages.xml'],
            ['name' => 'Products Sitemap (Page 1)', 'path' => '/sitemap-products-1.xml'],
        ];

        return view('admin.settings.cache.index', compact(
            'lastSitemapCachedAt',
            'lastSitemapCachedHost',
            'lastSitemapPurgedAt',
            'lastSitemapPurgedBy',
            'isSitemapCached',
            'currentHost',
            'sitemapLinks'
        ));
    }

    /**
     * Purge all XML sitemap caches across all hosts and sub-sitemaps.
     */
    public function purgeSitemap(Request $request): RedirectResponse
    {
        $currentHost = $request->getHttpHost();
        $sanitizedHost = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $currentHost);

        // Standard keys
        $keys = [
            'sitemap_index_xml',
            'sitemap_pages_xml',
            'sitemap_categories_xml',
            'sitemap_guides_xml',
            "sitemap_index_xml_{$sanitizedHost}",
            "sitemap_pages_xml_{$sanitizedHost}",
            "sitemap_categories_xml_{$sanitizedHost}",
            "sitemap_guides_xml_{$sanitizedHost}",
        ];

        // Also clean up to 20 chunked product sitemap keys
        for ($i = 1; $i <= 20; $i++) {
            $keys[] = "sitemap_products_{$i}_xml";
            $keys[] = "sitemap_products_{$i}_xml_{$sanitizedHost}";
        }

        foreach ($keys as $k) {
            Cache::forget($k);
        }

        // If using database cache driver, purge any orphaned keys starting with sitemap_
        if (config('cache.default') === 'database') {
            try {
                $cacheTable = config('cache.stores.database.table', 'cache');
                DB::table($cacheTable)->where('key', 'like', '%sitemap_%')->delete();
            } catch (\Throwable $e) {
                // Ignore if custom table
            }
        }

        $userName = auth()->check() ? auth()->user()->name : 'Admin';
        Setting::set('last_sitemap_purged_at', now()->toDateTimeString(), 'datetime', 'system');
        Setting::set('last_sitemap_purged_by', $userName, 'string', 'system');
        Setting::set('last_sitemap_cached_at', null, 'datetime', 'system');

        return redirect()->route('admin.settings.cache')
            ->with('success', 'Sitemap cache was purged successfully! Fresh sitemaps will be generated automatically on next request.');
    }

    /**
     * Purge compiled Blade views.
     */
    public function purgeViews(): RedirectResponse
    {
        Artisan::call('view:clear');

        return redirect()->route('admin.settings.cache')
            ->with('success', 'Compiled Blade view cache cleared successfully.');
    }

    /**
     * Purge complete application cache.
     */
    public function purgeAll(): RedirectResponse
    {
        Artisan::call('cache:clear');

        $userName = auth()->check() ? auth()->user()->name : 'Admin';
        Setting::set('last_sitemap_purged_at', now()->toDateTimeString(), 'datetime', 'system');
        Setting::set('last_sitemap_purged_by', $userName, 'string', 'system');
        Setting::set('last_sitemap_cached_at', null, 'datetime', 'system');

        return redirect()->route('admin.settings.cache')
            ->with('success', 'Entire application cache was purged successfully.');
    }
}
