<?php

declare(strict_types=1);

namespace App\Enum\Tenancy;

enum TenantFeature: string
{
    case SalesQuotes = 'sales.quotes';

    public function label(): string
    {
        return match ($this) {
            self::SalesQuotes => 'Cotizaciones',
        };
    }

    public function module(): TenantModule
    {
        return match ($this) {
            self::SalesQuotes => TenantModule::Sales,
        };
    }

    /**
     * @return array<string, array{label: string, features: list<array{key: string, label: string}>}>
     */
    public static function grouped(): array
    {
        $groups = [];

        foreach (self::cases() as $feature) {
            $module = $feature->module();
            $groups[$module->value] ??= [
                'label' => $module->label(),
                'features' => [],
            ];
            $groups[$module->value]['features'][] = [
                'key' => $feature->value,
                'label' => $feature->label(),
            ];
        }

        return $groups;
    }
}
