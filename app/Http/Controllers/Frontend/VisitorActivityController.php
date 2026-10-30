<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\VisitorCustomOffer;
use App\Services\VisitorActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisitorActivityController extends Controller
{
    /**
     * Record visitor activity pulse and check for pending custom offers/messages
     */
    public function pulse(Request $request): JsonResponse
    {
        $payload = $request->all();
        $payload['ip'] = $request->ip();
        $payload['user_agent'] = $request->header('User-Agent');

        try {
            VisitorActivityService::record($payload, $request);
        } catch (\Throwable $e) {
            // Silently fail to never disrupt user experience
        }

        // Check if there is any pending custom offer for this visitor
        $token = trim((string)($payload['vid'] ?? ''));
        $user = auth()->user();
        $phone = $payload['profile']['phone'] ?? ($user?->phone ?? null);

        $offer = null;
        if (!empty($token) || !empty($phone) || $user) {
            $offerQuery = VisitorCustomOffer::where(function ($q) {
                    $q->where('status', 'pending')
                      ->orWhere(function ($sub) {
                          $sub->where('show_mode', 'every_session')
                              ->where('status', '!=', 'clicked')
                              ->where(function ($timeSub) {
                                  $timeSub->whereNull('delivered_at')
                                      ->orWhereRaw('TIMESTAMPDIFF(MINUTE, delivered_at, NOW()) >= repeat_interval_minutes');
                              });
                      });
                })
                ->where(function ($q) use ($token, $phone, $user) {
                    if ($token) {
                        $q->where('visitor_token', $token);
                    }
                    if ($phone) {
                        $q->orWhere('customer_phone', $phone);
                    }
                    if ($user) {
                        $q->orWhere('user_id', $user->id);
                    }
                })
                ->latest()
                ->first();

            if ($offerQuery) {
                $offer = [
                    'id' => $offerQuery->id,
                    'title' => $offerQuery->title,
                    'message' => $offerQuery->message,
                    'coupon_code' => $offerQuery->coupon_code,
                    'button_text' => $offerQuery->button_text ?: null,
                    'button_url' => $offerQuery->button_url ?: null,
                    'delay_seconds' => (int) $offerQuery->delay_seconds,
                    'show_mode' => $offerQuery->show_mode,
                ];
            }
        }

        return response()->json([
            'status' => 'ok',
            'offer' => $offer,
        ]);
    }

    /**
     * Mark an offer as delivered or clicked/dismissed
     */
    public function respondOffer(Request $request, VisitorCustomOffer $offer): JsonResponse
    {
        $action = $request->input('action', 'delivered'); // 'delivered', 'clicked', 'dismissed'

        if ($action === 'clicked') {
            $offer->update([
                'status' => 'clicked',
                'action_at' => now(),
            ]);
            self::clearCachedOffer($offer);
        } elseif ($action === 'dismissed') {
            $offer->update([
                'status' => 'dismissed',
                'action_at' => now(),
            ]);
            if ($offer->show_mode === 'once') {
                self::clearCachedOffer($offer);
            }
        } elseif ($action === 'delivered') {
            $update = [
                'delivered_at' => now(),
                'display_count' => $offer->display_count + 1,
            ];
            if ($offer->show_mode === 'once') {
                $update['status'] = 'delivered';
                self::clearCachedOffer($offer);
            }
            $offer->update($update);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Remove delivered/completed offer from ultra-fast RAM cache
     */
    public static function clearCachedOffer(VisitorCustomOffer $offer): void
    {
        try {
            $storageDir = is_dir('/dev/shm') && is_writable('/dev/shm')
                ? '/dev/shm/ariomart_visitors'
                : storage_path('framework/cache/visitors');

            $offersFile = $storageDir . '/pending_offers.json';
            if (file_exists($offersFile)) {
                $fp = @fopen($offersFile, 'c+');
                if ($fp) {
                    if (flock($fp, LOCK_EX)) {
                        $size = filesize($offersFile);
                        $content = $size > 0 ? fread($fp, $size) : '';
                        $offers = json_decode($content, true);
                        if (is_array($offers)) {
                            unset($offers[$offer->visitor_token]);
                            if ($offer->customer_phone) {
                                unset($offers['phone_' . $offer->customer_phone]);
                            }
                            if ($offer->user_id) {
                                unset($offers['uid_' . $offer->user_id]);
                            }
                            ftruncate($fp, 0);
                            rewind($fp);
                            fwrite($fp, json_encode($offers));
                        }
                        flock($fp, LOCK_UN);
                    }
                    fclose($fp);
                }
            }
        } catch (\Throwable $e) { }
    }
}
