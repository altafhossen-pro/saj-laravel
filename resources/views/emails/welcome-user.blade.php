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
    $heading = $customHeading ?? \App\Models\Setting::get('email_tpl_welcome_user_heading', 'Welcome to ' . $siteName . '!');
    $messageText = $customMessage ?? \App\Models\Setting::get('email_tpl_welcome_user_message', 'We are thrilled to welcome you to our shopping community. Discover thousands of genuine products at unbeatable prices.');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $heading }}</title>
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
                            <p style="margin: 4px 0 0; font-size: 13px; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Welcome Email</p>
                        </td>
                    </tr>

                    <!-- Intro Box -->
                    <tr>
                        <td style="padding: 36px 36px 24px; text-align: center;">
                            <div style="display: inline-block; width: 56px; height: 56px; line-height: 56px; border-radius: 50%; background-color: #eff6ff; color: #2563eb; font-size: 28px; margin-bottom: 16px;">
                                🎉
                            </div>
                            <h2 style="margin: 0 0 10px; font-size: 22px; font-weight: 800; color: #111827;">Hello, {{ $user->name }}!</h2>
                            <p style="margin: 0 0 20px; font-size: 15px; color: #4b5563; line-height: 1.6;">{{ $messageText }}</p>

                            <div style="background-color: #f9fafb; border: 1px dashed #d1d5db; border-radius: 10px; padding: 20px; margin: 24px 0; text-align: left;">
                                <h4 style="margin: 0 0 8px; font-size: 14px; font-weight: 700; color: #111827;">Here is what you can do with your account:</h4>
                                <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #4b5563; line-height: 1.8;">
                                    <li>Track live orders and view digital PDF invoices anytime</li>
                                    <li>Save delivery addresses for blazing fast checkout</li>
                                    <li>Leave verified reviews & earn loyalty rewards</li>
                                    <li>Enjoy exclusive subscriber sales and flash deal discounts</li>
                                </ul>
                            </div>

                            <a href="{{ url('/') }}" style="display: inline-block; background-color: #111827; color: #ffffff; text-decoration: none; padding: 13px 32px; border-radius: 8px; font-size: 14px; font-weight: 700; letter-spacing: 0.3px; margin-top: 10px;">
                                Start Shopping Now &rarr;
                            </a>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="background-color: #f9fafb; padding: 24px 30px; border-top: 1px solid #e5e7eb; font-size: 12px; color: #6b7280; line-height: 1.5;">
                            <p style="margin: 0 0 6px;">Questions or support? We are always happy to help at <strong>{{ $sitePhone ?: $siteEmail }}</strong></p>
                            <p style="margin: 0; color: #9ca3af;">&copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
