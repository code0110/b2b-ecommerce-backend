<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($notifications);
    }

    public function unreadCount(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'unread' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead(Request $request, string $id)
    {
        $user = $request->user();

        $notification = $user->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read']);
    }

    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        $user->unreadNotifications->markAsRead();

        return response()->json(['message' => 'All notifications marked as read']);
    }

    public function getPreferences(Request $request)
    {
        $user = $request->user();
        $preferences = $user->notification_preferences ?? $this->getDefaultPreferences();
        return response()->json($preferences);
    }

    public function updatePreferences(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'preferences' => 'required|array',
        ]);

        // Merge with defaults to ensure structure integrity
        $newPreferences = array_merge($this->getDefaultPreferences(), $validated['preferences']);
        
        $user->notification_preferences = $newPreferences;
        $user->save();

        return response()->json($newPreferences);
    }

    private function getDefaultPreferences()
    {
        return [
            'order_updates_database' => true,
            'order_updates_email' => true,
            'promotions_database' => true,
            'promotions_email' => true,
        ];
    }
}
