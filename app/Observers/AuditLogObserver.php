<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogObserver
{
    public function created(Model $model)
    {
        $this->log($model, 'created');
    }

    public function updated(Model $model)
    {
        $this->log($model, 'updated');
    }

    public function deleted(Model $model)
    {
        $this->log($model, 'deleted');
    }

    protected function log(Model $model, string $action)
    {
        // Log only if user is authenticated (usually implies admin action in this context)
        if (!Auth::check()) {
            return;
        }

        $user = Auth::user();

        $changes = null;
        if ($action === 'updated') {
            // Get only changed attributes
            $dirty = $model->getDirty();
            // Get original values for those changed attributes
            $original = array_intersect_key($model->getOriginal(), $dirty);
            
            // Redact sensitive fields
            foreach (['password', 'remember_token', 'token', 'secret'] as $sensitive) {
                if (isset($dirty[$sensitive])) $dirty[$sensitive] = '[REDACTED]';
                if (isset($original[$sensitive])) $original[$sensitive] = '[REDACTED]';
            }

            $changes = [
                'before' => $original,
                'after' => $dirty,
            ];
        } elseif ($action === 'created') {
             $attrs = $model->getAttributes();
             foreach (['password', 'remember_token', 'token', 'secret'] as $sensitive) {
                if (isset($attrs[$sensitive])) $attrs[$sensitive] = '[REDACTED]';
             }
             $changes = [
                'after' => $attrs,
            ];
        } elseif ($action === 'deleted') {
            $attrs = $model->getAttributes();
            foreach (['password', 'remember_token', 'token', 'secret'] as $sensitive) {
                if (isset($attrs[$sensitive])) $attrs[$sensitive] = '[REDACTED]';
            }
            $changes = [
                'before' => $attrs,
            ];
        }

        AuditLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'entity_type' => class_basename($model),
            'entity_id' => $model->getKey(),
            'changes' => $changes,
            'meta' => [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'url' => request()->fullUrl(),
            ],
        ]);
    }
}
