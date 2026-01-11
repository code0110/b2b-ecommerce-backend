<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\FinancialRiskSetting;
use App\Models\Invoice;
use Carbon\Carbon;

use App\Models\FinancialRiskLog;
use Illuminate\Support\Facades\Request;

class FinancialRiskService
{
    public const STATUS_SAFE = 'safe';
    public const STATUS_WARNING = 'warning';
    public const STATUS_APPROVAL_REQUIRED = 'approval_required';
    public const STATUS_BLOCKED = 'blocked';

    /**
     * Calculate the financial risk status for a customer.
     *
     * @param Customer $customer
     * @return array
     */
    public function calculateRisk(Customer $customer): array
    {
        $settings = FinancialRiskSetting::getSettings();

        // Get unpaid invoices (assuming status 'unpaid' or check balance)
        // In a real scenario, you might filter by status != 'paid' and balance > tolerance
        $unpaidInvoices = $customer->invoices()
            ->whereIn('status', ['unpaid', 'partial'])
            ->where('due_date', '<', now())
            ->get();

        $unpaidCount = $unpaidInvoices->count();
        $maxOverdueDays = 0;

        foreach ($unpaidInvoices as $invoice) {
            $overdueDays = Carbon::parse($invoice->due_date)->diffInDays(now(), false);
            if ($overdueDays > $maxOverdueDays) {
                $maxOverdueDays = $overdueDays;
            }
        }

        $status = self::STATUS_SAFE;
        $messages = [];

        // Check Block Thresholds
        if ($unpaidCount >= $settings->block_threshold_invoices) {
            $status = self::STATUS_BLOCKED;
            $messages[] = "Clientul are {$unpaidCount} facturi restante (Limita blocare: {$settings->block_threshold_invoices}).";
        }
        if ($maxOverdueDays >= $settings->block_threshold_days) {
            $status = self::STATUS_BLOCKED;
            $messages[] = "Clientul are întârzieri de {$maxOverdueDays} zile (Limita blocare: {$settings->block_threshold_days} zile).";
        }

        // If not blocked, check Approval
        if ($status !== self::STATUS_BLOCKED) {
            if ($unpaidCount >= $settings->approval_threshold_invoices) {
                $status = self::STATUS_APPROVAL_REQUIRED;
                $messages[] = "Clientul are {$unpaidCount} facturi restante (Necesită aprobare).";
            }
            if ($maxOverdueDays >= $settings->approval_threshold_days) {
                $status = self::STATUS_APPROVAL_REQUIRED;
                $messages[] = "Clientul are întârzieri de {$maxOverdueDays} zile (Necesită aprobare).";
            }
        }

        // If still safe, check Warning
        if ($status === self::STATUS_SAFE) {
            if ($unpaidCount >= $settings->warning_threshold_invoices) {
                $status = self::STATUS_WARNING;
                $messages[] = "Atenție: {$unpaidCount} facturi restante.";
            }
            if ($maxOverdueDays >= $settings->warning_threshold_days) {
                $status = self::STATUS_WARNING;
                $messages[] = "Atenție: Întârzieri de {$maxOverdueDays} zile.";
            }
        }

        $hasDerogation = $customer->financial_derogation_until && $customer->financial_derogation_until->isFuture();
        if ($hasDerogation) {
             $messages[] = "Derogare financiară activă până la " . $customer->financial_derogation_until->format('d.m.Y H:i');
             // Downgrade status if blocked or approval required
             if (in_array($status, [self::STATUS_BLOCKED, self::STATUS_APPROVAL_REQUIRED])) {
                 $status = self::STATUS_WARNING;
             }
        }

        return [
            'status' => $status,
            'messages' => $messages,
            'is_derogated' => $hasDerogation,
            'details' => [
                'unpaid_count' => $unpaidCount,
                'max_overdue_days' => $maxOverdueDays,
                'total_balance' => $customer->current_balance,
                'credit_limit' => $customer->credit_limit,
                'credit_remaining' => $customer->credit_limit - $customer->current_balance
            ]
        ];
    }

    public function grantDerogation(Customer $customer, $user, Carbon $until, ?string $reason = null)
    {
        $customer->financial_derogation_until = $until;
        $customer->save();

        $this->logAction($customer, 'derogation_granted', [
            'until' => $until->toDateTimeString(),
            'reason' => $reason
        ], $user);

        return $customer;
    }

    public function acknowledgeRisk(Customer $customer, $user)
    {
        $this->logAction($customer, 'notification_acknowledged', [], $user);
    }

    public function logAction(Customer $customer, string $action, ?array $details = [], $user = null)
    {
        FinancialRiskLog::create([
            'customer_id' => $customer->id,
            'user_id' => $user ? $user->id : null,
            'action' => $action,
            'details' => $details,
            'ip_address' => Request::ip(),
        ]);
    }
}
