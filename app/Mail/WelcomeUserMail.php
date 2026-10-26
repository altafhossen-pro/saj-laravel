<?php

namespace App\Mail;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeUserMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $customSubject;
    public $customHeading;
    public $customMessage;

    public function __construct(User $user, $customSubject = null, $customHeading = null, $customMessage = null)
    {
        $this->user = $user;
        $this->customSubject = $customSubject;
        $this->customHeading = $customHeading;
        $this->customMessage = $customMessage;
    }

    public function build()
    {
        $siteName = Setting::get('site_name', 'Ario Mart');
        $subject = $this->customSubject ?: Setting::get('email_tpl_welcome_user_subject', 'Welcome to {site_name}! Happy Shopping 🎉');
        
        $subject = str_replace(
            ['{site_name}', '{customer_name}'],
            [$siteName, $this->user->name],
            $subject
        );

        return $this->subject($subject)
                    ->view('emails.welcome-user');
    }
}
