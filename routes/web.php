<?php

use Illuminate\Support\Facades\Route;

Route::get('/', [\App\Http\Controllers\Frontend\HomeController::class, 'index'])->name('home');
Route::get('/api/homepage/just-for-you', [\App\Http\Controllers\Frontend\HomeController::class, 'justForYou'])->name('api.homepage.just-for-you');

Route::get('/product/{slug}', [\App\Http\Controllers\Frontend\ProductController::class, 'show'])->name('product.show');
Route::get('/product/{product}/questions', [\App\Http\Controllers\Frontend\ProductController::class, 'getQuestions'])->name('product.questions.index');
Route::post('/product/{product}/questions', [\App\Http\Controllers\Frontend\ProductController::class, 'storeQuestion'])
    ->middleware('throttle:5,5') // Allow 5 requests per 5 minutes per IP
    ->name('product.questions.store');

Route::get('/shop/{category_slug}', [\App\Http\Controllers\Frontend\ShopController::class, 'category'])->name('shop.category');
Route::get('/api/search', [\App\Http\Controllers\Frontend\ShopController::class, 'search'])->name('api.search');
Route::post('/api/search/log', [\App\Http\Controllers\Frontend\ShopController::class, 'logSearchClick'])->name('api.search.log');
Route::get('/search', [\App\Http\Controllers\Frontend\ShopController::class, 'searchPage'])->name('shop.search');

Route::get('/checkout', [\App\Http\Controllers\Frontend\HomeController::class, 'checkout'])->name('checkout');
Route::post('/checkout', [\App\Http\Controllers\Frontend\CheckoutController::class, 'process'])->name('checkout.process');
Route::post('/checkout/apply-coupon', [\App\Http\Controllers\Frontend\CheckoutController::class, 'applyCoupon'])->name('checkout.apply-coupon');
Route::get('/checkout/success/{order_id}', [\App\Http\Controllers\Frontend\CheckoutController::class, 'success'])->name('checkout.success');
Route::post('/api/cart/sync', [\App\Http\Controllers\Frontend\CartSyncController::class, 'sync'])->name('api.cart.sync');
Route::post('/api/cart/update-info', [\App\Http\Controllers\Frontend\CartSyncController::class, 'updateCustomerInfo'])->name('api.cart.update-info');

// Static Pages
Route::get('/terms-and-conditions', [\App\Http\Controllers\Frontend\PageController::class, 'termsAndConditions'])->name('page.terms');
Route::get('/privacy-policy', [\App\Http\Controllers\Frontend\PageController::class, 'privacyPolicy'])->name('page.privacy');
Route::get('/return-and-refund-policy', [\App\Http\Controllers\Frontend\PageController::class, 'returnRefundPolicy'])->name('page.return-refund');
Route::get('/about-us', [\App\Http\Controllers\Frontend\PageController::class, 'aboutUs'])->name('page.about');
Route::get('/contact-us', [\App\Http\Controllers\Frontend\PageController::class, 'contactUs'])->name('page.contact');
Route::post('/contact-us', [\App\Http\Controllers\Frontend\PageController::class, 'submitContact'])
    ->middleware('throttle:10,1') // Max 10 submissions per minute per IP
    ->name('page.contact.submit');

// Order Tracking
Route::get('/order-tracking', [\App\Http\Controllers\Frontend\PageController::class, 'tracking'])->name('page.tracking');
Route::get('/api/order-tracking/{order_id}', [\App\Http\Controllers\Frontend\PageController::class, 'trackOrder'])->name('api.tracking');

// SEO XML Sitemap (Scalable Sitemap Index & Chunking)
Route::get('/sitemap.xml', [\App\Http\Controllers\Frontend\SitemapController::class, 'index'])->name('sitemap');
Route::get('/sitemap', [\App\Http\Controllers\Frontend\SitemapController::class, 'index']);
Route::get('/sitemap-pages.xml', [\App\Http\Controllers\Frontend\SitemapController::class, 'pages'])->name('sitemap.pages');
Route::get('/sitemap-categories.xml', [\App\Http\Controllers\Frontend\SitemapController::class, 'categories'])->name('sitemap.categories');
Route::get('/sitemap-products-{page}.xml', [\App\Http\Controllers\Frontend\SitemapController::class, 'products'])
    ->where('page', '[0-9]+')
    ->name('sitemap.products');

