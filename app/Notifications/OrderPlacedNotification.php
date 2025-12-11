<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderPlacedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Order $order
    ) {}

    public function via($notifiable): array
    {
        // păstrăm în DB; email se adaugă ulterior
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type'        => 'order_placed',
            'order_id'    => $this->order->id,
            'order_number'=> $this->order->order_number,
            'status'      => $this->order->status,
            'total'       => $this->order->grand_total,
            'currency'    => $this->order->currency,
            'message'     => "Comanda {$this->order->order_number} a fost plasată.",
        ];
    }
}
