<?php

namespace App\Console\Commands;

use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class CancelUnpaidB2COrders extends Command
{
    protected $signature = 'orders:cancel-unpaid-b2c';
    protected $description = 'Auto-anulează comenzile B2C neplătite în X zile';

    public function handle(): int
    {
        $days = (int) config('orders.b2c_unpaid_auto_cancel_days', 3);
        $threshold = CarbonImmutable::now()->subDays($days);

        $this->info("Anulez comenzile B2C neplătite mai vechi de {$days} zile (până la {$threshold}).");

        $query = Order::where('type', 'b2c')
            ->whereIn('payment_status', ['pending', 'awaiting'])
            ->whereIn('status', ['pending', 'processing'])
            ->whereNull('cancelled_at')
            ->whereNotNull('placed_at')
            ->where('placed_at', '<=', $threshold);

        $count = $query->count();

        if ($count === 0) {
            $this->info('Nu există comenzi de anulat.');
            return self::SUCCESS;
        }

        $this->info("Găsit {$count} comenzi de anulat.");

        $query->chunkById(100, function ($orders) use ($days) {
            foreach ($orders as $order) {
                $order->status         = 'cancelled';
                $order->payment_status = 'cancelled';
                $order->cancelled_at   = now();
                $order->cancel_reason  = "Auto-cancel B2C neplătită după {$days} zile";
                $order->auto_cancelled = true;
                $order->save();
            }
        });

        $this->info("Au fost anulate {$count} comenzi.");

        return self::SUCCESS;
    }
}
