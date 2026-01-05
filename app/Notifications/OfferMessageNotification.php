<?php

namespace App\Notifications;

use App\Models\Offer;
use App\Models\OfferMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OfferMessageNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Offer $offer,
        protected OfferMessage $message,
        protected string $senderType // 'agent' or 'customer'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $senderName = $this->senderType === 'agent' ? 'Agentul' : 'Clientul';
        $text = "Mesaj nou de la {$senderName} pentru oferta #{$this->offer->id}";

        return [
            'type'        => 'offer_message',
            'offer_id'    => $this->offer->id,
            'message_id'  => $this->message->id,
            'content'     => $this->message->message,
            'message'     => $text,
            'action_url'  => $this->senderType === 'agent' 
                ? "/cont/oferte/{$this->offer->id}" // Customer link
                : "/admin/offers/{$this->offer->id}" // Agent link
        ];
    }
}
