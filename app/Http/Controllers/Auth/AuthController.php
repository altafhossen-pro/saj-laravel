<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            // Redirect based on role later if needed
            if(Auth::user()->hasRole('super_admin')) {
                return redirect()->intended('/admin/dashboard');
            }
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function sendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email|unique:users,email']);

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Otp::updateOrCreate(
            ['email' => $request->email],
            [
                'otp' => $code,
                'is_verified' => false,
                'expires_at' => now()->addMinutes(5)
            ]
        );

        try {
            \App\Services\MailConfigService::applyRuntimeConfig();
            Mail::to($request->email)->send(new OtpMail($code));

            \App\Models\EmailLog::record(
                $request->email,
                'Your Verification Code - ' . \App\Models\Setting::get('site_name', 'Ario Mart'),
                'otp_verification',
                'sent',
                null,
                null,
                null,
                null,
                'system'
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("OTP Email Dispatch Error: " . $e->getMessage());

            \App\Models\EmailLog::record(
                $request->email,
                'Your Verification Code - ' . \App\Models\Setting::get('site_name', 'Ario Mart'),
                'otp_verification',
                'failed',
                $e->getMessage(),
                null,
                null,
                null,
                'system'
            );

            return response()->json([
                'success' => false,
                'message' => 'Failed to deliver OTP email. Please verify your email address or try again later.'
            ], 500);
        }

        return response()->json(['success' => true, 'message' => 'OTP sent successfully']);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:6'
        ]);

        $otpRecord = Otp::where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otpRecord) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired OTP.'], 400);
        }

        $otpRecord->update(['is_verified' => true]);

        return response()->json(['success' => true, 'message' => 'OTP verified successfully']);
    }

    public function register(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:users,phone',
            'password' => 'required|min:6'
        ], [
            'phone.unique' => 'This phone number is already registered with another account.',
            'email.unique' => 'This email address is already registered.'
        ]);

        // Check if OTP was verified
        $otpRecord = Otp::where('email', $request->email)->where('is_verified', true)->first();

        if (!$otpRecord) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email address is not verified. Please verify your OTP.'
                ], 422);
            }
            return back()->withErrors(['email' => 'Email address not verified.']);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'user' // Default role
        ]);

        // Cleanup OTP
        $otpRecord->delete();

        Auth::login($user);

        // Meta CAPI CompleteRegistration Track
        try {
            \App\Services\FacebookCapiService::trackCompleteRegistration([
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ]);
        } catch (\Throwable $fbEx) {}

        // Send Welcome email if active
        try {
            if (\App\Services\MailConfigService::isMailEnabled() && \App\Models\Setting::get('email_tpl_welcome_user_active', 1)) {
                \App\Services\MailConfigService::applyRuntimeConfig();
                Mail::to($user->email)->send(new \App\Mail\WelcomeUserMail($user));
                \App\Models\EmailLog::record(
                    $user->email,
                    'Welcome to ' . \App\Models\Setting::get('site_name', 'Ario Mart') . '! Happy Shopping 🎉',
                    'welcome_user',
                    'sent',
                    null,
                    $user->name,
                    $user->id,
                    null,
                    'system'
                );
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Welcome Email Error: " . $e->getMessage());
            try {
                \App\Models\EmailLog::record(
                    $user->email,
                    'Welcome to ' . \App\Models\Setting::get('site_name', 'Ario Mart') . '! Happy Shopping 🎉',
                    'welcome_user',
                    'failed',
                    $e->getMessage(),
                    $user->name,
                    $user->id,
                    null,
                    'system'
                );
            } catch (\Throwable $logEx) {}
        }

        $redirectUrl = $user->hasRole('super_admin') ? url('/admin/dashboard') : url('/dashboard');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Registration completed successfully!',
                'redirect_url' => $redirectUrl
            ]);
        }

        if($user->hasRole('super_admin')) {
            return redirect()->intended('/admin/dashboard');
        }
        return redirect('/dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    // Google Socialite
    public function redirectToGoogle()
    {
        try {
            return Socialite::driver('google')->redirect();
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['email' => 'Something went wrong with Google Login. Please contact support.']);
        }
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            $user = User::updateOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName(),
                    'google_id' => $googleUser->getId(),
                    'role' => 'user',
                    'avatar' => $googleUser->getAvatar(),
                    'password' => Hash::make(uniqid()) // Random password for social login
                ]
            );

            Auth::login($user);

            if($user->hasRole('super_admin')) {
                return redirect()->intended('/admin/dashboard');
            }
            return redirect('/dashboard');
        } catch (\Exception $e) {
            return redirect('/login')->withErrors(['email' => 'Failed to login with Google.']);
        }
    }
}
