<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DatabaseRestoredMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $details;

    public function __construct(array $details)
    {
        $this->details = $details;
    }

    public function build()
    {
        return $this->subject('🚨 [CRITICAL ALERT] Database Restored - ' . ($this->details['site_name'] ?? 'Ario Mart'))
                    ->view('emails.database-restored');
    }
}
