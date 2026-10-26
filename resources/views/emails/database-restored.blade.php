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
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Critical Security Alert: Database Restored</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1f2937;">

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout: fixed; background-color: #f3f4f6; padding: 30px 10px;">
        <tr>
            <td align="center">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 620px; background-color: #ffffff; border-radius: 14px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); border: 1px solid #fee2e2;">
                    
                    <!-- Alert Top Ribbon -->
                    <tr>
                        <td style="background-color: #dc2626; padding: 12px 24px; text-align: center; color: #ffffff; font-size: 13px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase;">
                            ⚠️ CRITICAL ADMINISTRATIVE SECURITY NOTIFICATION
                        </td>
                    </tr>

                    <!-- Header -->
                    <tr>
                        <td align="center" style="padding: 30px 30px 20px; border-bottom: 1px solid #f3f4f6;">
                            @if($logoUrl)
                                <img src="{{ $logoUrl }}" alt="{{ $siteName }}" style="max-height: 44px; max-width: 170px; height: auto; object-fit: contain; display: block; margin-bottom: 8px;">
                            @else
                                <h1 style="margin: 0; font-size: 22px; font-weight: 800; color: #111827;">{{ $siteName }}</h1>
                            @endif
                            <p style="margin: 4px 0 0; font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; font-weight: 700;">System Integrity & Audit Log</p>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 30px 34px;">
                            
                            <div style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 16px 20px; margin-bottom: 24px;">
                                <h2 style="margin: 0 0 6px; font-size: 18px; font-weight: 800; color: #991b1b;">
                                    Database Successfully Restored
                                </h2>
                                <p style="margin: 0; font-size: 14px; color: #7f1d1d; line-height: 1.5;">
                                    A full database restoration operation was executed in the administrative control panel. All prior tables have been replaced with the snapshot data.
                                </p>
                            </div>

                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="font-size: 13px; color: #374151; border-collapse: collapse; margin-bottom: 24px;">
                                <tr style="border-bottom: 1px solid #f3f4f6;">
                                    <td style="padding: 10px 0; font-weight: 700; color: #6b7280; width: 40%;">Timestamp:</td>
                                    <td style="padding: 10px 0; font-weight: 600; color: #111827;">{{ $details['timestamp'] ?? date('d M Y, h:i:s A') }}</td>
                                </tr>
                                <tr style="border-bottom: 1px solid #f3f4f6;">
                                    <td style="padding: 10px 0; font-weight: 700; color: #6b7280;">Restored Snapshot:</td>
                                    <td style="padding: 10px 0; font-family: monospace; font-size: 12px; color: #1e40af; font-weight: 700;">{{ $details['filename'] ?? 'N/A' }}</td>
                                </tr>
                                <tr style="border-bottom: 1px solid #f3f4f6;">
                                    <td style="padding: 10px 0; font-weight: 700; color: #6b7280;">Initiated By:</td>
                                    <td style="padding: 10px 0; font-weight: 600; color: #111827;">{{ $details['admin_name'] ?? 'Admin' }} ({{ $details['admin_email'] ?? 'N/A' }})</td>
                                </tr>
                                <tr style="border-bottom: 1px solid #f3f4f6;">
                                    <td style="padding: 10px 0; font-weight: 700; color: #6b7280;">Operator IP:</td>
                                    <td style="padding: 10px 0; font-family: monospace; font-weight: 700; color: #111827;">{{ $details['ip'] ?? '127.0.0.1' }}</td>
                                </tr>
                                <tr style="border-bottom: 1px solid #f3f4f6;">
                                    <td style="padding: 10px 0; font-weight: 700; color: #6b7280;">Safety Fallback Backup:</td>
                                    <td style="padding: 10px 0; font-family: monospace; font-size: 11px; color: #047857; font-weight: 700;">{{ $details['safety_backup'] ?? 'Created automatically' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 0; font-weight: 700; color: #6b7280;">Tables Restored:</td>
                                    <td style="padding: 10px 0; font-weight: 700; color: #111827;">{{ $details['table_count'] ?? '34' }} tables synchronized</td>
                                </tr>
                            </table>

                            <div style="background-color: #f8fafc; border-left: 4px solid #3b82f6; padding: 14px 18px; border-radius: 4px; font-size: 13px; color: #475569; line-height: 1.6;">
                                <strong>Safety Snapshot Note:</strong> Before this restore began, our automated fail-safe created a full snapshot of your previous database. If this restoration was unexpected or accidental, the previous state can be restored from the server backup vault immediately.
                            </div>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="background-color: #f9fafb; padding: 20px 30px; border-top: 1px solid #e5e7eb; font-size: 12px; color: #6b7280; line-height: 1.5;">
                            <p style="margin: 0 0 4px; font-weight: 600;">Automated System Dispatch &bull; Do not reply to this email</p>
                            <p style="margin: 0; color: #9ca3af;">&copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
