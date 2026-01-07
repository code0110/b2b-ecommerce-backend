<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\GeneralNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $limit = $request->input('limit', 20);

        $notifications = $user->notifications()->limit($limit)->get();

        return response()->json($notifications);
    }

    public function unreadCount(Request $request)
    {
        return response()->json([
            'count' => $request->user()->unreadNotifications()->count()
        ]);
    }

    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();
        $notification = $user->notifications()->where('id', $id)->first();

        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json(['message' => 'Marked as read']);
    }

    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return response()->json(['message' => 'All marked as read']);
    }

    public function history()
    {
        // Fetch last 50 sent notifications of type GeneralNotification
        $notifications = DB::table('notifications')
            ->where('type', 'App\\Notifications\\GeneralNotification')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        // Enrich with user names manually since it's a polymorphic relation manually queried
        // or just return raw data.
        // For better UI, we might want to know WHO received it.
        // notifiable_id is the user ID.
        
        $userIds = $notifications->pluck('notifiable_id')->unique();
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');

        $notifications->transform(function ($n) use ($users) {
            $n->data = json_decode($n->data);
            $user = $users[$n->notifiable_id] ?? null;
            $n->recipient_name = $user ? $user->full_name : 'Utilizator Șters';
            return $n;
        });

        return response()->json($notifications);
    }

    public function send(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'action_url' => 'nullable|string',
            'type' => 'required|in:info,warning,success,error',
            'target_type' => 'required|in:all,role,user,users',
            'target_id' => 'nullable', // role name or user id(s)
        ]);

        $users = collect();

        switch ($request->target_type) {
            case 'all':
                // Caution: This could be heavy if there are thousands of users.
                // For B2B usually manageable, but chunking is better.
                $users = User::where('is_active', true)->get();
                break;
                
            case 'role':
                $role = $request->target_id;
                if ($role) {
                    $users = User::whereHas('roles', function($q) use ($role) {
                        $q->where('slug', $role)->orWhere('name', $role);
                    })->where('is_active', true)->get();
                }
                break;

            case 'user':
                if ($request->target_id) {
                    $user = User::find($request->target_id);
                    if ($user) $users->push($user);
                }
                break;
                
            case 'users':
                 if (is_array($request->target_id)) {
                     $users = User::whereIn('id', $request->target_id)->get();
                 }
                 break;
        }

        if ($users->isEmpty()) {
            return response()->json(['message' => 'Niciun utilizator nu a fost găsit pentru criteriile selectate.'], 404);
        }

        // Send notification
        // Using Facade to potentially queue it efficiently
        Notification::send($users, new GeneralNotification(
            $request->title,
            $request->message,
            $request->action_url,
            $request->type
        ));

        return response()->json([
            'message' => 'Notificarea a fost trimisă cu succes către ' . $users->count() . ' utilizatori.'
        ]);
    }

    public function searchUsers(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2'
        ]);

        $query = $request->input('query');

        $users = User::query()
            ->where(function($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                  ->orWhere('last_name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get();

        $results = $users->map(function($user) {
            return [
                'id' => $user->id,
                'name' => $user->full_name,
                'email' => $user->email
            ];
        });

        return response()->json($results);
    }
}
