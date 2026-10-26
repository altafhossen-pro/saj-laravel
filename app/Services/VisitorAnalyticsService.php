<?php

namespace App\Services;

class VisitorAnalyticsService
{
    /**
     * Get storage directory (checks /dev/shm first, then storage/framework/cache/visitors)
     */
    protected static function getStorageDir(): string
    {
        if (is_dir('/dev/shm') && is_writable('/dev/shm')) {
            $dir = '/dev/shm/ariomart_visitors';
        } else {
            $dir = storage_path('framework/cache/visitors');
        }

        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return $dir;
    }

    /**
     * Get real-time active visitor count right now
     */
    public static function getLiveCount(): int
    {
        $dir = self::getStorageDir();
        $file = $dir . '/live_sessions.json';

        if (!file_exists($file)) {
            return 0;
        }

        $content = @file_get_contents($file);
        if (!$content) {
            return 0;
        }

        $sessions = json_decode($content, true);
        if (!is_array($sessions)) {
            return 0;
        }

        $now = time();
        $windowSeconds = 40; // 40 seconds active window
        $active = 0;

        return count(self::getLiveVisitors(500));
    }

    /**
     * Get active live visitors with real-time metadata (page, device, shopper identity)
     */
    public static function getLiveVisitors(int $limit = 30): array
    {
        $dir = self::getStorageDir();
        $file = $dir . '/live_sessions.json';

        if (!file_exists($file)) {
            return [];
        }

        $content = @file_get_contents($file);
        if (!$content) {
            return [];
        }

        $sessions = json_decode($content, true);
        if (!is_array($sessions)) {
            return [];
        }

        $now = time();
        $windowSeconds = 35;
        $visitors = [];

        foreach ($sessions as $id => $item) {
            $lastSeen = is_array($item) ? ($item['last_seen'] ?? 0) : (int)$item;
            $diff = $now - (int)$lastSeen;
            if ($diff <= $windowSeconds) {
                if (is_array($item)) {
                    $visitors[] = [
                        'vid' => $id,
                        'user_id' => $item['user_id'] ?? null,
                        'name' => $item['name'] ?? null,
                        'phone' => $item['phone'] ?? null,
                        'email' => $item['email'] ?? null,
                        'is_logged_in' => !empty($item['is_logged_in']),
                        'last_seen_as' => $item['last_seen_as'] ?? ($item['name'] ?? null),
                        'url' => $item['url'] ?? '/',
                        'title' => $item['title'] ?? 'Storefront',
                        'device' => $item['device'] ?? 'Desktop',
                        'ip' => $item['ip'] ?? '',
                        'seconds_ago' => max(0, $diff),
                        'duration' => isset($item['first_seen']) ? max(0, $now - (int)$item['first_seen']) : 0,
                        'history' => isset($item['history']) && is_array($item['history']) ? $item['history'] : [
                            [
                                'url' => $item['url'] ?? '/',
                                'title' => $item['title'] ?? 'Storefront',
                                'time' => date('h:i:s A', (int)($item['first_seen'] ?? $now)),
                                'timestamp' => (int)($item['first_seen'] ?? $now),
                            ]
                        ],
                    ];
                } else {
                    $visitors[] = [
                        'vid' => $id,
                        'user_id' => null,
                        'name' => null,
                        'phone' => null,
                        'email' => null,
                        'is_logged_in' => false,
                        'last_seen_as' => null,
                        'url' => '/',
                        'title' => 'Storefront',
                        'device' => 'Desktop',
                        'ip' => '',
                        'seconds_ago' => max(0, $diff),
                        'duration' => 0,
                        'history' => [],
                    ];
                }
            }
        }

        usort($visitors, function ($a, $b) {
            return $a['seconds_ago'] <=> $b['seconds_ago'];
        });

        // Intelligent deduplication: 1 customer = 1 entry (keep their latest active session)
        $deduped = [];
        $seen = [];
        foreach ($visitors as $v) {
            $key = null;
            if (!empty($v['user_id'])) {
                $key = 'uid_' . $v['user_id'];
            } elseif (!empty($v['phone'])) {
                $key = 'phone_' . preg_replace('/[^0-9]/', '', $v['phone']);
            } elseif (!empty($v['name']) && $v['is_logged_in']) {
                $key = 'name_' . strtolower(trim($v['name']));
            }

            if ($key) {
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
            }
            $deduped[] = $v;
        }

        return array_slice($deduped, 0, $limit);
    }

    /**
     * Get today's unique visitors and views
     */
    public static function getTodayStats(): array
    {
        $dir = self::getStorageDir();
        $today = date('Y-m-d');
        $file = $dir . '/daily_' . $today . '.json';

        if (!file_exists($file)) {
            return ['date' => $today, 'unique' => 0, 'views' => 0];
        }

        $content = @file_get_contents($file);
        $data = json_decode($content, true);

        return [
            'date' => $today,
            'unique' => (int)($data['unique'] ?? 0),
            'views' => (int)($data['views'] ?? 0),
        ];
    }
}
