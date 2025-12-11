<?php

namespace App\Notifications;

use App\Models\QuoteRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class QuoteStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected QuoteRequest $quote
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type'        => 'quote_status_changed',
            'quote_id'    => $this->quote->id,
            'status'      => $this->quote->status,
            'message'     => "Statusul ofertei #{$this->quote->id} a devenit {$this->quote->status}.",
        ];
    }
}
