<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('entity_type', 'like', "%{$search}%")
                  ->orWhere('entity_id', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($action = $request->get('action')) {
            $query->where('action', $action);
        }

        if ($entityType = $request->get('entity_type')) {
            $query->where('entity_type', $entityType);
        }

        if ($entityId = $request->get('entity_id')) {
            $query->where('entity_id', $entityId);
        }

        if ($userId = $request->get('user_id')) {
            $query->where('user_id', $userId);
        }

        $query->orderByDesc('created_at');

        return $query->paginate(50);
    }

    public function show($id)
    {
        return AuditLog::with('user')->findOrFail($id);
    }
}
