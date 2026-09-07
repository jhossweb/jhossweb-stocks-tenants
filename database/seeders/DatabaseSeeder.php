<?php

namespace Database\Seeders;

use App\Models\AccountPayments;
use App\Models\AccountsPayable;
use App\Models\AdjustmentReason;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\MovementsPurchase;
use App\Models\Product;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call(CompanySeeder::class);
        $this->call(BrandSeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(WarehouseSeeder::class);
        $this->call(TransactionTypeSeeder::class);
        Product::factory()->count(100)->create();
        Supplier::factory()->count(10)->create();

        AdjustmentReason::create([
            'adjr_code' => 'MERMA',
            'adjr_name' => 'Merma',
            'adjr_description' => 'Pérdida de producto por merma',
        ]);

        AdjustmentReason::create([
            'adjr_code' => 'DETERIORO',
            'adjr_name' => 'Deterioro',
            'adjr_description' => 'Producto dañado o en mal estado',
        ]);

        AdjustmentReason::create([
            'adjr_code' => 'VENCIDO',
            'adjr_name' => 'Vencido',
            'adjr_description' => 'Producto vencido',
        ]);

        $purchaseOrders = PurchaseOrder::factory()->count(10)->create();
        foreach ($purchaseOrders as $purchaseOrder) {
            MovementsPurchase::factory()->count(50)->create([
                'mov_invoice_number' => $purchaseOrder->invoice_number,
                'mov_invoice_trans_type_code' => $purchaseOrder->invoice_trans_type_code,
                'mov_factor' => fake()->randomElement(['1', '-1']),
                'mov_pro_code' => Product::inRandomOrder()->first()->pro_code,
                'mov_pro_iva' => fake()->numberBetween(0, 16),
                'mov_war_id' => Warehouse::inRandomOrder()->first()->id,
                'mov_quantity' => fake()->numberBetween(1, 100),
                'mov_unit_cost' => fake()->numberBetween(1, 100),
                'mov_unit_cost_ref' => fake()->numberBetween(1, 100),
            ]);
        }

        // Seed Expense Categories
        $this->call(ExpenseCategorySeeder::class);

        // Seed Bank Accounts
        $bankAccounts = BankAccount::factory()->count(3)->create();

        // Seed Accounts Payable for existing Purchase Orders
        foreach ($purchaseOrders as $order) {
            $payable = AccountsPayable::create([
                'acp_purchase_number' => $order->invoice_number,
                'acp_total' => $order->invoice_total,
                'acp_balance' => $order->invoice_total,
                'acp_status' => 'pending',
            ]);

            // Create a payment for some of them (50% chance)
            if (fake()->boolean(50)) {
                $transaction = BankTransaction::factory()->create([
                    'btr_bank_account_id' => $bankAccounts->random()->id,
                    'btr_type' => 'outflow',
                    'btr_amount' => $order->invoice_total,
                    'btr_description' => 'Pago de factura '.$order->invoice_number,
                    'btr_valorcam' => $order->invoice_valorcam,
                ]);

                $transaction->btr_transactionable()->associate($payable);
                $transaction->save();

                AccountPayments::create([
                    'apy_bank_transaction_id' => $transaction->id,
                    'apy_account_payable_id' => $payable->id,
                    'apy_type' => 'PAYMENT',
                ]);

                $payable->update([
                    'acp_balance' => 0,
                    'acp_status' => 'paid',
                ]);
            }
        }

    }
}
