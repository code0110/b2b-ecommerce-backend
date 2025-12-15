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

        $tickets = Ticket::where('customer_id', $user->customer_id)
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json($tickets);
    }

    public function show(int $id, Request $request)
    {
        $user = $request->user();

        $ticket = Ticket::with(['messages.sender'])
            ->where('customer_id', $user->customer_id)
            ->findOrFail($id);

        return response()->json($ticket);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'subject'  => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'message'  => 'required|string',
            'priority' => 'nullable|string|max:50',
        ]);

        $ticket = Ticket::create([
            'customer_id'        => $user->customer_id,
            'created_by_user_id' => $user->id,
            'subject'            => $data['subject'],
            'category'           => $data['category'] ?? null,
            'status'             => 'new',
            'priority'           => $data['priority'] ?? 'normal',
            'last_message_at'    => now(),
        ]);

        TicketMessage::create([
            'ticket_id'      => $ticket->id,
            'sender_user_id' => $user->id,
            'message'        => $data['message'],
            'is_internal'    => false,
        ]);

        return response()->json($ticket->fresh(), 201);
    }

    public function storeMessage(int $id, Request $request)
    {
        $user = $request->user();

        $ticket = Ticket::where('customer_id', $user->customer_id)->findOrFail($id);

        $data = $request->validate([
            'message' => 'required|string',
        ]);

        $message = TicketMessage::create([
            'ticket_id'      => $ticket->id,
            'sender_user_id' => $user->id,
            'message'        => $data['message'],
            'is_internal'    => false,
        ]);

        $ticket->update([
            'last_message_at' => now(),
            'status'          => $ticket->status === 'resolved' ? 'in_progress' : $ticket->status,
        ]);

        return response()->json($message, 201);
    }
}
