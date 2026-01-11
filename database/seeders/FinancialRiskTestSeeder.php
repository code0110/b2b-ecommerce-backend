<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\User;
use App\Models\Invoice;
use App\Models\FinancialRiskSetting;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class FinancialRiskTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure settings exist
        $settings = FinancialRiskSetting::getSettings();
        $this->command->info('Financial Risk Settings ensured.');

        // 2. Create or Update "Client B2B Risc"
        $customerEmail = 'client.b2b.risc@example.com';
        
        $customer = Customer::firstOrCreate(
            ['email' => $customerEmail],
            [
                'name' => 'Client B2B Risc (Blocked)',
                'legal_name' => 'SC Client Risc SRL',
                'cif' => 'RO12345678',
                'reg_com' => 'J40/1234/2023',
                'phone' => '0700111222',
                'type' => 'b2b',
                'is_active' => true,
                'payment_terms_days' => 30,
                'credit_limit' => 10000,
                'current_balance' => 0,
            ]
        );

        // 3. Create User for this Customer
        $user = User::firstOrCreate(
            ['email' => $customerEmail],
            [
                'first_name' => 'User',
                'last_name' => 'Client Risc',
                'password' => Hash::make('password'),
                'customer_id' => $customer->id,
            ]
        );
        
        // Assign role if needed (assuming 'customer' role exists based on other seeders)
        // $user->assignRole('customer'); // Uncomment if spatie/laravel-permission is used and seeded

        $this->command->info("Customer created: {$customer->name}");
        $this->command->info("User created: {$user->email} / password");

        // 4. Create Overdue Invoices to Trigger Blocked Status
        // Thresholds (default): Block > 5 invoices OR > 30 days overdue
        
        // Delete existing invoices for this customer to have a clean slate for testing
        Invoice::where('customer_id', $customer->id)->delete();

        // Create 6 invoices (triggers quantity block)
        // Made them 45 days overdue (triggers days block)
        $overdueDays = 45;
        $numInvoices = 6;
        $totalDebt = 0;

        for ($i = 1; $i <= $numInvoices; $i++) {
            $amount = 1000 * $i;
            Invoice::create([
                'customer_id' => $customer->id,
                'series' => 'RISK',
                'number' => '100' . $i,
                'type' => 'invoice',
                'status' => 'unpaid', // key for risk calculation
                'issue_date' => Carbon::now()->subDays($overdueDays + 30), // Issued long ago
                'due_date' => Carbon::now()->subDays($overdueDays), // Due 45 days ago
                'subtotal' => $amount,
                'tax_total' => $amount * 0.19,
                'total' => $amount * 1.19,
                'currency' => 'RON',
            ]);
            $totalDebt += $amount * 1.19;
        }

        // Update customer balance
        $customer->update(['current_balance' => $totalDebt]);

        $this->command->info("Created {$numInvoices} overdue invoices ({$overdueDays} days late).");
        $this->command->info("Total debt: {$totalDebt} RON.");
        $this->command->info("This customer should now be BLOCKED by FinancialRiskService.");

        // --- Create a "Warning" / "Approval" Customer ---
        $approvalEmail = 'client.b2b.approval@example.com';
        $customerApproval = Customer::firstOrCreate(
            ['email' => $approvalEmail],
            [
                'name' => 'Client B2B Approval',
                'legal_name' => 'SC Client Approval SRL',
                'cif' => 'RO87654321',
                'reg_com' => 'J40/4321/2023',
                'phone' => '0700222333',
                'type' => 'b2b',
                'is_active' => true,
                'payment_terms_days' => 30,
                'credit_limit' => 10000,
                'current_balance' => 0,
            ]
        );

        User::firstOrCreate(
            ['email' => $approvalEmail],
            [
                'first_name' => 'User',
                'last_name' => 'Client Approval',
                'password' => Hash::make('password'),
                'customer_id' => $customerApproval->id,
            ]
        );

        Invoice::where('customer_id', $customerApproval->id)->delete();

        // Create 3 invoices (Approval threshold is 3 usually)
        // 20 days overdue (Approval threshold is 15 usually)
        $approvalOverdueDays = 20;
        $approvalInvoices = 3;
        $approvalDebt = 0;

        for ($i = 1; $i <= $approvalInvoices; $i++) {
            $amount = 500 * $i;
            Invoice::create([
                'customer_id' => $customerApproval->id,
                'series' => 'APPR',
                'number' => '200' . $i,
                'type' => 'invoice',
                'status' => 'unpaid',
                'issue_date' => Carbon::now()->subDays($approvalOverdueDays + 30),
                'due_date' => Carbon::now()->subDays($approvalOverdueDays),
                'subtotal' => $amount,
                'tax_total' => $amount * 0.19,
                'total' => $amount * 1.19,
                'currency' => 'RON',
            ]);
            $approvalDebt += $amount * 1.19;
        }
        $customerApproval->update(['current_balance' => $approvalDebt]);
        
        $this->command->info("Created Approval customer: {$approvalEmail} / password");
    }
}
