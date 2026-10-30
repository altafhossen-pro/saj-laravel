<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CartSession;
use App\Models\OfferTemplate;
use App\Models\VisitorCustomOffer;
use App\Models\VisitorSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisitorFootprintController extends Controller
{
    /**
     * Display real-time live shoppers monitor dedicated page
     */
    public function live()
    {
        $initialTodayVisitors = 0;
        foreach ([
            '/dev/shm/ariomart_visitors',
            storage_path('framework/cache/visitors')
        ] as $p) {
            $f = $p . '/daily_' . date('Y-m-d') . '.json';
            if (file_exists($f)) {
                $d = json_decode(@file_get_contents($f), true);
                if (isset($d['unique'])) {
                    $initialTodayVisitors = (int) $d['unique'];
                    break;
                }
            }
        }
        $templates = OfferTemplate::where('is_active', true)->latest()->get();

        return view('admin.analytics.live-monitor', compact('initialTodayVisitors', 'templates'));
    }

    /**
     * Display date-wise visitor history and session footprints
     */
    public function index(Request $request)
    {
        $date = $request->input('date', date('Y-m-d'));
        $filter = $request->input('filter', 'all'); // 'all', 'returning', 'with_cart', 'checkout', 'identified'
        $search = trim($request->input('search', ''));

        $query = VisitorSession::with(['user', 'cartSession', 'customOffers'])
            ->whereDate('session_date', $date)
            ->latest('last_seen_at');

        if ($filter === 'returning') {
            $query->where('is_returning', true);
        } elseif ($filter === 'with_cart') {
            $query->where('has_cart', true);
        } elseif ($filter === 'checkout') {
            $query->where('visited_checkout', true);
        } elseif ($filter === 'identified') {
            $query->where(function ($q) {
                $q->whereNotNull('user_id')
                    ->orWhereNotNull('customer_phone')
                    ->orWhereNotNull('customer_name');
            });
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('visitor_token', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('last_title', 'like', "%{$search}%")
                    ->orWhere('entry_url', 'like', "%{$search}%");
            });
        }

        $sessions = $query->paginate(25)->withQueryString();

        // Summary metrics for selected date
        $baseDateQuery = VisitorSession::whereDate('session_date', $date);
        $metrics = [
            'total_visitors' => (clone $baseDateQuery)->count(),
            'total_page_views' => (clone $baseDateQuery)->sum('page_views_count'),
            'returning_count' => (clone $baseDateQuery)->where('is_returning', true)->count(),
            'cart_visitors' => (clone $baseDateQuery)->where('has_cart', true)->count(),
            'checkout_visitors' => (clone $baseDateQuery)->where('visited_checkout', true)->count(),
            'identified_visitors' => (clone $baseDateQuery)->where(function ($q) {
                $q->whereNotNull('user_id')
                    ->orWhereNotNull('customer_phone')
                    ->orWhereNotNull('customer_name');
            })->count(),
            'avg_duration_seconds' => (int) round((clone $baseDateQuery)->selectRaw('AVG(GREATEST(duration_seconds, TIMESTAMPDIFF(SECOND, first_seen_at, last_seen_at))) as avg_sec')->value('avg_sec') ?? 0),
        ];

        $templates = OfferTemplate::where('is_active', true)->latest()->get();

        return view('admin.analytics.visitor-footprint', compact(
            'sessions',
            'date',
            'filter',
            'search',
            'metrics',
            'templates'
        ));
    }

    /**
     * Get detailed journey timeline of a specific visitor session (AJAX modal)
     */
    public function journey(VisitorSession $session): JsonResponse
    {
        $session->load(['events', 'user', 'cartSession', 'customOffers']);

        $events = $session->events;
        $eventCount = $events->count();
        $formattedEvents = [];

        for ($i = 0; $i < $eventCount; $i++) {
            $ev = $events[$i];
            $currentTs = $ev->created_at ? $ev->created_at->timestamp : 0;
            $durationText = null;

            if ($i < ($eventCount - 1)) {
                $nextTs = $events[$i + 1]->created_at ? $events[$i + 1]->created_at->timestamp : $currentTs;
                $diffSec = max(0, $nextTs - $currentTs);
                if ($diffSec < 60) {
                    $durationText = "Stayed: {$diffSec}s";
                } else {
                    $m = floor($diffSec / 60);
                    $s = $diffSec % 60;
                    $durationText = "Stayed: {$m}m {$s}s";
                }
            } else {
                $durationText = "Current / Last Page";
            }

            $formattedEvents[] = [
                'id' => $ev->id,
                'type' => $ev->event_type,
                'url' => $ev->url,
                'title' => $ev->title,
                'time' => $ev->created_at?->format('h:i:s A'),
                'duration' => $durationText,
            ];
        }

        return response()->json([
            'success' => true,
            'session' => [
                'id' => $session->id,
                'vid' => $session->visitor_token,
                'name' => $session->customer_name ?: ($session->user?->name ?: 'Guest Shopper'),
                'phone' => $session->customer_phone ?: ($session->user?->phone ?: null),
                'email' => $session->customer_email ?: ($session->user?->email ?: null),
                'ip' => $session->ip_address,
                'device' => $session->device,
                'browser' => $session->browser,
                'os' => $session->os,
                'referrer' => $session->referrer ?: 'Direct / Bookmark',
                'first_seen' => $session->first_seen_at?->format('h:i:s A, d M Y'),
                'last_seen' => $session->last_seen_at?->format('h:i:s A, d M Y'),
                'duration' => $session->formatted_duration,
                'page_views' => $session->page_views_count,
                'is_returning' => $session->is_returning,
                'has_cart' => $session->has_cart,
                'visited_checkout' => $session->visited_checkout,
                'cart' => $session->cartSession ? [
                    'total_items' => $session->cartSession->total_items,
                    'total_amount' => $session->cartSession->total_amount,
                    'status' => $session->cartSession->status,
                    'items' => $session->cartSession->items,
                ] : null,
                'offers' => $session->customOffers->map(function ($o) {
                    return [
                        'id' => $o->id,
                        'title' => $o->title,
                        'message' => $o->message,
                        'coupon_code' => $o->coupon_code,
                        'button_text' => $o->button_text,
                        'button_url' => $o->button_url,
                        'delay_seconds' => $o->delay_seconds,
                        'status' => $o->status,
                        'sent_at' => $o->created_at?->format('h:i:s A, d M Y'),
                        'delivered_at' => $o->delivered_at?->format('h:i:s A, d M Y'),
                        'action_at' => $o->action_at?->format('h:i:s A, d M Y'),
                    ];
                }),
            ],
            'events' => $formattedEvents,
        ]);
    }

    /**
     * Cache pending offer into ultra-fast storage directory for visitor-heartbeat.php
     */
    public static function cacheInstantOffer(VisitorCustomOffer $offer): void
    {
        try {
            $storageDir = is_dir('/dev/shm') && is_writable('/dev/shm')
                ? '/dev/shm/ariomart_visitors'
                : storage_path('framework/cache/visitors');

            if (!is_dir($storageDir)) {
                @mkdir($storageDir, 0775, true);
            }

            $offersFile = $storageDir . '/pending_offers.json';
            $fp = @fopen($offersFile, 'c+');
            if ($fp) {
                if (flock($fp, LOCK_EX)) {
                    $size = filesize($offersFile);
                    $content = $size > 0 ? fread($fp, $size) : '';
                    $offers = json_decode($content, true);
                    if (!is_array($offers)) {
                        $offers = [];
                    }

                    $offerData = [
                        'id' => $offer->id,
                        'visitor_token' => $offer->visitor_token,
                        'customer_phone' => $offer->customer_phone,
                        'user_id' => $offer->user_id,
                        'title' => $offer->title,
                        'message' => $offer->message,
                        'coupon_code' => $offer->coupon_code,
                        'button_text' => $offer->button_text,
                        'button_url' => $offer->button_url,
                        'delay_seconds' => (int)$offer->delay_seconds,
                        'show_mode' => $offer->show_mode,
                        'repeat_interval_minutes' => (int)$offer->repeat_interval_minutes,
                        'created_at' => time(),
                    ];

                    // Store indexed by visitor_token for O(1) lightning lookup
                    $offers[$offer->visitor_token] = $offerData;
                    if ($offer->customer_phone) {
                        $offers['phone_' . $offer->customer_phone] = $offerData;
                    }
                    if ($offer->user_id) {
                        $offers['uid_' . $offer->user_id] = $offerData;
                    }

                    ftruncate($fp, 0);
                    rewind($fp);
                    fwrite($fp, json_encode($offers));
                    flock($fp, LOCK_UN);
                }
                fclose($fp);
            }
        } catch (\Throwable $e) { }
    }

    /**
     * Send custom instant popup offer to a visitor (Live or historical)
     */
    public function sendOffer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'visitor_token' => 'required|string',
            'title' => 'required|string|max:120',
            'message' => 'required|string|max:1000',
            'coupon_code' => 'nullable|string|max:50',
            'button_text' => 'nullable|string|max:60',
            'button_url' => 'nullable|string|max:255',
            'delay_seconds' => 'nullable|integer|min:0|max:3600',
            'show_mode' => 'nullable|string|in:once,every_session',
            'repeat_interval_minutes' => 'nullable|integer|min:1|max:1440',
            'customer_phone' => 'nullable|string|max:40',
            'user_id' => 'nullable|integer',
        ]);

        $offer = VisitorCustomOffer::create([
            'visitor_token' => trim($validated['visitor_token']),
            'user_id' => $validated['user_id'] ?? null,
            'customer_phone' => $validated['customer_phone'] ?? null,
            'title' => $validated['title'],
            'message' => $validated['message'],
            'coupon_code' => $validated['coupon_code'] ? strtoupper(trim($validated['coupon_code'])) : null,
            'button_text' => $validated['button_text'] ?: null,
            'button_url' => $validated['button_url'] ?: null,
            'delay_seconds' => isset($validated['delay_seconds']) ? (int)$validated['delay_seconds'] : 10,
            'show_mode' => $validated['show_mode'] ?? 'once',
            'repeat_interval_minutes' => isset($validated['repeat_interval_minutes']) ? (int)$validated['repeat_interval_minutes'] : 60,
            'status' => 'pending',
            'admin_name' => auth()->user()?->name ?: 'Admin',
        ]);

        // Write to fast shared RAM / cache file for ultra-fast zero-DB heartbeat retrieval
        self::cacheInstantOffer($offer);

        return response()->json([
            'success' => true,
            'message' => 'Custom offer dispatched successfully.',
            'offer' => $offer
        ]);
    }

    /**
     * Get list of offers sent to visitors
     */
    public function offersHistory(): JsonResponse
    {
        $offers = VisitorCustomOffer::latest()->take(50)->get();
        return response()->json([
            'success' => true,
            'offers' => $offers
        ]);
    }
}

