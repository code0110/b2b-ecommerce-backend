<?php

namespace App\Notifications;

use App\Models\Offer;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OfferStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Offer $offer,
        protected string $userType // 'agent' or 'customer' to customize message
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $message = "Statusul ofertei #{$this->offer->id} a fost actualizat la {$this->offer->status}.";
        
        if ($this->userType === 'customer') {
            if ($this->offer->status === 'sent') {
                $message = "Aveți o ofertă nouă #{$this->offer->id}.";
            } elseif ($this->offer->status === 'approved') {
                $message = "Oferta #{$this->offer->id} a fost aprobată de director.";
            }
        } elseif ($this->userType === 'agent') {
            if ($this->offer->status === 'accepted') {
                $message = "Clientul a acceptat oferta #{$this->offer->id}.";
            } elseif ($this->offer->status === 'rejected') {
                $message = "Clientul a respins oferta #{$this->offer->id}.";
            } elseif ($this->offer->status === 'negotiation') {
                $message = "Clientul dorește negocierea ofertei #{$this->offer->id}.";
            } elseif ($this->offer->status === 'sent') {
                $message = "Oferta #{$this->offer->id} a fost aprobată de director și trimisă la client.";
            }
        }

        return [
            'type'        => 'offer_status_changed',
            'offer_id'    => $this->offer->id,
            'status'      => $this->offer->status,
            'message'     => $message,
            'action_url'  => $this->userType === 'customer' ? "/cont/oferte/{$this->offer->id}" : "/admin/offers/{$this->offer->id}"
        ];
    }
}
