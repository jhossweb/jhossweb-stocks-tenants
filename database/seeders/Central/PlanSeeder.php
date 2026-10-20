<?php

declare(strict_types=1);

namespace Database\Seeders\Central;

use App\Enum\Tenancy\TenantModule;
use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        // Standard
        $standard = Plan::firstOrCreate([
            'code' => 'standard',
        ], [
            'name' => 'Standard',
            'description' => 'Ideal para pymes con operaciones básicas: inventario, ventas, compras, finanzas, gastos y nómina.',
            'billing_cycle' => 'monthly',
            'price_usd' => 35.00,
            'trial_days' => 14,
            'is_active' => true,
            'limits' => [
                'max_users' => 5,
                'max_products' => 1500,
                'max_customers' => 1000,
                'max_suppliers' => 500,
                'max_employees' => 25,
                'max_documents_month' => 1000,
                'max_warehouses' => 2,
                'max_branches' => 1,
                'storage_mb' => 1024,
            ],
        ]);

        // Sync standard modules (core only)
        $this->sync($standard, TenantModule::core());

        // Premium
        $premium = Plan::firstOrCreate([
            'code' => 'premium',
        ], [
            'name' => 'Premium',
            'description' => 'Incluye producción, reportes avanzados, facturación electrónica y mayor capacidad para crecer.',
            'billing_cycle' => 'monthly',
            'price_usd' => 69.00,
            'trial_days' => 14,
            'is_active' => true,
            'limits' => [
                'max_users' => 15,
                'max_products' => 5000,
                'max_customers' => 5000,
                'max_suppliers' => 2000,
                'max_employees' => 100,
                'max_documents_month' => 5000,
                'max_warehouses' => 5,
                'max_branches' => 3,
                'storage_mb' => 4096,
            ],
        ]);

        $premiumMods = array_merge(TenantModule::core(), [
            TenantModule::Production,
            TenantModule::AdvancedReports,
            TenantModule::ElectronicInvoice,
        ]);
        $this->sync($premium, $premiumMods);
    }

    private function sync(Plan $plan, array $modules): void
    {
        $values = [];
        foreach ($modules as $m) {
            $values[] = $m->value;
        }
        foreach (TenantModule::core() as $core) {
            if (! in_array($core->value, $values, true)) {
                $values[] = $core->value;
            }
        }
        $values = array_unique($values);
        DB::table('plan_modules')->where('plan_id', $plan->id)->delete();
        foreach ($values as $v) {
            try {
                TenantModule::from($v);
            } catch (\Throwable $e) {
                continue;
            }
            DB::table('plan_modules')->insert(['plan_id' => $plan->id, 'module_key' => $v]);
        }
    }
}
