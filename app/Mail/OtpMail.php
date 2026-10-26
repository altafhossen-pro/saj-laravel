<?php

namespace App\Mail;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;
    public $customHeading;
    public $customMessage;

    public function __construct($otp, $customHeading = null, $customMessage = null)
    {
        $this->otp = $otp;
        $this->customHeading = $customHeading;
        $this->customMessage = $customMessage;
    }

    public function build()
    {
        $siteName = Setting::get('site_name', 'Ario Mart');
        $subject = Setting::get('email_tpl_otp_verification_subject', 'Your Verification Code - {site_name}');
        $subject = str_replace('{site_name}', $siteName, $subject);

        return $this->subject($subject)
                    ->view('emails.otp');
    }
}
