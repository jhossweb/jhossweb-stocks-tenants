<?php

declare(strict_types=1);

use App\Models\Plan;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Mapea las claves de límites heredadas a las claves del catálogo
     * `TenantLimit`. Las claves antiguas se conservan para no perder datos.
     */
    public function up(): void
    {
        $map = [
            'max_products' => 'products.max_total',
            'max_customers' => 'customers.max_total',
            'max_suppliers' => 'suppliers.max_total',
            'max_warehouses' => 'warehouses.max_total',
        ];

        Plan::query()->each(function (Plan $plan) use ($map): void {
            $limits = $plan->limits;

            if (! is_array($limits) || $limits === []) {
                return;
            }

            $changed = false;

            foreach ($map as $legacy => $current) {
                if (! array_key_exists($legacy, $limits) || array_key_exists($current, $limits)) {
                    continue;
                }

                $limits[$current] = $limits[$legacy];
                $changed = true;
            }

            if ($changed) {
                $plan->limits = $limits;
                $plan->save();
            }
        });
    }

    public function down(): void
    {
        // No se revierte: las claves heredadas se mantienen intactas.
    }
};
