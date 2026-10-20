<?php

declare(strict_types=1);

namespace App\Enum\Tenancy;

enum TenantModule: string
{
    // Core
    case Inventory = 'inventory';
    case Sales = 'sales';
    case Purchases = 'purchases';
    case Finance = 'finance';
    case Expenses = 'expenses';
    case Payroll = 'payroll';

    // Opt-in
    case Production = 'production';
    case AdvancedReports = 'advanced_reports';
    case ElectronicInvoice = 'electronic_invoice';

    public function label(): string
    {
        return match ($this) {
            self::Inventory => 'Inventario',
            self::Sales => 'Ventas',
            self::Purchases => 'Compras',
            self::Finance => 'Finanzas',
            self::Expenses => 'Gastos',
            self::Payroll => 'Nómina',
            self::Production => 'Producción y Recetas',
            self::AdvancedReports => 'Reportes avanzados',
            self::ElectronicInvoice => 'Facturación electrónica',
        };
    }

    public function isCore(): bool
    {
        return in_array($this, self::core(), true);
    }

    /**
     * @return list<self>
     */
    public static function core(): array
    {
        return [
            self::Inventory,
            self::Sales,
            self::Purchases,
            self::Finance,
            self::Expenses,
            self::Payroll,
        ];
    }

    /**
     * @return list<self>
     */
    public static function optIn(): array
    {
        return array_values(array_filter(self::cases(), fn (self $m) => ! $m->isCore()));
    }

    /**
     * @return list<string>
     */
    public static function optInKeys(): array
    {
        return array_map(fn (self $m) => $m->value, self::optIn());
    }
}
