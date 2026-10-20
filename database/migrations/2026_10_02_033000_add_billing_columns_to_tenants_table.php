<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            if (! Schema::hasColumn('tenants', 'rif')) {
                $table->string('rif')->nullable();
            }
            if (! Schema::hasColumn('tenants', 'admin_email')) {
                $table->string('admin_email')->nullable();
            }
            if (! Schema::hasColumn('tenants', 'admin_name')) {
                $table->string('admin_name')->nullable();
            }
            if (! Schema::hasColumn('tenants', 'trial_ends_at')) {
                $table->dateTime('trial_ends_at')->nullable();
            }
            if (! Schema::hasColumn('tenants', 'internal_notes')) {
                $table->text('internal_notes')->nullable();
            }
            if (! Schema::hasColumn('tenants', 'status_source')) {
                $table->string('status_source')->default('subscription');
            }
            if (! Schema::hasColumn('tenants', 'suspended_reason')) {
                $table->text('suspended_reason')->nullable();
            }
            if (! Schema::hasColumn('tenants', 'suspended_at')) {
                $table->dateTime('suspended_at')->nullable();
            }
            if (! Schema::hasColumn('tenants', 'suspended_by')) {
                $table->unsignedBigInteger('suspended_by')->nullable();
            }
            if (! Schema::hasColumn('tenants', 'schema_version')) {
                $table->unsignedInteger('schema_version')->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $columns = array_values(array_filter([
                'rif',
                'admin_email',
                'admin_name',
                'trial_ends_at',
                'internal_notes',
                'status_source',
                'suspended_reason',
                'suspended_at',
                'suspended_by',
                'schema_version',
            ], fn (string $column): bool => Schema::hasColumn('tenants', $column)));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
