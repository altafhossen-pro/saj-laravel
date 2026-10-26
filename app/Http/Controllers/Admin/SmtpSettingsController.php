<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\EmailLog;
use App\Services\MailConfigService;
use Illuminate\Http\Request;
use Exception;

class SmtpSettingsController extends Controller
{
    /**
     * Display Email & SMTP Server Settings page.
     */
    public function index()
    {
        $settings = [
            'mail_enabled'        => (bool) Setting::get('mail_enabled', 1),
            'mail_mailer'         => Setting::get('mail_mailer', 'smtp'),
            'mail_sendmail_path'  => Setting::get('mail_sendmail_path', '/usr/sbin/sendmail -bs -i'),
            'mail_host'           => Setting::get('mail_host', config('mail.mailers.smtp.host', 'smtp.gmail.com')),
            'mail_port'           => (int) Setting::get('mail_port', config('mail.mailers.smtp.port', 587)),
            'mail_encryption'     => Setting::get('mail_encryption', 'tls'),
            'mail_username'       => Setting::get('mail_username', config('mail.mailers.smtp.username', '')),
            'mail_password'       => Setting::get('mail_password', config('mail.mailers.smtp.password', '')),
            'mail_timeout'        => (int) Setting::get('mail_timeout', 15),
            'mail_ignore_ssl'     => (bool) Setting::get('mail_ignore_ssl', 0),
            'mail_from_address'   => Setting::get('mail_from_address', config('mail.from.address', 'support@sajbd.com')),
            'mail_from_name'      => Setting::get('mail_from_name', Setting::get('site_name', 'SAJ')),
        ];

        // Diagnostic logs
        $totalSent = EmailLog::where('status', 'sent')->count();
        $totalFailed = EmailLog::where('status', 'failed')->count();
        $recentLogs = EmailLog::latest()->take(6)->get();

        return view('admin.settings.smtp', compact('settings', 'totalSent', 'totalFailed', 'recentLogs'));
    }

    /**
     * Save updated Email & SMTP configuration.
     */
    public function update(Request $request)
    {
        $request->validate([
            'mail_mailer'       => 'required|in:smtp,sendmail,log',
            'mail_from_address' => 'required|email|max:255',
            'mail_from_name'    => 'required|string|max:100',
            'mail_host'         => 'nullable|string|max:255',
            'mail_port'         => 'nullable|integer|between:1,65535',
            'mail_encryption'   => 'nullable|in:tls,ssl,none',
            'mail_username'     => 'nullable|string|max:255',
            'mail_password'     => 'nullable|string|max:255',
            'mail_timeout'      => 'nullable|integer|between:1,120',
        ]);

        $enabled = $request->boolean('mail_enabled');
        Setting::set('mail_enabled', $enabled ? '1' : '0', 'boolean', 'email_smtp');
        Setting::set('mail_mailer', $request->mail_mailer, 'string', 'email_smtp');
        Setting::set('mail_from_address', trim($request->mail_from_address), 'string', 'email_smtp');
        Setting::set('mail_from_name', trim($request->mail_from_name), 'string', 'email_smtp');

        if ($request->filled('mail_sendmail_path')) {
            Setting::set('mail_sendmail_path', trim($request->mail_sendmail_path), 'string', 'email_smtp');
        }

        Setting::set('mail_host', trim((string) $request->mail_host), 'string', 'email_smtp');
        Setting::set('mail_port', (string) ($request->mail_port ?? 587), 'integer', 'email_smtp');
        Setting::set('mail_encryption', $request->mail_encryption ?: 'tls', 'string', 'email_smtp');
        Setting::set('mail_username', trim((string) $request->mail_username), 'string', 'email_smtp');

        // Only update password if provided
        if ($request->filled('mail_password')) {
            Setting::set('mail_password', $request->mail_password, 'string', 'email_smtp');
        }

        Setting::set('mail_timeout', (string) ($request->mail_timeout ?? 15), 'integer', 'email_smtp');
        Setting::set('mail_ignore_ssl', $request->boolean('mail_ignore_ssl') ? '1' : '0', 'boolean', 'email_smtp');

        // Dynamically apply immediately
        MailConfigService::applyRuntimeConfig();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Email & SMTP server settings updated and applied successfully!'
            ]);
        }

        return redirect()->back()->with('success', 'Email & SMTP server settings updated and applied successfully!');
    }

    /**
     * Low-level Reachability & Socket Diagnostic test without sending email.
     */
    public function testReachability(Request $request)
    {
        $host = trim((string)$request->input('mail_host', Setting::get('mail_host', 'smtp.gmail.com')));
        $port = (int) $request->input('mail_port', Setting::get('mail_port', 587));
        $encryption = $request->input('mail_encryption', Setting::get('mail_encryption', 'tls'));
        $timeout = (int) $request->input('mail_timeout', 6);

        if (empty($host)) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a valid SMTP Host to test reachability.'
            ], 422);
        }

        $result = MailConfigService::testReachability($host, $port, $encryption, $timeout);
        return response()->json($result);
    }

    /**
     * Send end-to-end Test Email to verify delivery.
     */
    public function sendTest(Request $request)
    {
        $request->validate([
            'test_email' => 'required|email'
        ]);

        $recipientEmail = trim($request->test_email);

        // Optionally test draft configs passed from client
        $overrides = null;
        if ($request->has('mail_mailer')) {
            $overrides = [
                'mail_enabled'      => true,
                'mail_mailer'       => $request->mail_mailer,
                'mail_host'         => $request->mail_host,
                'mail_port'         => $request->mail_port,
                'mail_encryption'   => $request->mail_encryption,
                'mail_username'     => $request->mail_username,
                'mail_password'     => $request->mail_password ?: Setting::get('mail_password'),
                'mail_from_address' => $request->mail_from_address,
                'mail_from_name'    => $request->mail_from_name,
                'mail_ignore_ssl'   => $request->boolean('mail_ignore_ssl'),
            ];
        }

        $result = MailConfigService::sendTestEmail($recipientEmail, $overrides);

        return response()->json($result, $result['success'] ? 200 : 500);
    }
}
