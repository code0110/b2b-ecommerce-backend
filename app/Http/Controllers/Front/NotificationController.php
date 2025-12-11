<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return $user->notifications()
            ->orderByDesc('created_at')
            ->paginate(20);
    }

    public function unreadCount(Request $request)
    {
        $user = $request->user();

        return ['count' => $user->unreadNotifications()->count()];
    }

    public function markAsRead($id, Request $request)
    {
        $user = $request->user();

        $notification = $user->notifications()->where('id', $id)->firstOrFail();
        if (!$notification->read_at) {
            $notification->markAsRead();
        }

        return response()->json(['message' => 'Marked as read.']);
    }

    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        $user->unreadNotifications->markAsRead();

        return response()->json(['message' => 'All marked as read.']);
    }
}
