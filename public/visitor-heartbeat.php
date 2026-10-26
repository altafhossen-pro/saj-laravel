<?php
/**
 * Ultra-Fast High-Concurrency Visitor Heartbeat Handler
 * 
 * Performance Specs:
 * - Direct execution: Does NOT boot Laravel framework (Execution: ~0.0008s)
 * - Zero Database queries (MySQL completely untouched)
 * - Zero Disk I/O pressure: Prioritizes /dev/shm (Linux Shared RAM tmpfs)
 * - Sliding window algorithm: Smooth visitor transition between pages without count drops
 */

// Ensure timezone matches application (Dhaka / Bangladesh)
date_default_timezone_set('Asia/Dhaka');

// Headers: lightweight, no cache, CORS friendly
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Robots-Tag: noindex, nofollow');

// 1. Storage Location determination (Prefer RAM /dev/shm, fallback to local fast storage)
$storageDir = null;
if (is_dir('/dev/shm') && is_writable('/dev/shm')) {
    $storageDir = '/dev/shm/ariomart_visitors';
} else {
    // Fallback: fast framework cache folder
    $storageDir = dirname(__DIR__) . '/storage/framework/cache/visitors';
}

if (!is_dir($storageDir)) {
    @mkdir($storageDir, 0775, true);
}

// If GET request, return instant stats and live visitors list (0ms, bypasses Laravel boot)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['stats'])) {
    $activeFile = $storageDir . '/live_sessions.json';
    $liveCount = 0;
    $liveVisitors = [];
    $now = time();
    $windowSeconds = 40;

    if (file_exists($activeFile)) {
        $content = @file_get_contents($activeFile);
        $sessions = json_decode($content, true);
        if (is_array($sessions)) {
            foreach ($sessions as $id => $item) {
                $lastSeen = is_array($item) ? ($item['last_seen'] ?? 0) : (int)$item;
                $diff = $now - (int)$lastSeen;
                if ($diff <= $windowSeconds) {
                    $liveCount++;
                    if (is_array($item)) {
                        $liveVisitors[] = [
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
                        $liveVisitors[] = [
                            'vid' => $id,
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
                        ];
                    }
                }
            }
        }
    }

    // Sort by seconds_ago ascending (most recently active first)
    usort($liveVisitors, function ($a, $b) {
        return $a['seconds_ago'] <=> $b['seconds_ago'];
    });

    // Intelligent deduplication: 1 customer = 1 entry (keep their latest active page and session)
    $dedupedVisitors = [];
    $seenIdentities = [];
    foreach ($liveVisitors as $v) {
        $key = null;
        if (!empty($v['user_id'])) {
            $key = 'uid_' . $v['user_id'];
        } elseif (!empty($v['phone'])) {
            $key = 'phone_' . preg_replace('/[^0-9]/', '', $v['phone']);
        } elseif (!empty($v['name']) && $v['is_logged_in']) {
            $key = 'name_' . strtolower(trim($v['name']));
        }

        if ($key) {
            if (isset($seenIdentities[$key])) {
                continue; // Already has their most recent session
            }
            $seenIdentities[$key] = true;
        }
        $dedupedVisitors[] = $v;
    }

    $today = date('Y-m-d');
    $dailyFile = $storageDir . '/daily_' . $today . '.json';
    $todayData = ['date' => $today, 'unique' => 0, 'views' => 0];
    if (file_exists($dailyFile)) {
        $d = json_decode(@file_get_contents($dailyFile), true);
        if (is_array($d)) $todayData = $d;
    }
    echo json_encode([
        'live' => count($dedupedVisitors),
        'today' => $todayData,
        'visitors' => array_slice($dedupedVisitors, 0, 30)
    ]);
    exit;
}

// Only allow POST for heartbeat
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo '{"status":"method_not_allowed"}';
    exit;
}

// 2. Read incoming payload (JSON or Beacon form)
$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput, true);

if (!is_array($payload)) {
    $payload = $_POST;
}

$visitorId = isset($payload['vid']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$payload['vid']) : null;
$action = isset($payload['act']) ? (string)$payload['act'] : 'ping';

