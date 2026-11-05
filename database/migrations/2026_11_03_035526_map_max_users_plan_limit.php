<?php

declare(strict_types=1);

use App\Models\Plan;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Plan::query()->each(function (Plan $plan): void {
            $limits = $plan->limits;

            if (! is_array($limits) || ! isset($limits['max_users']) || array_key_exists('users.max_total', $limits)) {
                return;
            }

            $limits['users.max_total'] = (int) $limits['max_users'];
            $plan->limits = $limits;
            $plan->save();
        });
    }

    public function down(): void
    {
        // No se revierte: las claves heredadas se conservan.
    }
};
