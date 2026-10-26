<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product\Product;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookCapiService
{
    /**
     * Check if Meta Pixel & CAPI is properly enabled and configured.
     */
    public static function isConfigured(): bool
    {
        $enabled = (bool) Setting::get('fb_pixel_enabled', false);
        $pixelId = Setting::get('fb_pixel_id');
        $token = Setting::get('fb_capi_token');

        return $enabled && !empty($pixelId) && !empty($token);
    }

    /**
     * Send an event to Meta Conversions API.
     */
    public static function sendEvent(string $eventName, array $customData = [], array $userData = [], ?string $eventId = null): array
    {
        if (!self::isConfigured()) {
            return ['success' => false, 'message' => 'CAPI not enabled or credentials missing'];
        }

        $pixelId = trim((string) Setting::get('fb_pixel_id'));
        $token = trim((string) Setting::get('fb_capi_token'));
        $testCode = trim((string) Setting::get('fb_test_event_code'));

        $request = request();

        // 1. Prepare User Data (Hashed per Meta guidelines: SHA-256 lowercase)
        $formattedUserData = [
            'client_ip_address' => $request ? $request->ip() : null,
            'client_user_agent' => $request ? $request->userAgent() : null,
        ];

        // Cookies _fbp and _fbc
        if ($request && $request->hasCookie('_fbp')) {
            $formattedUserData['fbp'] = $request->cookie('_fbp');
        }
        if ($request && $request->hasCookie('_fbc')) {
            $formattedUserData['fbc'] = $request->cookie('_fbc');
        }

        // Phone hashing
        $phone = $userData['phone'] ?? null;
        if (!empty($phone)) {
            $cleanPhone = preg_replace('/[^0-9]/', '', (string)$phone);
            if (strlen($cleanPhone) === 11 && str_starts_with($cleanPhone, '01')) {
                $cleanPhone = '88' . $cleanPhone;
            }
            $formattedUserData['ph'] = [hash('sha256', $cleanPhone)];
        }

        // Email hashing
        $email = $userData['email'] ?? null;
        if (!empty($email)) {
            $formattedUserData['em'] = [hash('sha256', strtolower(trim((string)$email)))];
        }

        // Name hashing
        $name = $userData['name'] ?? null;
        if (!empty($name)) {
            $nameParts = explode(' ', trim((string)$name), 2);
            $formattedUserData['fn'] = [hash('sha256', strtolower(trim($nameParts[0])))];
            if (!empty($nameParts[1])) {
                $formattedUserData['ln'] = [hash('sha256', strtolower(trim($nameParts[1])))];
            }
        }

        // Country hashing: Zero-mismatch priority
        // 1. Live Cloudflare edge header CF-IPCountry (100% accurate per visitor geo)
        $detectedCountry = null;
        $cfCountry = $request ? ($request->header('CF-IPCountry') ?? $_SERVER['HTTP_CF_IPCOUNTRY'] ?? null) : null;
        if (!empty($cfCountry)) {
            $cfUpper = strtoupper(trim((string)$cfCountry));
            if (preg_match('/^[A-Z]{2}$/', $cfUpper) && !in_array($cfUpper, ['XX', 'T1'])) {
                $detectedCountry = strtolower($cfUpper);
            }
        }
        // 2. Explicit country if provided
        if (!$detectedCountry && !empty($userData['country'])) {
            $userCountry = strtolower(trim((string)$userData['country']));
            if (strlen($userCountry) === 2) {
                $detectedCountry = $userCountry;
            }
        }
        // 3. Known Bangladeshi order/customer phone
        if (!$detectedCountry && !empty($phone)) {
            $cleanDigits = preg_replace('/[^0-9]/', '', (string)$phone);
            if (str_starts_with($cleanDigits, '8801') || (strlen($cleanDigits) === 11 && str_starts_with($cleanDigits, '01'))) {
                $detectedCountry = 'bd';
            }
        }
        if (!empty($detectedCountry)) {
            $formattedUserData['country'] = [hash('sha256', $detectedCountry)];
        }

        // City hashing (ct: lowercase, spaces/punctuation stripped)
        $city = $userData['city'] ?? null;
        if (!empty($city)) {
            $cleanCity = preg_replace('/[^a-z]/', '', strtolower(trim((string)$city)));
            if (!empty($cleanCity)) {
                $formattedUserData['ct'] = [hash('sha256', $cleanCity)];
            }
        }

        // External ID hashing (Unique user / customer / visitor identity)
        $externalId = $userData['external_id'] ?? null;
        if (!$externalId && $request && $request->user()) {
            $externalId = 'usr_' . $request->user()->id;
        } elseif (!$externalId && !empty($phone)) {
            $cleanDigits = preg_replace('/[^0-9]/', '', (string)$phone);
            if (!empty($cleanDigits)) {
                $externalId = 'ph_' . $cleanDigits;
            }
        } elseif (!$externalId && $request && $request->hasCookie('fb_vid')) {
            $externalId = 'vid_' . $request->cookie('fb_vid');
        }
        if (!empty($externalId)) {
            $formattedUserData['external_id'] = [hash('sha256', (string)$externalId)];
        }

        // 2. Prepare Event Payload
        $eventPayload = [
            'event_name' => $eventName,
            'event_time' => time(),
            'event_source_url' => $request ? $request->fullUrl() : url('/'),
            'action_source' => 'website',
            'user_data' => array_filter($formattedUserData),
            'custom_data' => $customData,
        ];

        if (!empty($eventId)) {
            $eventPayload['event_id'] = $eventId;
        }

        $payload = [
            'data' => [$eventPayload],
        ];

        if (!empty($testCode)) {
            $payload['test_event_code'] = $testCode;
        }

        // 3. Dispatch to Meta Graph API
        try {
            $url = "https://graph.facebook.com/v19.0/{$pixelId}/events?access_token={$token}";
            $response = Http::timeout(4)
                ->asJson()
                ->post($url, $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'events_received' => $response->json('events_received', 1),
                    'fbtrace_id' => $response->json('fbtrace_id'),
                ];
            }

            Log::warning('Meta CAPI response error', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return [
                'success' => false,
                'error' => $response->json('error.message', 'Meta API rejected request'),
            ];
        } catch (\Throwable $e) {
            Log::warning('Meta CAPI connection error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Track Purchase Event on Order Completion
     */
    public static function trackPurchase(Order $order, ?string $eventId = null): void
    {
        try {
            if (!self::isConfigured() || !Setting::get('fb_track_purchase', true)) {
                return;
            }

            $items = $order->items ?? [];
            $contentIds = [];
            $contents = [];

            foreach ($items as $item) {
                $pid = (string) ($item->product_id ?? $item->id);
                $contentIds[] = $pid;
                $contents[] = [
                    'id' => $pid,
                    'quantity' => (int) ($item->quantity ?? 1),
                    'item_price' => (float) ($item->price ?? 0),
                ];
            }

            $customerPhone = $order->shipping_address['phone'] ?? $order->guest_info['phone'] ?? $order->user?->phone;
            $customerName = $order->shipping_address['name'] ?? $order->guest_info['name'] ?? $order->user?->name;
            $customerEmail = $order->user?->email ?? null;

            // Detect City safely from delivery type or address
            $city = null;
            $shippingAddress = $order->shipping_address ?? [];
            if (is_array($shippingAddress)) {
                $rawAddress = strtolower(($shippingAddress['address'] ?? '') . ' ' . ($shippingAddress['city'] ?? '') . ' ' . ($shippingAddress['district'] ?? ''));
                if (str_contains($rawAddress, 'dhaka') || ($order->delivery_type ?? '') === 'inside') {
                    $city = 'dhaka';
                } elseif (str_contains($rawAddress, 'chattogram') || str_contains($rawAddress, 'chittagong')) {
                    $city = 'chittagong';
                } elseif (str_contains($rawAddress, 'sylhet')) {
                    $city = 'sylhet';
                } elseif (str_contains($rawAddress, 'rajshahi')) {
                    $city = 'rajshahi';
                } elseif (str_contains($rawAddress, 'khulna')) {
                    $city = 'khulna';
                } elseif (str_contains($rawAddress, 'barishal') || str_contains($rawAddress, 'barisal')) {
                    $city = 'barisal';
                } elseif (str_contains($rawAddress, 'rangpur')) {
                    $city = 'rangpur';
                } elseif (str_contains($rawAddress, 'mymensingh')) {
                    $city = 'mymensingh';
                } elseif (str_contains($rawAddress, 'gazipur')) {
                    $city = 'gazipur';
                } elseif (str_contains($rawAddress, 'narayanganj')) {
                    $city = 'narayanganj';
                } elseif (!empty($shippingAddress['city'])) {
                    $city = strtolower(trim((string)$shippingAddress['city']));
                }
            }

            $externalId = $order->user_id ? ('usr_' . $order->user_id) : ('ord_' . ($order->order_id ?? $order->id));

            self::sendEvent(
                'Purchase',
                [
                    'value' => (float) $order->total,
                    'currency' => 'BDT',
                    'content_type' => 'product',
                    'content_ids' => $contentIds,
                    'contents' => $contents,
                    'num_items' => count($contentIds),
                    'order_id' => $order->order_id ?? (string) $order->id,
                ],
                [
                    'phone' => $customerPhone,
                    'name' => $customerName,
                    'email' => $customerEmail,
                    'city' => $city,
                    'external_id' => $externalId,
                ],
                $eventId ?: ('purchase_' . ($order->order_id ?? $order->id))
            );
        } catch (\Throwable $e) {
            Log::warning('CAPI Purchase track error: ' . $e->getMessage());
        }
    }

    /**
     * Track AddToCart Event
     */
    public static function trackAddToCart(array $productData, int $quantity = 1, ?string $eventId = null, ?array $userData = null): void
    {
        try {
            if (!self::isConfigured() || !Setting::get('fb_track_add_to_cart', true)) {
                return;
            }

            $pid = (string) ($productData['product_id'] ?? $productData['id'] ?? 0);
            $price = (float) ($productData['price'] ?? 0);

            self::sendEvent(
                'AddToCart',
                [
                    'value' => $price * $quantity,
                    'currency' => 'BDT',
                    'content_type' => 'product',
                    'content_ids' => [$pid],
                    'contents' => [
                        [
                            'id' => $pid,
                            'quantity' => $quantity,
                            'item_price' => $price,
                        ]
                    ],
                    'content_name' => (string) ($productData['name'] ?? 'Product'),
                ],
                $userData ?: [],
                $eventId ?: ('cart_' . $pid . '_' . time())
            );
        } catch (\Throwable $e) {
            Log::warning('CAPI AddToCart track error: ' . $e->getMessage());
        }
    }

    /**
     * Track Search Event
     */
    public static function trackSearch(string $query, ?string $eventId = null, ?array $userData = null): void
    {
        try {
            if (!self::isConfigured() || !Setting::get('fb_track_search', true)) {
                return;
            }

            self::sendEvent(
                'Search',
                [
                    'search_string' => trim($query),
                ],
                $userData ?: [],
                $eventId ?: ('search_' . md5($query) . '_' . time())
            );
        } catch (\Throwable $e) {
            Log::warning('CAPI Search track error: ' . $e->getMessage());
        }
    }

    /**
     * Track CompleteRegistration Event
     */
    public static function trackCompleteRegistration(array $userData = [], ?string $status = 'success', ?string $eventId = null): void
    {
        try {
            if (!self::isConfigured() || !Setting::get('fb_track_complete_registration', true)) {
                return;
            }

            self::sendEvent(
                'CompleteRegistration',
                [
                    'status' => $status ?? 'success',
                    'content_name' => 'User Account Registration',
                ],
                $userData,
                $eventId ?: ('reg_' . time())
            );
        } catch (\Throwable $e) {
            Log::warning('CAPI CompleteRegistration track error: ' . $e->getMessage());
        }
    }

    /**
     * Track Contact Event
     */
    public static function trackContact(string $channel = 'form', ?string $eventId = null, ?array $userData = null): void
    {
        try {
            if (!self::isConfigured() || !Setting::get('fb_track_contact', true)) {
                return;
            }

            self::sendEvent(
                'Contact',
                [
                    'content_name' => 'Contact Inquiry (' . $channel . ')',
                ],
                $userData ?: [],
                $eventId ?: ('contact_' . time())
            );
        } catch (\Throwable $e) {
            Log::warning('CAPI Contact track error: ' . $e->getMessage());
        }
    }

    /**
     * Track ViewCategory Event
     */
    public static function trackViewCategory(string $categoryName, array $productIds = [], ?string $eventId = null, ?array $userData = null): void
    {
        try {
            if (!self::isConfigured() || !Setting::get('fb_track_view_category', true)) {
                return;
            }

            self::sendEvent(
                'ViewCategory',
                [
                    'content_name' => $categoryName,
                    'content_category' => $categoryName,
                    'content_type' => 'product_group',
                    'content_ids' => array_map('strval', $productIds),
                ],
                $userData ?: [],
                $eventId ?: ('cat_' . md5($categoryName) . '_' . time())
            );
        } catch (\Throwable $e) {
            Log::warning('CAPI ViewCategory track error: ' . $e->getMessage());
        }
    }

    /**
     * Send a Live Test Event to check connection with Meta Graph API
     */
    public static function testConnection(string $pixelId, string $token, ?string $testCode = null): array
    {
        try {
            $payload = [
                'data' => [
                    [
                        'event_name' => 'TestEvent',
                        'event_time' => time(),
                        'event_source_url' => url('/'),
                        'action_source' => 'website',
                        'user_data' => [
                            'client_ip_address' => request()->ip(),
                            'client_user_agent' => request()->userAgent(),
                        ],
                        'custom_data' => [
                            'test_note' => 'Ario Mart CAPI Diagnostic Test',
                        ],
                    ]
                ],
            ];

            if (!empty($testCode)) {
                $payload['test_event_code'] = $testCode;
            }

            $url = "https://graph.facebook.com/v19.0/{$pixelId}/events?access_token={$token}";
            $response = Http::timeout(5)
                ->asJson()
                ->post($url, $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Connection verified! Event received by Meta.',
                    'events_received' => $response->json('events_received', 1),
                    'fbtrace_id' => $response->json('fbtrace_id'),
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('error.message', 'Meta rejected credentials.'),
                'error_code' => $response->json('error.code'),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }
}
