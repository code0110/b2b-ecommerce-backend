<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderApprovalRequiredNotification extends Notification
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
            'type'        => 'order_approval_required',
            'order_id'    => $this->order->id,
            'order_number'=> $this->order->order_number,
            'status'      => $this->order->status,
            'total'       => (float) $this->order->grand_total,
            'currency'    => $this->order->currency,
            'message'     => "Comanda {$this->order->order_number} necesită aprobarea dvs. (derogare discount)",
            'action_url'  => "/admin/orders/{$this->order->id}"
        ];
    }
}
