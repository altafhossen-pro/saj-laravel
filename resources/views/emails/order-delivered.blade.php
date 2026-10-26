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
    $shipping    = is_array($order->shipping_address) ? $order->shipping_address : json_decode($order->shipping_address, true);
    $customerName = $shipping['name'] ?? optional($order->user)->name ?? 'Valued Customer';
    $heading = $customHeading ?? \App\Models\Setting::get('email_tpl_order_delivered_heading', 'Your package has arrived!');
    $messageText = $customMessage ?? \App\Models\Setting::get('email_tpl_order_delivered_message', 'We are pleased to inform you that your order has been successfully delivered. We hope you love your purchase!');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $heading }} - #{{ $order->order_id }}</title>
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
                            <p style="margin: 4px 0 0; font-size: 13px; color: #109e62; text-transform: uppercase; letter-spacing: 1px; font-weight: 700;">Delivered Successfully</p>
                        </td>
                    </tr>

                    <!-- Delivered Box -->
                    <tr>
                        <td style="padding: 36px 36px 24px; text-align: center;">
                            <div style="display: inline-block; width: 60px; height: 60px; line-height: 60px; border-radius: 50%; background-color: #ecfdf5; color: #109e62; font-size: 32px; margin-bottom: 16px;">
                                📦
                            </div>
                            <h2 style="margin: 0 0 10px; font-size: 22px; font-weight: 800; color: #111827;">{{ $heading }}</h2>
                            <p style="margin: 0 0 20px; font-size: 15px; color: #4b5563; line-height: 1.6;">{{ $messageText }}</p>

                            <!-- Meta -->
                            <div style="background-color: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb; padding: 18px 24px; margin: 24px 0; text-align: left; font-size: 13px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                    <span style="color: #6b7280;">Order ID:</span>
                                    <strong style="color: #111827;">#{{ $order->order_id }}</strong>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                    <span style="color: #6b7280;">Total Paid:</span>
                                    <strong style="color: #109e62;">৳{{ number_format($order->total) }}</strong>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #6b7280;">Delivered To:</span>
                                    <strong style="color: #111827;">{{ $customerName }}</strong>
                                </div>
                            </div>

                            <p style="font-size: 13px; color: #4b5563; margin-bottom: 24px;">
                                How was your experience? Your honest feedback helps other shoppers make informed decisions.
                            </p>

                            <a href="{{ url('/dashboard/my-orders/' . $order->order_id . '/invoice') }}" style="display: inline-block; background-color: #111827; color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 8px; font-size: 14px; font-weight: 700; margin-right: 8px;">
                                View Invoice
                            </a>
                            <a href="{{ url('/dashboard/my-reviews') }}" style="display: inline-block; background-color: #109e62; color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 8px; font-size: 14px; font-weight: 700;">
                                Write a Review ⭐
                            </a>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="background-color: #f9fafb; padding: 24px 30px; border-top: 1px solid #e5e7eb; font-size: 12px; color: #6b7280; line-height: 1.5;">
                            <p style="margin: 0 0 6px;">Any issue with your package? Contact us at <strong>{{ $sitePhone ?: $siteEmail }}</strong></p>
                            <p style="margin: 0; color: #9ca3af;">&copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
