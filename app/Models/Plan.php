<?php

declare(strict_types=1);

namespace App\Models;

use App\Enum\Tenancy\TenantFeature;
use App\Enum\Tenancy\TenantModule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class Plan extends Model
{
    use CentralConnection, HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'billing_cycle',
        'price_usd',
        'trial_days',
        'is_active',
        'limits',
        'features',
    ];

    protected function casts(): array
    {
        return [
            'price_usd' => 'decimal:2',
            'trial_days' => 'integer',
            'is_active' => 'boolean',
            'limits' => 'array',
            'features' => 'array',
        ];
    }

    /**
     * @return BelongsToMany<TenantModuleRecord, Plan, Pivot>
     */
    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(TenantModuleRecord::class, 'plan_modules', 'plan_id', 'module_key', 'module_key', 'module_key')
            ->withPivot([]);
    }

    public function hasModule(string|TenantModule $module): bool
    {
        $value = $module instanceof TenantModule ? $module->value : $module;

        return $this->planModules()->where('module_key', $value)->exists();
    }

    public function planModules()
    {
        return DB::table('plan_modules')
            ->where('plan_id', $this->id);
    }

    public function moduleKeys(): array
    {
        return $this->planModules()->pluck('module_key')->toArray();
    }

    /**
     * Determina si una funcionalidad/submódulo está habilitada en el plan.
     *
     * `features` almacena la lista de funcionalidades habilitadas. Si es
     * `null` se consideran todas habilitadas para mantener compatibilidad
     * con los planes existentes.
     */
    public function hasFeature(string|TenantFeature $feature): bool
    {
        $key = $feature instanceof TenantFeature ? $feature->value : $feature;
        $features = $this->features;

        if (! is_array($features)) {
            return true;
        }

        return in_array($key, $features, true);
    }

    /**
     * @return array<string, bool>
     */
    public function featureStates(): array
    {
        $states = [];

        foreach (TenantFeature::cases() as $feature) {
            $states[$feature->value] = $this->hasFeature($feature);
        }

        return $states;
    }
}
