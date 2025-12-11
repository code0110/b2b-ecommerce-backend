<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CreditBlockedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Order $order
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type'        => 'credit_blocked',
            'order_id'    => $this->order->id,
            'order_number'=> $this->order->order_number,
            'total'       => $this->order->grand_total,
            'currency'    => $this->order->currency,
            'message'     => "Comanda {$this->order->order_number} a fost blocată din cauza depășirii limitei de credit.",
        ];
    }
}
