<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\EmailLog;
use App\Mail\TestConfigurationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Exception;

class MailConfigService
{
    /**
     * Dynamically inject mail configuration into Laravel runtime.
     * Purges MailManager cache so new transport connects immediately.
     */
    public static function applyRuntimeConfig(?array $overrides = null): bool
    {
        try {
            $enabled = $overrides['mail_enabled'] ?? (bool) Setting::get('mail_enabled', 1);

            if (!$enabled) {
                config(['mail.default' => 'log']);
                if (app()->bound('mail.manager')) {
                    app('mail.manager')->forgetMailers();
                }
                return false;
            }

            $driver = $overrides['mail_mailer'] ?? Setting::get('mail_mailer', 'smtp');
            $fromAddress = $overrides['mail_from_address'] ?? Setting::get('mail_from_address', config('mail.from.address'));
            $fromName = $overrides['mail_from_name'] ?? Setting::get('mail_from_name', Setting::get('site_name', 'Ario Mart'));

            config([
                'mail.from.address' => $fromAddress,
                'mail.from.name' => $fromName,
            ]);

            if ($driver === 'sendmail') {
                $sendmailPath = $overrides['mail_sendmail_path'] ?? Setting::get('mail_sendmail_path', '/usr/sbin/sendmail -bs -i');
                config([
                    'mail.default' => 'sendmail',
                    'mail.mailers.sendmail.path' => $sendmailPath,
                ]);
            } elseif ($driver === 'log') {
                config([
                    'mail.default' => 'log',
                ]);
            } else {
                // SMTP Driver
                $host = $overrides['mail_host'] ?? Setting::get('mail_host', config('mail.mailers.smtp.host', '127.0.0.1'));
                $port = (int) ($overrides['mail_port'] ?? Setting::get('mail_port', config('mail.mailers.smtp.port', 587)));
                $enc = $overrides['mail_encryption'] ?? Setting::get('mail_encryption', 'tls');
                $username = $overrides['mail_username'] ?? Setting::get('mail_username', config('mail.mailers.smtp.username'));
                $password = $overrides['mail_password'] ?? Setting::get('mail_password', config('mail.mailers.smtp.password'));
                $timeout = (int) ($overrides['mail_timeout'] ?? Setting::get('mail_timeout', 15));
                $ignoreSsl = (bool) ($overrides['mail_ignore_ssl'] ?? Setting::get('mail_ignore_ssl', 0));

                if ($enc === 'none' || empty($enc)) {
                    $enc = null;
                }

                $smtpConfig = [
                    'transport' => 'smtp',
                    'host' => $host,
                    'port' => $port,
                    'encryption' => $enc,
                    'username' => $username,
                    'password' => $password,
                    'timeout' => $timeout,
                    'local_domain' => parse_url((string) config('app.url', 'http://localhost'), PHP_URL_HOST),
                ];

                if ($ignoreSsl) {
                    $smtpConfig['stream'] = [
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true,
                        ],
                    ];
                }

                config([
                    'mail.default' => 'smtp',
                    'mail.mailers.smtp' => array_merge(config('mail.mailers.smtp', []), $smtpConfig),
                ]);
            }

            // Invalidate cached mailer instances in Laravel so fresh settings are used
            if (app()->bound('mail.manager')) {
                app('mail.manager')->forgetMailers();
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('MailConfigService::applyRuntimeConfig failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if email sending is globally permitted in settings.
     */
    public static function isMailEnabled(): bool
    {
        return (bool) Setting::get('mail_enabled', 1);
    }

    /**
     * Low-level network socket test & reachability diagnostic.
     * Verifies DNS, TCP connection, latency, greeting banner, and SSL/TLS certificate compatibility.
     */
    public static function testReachability(string $host, int $port, ?string $encryption = null, int $timeout = 6): array
    {
        $start = microtime(true);
        $cleanHost = trim($host);

        // 1. DNS Resolution check
        $ip = @gethostbyname($cleanHost);
        if ($ip === $cleanHost && !filter_var($cleanHost, FILTER_VALIDATE_IP)) {
            return [
                'success' => false,
                'status' => 'dns_error',
                'latency_ms' => 0,
                'ip' => null,
                'banner' => null,
                'message' => "DNS Lookup failed: Could not resolve hostname '{$cleanHost}'. Please check the host address.",
            ];
        }

        // 2. Socket Connection Test
        $protocol = '';
        if (strtolower((string)$encryption) === 'ssl' || $port === 465) {
            $protocol = 'ssl://';
        }

        $errno = 0;
        $errstr = '';
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ]
        ]);

        $socket = @stream_socket_client(
            $protocol . $cleanHost . ':' . $port,
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        $latency = round((microtime(true) - $start) * 1000, 1);

        if (!$socket) {
            $diagMsg = "Connection timed out or refused on {$cleanHost}:{$port} ({$errstr} [Code: {$errno}]).";
            if ($port === 465 || $port === 25) {
                $diagMsg .= " (Note: Shared hosting / cPanel firewalls often block outgoing port 465/25. Consider using Port 587 with TLS or switching to cPanel Local Sendmail).";
            }

            return [
                'success' => false,
                'status' => 'connection_failed',
                'latency_ms' => $latency,
                'ip' => $ip,
                'banner' => null,
                'message' => $diagMsg,
            ];
        }

        // 3. Read initial greeting banner from mail server (e.g., "220 smtp.gmail.com ...")
        stream_set_timeout($socket, 3);
        $banner = @fgets($socket, 512);
        @fclose($socket);

        $bannerClean = trim((string)$banner);
        $warning = null;

        // Check for cPanel proxy interception
        if (!empty($bannerClean)) {
            if (str_contains(strtolower($cleanHost), 'gmail.com') && !str_contains(strtolower($bannerClean), 'google') && !str_contains(strtolower($bannerClean), 'gmail')) {
                $warning = "Warning: The server responded with banner '{$bannerClean}', which does not look like Google's server. Your hosting provider (cPanel) may be intercepting external SMTP traffic.";
            }
        }

        return [
            'success' => true,
            'status' => 'reachable',
            'latency_ms' => $latency,
            'ip' => $ip,
            'banner' => $bannerClean ?: 'Connected (No banner received)',
            'warning' => $warning,
            'message' => "Successfully connected to {$cleanHost}:{$port} in {$latency}ms! Mail server is reachable.",
        ];
    }

    /**
     * Dispatch an end-to-end Test Email and log outcome.
     */
    public static function sendTestEmail(string $recipientEmail, ?array $configOverrides = null): array
    {
        try {
            self::applyRuntimeConfig($configOverrides);

            $mailData = [
                'mailer' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host', 'localhost'),
                'port' => config('mail.mailers.smtp.port', 'local'),
                'from_email' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
                'sent_at' => now()->format('d M Y, h:i:s A'),
            ];

            Mail::to($recipientEmail)->send(new TestConfigurationMail($mailData));

            // Record in EmailLog
            EmailLog::record(
                $recipientEmail,
                'SMTP Configuration Test - ' . config('mail.from.name', 'Ario Mart'),
                'smtp_test',
                'sent',
                null,
                'Administrator',
                auth()->id(),
                null,
                'admin'
            );

            return [
                'success' => true,
                'message' => "Test email dispatched successfully to '{$recipientEmail}'! Please check inbox and spam folders.",
            ];
        } catch (\Throwable $e) {
            $err = $e->getMessage();
            Log::error("SMTP Test Dispatch Error: " . $err);

            EmailLog::record(
                $recipientEmail,
                'SMTP Configuration Test - ' . config('mail.from.name', 'Ario Mart'),
                'smtp_test',
                'failed',
                $err,
                'Administrator',
                auth()->id(),
                null,
                'admin'
            );

            return [
                'success' => false,
                'message' => "Failed to deliver test email: " . $err,
            ];
        }
    }
}
