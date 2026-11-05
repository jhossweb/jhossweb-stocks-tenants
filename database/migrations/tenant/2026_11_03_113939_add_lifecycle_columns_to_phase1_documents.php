<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['sale_orders', 'expenses', 'payrolls', 'payroll_periods'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->boolean('is_active')->default(true)->index();
                $table->timestamp('voided_at')->nullable();
                $table->unsignedBigInteger('voided_by')->nullable();
                $table->string('void_reason', 500)->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['sale_orders', 'expenses', 'payrolls', 'payroll_periods'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropIndex(['is_active']);
                $table->dropColumn(['is_active', 'voided_at', 'voided_by', 'void_reason']);
            });
        }
    }
};
