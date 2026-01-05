<?php

namespace App\Notifications;

use App\Models\Offer;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OfferApprovalRequiredNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Offer $offer
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type'        => 'offer_approval_required',
            'offer_id'    => $this->offer->id,
            'status'      => $this->offer->status,
            'message'     => "Oferta #{$this->offer->id} necesită aprobarea dvs. (Discount > 15%)",
            'action_url'  => "/admin/offers/{$this->offer->id}"
        ];
    }
}