Route::middleware(['auth'])->prefix('dashboard')->group(function () {
    Route::get('/', [\App\Http\Controllers\Frontend\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/my-orders', [\App\Http\Controllers\Frontend\DashboardController::class, 'orders'])->name('dashboard.orders');
    Route::get('/my-reviews', [\App\Http\Controllers\Frontend\DashboardController::class, 'reviews'])->name('dashboard.reviews');
    Route::get('/my-reviews/write/{product_slug}', [\App\Http\Controllers\Frontend\DashboardController::class, 'writeReview'])->name('dashboard.reviews.write');
    Route::post('/my-reviews', [\App\Http\Controllers\Frontend\DashboardController::class, 'storeReview'])->name('dashboard.reviews.store');
    Route::get('/profile', [\App\Http\Controllers\Frontend\DashboardController::class, 'profile'])->name('dashboard.profile');
    Route::post('/profile', [\App\Http\Controllers\Frontend\DashboardController::class, 'updateProfile'])->name('dashboard.profile.update');
    Route::post('/profile/avatar', [\App\Http\Controllers\Frontend\DashboardController::class, 'updateAvatar'])->name('dashboard.profile.avatar');
    Route::post('/profile/password', [\App\Http\Controllers\Frontend\DashboardController::class, 'updatePassword'])->name('dashboard.profile.password');
    Route::get('/my-orders/{order_id}', [\App\Http\Controllers\Frontend\DashboardController::class, 'showOrder'])->name('dashboard.orders.show')->withoutMiddleware('auth');
    Route::get('/my-orders/{order_id}/invoice', [\App\Http\Controllers\Frontend\DashboardController::class, 'invoice'])->name('dashboard.orders.invoice')->withoutMiddleware('auth');
    Route::get('/stop-impersonating', [\App\Http\Controllers\Admin\CustomerController::class, 'stopImpersonating'])->name('impersonate.stop');
});

// Admin Routes
Route::middleware(['auth', 'role:super_admin'])->prefix('admin/dashboard')->group(function () {
    Route::get('/', function () {
        return view('admin.dashboard');
    })->name('admin.dashboard');

    Route::get('/orders/manual', [\App\Http\Controllers\Admin\OrderController::class, 'manual'])->name('admin.orders.manual');
    Route::post('/orders/manual', [\App\Http\Controllers\Admin\OrderController::class, 'storeManual'])->name('admin.orders.store');
    Route::get('/orders/drafts', [\App\Http\Controllers\Admin\OrderController::class, 'drafts'])->name('admin.orders.drafts');
    Route::get('/all-orders', [\App\Http\Controllers\Admin\OrderController::class, 'index'])->name('admin.orders');
    Route::get('/all-orders/{order_id}', [\App\Http\Controllers\Admin\OrderController::class, 'show'])->name('admin.orders.show');
    Route::get('/all-orders/{order_id}/edit', [\App\Http\Controllers\Admin\OrderController::class, 'edit'])->name('admin.orders.edit');
    Route::get('/api/orders', [\App\Http\Controllers\Admin\OrderController::class, 'fetchOrders'])->name('admin.orders.fetch');
    Route::put('/api/orders/{order_id}', [\App\Http\Controllers\Admin\OrderController::class, 'update'])->name('admin.orders.update');
    Route::post('/api/orders/{order_id}/status', [\App\Http\Controllers\Admin\OrderController::class, 'updateStatus'])->name('admin.orders.update-status');
    Route::post('/api/orders/{order_id}/send-to-steadfast', [\App\Http\Controllers\Admin\OrderController::class, 'sendToSteadfast'])->name('admin.orders.send-to-steadfast');
    Route::get('/api/orders/{order_id}/steadfast-status', [\App\Http\Controllers\Admin\OrderController::class, 'getSteadfastStatus'])->name('admin.orders.steadfast-status');
    Route::get('/api/orders/steadfast-fraud-check', [\App\Http\Controllers\Admin\OrderController::class, 'checkSteadfastFraud'])->name('admin.orders.steadfast-fraud-check');

    // Products
    Route::get('/products', [\App\Http\Controllers\Admin\ProductController::class, 'index'])->name('admin.products.index');
    Route::get('/products/search', [\App\Http\Controllers\Admin\ProductController::class, 'search'])->name('admin.products.search');
    Route::get('/products/check-slug', [\App\Http\Controllers\Admin\ProductController::class, 'checkSlug'])->name('admin.products.check-slug');
    Route::get('/products/create', [\App\Http\Controllers\Admin\ProductController::class, 'create'])->name('admin.products.create');
    Route::post('/products', [\App\Http\Controllers\Admin\ProductController::class, 'store'])->name('admin.products.store');
    Route::get('/products/{product}/edit', [\App\Http\Controllers\Admin\ProductController::class, 'edit'])->name('admin.products.edit');
    Route::put('/products/{product}', [\App\Http\Controllers\Admin\ProductController::class, 'update'])->name('admin.products.update');
    Route::delete('/products/{product}', [\App\Http\Controllers\Admin\ProductController::class, 'destroy'])->name('admin.products.destroy');

    // Brands
    Route::get('/brands', [\App\Http\Controllers\Admin\BrandController::class, 'index'])->name('admin.brands.index');
    Route::get('/api/brands', [\App\Http\Controllers\Admin\BrandController::class, 'fetch'])->name('admin.brands.fetch');
    Route::post('/brands', [\App\Http\Controllers\Admin\BrandController::class, 'store'])->name('admin.brands.store');
    Route::put('/brands/{brand}', [\App\Http\Controllers\Admin\BrandController::class, 'update'])->name('admin.brands.update');
    Route::delete('/brands/{brand}', [\App\Http\Controllers\Admin\BrandController::class, 'destroy'])->name('admin.brands.destroy');

    // Product Questions
    Route::get('/product-questions', [\App\Http\Controllers\Admin\ProductQuestionController::class, 'index'])->name('admin.questions.index');
    Route::put('/product-questions/{question}', [\App\Http\Controllers\Admin\ProductQuestionController::class, 'update'])->name('admin.questions.update');
    // Customers
    Route::get('/customers', [\App\Http\Controllers\Admin\CustomerController::class, 'index'])->name('admin.customers');
    Route::get('/customers/staff', [\App\Http\Controllers\Admin\CustomerController::class, 'staff'])->name('admin.customers.staff');
    Route::get('/api/customers', [\App\Http\Controllers\Admin\CustomerController::class, 'fetch'])->name('admin.customers.fetch');
    Route::get('/customers/search', [\App\Http\Controllers\Admin\OrderController::class, 'searchCustomer'])->name('admin.customers.search');
    Route::get('/customers/{user}', [\App\Http\Controllers\Admin\CustomerController::class, 'show'])->name('admin.customers.show');
    Route::put('/customers/{user}', [\App\Http\Controllers\Admin\CustomerController::class, 'update'])->name('admin.customers.update');
    Route::put('/customers/{user}/password', [\App\Http\Controllers\Admin\CustomerController::class, 'updatePassword'])->name('admin.customers.password.update');
    Route::post('/customers/{user}/login-as', [\App\Http\Controllers\Admin\CustomerController::class, 'loginAs'])->name('admin.customers.login_as');
    
    // Categories
    Route::get('/categories/search', [\App\Http\Controllers\Admin\CategoryController::class, 'search'])->name('admin.categories.search');
    Route::get('/categories', [\App\Http\Controllers\Admin\CategoryController::class, 'index'])->name('admin.categories.index');
    Route::get('/categories/create', [\App\Http\Controllers\Admin\CategoryController::class, 'create'])->name('admin.categories.create');
    Route::post('/categories', [\App\Http\Controllers\Admin\CategoryController::class, 'store'])->name('admin.categories.store');
    Route::get('/categories/{category}/edit', [\App\Http\Controllers\Admin\CategoryController::class, 'edit'])->name('admin.categories.edit');
    Route::put('/categories/{category}', [\App\Http\Controllers\Admin\CategoryController::class, 'update'])->name('admin.categories.update');
    Route::delete('/categories/{category}', [\App\Http\Controllers\Admin\CategoryController::class, 'destroy'])->name('admin.categories.destroy');
    Route::post('/categories/reorder', [\App\Http\Controllers\Admin\CategoryController::class, 'reorder'])->name('admin.categories.reorder');
    Route::post('/categories/{category}/homepage-settings', [\App\Http\Controllers\Admin\CategorySettingsController::class, 'updateHomepageSettings'])->name('admin.categories.homepage-settings');

    // Reviews
    Route::get('/reviews', [\App\Http\Controllers\Admin\ProductReviewController::class, 'index'])->name('admin.reviews.index');
    Route::post('/reviews/{review}/status', [\App\Http\Controllers\Admin\ProductReviewController::class, 'updateStatus'])->name('admin.reviews.status.update');
    Route::put('/reviews/{review}', [\App\Http\Controllers\Admin\ProductReviewController::class, 'update'])->name('admin.reviews.update');
    Route::delete('/reviews/{review}', [\App\Http\Controllers\Admin\ProductReviewController::class, 'destroy'])->name('admin.reviews.destroy');

    // Homepage Settings
    Route::get('/homepage/just-for-you', [\App\Http\Controllers\Admin\HomepageSettingsController::class, 'justForYou'])->name('admin.homepage.just-for-you');
    Route::post('/homepage/just-for-you', [\App\Http\Controllers\Admin\HomepageSettingsController::class, 'saveJustForYou'])->name('admin.homepage.just-for-you.save');

    Route::get('/homepage/trending', [\App\Http\Controllers\Admin\HomepageSettingsController::class, 'trending'])->name('admin.homepage.trending');
    Route::post('/homepage/trending/settings', [\App\Http\Controllers\Admin\HomepageSettingsController::class, 'saveTrendingSettings'])->name('admin.homepage.trending.settings.save');
    Route::post('/homepage/trending/add', [\App\Http\Controllers\Admin\HomepageSettingsController::class, 'addTrending'])->name('admin.homepage.trending.add');
    Route::delete('/homepage/trending/{product}', [\App\Http\Controllers\Admin\HomepageSettingsController::class, 'removeTrending'])->name('admin.homepage.trending.remove');
    Route::get('/homepage/category-sections', [\App\Http\Controllers\Admin\HomepageSettingsController::class, 'categorySections'])->name('admin.homepage.category-sections');
    Route::post('/homepage/category-sections/reorder', [\App\Http\Controllers\Admin\HomepageSettingsController::class, 'reorderCategorySections'])->name('admin.homepage.category-sections.reorder');
    Route::get('/api/products/search', [\App\Http\Controllers\Admin\HomepageSettingsController::class, 'searchProducts'])->name('admin.api.products.search');

    // Coupons
    Route::get('/coupons/api/products', [\App\Http\Controllers\Admin\CouponController::class, 'getProducts'])->name('admin.coupons.api.products');
    Route::get('/coupons', [\App\Http\Controllers\Admin\CouponController::class, 'index'])->name('admin.coupons.index');
    Route::get('/coupons/create', [\App\Http\Controllers\Admin\CouponController::class, 'create'])->name('admin.coupons.create');
    Route::post('/coupons', [\App\Http\Controllers\Admin\CouponController::class, 'store'])->name('admin.coupons.store');
    Route::get('/coupons/{coupon}/edit', [\App\Http\Controllers\Admin\CouponController::class, 'edit'])->name('admin.coupons.edit');
    Route::put('/coupons/{coupon}', [\App\Http\Controllers\Admin\CouponController::class, 'update'])->name('admin.coupons.update');
    Route::post('/coupons/{coupon}/toggle-status', [\App\Http\Controllers\Admin\CouponController::class, 'toggleStatus'])->name('admin.coupons.toggle-status');
    // Abandoned Carts & Cart Activity
    Route::get('/abandoned-carts', [\App\Http\Controllers\Admin\CartAnalyticsController::class, 'index'])->name('admin.carts.index');
    Route::delete('/abandoned-carts/{cart}', [\App\Http\Controllers\Admin\CartAnalyticsController::class, 'destroy'])->name('admin.carts.destroy');

    // Settings
    Route::get('/settings', [\App\Http\Controllers\Admin\SettingController::class, 'index'])->name('admin.settings.index');
    Route::get('/settings/branding', [\App\Http\Controllers\Admin\SettingController::class, 'branding'])->name('admin.settings.branding');
    Route::post('/settings/branding', [\App\Http\Controllers\Admin\SettingController::class, 'updateBranding'])->name('admin.settings.branding.update');

    Route::get('/settings/delivery', [\App\Http\Controllers\Admin\SettingController::class, 'delivery'])->name('admin.settings.delivery');
    Route::post('/settings/delivery', [\App\Http\Controllers\Admin\SettingController::class, 'updateDelivery'])->name('admin.settings.delivery.update');

    Route::get('/settings/site-info', [\App\Http\Controllers\Admin\SettingController::class, 'siteInfo'])->name('admin.settings.site-info');
    Route::post('/settings/site-info', [\App\Http\Controllers\Admin\SettingController::class, 'updateSiteInfo'])->name('admin.settings.site-info.update');

    Route::get('/settings/site-scripts', [\App\Http\Controllers\Admin\SettingController::class, 'scripts'])->name('admin.settings.scripts');
    Route::post('/settings/site-scripts', [\App\Http\Controllers\Admin\SettingController::class, 'updateScripts'])->name('admin.settings.scripts.update');

    Route::get('/settings/steadfast', [\App\Http\Controllers\Admin\SettingController::class, 'steadfast'])->name('admin.settings.steadfast');
    Route::post('/settings/steadfast', [\App\Http\Controllers\Admin\SettingController::class, 'updateSteadfast'])->name('admin.settings.steadfast.update');
    Route::post('/settings/steadfast/ping', [\App\Http\Controllers\Admin\SettingController::class, 'steadfastPing'])->name('admin.settings.steadfast.ping');
    Route::post('/settings/steadfast/balance', [\App\Http\Controllers\Admin\SettingController::class, 'steadfastBalance'])->name('admin.settings.steadfast.balance');

    Route::get('/settings/telegram', [\App\Http\Controllers\Admin\SettingController::class, 'telegram'])->name('admin.settings.telegram');
    Route::post('/settings/telegram', [\App\Http\Controllers\Admin\SettingController::class, 'updateTelegram'])->name('admin.settings.telegram.update');
    Route::post('/settings/telegram/test-bot', [\App\Http\Controllers\Admin\SettingController::class, 'telegramTestBot'])->name('admin.settings.telegram.test-bot');
    Route::post('/settings/telegram/test-message', [\App\Http\Controllers\Admin\SettingController::class, 'telegramTestMessage'])->name('admin.settings.telegram.test-message');

    // Tawk.to Live Chat / Invisible Tracker Settings
    Route::get('/settings/tawkto', [\App\Http\Controllers\Admin\SettingController::class, 'tawkto'])->name('admin.settings.tawkto');
    Route::post('/settings/tawkto', [\App\Http\Controllers\Admin\SettingController::class, 'updateTawkto'])->name('admin.settings.tawkto.update');

    // Facebook Pixel & Conversions API (CAPI) Settings
    Route::get('/settings/facebook-pixel', [\App\Http\Controllers\Admin\SettingController::class, 'facebookPixel'])->name('admin.settings.facebook-pixel');
    Route::post('/settings/facebook-pixel', [\App\Http\Controllers\Admin\SettingController::class, 'updateFacebookPixel'])->name('admin.settings.facebook-pixel.update');
    Route::post('/settings/facebook-pixel/test', [\App\Http\Controllers\Admin\SettingController::class, 'testFacebookPixel'])->name('admin.settings.facebook-pixel.test');

    // Email & SMTP Server Settings
    Route::get('/settings/smtp', [\App\Http\Controllers\Admin\SmtpSettingsController::class, 'index'])->name('admin.settings.smtp');
    Route::post('/settings/smtp', [\App\Http\Controllers\Admin\SmtpSettingsController::class, 'update'])->name('admin.settings.smtp.update');
    Route::post('/settings/smtp/reachability', [\App\Http\Controllers\Admin\SmtpSettingsController::class, 'testReachability'])->name('admin.settings.smtp.reachability');
    Route::post('/settings/smtp/test', [\App\Http\Controllers\Admin\SmtpSettingsController::class, 'sendTest'])->name('admin.settings.smtp.test');

    // Email Templates Management & Custom Dispatch
    Route::get('/settings/email-templates', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'index'])->name('admin.settings.email-templates');
    Route::post('/settings/email-templates/{key}', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'update'])->name('admin.settings.email-templates.update');
    Route::get('/settings/email-templates/{key}/preview', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'preview'])->name('admin.settings.email-templates.preview');
    Route::post('/settings/email-templates/{key}/send-test', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'sendTest'])->name('admin.settings.email-templates.send-test');
    Route::post('/settings/email-templates-send-custom', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'sendCustom'])->name('admin.settings.email-templates.send-custom');

    // Laravel System Logs Management & Editor
    Route::get('/settings/logs', [\App\Http\Controllers\Admin\LogViewerController::class, 'index'])->name('admin.settings.logs');
    Route::post('/settings/logs/save', [\App\Http\Controllers\Admin\LogViewerController::class, 'save'])->name('admin.settings.logs.save');
    Route::post('/settings/logs/clear', [\App\Http\Controllers\Admin\LogViewerController::class, 'clear'])->name('admin.settings.logs.clear');
    Route::delete('/settings/logs/delete', [\App\Http\Controllers\Admin\LogViewerController::class, 'destroy'])->name('admin.settings.logs.delete');
    Route::get('/settings/logs/download', [\App\Http\Controllers\Admin\LogViewerController::class, 'download'])->name('admin.settings.logs.download');

    // Database Backup & Disaster Recovery
    Route::get('/settings/backup', [\App\Http\Controllers\Admin\DatabaseBackupController::class, 'index'])->name('admin.settings.backup');
    Route::get('/settings/backup/captcha', [\App\Http\Controllers\Admin\DatabaseBackupController::class, 'getCaptcha'])->name('admin.settings.backup.captcha');
    Route::post('/settings/backup/create', [\App\Http\Controllers\Admin\DatabaseBackupController::class, 'create'])->name('admin.settings.backup.create');
    Route::get('/settings/backup/download/{filename}', [\App\Http\Controllers\Admin\DatabaseBackupController::class, 'download'])->name('admin.settings.backup.download');
    Route::delete('/settings/backup/{filename}', [\App\Http\Controllers\Admin\DatabaseBackupController::class, 'destroy'])->name('admin.settings.backup.destroy');
    Route::post('/settings/backup/restore/{filename}', [\App\Http\Controllers\Admin\DatabaseBackupController::class, 'restoreStored'])->name('admin.settings.backup.restore-stored');
    Route::post('/settings/backup/restore-upload', [\App\Http\Controllers\Admin\DatabaseBackupController::class, 'restoreUpload'])->name('admin.settings.backup.restore-upload');

    // Live Visitors Polling API
    Route::get('/api/live-visitors', function () {
        return response()->json([
            'live' => \App\Services\VisitorAnalyticsService::getLiveCount(),
            'today' => \App\Services\VisitorAnalyticsService::getTodayStats(),
            'visitors' => \App\Services\VisitorAnalyticsService::getLiveVisitors()
        ]);
    })->name('admin.api.live-visitors');

    // Search Analytics & Product Views Insights API
    Route::get('/api/search-insights', function () {
        $topQueries = \App\Models\SearchLog::select('query', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->groupBy('query')
            ->orderByDesc('total')
            ->take(8)
            ->get();

        $recentSearches = \App\Models\SearchLog::with(['user:id,name', 'clickedProduct:id,title,slug'])
            ->latest()
            ->take(10)
            ->get();

        $topViewedProducts = \App\Models\Product\Product::where('is_active', true)
            ->orderByDesc('views_count')
            ->take(8)
            ->get(['id', 'title', 'slug', 'featured_image', 'views_count', 'base_price']);

        return response()->json([
            'top_queries' => $topQueries,
            'recent_searches' => $recentSearches,
            'top_viewed_products' => $topViewedProducts
        ]);
    })->name('admin.api.search-insights');

    // Homepage Management
    Route::patch('homepage/hero/{heroSlide}/toggle', [\App\Http\Controllers\Admin\HeroSlideController::class, 'toggle'])->name('admin.homepage.hero.toggle');
    Route::resource('homepage/hero', \App\Http\Controllers\Admin\HeroSlideController::class)
        ->names('admin.homepage.hero')
        ->parameters(['hero' => 'heroSlide']);

    // Analytics Menu Routes
    Route::prefix('analytics')->name('admin.analytics.')->group(function () {
        Route::get('/search', [\App\Http\Controllers\Admin\AnalyticsController::class, 'search'])->name('search');
        Route::get('/sales', [\App\Http\Controllers\Admin\AnalyticsController::class, 'sales'])->name('sales');
        Route::get('/sales/data', [\App\Http\Controllers\Admin\AnalyticsController::class, 'salesData'])->name('sales.data');
        Route::get('/visitors', [\App\Http\Controllers\Admin\AnalyticsController::class, 'visitors'])->name('visitors');
    });

    // Security & Blacklist Routes
    Route::prefix('security/blacklist')->name('admin.blacklist.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\BlacklistController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Admin\BlacklistController::class, 'store'])->name('store');
        Route::put('/{blacklist}', [\App\Http\Controllers\Admin\BlacklistController::class, 'update'])->name('update');
        Route::patch('/{blacklist}/toggle', [\App\Http\Controllers\Admin\BlacklistController::class, 'toggle'])->name('toggle');
        Route::delete('/{blacklist}', [\App\Http\Controllers\Admin\BlacklistController::class, 'destroy'])->name('destroy');
    });

    // Contact Messages Routes
    Route::prefix('contact-messages')->name('admin.contact-messages.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ContactMessageController::class, 'index'])->name('index');
        Route::put('/{contactMessage}/status', [\App\Http\Controllers\Admin\ContactMessageController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{contactMessage}', [\App\Http\Controllers\Admin\ContactMessageController::class, 'destroy'])->name('destroy');
    });
});

use App\Http\Controllers\Auth\AuthController;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1'); // Max 5 login attempts per minute per IP

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register/send-otp', [AuthController::class, 'sendOtp'])
        ->middleware('throttle:5,5'); // Max 5 OTP requests per 5 minutes
    Route::post('/register/verify-otp', [AuthController::class, 'verifyOtp'])
        ->middleware('throttle:5,5'); // Max 5 verification attempts per 5 minutes
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:5,1'); // Max 5 account registrations per minute

    Route::get('/auth/google', [AuthController::class, 'redirectToGoogle']);
    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
});

Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');
