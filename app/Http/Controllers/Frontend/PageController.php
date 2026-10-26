<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;

class PageController extends Controller
{
    public function termsAndConditions() { return view('frontend.terms'); }
    public function privacyPolicy() { return view('frontend.privacy'); }
    public function returnRefundPolicy() { return view('frontend.return-refund'); }
    public function aboutUs() 
    { 
        $categories = \App\Models\Category::where('is_active', true)->take(8)->get(['id', 'name', 'slug', 'image']);
        return view('frontend.about-us', compact('categories')); 
    }
    public function contactUs() { return view('frontend.contact-us'); }

    public function submitContact(\Illuminate\Http\Request $request, \App\Services\TelegramService $telegramService)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'phone' => ['required', 'string', 'regex:/^01[0-9]{9}$/'],
            'email' => 'nullable|email|max:150',
            'subject' => 'nullable|string|max:150',
            'message' => 'required|string|max:3000',
        ], [
            'phone.regex' => 'মোবাইল নম্বরটি অবশ্যই ০১ দিয়ে শুরু হতে হবে এবং সঠিক ১১ ডিজিটের হতে হবে।'
        ]);

        $ip = $request->ip() ?? '127.0.0.1';
        $userAgent = substr($request->userAgent() ?? '', 0, 255);

        $contact = \App\Models\ContactMessage::create([
            'name' => trim($validated['name']),
            'phone' => trim($validated['phone']),
            'email' => !empty($validated['email']) ? trim($validated['email']) : null,
            'subject' => !empty($validated['subject']) ? trim($validated['subject']) : 'General Inquiry',
            'message' => trim($validated['message']),
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'status' => 'new',
        ]);

        // Send Telegram Notification if configured (without emojis/icons)
        try {
            if ($telegramService->isConfigured()) {
                $siteName = \App\Models\Setting::get('site_name', config('app.name', 'Famous Bazar'));
                $safeName = htmlspecialchars($contact->name, ENT_QUOTES, 'UTF-8');
                $safePhone = htmlspecialchars($contact->phone, ENT_QUOTES, 'UTF-8');
                $safeEmail = $contact->email ? htmlspecialchars($contact->email, ENT_QUOTES, 'UTF-8') : 'N/A';
                $safeSubject = htmlspecialchars($contact->subject, ENT_QUOTES, 'UTF-8');
                $safeMessage = htmlspecialchars(mb_strimwidth($contact->message, 0, 500, '...'), ENT_QUOTES, 'UTF-8');
                $time = now()->format('d M, Y h:i A');

                $telegramText = "<b>[New Contact Message Received]</b>\n";
                $telegramText .= "<b>Store:</b> {$siteName}\n";
                $telegramText .= "<b>Name:</b> {$safeName}\n";
                $telegramText .= "<b>Phone:</b> <code>{$safePhone}</code>\n";
                $telegramText .= "<b>Email:</b> {$safeEmail}\n";
                $telegramText .= "<b>Subject:</b> {$safeSubject}\n";
                $telegramText .= "<b>IP Address:</b> <code>{$ip}</code>\n";
                $telegramText .= "<b>Time:</b> {$time}\n\n";
                $telegramText .= "<b>Message:</b>\n<i>{$safeMessage}</i>";

                $telegramService->sendMessage($telegramText);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Telegram notification error for contact message: ' . $e->getMessage());
        }

        // Meta CAPI Contact Track
        try {
            \App\Services\FacebookCapiService::trackContact('contact_form', null, [
                'name' => $contact->name,
                'phone' => $contact->phone,
                'email' => $contact->email,
            ]);
        } catch (\Throwable $fbEx) {}

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'আপনার মেসেজটি সফলভাবে গৃহীত হয়েছে। আমরা খুব শীঘ্রই আপনার সাথে যোগাযোগ করবো।'
            ]);
        }

        return back()->with('success', 'আপনার মেসেজটি সফলভাবে গৃহীত হয়েছে।');
    }

    public function tracking()
    {
        return view('frontend.tracking');
    }

    public function trackOrder($order_id)
    {
        $order = \App\Models\Order::where('order_id', $order_id)->first();

        if (!$order) {
            return response()->json(['error' => 'Order not found. Please check your Order ID.'], 404);
        }

        return response()->json([
            'order_id' => $order->order_id,
            'status' => $order->status,
            'total' => number_format($order->total, 2),
            'shipping_address' => $order->shipping_address,
            'timestamps' => $order->status_timestamps ?? [],
            'created_at' => $order->created_at->format('M d, Y h:i A')
        ]);
    }
}
