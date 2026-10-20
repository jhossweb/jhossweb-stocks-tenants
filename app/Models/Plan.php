<?php

declare(strict_types=1);

namespace App\Models;

use App\Enum\Tenancy\TenantModule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\DB;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'billing_cycle',
        'price_usd',
        'trial_days',
        'is_active',
        'limits',
    ];

    protected function casts(): array
    {
        return [
            'price_usd' => 'decimal:2',
            'trial_days' => 'integer',
            'is_active' => 'boolean',
            'limits' => 'array',
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
}
