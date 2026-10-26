<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Storage;
use App\Models\Setting;

class SettingController extends Controller
{
    public function index()
    {
        return view('admin.settings.index');
    }

    public function branding()
    {
        $logo = Setting::get('site_logo');
        $favicon = Setting::get('site_favicon');
        $ogImage = Setting::get('site_og_image');
        $siteTitle = Setting::get('site_title', 'SAJ - Online Shopping in Bangladesh | Best Deals & Fast Delivery');
        $metaDescription = Setting::get('site_meta_description', 'Discover top quality trending jewelry, cosmetics & fashion products at best prices on SAJ. Fast delivery all across Bangladesh with Cash on Delivery.');
        $metaKeywords = Setting::get('site_meta_keywords', 'saj, saj bd, jewelry bangladesh, cosmetics bd, online shopping bangladesh, earrings, necklaces, fashion');

        return view('admin.settings.branding', compact(
            'logo', 'favicon', 'ogImage', 'siteTitle', 'metaDescription', 'metaKeywords'
        ));
    }

    public function updateBranding(Request $request)
    {
        $request->validate([
            'site_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'site_favicon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,ico,webp|max:1024',
            'site_og_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:3072',
            'site_title' => 'nullable|string|max:255',
            'site_meta_description' => 'nullable|string|max:500',
            'site_meta_keywords' => 'nullable|string|max:500',
        ]);

        if ($request->hasFile('site_logo')) {
            $logoPath = $request->file('site_logo')->store('settings', 'public');
            Setting::set('site_logo', Storage::url($logoPath), 'image', 'branding');
        }

        if ($request->hasFile('site_favicon')) {
            $faviconPath = $request->file('site_favicon')->store('settings', 'public');
            Setting::set('site_favicon', Storage::url($faviconPath), 'image', 'branding');
        }

        if ($request->hasFile('site_og_image')) {
            $ogPath = $request->file('site_og_image')->store('settings', 'public');
            Setting::set('site_og_image', Storage::url($ogPath), 'image', 'branding');
        }

        if ($request->has('site_title')) {
            Setting::set('site_title', $request->site_title, 'string', 'branding');
        }

        if ($request->has('site_meta_description')) {
            Setting::set('site_meta_description', $request->site_meta_description, 'string', 'branding');
        }

        if ($request->has('site_meta_keywords')) {
            Setting::set('site_meta_keywords', $request->site_meta_keywords, 'string', 'branding');
        }

        return redirect()->back()->with('success', 'Branding & SEO settings updated successfully!');
    }

    public function delivery()
    {
        $insideDhaka = Setting::get('delivery_charge_inside_dhaka', 70);
        $subDhaka = Setting::get('delivery_charge_sub_dhaka', 110);
        $outsideDhaka = Setting::get('delivery_charge_outside_dhaka', 130);
        $freeMinOrder = Setting::get('delivery_free_min_order', 3000);

        return view('admin.settings.delivery', compact('insideDhaka', 'subDhaka', 'outsideDhaka', 'freeMinOrder'));
    }

    public function updateDelivery(Request $request)
    {
        $request->validate([
            'delivery_charge_inside_dhaka' => 'required|numeric|min:0',
            'delivery_charge_sub_dhaka' => 'required|numeric|min:0',
            'delivery_charge_outside_dhaka' => 'required|numeric|min:0',
            'delivery_free_min_order' => 'required|numeric|min:0',
        ]);

        Setting::set('delivery_charge_inside_dhaka', $request->delivery_charge_inside_dhaka, 'number', 'delivery');
        Setting::set('delivery_charge_sub_dhaka', $request->delivery_charge_sub_dhaka, 'number', 'delivery');
        Setting::set('delivery_charge_outside_dhaka', $request->delivery_charge_outside_dhaka, 'number', 'delivery');
        Setting::set('delivery_free_min_order', $request->delivery_free_min_order, 'number', 'delivery');

        \Illuminate\Support\Facades\Cache::forget('delivery_charges');

        return redirect()->back()->with('success', 'Delivery settings updated successfully!');
    }

