<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $query = Ticket::with(['customer', 'createdBy', 'assignedTo']);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($category = $request->get('category')) {
            $query->where('category', $category);
        }

        if ($customerId = $request->get('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        return $query->orderByDesc('last_message_at')->paginate(25);
    }

    public function show(Ticket $ticket)
    {
        return $ticket->load('customer', 'createdBy', 'assignedTo', 'messages.sender');
    }

    public function update(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'status'              => ['sometimes', 'string', 'max:50'],
            'priority'            => ['sometimes', 'string', 'max:50'],
            'assigned_to_user_id' => ['sometimes', 'nullable', 'integer'],
        ]);

        $ticket->update($data);

        return response()->json($ticket->load('assignedTo'));
    }
}
