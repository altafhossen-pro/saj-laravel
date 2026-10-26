{!! '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
        http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd
        http://www.google.com/schemas/sitemap-image/1.1
        http://www.google.com/schemas/sitemap-image/1.1/sitemap-image.xsd">

    @foreach($urls as $item)
    <url>
        <loc>{{ $item['loc'] }}</loc>
        <lastmod>{{ $item['lastmod'] }}</lastmod>
        <changefreq>{{ $item['changefreq'] ?? 'weekly' }}</changefreq>
        <priority>{{ $item['priority'] ?? '0.8' }}</priority>
        @if(!empty($item['image']))
        <image:image>
            <image:loc>{{ $item['image']['loc'] }}</image:loc>
            @if(!empty($item['image']['title']))
            <image:title>{{ htmlspecialchars($item['image']['title'], ENT_XML1, 'UTF-8') }}</image:title>
            @endif
        </image:image>
        @endif
    </url>
    @endforeach

</urlset>
