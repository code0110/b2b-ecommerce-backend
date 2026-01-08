<?php

namespace App\Listeners;

use App\Models\AuditLog;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogAuditLogout
{
    /**
     * Handle the event.
     */
    public function handle(Logout $event): void
    {
        if (!$event->user) {
            return;
        }

        AuditLog::create([
            'user_id' => $event->user->id,
            'action' => 'logout',
            'entity_type' => 'User',
            'entity_id' => $event->user->id,
            'changes' => [],
            'meta' => [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'url' => request()->fullUrl(),
            ],
        ]);
    }
}
