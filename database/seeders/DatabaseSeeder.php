<?php

namespace Database\Seeders;

use App\Models\AccountPayments;
use App\Models\AccountsPayable;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Brand;
use App\Models\Category;
use App\Models\MovementsPurchase;
use App\Models\Product;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\TransactionType;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\ExpenseCategorySeeder;
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

        Brand::factory()->count(2)->create();
        Category::factory()->count(2)->create();
        Warehouse::factory()->count(2)->create();
        Product::factory()->count(100)->create();
        Supplier::factory()->count(10)->create();
        TransactionType::create(
            [
                'trans_type_code' => 'FAC',
                'trans_type_description' => 'FACTURA COMPRA',
                'trans_type_factor' => '1',
            ]
        );

        TransactionType::create(
            [
                'trans_type_code' => 'DEVC',
                'trans_type_description' => 'DEVOLUCION COMPRA',
                'trans_type_factor' => '-1',
            ]
        );

        TransactionType::create(
            [
                'trans_type_code' => 'FACV',
                'trans_type_description' => 'FACTURA VENTA',
                'trans_type_factor' => '-1',
            ]
        );

        TransactionType::create(
            [
                'trans_type_code' => 'DEVV',
                'trans_type_description' => 'DEVOLUCION VENTA',
                'trans_type_factor' => '1',
            ]
        );

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
