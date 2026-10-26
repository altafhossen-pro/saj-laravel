<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $order->order_id }} — {{ \App\Models\Setting::get('site_name', 'SAJ') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        :root {
            --text-main: #111827;
            --text-muted: #374151;
            --text-light: #4b5563;
            --border-color: #e5e7eb;
            --border-dark: #111827;
            --bg-light: #f9fafb;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #f3f4f6;
            color: var(--text-main);
            min-height: 100vh;
            padding: 30px 16px;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        .container {
            max-width: 820px;
            margin: 0 auto;
        }

        /* Top Action Buttons */
        .actions-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            text-decoration: none;
            padding: 8px 14px;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            transition: all 0.15s ease;
        }

        .btn-back:hover {
            background: #f9fafb;
            color: #111827;
            border-color: #9ca3af;
        }

        .btn-print {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 20px;
            background: #111827;
            color: #ffffff;
            border: 1px solid #111827;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .btn-print:hover {
            background: #1f2937;
        }

        /* Invoice Sheet (Clean, Document-style) */
        .invoice-sheet {
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            padding: 44px 48px;
        }

        /* Header */
        .inv-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 24px;
            border-bottom: 2px solid #111827;
            margin-bottom: 28px;
        }

        .inv-brand {
            max-width: 60%;
        }

        .brand-logo-img {
            max-height: 52px;
            max-width: 200px;
            width: auto;
            object-fit: contain;
            display: block;
            margin-bottom: 8px;
        }

        .brand-logo-fallback {
            font-size: 1.65rem;
            font-weight: 800;
            color: #111827;
            letter-spacing: -0.5px;
            margin-bottom: 4px;
        }

        .brand-info-line {
            font-size: 0.825rem;
            color: #374151;
            line-height: 1.45;
        }

        .inv-meta-right {
            text-align: right;
        }

        .inv-title {
            font-size: 1.85rem;
            font-weight: 800;
            color: #111827;
            letter-spacing: 1px;
            line-height: 1;
            margin-bottom: 6px;
        }

        .inv-number {
            font-size: 1rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 4px;
        }

        .inv-date {
            font-size: 0.825rem;
            color: #374151;
            margin-bottom: 8px;
        }

        .badge-status {
            display: inline-block;
            padding: 3px 10px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: 4px;
            border: 1px solid #111827;
            background: #ffffff;
            color: #111827;
        }

        .badge-status.paid {
            background: #111827;
            color: #ffffff;
        }

        /* Billing Section */
        .billing-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 32px;
            margin-bottom: 28px;
            padding-bottom: 24px;
            border-bottom: 1px solid var(--border-color);
        }

        .section-heading {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #111827;
            margin-bottom: 8px;
        }

        .person-name {
            font-size: 0.95rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 3px;
        }

        .info-text {
            font-size: 0.835rem;
            color: #374151;
            line-height: 1.45;
        }

        /* Table */
        .table-wrap {
            width: 100%;
            margin-bottom: 24px;
        }

        table.items-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        table.items-table thead th {
            background-color: #f9fafb;
            color: #111827;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 10px 12px;
            border-top: 1px solid #111827;
            border-bottom: 1px solid #111827;
        }

        table.items-table tbody td {
            padding: 12px;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.85rem;
            color: #111827;
            vertical-align: top;
        }

        .item-title {
            font-weight: 600;
            color: #111827;
        }

        .item-variant {
            font-size: 0.75rem;
            color: #4b5563;
            margin-top: 2px;
        }

        .col-center { text-align: center; }
        .col-right  { text-align: right; }

        /* Summary & Payment Info */
        .bottom-section {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 32px;
            align-items: start;
            margin-bottom: 28px;
        }

        .payment-box {
            background: #f9fafb;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 16px 18px;
        }

        .pay-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.825rem;
            padding: 4px 0;
            color: #111827;
        }

        .pay-row .label {
            color: #4b5563;
        }

        .pay-row .value {
            font-weight: 600;
            color: #111827;
        }

        .summary-box {
            width: 100%;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            padding: 6px 0;
            color: #374151;
            border-bottom: 1px dashed var(--border-color);
        }

        .summary-row.discount {
            color: #b91c1c;
        }

        .summary-row.total-row {
            font-size: 1.05rem;
            font-weight: 800;
            color: #111827;
            border-top: 2px solid #111827;
            border-bottom: 2px solid #111827;
            padding: 10px 0;
            margin-top: 6px;
        }

        .due-strip {
            display: grid;
            grid-template-columns: 1fr 1fr;
            border: 1px solid #111827;
            border-radius: 4px;
            margin-top: 14px;
            overflow: hidden;
        }

        .due-box {
            padding: 10px;
            text-align: center;
        }

        .due-box.paid {
            background: #f9fafb;
            border-right: 1px solid #111827;
        }

        .due-box.balance {
            background: #111827;
            color: #ffffff;
        }

        .due-box .d-label {
            font-size: 0.65rem;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.8px;
            margin-bottom: 2px;
        }

        .due-box.paid .d-label { color: #374151; }
        .due-box.balance .d-label { color: #e5e7eb; }

        .due-box .d-amount {
            font-size: 1.05rem;
            font-weight: 800;
        }

        .due-box.paid .d-amount { color: #111827; }
        .due-box.balance .d-amount { color: #ffffff; }

        /* Footer */
        .inv-footer {
            border-top: 1px solid var(--border-color);
            padding-top: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.775rem;
            color: #4b5563;
        }

        .inv-footer strong {
            color: #111827;
        }

        /* PRINT STYLES - Strict Black & White / True Document Print */
        @media print {
            @page {
                margin: 12mm;
                size: A4 portrait;
            }

            body {
                background: #ffffff !important;
                color: #000000 !important;
                padding: 0 !important;
            }

            .container {
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 !important;
            }

            .actions-bar {
                display: none !important;
            }

            .invoice-sheet {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
            }

            .brand-info-line,
            .inv-date,
            .info-text,
            .item-variant,
            .pay-row .label,
            .summary-row,
            .inv-footer {
                color: #000000 !important;
            }

            .pay-row .value,
            .item-title,
            .person-name,
            .inv-number,
            .inv-title,
            .section-heading,
            .summary-row.total-row {
                color: #000000 !important;
            }

            table.items-table thead th {
                background-color: #f3f4f6 !important;
                color: #000000 !important;
                border-top: 1.5px solid #000000 !important;
                border-bottom: 1.5px solid #000000 !important;
            }

            table.items-table tbody td {
                color: #000000 !important;
                border-bottom: 1px solid #d1d5db !important;
            }

            .payment-box {
                background: #f9fafb !important;
                border: 1px solid #000000 !important;
            }

            .due-strip {
                border: 1.5px solid #000000 !important;
            }

            .due-box.paid {
                background: #ffffff !important;
                border-right: 1.5px solid #000000 !important;
            }

            .due-box.paid .d-label,
            .due-box.paid .d-amount {
                color: #000000 !important;
            }

            .due-box.balance {
                background: #000000 !important;
                color: #ffffff !important;
            }

            .due-box.balance .d-label,
            .due-box.balance .d-amount {
                color: #ffffff !important;
            }

            .inv-header {
                border-bottom: 2px solid #000000 !important;
            }

            .summary-row.total-row {
                border-top: 2px solid #000000 !important;
                border-bottom: 2px solid #000000 !important;
            }
        }

        @media (max-width: 640px) {
            .invoice-sheet {
                padding: 24px 18px;
            }
            .inv-header {
                flex-direction: column;
                gap: 16px;
            }
            .inv-brand {
                max-width: 100%;
            }
            .inv-meta-right {
                text-align: left;
            }
            .billing-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            .bottom-section {
                grid-template-columns: 1fr;
                gap: 24px;
            }
            .inv-footer {
                flex-direction: column;
                gap: 8px;
                text-align: center;
            }
        }
    </style>
</head>
<body>

@php
    $shipping = is_array($order->shipping_address) ? $order->shipping_address : json_decode($order->shipping_address, true);
    $siteName = \App\Models\Setting::get('site_name', 'SAJ');
    $rawLogo  = \App\Models\Setting::get('site_logo');

    // Resolve full logo URL safely
    $logoUrl = null;
    if ($rawLogo) {
        if (\Illuminate\Support\Str::startsWith($rawLogo, ['http://', 'https://'])) {
            $logoUrl = $rawLogo;
        } elseif (\Illuminate\Support\Str::startsWith($rawLogo, '/storage/') || \Illuminate\Support\Str::startsWith($rawLogo, 'storage/')) {
            $logoUrl = asset(ltrim($rawLogo, '/'));
        } else {
            $logoUrl = asset('storage/' . ltrim($rawLogo, '/'));
        }
    }

    $sitePhone   = \App\Models\Setting::get('site_phone', '');
    $siteEmail   = \App\Models\Setting::get('site_email', '');
    $siteAddress = \App\Models\Setting::get('site_address', 'Dhaka, Bangladesh');

    $subtotal = $order->items->sum('subtotal');
    $discount = ($order->discount ?? 0) + ($order->coupon_discount ?? 0) + ($order->upsell_discount ?? 0) + ($order->loyalty_discount ?? 0);
    $grand    = $order->total;
    $isPaid   = $order->payment_status === 'paid';

    $payMethodLabel = match(strtolower($order->payment_method ?? 'cod')) {
        'cod'    => 'Cash on Delivery',
        'bkash'  => 'bKash',
        'nagad'  => 'Nagad',
        'rocket' => 'Rocket',
        'card'   => 'Card Payment',
        'bank'   => 'Bank Transfer',
        default  => ucfirst($order->payment_method ?? 'Cash on Delivery'),
    };
@endphp

<div class="container">

    {{-- Top Action Bar --}}
    <div class="actions-bar">
        <a href="{{ route('dashboard.orders.show', $order->order_id) }}" class="btn-back">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd"/>
            </svg>
            Back to Order
        </a>
        <button class="btn-print" onclick="window.print()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                <path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"></path>
                <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
            Print / Save PDF
        </button>
    </div>

    {{-- Clean Print/Document Sheet --}}
    <div class="invoice-sheet">

        {{-- Invoice Header --}}
        <div class="inv-header">
            <div class="inv-brand">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="brand-logo-img">
                @else
                    <div class="brand-logo-fallback">{{ $siteName }}</div>
                @endif
                @if($siteAddress)<div class="brand-info-line">{{ $siteAddress }}</div>@endif
                @if($sitePhone)<div class="brand-info-line">Phone: {{ $sitePhone }}</div>@endif
                @if($siteEmail)<div class="brand-info-line">Email: {{ $siteEmail }}</div>@endif
            </div>

            <div class="inv-meta-right">
                <h1 class="inv-title">INVOICE</h1>
                <div class="inv-number">#{{ $order->order_id }}</div>
                <div class="inv-date">Date: {{ \Carbon\Carbon::parse($order->created_at)->format('d M, Y') }}</div>
                <div>
                    @if($isPaid)
                        <span class="badge-status paid">PAID</span>
                    @else
                        <span class="badge-status">PAYMENT PENDING</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Billing / Shipping Details --}}
        <div class="billing-grid">
            <div>
                <div class="section-heading">Invoiced From</div>
                <div class="person-name">{{ $siteName }}</div>
                @if($siteAddress)<div class="info-text">{{ $siteAddress }}</div>@endif
                @if($sitePhone)<div class="info-text">Hotline: {{ $sitePhone }}</div>@endif
                @if($siteEmail)<div class="info-text">Support: {{ $siteEmail }}</div>@endif
            </div>

            <div>
                <div class="section-heading">Invoiced To (Customer)</div>
                <div class="person-name">{{ $shipping['name'] ?? 'N/A' }}</div>
                @if(!empty($shipping['phone']))<div class="info-text"><strong>Phone:</strong> {{ $shipping['phone'] }}</div>@endif
                @if(!empty($shipping['address']))<div class="info-text"><strong>Address:</strong> {{ $shipping['address'] }}</div>@endif
            </div>
        </div>

        {{-- Items Table --}}
        <div class="table-wrap">
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 50%;">Item Description</th>
                        <th class="col-right" style="width: 18%;">Unit Price</th>
                        <th class="col-center" style="width: 12%;">Qty</th>
                        <th class="col-right" style="width: 20%;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                        @php
                            $vi = is_string($item->variant_info) ? json_decode($item->variant_info, true) : $item->variant_info;
                        @endphp
                        <tr>
                            <td>
                                <div class="item-title">{{ $item->name }}</div>
                                @if(!empty($vi['label']))
                                    <div class="item-variant">Variant: {{ $vi['label'] }}</div>
                                @endif
                            </td>
                            <td class="col-right">৳{{ number_format($item->price) }}</td>
                            <td class="col-center">{{ $item->quantity }}</td>
                            <td class="col-right"><strong>৳{{ number_format($item->subtotal) }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Bottom Section: Payment Info + Summary --}}
        <div class="bottom-section">
            {{-- Left: Payment Details --}}
            <div class="payment-box">
                <div class="section-heading" style="margin-bottom: 10px;">Payment Information</div>
                <div class="pay-row">
                    <span class="label">Method:</span>
                    <span class="value">{{ $payMethodLabel }}</span>
                </div>
                <div class="pay-row">
                    <span class="label">Payment Status:</span>
                    <span class="value">{{ $isPaid ? 'Paid' : 'Pending' }}</span>
                </div>
                <div class="pay-row">
                    <span class="label">Order Status:</span>
                    <span class="value">{{ ucfirst($order->status) }}</span>
                </div>
                <div class="pay-row">
                    <span class="label">Date:</span>
                    <span class="value">{{ \Carbon\Carbon::parse($order->created_at)->format('d M Y, h:i A') }}</span>
                </div>
                @if($order->coupon)
                <div class="pay-row">
                    <span class="label">Coupon:</span>
                    <span class="value">{{ strtoupper($order->coupon) }}</span>
                </div>
                @endif
            </div>

            {{-- Right: Totals --}}
            <div class="summary-box">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>৳{{ number_format($subtotal) }}</span>
                </div>
                <div class="summary-row">
                    <span>Delivery Charge</span>
                    <span>৳{{ number_format($order->shipping_cost) }}</span>
                </div>
                @if($discount > 0)
                <div class="summary-row discount">
                    <span>Discount{{ $order->coupon ? ' (' . strtoupper($order->coupon) . ')' : '' }}</span>
                    <span>-৳{{ number_format($discount) }}</span>
                </div>
                @endif
                <div class="summary-row total-row">
                    <span>Total Amount</span>
                    <span>৳{{ number_format($grand) }}</span>
                </div>

                {{-- Paid vs Due Box --}}
                <div class="due-strip">
                    <div class="due-box paid">
                        <div class="d-label">Paid</div>
                        <div class="d-amount">৳{{ number_format($isPaid ? $grand : 0) }}</div>
                    </div>
                    <div class="due-box balance">
                        <div class="d-label">Due</div>
                        <div class="d-amount">৳{{ number_format($isPaid ? 0 : $grand) }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="inv-footer">
            <div>
                Thank you for your order! For support, contact <strong>{{ $sitePhone ?: $siteEmail }}</strong>
            </div>
            <div>
                <strong>{{ $siteName }}</strong>
            </div>
        </div>

    </div>

</div>

</body>
</html>
