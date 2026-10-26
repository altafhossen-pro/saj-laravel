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
    $mailer = $details['mailer'] ?? 'smtp';
    $host = $details['host'] ?? 'N/A';
    $port = $details['port'] ?? 'N/A';
    $fromEmail = $details['from_email'] ?? 'N/A';
    $fromName = $details['from_name'] ?? $siteName;
    $sentAt = $details['sent_at'] ?? date('d M Y, h:i:s A');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMTP Configuration Verified - {{ $siteName }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1f2937; -webkit-font-smoothing: antialiased;">

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout: fixed; background-color: #f3f4f6; padding: 30px 10px;">
        <tr>
            <td align="center">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); border: 1px solid #e5e7eb;">
                    
                    <!-- Header with Brand -->
                    <tr>
                        <td align="center" style="padding: 32px 30px 24px; border-bottom: 2px solid #16a34a; background-color: #ffffff;">
                            @if($logoUrl)
                                <img src="{{ $logoUrl }}" alt="{{ $siteName }}" style="max-height: 48px; max-width: 180px; height: auto; object-fit: contain; display: block; margin-bottom: 8px;">
                            @else
                                <h1 style="margin: 0; font-size: 24px; font-weight: 800; color: #111827; letter-spacing: -0.5px;">{{ $siteName }}</h1>
                            @endif
                            <p style="margin: 4px 0 0; font-size: 13px; color: #16a34a; text-transform: uppercase; letter-spacing: 1px; font-weight: 700;">System Diagnostic Test</p>
                        </td>
                    </tr>

                    <!-- Intro Box -->
                    <tr>
                        <td style="padding: 36px 36px 24px; text-align: center;">
                            <div style="display: inline-block; width: 60px; height: 60px; line-height: 60px; border-radius: 50%; background-color: #dcfce7; color: #16a34a; font-size: 30px; margin-bottom: 16px;">
                                🚀
                            </div>
                            <h2 style="margin: 0 0 10px; font-size: 22px; font-weight: 800; color: #111827;">Mail Server Connected!</h2>
                            <p style="margin: 0 0 20px; font-size: 15px; color: #4b5563; line-height: 1.6;">
                                If you are reading this email, your email server settings on <strong>{{ $siteName }}</strong> are working properly and successfully reaching external inboxes.
                            </p>

                            <!-- Configuration Details Card -->
                            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px; margin: 24px 0; text-align: left;">
                                <h3 style="margin: 0 0 14px; font-size: 14px; font-weight: 700; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">
                                    Diagnostic Summary
                                </h3>
                                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="font-size: 13px; color: #334155;">
                                    <tr>
                                        <td style="padding: 5px 0; font-weight: 600; width: 140px;">Active Transport:</td>
                                        <td style="padding: 5px 0; font-family: monospace; color: #0284c7; font-weight: 700;">{{ strtoupper($mailer) }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 5px 0; font-weight: 600;">Server Host:</td>
                                        <td style="padding: 5px 0; font-family: monospace;">{{ $host }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 5px 0; font-weight: 600;">Port:</td>
                                        <td style="padding: 5px 0; font-family: monospace;">{{ $port }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 5px 0; font-weight: 600;">Sender Address:</td>
                                        <td style="padding: 5px 0;">{{ $fromEmail }} ({{ $fromName }})</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 5px 0; font-weight: 600;">Dispatched At:</td>
                                        <td style="padding: 5px 0;">{{ $sentAt }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 5px 0; font-weight: 600;">Status:</td>
                                        <td style="padding: 5px 0;"><span style="display: inline-block; background-color: #dcfce7; color: #15803d; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 700;">DELIVERED (200 OK)</span></td>
                                    </tr>
                                </table>
                            </div>

                            <p style="margin: 16px 0 0; font-size: 13px; color: #64748b; line-height: 1.5;">
                                Transactional customer notifications (Order Confirmations, Welcome Emails, Security Alerts) are ready to be dispatched with this configuration.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding: 24px; background-color: #f8fafc; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8;">
                            <p style="margin: 0 0 4px;">Sent automatically by {{ $siteName }} System Administrator Console.</p>
                            <p style="margin: 0;">&copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
