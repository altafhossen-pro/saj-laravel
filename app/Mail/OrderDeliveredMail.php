<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderDeliveredMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $customSubject;
    public $customHeading;
    public $customMessage;

    public function __construct(Order $order, $customSubject = null, $customHeading = null, $customMessage = null)
    {
        $this->order = $order;
        $this->customSubject = $customSubject;
        $this->customHeading = $customHeading;
        $this->customMessage = $customMessage;
    }

    public function build()
    {
        $siteName = Setting::get('site_name', 'Ario Mart');
        $subject = $this->customSubject ?: Setting::get('email_tpl_order_delivered_subject', "Your Order #{order_id} Has Been Delivered! 🎉 - {site_name}");
        
        $subject = str_replace(
            ['{order_id}', '{site_name}', '{customer_name}'],
            [$this->order->order_id, $siteName, optional($this->order->user)->name ?? 'Customer'],
            $subject
        );

        return $this->subject($subject)
                    ->view('emails.order-delivered');
    }
}
