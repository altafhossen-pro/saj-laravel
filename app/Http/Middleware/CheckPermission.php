<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission  Single permission or pipe-separated list of permissions (OR logic: "manage_orders|create_orders")
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = Auth::user();

        // 1. Unauthenticated check
        if (!$user) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }
            return redirect()->guest(route('login'));
        }

        // 2. Super admin always bypasses all permission restrictions
        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        // 3. Support pipe '|' for OR checks (has ANY of the listed permissions/roles)
        $requiredItems = explode('|', $permission);
        $authorized = false;

        foreach ($requiredItems as $item) {
            $item = trim($item);
            if (!empty($item) && ($user->can($item) || $user->hasRole($item))) {
                $authorized = true;
                break;
            }
        }

        // 4. Unauthorized response
        if (!$authorized) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => "Access Denied: You do not have permission to perform this action.",
                ], 403);
            }

            if ($request->is('admin/*') || ($user && $user->hasAdminAccess())) {
                return response()->view('admin.errors.unauthorized', [
                    'permission' => $permission,
                ], 403);
            }

            abort(403);
        }

        return $next($request);
    }
}