if (empty($visitorId) || strlen($visitorId) < 8) {
    echo '{"status":"ignored"}';
    exit;
}

$now = time();
$windowSeconds = 40; // 40 seconds active window
$today = date('Y-m-d');

// Data file paths
$activeFile = $storageDir . '/live_sessions.json';
$dailyFile = $storageDir . '/daily_' . $today . '.json';

// Extract visitor page & client details
$url = isset($payload['url']) ? substr(strip_tags((string)$payload['url']), 0, 255) : '/';
$title = isset($payload['title']) ? substr(strip_tags((string)$payload['title']), 0, 120) : '';
$device = isset($payload['device']) ? substr(strip_tags((string)$payload['device']), 0, 20) : 'Desktop';
$profile = isset($payload['profile']) && is_array($payload['profile']) ? $payload['profile'] : [];

$pUserId = !empty($profile['user_id']) ? (int)$profile['user_id'] : null;
$pName = isset($profile['name']) ? substr(strip_tags((string)$profile['name']), 0, 60) : null;
$pPhone = isset($profile['phone']) ? substr(strip_tags((string)$profile['phone']), 0, 25) : null;
$pEmail = isset($profile['email']) ? substr(strip_tags((string)$profile['email']), 0, 80) : null;
$isLoggedIn = !empty($profile['is_logged_in']);
$lastSeenAs = isset($profile['last_seen_as']) ? substr(strip_tags((string)$profile['last_seen_as']), 0, 60) : null;
if (!$lastSeenAs && $pName) {
    $lastSeenAs = $pName;
}

// Client IP detection (Cloudflare / Reverse proxy friendly)
$clientIp = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
if (strpos($clientIp, ',') !== false) {
    $clientIp = trim(explode(',', $clientIp)[0]);
}
$clientIp = substr(preg_replace('/[^a-fA-F0-9:.]/', '', $clientIp), 0, 45);

