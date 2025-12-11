<?php

namespace App\Traits;

use App\Models\AuditLog;

trait RecordsAudit
{
    protected function recordAudit(string $action, string $entityType, ?int $entityId = null, array $changes = [], array $meta = []): void
    {
        $user = auth()->user();

        AuditLog::create([
            'user_id'     => $user?->id,
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'changes'     => $changes ?: null,
            'meta'        => $meta ?: null,
        ]);
    }
}
