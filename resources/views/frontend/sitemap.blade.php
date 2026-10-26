{!! '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
        http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd
        http://www.google.com/schemas/sitemap-image/1.1
        http://www.google.com/schemas/sitemap-image/1.1/sitemap-image.xsd">

    {{-- 1. Static Pages --}}
    @foreach($staticPages as $page)
    <url>
        <loc>{{ $page['loc'] }}</loc>
        <lastmod>{{ $page['lastmod'] }}</lastmod>
        <changefreq>{{ $page['changefreq'] }}</changefreq>
        <priority>{{ $page['priority'] }}</priority>
    </url>
    @endforeach

    {{-- 2. Category Pages --}}
    @foreach($categories as $category)
    <url>
        <loc>{{ $category['loc'] }}</loc>
        <lastmod>{{ $category['lastmod'] }}</lastmod>
        <changefreq>{{ $category['changefreq'] }}</changefreq>
        <priority>{{ $category['priority'] }}</priority>
    </url>
    @endforeach

    {{-- 3. Product Pages with Google Images --}}
    @foreach($products as $product)
    <url>
        <loc>{{ $product['loc'] }}</loc>
        <lastmod>{{ $product['lastmod'] }}</lastmod>
        <changefreq>{{ $product['changefreq'] }}</changefreq>
        <priority>{{ $product['priority'] }}</priority>
        @if(!empty($product['image']['loc']))
        <image:image>
            <image:loc>{{ $product['image']['loc'] }}</image:loc>
            <image:title>{{ htmlspecialchars($product['image']['title'] ?? '', ENT_XML1, 'UTF-8') }}</image:title>
        </image:image>
        @endif
    </url>
    @endforeach

</urlset>
