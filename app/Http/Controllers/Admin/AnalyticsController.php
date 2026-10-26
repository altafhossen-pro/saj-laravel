<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SearchLog;
use App\Models\Product\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Search Analytics Page with Time Range & Deep Insights
     */
    public function search(Request $request)
    {
        // 1. Period filter: all, today, week, month, year
        $period = $request->input('period', 'all');

        $baseDateFilter = function ($q) use ($period) {
            if ($period === 'today') {
                $q->whereDate('created_at', today());
            } elseif ($period === 'week') {
                $q->where('created_at', '>=', now()->startOfWeek());
            } elseif ($period === 'month') {
                $q->where('created_at', '>=', now()->startOfMonth());
            } elseif ($period === 'year') {
                $q->where('created_at', '>=', now()->startOfYear());
            }
        };

        // 2. High-Performance Single-Query Summary Aggregations (Zero multiple table scans)
        $summary = DB::table('search_logs')
            ->when($period === 'today', fn($q) => $q->whereDate('created_at', today()))
            ->when($period === 'week', fn($q) => $q->where('created_at', '>=', now()->startOfWeek()))
            ->when($period === 'month', fn($q) => $q->where('created_at', '>=', now()->startOfMonth()))
            ->when($period === 'year', fn($q) => $q->where('created_at', '>=', now()->startOfYear()))
            ->selectRaw('
                COUNT(*) as total_period,
                SUM(CASE WHEN source = "suggestion" THEN 1 ELSE 0 END) as total_suggestion,
                SUM(CASE WHEN source = "direct" THEN 1 ELSE 0 END) as total_direct,
                SUM(CASE WHEN source = "no_result" THEN 1 ELSE 0 END) as total_no_result
            ')
            ->first();

        $totalSearches = $summary->total_period ?? 0;
        $suggestionClicks = $summary->total_suggestion ?? 0;
        $directSearches = $summary->total_direct ?? 0;
        $noResultCount = $summary->total_no_result ?? 0;

        // Fast cached/indexed today count
        $todaySearches = SearchLog::whereDate('created_at', today())->count();

        // 3. Top Searched Keywords (Filtered by period, indexed)
        $keywordQuery = SearchLog::select('query', DB::raw('count(*) as count'), DB::raw('max(created_at) as last_searched'))
            ->groupBy('query');
        $baseDateFilter($keywordQuery);
        $topKeywords = $keywordQuery->orderByDesc('count')->take(10)->get();

        // 4. Top Products Clicked via Suggestions (Indexed via idx_search_product_created)
        $sugProdQuery = SearchLog::where('source', 'suggestion')
            ->whereNotNull('clicked_product_id')
            ->select('clicked_product_id', DB::raw('count(*) as clicks'))
            ->groupBy('clicked_product_id')
            ->with(['clickedProduct' => function ($q) {
                $q->select('id', 'title', 'slug', 'featured_image', 'base_price', 'views_count');
            }]);
        $baseDateFilter($sugProdQuery);
        $topSuggestionProducts = $sugProdQuery->orderByDesc('clicks')->take(8)->get();

        // 5. Top Viewed Products in Store (Covered by composite index idx_pviews_date_count)
        if (in_array($period, ['today', 'week', 'month', 'year'])) {
            $viewDateFilter = match ($period) {
                'today' => today()->toDateString(),
                'week' => now()->startOfWeek()->toDateString(),
                'month' => now()->startOfMonth()->toDateString(),
                'year' => now()->startOfYear()->toDateString(),
            };
            $topViewedProducts = \App\Models\ProductViewStat::where('view_date', '>=', $viewDateFilter)
                ->select('product_id', DB::raw('SUM(views_count) as period_views'))
                ->groupBy('product_id')
                ->with(['product' => function ($q) {
                    $q->select('id', 'title', 'slug', 'featured_image', 'base_price', 'views_count');
                }])
                ->orderByDesc('period_views')
                ->take(8)
                ->get()
                ->map(function ($row) {
                    if ($row->product) {
                        $row->product->current_period_views = $row->period_views;
                        return $row->product;
                    }
                    return null;
                })
                ->filter();
            
            if ($topViewedProducts->isEmpty()) {
                $topViewedProducts = Product::where('is_active', true)
                    ->orderByDesc('views_count')
                    ->take(8)
                    ->get(['id', 'title', 'slug', 'featured_image', 'base_price', 'views_count']);
            }
        } else {
            $topViewedProducts = Product::where('is_active', true)
                ->orderByDesc('views_count')
                ->take(8)
                ->get(['id', 'title', 'slug', 'featured_image', 'base_price', 'views_count']);
        }

        // 6. Top Customers (Logged-in users searching most, covered by idx_search_user_created)
        $topCustQuery = SearchLog::whereNotNull('user_id')
            ->select('user_id', DB::raw('count(*) as search_count'), DB::raw('max(created_at) as last_search'))
            ->groupBy('user_id')
            ->with(['user' => function ($q) {
                $q->select('id', 'name', 'phone', 'email', 'avatar');
            }]);
        $baseDateFilter($topCustQuery);
        $topCustomers = $topCustQuery->orderByDesc('search_count')->take(5)->get();

        // 7. Top IP Addresses (Most active IP visitors, covered by idx_search_ip_created)
        $topIpQuery = SearchLog::select('ip_address', DB::raw('count(*) as ip_count'), DB::raw('max(created_at) as last_seen'))
            ->whereNotNull('ip_address')
            ->groupBy('ip_address');
        $baseDateFilter($topIpQuery);
        $topIps = $topIpQuery->orderByDesc('ip_count')->take(5)->get();

        // 8. Paginated Logs Table with strict column projection
        $query = SearchLog::select('id', 'query', 'source', 'clicked_product_id', 'user_id', 'ip_address', 'created_at')
            ->with([
                'user:id,name,phone,email', 
                'clickedProduct:id,title,slug,featured_image'
            ]);
        $baseDateFilter($query);

        if ($request->filled('q')) {
            $query->where('query', 'like', $request->q . '%'); // Prefix search utilizes B-Tree indexes!
        }

        if ($request->filled('source') && in_array($request->source, ['direct', 'suggestion', 'no_result', 'live_search'])) {
            $query->where('source', $request->source);
        }

        $logs = $query->latest('id')->paginate(15)->withQueryString();

        return view('admin.analytics.search', compact(
            'period',
            'totalSearches',
            'todaySearches',
            'suggestionClicks',
            'directSearches',
            'noResultCount',
            'topKeywords',
            'topSuggestionProducts',
            'topViewedProducts',
            'topCustomers',
            'topIps',
            'logs'
        ));
    }

    /**
     * Sales Analytics Page
     */
    public function sales(Request $request)
    {
        // 1. Overall Quick Summary KPI (Single pass, exclude cancelled/returned)
        $kpi = DB::table('orders')
            ->whereNull('deleted_at')
            ->whereNotIn('status', ['cancelled', 'returned'])
            ->selectRaw("
                COUNT(*) as total_orders,
                COALESCE(SUM(total), 0) as total_revenue,
                COALESCE(AVG(total), 0) as avg_order_value,
                COUNT(CASE WHEN created_at >= ? THEN 1 END) as today_orders,
                COALESCE(SUM(CASE WHEN created_at >= ? THEN total ELSE 0 END), 0) as today_revenue,
                COUNT(CASE WHEN created_at >= ? THEN 1 END) as month_orders,
                COALESCE(SUM(CASE WHEN created_at >= ? THEN total ELSE 0 END), 0) as month_revenue,
                COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_orders,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
                COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_orders,
                COUNT(CASE WHEN status = 'shipped' THEN 1 END) as shipped_orders
            ", [
                Carbon::today()->startOfDay(),
                Carbon::today()->startOfDay(),
                Carbon::now()->startOfMonth(),
                Carbon::now()->startOfMonth()
            ])
            ->first();

        // Cancellation & Return stats (for fulfillment health KPI)
        $healthStats = DB::table('orders')
            ->whereNull('deleted_at')
            ->selectRaw("
                COUNT(*) as all_placed,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_count,
                COUNT(CASE WHEN status = 'returned' THEN 1 END) as returned_count,
                COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_count
            ")
            ->first();

        $deliverySuccessRate = $healthStats->all_placed > 0 
            ? round(($healthStats->delivered_count / $healthStats->all_placed) * 100, 1) 
            : 100;

        // Current Year for default annual view
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->format('Y-m');

        return view('admin.analytics.sales', compact(
            'kpi',
            'healthStats',
            'deliverySuccessRate',
            'currentYear',
            'currentMonth'
        ));
    }

    /**
     * Dedicated Per-Card Fast AJAX API for Sales Analytics
     * Allows individual filters without refreshing the whole page or executing unwanted queries!
     */
    public function salesData(Request $request)
    {
        $card = $request->input('card');

        switch ($card) {
            // ------------------------------------------------------------------
            // 1. Annual Monthly Sales Breakdown (12 Months of chosen year)
            // ------------------------------------------------------------------
            case 'annual_trend':
                $year = (int)$request->input('year', Carbon::now()->year);

                $monthlyData = DB::table('orders')
                    ->whereNull('deleted_at')
                    ->whereNotIn('status', ['cancelled', 'returned'])
                    ->whereYear('created_at', $year)
                    ->selectRaw("
                        MONTH(created_at) as m,
                        COUNT(*) as orders_count,
                        COALESCE(SUM(total), 0) as revenue
                    ")
                    ->groupBy(DB::raw('MONTH(created_at)'))
                    ->get()
                    ->keyBy('m');

                $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                $revenues = [];
                $orders = [];

                for ($i = 1; $i <= 12; $i++) {
                    $row = $monthlyData->get($i);
                    $revenues[] = $row ? round((float)$row->revenue, 2) : 0;
                    $orders[] = $row ? (int)$row->orders_count : 0;
                }

                return response()->json([
                    'labels' => $labels,
                    'revenues' => $revenues,
                    'orders' => $orders,
                    'total_year_revenue' => array_sum($revenues),
                    'total_year_orders' => array_sum($orders),
                ]);

            // ------------------------------------------------------------------
            // 2. Daily Sales Breakdown of a Month (Day 1 to 28/30/31)
            // ------------------------------------------------------------------
            case 'monthly_daily':
                $monthInput = $request->input('month', Carbon::now()->format('Y-m'));
                try {
                    $carbonMonth = Carbon::createFromFormat('Y-m', $monthInput)->startOfMonth();
                } catch (\Exception $e) {
                    $carbonMonth = Carbon::now()->startOfMonth();
                }

                $daysInMonth = $carbonMonth->daysInMonth;
                $start = $carbonMonth->copy()->startOfMonth();
                $end = $carbonMonth->copy()->endOfMonth();

                $dailyData = DB::table('orders')
                    ->whereNull('deleted_at')
                    ->whereNotIn('status', ['cancelled', 'returned'])
                    ->whereBetween('created_at', [$start, $end])
                    ->selectRaw("
                        DAY(created_at) as d,
                        COUNT(*) as orders_count,
                        COALESCE(SUM(total), 0) as revenue
                    ")
                    ->groupBy(DB::raw('DAY(created_at)'))
                    ->get()
                    ->keyBy('d');

                $labels = [];
                $revenues = [];
                $orders = [];

                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $labels[] = sprintf('%02d %s', $d, $carbonMonth->format('M'));
                    $row = $dailyData->get($d);
                    $revenues[] = $row ? round((float)$row->revenue, 2) : 0;
                    $orders[] = $row ? (int)$row->orders_count : 0;
                }

                return response()->json([
                    'labels' => $labels,
                    'revenues' => $revenues,
                    'orders' => $orders,
                    'month_total_revenue' => array_sum($revenues),
                    'month_total_orders' => array_sum($orders),
                ]);

            // ------------------------------------------------------------------
            // 3. Hourly Sales Distribution (24 Hours)
            // ------------------------------------------------------------------
            case 'peak_hours':
                $filter = $request->input('filter', 'month'); // today, week, month, year, all

                $query = DB::table('orders')
                    ->whereNull('deleted_at')
                    ->whereNotIn('status', ['cancelled', 'returned']);

                $this->applyTimeFilter($query, $filter);

                $hourlyData = $query->selectRaw("
                        HOUR(created_at) as h,
                        COUNT(*) as orders_count,
                        COALESCE(SUM(total), 0) as revenue
                    ")
                    ->groupBy(DB::raw('HOUR(created_at)'))
                    ->get()
                    ->keyBy('h');

                $labels = [];
                $orders = [];
                $revenues = [];

                for ($h = 0; $h < 24; $h++) {
                    $labels[] = sprintf('%02d:00', $h);
                    $row = $hourlyData->get($h);
                    $orders[] = $row ? (int)$row->orders_count : 0;
                    $revenues[] = $row ? round((float)$row->revenue, 2) : 0;
                }

                return response()->json([
                    'labels' => $labels,
                    'orders' => $orders,
                    'revenues' => $revenues
                ]);

            // ------------------------------------------------------------------
            // 4. Day of Week Heatmap / Distribution (Sun - Sat)
            // ------------------------------------------------------------------
            case 'weekdays':
                $filter = $request->input('filter', 'month');

                $query = DB::table('orders')
                    ->whereNull('deleted_at')
                    ->whereNotIn('status', ['cancelled', 'returned']);

                $this->applyTimeFilter($query, $filter);

                // MySQL DAYOFWEEK(): 1 = Sunday, 2 = Monday, ..., 7 = Saturday
                $dayData = $query->selectRaw("
                        DAYOFWEEK(created_at) as dow,
                        COUNT(*) as orders_count,
                        COALESCE(SUM(total), 0) as revenue
                    ")
                    ->groupBy(DB::raw('DAYOFWEEK(created_at)'))
                    ->get()
                    ->keyBy('dow');

                $dayNames = [
                    1 => 'Sunday',
                    2 => 'Monday',
                    3 => 'Tuesday',
                    4 => 'Wednesday',
                    5 => 'Thursday',
                    6 => 'Friday',
                    7 => 'Saturday'
                ];

                $labels = [];
                $orders = [];
                $revenues = [];

                foreach ($dayNames as $dowNum => $name) {
                    $labels[] = $name;
                    $row = $dayData->get($dowNum);
                    $orders[] = $row ? (int)$row->orders_count : 0;
                    $revenues[] = $row ? round((float)$row->revenue, 2) : 0;
                }

                return response()->json([
                    'labels' => $labels,
                    'orders' => $orders,
                    'revenues' => $revenues
                ]);

            // ------------------------------------------------------------------
            // 5. Top Categories by Sales Revenue & Units Sold
            // ------------------------------------------------------------------
            case 'top_categories':
                $filter = $request->input('filter', 'month');

                $query = DB::table('order_items')
                    ->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->join('products', 'order_items.product_id', '=', 'products.id')
                    ->join('categories', 'products.category_id', '=', 'categories.id')
                    ->whereNull('orders.deleted_at')
                    ->whereNotIn('orders.status', ['cancelled', 'returned']);

                $this->applyTimeFilter($query, $filter, 'orders.created_at');

                $categories = $query->selectRaw("
                        categories.id,
                        categories.name,
                        categories.image,
                        SUM(order_items.quantity) as total_qty,
                        SUM(order_items.subtotal) as total_sales
                    ")
                    ->groupBy('categories.id', 'categories.name', 'categories.image')
                    ->orderByDesc('total_sales')
                    ->take(7)
                    ->get();

                $totalPeriodSales = $categories->sum('total_sales');

                $categories = $categories->map(function ($cat) use ($totalPeriodSales) {
                    $cat->percentage = $totalPeriodSales > 0 ? round(($cat->total_sales / $totalPeriodSales) * 100, 1) : 0;
                    $cat->total_sales_formatted = number_format($cat->total_sales, 2);
                    return $cat;
                });

                return response()->json([
                    'categories' => $categories,
                    'total_period_sales' => $totalPeriodSales,
                    'labels' => $categories->pluck('name'),
                    'revenues' => $categories->pluck('total_sales')
                ]);

            // ------------------------------------------------------------------
            // 6. Top Selling Products & Fast Moving Items
            // ------------------------------------------------------------------
            case 'top_products':
                $filter = $request->input('filter', 'month');

                $query = DB::table('order_items')
                    ->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->join('products', 'order_items.product_id', '=', 'products.id')
                    ->whereNull('orders.deleted_at')
                    ->whereNotIn('orders.status', ['cancelled', 'returned']);

                $this->applyTimeFilter($query, $filter, 'orders.created_at');

                $products = $query->selectRaw("
                        products.id,
                        products.title,
                        products.slug,
                        products.featured_image,
                        products.base_price,
                        SUM(order_items.quantity) as units_sold,
                        SUM(order_items.subtotal) as total_revenue
                    ")
                    ->groupBy('products.id', 'products.title', 'products.slug', 'products.featured_image', 'products.base_price')
                    ->orderByDesc('units_sold')
                    ->take(8)
                    ->get();

                return response()->json(['products' => $products]);

            // ------------------------------------------------------------------
            // 7. Slow Moving Products (High Stock or Live, but zero or least sales)
            // ------------------------------------------------------------------
            case 'slow_moving':
                $days = (int)$request->input('days', 30);
                $cutoff = Carbon::now()->subDays($days);

                // Get product IDs that had orders in this period
                $soldProductIds = DB::table('order_items')
                    ->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->whereNull('orders.deleted_at')
                    ->whereNotIn('orders.status', ['cancelled', 'returned'])
                    ->where('orders.created_at', '>=', $cutoff)
                    ->pluck('order_items.product_id')
                    ->filter()
                    ->unique();

                $slowProducts = DB::table('products')
                    ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                    ->where('products.is_active', true)
                    ->whereNotIn('products.id', $soldProductIds)
                    ->select('products.id', 'products.title', 'products.slug', 'products.featured_image', 'products.base_price', 'categories.name as category_name', 'products.created_at')
                    ->orderBy('products.created_at', 'asc')
                    ->take(8)
                    ->get();

                return response()->json(['products' => $slowProducts]);

            // ------------------------------------------------------------------
            // 8. Top Purchasing Customers Leaderboard (VIP Spenders)
            // ------------------------------------------------------------------
            case 'top_customers':
                $filter = $request->input('filter', 'month'); // week, month, year, all

                $query = DB::table('orders')
                    ->whereNull('orders.deleted_at')
                    ->whereNotIn('orders.status', ['cancelled', 'returned']);

                $this->applyTimeFilter($query, $filter, 'orders.created_at');

                // Group by either user_id or phone number to account for guest checkout orders
                $customers = $query->leftJoin('users', 'orders.user_id', '=', 'users.id')
                    ->selectRaw("
                        orders.user_id,
                        COALESCE(users.name, JSON_UNQUOTE(JSON_EXTRACT(orders.shipping_address, '$.first_name')), 'Guest Buyer') as customer_name,
                        COALESCE(users.phone, JSON_UNQUOTE(JSON_EXTRACT(orders.shipping_address, '$.phone')), 'N/A') as customer_phone,
                        COUNT(orders.id) as order_count,
                        SUM(orders.total) as total_spent,
                        MAX(orders.created_at) as last_order_at
                    ")
                    ->groupBy('orders.user_id', 'customer_name', 'customer_phone')
                    ->orderByDesc('total_spent')
                    ->take(8)
                    ->get();

                return response()->json(['customers' => $customers]);

            // ------------------------------------------------------------------
            // 9. Payment Methods Breakdown (COD vs Digital)
            // ------------------------------------------------------------------
            case 'payment_methods':
                $filter = $request->input('filter', 'month');

                $query = DB::table('orders')
                    ->whereNull('orders.deleted_at')
                    ->whereNotIn('orders.status', ['cancelled', 'returned']);

                $this->applyTimeFilter($query, $filter, 'orders.created_at');

                $payments = $query->selectRaw("
                        COALESCE(payment_method, 'cod') as method,
                        COUNT(*) as count,
                        SUM(total) as revenue
                    ")
                    ->groupBy('method')
                    ->get();

                return response()->json([
                    'methods' => $payments,
                    'labels' => $payments->pluck('method')->map(fn($m) => strtoupper($m)),
                    'revenues' => $payments->pluck('revenue'),
                    'counts' => $payments->pluck('count')
                ]);

            default:
                return response()->json(['error' => 'Invalid card requested'], 400);
        }
    }

    /**
     * Helper to apply date filter to high performance queries
     */
    private function applyTimeFilter($query, $filter, $column = 'created_at')
    {
        if ($filter === 'today') {
            $query->whereDate($column, Carbon::today());
        } elseif ($filter === 'week') {
            $query->where($column, '>=', Carbon::now()->startOfWeek());
        } elseif ($filter === 'month') {
            $query->where($column, '>=', Carbon::now()->startOfMonth());
        } elseif ($filter === 'year') {
            $query->where($column, '>=', Carbon::now()->startOfYear());
        }
        // 'all' applies no extra where clause
    }

    /**
     * Visitor Analytics (Coming Soon placeholder)
     */
    public function visitors()
    {
        return view('admin.analytics.visitors');
    }
}
