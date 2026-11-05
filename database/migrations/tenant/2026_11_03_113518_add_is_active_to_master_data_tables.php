<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['products', 'categories', 'brands', 'warehouses', 'customers', 'suppliers'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->boolean('is_active')->default(true)->index();
            });
        }
    }

    public function down(): void
    {
        foreach (['products', 'categories', 'brands', 'warehouses', 'customers', 'suppliers'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropIndex(['is_active']);
                $table->dropColumn('is_active');
            });
        }
    }
};
