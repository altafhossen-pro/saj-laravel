<?php

namespace App\Mail;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomBroadcastMail extends Mailable
{
    use Queueable, SerializesModels;

    public $mailSubject;
    public $mailHeading;
    public $mailBody;
    public $actionText;
    public $actionUrl;

    public function __construct($mailSubject, $mailHeading, $mailBody, $actionText = null, $actionUrl = null)
    {
        $this->mailSubject = $mailSubject;
        $this->mailHeading = $mailHeading;
        $this->mailBody    = $mailBody;
        $this->actionText  = $actionText;
        $this->actionUrl   = $actionUrl;
    }

    public function build()
    {
        return $this->subject($this->mailSubject)
                    ->view('emails.custom-mail');
    }
}
