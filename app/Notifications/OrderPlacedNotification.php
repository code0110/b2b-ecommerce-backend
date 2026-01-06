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
        // Detect if the notifiable user is an agent (or admin) vs customer
        // Simple logic: if notifiable has 'sales_agent' or 'admin' role, show different message
        // Or check if notifiable id != order->placed_by_user_id
        
        $isAgent = $notifiable->hasRole('sales_agent') || $notifiable->hasRole('sales_director') || $notifiable->hasRole('admin');
        
        $message = $isAgent 
            ? "Clientul {$this->order->customer->name} a plasat comanda nouă {$this->order->order_number}."
            : "Comanda {$this->order->order_number} a fost plasată cu succes.";

        return [
            'type'        => 'order_placed',
            'order_id'    => $this->order->id,
            'order_number'=> $this->order->order_number,
            'status'      => $this->order->status,
            'total'       => $this->order->grand_total,
            'currency'    => $this->order->currency,
            'message'     => $message,
            'action_url'  => $isAgent ? "/admin/orders/{$this->order->id}" : "/cont/comenzi/{$this->order->id}",
        ];
    }
}
