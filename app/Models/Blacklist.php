<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Blacklist extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope only active bans that haven't expired
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Check if a given client IP matches an IP or CIDR range (e.g., 192.168.1.5 or 192.168.1.0/24)
     */
    public static function ipMatches(string $clientIp, string $rule): bool
    {
        $clientIp = trim($clientIp);
        $rule = trim($rule);

        // Exact match or localhost loopback match (127.0.0.1 and ::1)
        if ($clientIp === $rule) {
            return true;
        }

        if (($clientIp === '127.0.0.1' || $clientIp === '::1') && ($rule === '127.0.0.1' || $rule === '::1')) {
            return true;
        }

        // Subnet / CIDR match (e.g. 103.14.25.0/24 or 10.0.0.0/16)
        if (str_contains($rule, '/')) {
            [$subnet, $bits] = explode('/', $rule, 2);
            $bits = (int)$bits;

            if (filter_var($clientIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) &&
                filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                
                $ipLong = ip2long($clientIp);
                $subnetLong = ip2long($subnet);
                $mask = -1 << (32 - $bits);
                $subnetLong &= $mask;

                return ($ipLong & $mask) === $subnetLong;
            }
        }

        return false;
    }

    /**
     * Find if client IP is currently blocked
     */
    public static function findBlockedIp(string $clientIp): ?self
    {
        // Cache active IP rules for 5 minutes (auto cleared on admin changes)
        $activeIpRules = \Illuminate\Support\Facades\Cache::remember('active_blacklist_ips', 300, function () {
            return self::active()->where('type', 'ip')->get();
        });

        foreach ($activeIpRules as $rule) {
            if (self::ipMatches($clientIp, $rule->value)) {
                return $rule;
            }
        }

        return null;
    }

    /**
     * Check if a phone number is currently blocked
     */
    public static function isPhoneBlocked(string $phone): bool
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (empty($cleanPhone)) return false;

        $activePhoneRules = \Illuminate\Support\Facades\Cache::remember('active_blacklist_phones', 300, function () {
            return self::active()->where('type', 'phone')->pluck('value')->toArray();
        });

        return in_array($cleanPhone, $activePhoneRules, true);
    }

    /**
     * Check if a given IP or Phone is blocked
     */
    public static function isBlocked(?string $ip = null, ?string $phone = null): bool
    {
        if ($ip && self::findBlockedIp($ip)) {
            return true;
        }

        if ($phone && self::isPhoneBlocked($phone)) {
            return true;
        }

        return false;
    }

    /**
     * Auto clear cache on model events
     */
    protected static function booted(): void
    {
        static::saved(function () {
            \Illuminate\Support\Facades\Cache::forget('active_blacklist_ips');
            \Illuminate\Support\Facades\Cache::forget('active_blacklist_phones');
        });

        static::deleted(function () {
            \Illuminate\Support\Facades\Cache::forget('active_blacklist_ips');
            \Illuminate\Support\Facades\Cache::forget('active_blacklist_phones');
        });
    }
}
