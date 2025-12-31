<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReceiptBook;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReceiptBookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ReceiptBook::with('user');

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $receiptBooks = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($receiptBooks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'series' => 'required|string|max:10',
            'start_number' => 'required|integer|min:1',
        ]);

        $receiptBook = ReceiptBook::create([
            'user_id' => $validated['user_id'],
            'series' => $validated['series'],
            'start_number' => $validated['start_number'],
            'end_number' => $validated['start_number'] + 49,
            'current_number' => $validated['start_number'],
            'is_active' => true,
        ]);

        return response()->json($receiptBook, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ReceiptBook $receiptBook)
    {
        return response()->json($receiptBook->load('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ReceiptBook $receiptBook)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'series' => 'sometimes|required|string|max:10',
            'is_active' => 'boolean',
        ]);

        $receiptBook->update($validated);

        return response()->json($receiptBook);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ReceiptBook $receiptBook)
    {
        // Check if the receipt book has been used
        if ($receiptBook->current_number > $receiptBook->start_number) {
            return response()->json(['message' => 'Cannot delete a receipt book that has already been used.'], 409);
        }

        $receiptBook->delete();

        return response()->json(null, 204);
    }

    /**
     * Get a list of potential agents/directors for assignment.
     */
    public function getAgents()
    {
        $users = User::whereHas('roles', function ($query) {
            $query->whereIn('slug', ['sales_agent', 'sales_director', 'admin']);
        })->get(['id', 'first_name', 'last_name', 'email']);

        return response()->json($users);
    }
}
