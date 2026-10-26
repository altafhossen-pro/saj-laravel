<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Helper to log an outgoing email
     */
    public static function record($recipientEmail, $subject, $templateKey = 'custom', $status = 'sent', $errorMessage = null, $recipientName = null, $userId = null, $orderId = null, $senderType = 'system')
    {
        return self::create([
            'recipient_email' => $recipientEmail,
            'recipient_name'  => $recipientName,
            'subject'         => $subject,
            'template_key'    => $templateKey,
            'status'          => $status,
            'error_message'   => $errorMessage,
            'sender_type'     => $senderType,
            'user_id'         => $userId,
            'order_id'        => $orderId,
        ]);
    }
}
