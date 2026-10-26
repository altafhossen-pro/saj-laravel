<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBlockedIp
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. NEVER block admin dashboard routes or login/register/logout
        if ($request->is('admin*') || $request->is('login*') || $request->is('logout*') || $request->is('register*')) {
            return $next($request);
        }

        // 3. Fast cached IP / CIDR block check
        $clientIp = $request->ip();
        if ($clientIp) {
            $blockedRule = \App\Models\Blacklist::findBlockedIp($clientIp);

            if ($blockedRule) {
                // If API or JSON request, return structured 403
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'error' => 'Access Restricted',
                        'message' => $blockedRule->custom_message ?: 'Your IP address has been restricted from accessing this service.',
                        'expires_at' => $blockedRule->expires_at ? $blockedRule->expires_at->toIso8601String() : 'permanent'
                    ], 403);
                }

                // Render beautiful custom blocked error view with dynamic admin message
                return response()->view('frontend.errors.blocked', [
                    'rule' => $blockedRule,
                    'clientIp' => $clientIp,
                    'customMessage' => $blockedRule->custom_message,
                    'expiresAt' => $blockedRule->expires_at,
                ], 403);
            }
        }

        return $next($request);
    }
}
