<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\ErpSyncLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncOrderToErp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected Order $order
    ) {}

    public function handle(): void
    {
        $log = ErpSyncLog::create([
            'entity_type' => 'order',
            'direction'   => 'push',
            'external_id' => null,
            'status'      => 'pending',
            'payload'     => ['order_id' => $this->order->id],
        ]);

        try {
            // TODO: apel API ERP pentru trimitere comandă
            // ex: app(ErpService::class)->pushOrder($this->order);

            $log->status  = 'success';
            $log->message = 'Order synced to ERP (mock).';
            $log->run_at  = now();
            $log->save();
        } catch (\Throwable $e) {
            $log->status  = 'error';
            $log->message = $e->getMessage();
            $log->run_at  = now();
            $log->save();

            throw $e;
        }
    }
}
