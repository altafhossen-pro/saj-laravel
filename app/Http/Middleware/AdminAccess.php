<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminAccess
{
    /**
     * Handle an incoming request.
     * Ensure the user has administrative staff privileges.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }
            return redirect()->guest(route('login'));
        }

        // Allow any user with administrative access (super_admin, non-customer roles, or any permissions)
        if ($user->hasAdminAccess()) {
            return $next($request);
        }

        // User is a retail/customer account only
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'Access Denied: Your account does not have administrative access.',
            ], 403);
        }

        abort(403, 'Access Denied: Your account does not have permission to access the administrative dashboard.');
    }
}
