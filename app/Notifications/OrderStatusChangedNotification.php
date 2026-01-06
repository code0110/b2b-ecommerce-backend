<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Order $order,
        protected string $previousStatus
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $statusLabel = $this->getStatusLabel($this->order->status);
        $message = "Statusul comenzii #{$this->order->order_number} a fost actualizat la: {$statusLabel}.";

        return [
            'type'        => 'order_status_changed',
            'level'       => $this->getLevelForStatus($this->order->status),
            'order_id'    => $this->order->id,
            'order_number'=> $this->order->order_number,
            'status'      => $this->order->status,
            'previous_status' => $this->previousStatus,
            'message'     => $message,
            'action_url'  => "/cont/comenzi/{$this->order->id}"
        ];
    }

    protected function getStatusLabel(string $status): string
    {
        return match($status) {
            'pending' => 'În așteptare',
            'processing' => 'În procesare',
            'completed' => 'Finalizată',
            'cancelled' => 'Anulată',
            'awaiting_payment' => 'Așteaptă plata',
            'on_hold' => 'În așteptare (Hold)',
            default => $status
        };
    }

    protected function getLevelForStatus(string $status): string
    {
        return match($status) {
            'completed' => 'success',
            'cancelled' => 'error',
            'processing' => 'info',
            'awaiting_payment', 'on_hold' => 'warning',
            default => 'info'
        };
    }
}
