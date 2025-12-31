<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 100, 5000);
        $tax = $subtotal * 0.19;
        $total = $subtotal + $tax;
        $issueDate = $this->faker->dateTimeBetween('-2 months', 'now');

        return [
            'customer_id' => null, // Should be provided by seeder
            'order_id' => null,
            'erp_id' => 'ERP-' . $this->faker->unique()->numberBetween(10000, 99999),
            'type' => 'fiscal',
            'series' => 'FACT',
            'number' => $this->faker->unique()->numberBetween(1000, 99999),
            'status' => $this->faker->randomElement(['unpaid', 'partial', 'overdue']),
            'issue_date' => $issueDate,
            'due_date' => $this->faker->dateTimeBetween($issueDate, '+30 days'),
            'subtotal' => $subtotal,
            'tax_total' => $tax,
            'total' => $total,
            'currency' => 'RON',
            'pdf_url' => null,
        ];
    }
}