    public function smartRatings()
    {
        $smartRatingEnabled = Setting::get('smart_rating_enabled', 1);
        $smartRatingInterval = Setting::get('smart_rating_interval_days', 7);
        $smartRatingMin = Setting::get('smart_rating_min_rating', 4.5);
        $smartRatingMax = Setting::get('smart_rating_max_rating', 5.0);

        return view('admin.settings.smart-ratings', compact(
            'smartRatingEnabled', 
            'smartRatingInterval', 
            'smartRatingMin', 
            'smartRatingMax'
        ));
    }

    public function updateSmartRatings(Request $request)
    {
        $request->validate([
            'smart_rating_enabled' => 'required|boolean',
            'smart_rating_interval_days' => 'required|numeric|min:1',
            'smart_rating_min_rating' => 'required|numeric|min:1|max:5',
            'smart_rating_max_rating' => 'required|numeric|min:1|max:5|gte:smart_rating_min_rating',
        ]);

        Setting::set('smart_rating_enabled', $request->smart_rating_enabled, 'boolean', 'smart_ratings');
        Setting::set('smart_rating_interval_days', $request->smart_rating_interval_days, 'number', 'smart_ratings');
        Setting::set('smart_rating_min_rating', $request->smart_rating_min_rating, 'number', 'smart_ratings');
        Setting::set('smart_rating_max_rating', $request->smart_rating_max_rating, 'number', 'smart_ratings');

        \Illuminate\Support\Facades\Cache::forget('smart_ratings_settings');

        return redirect()->back()->with('success', 'Smart ratings settings updated successfully!');
    }

    public function siteInfo()
    {
        $siteName = Setting::get('site_name', 'SAJ');
        $sitePhone = Setting::get('site_phone', '');
        $siteWhatsapp = Setting::get('site_whatsapp', '');
        $siteEmail = Setting::get('site_email', '');
        $siteFacebook = Setting::get('site_facebook', '');
        $siteInstagram = Setting::get('site_instagram', '');
        $siteYoutube = Setting::get('site_youtube', '');
        $siteTiktok = Setting::get('site_tiktok', '');
        $siteLinkedin = Setting::get('site_linkedin', '');
        
        return view('admin.settings.site-info', compact(
            'siteName', 'sitePhone', 'siteWhatsapp', 'siteEmail', 
            'siteFacebook', 'siteInstagram', 'siteYoutube', 'siteTiktok', 'siteLinkedin'
        ));
    }

    public function updateSiteInfo(Request $request)
    {
        $request->validate([
            'site_name' => 'required|string|max:255',
            'site_phone' => 'nullable|string|max:50',
            'site_whatsapp' => 'nullable|string|max:50',
            'site_email' => 'nullable|email|max:255',
            'site_facebook' => 'nullable|url|max:255',
            'site_instagram' => 'nullable|url|max:255',
            'site_youtube' => 'nullable|url|max:255',
            'site_tiktok' => 'nullable|url|max:255',
            'site_linkedin' => 'nullable|url|max:255',
        ]);

        Setting::set('site_name', $request->site_name, 'string', 'site_info');
        Setting::set('site_phone', $request->site_phone, 'string', 'site_info');
        Setting::set('site_whatsapp', $request->site_whatsapp, 'string', 'site_info');
        Setting::set('site_email', $request->site_email, 'string', 'site_info');
        Setting::set('site_facebook', $request->site_facebook, 'string', 'site_info');
        Setting::set('site_instagram', $request->site_instagram, 'string', 'site_info');
        Setting::set('site_youtube', $request->site_youtube, 'string', 'site_info');
        Setting::set('site_tiktok', $request->site_tiktok, 'string', 'site_info');
        Setting::set('site_linkedin', $request->site_linkedin, 'string', 'site_info');

        return redirect()->back()->with('success', 'Site Information updated successfully!');
    }

