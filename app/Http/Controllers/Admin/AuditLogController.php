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
