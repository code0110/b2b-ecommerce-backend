<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return Ticket::where('customer_id', $user->customer_id)
            ->orderByDesc('last_message_at')
            ->paginate(20);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'subject'  => ['required', 'string', 'max:191'],
            'category' => ['nullable', 'string', 'max:100'],
            'message'  => ['required', 'string'],
        ]);

        $ticket = Ticket::create([
            'customer_id'        => $user->customer_id,
            'created_by_user_id' => $user->id,
            'subject'            => $data['subject'],
            'category'           => $data['category'] ?? null,
            'status'             => 'new',
            'priority'           => 'normal',
            'last_message_at'    => now(),
        ]);

        TicketMessage::create([
            'ticket_id'      => $ticket->id,
            'sender_user_id' => $user->id,
            'message'        => $data['message'],
            'is_internal'    => false,
        ]);

        return response()->json($ticket->load('messages.sender'), 201);
    }

    public function show($id, Request $request)
    {
        $user = $request->user();

        $ticket = Ticket::where('customer_id', $user->customer_id)
            ->where('id', $id)
            ->with('messages.sender')
            ->firstOrFail();

        return $ticket;
    }

    public function addMessage($id, Request $request)
    {
        $user = $request->user();

        $ticket = Ticket::where('customer_id', $user->customer_id)
            ->where('id', $id)
            ->firstOrFail();

        $data = $request->validate([
            'message' => ['required', 'string'],
        ]);

        $message = TicketMessage::create([
            'ticket_id'      => $ticket->id,
            'sender_user_id' => $user->id,
            'message'        => $data['message'],
            'is_internal'    => false,
        ]);

        $ticket->last_message_at = now();
        if ($ticket->status === 'new') {
            $ticket->status = 'open';
        }
        $ticket->save();

        return response()->json($message->load('sender'), 201);
    }
}
