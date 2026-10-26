<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CartSession;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CartAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        // 1. Auto-tag stale active carts as abandoned if older than 2 hours
        CartSession::where('status', 'active')
            ->where('last_activity_at', '<', Carbon::now()->subHours(2))
            ->update(['status' => 'abandoned']);

        $statusFilter = $request->input('status', 'all');
        $search = trim($request->input('search', ''));

        $query = CartSession::with(['user', 'order'])->latest('last_activity_at');

        if ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('customer_phone', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Only show carts that have at least 1 item
        $query->where('total_items', '>', 0);

        $carts = $query->paginate(20)->withQueryString();

        // 2. Metrics summary
        $stats = [
            'total_carts' => CartSession::where('total_items', '>', 0)->count(),
            'active_carts' => CartSession::where('status', 'active')->where('total_items', '>', 0)->count(),
            'abandoned_carts' => CartSession::where('status', 'abandoned')->where('total_items', '>', 0)->count(),
            'abandoned_value' => (float) CartSession::where('status', 'abandoned')->where('total_items', '>', 0)->sum('total_amount'),
            'converted_carts' => CartSession::where('status', 'converted')->where('total_items', '>', 0)->count(),
            'converted_value' => (float) CartSession::where('status', 'converted')->where('total_items', '>', 0)->sum('total_amount'),
        ];

        $totalIntent = $stats['abandoned_carts'] + $stats['converted_carts'];
        $stats['conversion_rate'] = $totalIntent > 0 ? round(($stats['converted_carts'] / $totalIntent) * 100, 1) : 0;

        if ($request->has('poll') || $request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'stats' => $stats,
                'carts' => $carts->map(fn($c) => $c->toApiArray())->values(),
                'server_time' => now()->toIso8601String(),
            ]);
        }

        $initialCartsJson = $carts->map(fn($c) => $c->toApiArray())->values();

        return view('admin.carts.index', compact('carts', 'initialCartsJson', 'stats', 'statusFilter', 'search'));
    }

    public function destroy(CartSession $cart)
    {
        $cart->delete();

        return redirect()->back()->with('success', 'Cart session removed successfully.');
    }
}
