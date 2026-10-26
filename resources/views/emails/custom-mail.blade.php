@php
    $siteName = \App\Models\Setting::get('site_name', 'SAJ');
    $rawLogo  = \App\Models\Setting::get('site_logo');
    $logoUrl = null;
    if ($rawLogo) {
        if (\Illuminate\Support\Str::startsWith($rawLogo, ['http://', 'https://'])) {
            $logoUrl = $rawLogo;
        } else {
            $appUrl = config('app.url', 'https://www.sajbd.com');
            if (str_contains($appUrl, 'localhost') || str_contains($appUrl, '127.0.0.1')) {
                $appUrl = 'https://www.sajbd.com';
            }
            $cleanPath = ltrim(str_replace('/storage/', '', $rawLogo), '/');
            $cleanPath = ltrim(str_replace('storage/', '', $cleanPath), '/');
            $logoUrl = rtrim($appUrl, '/') . '/storage/' . $cleanPath;
        }
    }
    $sitePhone   = \App\Models\Setting::get('site_phone', '');
    $siteEmail   = \App\Models\Setting::get('site_email', '');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $mailSubject }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1f2937; -webkit-font-smoothing: antialiased;">

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout: fixed; background-color: #f3f4f6; padding: 30px 10px;">
        <tr>
            <td align="center">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); border: 1px solid #e5e7eb;">
                    
                    <!-- Header with Brand -->
                    <tr>
                        <td align="center" style="padding: 32px 30px 24px; border-bottom: 2px solid #111827; background-color: #ffffff;">
                            @if($logoUrl)
                                <img src="{{ $logoUrl }}" alt="{{ $siteName }}" style="max-height: 48px; max-width: 180px; height: auto; object-fit: contain; display: block; margin-bottom: 8px;">
                            @else
                                <h1 style="margin: 0; font-size: 24px; font-weight: 800; color: #111827; letter-spacing: -0.5px;">{{ $siteName }}</h1>
                            @endif
                            <p style="margin: 4px 0 0; font-size: 13px; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Official Communication</p>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 36px 36px 30px;">
                            @if(!empty($mailHeading))
                                <h2 style="margin: 0 0 16px; font-size: 20px; font-weight: 800; color: #111827;">{{ $mailHeading }}</h2>
                            @endif

                            <div style="font-size: 15px; color: #374151; line-height: 1.7; white-space: pre-line;">
                                {!! nl2br(e($mailBody)) !!}
                            </div>

                            @if(!empty($actionText) && !empty($actionUrl))
                                <div style="margin-top: 28px; text-align: center;">
                                    <a href="{{ $actionUrl }}" style="display: inline-block; background-color: #111827; color: #ffffff; text-decoration: none; padding: 12px 30px; border-radius: 8px; font-size: 14px; font-weight: 700; letter-spacing: 0.3px;">
                                        {{ $actionText }}
                                    </a>
                                </div>
                            @endif
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="background-color: #f9fafb; padding: 24px 30px; border-top: 1px solid #e5e7eb; font-size: 12px; color: #6b7280; line-height: 1.5;">
                            <p style="margin: 0 0 6px;">Questions or assistance? Reach out to us at <strong>{{ $sitePhone ?: $siteEmail }}</strong></p>
                            <p style="margin: 0; color: #9ca3af;">&copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
