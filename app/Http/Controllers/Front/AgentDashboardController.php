<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgentDashboardController extends Controller
{
    /**
     * Get the list of clients managed by the current user (Agent or Director).
     */
    public function getClients(Request $request)
    {
        $user = $request->user('sanctum') ?? $request->user();
        
        // Determine if user is Director or Agent based on roles
        $isDirector = $user->hasRole('sales_director');
        $isAgent = $user->hasRole('sales_agent');

        if (!$isDirector && !$isAgent) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = Customer::query();

        if ($isDirector) {
            // Director: see clients where they are the director,
            // or clients of agents assigned under this director
            $agentIds = Customer::where('sales_director_user_id', $user->id)
                ->whereNotNull('agent_user_id')
                ->distinct()
                ->pluck('agent_user_id')
                ->all();

            $query->where(function ($q) use ($user, $agentIds) {
                $q->where('sales_director_user_id', $user->id)
                  ->orWhere('agent_user_id', $user->id);
                if (!empty($agentIds)) {
                    $q->orWhereIn('agent_user_id', $agentIds);
                }
            });
        } else {
            $query->where(function ($q) use ($user) {
                $q->where('agent_user_id', $user->id)
                  ->orWhereHas('teamMembers', function ($subQ) use ($user) {
                      $subQ->where('users.id', $user->id);
                  });
            });
        }

        $limit = (int) ($request->get('limit') ?? 20);
        $clients = $query->with(['group', 'users', 'agent'])
            ->orderBy('name')
            ->paginate(max(1, min($limit, 100)));

        return response()->json($clients);
    }

    /**
     * Get the list of agents assigned to the current Director.
     * Logic: Agents who manage customers that are also managed by this Director.
     */
    public function getAgents(Request $request)
    {
        $user = $request->user('sanctum') ?? $request->user();

        if (!$user->hasRole('sales_director')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Find agents who have customers assigned to this director
        $agentIds = Customer::where('sales_director_user_id', $user->id)
            ->whereNotNull('agent_user_id')
            ->pluck('agent_user_id');

        $query = User::query();
        if ($agentIds->isNotEmpty()) {
            $query->whereIn('id', $agentIds);
        } else {
            // Fallback demo: list all sales agents when none mapped yet
            $query->whereHas('roles', function ($q) {
                $q->where('slug', 'sales_agent');
            });
        }

        $agents = $query->withCount(['managedCustomers' => function ($q) use ($user) {
                $q->where('sales_director_user_id', $user->id);
            }])
            ->orderBy('first_name')
            ->get();

        return response()->json($agents);
    }

    /**
     * Get unpaid invoices for a client.
     */
    public function getClientInvoices(Request $request, $clientId)
    {
        $user = $request->user();
        $isDirector = $user->hasRole('sales_director');
        $isAgent = $user->hasRole('sales_agent');

        if (!$isDirector && !$isAgent) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $customer = Customer::findOrFail($clientId);

        // Access check
        $canManage = false;
        if ($isDirector) {
            if ($customer->sales_director_user_id == $user->id || $customer->agent_user_id == $user->id) {
                $canManage = true;
            }
        } elseif ($isAgent) {
            if ($customer->agent_user_id == $user->id) {
                $canManage = true;
            }
        }

        if (!$canManage) {
            return response()->json(['message' => 'Nu aveți permisiunea de a vizualiza facturile acestui client.'], 403);
        }

        $invoices = $customer->invoices()
            ->where('status', '!=', 'paid') // Assuming 'paid' is the status for fully paid invoices
            ->where('total', '>', 0) // Basic sanity check
            ->orderBy('due_date', 'asc')
            ->get()
            ->filter(function ($invoice) {
                return $invoice->balance > 0;
            })
            ->values();

        return response()->json($invoices);
    }

    /**
     * Get the active receipt book for the current user.
     */
    public function getActiveReceiptBook(Request $request)
    {
        $book = \App\Models\ReceiptBook::where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->first();

        if (!$book) {
            return response()->json(['message' => 'Nu aveți un chitanțier activ.'], 404);
        }

        return response()->json($book);
    }

    /**
     * Store a payment (Incasso) for a client.
     */
    public function storePayment(Request $request)
    {
        $user = $request->user();
        $isDirector = $user->hasRole('sales_director');
        $isAgent = $user->hasRole('sales_agent');

        if (!$isDirector && !$isAgent) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'instrument' => 'required|in:numerar,bo,cec', // Added instrument
            'type' => 'required|in:facturi,avans,valoare',
            'amount' => 'required|numeric|min:0.01',
            'selected_invoices' => 'array',
            'selected_invoices.*' => 'exists:invoices,id',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string',
            
            // BO/CEC fields
            'series' => 'nullable|string|max:20',
            'number' => 'nullable|string|max:50',
            'bank' => 'nullable|string|max:100',
            'due_date' => 'nullable|date',
            'customer_visit_id' => 'nullable|exists:customer_visits,id',
        ]);

        // Validation for BO/CEC
        if (in_array($validated['instrument'], ['bo', 'cec'])) {
            if (empty($validated['series']) || empty($validated['number']) || empty($validated['bank']) || empty($validated['due_date'])) {
                return response()->json(['message' => 'Pentru BO și CEC sunt obligatorii: Seria, Numărul, Banca și Data Scadenței.'], 422);
            }
        }

        // Verify that the user manages this customer
        $customer = Customer::findOrFail($validated['customer_id']);
        
        $canManage = false;
        if ($isDirector) {
            if ($customer->sales_director_user_id == $user->id || $customer->agent_user_id == $user->id) {
                $canManage = true;
            }
        } elseif ($isAgent) {
            if ($customer->agent_user_id == $user->id) {
                $canManage = true;
            }
        }

        if (!$canManage) {
            return response()->json(['message' => 'Nu aveți permisiunea de a încasa pentru acest client.'], 403);
        }

        // 1. Validate Receipt Book ONLY for Cash (Numerar)
        $receiptBook = null;
        if ($validated['instrument'] === 'numerar') {
            $receiptBook = \App\Models\ReceiptBook::where('user_id', $user->id)
                ->where('is_active', true)
                ->first();

            if (!$receiptBook) {
                return response()->json(['message' => 'Nu aveți un chitanțier activ asignat.'], 400);
            }

            if ($receiptBook->current_number > $receiptBook->end_number) {
                return response()->json(['message' => 'Chitanțierul curent este epuizat.'], 400);
            }
        }

        // 2. Process Logic based on Type (Allocation)
        $amount = $validated['amount'];
        $invoicesToAttach = collect();

        if ($validated['type'] === 'facturi') {
            if (empty($validated['selected_invoices'])) {
                return response()->json(['message' => 'Trebuie să selectați cel puțin o factură.'], 422);
            }

            $invoices = \App\Models\Invoice::whereIn('id', $validated['selected_invoices'])->get();
            $calculatedTotal = 0;

            foreach ($invoices as $invoice) {
                if ($invoice->balance <= 0) {
                    return response()->json(['message' => "Factura {$invoice->number} nu are sold pozitiv."], 422);
                }
                $calculatedTotal += $invoice->balance;
                $invoicesToAttach->push(['id' => $invoice->id, 'amount' => $invoice->balance]);
            }

            // Enforce calculated amount
            $amount = $calculatedTotal;

        } elseif ($validated['type'] === 'avans') {
            if (!empty($validated['selected_invoices'])) {
                return response()->json(['message' => 'Nu se pot selecta facturi pentru încasare tip Avans.'], 422);
            }
            // Amount is manually entered, no invoices attached.

        } elseif ($validated['type'] === 'valoare') {
            if (empty($validated['selected_invoices'])) {
                 return response()->json(['message' => 'Trebuie să selectați facturi pentru a acoperi valoarea încasată.'], 422);
            }

            $invoices = \App\Models\Invoice::whereIn('id', $validated['selected_invoices'])->get();
            $totalBalance = 0;

            foreach ($invoices as $invoice) {
                if ($invoice->balance <= 0) {
                     return response()->json(['message' => "Factura {$invoice->number} nu are sold pozitiv."], 422);
                }
                $totalBalance += $invoice->balance;
            }

            if ($totalBalance < $amount) {
                return response()->json(['message' => 'Suma soldurilor facturilor selectate este mai mică decât valoarea încasată.'], 422);
            }

            // Distribute amount
            $remaining = $amount;
            foreach ($invoices as $invoice) {
                if ($remaining <= 0) break;
                $toPay = min($remaining, $invoice->balance);
                $invoicesToAttach->push(['id' => $invoice->id, 'amount' => $toPay]);
                $remaining -= $toPay;
            }
        }

        // 3. Create Payment and Transactions
        $payment = DB::transaction(function () use ($user, $customer, $receiptBook, $amount, $validated, $invoicesToAttach) {
            
            $docNumber = '';
            if ($validated['instrument'] === 'numerar') {
                $docNumber = $receiptBook->series . ' ' . $receiptBook->current_number;
            } else {
                $docNumber = ($validated['series'] ?? '') . ' ' . ($validated['number'] ?? '');
            }

            $payment = Payment::create([
                'customer_id' => $customer->id,
                'recorded_by_user_id' => $user->id,
                'receipt_book_id' => $receiptBook ? $receiptBook->id : null,
                'type' => $validated['instrument'] === 'numerar' ? 'chs' : $validated['instrument'],
                'amount' => $amount,
                'currency' => $customer->currency ?? 'RON',
                'status' => $validated['instrument'] === 'numerar' ? 'completed' : 'collected', // BO/CEC are just collected
                'payment_date' => $validated['payment_date'],
                'document_number' => $docNumber,
                'series' => $validated['series'] ?? null,
                'number' => $validated['number'] ?? null,
                'bank' => $validated['bank'] ?? null,
                'due_date' => $validated['due_date'] ?? null,
                'notes' => ($validated['notes'] ? $validated['notes'] . ' ' : '') . "(Tip: " . ucfirst($validated['type']) . ")",
                'channel' => 'offline',
                'customer_visit_id' => $validated['customer_visit_id'] ?? null,
            ]);

            foreach ($invoicesToAttach as $inv) {
                $payment->invoices()->attach($inv['id'], ['amount' => $inv['amount']]);
            }

            if ($receiptBook) {
                $receiptBook->increment('current_number');
            }
            
            return $payment;
        });

        return response()->json([
            'message' => 'Chitanța a fost emisă cu succes.',
            'payment' => $payment
        ], 201);
    }

    /**
     * Mark the current receipt as cancelled (void).
     */
    public function cancelReceipt(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'notes' => 'nullable|string|max:255',
        ]);

        // Validate Receipt Book
        $receiptBook = \App\Models\ReceiptBook::where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (!$receiptBook) {
            return response()->json(['message' => 'Nu aveți un chitanțier activ asignat.'], 400);
        }

        if ($receiptBook->current_number > $receiptBook->end_number) {
            return response()->json(['message' => 'Chitanțierul curent este epuizat.'], 400);
        }

        DB::transaction(function () use ($user, $receiptBook, $validated) {
            $docNumber = $receiptBook->series . ' ' . $receiptBook->current_number;

            // Create a "cancelled" payment record
            Payment::create([
                'customer_id' => null, // Optional if general cancellation, or could link to dummy
                'recorded_by_user_id' => $user->id,
                'receipt_book_id' => $receiptBook->id,
                'type' => 'chs',
                'amount' => 0,
                'currency' => 'RON',
                'status' => 'cancelled',
                'payment_date' => now(),
                'document_number' => $docNumber,
                'notes' => "ANULAT. Motiv: " . $validated['notes'],
                'channel' => 'offline',
            ]);

            $receiptBook->increment('current_number');
        });

        return response()->json(['message' => 'Chitanța a fost anulată.']);
    }

    public function getRoutes(Request $request)
    {
        $user = $request->user();
        
        $agentId = $user->id;
        if ($request->has('agent_id') && $user->hasRole('sales_director')) {
             $requestedAgentId = $request->get('agent_id');
             
             // Verify that the requested agent is a subordinate of this director
             $isSubordinate = User::where('id', $requestedAgentId)
                ->where('director_id', $user->id)
                ->exists();

             if ($isSubordinate || $requestedAgentId == $user->id) {
                 $agentId = $requestedAgentId;
             } else {
                 return response()->json(['message' => 'Unauthorized access to this agent route'], 403);
             }
        }

        $routes = \App\Models\AgentRoute::where('agent_id', $agentId)
            ->with(['customer' => function($q) use ($agentId) {
                // Check if visited today by this agent
                $q->withCount(['visits' => function($q2) use ($agentId) {
                    $q2->where('agent_id', $agentId)
                       ->whereDate('start_time', now()->toDateString())
                       ->where('status', '!=', 'cancelled');
                }]);
            }])
            ->orderBy('sort_order')
            ->get();

        return response()->json($routes);
    }
}
