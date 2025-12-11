<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderApprovalController extends Controller
{
    protected function ensureApprover(Request $request): void
    {
        $user = $request->user();

        if (!$user->customer || $user->customer->type !== 'b2b' || !$user->canApproveCompanyOrders()) {
            abort(403, 'Nu ai drepturi de aprobare comenzi la nivel de companie.');
        }
    }

    public function pending(Request $request)
    {
        $this->ensureApprover($request);
        $user = $request->user();

        $query = Order::where('customer_id', $user->customer_id)
            ->where('approval_status', 'pending')
            ->orderByDesc('placed_at');

        return $query->paginate(20);
    }

    public function approve($orderId, Request $request)
    {
        $this->ensureApprover($request);
        $approver = $request->user();

        return DB::transaction(function () use ($orderId, $approver) {
            /** @var Order $order */
            $order = Order::lockForUpdate()->where('id', $orderId)
                ->where('customer_id', $approver->customer_id)
                ->firstOrFail();

            if ($order->approval_status !== 'pending') {
                return response()->json(['message' => 'Comanda nu este în status pending_approval.'], 422);
            }

            $customer = $order->customer;

            // verificăm credit B2B la aprobare
            if ($customer->type === 'b2b' && $customer->credit_limit > 0) {
                $futureBalance = ($customer->current_balance ?? 0) + $order->grand_total;

                if ($futureBalance > $customer->credit_limit) {
                    // depășire credit – respingem comanda
                    $order->approval_status = 'rejected';
                    $order->status          = 'credit_blocked';
                    $order->credit_blocked  = true;
                    $order->approved_by_user_id = $approver->id;
                    $order->approved_at     = now();
                    $order->save();

                    return response()->json([
                        'message' => 'Limita de credit a fost depășită. Comanda nu poate fi aprobată.',
                        'order'   => $order,
                    ], 422);
                }

                // actualizăm soldul clientului
                $customer->current_balance = $futureBalance;
                $customer->save();
            }

            $order->approval_status     = 'approved';
            $order->status              = 'pending'; // sau alt status operational
            $order->approved_by_user_id = $approver->id;
            $order->approved_at         = now();
            $order->save();

            // TODO: aici poți declanșa sync în ERP, ex:
            // SyncOrderToErp::dispatch($order);

            return response()->json($order, 200);
        });
    }

    public function reject($orderId, Request $request)
    {
        $this->ensureApprover($request);
        $approver = $request->user();

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var Order $order */
        $order = Order::where('id', $orderId)
            ->where('customer_id', $approver->customer_id)
            ->firstOrFail();

        if ($order->approval_status !== 'pending') {
            return response()->json(['message' => 'Comanda nu este în status pending_approval.'], 422);
        }

        $order->approval_status     = 'rejected';
        $order->status              = 'rejected';
        $order->approved_by_user_id = $approver->id;
        $order->approved_at         = now();
        $order->cancel_reason       = $data['reason'] ?? 'Respinsă de aprobator';
        $order->cancelled_at        = now();
        $order->save();

        // dacă ai vreo rezervare de stoc sau alt efect, îl poți elibera aici

        return response()->json($order, 200);
    }
}
