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
    $heading = $customHeading ?? \App\Models\Setting::get('email_tpl_otp_verification_heading', 'Verification Code');
    $messageText = $customMessage ?? \App\Models\Setting::get('email_tpl_otp_verification_message', 'Please use the one-time verification code (OTP) below to verify your email address. This code is confidential and will expire in 5 minutes.');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Verification Code - {{ $siteName }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1f2937; -webkit-font-smoothing: antialiased;">

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout: fixed; background-color: #f3f4f6; padding: 30px 10px;">
        <tr>
            <td align="center">
                <!-- Main Container -->
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); border: 1px solid #e5e7eb;">
                    
                    <!-- Header with Brand -->
                    <tr>
                        <td align="center" style="padding: 32px 30px 24px; border-bottom: 2px solid #111827; background-color: #ffffff;">
                            @if($logoUrl)
                                <img src="{{ $logoUrl }}" alt="{{ $siteName }}" style="max-height: 48px; max-width: 180px; height: auto; object-fit: contain; display: block; margin-bottom: 8px;">
                            @else
                                <h1 style="margin: 0; font-size: 24px; font-weight: 800; color: #111827; letter-spacing: -0.5px;">{{ $siteName }}</h1>
                            @endif
                            <p style="margin: 4px 0 0; font-size: 13px; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Account Security Verification</p>
                        </td>
                    </tr>

                    <!-- Content Body -->
                    <tr>
                        <td style="padding: 36px 36px 28px; text-align: center;">
                            <div style="display: inline-block; width: 56px; height: 56px; line-height: 56px; border-radius: 50%; background-color: #eff6ff; color: #2563eb; font-size: 26px; margin-bottom: 16px;">
                                🛡️
                            </div>
                            <h2 style="margin: 0 0 10px; font-size: 22px; font-weight: 800; color: #111827;">{{ $heading }}</h2>
                            <p style="margin: 0 0 24px; font-size: 14px; color: #4b5563; line-height: 1.6;">{{ $messageText }}</p>

                            <!-- OTP Box -->
                            <div style="background-color: #f9fafb; border: 2px dashed #111827; border-radius: 12px; padding: 20px 30px; margin: 24px 0; display: inline-block;">
                                <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; color: #6b7280; font-weight: 700; margin-bottom: 6px;">Your 6-Digit Code</div>
                                <span style="font-size: 36px; font-weight: 800; letter-spacing: 8px; color: #111827; font-family: 'Courier New', Courier, monospace;">{{ $otp }}</span>
                            </div>

                            <p style="margin: 16px 0 0; font-size: 13px; color: #9ca3af; line-height: 1.5;">
                                ⏱️ This verification code is valid for <strong>5 minutes</strong> only.<br>
                                If you did not initiate this request, please disregard this email safely.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="background-color: #f9fafb; padding: 24px 30px; border-top: 1px solid #e5e7eb; font-size: 12px; color: #6b7280; line-height: 1.5;">
                            <p style="margin: 0 0 6px;">Need assistance? Contact our support team at <strong>{{ $sitePhone ?: $siteEmail }}</strong></p>
                            <p style="margin: 0; color: #9ca3af;">&copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
