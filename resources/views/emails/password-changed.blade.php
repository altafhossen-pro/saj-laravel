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
    $changedAt = $meta['changed_at'] ?? date('d M Y, h:i:s A');
    $ip = $meta['ip'] ?? request()->ip();
    $device = $meta['device'] ?? 'Web Browser';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Alert: Password Changed - {{ $siteName }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1f2937; -webkit-font-smoothing: antialiased;">

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout: fixed; background-color: #f3f4f6; padding: 30px 10px;">
        <tr>
            <td align="center">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); border: 1px solid #e5e7eb;">
                    
                    <!-- Header with Brand -->
                    <tr>
                        <td align="center" style="padding: 32px 30px 24px; border-bottom: 2px solid #ef4444; background-color: #ffffff;">
                            @if($logoUrl)
                                <img src="{{ $logoUrl }}" alt="{{ $siteName }}" style="max-height: 48px; max-width: 180px; height: auto; object-fit: contain; display: block; margin-bottom: 8px;">
                            @else
                                <h1 style="margin: 0; font-size: 24px; font-weight: 800; color: #111827; letter-spacing: -0.5px;">{{ $siteName }}</h1>
                            @endif
                            <p style="margin: 4px 0 0; font-size: 13px; color: #ef4444; text-transform: uppercase; letter-spacing: 1px; font-weight: 700;">Security Notification</p>
                        </td>
                    </tr>

                    <!-- Intro Box -->
                    <tr>
                        <td style="padding: 36px 36px 24px; text-align: center;">
                            <div style="display: inline-block; width: 60px; height: 60px; line-height: 60px; border-radius: 50%; background-color: #fee2e2; color: #ef4444; font-size: 30px; margin-bottom: 16px;">
                                🔒
                            </div>
                            <h2 style="margin: 0 0 10px; font-size: 22px; font-weight: 800; color: #111827;">Hello, {{ $user->name }}</h2>
                            <p style="margin: 0 0 20px; font-size: 15px; color: #4b5563; line-height: 1.6;">
                                This is a confirmation that the password for your <strong>{{ $siteName }}</strong> account ({{ $user->email }}) was recently updated.
                            </p>

                            <!-- Event Details Card -->
                            <div style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 20px; margin: 24px 0; text-align: left;">
                                <h3 style="margin: 0 0 14px; font-size: 13px; font-weight: 700; color: #991b1b; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #fecaca; padding-bottom: 8px;">
                                    Activity Details
                                </h3>
                                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="font-size: 13px; color: #7f1d1d;">
                                    <tr>
                                        <td style="padding: 5px 0; font-weight: 600; width: 130px;">Time:</td>
                                        <td style="padding: 5px 0;">{{ $changedAt }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 5px 0; font-weight: 600;">IP Address:</td>
                                        <td style="padding: 5px 0; font-family: monospace;">{{ $ip }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 5px 0; font-weight: 600;">Device/Platform:</td>
                                        <td style="padding: 5px 0;">{{ $device }}</td>
                                    </tr>
                                </table>
                            </div>

                            <div style="background-color: #fffbeb; border: 1px solid #fef3c7; border-radius: 8px; padding: 14px 18px; text-align: left; margin-bottom: 24px;">
                                <p style="margin: 0; font-size: 13px; color: #92400e; line-height: 1.5;">
                                    <strong>Didn't make this change?</strong> If you did not update your password, your account may have been compromised. Please reset your password immediately or contact our support team.
                                </p>
                            </div>

                            <a href="{{ url('/login') }}" style="display: inline-block; background-color: #111827; color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 600; padding: 12px 28px; border-radius: 8px;">
                                Manage Account
                            </a>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding: 24px; background-color: #f8fafc; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8;">
                            <p style="margin: 0 0 4px;">Sent automatically by {{ $siteName }} Security System.</p>
                            <p style="margin: 0;">&copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
