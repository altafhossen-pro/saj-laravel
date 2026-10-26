{!! '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' !!}
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <sitemap>
        <loc>{{ url('/sitemap-pages.xml') }}</loc>
        <lastmod>{{ now()->toAtomString() }}</lastmod>
    </sitemap>
    <sitemap>
        <loc>{{ url('/sitemap-categories.xml') }}</loc>
        <lastmod>{{ now()->toAtomString() }}</lastmod>
    </sitemap>
    @foreach($productSitemaps as $productSitemap)
    <sitemap>
        <loc>{{ $productSitemap['loc'] }}</loc>
        <lastmod>{{ $productSitemap['lastmod'] }}</lastmod>
    </sitemap>
    @endforeach
</sitemapindex>
