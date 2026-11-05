<?php

declare(strict_types=1);

namespace App\Reports;

use App\Models\AccountPayments;
use App\Models\AccountsPayable;
use App\Models\AccountsReceivable;
use App\Models\BankAccount;
use App\Models\Brand;
use App\Models\CashClosing;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\EmployeeDamage;
use App\Models\EmployeeLoan;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Inventory;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Receipt;
use App\Models\SaleOrder;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class ExportRegistry
{
    /**
     * @return array<string, string>
     */
    public static function catalog(): array
    {
        return [
            'inventory.products' => 'Productos',
            'inventory.categories' => 'Categorías',
            'inventory.brands' => 'Marcas',
            'inventory.warehouses' => 'Almacenes',
            'inventory.adjustments' => 'Ajustes de inventario',
            'sales.customers' => 'Clientes',
            'sales.invoices' => 'Facturas de venta',
            'sales.quotes' => 'Cotizaciones',
            'sales.returns' => 'Devoluciones de venta',
            'purchases.invoices' => 'Facturas de compra',
            'purchases.returns' => 'Devoluciones de compra',
            'purchases.suppliers' => 'Proveedores',
            'expenses.expenses' => 'Gastos',
            'expenses.categories' => 'Categorías de gastos',
            'payroll.employees' => 'Empleados',
            'payroll.periods' => 'Períodos de nómina',
            'payroll.payrolls' => 'Nóminas',
            'payroll.loans' => 'Préstamos',
            'payroll.damages' => 'Daños',
            'finance.accounts' => 'Cuentas bancarias',
            'finance.cash_closing' => 'Cierres de caja',
            'sales.receipts' => 'Cobros',
            'purchases.payments' => 'Pagos a proveedores',
            'sales.receivables' => 'Cuentas por cobrar',
            'purchases.payables' => 'Cuentas por pagar',
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public static function make(string $key, array $filters = []): ExportDefinition
    {
        return match ($key) {
            'inventory.products' => self::simple('inventory.products', 'Productos', 'productos', Product::class, $filters,
                ['pro_code', 'pro_name', 'pro_und', 'pro_cant_min', 'pro_cat_id', 'pro_bra_id'],
                ['Código', 'Nombre', 'Unidad', 'Cant. mínima', 'Categoría', 'Marca'], ['pro_code', 'pro_name']),
            'inventory.categories' => self::simple('inventory.categories', 'Categorías', 'categorias', Category::class, $filters,
                ['id', 'cat_name'], ['ID', 'Nombre'], ['cat_name']),
            'inventory.brands' => self::simple('inventory.brands', 'Marcas', 'marcas', Brand::class, $filters,
                ['id', 'bra_name'], ['ID', 'Nombre'], ['bra_name']),
            'inventory.warehouses' => self::simple('inventory.warehouses', 'Almacenes', 'almacenes', Warehouse::class, $filters,
                ['id', 'war_name'], ['ID', 'Nombre'], ['war_name']),
            'inventory.adjustments' => self::simple('inventory.adjustments', 'Ajustes de inventario', 'ajustes-inventario', Inventory::class, $filters),
            'sales.customers' => self::simple('sales.customers', 'Clientes', 'clientes', Customer::class, $filters,
                ['id', 'clt_code', 'clt_cedula', 'clt_name', 'clt_phone', 'clt_email'],
                ['ID', 'Código', 'Cédula', 'Nombre', 'Teléfono', 'Email'], ['clt_name', 'clt_cedula', 'clt_email']),
            'sales.invoices' => self::documents('sales.invoices', 'Facturas de venta', 'facturas-venta', SaleOrder::class, 'sls_trans_type_code', 'FACV', $filters),
            'sales.quotes' => self::documents('sales.quotes', 'Cotizaciones', 'cotizaciones', SaleOrder::class, 'sls_trans_type_code', 'COT', $filters),
            'sales.returns' => self::documents('sales.returns', 'Devoluciones de venta', 'devoluciones-venta', SaleOrder::class, 'sls_trans_type_code', 'DEVV', $filters),
            'purchases.invoices' => self::documents('purchases.invoices', 'Facturas de compra', 'facturas-compra', PurchaseOrder::class, 'invoice_trans_type_code', 'FAC', $filters),
            'purchases.returns' => self::documents('purchases.returns', 'Devoluciones de compra', 'devoluciones-compra', PurchaseOrder::class, 'invoice_trans_type_code', 'DEVC', $filters),
            'purchases.suppliers' => self::simple('purchases.suppliers', 'Proveedores', 'proveedores', Supplier::class, $filters,
                ['id', 'supplier_name', 'supplier_phone', 'supplier_email'],
                ['ID', 'Nombre', 'Teléfono', 'Email'], ['supplier_name', 'supplier_email']),
            'expenses.expenses' => self::simple('expenses.expenses', 'Gastos', 'gastos', Expense::class, $filters),
            'expenses.categories' => self::simple('expenses.categories', 'Categorías de gastos', 'categorias-gastos', ExpenseCategory::class, $filters),
            'payroll.employees' => self::simple('payroll.employees', 'Empleados', 'empleados', Employee::class, $filters),
            'payroll.periods' => self::simple('payroll.periods', 'Períodos de nómina', 'periodos-nomina', PayrollPeriod::class, $filters),
            'payroll.payrolls' => self::simple('payroll.payrolls', 'Nóminas', 'nominas', Payroll::class, $filters),
            'payroll.loans' => self::simple('payroll.loans', 'Préstamos', 'prestamos', EmployeeLoan::class, $filters),
            'payroll.damages' => self::simple('payroll.damages', 'Daños', 'danos', EmployeeDamage::class, $filters),
            'finance.accounts' => self::simple('finance.accounts', 'Cuentas bancarias', 'cuentas-bancarias', BankAccount::class, $filters),
            'finance.cash_closing' => self::simple('finance.cash_closing', 'Cierres de caja', 'cierres-caja', CashClosing::class, $filters),
            'sales.receipts' => self::simple('sales.receipts', 'Cobros', 'cobros', Receipt::class, $filters),
            'purchases.payments' => self::simple('purchases.payments', 'Pagos a proveedores', 'pagos-proveedores', AccountPayments::class, $filters),
            'sales.receivables' => self::simple('sales.receivables', 'Cuentas por cobrar', 'cuentas-por-cobrar', AccountsReceivable::class, $filters),
            'purchases.payables' => self::simple('purchases.payables', 'Cuentas por pagar', 'cuentas-por-pagar', AccountsPayable::class, $filters),
        };
    }

    /**
     * @param  class-string<Model>  $model
     * @param  array<string, mixed>  $filters
     * @param  list<string>  $columns
     * @param  list<string>  $labels
     * @param  list<string>  $searchColumns
     */
    private static function simple(
        string $key,
        string $title,
        string $filename,
        string $model,
        array $filters,
        array $columns = [],
        array $labels = [],
        array $searchColumns = [],
    ): ExportDefinition {
        return new ModelExport(
            key: $key,
            title: $title,
            filename: $filename,
            modelClass: $model,
            columns: $columns,
            labels: $labels === [] ? [] : array_combine($columns, $labels),
            filters: $filters,
            applyFilters: function (Builder $query, array $filters) use ($searchColumns): void {
                if ($searchColumns === [] || empty($filters['search'])) {
                    return;
                }

                $search = (string) $filters['search'];
                $query->where(function (Builder $inner) use ($searchColumns, $search): void {
                    foreach ($searchColumns as $column) {
                        $inner->orWhere($column, 'like', "%{$search}%");
                    }
                });
            },
        );
    }

    /**
     * @param  class-string<Model>  $model
     * @param  array<string, mixed>  $filters
     */
    private static function documents(
        string $key,
        string $title,
        string $filename,
        string $model,
        string $typeColumn,
        string $type,
        array $filters,
    ): ExportDefinition {
        return new ModelExport(
            key: $key,
            title: $title,
            filename: $filename,
            modelClass: $model,
            filters: $filters,
            applyFilters: function (Builder $query, array $filters) use ($typeColumn, $type): void {
                $query->where($typeColumn, $type);

                if (! empty($filters['search'])) {
                    $numberColumn = $typeColumn === 'sls_trans_type_code' ? 'sls_number' : 'invoice_number';
                    $query->where($numberColumn, 'like', '%'.$filters['search'].'%');
                }
            },
        );
    }
}
