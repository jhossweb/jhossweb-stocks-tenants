<?php

declare(strict_types=1);

namespace App\Services\Tenancy;

use App\DTOs\Tenancy\PlanData;
use App\Enum\Tenancy\TenantModule;
use App\Models\Plan;
use Illuminate\Support\Facades\DB;

final class PlanService
{
    public function create(PlanData $data): Plan
    {
        return DB::transaction(function () use ($data): Plan {
            $plan = new Plan([
                'code' => $data->code,
                'name' => $data->name,
                'description' => $data->description,
                'billing_cycle' => $data->billing_cycle,
                'price_usd' => $data->price_usd,
                'trial_days' => $data->trial_days,
                'is_active' => $data->is_active,
                'limits' => $data->limits,
                'features' => $data->features,
            ]);
            $plan->save();

            $moduleKeys = $data->module_keys !== [] ? $data->module_keys : TenantModule::core();
            $this->syncModules($plan, $moduleKeys);

            return $plan->refresh();
        });
    }

    public function update(Plan $plan, PlanData $data): Plan
    {
        return DB::transaction(function () use ($plan, $data): Plan {
            $plan->code = $data->code;
            $plan->name = $data->name;
            $plan->description = $data->description;
            $plan->billing_cycle = $data->billing_cycle;
            $plan->price_usd = $data->price_usd;
            $plan->trial_days = $data->trial_days;
            $plan->is_active = $data->is_active;
            $plan->limits = $data->limits;
            $plan->features = $data->features;
            $plan->save();

            $this->syncModules($plan, $data->module_keys);

            return $plan->refresh();
        });
    }

    public function delete(Plan $plan): bool
    {
        return (bool) $plan->delete();
    }

    /**
     * @param  array<int, string|TenantModule>  $moduleKeys
     */
    private function syncModules(Plan $plan, array $moduleKeys): void
    {
        $values = collect($moduleKeys)
            ->map(fn ($m) => $m instanceof TenantModule ? $m->value : (string) $m)
            ->filter()
            ->unique()
            ->values()
            ->all();

        // Asegurar core siempre
        foreach (TenantModule::core() as $core) {
            if (! in_array($core->value, $values, true)) {
                $values[] = $core->value;
            }
        }

        DB::table('plan_modules')->where('plan_id', $plan->id)->delete();

        foreach ($values as $v) {
            try {
                TenantModule::from($v);
            } catch (\Throwable $e) {
                continue;
            }
            DB::table('plan_modules')->insert([
                'plan_id' => $plan->id,
                'module_key' => $v,
            ]);
        }
    }
}
