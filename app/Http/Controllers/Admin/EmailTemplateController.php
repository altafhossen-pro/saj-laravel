<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\User;
use App\Models\EmailLog;
use App\Mail\OrderConfirmationMail;
use App\Mail\OrderDeliveredMail;
use App\Mail\WelcomeUserMail;
use App\Mail\CustomBroadcastMail;
use App\Mail\OtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Exception;

class EmailTemplateController extends Controller
{
    /**
     * Display email templates, custom sender, and sent history.
     */
    public function index(Request $request)
    {
        $templates = [
            [
                'key'         => 'order_placed',
                'name'        => 'Order Placed (Confirmation)',
                'description' => 'Sent to customer immediately after successfully placing an order (if registered or provided email).',
                'recipient'   => 'Customer',
                'active'      => (bool) Setting::get('email_tpl_order_placed_active', 1),
                'subject'     => Setting::get('email_tpl_order_placed_subject', 'Order Confirmed! Your Order #{order_id} - {site_name}'),
                'heading'     => Setting::get('email_tpl_order_placed_heading', 'Thank you for your order!'),
                'message'     => Setting::get('email_tpl_order_placed_message', 'We have received your order and are currently preparing it for delivery. You can check the summary of your purchase below.'),
                'badge'       => 'Live Connected',
                'badge_color' => 'bg-emerald-50 text-emerald-700 border-emerald-200'
            ],
            [
                'key'         => 'order_delivered',
                'name'        => 'Order Delivered Successfully',
                'description' => 'Sent automatically to customer when admin updates order status to Delivered.',
                'recipient'   => 'Customer',
                'active'      => (bool) Setting::get('email_tpl_order_delivered_active', 1),
                'subject'     => Setting::get('email_tpl_order_delivered_subject', 'Your Order #{order_id} Has Been Delivered! 🎉 - {site_name}'),
                'heading'     => Setting::get('email_tpl_order_delivered_heading', 'Your package has arrived!'),
                'message'     => Setting::get('email_tpl_order_delivered_message', 'We are pleased to inform you that your order has been successfully delivered. We hope you love your purchase!'),
                'badge'       => 'Live Connected',
                'badge_color' => 'bg-emerald-50 text-emerald-700 border-emerald-200'
            ],
            [
                'key'         => 'welcome_user',
                'name'        => 'Welcome New Customer',
                'description' => 'Triggered right after user completes OTP verification & registers account.',
                'recipient'   => 'Customer',
                'active'      => (bool) Setting::get('email_tpl_welcome_user_active', 1),
                'subject'     => Setting::get('email_tpl_welcome_user_subject', 'Welcome to {site_name}! Happy Shopping 🎉'),
                'heading'     => Setting::get('email_tpl_welcome_user_heading', 'Welcome to our Family!'),
                'message'     => Setting::get('email_tpl_welcome_user_message', 'We are thrilled to welcome you to our shopping community. Discover thousands of genuine products at unbeatable prices.'),
                'badge'       => 'Live Connected',
                'badge_color' => 'bg-emerald-50 text-emerald-700 border-emerald-200'
            ],
            [
                'key'         => 'otp_verification',
                'name'        => 'Registration OTP Verification',
                'description' => 'Contains 6-digit OTP code to verify customer email during account signup.',
                'recipient'   => 'Customer',
                'active'      => (bool) Setting::get('email_tpl_otp_verification_active', 1),
                'subject'     => Setting::get('email_tpl_otp_verification_subject', 'Your Verification Code - {site_name}'),
                'heading'     => Setting::get('email_tpl_otp_verification_heading', 'Verification Code'),
                'message'     => Setting::get('email_tpl_otp_verification_message', 'Please use the OTP code to complete your verification.'),
                'badge'       => 'Active System',
                'badge_color' => 'bg-blue-50 text-blue-700 border-blue-200'
            ],
            [
                'key'         => 'admin_new_order_alert',
                'name'        => 'Admin New Order Notification',
                'description' => 'Instantly notifies store owner/admin inbox whenever a new order is received.',
                'recipient'   => 'Admin',
                'active'      => (bool) Setting::get('email_tpl_admin_new_order_alert_active', 0),
                'subject'     => Setting::get('email_tpl_admin_new_order_alert_subject', '🚨 New Order Received #{order_id} - {site_name}'),
                'heading'     => Setting::get('email_tpl_admin_new_order_alert_heading', 'You Have a New Order!'),
                'message'     => Setting::get('email_tpl_admin_new_order_alert_message', 'A customer has just placed a new order. Please review and process it promptly in the admin panel.'),
                'badge'       => 'Coming Soon',
                'badge_color' => 'bg-amber-50 text-amber-700 border-amber-200'
            ]
        ];

        // Fetch logs with pagination
        $logs = EmailLog::with(['user', 'order'])
                    ->latest()
                    ->paginate(15);

        return view('admin.settings.email-templates', compact('templates', 'logs'));
    }

