<?php

namespace App\Observers;

use App\Models\Promotion;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class PromotionObserver
{
    public function created(Promotion $promotion): void
    {
        $this->log($promotion, 'created', $promotion->getAttributes());
    }

    public function updated(Promotion $promotion): void
    {
        $changes = $promotion->getChanges();
        
        // Ignore updated_at if it's the only change
        if (count($changes) === 1 && isset($changes['updated_at'])) {
            return;
        }

        $this->log($promotion, 'updated', $changes);
    }

    public function deleted(Promotion $promotion): void
    {
        $this->log($promotion, 'deleted', $promotion->getAttributes());
    }

    protected function log(Promotion $promotion, string $action, array $changes = []): void
    {
        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => $action,
            'entity_type' => Promotion::class,
            'entity_id'   => $promotion->id,
            'changes'     => $changes,
            'meta'        => [
                'ip'         => request()->ip(),
                'user_agent' => request()->userAgent(),
            ],
        ]);
    }
}