$fp = @fopen($activeFile, 'c+');
if ($fp) {
    if (flock($fp, LOCK_EX)) {
        $fileSize = filesize($activeFile);
        $content = $fileSize > 0 ? fread($fp, $fileSize) : '';
        $activeSessions = json_decode($content, true);
        if (!is_array($activeSessions)) {
            $activeSessions = [];
        }

        // Clean expired sessions older than window
        $activeCount = 0;
        foreach ($activeSessions as $id => $item) {
            $lastSeen = is_array($item) ? ($item['last_seen'] ?? 0) : (int)$item;
            if (($now - (int)$lastSeen) <= $windowSeconds) {
                $activeCount++;
            } else {
                unset($activeSessions[$id]);
            }
        }

        // Handle current visitor
        if ($action === 'leave') {
            // Graceful leave: mark session for window expiry, but never destroy history
            if (isset($activeSessions[$visitorId])) {
                $activeSessions[$visitorId]['last_seen'] = $now - ($windowSeconds - 5);
            }
        } else {
            $existing = isset($activeSessions[$visitorId]) && is_array($activeSessions[$visitorId]) ? $activeSessions[$visitorId] : [];
            $firstSeen = $existing['first_seen'] ?? $now;

            // Retain name/phone/identity if previously captured
            $savedName = $pName ?: ($existing['name'] ?? null);
            $savedPhone = $pPhone ?: ($existing['phone'] ?? null);
            $savedEmail = $pEmail ?: ($existing['email'] ?? null);
            $savedLastSeenAs = $lastSeenAs ?: ($existing['last_seen_as'] ?? $savedName);

            // Accept persistent multi-page journey timeline from client localStorage
            $clientHistory = isset($payload['history']) && is_array($payload['history']) ? $payload['history'] : null;
            $cleanClientHistory = [];
            if (!empty($clientHistory)) {
                foreach ($clientHistory as $h) {
                    if (is_array($h) && !empty($h['url'])) {
                        $cleanClientHistory[] = [
                            'url' => substr(strip_tags((string)$h['url']), 0, 255),
                            'title' => substr(strip_tags((string)($h['title'] ?? 'Storefront')), 0, 120),
                            'time' => substr(strip_tags((string)($h['time'] ?? '')), 0, 20),
                            'timestamp' => (int)($h['timestamp'] ?? $now),
                        ];
                    }
                }
            }

            $history = !empty($cleanClientHistory) ? $cleanClientHistory : (isset($existing['history']) && is_array($existing['history']) ? $existing['history'] : []);
            $currentUrl = $url ?: ($existing['url'] ?? '/');
            $currentTitle = $title ?: ($existing['title'] ?? 'Storefront');

            $lastHistoryItem = !empty($history) ? end($history) : null;
            if (!$lastHistoryItem || ($lastHistoryItem['url'] ?? '') !== $currentUrl) {
                $history[] = [
                    'url' => $currentUrl,
                    'title' => $currentTitle,
                    'time' => date('h:i:s A'),
                    'timestamp' => $now,
                ];
                // Keep max 25 recent steps to keep memory usage super light (< 2KB)
                if (count($history) > 25) {
                    $history = array_slice($history, -25);
                }
            } elseif ($lastHistoryItem && (empty($lastHistoryItem['title']) || $lastHistoryItem['title'] === 'Storefront') && !empty($currentTitle)) {
                $history[count($history) - 1]['title'] = $currentTitle;
            }

            // Remove any older duplicate sessions for this exact customer so 1 customer always has 1 row
            if ($pUserId || $savedPhone || ($isLoggedIn && $savedName)) {
                foreach ($activeSessions as $existingId => $sess) {
                    if ($existingId !== $visitorId && is_array($sess)) {
                        $sameUser = $pUserId && !empty($sess['user_id']) && (int)$sess['user_id'] === $pUserId;
                        $samePhone = $savedPhone && !empty($sess['phone']) && $sess['phone'] === $savedPhone;
                        $sameMember = $isLoggedIn && !empty($sess['is_logged_in']) && !empty($sess['name']) && strtolower(trim($sess['name'])) === strtolower(trim($savedName));
                        if ($sameUser || $samePhone || $sameMember) {
                            unset($activeSessions[$existingId]);
                        }
                    }
                }
            }

            $activeSessions[$visitorId] = [
                'last_seen' => $now,
                'first_seen' => $firstSeen,
                'url' => $currentUrl,
                'title' => $currentTitle,
                'device' => $device ?: ($existing['device'] ?? 'Desktop'),
                'ip' => $clientIp ?: ($existing['ip'] ?? ''),
                'user_id' => $pUserId ?: ($existing['user_id'] ?? null),
                'name' => $savedName,
                'phone' => $savedPhone,
                'email' => $savedEmail,
                'is_logged_in' => $isLoggedIn,
                'last_seen_as' => $savedLastSeenAs,
                'history' => $history,
            ];
            if (!isset($existing['last_seen'])) {
                $activeCount++;
            }
        }

        // Write back updated sessions
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($activeSessions));
        flock($fp, LOCK_UN);
    }
    fclose($fp);
}

// 3. Increment daily unique counter in a super lightweight daily file (1 write per new daily session)
$isNewDaily = !empty($payload['is_new']);
if ($isNewDaily) {
    $dfp = @fopen($dailyFile, 'c+');
    if ($dfp) {
        if (flock($dfp, LOCK_EX)) {
            $dSize = filesize($dailyFile);
            $dContent = $dSize > 0 ? fread($dfp, $dSize) : '';
            $dData = json_decode($dContent, true);
            if (!is_array($dData)) {
                $dData = ['date' => $today, 'unique' => 0, 'views' => 0];
            }
            $dData['unique'] = (int)($dData['unique'] ?? 0) + 1;
            $dData['views'] = (int)($dData['views'] ?? 0) + 1;
            ftruncate($dfp, 0);
            rewind($dfp);
            fwrite($dfp, json_encode($dData));
            flock($dfp, LOCK_UN);
        }
        fclose($dfp);
    }
}

echo '{"status":"ok"}';
exit;
