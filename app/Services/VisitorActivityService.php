<?php

namespace App\Services;

use App\Models\CartSession;
use App\Models\VisitorEvent;
use App\Models\VisitorSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class VisitorActivityService
{
    /**
     * Record or update visitor session and event
     */
    public static function record(array $data, ?Request $request = null): ?VisitorSession
    {
        $token = trim((string)($data['vid'] ?? ''));
        if (empty($token) || strlen($token) < 6) {
            return null;
        }

        // Avoid logging known bot crawlers or placeholders
        $userAgent = $data['user_agent'] ?? ($request?->header('User-Agent') ?? '');
        $lowerAgent = strtolower($userAgent);
        $bots = ['googlebot', 'bingbot', 'slurp', 'duckduckbot', 'baiduspider', 'yandexbot', 'semrushbot', 'ahrefsbot', 'bytespider'];
        foreach ($bots as $bot) {
            if (str_contains($lowerAgent, $bot)) {
                return null;
            }
        }

        $today = date('Y-m-d');
        $ip = $data['ip'] ?? ($request?->ip() ?? '127.0.0.1');
        $device = $data['device'] ?? 'Desktop';
        $url = substr(strip_tags((string)($data['url'] ?? '/')), 0, 255);
        $title = substr(strip_tags((string)($data['title'] ?? 'Storefront')), 0, 150);
        $referrer = substr(strip_tags((string)($data['referrer'] ?? '')), 0, 255);

        // Detect OS and Browser simply
        $browser = self::detectBrowser($userAgent);
        $os = self::detectOS($userAgent);

        $now = now();

        // Check if returning visitor (has prior sessions before today)
        $hasPrior = VisitorSession::where('visitor_token', $token)
            ->where('session_date', '<', $today)
            ->exists();

        // Find or create session for today
        $session = VisitorSession::where('visitor_token', $token)
            ->where('session_date', $today)
            ->first();

        $profile = $data['profile'] ?? [];
        $userId = $profile['user_id'] ?? auth()->id();
        $customerName = $profile['name'] ?? null;
        $customerPhone = $profile['phone'] ?? null;
        $customerEmail = $profile['email'] ?? null;
        $customerAddress = $profile['address'] ?? ($data['address'] ?? null);

        // Check cart status & address fallback
        $cartSession = CartSession::where('visitor_token', $token)->latest('id')->first();
        $hasCart = $cartSession && $cartSession->total_items > 0;
        if (!$customerAddress && $cartSession && !empty($cartSession->customer_address)) {
            $customerAddress = $cartSession->customer_address;
        }

        $visitedCheckout = str_contains($url, '/checkout');

        // Determine daily serial (keeps same sequential serial for today)
        $passedSerial = !empty($data['daily_serial']) ? (int)$data['daily_serial'] : 0;
        if ($passedSerial <= 0) {
            $maxSerial = VisitorSession::where('session_date', $today)->max('daily_serial') ?? 0;
            $dailySerial = $maxSerial + 1;
        } else {
            $dailySerial = $passedSerial;
        }

        if (!$session) {
            $session = VisitorSession::create([
                'visitor_token' => $token,
                'daily_serial' => $dailySerial,
                'user_id' => $userId,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'customer_address' => $customerAddress,
                'customer_email' => $customerEmail,
                'ip_address' => $ip,
                'device' => $device,
                'browser' => $browser,
                'os' => $os,
                'referrer' => $referrer ?: null,
                'entry_url' => $url,
                'last_url' => $url,
                'last_title' => $title,
                'page_views_count' => 1,
                'duration_seconds' => 0,
                'has_cart' => $hasCart,
                'visited_checkout' => $visitedCheckout,
                'is_returning' => $hasPrior,
                'session_date' => $today,
                'first_seen_at' => $now,
                'last_seen_at' => $now,
            ]);

            // First event
            VisitorEvent::create([
                'visitor_session_id' => $session->id,
                'visitor_token' => $token,
                'event_type' => $visitedCheckout ? 'checkout_visit' : 'page_view',
                'url' => $url,
                'title' => $title,
                'created_at' => $now,
            ]);
        } else {
            $isDifferentPage = ($session->last_url !== $url);
            $firstSeenTs = $session->first_seen_at ? $session->first_seen_at->timestamp : $now->timestamp;
            $duration = max(0, (int)($now->timestamp - $firstSeenTs));

            $updateData = [
                'last_url' => $url,
                'last_title' => $title,
                'duration_seconds' => $duration,
                'last_seen_at' => $now,
            ];

            if ($isDifferentPage) {
                $updateData['page_views_count'] = $session->page_views_count + 1;
            }

            if (!$session->daily_serial && $dailySerial > 0) $updateData['daily_serial'] = $dailySerial;
            if ($userId && !$session->user_id) $updateData['user_id'] = $userId;
            if ($customerName && !$session->customer_name) $updateData['customer_name'] = $customerName;
            if ($customerPhone && !$session->customer_phone) $updateData['customer_phone'] = $customerPhone;
            if ($customerAddress && !$session->customer_address) $updateData['customer_address'] = $customerAddress;
            if ($customerEmail && !$session->customer_email) $updateData['customer_email'] = $customerEmail;
            if ($hasCart) $updateData['has_cart'] = true;
            if ($visitedCheckout) $updateData['visited_checkout'] = true;

            $session->update($updateData);

            if ($isDifferentPage) {
                VisitorEvent::create([
                    'visitor_session_id' => $session->id,
                    'visitor_token' => $token,
                    'event_type' => $visitedCheckout ? 'checkout_visit' : 'page_view',
                    'url' => $url,
                    'title' => $title,
                    'created_at' => $now,
                ]);
            }
        }

        return $session;
    }

    protected static function detectBrowser(?string $ua): string
    {
        if (empty($ua)) return 'Unknown';
        if (str_contains($ua, 'Edg/')) return 'Edge';
        if (str_contains($ua, 'Chrome/')) return 'Chrome';
        if (str_contains($ua, 'Firefox/')) return 'Firefox';
        if (str_contains($ua, 'Safari/') && !str_contains($ua, 'Chrome/')) return 'Safari';
        if (str_contains($ua, 'Opera/') || str_contains($ua, 'OPR/')) return 'Opera';
        return 'Other';
    }

    protected static function detectOS(?string $ua): string
    {
        if (empty($ua)) return 'Unknown';
        if (str_contains($ua, 'Windows NT 10')) return 'Windows 10/11';
        if (str_contains($ua, 'Windows NT')) return 'Windows';
        if (str_contains($ua, 'Android')) return 'Android';
        if (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) return 'iOS';
        if (str_contains($ua, 'Mac OS X')) return 'macOS';
        if (str_contains($ua, 'Linux')) return 'Linux';
        return 'Other';
    }
}

