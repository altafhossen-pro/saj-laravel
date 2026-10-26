<?php

namespace App\Mail;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $meta;

    public function __construct(User $user, array $meta = [])
    {
        $this->user = $user;
        $this->meta = $meta;
    }

    public function build()
    {
        $siteName = Setting::get('site_name', 'Ario Mart');
        $subject = '🔒 Security Alert: Your Password Was Changed - ' . $siteName;

        return $this->subject($subject)
                    ->view('emails.password-changed');
    }
}
