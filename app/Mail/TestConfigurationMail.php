<?php

namespace App\Mail;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestConfigurationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $details;

    public function __construct(array $details)
    {
        $this->details = $details;
    }

    public function build()
    {
        $siteName = Setting::get('site_name', 'Ario Mart');
        $subject = '✅ Mail Configuration Verified: Test Email from ' . $siteName;

        return $this->subject($subject)
                    ->view('emails.test-configuration');
    }
}
