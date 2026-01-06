<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GeneralNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $title,
        protected string $message,
        protected ?string $actionUrl = null,
        protected string $type = 'info' // info, warning, success, error
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type'       => 'general',
            'title'      => $this->title,
            'message'    => $this->message,
            'action_url' => $this->actionUrl,
            'level'      => $this->type, // useful for UI styling
        ];
    }
}
