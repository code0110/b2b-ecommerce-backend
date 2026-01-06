<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ProductBackInStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Product $product,
        protected ?string $variantName = null
    ) {}

    public function via($notifiable): array
    {
        // Trimite pe database (în aplicație) și pe mail dacă are email
        return ['database']; // TODO: Add 'mail' when mail is configured
    }

    public function toMail($notifiable): MailMessage
    {
        $productName = $this->product->name . ($this->variantName ? " ({$this->variantName})" : '');
        
        return (new MailMessage)
                    ->subject('Produsul revine în stoc: ' . $productName)
                    ->greeting('Salut ' . ($notifiable->first_name ?? ''))
                    ->line("Veste bună! Produsul {$productName} este din nou disponibil în stoc.")
                    ->action('Vezi Produsul', url("/produse/{$this->product->slug}"))
                    ->line('Grăbește-te să îl comanzi înainte să se epuizeze din nou!');
    }

    public function toDatabase($notifiable): array
    {
        $productName = $this->product->name . ($this->variantName ? " ({$this->variantName})" : '');

        return [
            'type' => 'back_in_stock',
            'title' => 'Produs revenit în stoc',
            'message' => "Produsul {$productName} este acum disponibil.",
            'product_id' => $this->product->id,
            'slug' => $this->product->slug,
            'action_url' => "/produse/{$this->product->slug}",
        ];
    }
}