    public function scripts()
    {
        $headScripts = Setting::get('site_head_scripts', '');
        $bodyScripts = Setting::get('site_body_scripts', '');
        
        return view('admin.settings.scripts', compact('headScripts', 'bodyScripts'));
    }

    public function updateScripts(Request $request)
    {
        // No validation rules for scripts as they can contain anything
        Setting::set('site_head_scripts', $request->input('site_head_scripts', ''), 'text', 'scripts');
        Setting::set('site_body_scripts', $request->input('site_body_scripts', ''), 'text', 'scripts');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Dynamic scripts updated successfully!'
            ]);
        }

        return redirect()->back()->with('success', 'Dynamic scripts updated successfully!');
    }

    public function steadfast()
    {
        $apiKey = Setting::get('steadfast_api_key', '');
        $secretKey = Setting::get('steadfast_secret_key', '');
        $webhookToken = Setting::get('steadfast_webhook_token', '');
        $baseUrl = Setting::get('steadfast_base_url', 'https://portal.packzy.com/api/v1');
        $callbackUrl = url('/api/steadfast/webhook');

        return view('admin.settings.steadfast', compact('apiKey', 'secretKey', 'webhookToken', 'baseUrl', 'callbackUrl'));
    }

    public function updateSteadfast(Request $request)
    {
        $request->validate([
            'steadfast_api_key' => 'nullable|string|max:255',
            'steadfast_secret_key' => 'nullable|string|max:255',
            'steadfast_webhook_token' => 'nullable|string|max:255',
            'steadfast_base_url' => 'nullable|url|max:255',
        ]);

        Setting::set('steadfast_api_key', $request->input('steadfast_api_key', ''), 'string', 'courier');
        Setting::set('steadfast_secret_key', $request->input('steadfast_secret_key', ''), 'string', 'courier');
        Setting::set('steadfast_webhook_token', $request->input('steadfast_webhook_token', ''), 'string', 'courier');
        Setting::set('steadfast_base_url', $request->input('steadfast_base_url', 'https://portal.packzy.com/api/v1'), 'string', 'courier');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Steadfast configuration saved successfully!'
            ]);
        }

        return redirect()->back()->with('success', 'Steadfast configuration saved successfully!');
    }

    public function steadfastPing(\App\Services\SteadfastService $steadfastService)
    {
        $result = $steadfastService->ping();
        return response()->json($result);
    }

    public function steadfastBalance(\App\Services\SteadfastService $steadfastService)
    {
        $result = $steadfastService->getBalance();
        return response()->json($result);
    }

    public function telegram()
    {
        $botToken = Setting::get('telegram_bot_token', '');
        $chatIds = Setting::get('telegram_chat_ids', '');

        return view('admin.settings.telegram', compact('botToken', 'chatIds'));
    }

    public function updateTelegram(Request $request)
    {
        $request->validate([
            'telegram_bot_token' => 'nullable|string|max:255',
            'telegram_chat_ids' => 'nullable|string',
        ]);

        Setting::set('telegram_bot_token', trim($request->input('telegram_bot_token', '')), 'string', 'telegram');
        Setting::set('telegram_chat_ids', trim($request->input('telegram_chat_ids', '')), 'text', 'telegram');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Telegram configuration saved successfully!'
            ]);
        }

        return redirect()->back()->with('success', 'Telegram configuration saved successfully!');
    }

    public function telegramTestBot(Request $request, \App\Services\TelegramService $telegramService)
    {
        $token = $request->input('bot_token') ?: null;
        $result = $telegramService->getMe($token);
        return response()->json($result);
    }

    public function telegramTestMessage(Request $request, \App\Services\TelegramService $telegramService)
    {
        $request->validate([
            'chat_id' => 'nullable|string',
            'message' => 'nullable|string',
        ]);

        $customChatId = $request->input('chat_id');
        $siteName = Setting::get('site_name', config('app.name', 'Famous Bazar'));
        $defaultMsg = "🚀 <b>{$siteName} - Telegram Test Notification</b>\n\n" .
                      "✅ Your Telegram Bot connection is successfully configured and working!\n" .
                      "🕒 <i>Sent at: " . now()->format('Y-m-d h:i:s A') . "</i>";

        $message = $request->input('message') ?: $defaultMsg;

        $result = $telegramService->sendMessage($message, $customChatId);
        return response()->json($result);
    }

    public function tawkto()
    {
        $enabled = (bool) Setting::get('tawkto_enabled', false);
        $propertyId = Setting::get('tawkto_property_id', '');
        $widgetId = Setting::get('tawkto_widget_id', 'default');
        $visibilityMode = Setting::get('tawkto_visibility_mode', 'hidden'); // 'hidden' or 'visible'
        $trackOnlyLoggedIn = (bool) Setting::get('tawkto_track_logged_in_only', false);

        return view('admin.settings.tawkto', compact(
            'enabled',
            'propertyId',
            'widgetId',
            'visibilityMode',
            'trackOnlyLoggedIn'
        ));
    }

    public function updateTawkto(Request $request)
    {
        $request->validate([
            'tawkto_enabled' => 'nullable|in:0,1',
            'tawkto_property_id' => 'nullable|string|max:100',
            'tawkto_widget_id' => 'nullable|string|max:100',
            'tawkto_visibility_mode' => 'required|in:hidden,visible',
            'tawkto_track_logged_in_only' => 'nullable|in:0,1',
        ]);

        Setting::set('tawkto_enabled', $request->has('tawkto_enabled') ? '1' : '0', 'boolean', 'tawkto');
        Setting::set('tawkto_property_id', trim($request->input('tawkto_property_id', '')), 'string', 'tawkto');
        Setting::set('tawkto_widget_id', trim($request->input('tawkto_widget_id', 'default')), 'string', 'tawkto');
        Setting::set('tawkto_visibility_mode', $request->input('tawkto_visibility_mode', 'hidden'), 'string', 'tawkto');
        Setting::set('tawkto_track_logged_in_only', $request->has('tawkto_track_logged_in_only') ? '1' : '0', 'boolean', 'tawkto');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Tawk.to configuration updated successfully!'
            ]);
        }

        return redirect()->back()->with('success', 'Tawk.to configuration updated successfully!');
    }

    public function facebookPixel()
    {
        $enabled = (bool) Setting::get('fb_pixel_enabled', false);
        $pixelScript = Setting::get('fb_pixel_script', '');
        $pixelId = Setting::get('fb_pixel_id', '');
        $capiToken = Setting::get('fb_capi_token', '');
        $testEventCode = Setting::get('fb_test_event_code', '');

        $trackPageView = (bool) Setting::get('fb_track_page_view', true);
        $trackViewContent = (bool) Setting::get('fb_track_view_content', true);
        $trackViewCategory = (bool) Setting::get('fb_track_view_category', true);
        $trackSearch = (bool) Setting::get('fb_track_search', true);
        $trackAddToCart = (bool) Setting::get('fb_track_add_to_cart', true);
        $trackInitiateCheckout = (bool) Setting::get('fb_track_initiate_checkout', true);
        $trackPurchase = (bool) Setting::get('fb_track_purchase', true);
        $trackCompleteRegistration = (bool) Setting::get('fb_track_complete_registration', true);
        $trackContact = (bool) Setting::get('fb_track_contact', true);

        return view('admin.settings.facebook-pixel', compact(
            'enabled', 'pixelScript', 'pixelId', 'capiToken', 'testEventCode',
            'trackPageView', 'trackViewContent', 'trackViewCategory', 'trackSearch',
            'trackAddToCart', 'trackInitiateCheckout', 'trackPurchase',
            'trackCompleteRegistration', 'trackContact'
        ));
    }

    public function updateFacebookPixel(Request $request)
    {
        $request->validate([
            'fb_pixel_script' => 'nullable|string',
            'fb_pixel_id' => 'nullable|string|max:100',
            'fb_capi_token' => 'nullable|string',
            'fb_test_event_code' => 'nullable|string|max:50',
        ]);

        $rawScript = $request->input('fb_pixel_script', '');
        $pixelId = trim($request->input('fb_pixel_id', ''));

        // If raw script provided, auto-extract Pixel ID
        if (!empty($rawScript)) {
            if (preg_match('/fbq\([\'"]init[\'"],\s*[\'"](\d+)[\'"]\)/i', $rawScript, $matches)) {
                $pixelId = $matches[1];
            } elseif (preg_match('/tr\?id=(\d+)/i', $rawScript, $matches)) {
                $pixelId = $matches[1];
            } elseif (empty($pixelId) && preg_match('/(\d{14,17})/', $rawScript, $matches)) {
                $pixelId = $matches[1];
            }
        }

        Setting::set('fb_pixel_script', $rawScript, 'text', 'facebook_pixel');
        Setting::set('fb_pixel_enabled', $request->has('fb_pixel_enabled') ? '1' : '0', 'boolean', 'facebook_pixel');
        Setting::set('fb_pixel_id', $pixelId, 'string', 'facebook_pixel');
        Setting::set('fb_capi_token', trim($request->input('fb_capi_token', '')), 'string', 'facebook_pixel');
        Setting::set('fb_test_event_code', trim($request->input('fb_test_event_code', '')), 'string', 'facebook_pixel');

        Setting::set('fb_track_page_view', $request->has('fb_track_page_view') ? '1' : '0', 'boolean', 'facebook_pixel');
        Setting::set('fb_track_view_content', $request->has('fb_track_view_content') ? '1' : '0', 'boolean', 'facebook_pixel');
        Setting::set('fb_track_view_category', $request->has('fb_track_view_category') ? '1' : '0', 'boolean', 'facebook_pixel');
        Setting::set('fb_track_search', $request->has('fb_track_search') ? '1' : '0', 'boolean', 'facebook_pixel');
        Setting::set('fb_track_add_to_cart', $request->has('fb_track_add_to_cart') ? '1' : '0', 'boolean', 'facebook_pixel');
        Setting::set('fb_track_initiate_checkout', $request->has('fb_track_initiate_checkout') ? '1' : '0', 'boolean', 'facebook_pixel');
        Setting::set('fb_track_purchase', $request->has('fb_track_purchase') ? '1' : '0', 'boolean', 'facebook_pixel');
        Setting::set('fb_track_complete_registration', $request->has('fb_track_complete_registration') ? '1' : '0', 'boolean', 'facebook_pixel');
        Setting::set('fb_track_contact', $request->has('fb_track_contact') ? '1' : '0', 'boolean', 'facebook_pixel');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Meta Pixel & Conversions API settings updated successfully!'
            ]);
        }

        return redirect()->back()->with('success', 'Meta Pixel & Conversions API settings updated successfully!');
    }

    public function testFacebookPixel(Request $request)
    {
        $pixelId = trim($request->input('pixel_id', '')) ?: Setting::get('fb_pixel_id');
        $token = trim($request->input('capi_token', '')) ?: Setting::get('fb_capi_token');
        $testCode = trim($request->input('test_code', '')) ?: Setting::get('fb_test_event_code');

        if (empty($pixelId) || empty($token)) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter both Pixel ID and Conversions API Access Token to test.'
            ], 422);
        }

        $result = \App\Services\FacebookCapiService::testConnection($pixelId, $token, $testCode);

        return response()->json($result);
    }
}


