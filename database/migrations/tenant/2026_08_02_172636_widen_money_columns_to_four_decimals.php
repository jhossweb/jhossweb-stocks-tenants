<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Columnas monetarias ensanchadas a numeric(15,4) para trabajar a 4 decimales.
     * Cada columna define: [nullable, default].
     *
     * @var array<string, array<string, array{0: bool, 1: int|float|null}>>
     */
    private array $columns = [
        'employees' => [
            'emp_base_salary' => [false, null],
        ],
        'expenses' => [
            'exp_amount' => [false, null],
            'exp_paid_amount' => [false, 0],
            'exp_remaining_balance' => [false, 0],
            'exp_valorcam' => [false, 1],
        ],
        'employee_loans' => [
            'elo_amount' => [false, null],
            'elo_remaining_balance' => [false, null],
            'elo_valorcam' => [false, 1],
        ],
        'employee_damages' => [
            'eda_amount' => [false, null],
            'eda_remaining_balance' => [false, null],
            'eda_valorcam' => [false, 1],
        ],
        'payrolls' => [
            'pay_base_salary' => [false, null],
            'pay_total_deductions' => [false, 0],
            'pay_net_salary' => [false, null],
            'pay_valorcam' => [false, 1],
        ],
        'payroll_deductions' => [
            'pde_amount' => [false, null],
        ],
        'sale_orders' => [
            'sls_valorcam' => [false, null],
            'sls_subtotal' => [false, null],
            'sls_exento' => [false, null],
            'sls_iva' => [false, null],
            'sls_total' => [false, null],
        ],
        'purchase_orders' => [
            'invoice_valorcam' => [false, null],
            'invoice_subtotal' => [false, null],
            'invoice_exento' => [false, null],
            'invoice_iva' => [false, null],
            'invoice_total' => [false, null],
        ],
        'rates' => [
            'rate_usd' => [false, 0],
            'rate_cop' => [false, 0],
            'rate_eur' => [false, 0],
            'rate_cop_bs' => [false, 0],
        ],
        'accounts_receivable' => [
            'acr_total' => [false, null],
            'acr_balance' => [false, null],
            'acr_total_usd' => [true, null],
            'acr_balance_usd' => [true, null],
        ],
        'accounts_payables' => [
            'acp_total' => [false, null],
            'acp_balance' => [false, null],
            'acp_total_usd' => [true, null],
            'acp_balance_usd' => [true, null],
        ],
        'movements_sales' => [
            'mvs_unit_price' => [false, 0],
            'mvs_unit_price_ref' => [false, 0],
        ],
        'movements_purchases' => [
            'mov_unit_cost' => [false, 0],
            'mov_unit_cost_ref' => [false, 0],
        ],
        'cost_histories' => [
            'cost_pro_cost' => [false, null],
            'cost_pro_cost_ref' => [false, null],
            'cost_valorcam' => [false, null],
            'cost_valorcambio' => [false, 0],
        ],
        'price_histories' => [
            'price_usd' => [false, null],
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->columns as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                foreach ($columns as $column => [$nullable, $default]) {
                    $definition = $blueprint->decimal($column, 15, 4)->nullable($nullable);

                    if ($default !== null) {
                        $definition->default($default);
                    }

                    $definition->change();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->columns as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                foreach ($columns as $column => [$nullable, $default]) {
                    $definition = $blueprint->decimal($column, 10, 4)->nullable($nullable);

                    if ($default !== null) {
                        $definition->default($default);
                    }

                    $definition->change();
                }
            });
        }
    }
};
