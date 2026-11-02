<?php

declare(strict_types=1);

namespace App\Enum\Tenancy;

enum TenantLimit: string
{
    case SalesInvoicesMonthly = 'sales.invoices_monthly';
    case SalesQuotesMonthly = 'sales.quotes_monthly';
    case SalesReturnsMonthly = 'sales.returns_monthly';
    case PurchasesReturnsMonthly = 'purchases.returns_monthly';
    case CustomersMaxTotal = 'customers.max_total';
    case SuppliersMaxTotal = 'suppliers.max_total';
    case WarehousesMaxTotal = 'warehouses.max_total';
    case ProductsMaxTotal = 'products.max_total';

    public function label(): string
    {
        return match ($this) {
            self::SalesInvoicesMonthly => 'Facturas de venta mensuales',
            self::SalesQuotesMonthly => 'Cotizaciones mensuales',
            self::SalesReturnsMonthly => 'Devoluciones de venta mensuales',
            self::PurchasesReturnsMonthly => 'Devoluciones de compra mensuales',
            self::CustomersMaxTotal => 'Clientes',
            self::SuppliersMaxTotal => 'Proveedores',
            self::WarehousesMaxTotal => 'Almacenes',
            self::ProductsMaxTotal => 'Productos',
        };
    }

    public function scope(): LimitScope
    {
        return match ($this) {
            self::SalesInvoicesMonthly,
            self::SalesQuotesMonthly,
            self::SalesReturnsMonthly,
            self::PurchasesReturnsMonthly => LimitScope::Monthly,
            self::CustomersMaxTotal,
            self::SuppliersMaxTotal,
            self::WarehousesMaxTotal,
            self::ProductsMaxTotal => LimitScope::Total,
        };
    }

    public function isMonthly(): bool
    {
        return $this->scope() === LimitScope::Monthly;
    }

    public function groupKey(): string
    {
        return match ($this) {
            self::SalesInvoicesMonthly,
            self::SalesQuotesMonthly,
            self::SalesReturnsMonthly => 'sales',
            self::PurchasesReturnsMonthly => 'purchases',
            self::CustomersMaxTotal,
            self::SuppliersMaxTotal => 'contacts',
            self::WarehousesMaxTotal,
            self::ProductsMaxTotal => 'inventory',
        };
    }

    public function groupLabel(): string
    {
        return match ($this->groupKey()) {
            'sales' => 'Ventas',
            'purchases' => 'Compras',
            'contacts' => 'Contactos',
            'inventory' => 'Inventario',
        };
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_map(fn (self $limit): string => $limit->value, self::cases());
    }

    /**
     * @return list<array{key: string, label: string, limits: list<array{key: string, label: string, scope: string}>}>
     */
    public static function grouped(): array
    {
        $groups = [];

        foreach (self::cases() as $limit) {
            $groups[$limit->groupKey()] ??= [
                'key' => $limit->groupKey(),
                'label' => $limit->groupLabel(),
                'limits' => [],
            ];
            $groups[$limit->groupKey()]['limits'][] = [
                'key' => $limit->value,
                'label' => $limit->label(),
                'scope' => $limit->scope()->value,
            ];
        }

        return array_values($groups);
    }
}
