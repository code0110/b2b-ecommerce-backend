<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = Customer::all();

        if ($customers->isEmpty()) {
            $this->command->info('No customers found. Skipping invoice seeding.');
            return;
        }

        foreach ($customers as $customer) {
            // Create 3 unpaid invoices (fully due)
            Invoice::factory()->count(3)->create([
                'customer_id' => $customer->id,
                'status' => 'unpaid',
            ]);

            // Create 2 paid invoices (fully paid - balance 0)
            // For simplicity in factory, we didn't implement payments, 
            // but we can simulate paid status if logic depends only on status field 
            // OR we can skip creating paid ones if the goal is to test payments.
            // Let's create 'paid' ones but technically they might have balance > 0 if we don't add payments.
            // However, the frontend filters by balance > 0. 
            // If I want to verify "paid" invoices don't show up, I should ensure their balance is 0.
            // But balance is calculated as total - payments.
            // So to make a "paid" invoice effectively paid in this system, I need to create a payment for it.
            // For now, I'll just create "unpaid" ones which are the most useful for testing "Încasare".
            
            // Create 1 overdue invoice
             Invoice::factory()->create([
                'customer_id' => $customer->id,
                'status' => 'overdue',
                'due_date' => now()->subDays(10),
            ]);
        }

        $this->command->info('Invoices seeded successfully for ' . $customers->count() . ' customers.');
    }
}