    /**
     * Update settings for an email template.
     */
    public function update(Request $request, $key)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'heading' => 'nullable|string|max:255',
            'message' => 'nullable|string|max:1000',
        ]);

        Setting::set("email_tpl_{$key}_active", $request->has('active') ? 1 : 0);
        Setting::set("email_tpl_{$key}_subject", $request->input('subject'));
        if ($request->has('heading')) {
            Setting::set("email_tpl_{$key}_heading", $request->input('heading'));
        }
        if ($request->has('message')) {
            Setting::set("email_tpl_{$key}_message", $request->input('message'));
        }

        return redirect()->back()->with('success', 'Email template updated successfully!');
    }

    /**
     * Render live HTML preview of a template.
     */
    public function preview($key)
    {
        $dummyOrder = $this->getDummyOrder();
        $dummyUser = User::first() ?? new User(['name' => 'Mohammad Rahim', 'email' => 'customer@example.com']);

        if ($key === 'order_placed' || $key === 'admin_new_order_alert') {
            $heading = Setting::get("email_tpl_{$key}_heading", 'Thank you for your order!');
            $message = Setting::get("email_tpl_{$key}_message", 'We have received your order and are currently preparing it for delivery.');
            return view('emails.order-confirmation', [
                'order' => $dummyOrder,
                'customHeading' => $heading,
                'customMessage' => $message,
            ]);
        }

        if ($key === 'order_delivered') {
            $heading = Setting::get("email_tpl_{$key}_heading", 'Your package has arrived!');
            $message = Setting::get("email_tpl_{$key}_message", 'We are pleased to inform you that your order has been successfully delivered. We hope you love your purchase!');
            return view('emails.order-delivered', [
                'order' => $dummyOrder,
                'customHeading' => $heading,
                'customMessage' => $message,
            ]);
        }

        if ($key === 'welcome_user') {
            $heading = Setting::get("email_tpl_{$key}_heading", 'Welcome to ' . Setting::get('site_name', 'Ario Mart') . '!');
            $message = Setting::get("email_tpl_{$key}_message", 'We are thrilled to welcome you to our shopping community. Discover thousands of genuine products at unbeatable prices.');
            return view('emails.welcome-user', [
                'user' => $dummyUser,
                'customHeading' => $heading,
                'customMessage' => $message,
            ]);
        }

        if ($key === 'otp_verification') {
            $heading = Setting::get("email_tpl_{$key}_heading", 'Verification Code');
            $message = Setting::get("email_tpl_{$key}_message", 'Please use the one-time verification code (OTP) below to verify your email address. This code is confidential and will expire in 5 minutes.');
            return view('emails.otp', [
                'otp' => '482910',
                'customHeading' => $heading,
                'customMessage' => $message
            ]);
        }

        // Generic fallback preview
        return view('emails.custom-mail', [
            'mailSubject' => 'Official Store Notice',
            'mailHeading' => 'Hello Valued Customer',
            'mailBody'    => 'This is a sample custom email notification sent from the store administration.',
            'actionText'  => 'Visit Store',
            'actionUrl'   => url('/'),
        ]);
    }

    /**
     * Send a test email to given address.
     */
    public function sendTest(Request $request, $key)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $recipientEmail = $request->input('email');
        $dummyOrder = $this->getDummyOrder();
        $dummyUser = User::first() ?? new User(['name' => 'Valued Customer', 'email' => $recipientEmail]);

        try {
            \App\Services\MailConfigService::applyRuntimeConfig();
            if ($key === 'otp_verification') {
                Mail::to($recipientEmail)->send(new OtpMail('123456'));
                $subject = '[TEST] Your Verification Code';
            } elseif ($key === 'order_delivered') {
                $heading = Setting::get("email_tpl_{$key}_heading");
                $message = Setting::get("email_tpl_{$key}_message");
                $subject = '[TEST MAIL] ' . Setting::get("email_tpl_{$key}_subject", "Your Order #{$dummyOrder->order_id} Has Been Delivered! 🎉");
                Mail::to($recipientEmail)->send(new OrderDeliveredMail($dummyOrder, $subject, $heading, $message));
            } elseif ($key === 'welcome_user') {
                $heading = Setting::get("email_tpl_{$key}_heading");
                $message = Setting::get("email_tpl_{$key}_message");
                $subject = '[TEST MAIL] ' . Setting::get("email_tpl_{$key}_subject", "Welcome to " . Setting::get('site_name', 'Ario Mart'));
                Mail::to($recipientEmail)->send(new WelcomeUserMail($dummyUser, $subject, $heading, $message));
            } else {
                $heading = Setting::get("email_tpl_{$key}_heading");
                $message = Setting::get("email_tpl_{$key}_message");
                $subject = '[TEST MAIL] ' . Setting::get("email_tpl_{$key}_subject", "Order Confirmed! Your Order #{$dummyOrder->order_id}");
                Mail::to($recipientEmail)->send(new OrderConfirmationMail($dummyOrder, $subject, $heading, $message));
            }

            // Log Test Mail
            EmailLog::record(
                $recipientEmail,
                $subject,
                $key . '_test',
                'sent',
                null,
                'Admin Test',
                auth()->id(),
                null,
                'admin'
            );

            return response()->json([
                'success' => true,
                'message' => "Test email sent successfully to {$recipientEmail}!"
            ]);
        } catch (Exception $e) {
            EmailLog::record(
                $recipientEmail,
                '[TEST FAILED] ' . $key,
                $key . '_test',
                'failed',
                $e->getMessage(),
                'Admin Test',
                auth()->id(),
                null,
                'admin'
            );

            return response()->json([
                'success' => false,
                'message' => 'Failed to send test email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send Custom Email to any recipient email address.
     */
    public function sendCustom(Request $request)
    {
        $request->validate([
            'recipient_email' => 'required|email',
            'subject'         => 'required|string|max:255',
            'heading'         => 'nullable|string|max:255',
            'message'         => 'required|string|max:5000',
            'action_text'     => 'nullable|string|max:100',
            'action_url'      => 'nullable|url|max:255',
        ]);

        $recipientEmail = $request->input('recipient_email');
        $subject        = $request->input('subject');
        $heading        = $request->input('heading');
        $body           = $request->input('message');
        $actionText     = $request->input('action_text');
        $actionUrl      = $request->input('action_url');

        try {
            \App\Services\MailConfigService::applyRuntimeConfig();
            Mail::to($recipientEmail)->send(new CustomBroadcastMail($subject, $heading, $body, $actionText, $actionUrl));

            EmailLog::record(
                $recipientEmail,
                $subject,
                'custom',
                'sent',
                null,
                null,
                auth()->id(),
                null,
                'admin'
            );

            return response()->json([
                'success' => true,
                'message' => "Custom email successfully sent to {$recipientEmail}!"
            ]);
        } catch (Exception $e) {
            EmailLog::record(
                $recipientEmail,
                $subject,
                'custom',
                'failed',
                $e->getMessage(),
                null,
                auth()->id(),
                null,
                'admin'
            );

            return response()->json([
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper to get or build dummy order for realistic preview & test email.
     */
    protected function getDummyOrder()
    {
        $order = Order::with('items')->latest()->first();
        if ($order && $order->items->count() > 0) {
            return $order;
        }

        $order = new Order();
        $order->id = 1;
        $order->order_id = 'ORD-' . rand(100000, 999999);
        $order->created_at = now();
        $order->status = 'delivered';
        $order->payment_method = 'Cash on Delivery';
        $order->payment_status = 'paid';
        $order->shipping_cost = 70;
        $order->coupon_discount = 50;
        $order->total = 1270;
        $order->shipping_address = [
            'name' => 'Mohammad Rahim',
            'phone' => '01712-345678',
            'address' => 'House 12, Road 4, Sector 7, Uttara, Dhaka'
        ];

        $dummyItem1 = new OrderItem([
            'name' => 'Premium Smart Digital Watch Series 9',
            'price' => 850,
            'quantity' => 1,
            'subtotal' => 850,
            'variant_info' => ['label' => 'Color: Midnight Black']
        ]);

        $dummyItem2 = new OrderItem([
            'name' => 'Wireless Bluetooth Earbuds Pro',
            'price' => 400,
            'quantity' => 1,
            'subtotal' => 400,
            'variant_info' => ['label' => 'Edition: Standard']
        ]);

        $order->setRelation('items', collect([$dummyItem1, $dummyItem2]));
        return $order;
    }
}
