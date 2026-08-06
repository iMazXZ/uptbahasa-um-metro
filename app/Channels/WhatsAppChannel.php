<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;

class WhatsAppChannel
{
    /**
     * Send the given notification via WhatsApp.
     *
     * @param mixed $notifiable
     * @param \Illuminate\Notifications\Notification $notification
     * @return bool
     */
    public function send($notifiable, Notification $notification): bool
    {
        if (method_exists($notification, 'toWhatsApp')) {
            return $notification->toWhatsApp($notifiable);
        }

        return false;
    }
}
