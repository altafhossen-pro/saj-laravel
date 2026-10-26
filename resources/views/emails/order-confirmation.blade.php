@php
    $siteName = \App\Models\Setting::get('site_name', 'SAJ');
    $rawLogo  = \App\Models\Setting::get('site_logo');
    $logoUrl = null;
    // Resolve public HTTPS logo URL for email clients (Gmail blocks data:base64)
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
    
    $heading = $customHeading ?? \App\Models\Setting::get('email_tpl_order_placed_heading', 'Thank you for your order!');
    $messageText = $customMessage ?? \App\Models\Setting::get('email_tpl_order_placed_message', 'We have received your order and are currently preparing it for delivery. You can check the summary of your purchase below.');

    $subtotal = $order->items ? $order->items->sum('subtotal') : 0;
    $discount = ($order->discount ?? 0) + ($order->coupon_discount ?? 0) + ($order->upsell_discount ?? 0) + ($order->loyalty_discount ?? 0);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation #{{ $order->order_id }}</title>
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
                            <p style="margin: 4px 0 0; font-size: 13px; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Official Order Receipt</p>
                        </td>
                    </tr>

                    <!-- Order Intro Banner -->
                    <tr>
                        <td style="padding: 30px 32px 20px;">
                            <div style="background-color: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb; padding: 20px 24px; text-align: center;">
                                <div style="display: inline-block; width: 44px; height: 44px; line-height: 44px; border-radius: 50%; background-color: #109e62; color: #ffffff; font-size: 22px; margin-bottom: 10px;">✓</div>
                                <h2 style="margin: 0 0 6px; font-size: 20px; font-weight: 700; color: #111827;">{{ $heading }}</h2>
                                <p style="margin: 0; font-size: 14px; color: #4b5563; line-height: 1.5;">{{ $messageText }}</p>
                            </div>
                        </td>
                    </tr>

                    <!-- Order Details Summary Meta -->
                    <tr>
                        <td style="padding: 0 32px 24px;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse; font-size: 13px;">
                                <tr>
                                    <td style="padding: 8px 0; border-bottom: 1px solid #f3f4f6; color: #6b7280;">Order ID:</td>
                                    <td align="right" style="padding: 8px 0; border-bottom: 1px solid #f3f4f6; font-weight: 700; color: #111827;">#{{ $order->order_id }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; border-bottom: 1px solid #f3f4f6; color: #6b7280;">Order Date:</td>
                                    <td align="right" style="padding: 8px 0; border-bottom: 1px solid #f3f4f6; font-weight: 600; color: #374151;">{{ \Carbon\Carbon::parse($order->created_at)->format('d M Y, h:i A') }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; border-bottom: 1px solid #f3f4f6; color: #6b7280;">Payment Method:</td>
                                    <td align="right" style="padding: 8px 0; border-bottom: 1px solid #f3f4f6; font-weight: 600; color: #374151;">{{ ucfirst($order->payment_method ?? 'COD') }} ({{ ucfirst($order->payment_status ?? 'Pending') }})</td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 0; color: #6b7280;">Delivery To:</td>
                                    <td align="right" style="padding: 8px 0; font-weight: 600; color: #111827;">
                                        {{ $customerName }} ({{ $shipping['phone'] ?? '' }})<br>
                                        <span style="font-size: 12px; font-weight: normal; color: #6b7280;">{{ $shipping['address'] ?? '' }}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Order Items List -->
                    <tr>
                        <td style="padding: 0 32px 24px;">
                            <h3 style="margin: 0 0 12px; font-size: 14px; text-transform: uppercase; letter-spacing: 0.8px; color: #111827; font-weight: 700;">Order Items</h3>
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse; font-size: 13px;">
                                <thead>
                                    <tr style="background-color: #f9fafb; border-top: 1px solid #111827; border-bottom: 1px solid #111827;">
                                        <th align="left" style="padding: 8px 10px; color: #111827; font-weight: 700;">Product</th>
                                        <th align="center" style="padding: 8px 10px; color: #111827; font-weight: 700;">Qty</th>
                                        <th align="right" style="padding: 8px 10px; color: #111827; font-weight: 700;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($order->items as $item)
                                        @php
                                            $vi = is_string($item->variant_info) ? json_decode($item->variant_info, true) : $item->variant_info;
                                        @endphp
                                        <tr style="border-bottom: 1px solid #e5e7eb;">
                                            <td style="padding: 10px; color: #111827;">
                                                <div style="font-weight: 600;">{{ $item->name }}</div>
                                                @if(!empty($vi['label']))
                                                    <div style="font-size: 11px; color: #6b7280; margin-top: 2px;">Variant: {{ $vi['label'] }}</div>
                                                @endif
                                            </td>
                                            <td align="center" style="padding: 10px; color: #374151;">{{ $item->quantity }}</td>
                                            <td align="right" style="padding: 10px; font-weight: 600; color: #111827;">৳{{ number_format($item->subtotal) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </td>
                    </tr>

                    <!-- Totals Box -->
                    <tr>
                        <td style="padding: 0 32px 30px;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="font-size: 13px; line-height: 1.6;">
                                <tr>
                                    <td style="color: #6b7280;">Subtotal:</td>
                                    <td align="right" style="font-weight: 600; color: #111827;">৳{{ number_format($subtotal) }}</td>
                                </tr>
                                <tr>
                                    <td style="color: #6b7280;">Delivery Charge:</td>
                                    <td align="right" style="font-weight: 600; color: #111827;">৳{{ number_format($order->shipping_cost) }}</td>
                                </tr>
                                @if($discount > 0)
                                <tr>
                                    <td style="color: #b91c1c;">Discount{{ $order->coupon ? ' (' . strtoupper($order->coupon) . ')' : '' }}:</td>
                                    <td align="right" style="font-weight: 600; color: #b91c1c;">−৳{{ number_format($discount) }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td style="padding-top: 10px; border-top: 2px solid #111827; font-size: 16px; font-weight: 800; color: #111827;">Total Amount:</td>
                                    <td align="right" style="padding-top: 10px; border-top: 2px solid #111827; font-size: 17px; font-weight: 800; color: #111827;">৳{{ number_format($order->total) }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Button to view online order/invoice -->
                    <tr>
                        <td align="center" style="padding: 0 32px 32px;">
                            <a href="{{ url('/dashboard/my-orders/' . $order->order_id . '/invoice') }}" style="display: inline-block; background-color: #111827; color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 6px; font-size: 14px; font-weight: 600; letter-spacing: 0.3px;">View Invoice Online</a>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="background-color: #f9fafb; padding: 24px 30px; border-top: 1px solid #e5e7eb; font-size: 12px; color: #6b7280; line-height: 1.5;">
                            <p style="margin: 0 0 6px;">Need help with your order? Contact us at <strong>{{ $sitePhone ?: $siteEmail }}</strong></p>
                            <p style="margin: 0; color: #9ca3af;">&copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
